<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Model;


use RS\Helper\Log;
use RS\Module\AbstractModel\BaseModel;
use RS\Module\Item;
use RS\File\Tools as FileTools;

class ServerApi extends BaseModel
{
    const REQUEST_TIMEOUT       = 50;      // Таймаут запроса в секундах
    const DOWNLOAD_PART_SIZE    = 2000000; // Размер единоразово загружаемой части архива в байтах

    const LOG_DIR = '/logs';
    const LOG_FILE = '/antivirus_server_requests.log';

    /**
     * @var Log
     */
    public $log;

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
        $this->log = Log::file(\Setup::$PATH.\Setup::$STORAGE_DIR.self::LOG_DIR.self::LOG_FILE);
        FileTools::makePrivateDir(\Setup::$PATH.\Setup::$STORAGE_DIR.self::LOG_DIR);
        
        $this->log->setMaxLength(1000*1000);
        $this->log->append(t("\n\nСоздание объекта ServerApi"));
    }

    /**
     * Выполняет POST запрос к серверу обновления
     *
     * @param array $params
     * @return false|string
     * @internal param mixed $url
     */
    function requester($params)
    {
        $params = $params + $this->getRequestVars();

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'ignore_errors' => true,
                'timeout' => self::REQUEST_TIMEOUT,
                'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
                'content' => http_build_query($params),
            ),
        ));
        $result = @file_get_contents(\Setup::$UPDATE_URL, false, $context);

        $this->log->append("Request Params: " . print_r($params, true));
        $this->log->append("Response: " . $result);
        
        if ($result === false) {
            return $this->addError(t('Невозможно соедениться с сервером обновлений. Попробуйте повторить попытку позже'));
        }

        $json = false;
        foreach($http_response_header as $header)
        {
            if(strpos($header, 'Content-Type: text/html') !== false)
            {
                $json = true;
            }
        }


        if ($json && $result !== false) {
            $result = json_decode($result);

            if(is_object($result) && !$result->success)
            {
                $this->addError($result->error);
                return false;
            }
        }

        return $result;
    }

    /**
     * Подготавливает массив обязательных параметров для обращения к серверу обновлений
     *
     * @return array
     */
    private function getRequestVars()
    {
        $licenses = array_keys(__GET_LICENSE_LIST());
        return array(
            'licenses' => $licenses,
            'channel' => \Setup::$UPDATE_CHANNEL,
            'product' => \Setup::$SCRIPT_TYPE,
            'need_product' => \Setup::$SCRIPT_TYPE,
            'copy_id' => COPY_ID,
            'install_id' => INSTALL_ID,
            'lang' => \RS\Language\Core::getCurrentLang()
        );
    }

    /**
     * Загрузить часть архива с оригинальными файлами с сервера readyscript.ru.
     * Метод должен вызываться до тех пор, пока он не вернет false.
     * Все части архива должны быть склеены вместе.
     *
     * @param array $files массив путей к файлам (относительно корня проекта, без первого слеша)
     * @param int $limit размер загружаемой части в байтах
     * @param int $offset смещение от начала архива
     * @return false|string часть архива
     */
    public function downloadRecoverFiles(array $files, $limit, $offset)
    {
        $response = $this->requester(array(
            'do' => 'downloadRecoverFiles',
            'files' => $files,
            'limit' => $limit,
            'offset' => $offset,
        ));
        return $response === '' ? false : $response;
    }


    /**
     * Загрузить оригинальное содержимое файла с сервера readyscript.ru
     *
     * @param string $file путь к файлу относительно корня проекта (без ведущего слеша)
     * @return false|string
     */
    public function downloadOneFileContent($file)
    {
        $temp_zip_file = \Setup::$TMP_DIR . '/downloaded_' . sha1($file) . '.zip';
        @unlink($temp_zip_file);

        $limit = self::DOWNLOAD_PART_SIZE;
        $offset = 0;

        // Загрузка

        while(($response = $this->downloadRecoverFiles(array($file), $limit, $offset)) !== false)
        {
            file_put_contents($temp_zip_file, $response, FILE_APPEND);
            $offset += strlen($response);
        }

        if($this->hasError()) return false;

        // Распаковка

        $zip = new \ZipArchive;
        $content = false;
        if ($zip->open($temp_zip_file) === TRUE)
        {
            $content = $zip->getFromIndex(0);
            $zip->close();
        }
        else
        {
            $this->addError(t('Не удалось открыть архив: ') . $temp_zip_file);
        }

        @unlink($temp_zip_file);
        return $content;
    }
    
    /**
    * Удаляет зараженные файлы
    * 
    * @return bool
    */
    public function deleteFiles(array $files)
    {
        foreach($files as $file) {
            $file_path = \Setup::$PATH.'/'.ltrim($file, '/');
            if (file_exists($file_path)) {
                if (!@unlink($file_path)) {
                    $this->addError(t('Не удалось удалить файл %0', $file));
                }
            }
        }
        return !$this->hasError();
    }

    /**
     * Восстановление файлов в один шаг.
     * Может делать несколько запросов к серверу
     * в процессе загрузки архива
     *
     * @param array $files
     */
    public function recoverFiles(array $files)
    {
        $versions_compatible = $this->isServerFilesVersionsCompatible($files);

        if(!$versions_compatible) return false;

        $temp_zip_file = \Setup::$TMP_DIR . '/downloaded_' . sha1(join('|',$files)) . '.zip';
        @unlink($temp_zip_file);

        $limit = self::DOWNLOAD_PART_SIZE;
        $offset = 0;

        // Загрузка

        while(($response = $this->downloadRecoverFiles($files, $limit, $offset)) !== false)
        {
            file_put_contents($temp_zip_file, $response, FILE_APPEND);
            $offset += strlen($response);
        }

        if($this->hasError()) return;

        // Распаковка

        $zip = new \ZipArchive;

        if ($zip->open($temp_zip_file) === TRUE)
        {
            $ok = @$zip->extractTo(\Setup::$PATH);

            if(!$ok)
            {
                $this->addError(t('Не удалось записать файлы'));
            }

            $zip->close();
        }
        else
        {
            $this->addError(t('Не удалось открыть архив: ') . $temp_zip_file);
        }

        @unlink($temp_zip_file);
    }

    /**
     * Восстановление файлов в несколько шагов.
     * За один раз делает один запрос к серверу.
     *
     * @param array $files
     * @return int|boolean true в случае завершения восстановления, false в случае ошибки, число загруженных байт, если восстановление еще в процессе
     */
    public function recoverFilesBySteps(array $files)
    {
        $versions_compatible = $this->isServerFilesVersionsCompatible($files);

        if(!$versions_compatible)
        {
            $this->addError(t('Восстановление невозможно. Ваша версия системы устарела. Обновите систему'));
            return true;
        }

        $temp_zip_file = \Setup::$TMP_DIR . '/downloaded_' . sha1(join('|',$files)) . '.zip';

        $limit = self::DOWNLOAD_PART_SIZE;
        $offset = file_exists($temp_zip_file) ? filesize($temp_zip_file) : 0;

        // Загрузка
        if(($response = $this->downloadRecoverFiles($files, $limit, $offset)) !== false)
        {
            file_put_contents($temp_zip_file, $response, FILE_APPEND);
            $offset += strlen($response);
            // Загрузка еще не завершена, возвращаем общее количество загруженных байт
            return $offset;
        }

        // Если загрузка завершена

        if($this->hasError()) return false;

        // Распаковка

        $zip = new \ZipArchive;

        if ($zip->open($temp_zip_file) === TRUE)
        {
            $ok = @$zip->extractTo(\Setup::$PATH);

            if(!$ok)
            {
                $this->addError(t('Не удалось записать файлы'));
            }

            $zip->close();
        }
        else
        {
            $this->addError(t('Не удалось открыть архив: ') . $temp_zip_file);
        }

        @unlink($temp_zip_file);

        return true;
    }


    /**
     * Подготавливает информацию о том, какие модули могут быть обновлены
     *
     * @param string $product строковый идентификатор текущей комплектации (например Shop.Full)
     * @return bool возвращает массив в случае успеха, иначе - false
     */
    function getServerVersions( $product )
    {
        $params = array(
            'do' => 'getServerVersions',
            'need_product' => $product,
            'client_versions' => $this->getMyVersions()
        );
        $response = $this->requester($params);
        
        if ($response === false) return false;

        if (!isset($response->versions))
        {
            return $this->addError(t('Не удалось получить версии модулей на сервере'));
        }


        $versions = array();

        foreach($response->versions as $key=>$value)
        {
            if($key{0} == '@') $key = substr($key, 1);
            $versions[$key] = $value->version;
        }

        return $versions;

    }


    /**
     * Возвращает массив со списком версий текущей системы
     */
    function getMyVersions()
    {
        $versions = array();

        // версии модулей
        $mod_manager = new \RS\Module\Manager();
        $modules = $mod_manager->getList();

        foreach($modules as $name => $module) {
            $config = $module->getConfig();
            $versions[$name] = $config['version'];
        }
        return $versions;
    }


    /**
     * Получить идентификатор файла обновления антивируса
     *
     * @return bool|string
     */
    function getGetUpdateFile()
    {
        $params = array(
            'do' => 'getUpdateFile',
            'items' => array('antivirus'),
        );
        $response = $this->requester($params);

        if ($response === false || !isset($response->file_id)) {
            return $this->addError(t('Невозможно соедениться с сервером обновлений. Попробуйте повторить попытку позже'));
        }

        return $response->file_id;
    }

    /**
     * Загрузить часть архива с обновлением модуля Антивирус
     * Метод должен вызываться до тех пор, пока он не вернет false.
     * Все части архива должны быть склеены вместе.
     *
     * @param string $file_id идентификатор файла с обновлением
     * @param int $limit размер загружаемой части в байтах
     * @param int $offset смещение от начала архива
     * @return false|string часть архива
     */
    public function downloadUpdatePackage($file_id, $limit, $offset)
    {
        $response = $this->requester(array(
            'do' => 'downloadUpdatePackage',
            'type' => 'core',
            'file_id' => $file_id,
            'length' => $limit,
            'offset' => $offset,
        ));
        return $response === '' ? false : $response;
    }

    /**
     * Производит сверку серверных версий модулей с версиями текущей системы.
     * Если хотябы один из модулей для данного набора файлов несовместим - возвращает false
     *
     * @param $files
     * @return bool возвращает true, если для всех переданных файлов версии совпадают
     */
    public function isServerFilesVersionsCompatible($files)
    {
        $modules = array();

        // Составляем список модулей исходя из списка файлов
        foreach($files as $file)
        {
            $is_signed = Utils::isFileSigned($file);
            $is_sig_db = Utils::isFileSignaturesDatabase($file);

            if($is_signed || $is_sig_db)
            {
                $module = Utils::getModuleByFile($file);
                $modules[$module->getName()] = $module;
            }
        }

        $versions = array();

        foreach($modules as $module)
        {
            $versions[$module->getName()] = Utils::getModuleVersion($module);
        }

        $server_versions = $this->getServerVersions(\Setup::$SCRIPT_TYPE);
        
        if ($server_versions === false) return false;

        foreach($versions as $key => $value)
        {
            if(!isset($server_versions[$key]))
            {
                return $this->addError(t('На сервере отсуствует модуль\'%0\'', $key));
            }
            if($server_versions[$key] !== $value)
            {
                return $this->addError(t('Версия модуля \'%0\' %1 не является последней (%2)', array($key, $value, $server_versions[$key])));
            }
        }

        return true;
    }

}