<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Importers;

use Catalog\Model\OfferApi;
use Catalog\Model\Orm\Brand;
use Catalog\Model\Orm\Dir;
use Catalog\Model\Orm\Offer as OrmOffer;
use Catalog\Model\Orm\Product;
use Catalog\Model\Orm\Property\Item as PropertyItem;
use Catalog\Model\Orm\Property\ItemValue as PropertyItemValue;
use Catalog\Model\Orm\Property\Link as PropertyLink;
use Catalog\Model\Orm\Unit;
use Exchange\Model\Api as ExchangeApi;
use Exchange\Model\Importers\CatalogProperty as ImporterCatalogProperty;
use Exchange\Model\Log;
use Photo\Model\Orm\Image;
use Photo\Model\PhotoApi;
use RS\Event\Manager as EventManager;
use RS\Helper\Tools as Tools;
use RS\Helper\Transliteration;
use RS\Orm\Request as OrmRequest;
use RS\Site\Manager as SiteManager;
use Shop\Model\Orm\Tax;
use Shop\Model\Orm\TaxRate;
use Shop\Model\RegionApi;

class CatalogProduct extends AbstractImporter
{
    const SESSION_KEY        = "property_ids"; //Сессионный ключ для массива в сессии со свойствами, для последуеющей замены типа на список
    const SESS_KEY_GOODS_IDS = "products_ids"; //Сессионный ключ для массива c товарами которые редактировались
    const SESS_KEY_NO_OFFER_PARAMS_FLAG = "no_offer_param"; //Сессионный ключ флаг, который будет говорить, о том, что нельзя использовать характистики товара(нужно для некоторых версий CommerceML)

    static public $pattern = '/Товар$/i';
    static public $title   = 'Импорт Товаров';

    static private $_cache_brands           = array();
    static private $_cache_categories       = array();
    static private $_cache_properties       = array();
    static private $_cache_properties_types = array();
    static private $_cache_taxes            = array();
    static private $_cache_units_by_code    = array(); //Кэш по коду единицы измерения
    static private $_cache_units_by_name    = array(); //Кэш по наименованию единицы измерения
    static private $_cache_import_hash      = null; //Кэш по хэшам импорта

    public function init()
    {
        if (self::$_cache_import_hash == null) {
            self::$_cache_import_hash = OrmRequest::make()
                ->select('id, xml_id, import_hash')
                ->from(new Product())
                ->where('xml_id > "" and import_hash > ""')
                ->where(array('site_id' => SiteManager::getSiteId()))
                ->exec()->fetchSelected('xml_id');
        }
    }

