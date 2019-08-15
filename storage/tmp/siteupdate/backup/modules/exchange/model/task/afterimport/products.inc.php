<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Task\AfterImport;

use Catalog\Model\Api as CatalogApi;
use Catalog\Model\CostApi;
use Catalog\Model\Dirapi as CatalogDirapi;
use Catalog\Model\Orm\Brand;
use Catalog\Model\Orm\Offer;
use Catalog\Model\Orm\Product;
use Catalog\Model\Orm\Property\Item as PropertyItem;
use Catalog\Model\Orm\Property\Link as PropertyLink;
use Catalog\Model\Orm\Typecost;
use Catalog\Model\Orm\Xstock;
use Exchange\Config\File as ExchangeConfig;
use Exchange\Model\Exception;
use Exchange\Model\Importers;
use Exchange\Model\Log;
use Exchange\Model\Task\AbstractTask;
use RS\Config\Loader as ConfigLoader;
use RS\Db\Adapter;
use RS\Event\Manager as EventManager;
use RS\Helper\Transliteration;
use RS\Orm\Request as OrmRequest;
use RS\Site\Manager as SiteManager;

/**
 * Объект этого класса хранится в сессии, соотвественно все свойства объекта доступны
 * не только до окончания выполнения скрипта, но и в течении всей сессии
 */

class Products extends AbstractTask
{
    const DELETE_LIMIT = 100; // По сколько удалять продуктов за один раз

    protected $productPartNum = 1000; //Количество товаров для обработки вконце импорта
    protected $filename;
    protected $config;

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    public function exec($max_exec_time = 0)
    {
        $this->config = ConfigLoader::byModule($this);
        // выполняем действия после завершения импорта
        if(preg_match('/offers/iu',$this->filename)) {
            Log::w(t("Обновляем типы свойств и общий остаток товаров после загрузки offers.xml"));
            // Обновим типы свойств, если есть условие и строковые множественные свойства 
            $this->updatePropTypes();
            // Обновим количество товаров у которых есть в наличии комплектации
            $this->updateNumByOffers();
            // Обновление всех брендов из характеристик, если задан параметр "Производитель" в модуле обмена с 1С
            if ($this->config['brand_property']){
                $this->updateAllBrands($this->config['brand_property']);
                $this->updateBrandsInProducts($this->config['brand_property']);
            }
            if (!$this->config['dont_delete_prop']){ //Удалять ли характеристику созданную на сайте
                $this->deletePropsCreatedOnSite();
            }
            //Удалим комплектации, которые есть у товара, но в 1С их уже нет.
            $this->deleteOffersNotUsedInProducts();

            //Для некоторых версий CommerceML обновим сортировочный индекс комплектаций
            $this->setOffersRightSortnIfNeed();

            if($this->config['sort_offers_by_title']){
                $this->setOffersSortnByTitle();
            }

            //Обновляем счетчики у категорий
            CatalogDirapi::updateCounts();
        }

        //Вызовем хук
        EventManager::fire('exchange.task.afterimport.products', array(
            'filename' => $this->filename
        ));

        // Только для import.xml
        if(!preg_match('/import/iu', $this->filename)){
            Log::w(t("Ничего не делаем, так как это не import.xml"));
            return true;
        }

        // Если классификатор содержит только изменения, то ничего не делаем
        if(Importers\Catalog::containsOnlyChanges()){
            Log::w(t("Ничего не делаем, так как классификатор содержит только изменения"));
            return true;
        }

        // Если установлена настройка "Что делать с элементами, отсутствующими в файле импорта -> Ничего не делать"
        if($this->config->catalog_element_action == ExchangeConfig::ACTION_NOTHING){
            Log::w(t("Ничего не делаем, так как установлена настройка Ничего не делать c элементами, отсутсвующими в файле импорта"));
            return true;
        }

        // Если установлена настройка "Что делать с элементами, отсутствующими в файле импорта -> Обнулять остаток"
        if($this->config->catalog_element_action == ExchangeConfig::ACTION_CLEAR_STOCKS){
            Log::w(t("Обнуляем остаток товаров, которые не учавствуют в файле импорта..."));
            // Получаем id товаров, не участвовавших в импорте
            $ids = OrmRequest::make()
                ->select('id')
                ->from(new Product())
                ->where(array(
                    'site_id'   => SiteManager::getSiteId(),
                    'processed' => null,
                ))
                ->where('xml_id > ""')
                ->exec()
                ->fetchSelected(null, 'id');

            // Если есть товары, не участвовавшие в импорте - удалим линки остатков, кэш остатков у комплектаций и товаров
            if (!empty($ids)) {
                OrmRequest::make()
                    ->delete()
                    ->from(new Xstock())
                    ->whereIn('product_id', $ids)
                    ->exec();
                OrmRequest::make()
                    ->update(new Offer())
                    ->set(array('num' => 0))
                    ->whereIn('product_id', $ids)
                    ->exec();
                OrmRequest::make()
                    ->update(new Product())
                    ->set(array('num' => 0))
                    ->whereIn('id', $ids)
                    ->exec();
            }

            return true;
        }

        // Если установлена настройка "Что делать с элементами, отсутствующими в файле импорта -> Удалять"
        if($this->config->catalog_element_action == ExchangeConfig::ACTION_REMOVE)
        {
            Log::w(t("Удаление товаров, которые не учавствуют в файле импорта..."));
            $apiCatalog = new CatalogApi();

            while(true)
            {
                $ids = OrmRequest::make()
                    ->select('id')
                    ->from(new Product())
                    ->where(array(
                        'site_id'   => SiteManager::getSiteId(),
                        'processed' => null,
                    ))
                    ->where('xml_id > ""')
                    ->limit(self::DELETE_LIMIT)
                    ->exec()
                    ->fetchSelected(null, 'id');

                // Если не осталось больше товаров для удаления
                if(empty($ids)){
                    Log::w(t("Нет больше товаров для удаления"));
                    return true;
                }

                // Если превышено время выполнения
                if($this->isExceed()){
                    Log::w(t("Превышено время выполнения"));
                    return false;
                }

                $apiCatalog->multiDelete($ids);

            }
        }

        // Если установлена настройка "Что делать с элементами, отсутствующими в файле импорта -> Деактивировать"
        if($this->config->catalog_element_action == ExchangeConfig::ACTION_DEACTIVATE)
        {
            Log::w(t("Деактивация товаров, которые не учавствуют в файле импорта..."));

            // Скрываем товары
            $affected = OrmRequest::make()
                ->update(new Product())
                ->set(array('public' => 0))
                ->where(array(
                    'site_id'   => SiteManager::getSiteId(),
                    'processed' => null,
                ))
                ->where('xml_id > ""')
                ->exec()->affectedRows();
            Log::w(t("Деактивировано товаров: ").$affected);
            return true;
        }

        throw new \Exception('Impossible 2!');
    }

