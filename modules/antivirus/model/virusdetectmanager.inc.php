<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Antivirus\Model;


use Antivirus\Model\Libs\Manul\MalwareInfo;
use Antivirus\Model\Orm\Event;
use RS\Config\Loader;

/**
 * Класс отвечает за циклическую проверку на наличие вирусов всех файлов системы.
 * Метод onCron() должен вызваться по крону. За каждый проход происходит
 * проверка определенного количества файлов (задается в настройках модуля)
 *
 * Class VirusDetectManager
 * @package Antivirus\Model
 */
class VirusDetectManager extends ResumableTask
{

    /**
     * @var \Antivirus\Config\File
     */
    private $config;

    /**
     * Массив путей (относительно корня проекта) к исключенным файлам
     * Ключи массива равны значениям массива (для ускорения доступа)
     *
     * @var array
     */
    private $excluded_files = array();

    /**
     * Жестко исключенные файлы, не подлежащие проверке антивирусом
     *
     * @var array
     */
    private $forever_excluded_files = array(
        'modules/antivirus/model/libs/manul/malware_db.xml',
        'storage/tmp/siteupdate/backup/modules/antivirus/model/libs/manul/malware_db.xml'
    );

    static private $instance;

    static public function getInstance()
    {
        if(self::$instance === null)
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    protected function __construct()
    {
        parent::__construct();
        $this->config            = Loader::byModule($this);
        $this->step_size         = $this->isIntensiveModeEnabled() ? (int)$this->config->antivirus_step_size_intensive : (int)$this->config->antivirus_step_size;
        $this->excluded_files    = ExcludedFileApi::getInstance()->getAllFiles();
        $this->excluded_files   += array_combine($this->forever_excluded_files, $this->forever_excluded_files);
    }



    /**
     * Возвращает время последнего завершения полного цикла проверки
     *
     * @return int
     */
    public function getLastCycleCompletedDate()
    {
        return (int) $this->state->last_cycle_completed_timestamp;
    }



    /**
     * Возвращает общее число файлов системы, подлежащих проверке
     *
     * @return int
     */
    public function getTotalCount()
    {
        // Если пусто закешированное значение, считаем заново

        if(!$this->state->total_files_count)
        {
            $this->state->total_files_count = Utils::getFilesCount(\Setup::$PATH, true);
        }

        return $this->state->total_files_count;
    }


    public function enableIntensiveMode($value)
    {
        $this->state->intensive_mode = (boolean)$value;

        if($value)
        {
            // Интенсивная проверка начинается с начала
            $this->position = $this->state->position = 0;

            // Для запуска пересчета общего числа файлов
            $this->state->total_files_count = 0;
            
            // Принудительно снимаем блокировку задачи
            $this->unlock();
        }
    }


    public function isIntensiveModeEnabled()
    {
        return (boolean) $this->state->intensive_mode;
    }


    /**
     * Метод выполняет проверку следующих $limit файлов
     *
     * @param int $offset
     * @param int $limit
     * @return int количество обработаных файлов
     */
    protected function doWork($offset, $limit)
    {
        $detector = new VirusDetector();
        $detector->setMaxFileSize((int)$this->config->antivirus_max_file_size_kb * 1024);

        $this->log(t("Начинаем проверку файлов... "));

        $result = $detector->scanFolder(\Setup::$PATH, $offset, $limit);

        $infected_files         = $result['infected_files'];
        $files_checked_count    = $result['files_checked_count'];

        if(!empty($infected_files))
        {
            // Сообщаем о найденных поврежденных файлах
            $this->log(t('Найдены зараженные файлы: ') . "\n\t" . join("\n\t", $this->getFilenamesOnly($infected_files)));
            $this->onInfectedFilesFound($infected_files);
        }
        else
        {
            $this->log("OK");
        }
        return $files_checked_count;
    }



    /**
     * Вызывается, когда найдены инфицированные файлы
     *
     * @param MalwareInfo[] $files
     */
    private function onInfectedFilesFound(array $files)
    {
        $api = new EventApi();

        $inserted_problems = array();

        foreach($files as $malwareInfo)
        {
            $rel_file = $malwareInfo->getRelativeFilename();

            // Пропускаем исключенные файлы
            if(isset($this->excluded_files[$rel_file]))
            {
                continue;
            }

            if(!$api->eventExists(Event::COMPONENT_ANTIVIRUS, Event::TYPE_PROBLEM, $rel_file))
            {
                $event = new Event();
                $event['component'] = Event::COMPONENT_ANTIVIRUS;
                $event['type'] = Event::TYPE_PROBLEM;
                $event['file'] = $rel_file;
                $event['dateof'] = date('Y-m-d H:i:s');
                $event->setDetails($malwareInfo);
                $event->insert();

                $inserted_problems[] = $event;
            }
        }

        // Если включена опция "Автоматически лечить зараженные файлы"
        if($this->config->antivirus_auto_recover)
        {
            $this->autoRecoverFoundProblems($inserted_problems);
        }

    }

    /**
     * Восстановить зараженные файлы
     *
     * @param Event[] $events
     */
    private function autoRecoverFoundProblems(array $events)
    {
        $server_api = ServerApi::getInstance();

        $paths = array();

        foreach($events as $event)
        {
            $paths[] = $event['file'];
        }

        $server_api->recoverFiles($paths);

        if(!$server_api->hasError())
        {
            $this->log(t("Файлы успешно восстановлены (%0)", array(join(', ', $paths))));
            foreach($events as $event)
            {
                $event['type'] = Event::TYPE_FIXED;
                $event->update();
            }
        }
        else
        {
            $this->log(t("В процессе восстановления файлов (%0) возникла ошибка: %1", array(join(', ', $paths), $server_api->getErrorsStr())));
        }
    }


    /**
     * Вызывается на завершение полного цикла проверки всех файлов системы
     * после проверки последнего файла в последнем модуле
     */
    protected function onFullCycleCompleted()
    {
        $this->log(t("Проверка на наличие вирусов заверешена"));

        // Сохранение даты завершения проверки
        $this->state->last_cycle_completed_timestamp = time();
        $this->enableIntensiveMode(false);
    }


    /**
     * @param MalwareInfo[] $malwareInfoList
     * @return string[]
     */
    private function getFilenamesOnly(array $malwareInfoList)
    {
        $ret = array();
        foreach($malwareInfoList as $malwareInfo)
            $ret[] = $malwareInfo->filename;

        return $ret;
    }


}