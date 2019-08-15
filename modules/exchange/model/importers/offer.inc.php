<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Importers;

use Catalog\Model\Orm\Currency;
use Catalog\Model\Orm\Offer as ProductOffer;
use Catalog\Model\Orm\Product;
use Catalog\Model\Orm\Typecost;
use Catalog\Model\Orm\Unit;
use Catalog\Model\Orm\WareHouse as OrmWareHouse;
use Catalog\Model\WareHouseApi;
use Exchange\Model\Log;
use Exchange\Model\Importers\CatalogProduct as ImporterCatalogProduct;
use Exchange\Model\Importers\Warehouse as ImporterWarehouse;
use RS\Helper\Tools as Tools;
use RS\Orm\Request as OrmRequest;
use RS\Site\Manager as SiteManager;

/**
 * Импорт предложения из пакета предложений
 */
class Offer extends AbstractImporter
{
    static public $pattern = '/Предложения\/Предложение$/i';
    static public $title = 'Импорт товарных предложений';
    static public $product_barcode_by_xml_id = array();

    static private $_cache_import_hash = null; //Кэш по хэшам импорта

    public function init()
    {
        if (self::$_cache_import_hash == null) {
            self::$_cache_import_hash = OrmRequest::make()
                ->select('xml_id, import_hash')
                ->from(new ProductOffer())
                ->where('xml_id > "" and import_hash > ""')
                ->where(array('site_id' => SiteManager::getSiteId()))
                ->exec()->fetchSelected('xml_id', 'import_hash');
        }
    }

