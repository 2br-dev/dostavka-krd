<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Controller\Front;

use Exchange\Config\ModuleRights;
use Exchange\Model\Api as ExchangeApi;
use Exchange\Model\BasicAuth as ExchangeBasicAuth;
use Exchange\Model\Orm\History;
use Exchange\Model\Log;
use Exchange\Model\Exception as ExchangeException;
use Exchange\Model\Task;
use RS\Application\Auth as AppAuth;
use RS\Config\Loader as ConfigLoader;
use RS\Controller\Front;
use RS\Event\Manager as EventManager;
use RS\File\Tools as FileTools;
use RS\Site\Manager as SiteManager;
use Site\Model\Orm\Site;

class Gate extends Front
{
    const LOCK_FILE = '/storage/locks/exchange';
    const LOCK_EXPIRE_INTERVAL = 300;

    /** @var History */
    private $historyItem;
    private $startTime = 0;

    protected $config;

    /** @var ExchangeApi */
    public $api;

    function init()
    {
        @set_time_limit(0);
        $this->startTime = microtime(true);

        $site_id = $this->url->get('site_id', TYPE_INTEGER, SiteManager::getSiteId());
        SiteManager::setCurrentSite(new Site($site_id));

        // Перезагружаем конфиг (на случай если если был передан site_id)
        $this->config = ConfigLoader::byModule($this);
        $this->config->load();

        $this->api = ExchangeApi::getInstance();

        // Сохраняем параметры запроса в лог-таблицу

        if ($this->config->use_log) {
            $this->historyItem = new History();
            $this->historyItem['dateof'] = date('c');
            $this->historyItem['query'] = $_SERVER['REQUEST_URI'];
            $this->historyItem['method'] = $_SERVER['REQUEST_METHOD'];
            $this->historyItem['type'] = $this->url->get("type", TYPE_STRING);
            $this->historyItem['mode'] = $this->url->get("mode", TYPE_STRING);
            $this->historyItem['postsize'] = @$_SERVER['CONTENT_LENGTH'];
            $this->historyItem->insert();
        }

        Log::setEnable($this->config->use_log);
        History::removeOldItems();
    }

    /**
     * Все запросы из 1C проходят через этот метод
     *
     */
    function actionIndex()
    {
        Log::w(t("Запрос ") . $_SERVER['REQUEST_URI']);
        Log::indentInc();
        $this->wrapOutput(false);
        $type = $this->url->get("type", TYPE_STRING);
        $mode = $this->url->get("mode", TYPE_STRING);
        // Формируем имя метода в формате (get|post)(catalog|sale)(init|file|checkauth|import|query)
        $method = strtolower($_SERVER['REQUEST_METHOD']) . "{$type}{$mode}";

        $config_catalog = ConfigLoader::byModule('catalog');
        if ($config_catalog['inventory_control_enable']) {
            $response = $this->failure(t('Включен складской учет, обмен с 1С невозможен'));
            Task\TaskQueue::clearAll();
            return $response;
        }

        try {
            if ($this->isLocked()) {
                throw new ExchangeException(t("Запущен другой обмен"), ExchangeException::CODE_EXCHANGE_LOCKED);
            }
            if (!method_exists($this, $method)) {
                throw new ExchangeException(t("Неизвестная команда"), ExchangeException::CODE_UNKNOWN_COMMAND);
            }
            // Вызываем приватный метод текущего контроллера 
            $response = $this->{$method}();
        } catch (\Exception $e) {
            Log::w(t("[Брошено исключение] {$e->getMessage()} \n{$e->getTraceAsString()}"));
            ExchangeApi::removeSessionIdFile(); // Удалим сессионый файл
            // Выводим сообщение об ошибке в 1C
            $response = $this->failure($e->getMessage());
            // Ощичаем очередь задач
            Task\TaskQueue::clearAll();
        }

        if ($this->config->use_log) {
            // Дописываем данные ответа сервера в лог-объект
            $this->historyItem['response'] = $response;                            // Ответ сервера
            $this->historyItem['duration'] = microtime(true) - $this->startTime;   // Продолжительность обработки запроса
            $this->historyItem['memory_peak'] = memory_get_peak_usage();           // Израсходовано памяти
            $this->historyItem->update();
        }

        return $response;
    }

    /**
     * Авторизация при обмене товарами
     */
    private function getCatalogCheckauth()
    {
        $auth = new ExchangeBasicAuth();
        $ok = AppAuth::login($auth->getUser(), $auth->getPass());
        if (!$ok) {
            throw new ExchangeException(AppAuth::getError(), ExchangeException::CODE_AUTH_ERROR);
        }
        $this->checkUserRights();

        return "success\n" . session_name() . "\n" . ExchangeApi::getSessionId(); //успешно/имя_куки/значение_куки
    }

