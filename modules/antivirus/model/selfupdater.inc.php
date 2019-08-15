<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Model;


use RS\Config\Loader;
use RS\Helper\PersistentStateHashStore;
use RS\Module\AbstractModel\BaseModel;

/**
 * Класс занимается проверкой обновлений модуля антивирус и его автоматическим обновлением.
 * Основной метод класса вызывается по крону, раз в минуту.
 * Проверка обновлений осущетвляется с заданным интервалом.
 *
 * Class SelfUpdater
 * @package Antivirus\Model
 */
class SelfUpdater extends BaseModel
{
    const INTERVAL              = 14400;    // Проверка обновления каждые 4 часа
    const ARCHIVE_TMP_FILE      = 'antivirus_update.zip';
    const DOWNLOAD_PART_SIZE    = 2000000;  // Размер единоразово загружаемой части архива в байтах

    /**
     * @var PersistentStateHashStore
     */
    private $state;

    static private $instance;

    static public function getInstance()
    {
        if(self::$instance === null)
        {
            self::$instance = new self;
        }
        return self::$instance;
    }


    public function __construct()
    {
        $this->state = new PersistentStateHashStore();
    }


    public function log($msg)
    {
        $ref = new \ReflectionClass($this);
        $out = "{$ref->getShortName()}: {$msg}";
        Utils::log($out);
    }

    /**
     * Метод вызывается по крону, каждую минуту
     */
    public function onCron()
    {
        // Если подошло время проверить наличие обновлений
        if(time() - self::INTERVAL > (int)$this->state->last_check_time)
        {
            $this->state->last_check_time = time();

            self::log(t("Проверка наличия обновлений антивируса... "));

            if($this->isNewVersionAvailable())
            {
                self::log(t("Обнаружена новая версия. Обновление..."));
                $this->doUpdate();
            }
            else
            {
                if(!$this->hasError())
                {
                    self::log(t("Обновление не требуется"));
                }
            }
        }

        if($this->hasError())
        {
            self::log(t('Ошибка проверки обновлений: %0', $this->getErrorsStr()));
        }

    }

    /**
     * Доступно ли обновление модуля Антивирус
     *
     * @return bool
     */
    private function isNewVersionAvailable()
    {

        $server_api = ServerApi::getInstance();
        $config = Loader::byModule($this);


        $versions = $server_api->getServerVersions(\Setup::$SCRIPT_TYPE);

        if($server_api->hasError())
        {
            foreach($server_api->getErrors() as $error)
                $this->addError($error);
            return false;
        }
        if (!isset($versions['antivirus'])) {
            return $this->addError(t('Среди обновлений нет модуля антивируса'));
        }
        $sever_version = $versions['antivirus'];
        $client_version = $config['version'];

        return !\RS\Helper\Tools::compareVersion($sever_version, $client_version);
    }

    /**
     * Загрузка и применение обновления
     */
    private function doUpdate()
    {
        $server_api = ServerApi::getInstance();
        $state = $this->state;
        $tmp_file = \Setup::$TMP_DIR.'/'.self::ARCHIVE_TMP_FILE;
        \RS\File\Tools::makePath(\Setup::$TMP_DIR);


        if($state->file_id === null)
        {
            $state->file_id = $server_api->getGetUpdateFile();
            @unlink($tmp_file);
        }

        if($state->file_id)
        {
            $res = $server_api->downloadUpdatePackage($state->file_id, self::DOWNLOAD_PART_SIZE, (int)$state->offset);

            if($server_api->hasError())
            {
                foreach($server_api->getErrors() as $error)
                    $this->addError($error);
                return false;
            }

            if($res === false)
            {
                // Загрузка окончена
                $state->file_id = null;
                $state->offset = null;
                $this->extractArchive();
                
                //Переустанавливаем модуль
                $module = new \RS\Module\Item('antivirus');
                if ($result = $module->install()) {
                    self::log(t("Обновление антивируса завершено"));
                } else {
                    self::log(t('Во время переустановки модуля возникли ошибки: %0', array(implode(',', $result))));
                }
            }
            else
            {
                // Загрузка очередной порции
                file_put_contents($tmp_file, $res, FILE_APPEND);
                $state->offset += strlen($res);
                self::log(t('Загружена очередная порция архива, offset: %0', array($state->offset)));
            }
        }
        return true;
    }

    /**
     * Распаковать архив с обновлением и переписать существующие файлы
     */
    private function extractArchive()
    {
        $tmp_file = \Setup::$TMP_DIR.'/'.self::ARCHIVE_TMP_FILE;
        $zip = new \ZipArchive;
        if ($zip->open($tmp_file) === TRUE)
        {
            $zip->extractTo(\Setup::$PATH);
            $zip->close();
        }
        else
        {
            $this->addError(t('Не удалось открыть архив: ') . $tmp_file);
        }

        @unlink($tmp_file);
    }

}