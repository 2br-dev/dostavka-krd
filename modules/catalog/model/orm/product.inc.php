<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Model\Orm;

use Catalog\Model\Api as ProductApi;
use Catalog\Model\Compare as CompareApi;
use Catalog\Model\CostApi;
use Catalog\Model\CurrencyApi;
use Catalog\Model\DirApi;
use Catalog\Model\FavoriteApi;
use Catalog\Model\MultiOfferLevelApi;
use Catalog\Model\OfferApi;
use Catalog\Model\Orm\Property\Item as PropertyItem;
use Catalog\Model\ProductDialog;
use Catalog\Model\ProductDimensions;
use Catalog\Model\PropertyApi;
use Catalog\Model\VirtualMultiOffersApi;
use Catalog\Model\WareHouseApi;
use Files\Model\FileApi;
use Files\Model\FilesType;
use Files\Model\Orm\File as OrmFile;
use Photo\Model\Orm\Image as PhotoImage;
use Photo\Model\PhotoApi;
use Photo\Model\Stub as PhotoStub;
use RS\Application\Auth;
use RS\Config\Loader as ConfigLoader;
use RS\Config\Loader;
use RS\Db\Adapter as DbAdapter;
use RS\Db\Exception as DbException;
use RS\Debug\Action as DebugAction;
use RS\Event\Exception as EventException;
use RS\Event\Manager as EventManager;
use RS\Exception as RSException;
use RS\Helper\CustomView;
use RS\Helper\Tools as HelperTools;
use RS\Module\AbstractModel\TreeList\AbstractTreeListIterator;
use RS\Orm\Exception as OrmException;
use RS\Orm\OrmObject;
use RS\Orm\Request as OrmRequest;
use RS\Orm\Request;
use RS\Orm\Type;
use RS\Router\Manager as RouterManager;
use RS\Site\Manager as SiteManager;
use Search\Model\IndexApi;
use RS\Module\Manager as ModuleManager;

/**
 * ORM Объект - товар
 */
class Product extends OrmObject
{
    const MAX_RATING = 5;
    const IMAGES_TYPE = 'catalog';
    const FILE_ACCESS_HIDE = 0; //Скрытый файл
    const FILE_ACCESS_PUBLIC = 1; //Доступный всем файл
    const FILE_ACCESS_PAID = 2; //Файл, доступный после оплаты
    //Тип кнопки заказать у товара
    const ORDER_TYPE_BASKET = 'basket'; //кнопка добавить в корзину
    const ORDER_TYPE_UNOBTAINABLE = 'unobtainable'; //нет в наличии
    const ORDER_TYPE_ADVORDER = 'advorder'; //нет в наличии, кнопка сделать предварительный заказ
    const FLAG_DONT_RESET_IMPORT_HASH = 'dont_reset_import_hash'; // флаг "не сбрасывать хэш импорта" - включать если не изменяются импортируемые свойтва
    const FLAG_DONT_UPDATE_SEARCH_INDEX = 'dont_update_search_index'; // флаг "не обновлять поисковый индекс" - включать при массовых операциях если поисковые данные не изменяются
    const FLAG_DONT_UPDATE_DIR_COUNTER = 'dont_update_dir_counter'; // флаг "на пересчитывать счётчики товаров у категорий" - включать при массовых операциях для экономии времени

    protected static $table = 'product';
    protected static $property_name_id = array();
    protected static $cost_title_id = array();
    protected static $spec_dirs = array();
    protected static $cost_list;
    protected static $dirlist;