    public function import(\XMLReader $reader)
    {
        $config = $this->getConfig();
        $import_hash = md5($this->getSimpleXML()->asXML());
        $xml_id = $this->getProductXMLId();

        if (isset(self::$_cache_import_hash[$xml_id]) && self::$_cache_import_hash[$xml_id]['import_hash'] == $import_hash) {
            Log::w(t("Нет изменений в товаре: ") . (string)$this->getSimpleXML()->Наименование . t(" Артикул:") . (string)$this->getSimpleXML()->Артикул);
            OrmRequest::make()
                ->update(new Product())
                ->set(array('processed' => 1))
                ->where(array(
                    'xml_id' => $xml_id,
                    'site_id' => SiteManager::getSiteId(),
                ))
                ->exec();

            // Заносим товар в сессию для манипуляций по завершению импорта
            $_SESSION[self::SESS_KEY_GOODS_IDS][] = self::$_cache_import_hash[$xml_id]['id'];
        } else {
            Log::w(t("Импорт товара: ") . (string)$this->getSimpleXML()->Наименование . t(" Артикул:") . (string)$this->getSimpleXML()->Артикул);

            $barcode = Tools::toEntityString($this->getSimpleXML()->Артикул);
            $title = Tools::toEntityString($this->getSimpleXML()->Наименование);
            // Если включена настройка "Идентифицировать товары по артикулу" - обновим xml_id товара
            if ($config['product_uniq_field'] != 'xml_id') {
                $q = OrmRequest::make()
                    ->update(new Product())
                    ->set(array('xml_id' => $xml_id))
                    ->where(array('site_id' => SiteManager::getSiteId()))
                    ->limit(1);

                switch ($config['product_uniq_field']) {
                    case 'barcode':
                        if ($barcode) {
                            $q->where(array('barcode' => $barcode));
                        }
                        break;
                    case 'title':
                        if ($title) {
                            $q->where(array('title' => $title));
                        }
                        break;
                }
                $q->exec();
            }

            $categories = array();
            if ($this->getSimpleXML()->Группы->Ид != null) {
                foreach ($this->getSimpleXML()->Группы->Ид as $one) {
                    $catid = self::getCategoryIdByXmlId((string)$one);
                    if ($catid) {
                        $categories[] = $catid;
                    }
                }
            }
            if (empty($categories)) {
                $cat_for_catless_porducts = new Dir($config['cat_for_catless_porducts']);
                if (!empty($cat_for_catless_porducts['id'])) {
                    $categories[] = $cat_for_catless_porducts['id'];
                }
            }

            // Создаем продукт и заполняем поля, которые будут обновлены в любом случае
            $product = new Product();
            $product['site_id'] = SiteManager::getSiteId();
            $product['xml_id'] = $xml_id;
            $product['sku'] = (string)$this->getSimpleXML()->Штрихкод;
            $product['processed'] = 1; //Флаг обработанного товара
            $product['import_hash'] = $import_hash;
            $product['dateof'] = date('c');
            if ($this->getSimpleXML()->Статус == 'Удален') {
                $product['public'] = 0;
            } elseif (!$config['hide_new_products']) {
                $product['public'] = 1;
            }

            // Заполняем бренд товара, если он присутствует в выгрузке
            if ($config['import_brand'] && $this->getSimpleXML()->Изготовитель) {
                $brand_xml_id = Tools::toEntityString($this->getSimpleXML()->Изготовитель->Ид);
                $brand_title = Tools::toEntityString($this->getSimpleXML()->Изготовитель->Наименование);
                $product['brand_id'] = self::getBrandIdByXmlId($brand_xml_id, $brand_title);
            }

            // Выбираем поля продукта, обновление которых может быть отключено через настройки модуля
            $product_data = array();
            $product_data['title'] = $title;
            $product_data['barcode'] = $barcode;
            $product_data['description'] = Tools::toEntityString($this->getSimpleXML()->Описание);
            $product_data['short_description'] = Tools::toEntityString($this->getSimpleXML()->Описание);

            $rekvisits = array(); // Совокупные реквизиты

            // Стандарное располжение реквизитов
            if ($this->getSimpleXML()->ЗначенияРеквизитов->ЗначениеРеквизита != null) {
                foreach ($this->getSimpleXML()->ЗначенияРеквизитов->ЗначениеРеквизита as $one) {
                    $rekvisits[] = $one;
                }
            }

            // В некоторых версиях некоторые реквизиты выпадают из родительского тэга и находятся непосредственно в теге <Товар>
            if ($this->getSimpleXML()->ЗначенияРеквизитов->ЗначениеРеквизита != null) {
                foreach ($this->getSimpleXML()->ЗначениеРеквизита as $one) {
                    $rekvisits[] = $one;
                }
            }

            // Перебираем реквизиты
            foreach ($rekvisits as $one) {
                // Заполняем "Вес"
                if ($one->Наименование == "Вес") {
                    $product_data['weight'] = Tools::toEntityString($one->Значение);
                }
                // Заполняем "Краткое описание"
                if ($one->Наименование == "Полное наименование") {
                    $product_data['short_description'] = Tools::toEntityString($one->Значение);
                }
                // Если передано описание в формате HTML, то используем его
                if ($one->Наименование == "ОписаниеВФорматеHTML") {
                    $product_data['description'] = htmlspecialchars_decode($one->Значение);
                }
            }
            // Заполняем данными продукт
            $product->getFromArray($product_data);

            $product['xdir'] = $categories;

            // В случает product_update_dir = 0, категория у товара обновлятся не будет
            $product->keepUpdateProductCategory($config['product_update_dir']);

            // В случае catalog_keep_spec_dirs = 1, все прежние связи со спец-категориями будут сохранены
            $product->keepSpecDirs($config['catalog_keep_spec_dirs']);

            // Настройка "Транслитерировать символьный код из названия при _добавлении_ элемента или раздела"
            if ($config['catalog_translit_on_add']) {
                $uniq_postfix = hexdec(substr(md5($xml_id), 0, 4));
                $product['alias'] = Transliteration::str2url(Tools::unEntityString($product['title']), true, 140) . "-" . $uniq_postfix;
                $product['alias'] = preg_replace('/\(\)/', '', $product['alias']);
            }

            // Список полей, которые будут обновлены, если товар уже существует в нашей базе
            $on_duplicate_update_fields = array('xml_id', 'title', 'barcode', 'description', 'short_description', 'processed', 'import_hash');
            if (!$config['hide_new_products']) {
                $on_duplicate_update_fields[] = 'public';
            }
            if ($config['import_brand'] && $this->getSimpleXML()->Изготовитель) {
                $on_duplicate_update_fields[] = 'brand_id';
            }

            // Если обновлять категории
            if ($config['product_update_dir']) {
                $on_duplicate_update_fields[] = 'maindir';
            }

            // Исключаем поля, которые помечены как "не обновлять" в настройках модуля
            $on_duplicate_update_fields = array_diff($on_duplicate_update_fields, (array)$config['dont_update_fields']);

            // Настройка "Транслитерировать символьный код из названия при _обновлении_ элемента или раздела"
            if ($config['catalog_translit_on_update']) {
                $on_duplicate_update_fields[] = 'alias';
            }


            // Загрузка базовой единицы (штуки, килограммы и т.п.)
            if ($this->importBaseUnit($product)) {
                $on_duplicate_update_fields[] = 'unit';
            }

            // Загрузка налогов
            if ($this->importTaxes($product)) {
                $on_duplicate_update_fields[] = 'tax_ids';
            }

            $product['dont_save_offers'] = true; // флаг, предотвращающий перезапись комплектацию
            $product->setFlag(Product::FLAG_DONT_UPDATE_DIR_COUNTER); //Не обновляем счетчики у категорий. Обновим их один раз в конце импорта
            $product->setFlag(Product::FLAG_DONT_RESET_IMPORT_HASH);
            // Вставка _ИЛИ_ обновление товара
            $product->insert(false, $on_duplicate_update_fields, array('site_id', 'xml_id'));

            // Если во время вставки произошла ошибка, то бросаем исключение
            if ($product->hasError()) {
                throw new \Exception(join(", ", $product->getErrors()));
            }

            // Заносим товар в сессию для манипуляций по завершению импорта
            $_SESSION[self::SESS_KEY_GOODS_IDS][] = $product['id'];

            // Загрузка изображения товара
            $this->importImages($product);

            // Очистка свойств
            $prop_delete = OrmRequest::make()
                ->from(new PropertyLink())
                ->where(array(
                    'product_id' => $product['id'],
                ))
                ->delete();
            // Если стоит настройка не удалять характеристики созданные на сайте
            if ($config['dont_delete_prop']) {
                $prop_delete->where('xml_id !=""');
            }
            $prop_delete->exec();


            // Загрузка Характеристик товара
            $this->importCharacteristics($product); // Это будет работать только при выгрузке из старой 1C. В новой версии характеристики находятся в offers.xml

            // Загрузка Свойств товара
            $this->importProperties($product, $on_duplicate_update_fields);

            if ($config['product_uniq_field'] != 'xml_id') {
                $offer_api = new OfferApi();
                $offer_api->setFilter(array(
                    'product_id' => $product['id'],
                    'xml_id:is' => 'NULL',
                ));
                foreach ($offer_api->getList() as $offer) {
                    $offer->delete();
                }
            }

            EventManager::fire('exchange.catalogproduct.after', array(
                'product' => $product,
                'importer' => $this
            ));
        }
    }

