<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Antivirus\Model;


use Antivirus\Model\Orm\Event;
use RS\Config\Loader;
use RS\Module\Item;

/**
 * Класс отвечает за циклическую проверку подписей всех файлов системы.
 * Метод onCron() должен вызваться по крону. За каждый проход происходит
 * проверка определенного количества файлов (задается в настройках модуля)
 *
 * Class SignVerifyManager
 * @package Antivirus\Model
 */
class SignVerifyManager extends ResumableTask
{

    /**
     * @var \Antivirus\Config\File
     */
    private $config;


    /**
     * Массив объектов модулей в алфавитном порядке
     *
     * @var \RS\Module\Item []
     */
    private $all_modules = array();

    /**
     * Количества файлов в каждом из модулей
     *
     * @var int[]
     */
    private $modules_files_counts = array();

    /**
     * Массив путей (относительно корня проекта) к исключенным файлам
     * Ключи массива равны значениям массива
     *
     * @var array
     */
    private $excluded_files = array();

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
        $this->step_size         = $this->isIntensiveModeEnabled() ? (int)$this->config->signverify_step_size_intensive : (int)$this->config->signverify_step_size;
        $this->all_modules       = Utils::getSystemModules();
        $this->excluded_files    = ExcludedFileApi::getInstance()->getAllFiles();
    }


    /**
     * Подсчет количества файлов, подлежащих проверке.
     * Значения группируются по имени модуля.
     */
    private function calculateFilesCounts()
    {
        // Если уже было подсчитано ранее - ничего не делем

        if(!empty($this->modules_files_counts))
        {
            return;
        }

        if(!$this->state->modules_files_counts_serialized)
        {
            // Подсчет количества файлов для каждого модуля

            foreach ($this->all_modules as $key => $module)
            {
                $this->modules_files_counts[$key] = Utils::getSignedFilesCount($module);
            }

            $this->state->modules_files_counts_serialized = serialize($this->modules_files_counts);
        }
        else
        {
            $this->modules_files_counts = unserialize($this->state->modules_files_counts_serialized);
        }

    }




    /**
     * Вызывается, когда найдены файлы с неверной подписью
     *
     * @param array $files
     * @param Item $module
     */
    private function onCorruptedFilesFound(array $files, $module)
    {
        $api = new EventApi();

        $inserted_problems = array();

        foreach($files as $file)
        {
            $rel_file = Utils::toRelativePath($file, $module);

            // Пропускаем исключенные файлы
            if(isset($this->excluded_files[$rel_file]))
            {
                continue;
            }

            // Если проблема не была зарегистрирована ранее
            if(!$api->eventExists(Event::COMPONENT_INTEGRITY, Event::TYPE_PROBLEM, $rel_file))
            {
                $event = new Event();
                $event['component'] = Event::COMPONENT_INTEGRITY;
                $event['type'] = Event::TYPE_PROBLEM;
                $event['file'] = $rel_file;
                $event['dateof'] = date('Y-m-d H:i:s');
                $event->insert();

                $inserted_problems[] = $event;
            }
        }

        // Если включена опция "Автоматически восстанавливать поврежденные файлы"
        if($this->config->signverify_auto_recover)
        {
            $this->autoRecoverFoundProblems($inserted_problems);
        }

    }


    /**
     * Восстановить поврежденные файлы
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
        $this->calculateFilesCounts();

        return array_sum($this->modules_files_counts);
    }


    public function enableIntensiveMode($value)
    {
        $this->state->intensive_mode = (boolean)$value;

        if($value)
        {
            // Интенсивная проверка начинается с начала
            $this->position = $this->state->position = 0;

            // Для запуска пересчета общего числа файлов
            $this->state->modules_files_counts_serialized = null;
            
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
        $offset_info    = $this->getModuleOffsetByGlobalOffset($offset);

        $module         = $offset_info['module'];
        $module_offset  = $offset_info['module_offset'];

        $sign_verifier                          = new SignVerifier();
        $sign_verifier->signatures_file_path    = Utils::getSignaturesFilePath($module);

        $this->log(t("Начинаем проверку файлов модуля [%0]...", array($module->getName())));

        $verify_result = $sign_verifier->verifyModule(
            $module->getFolder(),
            $module_offset,
            $limit
        );

        $corrupted_files     = $verify_result['corrupted_files'];
        $broke_at            = $verify_result['broke_at'];
        $files_checked_count = $verify_result['files_checked_count'];


        if(!empty($corrupted_files))
        {
            // Сообщаем о найденных поврежденных файлах
            $this->log(t('Найдены поврежденные файлы: ') . "\n\t" . join("\n\t", $corrupted_files));
            $this->onCorruptedFilesFound($corrupted_files, $module);
        }
        else
        {
            $this->log("OK");
        }

        return $files_checked_count;
    }


    /**
     * Вызывается на завершение полного цикла проверки всех файлов системы
     * после проверки последнего файла в последнем модуле
     */
    protected function onFullCycleCompleted()
    {
        $this->log(t("Проверка подписей заверешена"));

        // Сохранение даты завершения проверки
        $this->state->last_cycle_completed_timestamp = time();
        $this->enableIntensiveMode(false);
    }


    /**
     * Трансляция глобального смещения в объект модуля и смещение в рамках данного модуля
     *
     * @param $offset
     * @return array массив из двух элементов - объект модуля и смещение
     * @throws \Exception
     */
    private function getModuleOffsetByGlobalOffset($offset)
    {
        $this->calculateFilesCounts();

        $sum = 0;
        foreach($this->modules_files_counts as $index => $count)
        {
            $sum += $count;
            if($sum > $offset)
            {
                return array(
                    'module' => $this->all_modules[$index],
                    'module_offset' => $offset - ($sum - $count)
                );
            }
        }
        
        //throw new \Exception(t('Offset (%0) должен быть меньше, чем общее число элементов (%1)',array($offset, $this->getTotalCount())));        
        return array(
            'module' => $this->all_modules[$index],
            'module_offset' => $sum
        );
    }


}