    public function import(\XMLReader $reader)
    {
        $import_hash = md5($this->getSimpleXML()->asXML());
        $xml_id = (string)$this->getSimpleXML()->Ид;

        if (isset(self::$_cache_import_hash[$xml_id]) && self::$_cache_import_hash[$xml_id] == $import_hash) {
            Log::w(t("Нет изменений в предложении: ") . (string)$this->getSimpleXML()->Наименование);
            OrmRequest::make()
                ->update(new ProductOffer())
                ->set(array('processed' => 1))
                ->where(array(
                    'xml_id' => $xml_id,
                    'site_id' => SiteManager::getSiteId(),
                ))
                ->exec();
        } else {
            $config = $this->getConfig();
            $catalog_config = $this->getCatalogConfig();
            Log::w(t("Импорт предложения: ") . (string)$this->getSimpleXML()->Наименование);

            $default_warehouse = WareHouseApi::getDefaultWareHouse();

            // Обрезанный xml_id (то, что до символа #)
            $product_xml_id = $this->getProductXMLId();
            $product = Product::loadByWhere(
                array(
                    'xml_id' => $product_xml_id,
                    'site_id' => SiteManager::getSiteId(),
                )
            );

            // Возможно это старая версия и XML_ID предложения совпадает с XML_ID товара
            if (!$product['id']) {
                $product = Product::loadByWhere(
                    array(
                        'xml_id' => (string)$this->getSimpleXML()->Ид,
                        'site_id' => SiteManager::getSiteId(),
                    )
                );
            }

            if (!$product['id']) {
                Log::w(t("Не удалось загрузить товар '") . $product_xml_id);
                return;
            }

            $barcode = Tools::toEntityString($this->getSimpleXML()->Артикул);
            $title = Tools::toEntityString($this->getSimpleXML()->Наименование);
            // Если включена настройка "Идентифицировать товары по артикулу" - обновим xml_id комплектации
            if ($config['product_uniq_field'] != 'xml_id') {
                $q = OrmRequest::make()
                    ->update(new ProductOffer())
                    ->set(array('xml_id' => $xml_id))
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

            // Добавляем запись в таблицу product_offer (Ценовое предложение)
            $product_offer = new ProductOffer();

            $product_offer['site_id'] = SiteManager::getSiteId();
            $product_offer['product_id'] = $product['id'];
            $product_offer['title'] = $title;
            $product_offer['barcode'] = $barcode;
            $product_offer['num'] = (string)$this->getSimpleXML()->Количество;
            $product_offer['xml_id'] = (string)$this->getSimpleXML()->Ид; //Уникальный идентификатор в 1С
            $product_offer['sku'] = (string)$this->getSimpleXML()->Штрихкод;
            $product_offer['processed'] = 1; //Флаг обработанной комплектации
            $product_offer['import_hash'] = $import_hash;

            //Добавим базовую единицу если включена опция использовать единицы измерения комплектаций
            if ($catalog_config['use_offer_unit']) {
                $this->getBaseUnit($product_offer);
            }

            //Если установлен флаг уникализировать артикулы у комплектаций. Сложим артикул товара и уникальный "хвост"
            if ($config['unique_offer_barcode']) {
                $uniq_tail = strtoupper(mb_substr(md5($product_offer['xml_id']), 0, 6));
                $product_offer['barcode'] = $this->getProductBarcodeByXMLId() . "-" . $uniq_tail;
            }

            // Записываем сериализованные Цены в pricedata
            $pricedata = array();
            if (isset($this->getSimpleXML()->Цены->Цена)) {
                if ($config['dont_delete_costs']) {
                    $old_pricedata = OrmRequest::make()
                        ->select('pricedata')
                        ->from(new ProductOffer())
                        ->where(array('xml_id' => $product_offer['xml_id']))
                        ->exec()->getOneField('pricedata');

                    if ($old_pricedata) {
                        $pricedata = @unserialize($old_pricedata);
                    }
                }

                foreach ($this->getSimpleXML()->Цены->Цена as $one) {
                    $typecost = Typecost::loadByWhere(array(
                        'site_id' => SiteManager::getSiteId(),
                        'xml_id' => $one->ИдТипаЦены,
                    ));
                    $currency = Currency::loadByWhere(array(
                        'site_id' => SiteManager::getSiteId(),
                        'title' => (string)$one->Валюта,
                    ));
                    $pricedata[$typecost['id']] = array(
                        'znak' => '=',
                        'original_value' => (string)$one->ЦенаЗаЕдиницу,
                        'unit' => $currency['id'],
                    );
                }
            }

            $product_offer['pricedata_arr'] = array('price' => $pricedata);

            // Записываем сериализованные Характеристики в propsdata
            $props_nodes = $this->getSimpleXML()->ХарактеристикиТовара->ХарактеристикаТовара;
            if ($props_nodes != null) {
                $props = array();
                foreach ($props_nodes as $one) {
                    $props[Tools::toEntityString((string)$one->Наименование)] = Tools::toEntityString((string)$one->Значение);
                }
                $product_offer['propsdata'] = serialize($props);
            }

            // Если схема больше 2.07 и Характеристики хранятся в файле import.xml и отсуствуют в offers.xml
            if (($props_nodes == null) && isset($_SESSION[ImporterCatalogProduct::SESS_KEY_NO_OFFER_PARAMS_FLAG]) && $_SESSION[ImporterCatalogProduct::SESS_KEY_NO_OFFER_PARAMS_FLAG]) {
                // Флаг, что характеристики не используются и нужно нумеровать все характеристики от 0
                $product_offer->cml_207_no_offer_params = true;
            }

            if ($config['dont_delete_stocks']) {
                $real_offer = ProductOffer::loadByWhere(array('xml_id' => $product_offer['xml_id']));
                $stock_num = $real_offer->fillStockNum();
            } else {
                $stock_num = array();
            }
            // Записываем сведения о количества товара на складе, если сведения об этом присутствуют
            // Для версии 2.07
            $stock_node = $this->getSimpleXML()->Склад;
            if (!count($stock_node)) {
                //Для версии 2.05
                $stock_node = $this->getSimpleXML()->КоличествоНаСкладах->КоличествоНаСкладе;
            } else {
                $product_offer->cml_207_no_offer_params = true;
            }

            if (count($stock_node)) {
                Log::w(t("Импорт остатков по складам для торгового предложения: ") . (string)$this->getSimpleXML()->Наименование);
                foreach ($stock_node as $one) {
                    $warehouse_xml_id = (string)($one->ИдСклада ?: $one['ИдСклада']);
                    $warehouse_stock = (float)preg_replace("/[^\d\.,^-]/", '', ($one->Количество ?: $one['КоличествоНаСкладе']));
                    $warehouse_id = $this->getWarehouseByXMLId($warehouse_xml_id);
                    $stock_num[$warehouse_id] = $warehouse_stock;
                }
            } else {
                $stock_num[$default_warehouse['id']] = (float)$product_offer['num'];
            }
            $product_offer['stock_num'] = $stock_num;

            $summary_num = 0;
            foreach ($stock_num as $warehouse_stock) {
                $summary_num += $warehouse_stock;
            }
            $product_offer['num'] = $summary_num;

            //Поля которые, будут обновлены при совпадении строки
            $on_duplicate_update_fields = array_diff(array('title', 'barcode', 'pricedata', 'propsdata', 'num', 'processed', 'import_hash', 'sku'), (array)$config['dont_update_offer_fields']);

            // Вставка _ИЛИ_ обновление товарного предложения (комплектации)
            //  $product['num'] += $product_offer->num;
            $product_offer->dont_reset_hash = true;
            $product_offer->insert(false, $on_duplicate_update_fields, array('site_id', 'xml_id'));
            // Получим настоящий sortn
            $product_offer['sortn'] = OrmRequest::make()
                ->select('sortn')
                ->from(new ProductOffer())
                ->where("id = {$product_offer['id']}")
                ->exec()->getOneField('sortn');

            // Если это основная комплектация - обновим цены продукта
            if ($product_offer['sortn'] == 0) {
                Log::w(t("Импортируем цены в таблицу product_x_cost для товара [id={$product['id']}]"));

                // Импортируем цены в таблицу product_x_cost
                if ($config['dont_delete_costs']) {
                    $product->fillCost();
                    $excost_array = $product['excost'];
                } else {
                    $excost_array = array();
                }

                if (isset($this->getSimpleXML()->Цены->Цена)) {
                    foreach ($this->getSimpleXML()->Цены->Цена as $one) {
                        $typecost = Typecost::loadByWhere(
                            array(
                                'xml_id' => $one->ИдТипаЦены,
                                'site_id' => SiteManager::getSiteId(),
                            )
                        );

                        $currency = Currency::loadByWhere(
                            array(
                                'site_id' => SiteManager::getSiteId(),
                                'title' => (string)$one->Валюта,
                            )
                        );

                        $excost_array[$typecost['id']] = array(
                            'cost_original_val' => (string)$one->ЦенаЗаЕдиницу,
                            'cost_original_currency' => $currency['id'],
                        );
                    }
                }

                $product['excost'] = $excost_array;
                if (!empty($product_offer['barcode'])) {
                    $product['barcode'] = $product_offer['barcode'];
                }
                if (!empty($product_offer['sku'])) {
                    $product['sku'] = $product_offer['sku'];
                }
                $product->setFlag(Product::FLAG_DONT_UPDATE_DIR_COUNTER);
                $product->is_exchange_action = true;
                $product->update();
            }
        }
    }

    /**
     * Импорт единиц измерения
     *
     * @param ProductOffer $offer - комплектация
     */
    private function getBaseUnit(ProductOffer $offer)
    {
        if (!(string)$this->getSimpleXML()->БазоваяЕдиница) {   // Если единицы нет, ничего не делаем
            return;
        }
        $code = $this->getSimpleXML()->БазоваяЕдиница['Код'];
        $inter_sokr = $this->getSimpleXML()->БазоваяЕдиница['МеждународноеСокращение'];
        $full_title = $this->getSimpleXML()->БазоваяЕдиница['НаименованиеПолное'];
        $short_title = (string)$this->getSimpleXML()->БазоваяЕдиница;

        if (empty($full_title)) { //Если полного наименования не указано.
            $full_title = $short_title;
        }

        // Получаем идентификатор единицы изменерия по коду
        if (!empty($code)) {
            $unit_id = ImporterCatalogProduct::getUnitIdByCode($code);
        } elseif (!empty($short_title)) { // Получаем идентификатор единицы изменерия по полному наименованию
            $unit_id = ImporterCatalogProduct::getUnitIdByName($short_title);
        } else {
            $unit_id = false;
        }

        if ($unit_id === false) {
            // Если единицы измерения еще нет - вставляем
            $unit = new Unit();
            $unit['code'] = $code;
            $unit['icode'] = $inter_sokr;
            $unit['title'] = $full_title;
            $unit['stitle'] = $short_title;
            $unit->insert();
            $unit_id = $unit['id'];
        }
        $offer['unit'] = $unit_id;
    }

    /**
     * Возвращает артикул товара к которой привязана комплектация
     *
     * @param string|bool $xml_id - внешний идентификатор
     * @return string
     */
    private function getProductBarcodeByXMLId($xml_id = false)
    {
        if (!$xml_id) {
            $xml_id = $this->getProductXMLId();
        }
        if (!isset(self::$product_barcode_by_xml_id[$xml_id])) {
            self::$product_barcode_by_xml_id[$xml_id] = OrmRequest::make()
                ->select('barcode')
                ->from(new Product())
                ->where(array(
                    'site_id' => SiteManager::getSiteId(),
                    'xml_id' => $xml_id
                ))->exec()
                ->getOneField('barcode', '');
        }
        return self::$product_barcode_by_xml_id[$xml_id];
    }

    /**
     * Получает XML_ID товара из XML в файле
     */
    private function getProductXMLId()
    {
        // Получаем XML-идентификатор товара (первую часть до решетки)
        $xml_id = (string)$this->getSimpleXML()->Ид;
        $xml_id_arr = explode("#", $xml_id);
        return $xml_id_arr[0];
    }

    /**
     * Получает ID склада из базы и помещает значение в сессию
     *
     * @param string $xml_id - XML_ID склада
     * @return int
     */
    private function getWarehouseByXMLId($xml_id)
    {
        if (!isset($_SESSION[ImporterWarehouse::SESS_KEY_WAREHOUSE_IDS][$xml_id])) {
            $id = OrmRequest::make()
                ->from(new OrmWareHouse())
                ->where(array(
                    'site_id' => SiteManager::getSiteId(),
                    'xml_id' => $xml_id,
                ))
                ->exec()
                ->getOneField('id', 0);

            $_SESSION[ImporterWarehouse::SESS_KEY_WAREHOUSE_IDS][$xml_id] = $id;
        }
        return $_SESSION[ImporterWarehouse::SESS_KEY_WAREHOUSE_IDS][$xml_id];
    }
}