    /**
    * Импорт характеристик (для старой версии 1с, где характеристики содержались в Товаре а не в Предложении)
    * Для новой схемы начиная с 2.07 проскакиеваем импорт этих комплектаций
    * 
    * @param Product $product
    */
    private function importCharacteristics(Product $product)
    {
        $config = $this->getConfig();
        //Если нет тегов Характеристика товара
        $props_nodes = $this->getSimpleXML()->ХарактеристикиТовара->ХарактеристикаТовара;
        if($props_nodes == null){
            return;
        }
        //Проверим, если есть <Ид> у характеристик товара, то ничего не импортируем
        //Изменение внесено в связи с выходом CommerceML2 2.07 
        //В некоторых версиях(новых) при 2.07 значение характеристик невозможно определить
        //Поэтому и использовать их тоже нельзя
        if (isset($this->getSimpleXML()->ХарактеристикиТовара->ХарактеристикаТовара[0]->Ид)){
            $_SESSION[self::SESS_KEY_NO_OFFER_PARAMS_FLAG] = true; //Включим флаг показыващий этот особый тип CommerceML
            return;
        }
        
        $props = array();
        foreach($props_nodes as $one){
            $props[Tools::toEntityString((string)$one->Наименование)] = Tools::toEntityString((string)$one->Значение);
        } 
        // Сохнаняем характеристики в дефолтное товарное предложение в таблицу "product_offer"
        $offer = new OrmOffer();
        $offer['site_id']     = SiteManager::getSiteId();
        $offer['product_id']  = $product['id'];
        $offer['title']       = Tools::toEntityString($this->getSimpleXML()->Наименование);
        $offer['barcode']     = Tools::toEntityString($this->getSimpleXML()->Артикул);
        $offer['xml_id']      = $this->getProductXMLId();  // В этом случае xml_id предложения совпадает с xml_id товара
        $offer['propsdata']   = serialize($props);       // Характеристики хранятся в сериализованном виде в поле propsdata
        $offer['processed']   = 1; //Флаг обработанной комплектации

        //Если установлен флаг уникализировать артикулы у комплектаций. Сложим артикул товара и уникальный "хвост"
        if ($config['unique_offer_barcode']){
           $uniq_tail = strtoupper(mb_substr(md5($offer['barcode'].$offer['title'].$offer['xml_id']), 0, 6));
           $offer['barcode'] = $product['barcode']."-".$uniq_tail;
        }
        
        //Поля которые, будут обновлены при совпадении строки
        $on_duplicate_update_fields = array_diff_key(array('title', 'barcode', 'pricedata', 'propsdata','processed'), $config['dont_update_offer_fields']);
        
        $offer->insert(false, $on_duplicate_update_fields, array('xml_id', 'site_id'));
    }

