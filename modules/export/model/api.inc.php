<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Export\Model;

use Export\Model\ExportType\AbstractType as AbstractExportType;
use Export\Model\Orm\ExportProfile;
use RS\Application\Application;
use RS\Event\Exception as EventException;
use RS\Module\AbstractModel\EntityList;
use RS\Orm\Exception as OrmException;
use RS\Orm\Request as OrmRequest;

class Api extends EntityList
{
    private $basedir;       // Корневая папка модуля

    public static $inst = null;
    public static $types;

    public function __construct()
    {
        parent::__construct(new ExportProfile(), array(
            'multisite' => true,
            'alias_field' => 'alias',
        ));

        $this->basedir = \Setup::$ROOT . \Setup::$STORAGE_DIR . DS . 'export';
        if (!is_dir($this->basedir)) {
            mkdir($this->basedir, \Setup::$CREATE_DIR_RIGHTS, true);
        }
    }

    static public function getInstance()
    {
        if (self::$inst == null) {
            self::$inst = new self();
        }
        return self::$inst;
    }

    /**
     * Получить экспортированные данные для данного профиля
     * Кеширует результат в файле
     *
     * @param ExportProfile $profile
     * @return void
     */
    function printExportedData(ExportProfile $profile)
    {
        $cache_file = $profile->getCacheFilePath();
        // Если установлено "время жизни"
        if ($profile['life_time'] > 0) {
            $life_time_in_sec = $profile['life_time'] * 24 * 60 * 60;
            // Если время жизни еще не истекло
            if (file_exists($cache_file) && (time() < filemtime($cache_file) + $life_time_in_sec)) {
                readfile($cache_file);
                return;
            }
        }

        // Экспортируем данные в файл
        $profile->export();

        // Отправляем содержимое файла на вывод
        Application::getInstance()->headers->cleanCookies();
        readfile($cache_file);
    }

    /**
     * Возвращает полный путь к файлу, содержащему экспортированные данные
     *
     * @param ExportProfile $profile
     * @return string
     */
    function getCacheFilePath(ExportProfile $profile)
    {
        return $this->basedir . DS . 'site' . $profile['site_id'] . '_' . $profile['class'] . '_' . $profile['id'] . '.cache';
    }

    /**
     * Возвращает объекты типов экспорта
     *
     * @return AbstractExportType[]
     * @throws EventException
     * @throws \Exception
     */
    function getTypes()
    {
        if (self::$types === null) {
            $event_result = \RS\Event\Manager::fire('export.gettypes', array());
            $list = $event_result->getResult();
            self::$types = array();
            foreach ($list as $type_object) {
                if (!($type_object instanceof ExportType\AbstractType)) {
                    throw new \Exception(t('Тип экспорта должен реализовать интерфейс \Export\Model\ExportType\AbstractType'));
                }
                self::$types[$type_object->getShortName()] = $type_object;
            }
        }

        return self::$types;
    }

    /**
     * Возвращает массив ключ => название типа
     *
     * @return array
     * @throws \Exception
     */
    static public function getTypesAssoc()
    {
        $_this = new self();
        $result = array();
        foreach ($_this->getTypes() as $key => $object) {
            $result[$key] = $object->getTitle();
        }
        return $result;
    }

    /**
     * Возвращает объект экспорта доставки по идентификатору
     *
     * @param string $name - имя типа
     * @return AbstractExportType|null
     * @throws \Exception
     */
    static public function getTypeByShortName($name)
    {
        $_this = new self();
        $list = $_this->getTypes();
        return isset($list[$name]) ? $list[$name] : null;
    }

    /**
     * Возвращает объект экспорта по типа класса и идентификатору
     *
     * @param string $class - класс типа объекта
     * @param string $alias - идентификатор или alias
     * @param integer $site_id - id сайта
     *
     * @return ExportProfile|false
     * @throws OrmException
     */
    function getObjectByAliasAndType($class, $alias, $site_id)
    {
        return OrmRequest::make()
            ->from(new ExportProfile())
            ->where(array(
                'site_id' => $site_id,
                'class' => $class
            ))
            ->where("(alias = '#alias' OR (alias = '' AND id='#alias'))", array(
                'alias' => $alias
            ))
            ->object();
    }
}