    /**
     * Иницализация обмена товарами
     */
    private function getCatalogInit()
    {
        // Проверяем авторизацию и блокируем обмен
        $this->checkAuth();
        $this->lock();
        $config = $this->getModuleConfig();
        // Очищаем папку для импорта/экспорта
        if (!$this->url->get('dont_clear', TYPE_INTEGER)) {
            $this->api->clearExchangeDirectory('import');
        }

        $use_zip = (bool)$config['use_zip'];
        if (!$this->api->isZipAvailable()) {
            $use_zip = false;
        }

        $out = array();
        $out[] = "zip=" . ($use_zip ? "yes" : "no");
        $out[] = "file_limit=" . $config['file_limit'];
        return join("\n", $out);
    }

    /**
     * Сохранение файла
     */
    private function postCatalogFile()
    {
        $this->checkInit();
        $filename = $this->url->get("filename", TYPE_STRING);
        $postdata = file_get_contents("php://input");
        $this->api->saveUploadedFile($filename, $postdata);
        $out = "success";
        return $out;
    }

    /**
     * Псевдоним для postCatalogFile
     */
    private function putCatalogFile()
    {
        return $this->postCatalogFile();
    }

    /**
     * Импорт XML-файлов каталога
     */
    private function getCatalogImport()
    {
        $this->checkInit();
        $config = $this->getModuleConfig();
        $filename = $this->url->get("filename", TYPE_STRING);
        $max_exec_time = (int)$config['catalog_import_interval'];

        $taskqueue = new Task\TaskQueue();
        $taskBefore = $taskqueue->addTask("t1", new Task\BeforeImportTask($filename));
        $taskImport = $taskqueue->addTask("t2", new Task\ImportTask($filename));
        $taskAfter1 = $taskqueue->addTask("t3", new Task\AfterImport\Products($filename));
        $taskAfter2 = $taskqueue->addTask("t4", new Task\AfterImport\MultiOffers($filename));
        $taskAfter3 = $taskqueue->addTask("t5", new Task\AfterImport\Groups($filename));

        $taskqueue->exec($max_exec_time);

        if ($taskqueue->isCompleted()) {
            $out = "success";

            //По завершению всего импорта
            if (preg_match('/offer/iu', $filename)) {
                ExchangeApi::removeSessionIdFile(); //Удалим сессионный файл
                $this->unlock(); // Разблокируем обмен
                EventManager::fire('exchange.gate.afterimport.all'); //Вызовем хук окончания импорта
            }
        } else {
            if ($this->config->use_log) {
                $this->historyItem['readed_nodes'] = $taskImport->getOffset();
                $this->historyItem->update();
            }
            $out = "progress";
        }

        return $out;
    }

    /**
     * Авторизация при обмене заказами
     */
    private function getSaleCheckauth()
    {
        // Очищаем папку для импорта/экспорта
        $this->api->clearExchangeDirectory('orders');
        return $this->getCatalogCheckauth();
    }

    /**
     * Иницализация обмена заказами
     */
    private function getSaleInit()
    {
        // Проверяем авторизацию и блокируем обмен
        $this->checkAuth();
        $this->lock('orders');
        $config = $this->getModuleConfig();
        // Очищаем папку для импорта/экспорта
        $this->api->clearExchangeDirectory('orders');

        $use_zip = (bool)$config['use_zip'];
        if (!$this->api->isZipAvailable()) {
            $use_zip = false;
        }

        $out = array();
        $out[] = "zip=" . ($use_zip ? "yes" : "no");
        $out[] = "file_limit=" . $config['file_limit'];
        return join("\n", $out);
    }

    /**
     * Отдает заказы в виде XML
     */
    private function getSaleQuery()
    {
        // getSaleQuery вызывается без инициализации, проверяем авторизацию
        $this->checkAuth();
        header("Content-type: text/xml; charset=windows-1251");
        $xml = $this->api->createSalesXML($this->config->sale_export_statuses);
        $xml = preg_replace("/^\<\?.+?\>/", "", $xml);
        $xml = '<?xml version="1.0" encoding="windows-1251" ?>' . @iconv('utf-8', 'windows-1251//TRANSLIT//IGNORE', $xml);
        if (!is_dir($this->api->getDir('export'))) {
            @mkdir($this->api->getDir('export'), \Setup::$CREATE_DIR_RIGHTS, true);
        }
        file_put_contents($this->api->getDir('export') . DS . "last_orders_export.xml", $xml);
        return $xml;
    }