    /**
     * Импорт свойств товаров
     *
     * @param Product $product
     * @param string[] $on_duplicate_update_fields
     */
    private function importProperties(Product $product, $on_duplicate_update_fields)
    {
        $config = $this->getConfig();

        $props_nodes = $this->getSimpleXML()->ЗначенияСвойств->ЗначенияСвойства;
        if ($props_nodes == null) {
            return;
        }

        // Смотреть ли на разделитель в тексте (это флаг для множественного свойства)
        // Для текстовых полей с символом радлителя указанном в настройках (Например ";")
        $separator = $config['multi_separator_fields'];

        // Для каждого свойства товара
        foreach ($props_nodes as $one) {
            $prop_id = self::getPropertyIdByXmlId((string)$one->Ид);

            $value = (string)$one->Значение;

            /**
             * Элемент "Значение" может содержать как само значение, так и xml_id значения.
             * На данном этапе мы не знаем что именно в нем находится.
             * Будем предполагать, что в нем находится xml_id.
             * Если данного xml_id в справочникике не найдется, значит это само значение
             */

            $value_by_xml_id = ImporterCatalogProperty::getPropertyAllowedValueByXmlId($value);

            // Если элемент "Значение" содержит xml_id 
            if ($value_by_xml_id !== null) {
                // Перезаписываем значение
                $value = $value_by_xml_id;
            }

            // Пустые значения свойств игнорируются
            if (trim($value) === "") {
                continue;
            }

            // Импортируем вес из характеристики
            if ($config['weight_property'] && $config['weight_property'] == $prop_id && is_numeric($value)) {
                $product['weight'] = $value;
                $on_duplicate_update_fields[] = 'weight';
                $product->insert(false, $on_duplicate_update_fields, array('site_id', 'xml_id'));
            }

            if (!empty($separator)) { //Если флаг разделителя в настройках указан делаем проверку на множественное свойство
                $value = trim($value, $separator);
                if (strpos($value, $separator) !== false) { //Если разделитель найден
                    $values = explode($separator, $value);
                    foreach ($values as $val) {
                        $this->insertProperty($product['id'], $product['xml_id'], $prop_id, $val);
                    }
                    $this->setUpdatableProperty($prop_id); //Устанавливает свойства, для обновления типа
                } else {
                    $this->insertProperty($product['id'], $product['xml_id'], $prop_id, $value);
                }
            } else { //Если флаг разделителя в настройках не указан, то вставляем
                $this->insertProperty($product['id'], $product['xml_id'], $prop_id, $value);
            }
        }
    }