    /**
     * Для некоторых версий CommerceML, где в комплектациях нет указания признака нулевой комплектации нужно обновить сортировочный индекс, который начинается с 1
     *
     */
    private function setOffersRightSortnIfNeed()
    {
        $site_id = SiteManager::getSiteId(); //текущий id сайта

        //Получим подзапрос
        $sub_query = OrmRequest::make()
            ->from(new Offer(), 'B')
            ->where(array(
                'B.sortn' => 0,
            ))
            ->where('B.product_id=A.product_id')->toSql();

        //Получим подзапрос с выборкой нужных элементов, где только товары без нулевой комплектации
        $product_ids = OrmRequest::make()
            ->select('A.product_id')
            ->from(new Offer(), 'A')
            ->where(array(
                'A.site_id' => $site_id,
            ))
            ->where('A.sortn>0 AND NOT EXISTS ('.$sub_query.')')
            ->groupby('product_id')
            ->exec()
            ->fetchSelected(null, 'product_id');
        Log::w(t("Найдено товаров ").count($product_ids).t(" у которых нет нулевых комлектаций"));



        //Если у нас есть уже набор товаров
        if (!empty($product_ids)){

            // Разделим на части обработку
            $offset = 0;
            $part_ids   = array_slice($product_ids, $offset, $this->productPartNum);//id изменённых товаров
            while(!empty($part_ids)) {

                //Обновим записи сортировки уменьших сортировочный индекс где это нужно
                OrmRequest::make()
                    ->update()
                    ->from(new Offer())
                    ->set('sortn=sortn-1')
                    ->whereIn('product_id', $part_ids)
                    ->exec();

                $offset += $this->productPartNum;
                $part_ids = array_slice($product_ids, $offset, $this->productPartNum);
            }

            $cost_api = new CostApi();
            $cost_api->setFilter(array(
                'type' => Typecost::TYPE_MANUAL
            ));
            $costs = $cost_api->getAssocList('id', 'id');

            foreach ($product_ids as $product_id) {
                $offer = Offer::loadByWhere(array(
                    'product_id' => $product_id,
                    'sortn' => 0,
                ));
                if ($offer['id']) {
                    $offer_price_data = unserialize($offer['pricedata']);

                    if (!empty($offer_price_data['oneprice']['use'])) {
                        foreach($costs as $cost_id) {
                            $offer_price_data['price'][$cost_id] = $offer_price_data['oneprice'];
                        }
                        unset($offer_price_data['oneprice']);
                    }
                    $product_excost = array();
                    if (!empty($offer_price_data['price'])) {
                        foreach($offer_price_data['price'] as $cost_id => $item) {
                            if($item['znak'] == '=') {
                                $product_excost[$cost_id]['cost_original_val'] = $item['original_value'];
                                $product_excost[$cost_id]['cost_original_currency'] = $item['unit'];
                            }
                        }
                    }

                    $product = new Product($product_id);
                    $product['excost'] = $product_excost;
                    $product->update();
                }
            }
            Log::w(t("Обновление товаров у которых нет нулевых комлектаций"));
        }
    }