    /**
     * Импорт заказов
     */
    private function postSaleFile()
    {
        $config = ConfigLoader::byModule($this, SiteManager::getSiteId());
        if ($config['dont_check_sale_init']) {
            $this->checkAuth();
            $this->api->clearExchangeDirectory('orders'); // Очищаем папку для импорта чтобы файлы не налазили друг не друга
        } else {
            $this->checkInit('orders');
        }
        $filename = $this->url->get("filename", TYPE_STRING);
        $postdata = file_get_contents("php://input");
        $this->api->saveUploadedFile($filename, $postdata, 'orders');
        $this->api->saleImport($filename);
        $this->unlock('orders'); // Удалим блокировку обмена
        $out = "success";
        return $out;
    }

    //////////////!!!!!!!!!!!!!!!!
    private function getSaleFile()
    {
        if (ConfigLoader::byModule($this, SiteManager::getSiteId())->dont_check_sale_init) {
            $this->checkAuth();
        } else {
            $this->checkInit('orders');
        }
        $filename = $this->url->get("filename", TYPE_STRING);

        $this->api->saleImport($filename);
        $out = "success";
        return $out;
    }


    /**
     * Уведомление из 1C о том, что она успешно завершила импорт заявок
     *
     */
    private function getSaleSuccess()
    {
        $out = "success";
        return $out;
    }

    /**
     * Стандартный способ сообщить об ошибке 1C
     *
     * @param string $text
     * @return string
     */
    private function failure($text)
    {
        ExchangeApi::removeSessionIdFile(); //Удалим сессионый файл
        return "failure\n" . iconv('utf-8', 'windows-1251', $text);
    }

    /**
     * Проверяет у текущего пользователя права на запуск обмена
     * В случае неудачи - бросает исключение
     *
     * @return void
     * @throws ExchangeException
     */
    private function checkUserRights()
    {
        $user = AppAuth::getCurrentUser();
        $has_rights = $user->checkModuleRight("exchange", ModuleRights::RIGHT_EXCHANGE);
        if (!$has_rights) {
            throw new ExchangeException(t("Недостаточно прав для выполнения обмена данными"), ExchangeException::CODE_RIGHTS_ERROR);
        }
    }

    /**
     * Проверяет авторизацию пользователя
     * В случае неудачи - бросает исключение
     *
     * @return void
     * @throws ExchangeException
     */
    private function checkAuth()
    {
        $is_auth = AppAuth::isAuthorize();
        if (!$is_auth) {
            throw new ExchangeException(t("Не авторизованный запрос"), ExchangeException::CODE_NOT_AUTORIZED);
        }
        $this->checkUserRights();
    }

    /**
     * Возвращает полный путь к файлу блокировки
     *
     * @param string $type - тип файлов
     * @return string
     */
    private function lockFile($type = 'import')
    {
        return \Setup::$PATH . self::LOCK_FILE . '_' . $type;
    }

    /**
     * Проверяет был ли инициализирован обмен
     * В случае неудачи - бросает исключение
     *
     * @param string $type - тип файлов
     * @return void
     * @throws ExchangeException
     */
    private function checkInit($type = 'import')
    {
        if (!file_exists($this->lockFile($type))) {
            throw new ExchangeException(t("Обмен не инициализирован"), ExchangeException::CODE_NOT_INIT);
        }
    }

    /**
     * Создает файл блокировки, препятствующий одновременному запуску второго обмена
     *
     * @param string $type - тип файлов
     * @return void
     */
    private function lock($type = 'import')
    {
        $lock_file = $this->lockFile($type);
        FileTools::makePath($lock_file, true);
        file_put_contents($lock_file, session_name() . "\n" . ExchangeApi::getSessionId());
    }

    /**
     * Удаляет файл блокировки
     *
     * @param string $type - тип файлов
     * @return void
     */
    private function unlock($type = 'import')
    {
        @unlink($this->lockFile($type));
    }

    /**
     * Возвращает true, если другой обмен уже запущен
     *
     * @param string $type - тип файлов
     * @return bool
     */
    private function isLocked($type = 'import')
    {
        $lock_file = $this->lockFile($type);

        if (file_exists($lock_file)) {
            // Если файл блокировки слишком давно не перезаписовался, то удаляем его
            if (time() > filemtime($lock_file) + self::LOCK_EXPIRE_INTERVAL) {
                @unlink($lock_file);
            } else {
                if (file_get_contents($lock_file) == session_name() . "\n" . ExchangeApi::getSessionId()) {
                    touch($lock_file);
                } else {
                    return true;
                }
            }
        }
        return false;
    }
}