    protected $fast_mark_offers_use; //Флаг о том, что используются комплектации
    protected $fast_mark_multioffers_use; //Флаг о том, что используются многомерные комплектации
    protected $fast_mark_virtual_multioffers_use; //Флаг о том, что используются виртуальные многомерные комплектации
    protected $keep_update_prod_cat = true; // флаг отвечает за обновление категорий у товара при обновлении товара
    protected $keep_spec_dirs = false; // флаг отвечает за сохранение связей с категориями
    protected $cache_visible_property;
    protected $cache_amount_step;
    protected $cache_warehouse_stick;
    protected $user_cost;
    protected $stock = null; //Остатки по складам
    protected $full_stock = null; //Остатки по складам обобщённые
    protected $offer_xcost = array();
    protected $dir_alias_cache = array(); //Кэш с алиасами директорий в которых присутствует товар
    protected $calculate_user_cost = null; //Кэш подсчета цены для пользователя
    protected $files;
    protected $dimensions_object;
    protected $flags = array();

    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
                'site_id' => new Type\CurrentSite(),
                'title' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Короткое название'),
                    'Checker' => array('chkEmpty', t('Укажите название товара')),
                    'attr' => array(array(
                        'data-autotranslit' => 'alias'
                    ))
                )),
                'alias' => new Type\Varchar(array(
                    'maxLength' => '150',
                    'description' => t('URL имя'),
                    'hint' => t('Могут использоваться только английские буквы, цифры, знак подчеркивания, запятая, точка и минус'),
                    'meVisible' => false,
                    'Checker' => array('chkalias', null),
                )),
                'short_description' => new Type\Text(array(
                    'description' => t('Краткое описание'),
                    'Attr' => array(array('rows' => 3, 'cols' => 80)),
                )),
                'description' => new Type\Richtext(array(
                    'description' => t('Описание товара'),
                )),
                'barcode' => new Type\Varchar(array(
                    'maxLength' => '50',
                    'index' => true,
                    'description' => t('Артикул'),
                    'allowempty' => true,
                )),
                'weight' => new Type\Real(array(
                    'description' => t('Вес'),
                )),
                'dateof' => new Type\Datetime(array(
                    'description' => t('Дата поступления'),
                    'index' => true
                )),
                'xcost' => new Type\MixedType(array(
                    'description' => t('Цены в базовой валюте'),
                    'visible' => false,
                )),
                'excost' => new Type\MixedType(array(
                    'description' => t('Цены'),
                    'visible' => true,
                    'template' => '%catalog%/form/product/cost.tpl',
                    'meTemplate' => '%catalog%/form/product/mecost.tpl',
                )),
                'num' => new Type\Decimal(array(
                    'maxLength' => 11,
                    'decimal' => 3,
                    'allowEmpty' => false,
                    'visible' => false,
                    'appVisible' => true,
                    'mevisible' => true,
                    'meTemplate' => '%catalog%/form/product/menum.tpl',
                    'description' => t('Доступно'),
                    'getWarehousesList' => function () {
                        return WareHouseApi::getWarehousesList();
                    },
                )),
                'dynamic_num' => new Type\Decimal(array(
                    'description' => t('Динамический остаток, заполняется во время выполнения'),
                    'maxLength' => 11,
                    'decimal' => 3,
                    'runtime' => true,
                    'visible' => false
                )),
                'waiting' => new Type\Decimal(array(
                    'maxLength' => 11,
                    'decimal' => 3,
                    'allowEmpty' => false,
                    'visible' => false,
                    'appVisible' => true,
                    'mevisible' => false,
                    'description' => t('Ожидание'),
                )),
                'reserve' => new Type\Decimal(array(
                    'maxLength' => 11,
                    'decimal' => 3,
                    'allowEmpty' => false,
                    'visible' => false,
                    'appVisible' => true,
                    'mevisible' => false,
                    'description' => t('Зарезервировано'),
                )),
                'remains' => new Type\Decimal(array(
                    'maxLength' => 11,
                    'decimal' => 3,
                    'allowEmpty' => false,
                    'visible' => false,
                    'appVisible' => true,
                    'mevisible' => false,
                    'description' => t('Остаток'),
                )),
                'amount_step' => new Type\Decimal(array(
                    'description' => t('Шаг изменения количества товара в корзине'),
                    'hint' => t('0 - означает, что будет использоваться шаг из настроек единицы измерения'),
                    'maxLength' => 11,
                    'decimal' => 3,
                    'allowEmpty' => false,
                    'default' => 0,
                )),
                'unit' => new Type\Integer(array(
                    'description' => t('Единица измерения'),
                    'List' => array(array('\Catalog\Model\UnitApi', 'selectList')),
                )),
                'min_order' => new Type\Integer(array(
                    'maxLength' => 11,
                    'mevisible' => true,
                    'description' => t('Минимальное количество товара для заказа'),
                    'hint' => t('Если пустое поле, то контроля не будет')
                )),
                'max_order' => new Type\Integer(array(
                    'maxLength' => 11,
                    'mevisible' => true,
                    'description' => t('Максимальное количество товара для заказа'),
                    'hint' => t('Если пустое поле, то контроля не будет')
                )),
                'public' => new Type\Integer(array(
                    'maxLength' => '1',
                    'index' => true,
                    'description' => t('Показывать товар'),
                    'CheckboxView' => array(1, 0),
                )),
                'no_export' => new Type\Integer(array(
                    'description' => t('Не экспортировать'),
                    'checkboxView' => array(1, 0),
                    'maxLength' => 1,
                    'default' => 0
                )),
                'xdir' => new Type\ArrayList(array(
                    'description' => t('Категория'),
                    'tree' => array(array('\Catalog\Model\DirApi', 'staticNoSpecTreeList')),
                    'attr' => array(array(
                        AbstractTreeListIterator::ATTRIBUTE_MULTIPLE => true,
                    )),
                    'meTemplate' => '%catalog%/form/product/mexdir.tpl',
                    'Checker' => array('chkEmpty', t('Укажите хотя бы одну категорию')),
                    'template' => '%catalog%/form/product/xdir_treelistbox.tpl',
                )),
                'offers' => new Type\ArrayList(array(
                    'description' => t('Комплектация'),
                    'visible' => false
                )),
                'maindir' => new Type\Integer(array(
                    'maxLength' => '11',
                    'index' => true,
                    'description' => t('Основная категория'),
                    'template' => '%catalog%/form/product/maindir.tpl',
                )),
                'xspec' => new Type\ArrayList(array(
                    'description' => t('Спец. категория'),
                    'template' => '%catalog%/form/product/specdir.tpl',
                    'meTemplate' => '%catalog%/form/product/mespecdir.tpl'
                )),
                'reservation' => new Type\Enum(array('default', 'throughout', 'forced'), array(
                    'allowEmpty' => false,
                    'default' => 'default',
                    'description' => t('Предварительный заказ'),
                    'hint' => t('По-умолчанию означает: как в настройках модуля Магазин'),
                    'ListFromArray' => array(array(
                        'default' => t('По умолчанию'),
                        'throughout' => t('Запрещено'),
                        'forced' => t('Только предзаказ')
                    )),
                )),
                'brand_id' => new Type\Integer(array(
                    'maxLength' => '11',
                    'default' => 0,
                    'index' => true,
                    'description' => t('Бренд товара'),
                    'list' => array(array('\Catalog\Model\BrandApi', 'staticSelectList'))
                )),
                'simage' => new Type\MixedType(array(
                    'description' => t('Фото'),
                    'visible' => false,
                    'meVisible' => true,
                    'template' => '%catalog%/form/product/simage.tpl'
                )),
                '_tmpid' => new Type\Hidden(array(
                    'appVisible' => false,
                    'meVisible' => false
                )),
                'format' => new Type\Varchar(array(
                    'maxLength' => '20',
                    'index' => true,
                    'description' => t('Загружен из'),
                    'visible' => false,
                )),
                'rating' => new Type\Decimal(array(
                    'maxLength' => '3',
                    'decimal' => '1',
                    'description' => t('Средний балл(рейтинг)'),
                    'hint' => t('Расчитывается автоматически, исходя из поставленных оценок, если установлен блок комментариев на странице товара.')
                )),
                'comments' => new Type\Integer(array(
                    'maxLength' => '11',
                    'description' => t('Кол-во комментариев'),
                    'visible' => false,
                )),
                'last_id' => new Type\Varchar(array(
                    'maxLength' => '36',
                    'uniq' => true,
                    'description' => t('Прошлый ID'),
                    'visible' => false,
                )),
                'processed' => new Type\Integer(array(
                    'maxLength' => '2',
                    'visible' => false,
                )),
                'is_new' => new Type\Integer(array(
                    'maxLength' => '1',
                    'description' => t('Служебное поле'),
                    'visible' => false,
                )),
                'group_id' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Идентификатор группы товаров'),
                    'hint' => t('Вы можете объединять схожие товары в группы, в этом случае другие товары группы будут выступать в качестве комплектаций.<br> Укажите у нескольких товаров один и тот же идентификатор и задайте характеристики у основной комплектации каждого товара группы.', array(), 'Описание поля `Идентификатор группы товаров`')
                )),
                'xml_id' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Идентификатор в системе 1C'),
                    'meVisible' => false
                )),
                'import_hash' => new Type\Varchar(array(
                    'maxLength' => '32',
                    'description' => t('Хэш данных импорта'),
                    'visible' => false
                )),
                'sku' => new Type\Varchar(array(
                    'maxLength' => 50,
                    'description' => t('Штрихкод'),
                )),
                'sortn' => new Type\Integer(array(
                    'description' => t('Сортировочный вес'),
                    'default' => 100
                )),
                'cost' => new Type\MixedType(array(
                    'description' => t('Стоимость'),
                    'visible' => false
                )),
                'recommended' => new Type\Varchar(array(
                    'maxLength' => 4000,
                    'description' => t('Рекомендуемые товары'),
                    'visible' => false,
                )),
                'recommended_arr' => new Type\ArrayList(array(
                    'visible' => false
                )),
                'concomitant' => new Type\Varchar(array(
                    'maxLength' => 4000,
                    'description' => t('Сопутствующие товары'),
                    'visible' => false,
                )),
                'concomitant_arr' => new Type\ArrayList(array(
                    'visible' => false
                )),
                '_currency' => new Type\MixedType(array(
                    'visible' => false
                )),
                '_alias' => new Type\MixedType(array(
                    'visible' => false
                )),
                'prop' => new Type\ArrayList(array(
                    'description' => t('Характеристики товара, используется для сохранения значений'),
                    'visible' => false
                )),
                'properties' => new Type\MixedType(array(
                    'description' => t('Характеристики товара по группам'),
                    'visible' => false
                )),
                'payment_subject' => new Type\Varchar(array(
                    'description' => t('Признак предмета товара'),
                    'hint' => t('Признак расчета товара для печати в чеке по ФЗ-54'),
                    'listFromArray' => array(array(
                        'commodity' => t('Товар'),
                        'excise' => t('Подакцизный товар'),
                        'service' => t('Услуга'),
                        'payment' => t('Платеж'),
                        'another' => t('Другое'),
                    )),
                    'default' => 'commodity'
                )),
            t('Характеристики'),
                '_property_' => new Type\UserTemplate('%catalog%/form/product/properties.tpl', '%catalog%/form/product/meproperties.tpl', array(
                    'meVisible' => true,
                    'getPropertyItemAllowTypeData' => function () {
                        return PropertyItem::getAllowTypeData();
                    },
                )),
            t('Комплектации'),
                '_offers_' => new Type\UserTemplate('%catalog%/form/product/offers.tpl', '%catalog%/form/product/meoffers.tpl', array(
                    'meVisible' => true,
                    'getDefaultCurrency' => function () {
                        return CurrencyApi::getBaseCurrency();
                    },
                    'getAllProperties' => function () {
                        return PropertyApi::getListTypeProperty();
                    }
                )),
                'offer_caption' => new Type\Varchar(array(
                    'description' => t('Подпись к комплектациям'),
                    'maxLength' => '200',
                    'hint' => t('Будет отображатся над полями с комплектациями')
                )),
                'multioffers' => new Type\ArrayList(array(
                    'description' => t('Многомерные комрлектации'),
                    'visible' => false
                )),
                'virtual_multioffers' => new Type\ArrayList(array(
                    'description' => t('Виртуальные многомерные комрлектации'),
                    'visible' => false
                )),
            t('Мета-тэги'),
                'meta_title' => new Type\Varchar(array(
                    'maxLength' => '1000',
                    'description' => t('SEO Заголовок'),
                )),
                'meta_keywords' => new Type\Varchar(array(
                    'maxLength' => '1000',
                    'description' => t('SEO Ключевые слова(keywords)'),
                )),
                'meta_description' => new Type\Varchar(array(
                    'maxLength' => '1000',
                    'viewAsTextarea' => true,
                    'description' => t('SEO Описание(description)'),
                )),
            t('Рекомендуемые товары'),
                '_recomended_' => new Type\UserTemplate('%catalog%/form/product/recomended.tpl', '%catalog%/form/product/merecomended.tpl', array(
                    'meVisible' => true  //Видимость при мультиредактировании
                )),
            t('Сопутствующие товары'),
                '_concomitant_' => new Type\UserTemplate('%catalog%/form/product/concomitant.tpl', '%catalog%/form/product/meconcomitant.tpl', array(
                    'meVisible' => true  //Видимость при мультиредактировании
                )),
            t('Фото'),
                '_photo_' => new Type\UserTemplate('%catalog%/form/product/photos.tpl'),
        ));

        //Включаем в форму hidden поле id.
        $this['__id']->setVisible(true);
        $this['__id']->setMeVisible(false);
        $this['__id']->setHidden(true);

        $this->addIndex(array('site_id', 'public', 'num'));
        $this->addIndex(array('site_id', 'public', 'dateof'));
        $this->addIndex(array('site_id', 'public', 'sortn'));
        $this->addIndex(array('site_id', 'xml_id'), self::INDEX_UNIQUE);
        $this->addIndex(array('site_id', 'alias'), self::INDEX_UNIQUE);
        $this->addIndex(array('site_id', 'group_id'));
    }

    /**
     * Возвращает отладочные действия, которые можно произвести с объектом
     *
     * @return DebugAction\AbstractAction[]
     */
    public function getDebugActions()
    {
        return array(
            new DebugAction\Edit(RouterManager::obj()->getAdminPattern('edit', array(':id' => '{id}'), 'catalog-ctrl')),
            new DebugAction\Delete(RouterManager::obj()->getAdminPattern('del', array(':chk[]' => '{id}'), 'catalog-ctrl'))
        );
    }

    /**
     * Устанавливает, сохранять ли связь со spec категориями
     *
     * @param bool $bool
     * @return void
     */
    function keepSpecDirs($bool)
    {
        $this->keep_spec_dirs = $bool;
    }

    /**
     * Устанавливает обновлять ли категорию у товара или нет при обновлении данных товара
     * В основном используется для импорта из 1С
     *
     * @param bool $bool
     * @return void
     */
    function keepUpdateProductCategory($bool)
    {
        $this->keep_update_prod_cat = $bool;
    }

    /**
     * Возвращает список характеристик в виде списка объектов. Для формы редактирования товара
     *
     * @return array of Property\Item
     */
    function getPropObjects()
    {
        return $this['properties'];
    }

    /**
     * Вызывается после загрузки объекта
     * @return void
     */
    function afterObjectLoad()
    {
        if (!empty($this['recommended'])) {
            $this['recommended_arr'] = @unserialize($this['recommended']);
        }
        if (!empty($this['concomitant'])) {
            $this['concomitant_arr'] = @unserialize($this['concomitant']);
        }
        $this['_alias'] = !empty($this['alias']) ? $this['alias'] : $this['id'];

        // Приведение типов
        $this['num'] = (float)$this['num'];
        $this['amount_step'] = (float)$this['amount_step'];
    }

    /**
     * Вызывается перед сохранением объекта
     *
     * @param string $flag - строковое представление текущей операции (insert или update)
     * @return false|void
     * @throws DbException
     * @throws RSException
     */
    function beforeWrite($flag)
    {
        if ($this['id'] < 0) {
            $this['_tmpid'] = $this['id'];
            unset($this['id']);
        }

        if (!$this['maindir'] && ($this['xdir'] || $this['xspec'])) {
            $xdir = $this['xdir'];
            $xspec = $this['xspec'];
            $this['maindir'] = $this['xdir'] ? reset($xdir) : reset($xspec);
        }

        if (!$this->getFlag(self::FLAG_DONT_RESET_IMPORT_HASH)) { // при любом изменении - сбрасываем хэш
            $this['import_hash'] = null;
        }
        if ($this['xml_id'] === '') {
            $this['xml_id'] = null;
        }

        if ($this->isModified('recommended_arr')) { //Если изменялись рекомендуемые
            if (!empty($this['recommended_arr']['product']) && ($key = array_search($this['id'], $this['recommended_arr']['product']))) {
                $recommended = $this['recommended_arr'];
                unset($recommended['product'][$key]);
                $this['recommended_arr'] = $recommended;
            }
            $this['recommended'] = serialize($this['recommended_arr']);
        }
        if ($this->isModified('concomitant_arr')) { //Если изменялись сопутствующие
            if (!empty($this['concomitant_arr']['product']) && ($key = array_search($this['id'], $this['concomitant_arr']['product']))) {
                $concomitants = $this['concomitant_arr'];
                unset($concomitants['product'][$key]);
                $this['concomitant_arr'] = $concomitants;
            }
            $this['concomitant'] = serialize($this['concomitant_arr']);
        }

        if ($this->isModified('alias') && empty($this['alias'])) {
            $this['alias'] = null;
        }

        if ($flag == self::INSERT_FLAG) {
            // Выполняем проверку не привышен ли лимит на количество товаров
            if (defined('PRODUCTS_LIMIT')) {
                // Считаем количество товаров
                $products_count = OrmRequest::make()
                    ->from(new self())
                    ->count();
                // Если лимит достигнут, то бросаем исключение
                if ($products_count + 1 >= PRODUCTS_LIMIT) {
                    $this->addError(t('Достигнут лимит на количество товаров (%0)', PRODUCTS_LIMIT));
                    return false;
                }
            }

            if ($this['dateof']) {
                $this['dateof'] = date('Y-m-d H:i:s');
            }
        }

        return null;
    }

    /**
     * Вызывается после сохранения объекта
     *
     * @param mixed $flag - флаг процедуры записи (insert, update, replace)
     * @return void
     * @throws DbException
     * @throws EventException
     * @throws RSException
     */
    function afterWrite($flag)
    {
        //Переносим временные объекты, если таковые имелись
        if ($this['_tmpid'] < 0) {
            OrmRequest::make()
                ->update(new PhotoImage())
                ->set(array('linkid' => $this['id']))
                ->where(array(
                    'type' => self::IMAGES_TYPE,
                    'linkid' => $this['_tmpid']
                ))->exec();

            OrmRequest::make()
                ->update(new Offer())
                ->set(array('product_id' => $this['id']))
                ->where(array(
                    'product_id' => $this['_tmpid']
                ))->exec();

            OrmRequest::make()
                ->update(new Xstock(), true)
                ->set(array('product_id' => $this['id']))
                ->where(array(
                    'product_id' => $this['_tmpid']
                ))->exec();
        }

        // Если указано, не обновлять категории у товара при обновлении товара
        if (!$this->keep_update_prod_cat && $this->getLocalParameter('duplicate_updated')) {
            unset($this['xdir']);
        }

        if (!empty($this['xdir'])) {
            $xdir = new Xdir();
            $pairs = array();
            $xdirs = array_merge((array)$this['xdir'], (array)$this['xspec']); // Объединяем в один список спец категории и обычные категории
            $xdirs = array_unique($xdirs);
            foreach ($xdirs as $dir) {
                $pairs[] = "('{$this['id']}','{$dir}')";
            }

            $sql1 = "DELETE FROM " . $xdir->_getTable() . " WHERE product_id='{$this['id']}'";
            $spec_dirs = $this->getSpecDirs(true);
            if ($this->keep_spec_dirs && $spec_dirs) {
                $sql1 .= ' AND dir_id NOT IN (' . implode(',', $spec_dirs) . ')';
            }
            $sql2 = "INSERT IGNORE INTO " . $xdir->_getTable() . " (product_id, dir_id) VALUES" . implode(',', $pairs);
            DbAdapter::sqlExec($sql1);
            DbAdapter::sqlExec($sql2);
        }

        //Сохраняем цены
        if ($this->isModified('excost') || $this->isModified('xcost')) {
            OrmRequest::make()->delete()
                ->from(new Xcost())
                ->where(array(
                    'product_id' => $this['id']
                ))->exec();
            $costs = $this->isModified('excost') ? $this['excost'] : $this['xcost'];

            foreach ($costs as $cost_id => $data) {
                $cost_item = new Xcost();
                $cost_item->fillData($cost_id, $this['id'], $data);
                $cost_item->insert();
            }
        }

        //Сохраняем характеристики
        if ($this->isModified('prop')) {
            $prop_api = new PropertyApi();
            $prop_api->saveProperties($this['id'], 'product', $this['prop']);
        }

        // Проверяем что это было, вставка или апдейт
        if ($flag == self::INSERT_FLAG && !$this['offers'] && !self::getLocalParameter('duplicate_updated')) {
            //Создаем основную комплектацию, если у товара не заданы комплектации
            $default_warehouse = WareHouseApi::getDefaultWareHouse();
            $default_warehouse_id = $default_warehouse['id'];
            $this['offers'] = array(
                'main' => array(
                    'stock_num' => array(
                        $default_warehouse_id => $this['num'] ?: 0
                    )
                )
            );
        }

        //Сохраняем комплектации
        $offer_api = new OfferApi();
        if (!$this['dont_save_offers'] && $this->isModified('offers')) {
            $offer_api->saveOffers($this['id'], isset($this['offers']['items']) ? $this['offers']['items'] : $this['offers'], $this->use_offers_unconverted_propsdata);
        }

        //Многомерные комплектации
        if ($this->isModified('multioffers')) {
            $moffer_api = new MultiOfferLevelApi();
            if (isset($this['multioffers']['use'])) {
                if (!empty($this['multioffers']['is_photo'])) { //Флаг "С фото" У многомерных комплектаций
                    $multioffers = $this['multioffers'];
                    $multioffers['levels'][$this['multioffers']['is_photo'] - 1]['is_photo'] = 1;
                    $this['multioffers'] = $multioffers;
                }
                $moffer_api->saveMultiOfferLevels($this['id'], $this['multioffers']['levels']);
            } else { //Если снята галочка уровней многомерных комплектаций
                $moffer_api->clearMultiOfferLevelsByProductId($this['id']);
            }
        }

        //Обновляем доступность связанных с комплектациями характеристик
        $config = ConfigLoader::byModule($this);
        if ($config['link_property_to_offer_amount']) {
            $offer_api->updateLinkedProperties($this['id']);
        }

        //Обновляем поисковый индекс
        if (!$this->getFlag(self::FLAG_DONT_UPDATE_SEARCH_INDEX)) {
            $this->updateSearchIndex();
        }

        //Обновляем счетчики товаров у категорий
        if (!$this->getFlag(self::FLAG_DONT_UPDATE_DIR_COUNTER)) {
            DirApi::updateCounts(); //Обновляем счетчики у категорий
        }
    }

    /**
     * Загружает характеристики у товара
     *
     * @param bool $onlyVisible - если true, вернёт только видимые не пустые характеристики
     * @return array
     * @throws DbException
     * @throws OrmException
     * @throws EventException
     */
    function fillProperty($onlyVisible = false)
    {
        if ($this['properties'] === null) {
            $this->fillCategories();
            $propapi = new Propertyapi();
            $this['properties'] = $propapi->getProductProperty($this, $onlyVisible);
        }
        return $this['properties'];
    }

    /**
     * Заполняет значениями остатки по складам для разных складов
     *
     * @return void
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    function fillOffersStock()
    {
        if ($this['offers'] === null) {
            $this->fillOffers();
        }

        if (!empty($this['offers']) && isset($this['offers']['items'])) {
            foreach ($this['offers']['items'] as $offer) {
                $stock_num = OrmRequest::make()
                    ->select('warehouse_id, stock')
                    ->from(new Xstock())
                    ->where(array(
                        'offer_id' => $offer['id'],
                        'product_id' => $offer['product_id'],
                    ))
                    ->exec()
                    ->fetchSelected('warehouse_id', 'stock');
                $offer['stock_num'] = $stock_num;
            }
        }
    }

    /**
     * Заполняет у товара остатки по складам в виде градаций по параметру
     * warehouse_stars в настройках модуля
     * Включает метки остатков только для тех складов, которые должны
     * отображаться с учетом текущего филиала
     *
     * @return void
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    function fillOffersStockStars()
    {
        if ($this['offers'] === null) { //проверка на комплектации
            $this->fillOffers();
        }

        if (!isset($this['offers']['items'][0]['stock_num'])) { //проверка на подгруженные остатки
            $this->fillOffersStock();
        }

        $config = ConfigLoader::byModule($this);
        $warehouse_stars = explode(",", $config['warehouse_sticks']);

        //Получаем склады, которые соответствуют текущему филиалу
        $warehouses_info = $this->getWarehouseStickInfo();
        $warehouses_ids = array();
        foreach ($warehouses_info['warehouses'] as $item) {
            $warehouses_ids[] = $item['id'];
        }

        foreach ($this['offers']['items'] as $offer) {
            $sticks = array();
            foreach ($offer['stock_num'] as $warehouse_id => $stock_num) {
                if (in_array($warehouse_id, $warehouses_ids)) {
                    $sticks[$warehouse_id] = 0;
                    foreach ($warehouse_stars as $warehouse_num) {
                        if ($stock_num >= $warehouse_num) {
                            $sticks[$warehouse_id] = $sticks[$warehouse_id] + 1;
                        }
                    }
                    $offer['sticks'] = $sticks;
                }
            }
        }
    }

    /**
     * Загружает информацию о комплектациях
     *
     * @return array возвращает массив с комплектациями
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    function fillOffers()
    {
        if ($this['offers'] === null) {
            $q = OrmRequest::make()
                ->select('O.*')
                ->from(new Offer(), 'O')
                ->where(array('O.product_id' => $this['id']))
                ->orderby('sortn');

            $this->appendOfferDynamicNum($q);
            $offers = $q->objects();

            $offers_arr = array(
                'use' => 0,
                'items' => array()
            );
            if (count($offers)) {
                $offers_arr['use'] = 1;
                $offers_arr['items'] = $offers;
            }
            $this['offers'] = $offers_arr;
        }
        return $this['offers'];
    }

    /**
     * Добавляет к оъекту запроса комплектаций условие для выборки
     * остатков только на связанных с филиалом складах. Результат будет в поле dynamic_num
     *
     * @param OrmRequest $q
     * @return OrmRequest
     * @throws RSException
     */
    protected function appendOfferDynamicNum(OrmRequest $q)
    {
        if ($this->dynamic_num_warehouses_id) {
            $warehouse_ids_str = implode(',', $this->dynamic_num_warehouses_id);

            $q->select('SUM(XST.stock) as dynamic_num')
                ->leftjoin(new Xstock(), 'XST.offer_id = O.id AND XST.warehouse_id IN (' . $warehouse_ids_str . ')', 'XST')
                ->groupby('O.id');
        }
        return $q;
    }

    /**
     * Добавляет к товару поле dynamic_num с остатком только на складах выбранного филиала.
     * После вызова данного метода fillOffers() бдет также добавлять dynamic_num к каждой комплектации
     * Актуально для Мегамаркета
     *
     * @return void
     * @throws DbException
     * @throws RSException
     */
    public function fillAffiliateDynamicNum()
    {
        $config = Loader::byModule($this);
        if (ModuleManager::staticModuleExists('affiliate') && $config['affiliate_stock_restriction']) { //Если включена опция ограничения остатков у филиала
            $this->setWarehousesForDynamicNum(WareHouseApi::getAvailableWarehouses());
        }
    }

    /**
     * Добавляет к товару поле dynamic_num с остатком только на указанных складах.
     * После вызова данного метода fillOffers() бдет также добавлять dynamic_num к каждой комплектации
     *
     * @param int[] $warehouse_ids
     * @throws DbException
     * @throws RSException
     */
    public function setWarehousesForDynamicNum($warehouse_ids)
    {
        $this->dynamic_num_warehouses_id = $warehouse_ids;

        if ($warehouse_ids) {
            $q = Request::make()
                ->select('SUM(stock) as dynamic_num')
                ->from(new Xstock())
                ->where(array(
                    'product_id' => $this['id']
                ))
                ->whereIn('warehouse_id', $warehouse_ids);

            $this['dynamic_num'] = $q->exec()->getOneField('dynamic_num');
        }
    }

    /**
     * Заполняет уровни многомерных комплектаций у товара
     *
     * @return array массив многомерных комплектаций
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    function fillMultiOffers()
    {
        if ($this['multioffers'] === null) {
            $levelsApi = new MultiOfferLevelApi();
            $levels = $levelsApi->getLevelsInfoByProductId($this['id']);

            $levels_arr = array(
                'use' => 0,
                'levels' => array()
            );

            if (!empty($levels)) {
                $levels_arr['use'] = 1;

                foreach ($levels as $k => $level) {
                    //Подгрузим отмеченные значения
                    $values = OrmRequest::make()
                        ->select('V.*, V.value as val_str')//Для совместимости
                        ->from(new Property\ItemValue(), 'V')
                        ->join(new Property\Link(), 'L.val_list_id = V.id', 'L')
                        ->where(array(
                            'L.product_id' => $this['id'],
                            'L.prop_id' => $level['prop_id'],
                        ))
                        ->where("V.value != ''")
                        ->orderby('V.sortn');

                    $level['values'] = $values->objects();
                    $levels_arr['levels'][$k] = $level;
                }
            }
            $this['multioffers'] = $levels_arr;
        }

        return $this['multioffers'];
    }

    /**
     * Заполняет виртуальные многомерные комплектации у товаров
     *
     * @return array
     */
    function fillVirtualMultiOffers()
    {
        if ($this['virtual_multioffers'] === null) {
            $offers_arr = array(
                'use' => 0,
                'items' => array()
            );
            if (!empty($this['group_id'])) { //Если определена группа у товара
                $virtual_multioffers_api = new VirtualMultiOffersApi();
                $items = $virtual_multioffers_api->getVirtualMultiOffersByProduct($this);
                if (!empty($items)) {
                    $offers_arr['use'] = 1;
                    $offers_arr['items'] = $items;
                }
            }
            $this['virtual_multioffers'] = $offers_arr;

            if ($offers_arr['use']) { //Если используются
                //Перезаполним массив с обычными многомерными комплектациями для маскировки
                $api = new VirtualMultiOffersApi();
                $multioffers = $this['multioffers'];
                $multioffers['use'] = true;
                $multioffers['levels'] = $api->prepareVirtualMultiForMultioffer($this['virtual_multioffers']['items']);
                $this['multioffers'] = $multioffers;
            }
        }
        return $this['virtual_multioffers'];
    }

    /**
     * Возвращает виртуальные многомерные комплектации, где в ключи идут ключи из параметров со множеством возможных значений
     *
     * @return array
     */
    function getVirtualMultiOffersByPropertyKeys()
    {
        $arr = array();
        if ($this->isVirtualMultiOffersUse()) { //Если виртуальные многомерные комплектации присутствуют\
            $api = new VirtualMultiOffersApi();
            $arr = $api->prepareVirtualMultiOffersByKeys($this['virtual_multioffers']['items']);
        }
        return $arr;
    }

    /**
     * Возвращает список остатков на группах складов, отсортированный по приоритету групп
     *
     * @param int $offer - индекс комплектации
     * @param bool $only_available_warehouses - искать остатки только на доступных складах
     * @return float[]
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    public function getWarehouseGroupStocks($offer = 0, $only_available_warehouses = true)
    {
        $stocks = $this->baseWarehouseAvailabilityRequest($offer, $only_available_warehouses)
            ->select('G.id, sum(X.stock) sum')
            ->groupby('G.id')
            ->exec()->fetchSelected('id', 'sum');

        if ($stocks) {
            $groups = OrmRequest::make()
                ->from(new WareHouseGroup())
                ->whereIn('id', array_keys($stocks))
                ->objects(null, 'id');
        }

        $result = array();
        foreach ($stocks as $group_id => $stock) {
            $result[] = array(
                'group' => (isset($groups[$group_id])) ? $groups[$group_id] : new WareHouseGroup(),
                'stock' => $stock,
            );
        }

        return $result;
    }

    /**
     * Возвращает базовый запрос на получение остатков по группам складов
     *
     * @param int $offer - индекс комплектации
     * @param bool $only_available_warehouses - искать остатки только на доступных складах
     * @return OrmRequest
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    protected function baseWarehouseAvailabilityRequest($offer = 0, $only_available_warehouses = true)
    {
        $this->fillOffers();
        $offer_id = (isset($this['offers']['items'][$offer])) ? $this['offers']['items'][$offer]['id'] : $this['offers']['items'][0]['id'];

        $request = OrmRequest::make()
            ->from(new Xstock(), 'X')
            ->join(new WareHouse(), 'X.warehouse_id = W.id', 'W')
            ->join(new WareHouseGroup(), 'W.group_id = G.id', 'G', 'left')
            ->where(array(
                'X.product_id' => $this['id'],
                'X.offer_id' => $offer_id,
            ))
            ->where('X.stock > 0')
            ->orderby('W.group_id > 0 desc, W.group_id asc');

        if ($only_available_warehouses) {
            $request->whereIn('W.id', WareHouseApi::getAvailableWarehouses());
        }

        return $request;
    }

    /**
     * Возвращает виртуальные многомерные комплектации, где в ключи идут ключи из параметров со множеством возможных значений
     *
     * @return array
     */
    function getMultiOffersByPropertyKeys()
    {
        $arr = array();
        if ($this->isVirtualMultiOffersUse()) { //Если виртуальные многомерные комплектации присутствуют\
            $api = new VirtualMultiOffersApi();
            $arr = $api->prepareVirtualMultiOffersByKeys($this['virtual_multioffers']['items']);
        }
        return $arr;
    }

    /**
     * Возвращает объект единицы измерения, в котором измеряется данный продукт
     *
     * @param string $property - имя свойства объекта Unit. Используется для быстрого обращения
     * @return Unit
     * @throws RSException
     */
    function getUnit($property = null)
    {
        $congig = ConfigLoader::byModule($this);
        $unit_id = $this['unit'] ?: $congig['default_unit'];
        $unit = new Unit($unit_id);
        return ($property === null) ? $unit : $unit[$property];
    }

    /**
     * Возвращает райтинг товара в процентах от 0 до 100
     *
     * @return integer
     */
    function getRatingPercent()
    {
        return round($this['rating'] / self::MAX_RATING, 1) * 100;
    }

    /**
     * Возвращает средний балл товара
     *
     * @return float
     */
    function getRatingBall()
    {
        return round(self::MAX_RATING * ($this->getRatingPercent() / 100), 2);
    }

    /**
     * Возвращает максимальное количество баллов, которое можно поставить данному товару
     *
     * @return integer
     */
    function getMaxBall()
    {
        return self::MAX_RATING;
    }

    /**
     * Возврщает количество комментариев
     *
     * @return integer
     */
    function getCommentsNum()
    {
        return (int)$this['comments'];
    }

    /**
     * Возвращает true, если товар состоит в категории с псевдонимом alias, иначе false
     *
     * @param string|integer $alias - псевдоним категории
     * @return bool
     * @throws OrmException
     */
    function inDir($alias)
    {
        if (isset($this->dir_alias_cache[$alias])) {
            return $this->dir_alias_cache[$alias];
        }

        //Конвертируем alias в id категории
        $dirapi = DirApi::getInstance();
        $dir = $dirapi->getByAlias($alias);
        if (!$dir) {
            return $this->dir_alias_cache[$alias] = false;
        }

        return $this->dir_alias_cache[$alias] = ((is_array($this['xdir']) && in_array($dir['id'], $this['xdir'])) || (is_array($this['xspec']) && in_array($dir['id'], $this['xspec'])));
    }

    /**
     * Возвращает все спец. категории
     *
     * @param bool $only_id - если true, то массив будет содержать только id категорий, иначе - объект Dir
     * @return array
     */
    function getSpecDirs($only_id = false)
    {
        $only_id = (int)$only_id;
        if (!isset(self::$spec_dirs[$only_id])) {
            $dirapi = new Dirapi();
            $dirapi->setFilter('is_spec_dir', 'Y');
            self::$spec_dirs[$only_id] = $dirapi->getAssocList('id', $only_id ? 'id' : null);
        }
        return self::$spec_dirs[$only_id];
    }

    /**
     * Возвращает количество спец категорий
     *
     * @return integer
     */
    function specDirCount()
    {
        $dirapi = DirApi::getInstance('spec');
        $dirapi->setFilter('is_spec_dir', 'Y');
        return $dirapi->getListCount();
    }

    /**
     * Загружает категории, в которых состоит товар
     *
     * @return void
     * @throws DbException
     */
    function fillCategories()
    {
        if (!empty($this['id']) && $this['xdir'] == null) {
            //Получаем спец. категории
            $spec_dirs = $this->getSpecDirs();

            //Получаем категории товара
            $res = OrmRequest::make()
                ->select('*')
                ->from(new Xdir())
                ->where(array('product_id' => $this['id']))
                ->exec()->fetchAll();

            $xdir = array();
            $xspec = array();
            if (!empty($res)) {
                foreach ($res as $cats) {
                    $dir_id = $cats['dir_id'];

                    $xdir[] = $dir_id;
                    if (isset($spec_dirs[$dir_id])) {
                        $xspec[] = $dir_id;
                    }
                }
            }
            $this['xdir'] = $xdir;
            $this['xspec'] = $xspec;
        }
    }

    /**
     * Загружает объект - категорию в свойство maindir_obj
     *
     * @return void
     */
    function fillMainRubric()
    {
        $dirapi = DirApi::getInstance();
        $dir = $dirapi->getById($this['maindir']);
        if ($dir) {
            $this['maindir_obj'] = $dir;
        }
    }

    /**
     * Возвращает объект главной директории
     *
     * @return Dir
     */
    function getMainDir()
    {
        if (!isset($this['maindir_obj'])) {
            $this['maindir_obj'] = new Dir($this['maindir']);
        }
        return $this['maindir_obj'];
    }

    /**
     * Возвращает true, если товар присутствует в списке для сравнения
     *
     * @return bool
     */
    function inCompareList()
    {
        $compare_api = CompareApi::currentCompare();
        return $compare_api->inList($this['id']);
    }

    /**
     * Возвращает true если данный продукт уже в избранном
     *
     * @return bool
     */
    function inFavorite()
    {
        if ($this['isInFavorite'] === null) {
            $this['isInFavorite'] = FavoriteApi::alreadyInFaforite($this['id']);
        }
        return $this['isInFavorite'];
    }

    /**
     * Возвращает путь к товару(из массива директорий) наиболее соответствующий переданному dir_id
     * Должно быть загружены свойство xdir
     *
     * @param integer $dir_id - id категории, через которую должен проходить путь. Если не задан, то будет возвращен один (произвольный) из путей товара.
     * @return Dir[]
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    function getItemPathLine($dir_id = null)
    {
        $dir_api = DirApi::getInstance();

        if (!empty($this['xdir'])) {
            foreach ($this['xdir'] as $cat_id) {
                $path = $dir_api->getPathToFirst($cat_id);
                foreach ($path as $dir) {
                    if ($dir['id'] == $dir_id) {
                        /** @var Dir[] $path */
                        $path = $dir_api->getPathToFirst($dir_id);
                        return $path;
                    }
                }
            }
        }
        //Если по dir_id не удалось найти каталог
        /** @var Dir[] $path */
        $path = $dir_api->getPathToFirst($this['maindir']);
        return $path;
    }

    /**
     * Подгружает все цены товара, если они не загружены раннее
     *
     * @throws DbException
     * @throws EventException
     * @throws OrmException
     * @throws RSException
     */
    function fillCost()
    {
        if (!empty($this['id']) && $this['xcost'] === null) {
            $resource = OrmRequest::make()->from(new Xcost())
                ->where(array('product_id' => $this['id']))
                ->exec();
            $xcost = array(); //Упрощенный массив с ценами
            $excost = array(); //Расширенный массив с ценами
            while ($cost = $resource->fetchRow()) {
                $xcost[$cost['cost_id']] = $cost['cost_val'];
                $excost[$cost['cost_id']] = $cost;
            }
            $this['xcost'] = $xcost;
            $this['excost'] = $excost;

            $this->calculateUserCost();
        }
    }

    /**
     * Пересчитает автоматически формируемые цены
     *
     * @return void
     * @throws DbException
     * @throws EventException
     * @throws OrmException
     * @throws RSException
     */
    function calculateUserCost()
    {
        if ($this->calculate_user_cost === null) {
            //Пересчитываем автоматические цены
            $costapi = CostApi::getInstance();
            $this['xcost'] = $costapi->getCalculatedCostList($this['xcost']);

            //Сохраним объект текущей валюты
            $this['_currency'] = CurrencyApi::getCurrentCurrency();
            $this['_current_cost_id'] = CostApi::getUserCost();

            //Отработаем событие, чтобы достать преобразовать данные
            EventManager::fire('product.calculateusercost', array(
                'xcost' => $this['xcost'],
                'product' => $this,
            ));

            $this->calculate_user_cost = $this['xcost'];
        }
    }

    /**
     * Обновляет поисковый индекс
     */
    function updateSearchIndex()
    {
        $module_config = ConfigLoader::byModule($this);
        if (!$module_config['disable_search_index']) {
            IndexApi::updateSearch($this, $this['id'], $this['title'], $this->getSearchText());
        }
    }

    /**
     * Возвращает артикулы комплектаций, используется для построения поискового индекса
     *
     * @return string
     * @throws DbException
     */

    function getOffersBarcodes()
    {
        $result = OrmRequest::make()
            ->select('barcode')
            ->from(new Offer())
            ->where(array(
                'product_id' => $this['id']
            ))
            ->exec()
            ->fetchSelected(null, 'barcode');

        $return = implode(' ', $result);

        return $return;
    }

    /**
     * Возвращает текст для индексации. Должен содержать все слова, по которым товар должен находиться
     *
     * @return string
     * @throws EventException
     * @throws RSException
     */
    function getSearchText()
    {
        $config = ConfigLoader::byModule($this);
        //Для поиска: Штрих-код, Краткое опиание, Характеристики, мета ключевые слова
        $properties = '';
        if (in_array('properties', $config['search_fields'])) {
            if (!$this->no_use_property_in_search_index) {
                foreach ($this->fillProperty() as $groups) {
                    foreach ($groups['properties'] as $prop) {
                        /**
                         * @var \Catalog\Model\Orm\Property\Item $prop
                         */
                        $properties .= $prop['title'] . ' : ' . $prop->textView() . ' , ';
                    }
                }
            }
        }

        $text = array();

        //Кэш для подгрузки брендов
        static $product_brands = array();
        if (in_array('brand', $config['search_fields']) && $this['brand_id'] && !isset($product_brands[$this['brand_id']])) {
            $product_brands[$this['brand_id']] = $this->getBrand();
        }

        $offersbarcodes = $this->getOffersBarcodes();

        //Заносим параметры в индекс в зависимости он настроек в конфиге модуля
        if (in_array('barcode', $config['search_fields'])) $text[] = $this['barcode']; //Артикул
        if (in_array('brand', $config['search_fields']) && isset($product_brands[$this['brand_id']])) $text[] = $product_brands[$this['brand_id']]->title; //Бренд
        if (in_array('short_description', $config['search_fields'])) $text[] = $this['short_description']; //Короткое описание
        if (in_array('properties', $config['search_fields'])) $text[] = $properties; //Характеристики
        if (in_array('meta_keywords', $config['search_fields'])) $text[] = $this['meta_keywords']; //Ключевые слова из META
        if (in_array('ofbarcodes', $config['search_fields'])) $text[] = $offersbarcodes; //Артикулы комплектаций

        $event_result = EventManager::fire('product.getsearchtext', array(
            'text_parts' => $text,
            'product' => $this
        ));
        list($text) = $event_result->extract();

        return trim(strip_tags(implode(' , ', $text)));
    }

    /**
     * Возвращает объект фото-заглушку
     * @return \Photo\Model\Stub
     */
    function getImageStub()
    {
        return new PhotoStub();
    }

    /**
     * Загружает фотографии для товара
     *
     * @return void
     */
    function fillImages()
    {
        if (!$this['images']) {
            if ($this['id']) {
                $photo_api = new PhotoApi();
                $images = $photo_api->getLinkedImages($this['id'], 'catalog');
            } else {
                $images = array();
            }
            $this['images'] = $images;
        }
    }

    /**
     * Возвращает ссылку на главную фотографию (первая в списке фотографий)
     * При вызове без параметров возвращает объект фотографии
     *
     * @param int $width - ширина в пикселях
     * @param int $height - высота в пикселях
     * @param string $type - тип ресайза
     * @return PhotoImage|string
     */
    function getMainImage($width = null, $height = null, $type = 'xy')
    {
        $this->fillImages();
        $images = $this['images'];
        $img = (count($images) > 0) ? reset($images) : $this->getImageStub();

        return ($width === null) ? $img : $img->getUrl($width, $height, $type);
    }

    /**
     * Возвращает ссылку на главную фотографию комплектации (первая в списке фотографий)
     * если не указана ширина изображения - возвращает объект фотографии
     *
     * @param mixed $offer_index - индекс комплектации
     * @param mixed $width - ширина изображения
     * @param mixed $height - высота изображения
     * @param mixed $type - тип ресайза
     * @return PhotoImage|string
     * @throws OrmException
     */
    function getOfferMainImage($offer_index = 0, $width = null, $height = null, $type = 'xy')
    {
        $this->fillOffers();
        $this->fillImages();
        $images = $this['images'];
        if (count($images) > 0) {
            $photos_arr = $this['offers']['items'][$offer_index]['photos_arr'];
            $img = reset($images);
            if (!empty($photos_arr)) {
                foreach ($images as $image) {
                    if (in_array($image['id'], $photos_arr)) {
                        $img = $image;
                        break;
                    }
                }
            }
        } else {
            $img = $this->getImageStub();
        }

        return ($width === null) ? $img : $img->getUrl($width, $height, $type);
    }

    /**
     * Возвращает список картинок, привязанных к товару
     *
     * @param boolean $without_first - если true, то не возвращать первое фото
     * @return PhotoImage[]
     */
    function getImages($without_first = false)
    {
        $this->fillImages();
        return ($without_first) ? array_slice($this['images'], 1, null, true) : $this['images'];
    }

    /**
     * Возвращает true, если у объекта есть фото
     */
    function hasImage()
    {
        $this->fillImages();
        return count($this['images']) > 0;
    }

    /**
     * Полное удаление товара
     *
     * @return bool
     * @throws DbException
     * @throws OrmException
     * @throws RSException
     */
    function delete()
    {
        if (empty($this['id']))
            return false;

        //Удаляем фотографии, при удалении товара
        $photoapi = new PhotoApi();
        $photoapi->setFilter('linkid', $this['id']);
        $photoapi->setFilter('type', 'catalog');
        /** @var PhotoImage[] $photo_list */
        $photo_list = $photoapi->getList();
        foreach ($photo_list as $photo) {
            $photo->delete();
        }

        //Удляем связи с директориями
        OrmRequest::make()->delete()
            ->from(new Xdir())
            ->where(array('product_id' => $this['id']))
            ->exec();

        //Удаляем цены
        OrmRequest::make()->delete()
            ->from(new Xcost())
            ->where(array('product_id' => $this['id']))
            ->exec();

        //Удаляем комплектации
        OrmRequest::make()->delete()
            ->from(new Offer())
            ->where(array('product_id' => $this['id']))
            ->exec();

        //Удаляем многомерные комплектации
        OrmRequest::make()->delete()
            ->from(new MultiOfferLevel())
            ->where(array('product_id' => $this['id']))
            ->exec();

        //Удаляем характеристики
        OrmRequest::make()->delete()
            ->from(new Property\Link())
            ->where(array('product_id' => $this['id']))
            ->exec();

        //Удаляем остатки на складах
        OrmRequest::make()->delete()
            ->from(new Xstock())
            ->where(array('product_id' => $this['id']))
            ->exec();

        //Удаляем из поискового индекса
        IndexApi::removeFromSearch($this, $this['id']);

        $ret = parent::delete();

        DirApi::updateCounts(); //Обновляем счетчики у категорий

        return $ret;
    }

    /**
     * Возвращает true если цены на товар заполнены
     *
     * @return bool
     * @throws OrmException
     */
    function hasCost()
    {
        $cost = Xcost::loadByWhere(array('product_id' => $this['id']));
        return (bool)$cost['product_id'];
    }

    /**
     * Возвращает цену товара
     *
     * @param int|string $cost_id - id или Название цены. Если null, то текущая цена у пользователя.
     * @param integer $offer - комплектация
     * @param bool $format - форматировать цену
     * @param bool $inBaseCurrency - возвращать стоимость в базовой валюте
     * @return mixed
     * @throws DbException
     * @throws EventException
     * @throws OrmException
     * @throws RSException
     */
    function getCost($cost_id = null, $offer = null, $format = true, $inBaseCurrency = false)
    {
        $cost = $this->getBaseCost($cost_id, $offer);

        if (!$inBaseCurrency) {
            $cost = CurrencyApi::applyCurrency($cost, $this['_currency']);
        }
        return ($format) ? CustomView::cost($cost) : $cost;
    }

    /**
     * Возвращает базовую цену товара
     *
     * @param int|string $cost_id - id или Название цены. Если null, то текущая цена у пользователя.
     * @param integer $offer - комплектация
     * @return float
     * @throws DbException
     * @throws EventException
     * @throws OrmException
     * @throws RSException
     */
    public function getBaseCost($cost_id = null, $offer = null)
    {
        if ( $this->getUserCost() !== null ) {
            $cost = $this->user_cost;
        } else {
            $offer = (int)$offer;
            $this->fillCost();

            if ($cost_id !== null && !is_numeric($cost_id)) { //Получаем id, если передано название цены
                if (!isset(self::$cost_title_id[$cost_id])) {
                    self::$cost_title_id[$cost_id] = $this->getCostIdByTitle($cost_id);
                }
                if (self::$cost_title_id[$cost_id] === null) {
                    return false;
                }
                $cost_id = self::$cost_title_id[$cost_id];
            }

            if ($offer > 0) {
                $xcost = $this->getOfferCost($offer, $this['xcost']);
            } else {
                $xcost = $this['xcost'];
            }

            if ($cost_id === null) {
                $cost_id = $this['_current_cost_id'];
            }
            $cost = $xcost[$cost_id];
        }
        return $cost;
    }

    /**
     * Возвращает id типа цены по его названию
     *
     * @param string $title - имя типа цен
     * @return int|null
     * @throws OrmException
     * @throws DbException
     */
    protected function getCostIdByTitle($title)
    {
        $type_cost = Typecost::loadByWhere(array(
            'site_id' => SiteManager::getSiteId(),
            'title' => $title,
        ));
        return ($type_cost) ? $type_cost['id'] : null;
    }

    /**
     * Возвращает старую(зачеркнутую) цену, если она есть
     *
     * @param integer $offer - комплектация
     * @param bool $format - форматировать цену
     * @param bool $inBaseCurrency - возвращать стоимость в базовой валюте
     * @return float
     * @throws DbException
     * @throws EventException
     * @throws OrmException
     * @throws RSException
     */
    function getOldCost($offer = null, $format = true, $inBaseCurrency = false)
    {
        $cost = 0;

        $old_cost_id = CostApi::getOldCostId();
        if ($old_cost_id) {
            $cost = $this->getCost($old_cost_id, $offer, $format, $inBaseCurrency);
        }
        
        return $cost;
    }

    /**
     * Возвращает персональную цену для данного товара
     * если у товара указана персональная цена, метод getCost вернёт именно её вне зависимости от параметров
     *
     * @return float|null
     */
    public function getUserCost()
    {
        return $this->user_cost;
    }

    /**
     * Устанавливает персональную цену для данного товара
     * если у товара указана персональная цена, метод getCost вернёт именно её все зависимости от параметров
     *
     * @param float|null $cost - пользовательская цена в базовой валюте
     * @return void
     */
    public function setUserCost($cost)
    {
        $this->user_cost = $cost;
    }

    /**
     * Возвращает цены откорректированные с учетом выбранной комплектации
     *
     * @param integer $offer_key комплектация
     * @param array $xcost массив: ID цены => Значение цены для нулевого offer'а
     * @return array
     * @throws EventException
     * @throws OrmException
     * @throws RSException
     */
    public function getOfferCost($offer_key, $xcost)
    {
        if (!isset($this->offer_xcost[$offer_key])) {
            $this->fillOffers();
            if ($offer_key > 0 && isset($this['offers']['items'][$offer_key])) {
                $offer = $this['offers']['items'][$offer_key]['pricedata_arr'];
                foreach ($xcost as $cost_id => $base) {

                    if (isset($offer['price'][$cost_id]) || !empty($offer['oneprice']['use'])) {
                        if (!empty($offer['oneprice']['use'])) {
                            $price = $offer['oneprice'];
                        } else {
                            $price = $offer['price'][$cost_id];
                        }
                        if (!isset($price['value'])) {
                            $price['value'] = 0;
                        }

                        if ($price['znak'] == '=') {
                            $base = $price['value'];
                        } else {
                            if ($price['unit'] == '%') {
                                $delta = $base * ($price['value'] / 100);
                            } else {
                                $delta = $price['value'];
                            }
                            $base += (float)$delta;
                        }
                        $xcost[$cost_id] = CostApi::roundCost($base); //round($base, 2);
                    }
                }

                $cost_api = new CostApi();
                $xcost = $cost_api->getCalculatedCostList($xcost);
            }
            $this->offer_xcost[$offer_key] = $xcost;

            //Отработаем событие, чтобы достать преобразовать данные
            $event_result = EventManager::fire('product.getoffercost', array(
                'offer_xcost' => $this->offer_xcost[$offer_key],
                'offer' => isset($offer) ? $offer : array(),
                'offer_key' => $offer_key,
                'product' => $this
            ));
            list($this->offer_xcost[$offer_key]) = $event_result->extract();
        }
        return $this->offer_xcost[$offer_key];
    }

    /**
     * Возвращает текущую валюту
     *
     * @return string
     */
    function getCurrency()
    {
        return CurrencyApi::getCurrecyLiter();
    }

    /**
     * Возвращает код текущей валюты
     */
    function getCurrencyCode()
    {
        return CurrencyApi::getCurrecyCode();
    }

    /**
     * Возвращает символ базовой валюты
     *
     * @return string
     */
    function getBaseCurrency()
    {
        $base_currency = CurrencyApi::getBaseCurrency();
        return $base_currency['stitle'];
    }

    /**
     * Возвращает URL страницы товара
     *
     * @param bool $absolute - Если true, то вернет абсолютный URL, иначе относительный
     * @return string
     */
    function getUrl($absolute = false)
    {
        return RouterManager::obj()->getUrl('catalog-front-product', array('id' => $this['_alias']), $absolute);
    }

    /**
     * Возвращает видимые характеристики товара
     *
     * @param bool $cache - кэшировать результат
     * @param bool $exportVisible - если true, то возвращает видимые для эекспорта характеристики товара
     * @return array
     * @throws DbException
     * @throws OrmException
     * @throws EventException
     */
    function getVisiblePropertyList($cache = true, $exportVisible = false)
    {
        if (!$cache || !isset($this->cache_visible_property[$exportVisible])) {
            $this->fillProperty();
            $this->cache_visible_property[$exportVisible] = array();
            foreach ($this['properties'] as $n => $item) {
                $property_list = array();
                foreach ($item['properties'] as $property_id => $property) {
                    if ($exportVisible) {
                        if (!$property['no_export']) {
                            $property_list[$property_id] = $property;
                        }
                    } else {
                        if (!$property['hidden']) {
                            $property_list[$property_id] = $property;
                        }
                    }

                }
                if (count($property_list)) {
                    $this->cache_visible_property[$exportVisible][] = array(
                        'group' => $item['group'],
                        'properties' => $property_list
                    );
                }
            }
        }
        return $this->cache_visible_property[$exportVisible];
    }

    /**
     * Возвращает значение свойста по его имени
     *
     * @param string $name - название свойства
     * @param mixed $default - значение по-умолчанию
     * @param bool $textView - если задано true, то возвращает всегда текстовое значение характеристики
     * @param bool $available - возвращать только те значения, что есть в наличии у товара
     * @return string|null
     */
    function getPropertyValueByTitle($name, $default = null, $textView = true, $available = false)
    {
        if (!isset(self::$property_name_id[$name])) {
            $res = null;
            if (!empty($this['properties'])) {
                foreach ($this['properties'] as $item) {
                    foreach ($item['properties'] as $prop) {
                        if ($prop['title'] == $name) {
                            $res = array(
                                'dir_id' => $prop['parent_id'],
                                'prop_id' => $prop['id']
                            );
                            break 2;
                        }
                    }
                }
                self::$property_name_id[$name] = $res;
            }
        }

        $name_id = isset(self::$property_name_id[$name]) ? self::$property_name_id[$name] : null;
        if ($name_id !== null) {
            $prop = @$this['properties'][$name_id['dir_id']]['properties'][$name_id['prop_id']];
            if (is_object($prop)) {
                $value = !$available ? $prop['value'] : $prop['available_value'];
                return $textView ? $prop->textView($available) : $value;
            }
        }
        return null;
    }

    /**
     * Возвращает значение свойста по его ID
     *
     * @param integer $id - ID свойства
     * @param mixed $default - значение по умолчанию
     * @param boolean $textView - Возвращать в текстовом виде
     * @param boolean $available - Возвращать только те значения, что есть в наличии у товара
     * @return string
     * @throws EventException
     */
    function getPropertyValueById($id, $default = null, $textView = true, $available = false)
    {
        if (!empty($this['properties'])) {
            foreach ($this['properties'] as $item) {
                if (isset($item['properties'][$id])) {
                    /** @var PropertyItem $prop */
                    $prop = $item['properties'][$id];
                    return $textView ? $prop->textView($available) : $prop['value'];
                }
            }
        }
        return $default;
    }

    /**
     * Очищает поля, которые не понадобятся при отображении товара в корзине
     * Это уменьшит объект в сериализованном виде.
     *
     * @return void
     */
    function cleanForBasket()
    {
        $this['description'] = '';
        $this['short_description'] = '';
    }

    /**
     * Возвращает HTML код для блока "рекомендуемые товары"
     * @return ProductDialog
     */
    function getProductsDialog()
    {
        return new ProductDialog('recommended_arr', true, @(array)$this['recommended_arr']);
    }

    /**
     * Возвращает HTML код для блока "сопутствующие товары"
     * @return ProductDialog
     */
    function getProductsDialogConcomitant()
    {
        $product_dialog = new ProductDialog('concomitant_arr', true, @(array)$this['concomitant_arr']);
        $product_dialog->setTemplate('%catalog%/dialog/view_selected_concomitant.tpl');
        return $product_dialog;
    }

    /**
     * Возвращает товары, рекомендуемые вместе с текущим
     *
     * @param bool $return_hidden - Если true, то метод вернет даже не публичные товары. Если false, то только публичные
     * @param bool $add_dir_recommended - Если true, будут добавлены рекоммендуемые из основной категории
     * @return Product[]
     */
    function getRecommended($return_hidden = false, $add_dir_recommended = true)
    {
        $list = array();
        if (isset($this['recommended_arr']['product'])) {
            foreach ($this['recommended_arr']['product'] as $id) {
                $product = new self($id);
                if ($product['id'] && ($return_hidden || $product['public'])) {
                    $list[$id] = $product;
                }
            }
        }
        if ($add_dir_recommended) {
            $list = $this->getMainDir()->getRecommended() + $list;
        }

        return $list;
    }

    /**
     * Возвращает есть ли у товара рекомендуемые
     * Облегченный метод в основном используется для проверки показа блока рекомендуемых
     *
     * @return boolean
     */
    function isHaveRecommended()
    {
        if (isset($this['recommended_arr']['product']) && !empty($this['recommended_arr']['product'])) {
            return true;
        }

        return $this->getMainDir()->isHaveRecommended();
    }

    /**
     * Возвращает товары, сопутствующие для текущего
     *
     * @param bool $add_dir_concomitant - Если true, будут добавлены сопутствующие из основной категории
     * @return Product[]
     */
    function getConcomitant($add_dir_concomitant = true)
    {
        $list = array();
        if (isset($this['concomitant_arr']['product'])) {
            foreach ($this['concomitant_arr']['product'] as $id) {
                $only_one = @$this['concomitant_arr']['onlyone'][$id];
                $concomitant = new self($id);
                if ($concomitant['id']) {
                    $list[$id] = $concomitant;
                    $list[$id]->onlyone = $only_one;
                }
            }
        }
        if ($add_dir_concomitant) {
            $list = $this->getMainDir()->getConcomitant() + $list;
        }
        return $list;
    }

    /**
     * Возвращает есть ли у товара рекомендуемые
     * Облегченный метод в основном используется для проверки показа блока рекомендуемых
     *
     * @return bool
     */
    function isHaveConcomitant()
    {
        if (isset($this['concomitant_arr']['product']) && !empty($this['concomitant_arr']['product'])) {
            return true;
        }

        return $this->getMainDir()->isHaveConcomitant();
    }

    /**
     * Возвращает заголовок МЕТА данных товара, если нет, то берёт из категорий
     *
     * @return string
     * @throws RSException
     */
    function getMetaTitle()
    {
        if (!empty($this['meta_title'])) {
            return $this['meta_title'];
        }

        //Попытаемся получить данные из категории
        $maindir = $this->getMainDir();
        if (!empty($maindir['product_meta_title'])) {
            return $maindir['product_meta_title'];
        }

        //Попытаемся получить данные по умолчанию из конфига модуля
        $config = ConfigLoader::byModule($this);
        if (!empty($config['default_product_meta_title'])) {
            return $config['default_product_meta_title'];
        }

        return '';
    }

    /**
     * Возвращает заданные в админ панели ключевые слова, а если они не заданны,
     * то генерирует новые
     *
     * @return string
     * @throws RSException
     */
    function getMetaKeywords()
    {
        if (!empty($this['meta_keywords'])) {
            return $this['meta_keywords'];
        }

        //Попытаемся получить данные из категории
        $maindir = $this->getMainDir();
        if (!empty($maindir['product_meta_keywords'])) {
            return $maindir['product_meta_keywords'];
        }

        //Попытаемся получить данные по умолчанию из конфига модуля
        $config = ConfigLoader::byModule($this);
        if (!empty($config['default_product_meta_keywords'])) {
            return $config['default_product_meta_keywords'];
        }

        $parts = array(
            $this['title'],
            $this['barcode']
        );

        return implode(',', $parts);
    }

    /**
     * Возвращает описание из карточки товара или генерирует его
     *
     * @return string
     * @throws RSException
     */
    function getMetaDescription()
    {
        if (!empty($this['meta_description'])) {
            return $this['meta_description'];
        }

        //Попытаемся получить данные из категории
        $main_dir = $this->getMainDir();
        if (!empty($main_dir['product_meta_description'])) {
            return $main_dir['product_meta_description'];
        }

        //Попытаемся получить данные по умолчанию из конфига модуля
        $config = ConfigLoader::byModule($this);
        if (!empty($config['default_product_meta_description'])) {
            return $config['default_product_meta_description'];
        }

        if (!empty($this['short_description'])) {
            return str_replace(array("\n", "\r"), ' ', strip_tags($this['short_description']));
        }

        if (!empty($this['description'])) {
            return HelperTools::teaser(str_replace(array("\n", "\r"), ' ', strip_tags($this['description'])), 700);
        }

        if (!empty($this['title'])) {
            return $this['title'];
        }

        return '';
    }

    /**
     * Возвращает тип кнопки, которую нужно отобразить на месте кнопки заказать
     *
     * @return string - basket | unobtainable | advorder
     * @throws RSException
     */
    function getOrderType()
    {
        $config = ConfigLoader::byModule($this);
        if ($this->getNum() > 0 || $config['check_quantity'] == 'N')
            return self::ORDER_TYPE_BASKET;
        return ($config['allow_advanced_order_goods'] == 'N' || $this['disallow_advorder'] == 1) ? self::ORDER_TYPE_UNOBTAINABLE : self::ORDER_TYPE_ADVORDER;
    }

    /**
     * Возвращает артикул в зависимости от комплектации
     *
     * @param integer $offer комплектация
     * @return string
     * @throws OrmException
     */
    function getBarCode($offer)
    {
        $this->fillOffers();
        if (!empty($offer) && $this['offers']['use'] && isset($this['offers']['items'][$offer])) {
            return $this['offers']['items'][$offer]['barcode'];
        } else {
            return $this['barcode'];
        }
    }

    /**
     * Возвращает название комплектации. Если у товара есть комплектации, иначе false
     *
     * @param integer $offer комплектация
     * @return string
     * @throws OrmException
     */
    function getOfferTitle($offer)
    {
        $this->fillOffers();
        if ($this['offers']['use']) {
            if (isset($this['offers']['items'][(int)$offer])) {
                return $this['offers']['items'][(int)$offer]['title'];
            }
        }
        return false;
    }

    /**
     * Возвращает клонированный объект товара
     *
     * @return Product
     * @throws DbException
     * @throws EventException
     * @throws OrmException
     */
    function cloneSelf()
    {
        $this->fillCategories();
        $this->fillCost();
        $this->fillProperty();
        $this->fillOffers();
        $this->fillMultiOffers();
        $this->fillOffersStock();
        $images = $this->getImages(false);

        /** @var Product $clone */
        $clone = parent::cloneSelf();
        $clone->setTemporaryId();
        unset($clone['alias']);
        unset($clone['xml_id']);
        unset($clone['comments']);

        //Клонируем фотографии
        $old_photo_id = array();
        $new_photo_id = array();
        foreach ($images as $image) {
            $old_id = $image['id'];
            $image['linkid'] = $clone['id'];
            $image['id'] = null;
            $image->insert();
            $old_photo_id[] = $old_id;
            $new_photo_id[] = $image['id'];
        }

        $api = new ProductApi();
        $clone['barcode'] = $api->genereteBarcode();

        //Заменяем ссылки на фото и создаем комплектации
        foreach ($this['offers']['items'] as $key => $offer) {
            /** @var Offer $offer */
            $offer['photos_arr'] = str_replace($old_photo_id, $new_photo_id, $offer['photos_arr']);
            $offer['product_id'] = $clone['id'];
            if ($key > 0) { //Если не нулевая комплектация, то и артикулы сделаем разные
                $offer['barcode'] = $clone['barcode'] . "-" . ($key + 1);
            }
            unset($offer['xml_id']); //Очищаем лишние id
            unset($offer['id']);

            $offer->insert(); //Дублируем не дополнительные комплектаци
        }

        return $clone;
    }

    /**
     * Добавить характеристику для сохранения
     *
     * @param integer $property_id - уникальный идентификатор характеристики
     * @param mixed $value - значение характеристики
     * @param integer $is_my - флаг означающий, что нужно добавить флаг перезаписывающий значения установленные через категорию
     *
     */
    public function addProperty($property_id, $value, $is_my = 1)
    {
        if ($this['prop'] === NULL) {
            $this['prop'] = array();
        }

        $new_property = array(
            $property_id => array(
                'id' => $property_id,
                'is_my' => $is_my,
                'value' => $value,
            ),
        );
        $this['prop'] = $new_property + $this['prop'];
    }

    /**
     * Возвращает текст с кратким описание товара
     *
     * @param integer $max_len максимально количество знаков
     * @return string
     */
    public function getShortDescription($max_len = 300)
    {
        $text = !empty($this['short_description']) ? $this['short_description'] : $this['description'];
        return HelperTools::teaser($text, $max_len, false);
    }

    /**
     * Возвращает вес товара с учетом настроек ОСНОВНОЙ категории и настроек модуля
     *
     * @param null|integer $offer - номер комплектации от которой нужно вернуть вес
     * @param null|string $weight_unit - идентификатор единицы измерения, в которй нужно получить вес (список возможных констатнт в \Catalog\Model\Api)
     * @return float
     * @throws RSException
     */
    function getWeight($offer = null, $weight_unit = null)
    {
        $config = ConfigLoader::byModule($this);
        $weight = 0;
        if ($offer !== null) {
            $this->fillOffers();
            if (isset($this['offers']['items'][$offer]) && ($this['offers']['items'][$offer]['weight'] > 0)) {
                $weight = $this['offers']['items'][$offer]['weight'];
            }
        }
        if (!$weight) {
            if ($this['weight']) {
                $weight = $this['weight'];
            } else {
                $dir = $this->getMainDir();
                $weight = ($dir['weight']) ?: $config['default_weight'];
            }
        }
        // Если нужно вернуть результат в конкретной ед. измерения - конвертируем вес из указанной в настройках модуля
        if ($weight_unit !== null) {
            $unit_list = ProductApi::getWeightUnits();
            $catalog_weight_unit = $config['weight_unit'];
            $product_unit_ratio = (isset($unit_list[$catalog_weight_unit]['ratio'])) ? $unit_list[$catalog_weight_unit]['ratio'] : 1;
            $output_unit_ratio = (isset($unit_list[$weight_unit]['ratio'])) ? $unit_list[$weight_unit]['ratio'] : 1;

            $weight = round($weight * $product_unit_ratio / $output_unit_ratio, 3);
        }

        return $weight;
    }

    /**
     * Возвращает true, если необходимо отобразить форму предварительного заказа, иначе false
     *
     * @return bool
     * @throws RSException
     */
    function shouldReserve()
    {
        switch ($this['reservation']) {
            case 'forced':
                return true;
            case 'throughout':
                return false;
            default: {
                $shop_config = ConfigLoader::byModule('shop');
                return ($this->getNum() < 1 && $shop_config['reservation'] && $shop_config['check_quantity']);
            }
        }
    }

    /**
     * Возвращает true, если товар потенциально может быть предзаказан.
     * т.е. у него не установлен запрет на предзаказ и опция в админ панели
     * "разрешить предзаказ товаров с нулевым остатком"
     *
     * @return bool
     * @throws RSException
     */
    function canBeReserved()
    {
        $shop_config = ConfigLoader::byModule('shop');
        return ($this['reservation'] != 'throughout' && $shop_config['reservation']);
    }

    /**
     * Возвращает количество комплектаций
     *
     * @return int
     * @throws OrmException
     */
    function getOfferCount()
    {
        $this->fillOffers();
        if (!isset($this['offers']['items'])) return 0;
        return count($this['offers']['items']);
    }

    /**
     * Возвращает количество для необходимой комплектации или всего товара.
     * Сперва возвращает динамически высчитанное значение из dynamic_num, если таковое есть.
     * иначе - статическое значение из поля num.
     *
     * Только данный метод может отдавать остаток с учетом всех опций в административной панели.
     * Используйте его вместо обращения к свойству num напрямую.
     *
     * @param int|null $offer - номер комплектации (начинается с нуля) или если null, то всего товара
     * @return mixed|Type\AbstractType
     * @throws OrmException
     */
    function getNum($offer = null)
    {
        if ($offer !== null) {
            $this->fillOffers();
            if ($this->getOfferCount() > 0) {
                if (isset($this['offers']['items'][$offer]['dynamic_num'])) {
                    return $this['offers']['items'][$offer]['dynamic_num'];
                }

                if (isset($this['offers']['items'][$offer]['num'])) {
                    return $this['offers']['items'][$offer]['num'];
                }
            }
        }

        if (isset($this['dynamic_num'])) {
            return $this['dynamic_num'];
        }

        return $this['num'];
    }

    /**
     * Возвращает штрихкод указанной комплектации, в случае отсутствия возвращает штрихкод товара
     *
     * @param int $offer - индекс комплектации
     * @return string
     * @throws OrmException
     */
    function getSKU($offer = 0)
    {
        $this->fillOffers();
        if (!empty($this['offers']['items'][$offer]['sku'])) {
            return $this['offers']['items'][$offer]['sku'];
        } else {
            return $this['sku'];
        }
    }

    /**
     * Возвращает список доступных валют
     * Используется в карточке товара в админ. панели
     *
     * @return array
     */
    function getCurrencies()
    {
        $currency_api = new CurrencyApi();
        $currency_api->setOrder('is_base desc, title');
        return $currency_api->getAssocList('id', 'title');
    }

    /**
     * Возвращает Список цен, имеющихся в системе
     *
     * @return array
     * @throws OrmException
     */
    function getCostList()
    {
        if (!isset(self::$cost_list)) {
            $costapi = new Costapi();
            self::$cost_list = $costapi->getList();
        }
        return self::$cost_list;
    }

    /**
     * Возвращает список спецкатегорий, в которых состоит товар
     *
     * @return array of Orm\Dir
     */
    function getMySpecDir()
    {
        $spec = $this->getSpecDirs();
        return array_intersect_key($spec, array_flip($this['xspec'] ?: array()));
    }

    /**
     * Устанавливает используются ли у товаров комплектации.
     * Установленное значение будет импользоваться для быстрого возврата результата методом isOffersUse
     *
     * @param bool | null $bool
     * @return void
     */
    function setFastMarkOffersUse($bool)
    {
        $this->fast_mark_offers_use = $bool;
    }

    /**
     * Возвращает true, если у товара должны использоваться комплектации.
     *
     * @return bool
     * @throws OrmException
     */
    function isOffersUse()
    {
        if ($this->fast_mark_offers_use !== null) {
            return $this->fast_mark_offers_use;
        }
        $this->fillOffers();
        return $this['offers']['use'] && count($this['offers']['items']) > 1;
    }

    /**
     * Устанавливает используются ли у товаров комплектации.
     * Установленное значение будет импользоваться для быстрого возврата результата методом isOffersUse
     *
     * @param bool | null $bool
     * @return void
     */
    function setFastMarkMultiOffersUse($bool)
    {
        $this->fast_mark_multioffers_use = $bool;
    }

    /**
     * Возвращает true, если у товара должны использоваться многомерные комплектации.
     *
     * @return bool
     * @throws OrmException
     */
    function isMultiOffersUse()
    {
        if ($this->fast_mark_multioffers_use !== null) {
            return $this->fast_mark_multioffers_use;
        }
        $this->fillMultiOffers();

        return $this['multioffers']['use'] && count($this['multioffers']['levels']) > 0;
    }

    /**
     * Устанавливает используются ли у товаров виртуальные многомерные комплектации.
     * Установленное значение будет импользоваться для быстрого возврата результата методом isVirtualMultiOffersUse
     *
     * @param bool | null $bool
     * @return void
     */
    function setFastMarkVirtualMultiOffersUse($bool)
    {
        $this->fast_mark_virtual_multioffers_use = $bool;
    }

    /**
     * Возвращает true, если у товара должны использоваться виртуальные многомерные комплектации.
     * @return bool
     */
    function isVirtualMultiOffersUse()
    {
        if ($this['virtual_multioffers'] === null) {
            $this->fillVirtualMultiOffers();
        }
        return $this['virtual_multioffers']['use'] && count($this['virtual_multioffers']['items']) > 0;
    }

    /**
     * Подгружает к многомерным комплектациями фото к вариантам выбора
     * Работает только, если у товара есть как комплектации так и многомерные комплектации
     *
     * @return void
     * @throws OrmException
     */
    function fillMultiOffersPhotos()
    {
        //Если многомерки подгружены или если есть многомерки, но нет комплектаций, кроме основной
        if (($this->isMultiOffersUse() && $this->isOffersUse()) || ($this->isMultiOffersUse() && !$this->isOffersUse() && isset($this['offers']['items'][0]))) {

            foreach ($this['multioffers']['levels'] as $k => $level) {
                if (isset($level['is_photo']) && $level['is_photo']) { //Если флаг c "С фото" стоит
                    $this->fillMultiOffersPhotoValuesByLevel($k, $level);
                    break;
                }
            }
        }
    }

    /**
     * Записывает многомерным комплектациям сведения по фото и характеристикам исходя из переданного уровня многомерной комлпектации
     *
     * @param integer $level_position - номер в списке многомерных комлектаций
     * @param array $level - массив со сведениями об уровне многомерной комлектации
     */
    private function fillMultiOffersPhotoValuesByLevel($level_position, $level)
    {
        static $offers_prop_vals = array(); //Массив для хранения уникальных значений характеристик комплектаций
        $level_title = !empty($level['title']) ? $level['title'] : $level['prop_title'];

        if ($this['images'] === null) { //Если фотографии ещё не подгруженны
            $this->fillImages();
        }
        //Перебираем комплектации, чтобы найти те которые с фото и выставить для нужного нам значения
        foreach ($this['offers']['items'] as $offer) {
            $offer_prop_value = isset($offer['propsdata_arr'][$level_title]) ? $offer['propsdata_arr'][$level_title] : false;

            if (!empty($offer['photos_arr']) && $offer_prop_value && !isset($offers_prop_vals[$offer_prop_value])) { //Если фото заданы и есть характеристики у комплектации

                //Назначаем фото для значения характеристики
                $offers_prop_vals[$offer_prop_value] = $this['images'][$offer['photos_arr'][0]];
            }
        }

        $multioffers = $this['multioffers'];
        $multioffers['levels'][$level_position]['values_photos'] = $offers_prop_vals;
        $this['multioffers'] = $multioffers;
    }

    /**
     * Возвращает true, если имеется возможность купить товар в комплектации по-умолчанию
     * Если отключен контроль остатков - возвращает true
     * Если включен контроль остатков - общее количество товара и остаток выбранной комплектации больше нуля - возвращает true
     * В остальных случаях - false
     *
     * @return false
     * @throws RSException
     */
    function isAvailable()
    {
        $shop_config = ConfigLoader::byModule('shop');
        if (!$shop_config || !$shop_config['check_quantity']) {
            return true;
        }

        if ($this->getNum() <= 0) {
            return false;
        }
        return !$this->isOffersUse() || $this->getNum(0) > 0;
    }

    /**
     * Возвращает объект бренда товара
     *
     * @return Brand
     */
    function getBrand()
    {
        if (!$this['brand']) {
            $this['brand'] = new Brand($this['brand_id']);
        }
        return $this['brand'];
    }


    /**
     * Получает остатки у комплектаций по складам товара в виде массива
     * Ключ - id склада
     * Значение - информация по складам
     *
     * @return array
     * @throws DbException
     */
    function getWarehouseStock()
    {
        if ($this->stock === null) {
            $this->stock = OrmRequest::make()
                ->select('X.*')
                ->from(new Xstock(), 'X')
                ->join(new Offer(), 'O.id = X.offer_id', 'O')
                ->where(array(
                    'X.product_id' => $this['id'],
                ))
                ->orderby('O.sortn ASC')
                ->exec()
                ->fetchSelected('warehouse_id', null, true);
        }

        return $this->stock;
    }

    /**
     * Получает общие остатки по складам товара в виде массива
     * Ключ - id склада
     * Значение - количество товаров на складе
     *
     * @return float[]
     * @throws DbException
     */
    function getWarehouseFullStock()
    {
        if ($this->full_stock === null) {
            $this->full_stock = OrmRequest::make()
                ->select('warehouse_id, SUM(stock)as cnt')
                ->from(new Xstock())
                ->where(array(
                    'product_id' => $this['id']
                ))
                ->groupby('warehouse_id')
                ->exec()->fetchSelected('warehouse_id', 'cnt');
        }
        return $this->full_stock;
    }

    /**
     * Возвращает необходимую информацию для отображения остатков по складам на сайте
     * - список складов
     * - количество диапазонов остатков
     *
     * @param bool $cache - использовать кэш
     * @return array
     * @throws RSException
     */
    function getWarehouseStickInfo($cache = true)
    {
        if (!$cache || $this->cache_warehouse_stick === null) {
            $result = array();

            $config = ConfigLoader::byModule($this);

            //Загружаем все имеющиеся склады
            $result['warehouses'] = WareHouseApi::getAvailableWarehouses(true, false, true);
            $result['stick_ranges'] = range(1, count(explode(",", $config['warehouse_sticks'])));

            $this->cache_warehouse_stick = $result;
        }

        return $this->cache_warehouse_stick;
    }

    /**
     * Возвращает количество складов, на которых доступен товар
     *
     * @param integer $offer - Номер комплектации
     * @param bool $cache - использовать кэш
     * @return int
     * @throws RSException
     */
    function getAvailableWarehouses($offer = 0, $cache = true)
    {
        $info = $this->getWarehouseStickInfo($cache);
        $count = 0;
        foreach ($info['warehouses'] as $warehouse) {
            $sticks = $this['offers']['items'][$offer]['sticks'][$warehouse['id']];
            if ($sticks) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Устанавливает, что при сохранении комплектаций, нужно учитывать поле _propsdata,
     * в котором характеристики комплектаций находятся в денормализованном виде.
     *
     * @param mixed $bool
     */
    function useOffersUnconvertedPropsdata($bool = true)
    {
        $this->use_offers_unconverted_propsdata = $bool;
    }

    /**
     * Возвращает привязанные файлы к товару
     *
     * @param string|string[] $access - идентификатор(ы) уровня доступа.
     * @return OrmFile[]
     * @throws EventException
     * @throws OrmException
     */
    function getFiles($access = null)
    {
        if ($access === null) {
            $access = array(FilesType\CatalogProduct::ACCESS_TYPE_VISIBLE);
            if (Auth::isAuthorize()) {
                $access[] = FilesType\CatalogProduct::ACCESS_TYPE_AUTHORIZED;
            }
        }
        $access = (array)$access;
        $result = array();
        if (ModuleManager::staticModuleExists('files') && !empty($access)) {
            $file_api = new FileApi();
            $file_api->setFilter('link_id', $this['id']);
            $file_api->setFilter('link_type_class', 'files-catalogproduct');
            $file_api->setFilter('access', $access, 'in');
            $result = $files = $file_api->getList();

            // Событие для модификации списка файлов
            $event_result = EventManager::fire('product.files.list', array(
                'files' => $result,
                'product' => $this,
                'access' => $access,
            ));
            list($result) = $event_result->extract();
        }

        return $result;
    }

    /**
     * @deprecated (19.06) - для получения объекта габаритов товара следует использовать метод getDimensionsObject()
     * Устанавливает габариты товара по умолчанию
     *
     * @param integer $width - ширина товара в условных единицах
     * @param integer $height - высота товара в условных единицах
     * @param integer $depth - глубина товара в условных единицах
     */
    function setDefaultProductDimensions($width, $height, $depth)
    {
        $this['_delivery_width'] = $width ? $width : 0;
        $this['_delivery_height'] = $height ? $height : 0;
        $this['_delivery_depth'] = $depth ? $depth : 0;
    }

    /**
     * @deprecated (19.06) - для получения объекта габаритов товара следует использовать метод getDimensionsObject()
     * Возвращает габариты товара по умолчанию
     *
     * @param string $dimention_type - тип габарита (width|height|depth). Если null - то вернёт массив значений
     *
     * @return integer|array
     */
    function getDefaultProductDimensions($dimention_type = null)
    {
        if (!$dimention_type || !in_array($dimention_type, array('width', 'height', 'depth'))) {
            return array(
                'width' => $this['_delivery_width'],
                'height' => $this['_delivery_height'],
                'depth' => $this['_delivery_depth'],
            );
        } else {
            return $this['_delivery_' . $dimention_type];
        }
    }

    /**
     * Возвращает тип кнопки для показа в зависимости от переданной комплектации. Купить, заказать, не показывать. (buy|reservation|none)
     *
     * @param integer $offer_sortn - сортировочный индекс комплектации
     * @return string
     * @throws RSException
     */
    function getButtonTypeByOffer($offer_sortn)
    {
        //Если только предзаказ
        if ($this->shouldReserve()) {
            return 'reservation';
        }

        //Если нет контроля остатков, то всегда купить можно.
        $shop_config = ConfigLoader::byModule('shop');
        if (!$shop_config || !$shop_config['check_quantity']) {
            return 'buy';
        }

        //Проверим конкретную комплектацию
        if ($this->getNum($offer_sortn) < 1 && $shop_config['reservation']) { //Если нет в наличии и можно заказать
            return 'reservation';
        } elseif ($this->getNum($offer_sortn) > 0) {
            return 'buy';
        }

        return 'none';
    }

    /**
     * Возвращает характеристики, которые нужно отобразить в списке товаров в конкретной категории $dir
     *
     * @param Dir $dir объект текущей категории
     * @return array
     * @throws DbException
     * @throws OrmException
     * @throws EventException
     */
    function getListProperties(Dir $dir = null)
    {
        if (!$dir || !$dir['id']) {
            $dir = $this->getMainDir();
        }

        $id_list = $dir['in_list_properties_arr'] ?: array();
        $result = array();

        if ($id_list) {
            $properties = $this->fillProperty();
            foreach ($properties as $item) {
                foreach ($item['properties'] as $prop) {
                    if (in_array($prop['id'], $id_list)) {
                        $result[$prop['id']] = $prop;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Возвращает минимальную цену за товар, если существуют различия в стоимости комплектаций
     *
     * @param integer|null $cost_id ID или название цены. Если null, то будет использована цена по умолчанию
     * @param bool $format если true, то будет возвращена строка "от 12 500", в противном случае 12500.00
     * @param boolean $in_base_currency если true, то будет возвращена цена всегда в базовой валюте
     * @param bool $has_difference возвращает в данной переменно true, если цены комплектаций отличаются
     * @return float|string
     * @throws DbException
     * @throws EventException
     * @throws OrmException
     * @throws RSException
     */
    function getMinPrice($cost_id = null, $format = true, $in_base_currency = false, &$has_difference = null)
    {
        $has_difference = false;
        $min_price = null;
        $max_price = null;

        if ($this['group_id']) {
            $costs = (new OrmRequest)
                ->select('X.cost_val')
                ->from(new Xcost(), 'X')
                ->join(new Product(), 'P.id = X.product_id','P')
                ->where([
                    'P.site_id' => $this['site_id'],
                    'P.group_id' => $this['group_id'],
                    'cost_id' => CostApi::getUserCost(),
                ])
                ->orderby('X.cost_val')
                ->exec()->fetchSelected(null, 'cost_val');

            $min_price = reset($costs);
            $max_price = end($costs);
        } else {
            $offers = $this->fillOffers();
            foreach ($offers['items'] as $key => $offer) {
                $price = $this->getCost($cost_id, $key, false, $in_base_currency);
                if ($price < $min_price || $min_price === null) {
                    $min_price = $price;
                }
                if ($price > $max_price || $max_price === null) {
                    $max_price = $price;
                }
            }
        }

        $has_difference = ($min_price != $max_price);

        if ($format) {
            return ($has_difference ? t('от') . ' ' : '') . CustomView::cost($min_price);
        }

        return $min_price;
    }

    /**
     * Возвращает шаг количества товара
     *
     * @param bool $cache - использовать кеш
     * @return float
     * @throws EventException
     * @throws RSException
     */
    function getAmountStep($cache = true)
    {
        if (!$this->cache_amount_step || !$cache) {
            $amount_step = 1;
            if ((float)$this['amount_step']) {
                $amount_step = (float)$this['amount_step'];
            } elseif ((float)$this->getUnit('amount_step')) {
                $amount_step = (float)$this->getUnit('amount_step');
            }

            $event_result = EventManager::fire('product.amountstep', array(
                'product' => $this,
                'amount_step' => $amount_step,
            ));
            $result_data = $event_result->getResult();
            $amount_step = $result_data['amount_step'];

            $this->cache_amount_step = $amount_step;
        }
        return $this->cache_amount_step;
    }

    /**
     * Проверка есть ли у какой то из комплектаций персональные характеристики
     *
     * @return bool
     */
    function checkPropExist()
    {
        foreach ($this['offers']['items'] as $item) {
            if (!empty($item["propsdata_arr"])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Возвращает объект габаритов товара
     *
     * @return ProductDimensions
     * @throws RSException
     */
    public function getDimensionsObject()
    {
        if ($this->dimensions_object === null) {
            $this->dimensions_object = new ProductDimensions($this);
        }
        return $this->dimensions_object;
    }

    /**
     * Возвращает специальный флаг, список возможных флагов находится в константах класса
     *
     * @param string $flag - флаг
     * @return bool
     */
    public function getFlag($flag)
    {
        return (isset($this->flags[$flag])) ? $this->flags[$flag] : false;
    }

    /**
     * Устанавливает специальный флаг, список возможных флагов находится в константах класса
     *
     * @param string $flag - флаг
     * @param bool $value - значение
     * @return void
     */
    public function setFlag(string $flag, $value = true): void
    {
        $this->flags[$flag] = $value;
    }
}