    /**
     * Вставляет в БД значение импортированного свойства для товара
     *
     * @param integer $product_id - id товара
     * @param string $xml_id - внешний идентификатор
     * @param integer $prop_id - id свойства
     * @param string $value - значение свойства
     */
    private function insertProperty($product_id, $xml_id, $prop_id, $value)
    {
        $value_escaped = Tools::toEntityString((string)$value);
        $type = self::getPropertyTypeById($prop_id);
        if (in_array($type, PropertyItem::getListTypes())) {
            //Если это списковая характеристика, то добавляем возможное значение характеристики
            $val_list_id = PropertyItemValue::getIdByValue($prop_id, $value_escaped);
        } else {
            $val_list_id = null;
        }

        $prop_link = new PropertyLink();
        $prop_link['product_id'] = $product_id;
        $prop_link['prop_id'] = $prop_id;
        $prop_link['val_str'] = $value_escaped;
        $prop_link['val_int'] = ($value == 'true') ? 1 : floatval(str_replace(array("\xc2\xa0", " ", "\r","\n"), '', $value));
        $prop_link['val_list_id'] = $val_list_id;
        $prop_link['xml_id'] = $xml_id;

        $prop_link->insert();
    }

    /**
     * Устанавливает в массив свойства у которых будет изменён тип в дальнейшем
     *
     * @param integer $property_id - id свойства для последубщего обновления типа
     */
    private function setUpdatableProperty($property_id)
    {
        if (!isset($_SESSION[self::SESSION_KEY][$property_id])) {
            $_SESSION[self::SESSION_KEY][$property_id] = true;
        }
    }

    /**
     * Возращает массив со свойствами для обновления
     *
     * @return array - возвращает массив со свойствами
     */
    public static function getPropertiesToUpdate()
    {
        if (!empty($_SESSION[self::SESSION_KEY])) {
            return array_keys($_SESSION[self::SESSION_KEY]);
        }
        return array();
    }

    /**
     * Импорт изображений товра
     *
     * @param Product $product
     * @throws \Exception
     */
    private function importImages(Product $product)
    {
        $xml_id = (string)$this->getProductXMLId();

        if (!(string)$this->getSimpleXML()->Картинка) {
            return;
        }

        // Для каждого изображения
        $exists_photos_id = array();
        foreach ($this->getSimpleXML()->Картинка as $one) {

            $path = ExchangeApi::getInstance()->getDir() . DS . $one;
            //Проверим с каким расширением передан файл
            $path_parts = pathinfo($path);
            $extention = $path_parts['extension'];

            if (!in_array(strtolower($extention), array('png', 'jpg', 'jpeg', 'gif', 'tiff'))) {
                continue;
            }

            //Проверяем, присутствует ли данное фото у товара
            $image = OrmRequest::make()
                ->from(new Image())
                ->where(array(
                    'site_id' => SiteManager::getSiteId(),
                    'extra' => $xml_id,
                    'filename' => basename($path),
                    'linkid' => $product['id'],
                ))
                ->object();

            //Если фото существует и оно ещё не было загружено
            if (file_exists($path) && !$image) {

                // Привязываем новую картинку
                $photoapi = new PhotoApi();
                $image = $photoapi->addFromUrl($path, 'catalog', $product->id, true, $xml_id);
                if (!$image) {
                    throw new \Exception(implode(", ", $photoapi->getUploadError()) . t(". Товар с артикулом: %0. Ид = %1", array(
                            $product['barcode'],
                            $xml_id,
                        )), 0);
                }
            }
            //Если фото удачно загружено
            if ($image) {
                $exists_photos_id[] = $image['id'];
            }
        }

        //Удаляем фото, не присутствующие в выгрузке
        $q = OrmRequest::make()
            ->delete()
            ->from(new Image)
            ->where(array(
                'site_id' => SiteManager::getSiteId(),
                'extra' => $xml_id,
            ));

        if ($exists_photos_id) {
            $q->where("id NOT IN (" . implode(",", $exists_photos_id) . ')');
        }
        $q->exec();
    }

