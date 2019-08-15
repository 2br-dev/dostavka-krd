<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Importers;

use Exchange\Model\Exception;
use RS\Config\Loader as ConfigLoader;

/**
 * Абстрактный класс Импортера
 *
 */
abstract class AbstractImporter
{
    // Шаблон XML-пути. Напрмер '/Классификатор\/Свойства\/Свойство$/i';
    static public $pattern = "";

    static public $title = "";

    protected $reader = null;
    protected $stack = array();
    protected $attributes_stack = array();

    private $simpleXML = null;

    /**
     * Соответсвует ли данный импортер данному тэгу.
     * В случае соотвествия возвращает true.
     * Метод вызывается для каждого тэга XML-файла.
     *
     * @param array $stack - стэк тэгов. Первый элемент массива - корневой тэг. Последний - текущий тэг
     * @param array $attributes_stack массив формата: $arr['ИмяРодТэга']['ИмяАттрибута'] = 'ЗначениеАттрибута'
     * @return bool
     */
    static function match($stack, $attributes_stack)
    {
        // Получаем версию схемы. Например: 2.04
        $scheme_version = self::getParentAttributeStatic('КоммерческаяИнформация', 'ВерсияСхемы', $stack, $attributes_stack);

        $pattern = static::$pattern;

        // Если шаблон XML-пути представляет собой не строку а массив строк, для разных версий схемы
        if (is_array($pattern)) {
            $pattern = isset(static::$pattern[$scheme_version]) ? static::$pattern[$scheme_version] : reset($pattern);
        }

        // Склеиваем путь из стэка тэгов
        $path = join("/", $stack);

        // Возвращем true, если склеенный путь соответсвует шаблону
        return preg_match($pattern, $path);
    }

    /**
     * Возвращает название импортёра
     *
     * @return string
     * @throws Exception
     */
    static function getTitle()
    {
        if (!static::$title) {
            throw new Exception(t("Не указано свойство \$title у %0", array(get_called_class())));
        }
        return static::$title;
    }

    public final function __construct(\XMLReader $reader, $stack, $attributes_stack)
    {
        $this->reader = $reader;
        $this->stack = $stack;
        $this->attributes_stack = $attributes_stack;
        $this->init();
    }

    public function init()
    {}

    /**
     * Получить значение аттрибута из стека аттрибутов родительских тэгов
     *
     * @param string $parent_tag_name
     * @param string $attribute_name
     * @return string
     */
    public function getParentAttribute($parent_tag_name, $attribute_name)
    {
        return self::getParentAttributeStatic($parent_tag_name, $attribute_name, $this->stack, $this->attributes_stack);
    }

    static public function getParentAttributeStatic($parent_tag_name, $attribute_name, $stack, $attributes_stack)
    {
        $index = array_search($parent_tag_name, $stack);
        if (!array_key_exists($attribute_name, $attributes_stack[$index])) {
            return false;
        }
        return $attributes_stack[$index][$attribute_name];
    }

    /**
     * Представить текущий XML-элементов в виде SimpleXMLElement
     * Данный метод следует вызывать с осторожностью для больших XML элементов, так как это приведет к полной его загрузки в память
     *
     * @return \SimpleXMLElement
     */
    public function getSimpleXML()
    {
        if ($this->simpleXML == null) {
            $this->simpleXML = new \SimpleXMLElement($this->reader->readOuterXml());
        }
        return $this->simpleXML;
    }

    /**
     * Получить конфиг текущего модуля
     *
     * @return \Exchange\Config\File
     */
    public function getConfig()
    {
        return ConfigLoader::byModule($this);
    }

    /**
     * Получает конфиг модуля Каталог
     *
     * @return \Catalog\Config\File
     */
    public function getCatalogConfig()
    {
        return ConfigLoader::byModule('catalog');
    }

    /**
     * Произвести импорт.
     * Все действия по импорту (вставка и обновление записей в базе, создание файлов картинок и т.п.) производится в этом методе
     * Этот метод вызывается только из класса Matcher
     *
     * @param \XMLReader $reader
     */
    abstract function import(\XMLReader $reader);
}