    /**
     * Сортировка комплектаций по наименованию (Natural Sort)
     *
     * @return void
     */
    private function setOffersSortnByTitle(){
        // Получаем id продуктов, у которых были изменения в комплектациях
        $offer = new Offer();

        $products_id = OrmRequest::make()
            ->select('product_id')
            ->from($offer, 'O')
            ->where(array(
                'O.site_id' => SiteManager::getSiteId(),
                'O.processed' => 1
            ))
            ->exec()
            ->fetchSelected('product_id', 'product_id');

        // Сортируем комлектации у полученых продуктов
        foreach($products_id as $id){

            // Выбираем все комплектации продукта
            $sort_arr = OrmRequest::make()
                ->select('id, title')
                ->from($offer, 'O')
                ->where(array(
                    'O.product_id' => $id
                ))
                ->exec()
                ->fetchSelected('id', 'title');

            natsort($sort_arr);

            $i = 0;
            $val_str = array();
            foreach($sort_arr as $key => $title){
                $val_str[] = '('. $key . ',' . $i++ .')';
            }
            // Обновляем порядковые номера у комплектаций
            $query = 'INSERT INTO ' . $offer->_getTable() . ' (id, sortn) VALUES ' . implode(',', $val_str) . ' ON DUPLICATE KEY UPDATE sortn=VALUES(sortn);';
            Adapter::sqlExec($query);
        }
    }

    /**
     * Обновление всех брендов на основании значений характеристики
     * Функция сравнивает новые значения со старыми и если есть несовпадения, то удаляет их.
     *
     * @param integer $brand_prop_id - id характеристики "Производителя"
     */
    private function updateAllBrands($brand_prop_id)
    {
        //Получим текущие бренды
        $brands = OrmRequest::make()
            ->select('title')
            ->from(new Brand())
            ->where(array(
                'site_id' => SiteManager::getSiteId(),
            ))->exec()
            ->fetchSelected(null,'title',false);


        //Получим доступные значения характеристики производителя
        $props = OrmRequest::make()
            ->select('DISTINCT (val_str) as val_str')
            ->from(new PropertyLink())
            ->where(array(
                'site_id' => SiteManager::getSiteId(),
                'prop_id' => $brand_prop_id,
            ))
            ->where("val_str <> ''")
            ->exec()
            ->fetchSelected(null,'val_str',false);

        $new_brands = array_diff($props,$brands);

        //Если есть различия, и появились новые бренды
        if (!empty($new_brands)){
            foreach($new_brands as $brand_title){
                $brand = new Brand();
                $brand['title']  = $brand_title;
                $brand['alias']  = Transliteration::rus2translit($brand_title);
                $brand['public'] = 1;
                $brand->insert();
            }
        }

        Log::w(t("Обновление сведений о брендах"));
    }

    /**
     * Обновляет id брендов у товаров
     *
     * @param integer $brand_prop_id - id характеристики "Производителя"
     * @return void
     * @throws \Exchange\Model\Exception
     * @throws \Exception
     */
    private function updateBrandsInProducts($brand_prop_id)
    {
        $prop  = new PropertyLink();
        $brand = new Brand();

        try {
            $brand_query = '
                (SELECT B.id FROM '.$prop->_getTable().' AS L
                INNER JOIN '.$brand->_getTable().' AS B ON B.title = L.val_str
                WHERE prop_id='.$brand_prop_id.' AND B.site_id='.SiteManager::getSiteId().' AND L.val_str != "" AND L.product_id = P.id LIMIT 1)
            ';

            OrmRequest::make()
                ->update()
                ->from(new Product(),'P')
                ->set('brand_id = '.$brand_query)
                ->where(array(
                    'P.site_id' => SiteManager::getSiteId(),
                ))->exec();
        } catch (\Exception $e) {
            if ($e->getCode() == 1242) {
                throw new Exception(t('Ошибка при обновлении брендов у товара! Убедитесь, что характеристика бренда имеет строковый тип.'), $e->getCode());
            } else {
                throw $e;
            }
        }
        Log::w(t("Обновление брендов у товаров"));
    }