    /**
     * Импорт налогов из тега <СтавкиНалогов>
     * Парсит список налогов товара
     * Вставляет налог в справочник order_tax (если его еще нет)
     * Вставляет ставку налога в таблицу связи order_tax_rate
     * Привязывает товар к списку налогов через поле tax_ids (перечисляя идентификаторы через запятую)
     *
     * @param Product $product
     * @return bool
     */
    private function importTaxes(Product $product)
    {
        if (!(string)$this->getSimpleXML()->СтавкиНалогов->СтавкаНалога) {   // Если налогов нет, ничего не делаем
            return false;
        }

        $product_taxes = array();

        // Для каждого налога этого товара
        foreach ($this->getSimpleXML()->СтавкиНалогов->СтавкаНалога as $one) {
            $alias = Transliteration::str2url($one->Наименование . "-" . $one->Ставка);
            $tax_id = $this->getTaxIdByAlias($alias);
            // Если такого налога в системе ще нет
            if ($tax_id === false) {
                $default_region = RegionApi::getDefaultRegion();
                // Вставляем налог
                $tax = new Tax();
                $tax['alias'] = $alias;
                $tax['title'] = Tools::toEntityString($one->Наименование) . ', ' . $one->Ставка . '%';
                $tax['description'] = $tax['title'];
                $tax['user_type'] = 'all';
                $tax['included'] = 1;
                $tax['enabled'] = 1;
                $tax->insert();
                $tax_id = $tax['id'];

                // Вставляем процент налога
                $tax_rate = new TaxRate();
                $tax_rate['tax_id'] = $tax->id;
                $tax_rate['region_id'] = $default_region['id']; //Россия
                $tax_rate['rate'] = (string)$one->Ставка;
                $tax_rate['insert()'];

            }
            $product_taxes[] = $tax_id;
        }

        // Прикрепляем налоги к продукту
        $product['tax_ids'] = join(',', $product_taxes);
        return true;
    }

    /**
     * Импорт единиц измерения
     *
     * @param Product $product
     * @return bool
     */
    private function importBaseUnit(Product $product)
    {
        if (!(string)$this->getSimpleXML()->БазоваяЕдиница) {   // Если единицы нет, ничего не делаем
            return false;
        }
        $code = @$this->getSimpleXML()->БазоваяЕдиница['Код'];
        $inter_sokr = @$this->getSimpleXML()->БазоваяЕдиница['МеждународноеСокращение'];
        $full_title = $this->getSimpleXML()->БазоваяЕдиница['НаименованиеПолное'];
        $short_title = (string)$this->getSimpleXML()->БазоваяЕдиница;

        if (empty($full_title)) { //Если полного наименования не указано.
            $full_title = $short_title;
        }

        // Получаем идентификатор единицы изменерия по коду
        if (!empty($code)) {
            $unit_id = self::getUnitIdByCode($code);
        } elseif (!empty($short_title)) { // Получаем идентификатор единицы изменерия по полному наименованию
            $unit_id = self::getUnitIdByName($short_title);
        } else {
            // Если единицы измерения еще нет - вставляем
            $unit = new Unit();
            $unit['code'] = $code;
            $unit['icode'] = $inter_sokr;
            $unit['title'] = $full_title;
            $unit['stitle'] = $short_title;
            $unit->insert();
            $unit_id = $unit['id'];
        }
        $product['unit'] = $unit_id;
        return true;
    }

    /**
     * Получить XML_ID
     * @return string
     */
    private function getProductXMLId()
    {
        $xml_id = (string)$this->getSimpleXML()->Ид;
        return $xml_id;
    }

    /**
     * Получить id бренда по xml_id. Результат кешируется
     * Если бренд отсутствует - он будет создан
     *
     * @param string $brand_xml_id - внешний идентификатор бренда
     * @param string $brand_title - название бренда
     * @return int
     */
    static private function getBrandIdByXmlId($brand_xml_id, $brand_title)
    {
        if (!array_key_exists($brand_xml_id, self::$_cache_brands)) {
            $brand = Brand::loadByWhere(array(
                'site_id' => SiteManager::getSiteId(),
                'xml_id' => $brand_xml_id,
            ));
            if (!$brand['id']) {
                $brand['site_id'] = SiteManager::getSiteId();
                $brand['public'] = 1;
                $brand['xml_id'] = $brand_xml_id;
                $brand['title'] = $brand_title;
                $brand['alias'] = Transliteration::str2url($brand['title']);

                $same_aliases = OrmRequest::make()
                    ->select('alias')
                    ->from(new Brand())
                    ->where('alias like "#brand_alias%"', array('brand_alias' => $brand['alias']))
                    ->exec()->fetchSelected('alias', 'alias');
                if (in_array($brand['alias'], $same_aliases)) {
                    $counter = 2;
                    while (in_array($brand['alias'] . $counter, $same_aliases)) {
                        $counter++;
                    }
                    $brand['alias'] .= $counter;
                }

                $brand->insert();
            }
            self::$_cache_brands[$brand_xml_id] = $brand['id'];
        }
        return self::$_cache_brands[$brand_xml_id];
    }

    /**
     * Получить id категории по xml_id. Результат кешируется
     *
     * @param String $category_xml_id
     * @return int
     */
    static private function getCategoryIdByXmlId($category_xml_id)
    {
        if (!array_key_exists($category_xml_id, self::$_cache_categories)) {
            $dir = Dir::loadByWhere(array(
                'site_id' => SiteManager::getSiteId(),
                'xml_id' => $category_xml_id,
            ));
            if (!$dir['id']) {
                Log::w(t("Не найдена категория ") . $category_xml_id);
                return false;
            }
            self::$_cache_categories[$category_xml_id] = $dir['id'];
        }
        return self::$_cache_categories[$category_xml_id];
    }

    /**
     * Получить id свойства по xml_id. Результат кешируется
     *
     * @param String $property_xml_id
     * @return int
     * @throws \Exception
     */
    static private function getPropertyIdByXmlId($property_xml_id)
    {
        if (!array_key_exists($property_xml_id, self::$_cache_properties)) {
            $prop = PropertyItem::loadByWhere(array(
                'site_id' => SiteManager::getSiteId(),
                'xml_id' => $property_xml_id,
            ));
            if (!$prop['id']) {
                throw new \Exception(t("Не найдено свойство ") . $property_xml_id);
            }
            self::$_cache_properties[$property_xml_id] = $prop['id'];
            self::$_cache_properties_types[$prop['id']] = $prop['type'];
        }
        return self::$_cache_properties[$property_xml_id];
    }

    static private function getPropertyTypeById($property_id)
    {

        if (!isset(self::$_cache_properties_types[$property_id])) {
            $prop = new PropertyItem($property_id);
            if (!$prop['id']) {
                throw new \Exception(t("Не найдено свойство по ID") . $property_id);
            }
            self::$_cache_properties_types[$property_id] = $prop['type'];
        }
        return self::$_cache_properties_types[$property_id];
    }

    static private function getTaxIdByAlias($tax_alias)
    {
        if (!array_key_exists($tax_alias, self::$_cache_taxes)) {
            $tax = Tax::loadByWhere(array(
                'site_id' => SiteManager::getSiteId(),
                'alias' => $tax_alias,
            ));
            if (!$tax['id']) {
                return false;
            }
            self::$_cache_taxes[$tax_alias] = $tax['id'];
        }
        return self::$_cache_taxes[$tax_alias];
    }

    /**
     * Получает единицу измерения по её коду
     *
     * @param string $unit_name - наименование единицы измерения
     * @return int
     */
    static function getUnitIdByName($unit_name)
    {
        $unit_name = (string)$unit_name;
        if (!array_key_exists($unit_name, self::$_cache_units_by_name)) {
            $unit = Unit::loadByWhere(array(
                'site_id' => SiteManager::getSiteId(),
                'stitle' => $unit_name
            ));
            if (!$unit['id']) {
                return false;
            }
            self::$_cache_units_by_name[$unit_name] = $unit['id'];
        }
        return self::$_cache_units_by_name[$unit_name];
    }

    /**
     * Получает единицу измерения по её коду
     *
     * @param string $unit_code - код единицы измерения
     * @return int
     */
    static function getUnitIdByCode($unit_code)
    {
        $unit_code = (string)$unit_code;
        if (!array_key_exists($unit_code, self::$_cache_units_by_code)) {
            $unit = Unit::loadByWhere(array(
                'site_id' => SiteManager::getSiteId(),
                'code' => $unit_code
            ));
            if (!$unit['id']) {
                return false;
            }
            self::$_cache_units_by_code[$unit_code] = $unit['id'];
        }
        return self::$_cache_units_by_code[$unit_code];
    }
}