    /**
     * Находит комплектации товара, которые отсуствуют в 1С, а на сайте ещё присутствуют и удаляет их
     *
     */
    private function deleteOffersNotUsedInProducts()
    {
        if ($this->config['catalog_offer_action'] == ExchangeConfig::ACTION_REMOVE) {
            Log::w(t("Удаление комплектаций, которые отсутствуют в 1С, но есть на сайте"));
            $offers_ids = (array) OrmRequest::make()
                ->select('O.id')
                ->from(new Product(),'P')
                ->join(new Offer(),"O.product_id=P.id","O")
                ->where(array(
                    'P.site_id'   => SiteManager::getSiteId(),
                    'P.processed' => 1,
                ))
                ->where('O.processed IS NULL')
                ->exec()
                ->fetchSelected(null,'id');

            if (!empty($offers_ids)){
                OrmRequest::make()
                    ->from(new Offer())
                    ->whereIn('id', $offers_ids)
                    ->delete()
                    ->exec();
            }
        } else {
            Log::w(t("Ничего не делаем с комплектациями, которые отсутствуют в 1С, но есть на сайте"));
        }
    }

    /**
     * Удаляет характеристики созданные на сайте
     *
     */
    private function deletePropsCreatedOnSite()
    {
        Log::w(t("Удаление характеристик созданных на сайте"));
        OrmRequest::make()
            ->from(new PropertyItem())
            ->where(array(
                'site_id' => SiteManager::getSiteId()
            ))
            ->where('xml_id IS NULL')
            ->delete()
            ->exec();
    }

    /**
     * Обновит количество товаров у которых есть в наличии комплектации комплектации.
     * Товары будут братся из сессии
     *
     * @return void
     */
    private function updateNumByOffers()
    {
        if (!isset($_SESSION[Importers\CatalogProduct::SESS_KEY_GOODS_IDS])){ //Если товаров для обновления не существует
            return;
        }
        Log::w(t("Количество товаров обновления - ").count($_SESSION[Importers\CatalogProduct::SESS_KEY_GOODS_IDS]));
        if (!empty($_SESSION[Importers\CatalogProduct::SESS_KEY_GOODS_IDS])) {
            // Разделим на части обработку
            $offset = 0;
            $part_ids   = array_slice($_SESSION[Importers\CatalogProduct::SESS_KEY_GOODS_IDS],$offset,$this->productPartNum);//id изменённых товаров
            $offer  = new Offer();
            while(!empty($part_ids)) {

                // Собственно обновим количество товара
                OrmRequest::make()
                    ->from(new Product(),"P")
                    ->set("
                        P.num = (SELECT sum(num) as num FROM ".$offer->_getTable()." as O
                                    WHERE O.product_id = P.id AND O.site_id=".SiteManager::getSiteId().")"
                    )
                    ->whereIn('id',$part_ids)
                    ->update()
                    ->exec();


                $offset += $this->productPartNum;
                $part_ids   = array_slice($_SESSION[Importers\CatalogProduct::SESS_KEY_GOODS_IDS],$offset,$this->productPartNum);
            }
            // Проверим, выключен ли импорт многомерных комплектаций из 1С
            if (!$this->config['allow_insert_multioffers']) {
                // Очистим сессию
                unset($_SESSION[Importers\CatalogProduct::SESS_KEY_GOODS_IDS]);
            }

        }
        Log::w(t("Обновление количества товаров комплектаций у товаров"));
    }

    /**
     * Обновит типы свойств, если есть условие и строковые множественные свойства
     *
     * @return void
     */
    private function updatePropTypes()
    {
        $separator = $this->config['multi_separator_fields']; //Получаем разделитель множественного свойства

        if (!empty($separator)){ //Если разделитель задан и есть свойства для обновления, то обновим
            $props_ids = Importers\CatalogProduct::getPropertiesToUpdate(); //Получаем свойства для обновления

            foreach($props_ids as $property_id) {
                $property = new PropertyItem($property_id);
                if (!in_array($property['type'], PropertyItem::getListTypes())) {
                    //Обновим в базе тип характеристики
                    OrmRequest::make()
                        ->update($property)
                        ->set(array(
                            'type' => PropertyItem::TYPE_LIST
                        ))->exec();
                }
            }
        }
    }
}
