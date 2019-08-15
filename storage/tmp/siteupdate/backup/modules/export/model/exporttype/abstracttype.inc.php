<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Export\Model\ExportType;

use Catalog\Model\CostApi;
use Export\Model\Orm\ExportProfile;
use Catalog\Model\Orm\Product;
use RS\Db\Exception as DbException;
use RS\Event\Exception as EventException;
use RS\Orm\AbstractObject;
use RS\Orm\Exception as OrmException;
use RS\Orm\Type;

/**
 * Абстрактный класс типа экспорта.
 */
abstract class AbstractType extends AbstractObject
{
    const CHARSET = 'utf-8';

    protected $offer_types = null;
    protected $offer_types_data = null;
    protected $cacheSelectedProductIds = null;

    public function _init()
    {
        return $this->getPropertyIterator()->append(array(
            t('Основные'),
                'export_cost_id' => new Type\Integer(array(
                    'description' => t('Выгружаемый тип цен'),
                    'list' => array(array('\Catalog\Model\CostApi', 'staticSelectList'), array(0 => t('Не выбрано'))),
                    'default' => CostApi::getDefaultCostId()
                )),
                'products' => new Type\ArrayList(array(
                    'description' => t('Список товаров'),
                    'template' => '%export%/form/profile/products.tpl'
                )),
                'only_available' => new Type\Integer(array(
                    'description' => t('Выгружать только товары, которые в наличии?'),
                    'checkboxView' => array(1, 0),
                )),
                'consider_warehouses' => new Type\ArrayList(array(
                    'description' => t('Какие склады учитывать при опреденлении наличия товара?'),
                    'list' => array(array('\Catalog\Model\WareHouseApi', 'staticSelectList'), array(0 => t('- Все -'))),
                    'attr' =>array(array(
                        'multiple' => true,
                        'size' => 10,
                    )),
                )),
                'min_cost' => new Type\Integer(array(
                    'description' => t('Выгружать товары с ценой из диапазона'),
                    'maxLength' => 11,
                    'template' => '%export%/form/profile/cost_range.tpl',
                    'hint' => t('Диапазон цен указывайте в базовой валюте')
                )),
                'max_cost' => new Type\Integer(array(
                    'description' => t('Максисальная цена'),
                    'maxLength' => 11,
                    'visible' => false
                )),
                'no_export_offers' => new Type\Integer(array(
                    'description' => t('Не выгружать комлектации товаров'),
                    'checkboxview' => array(1, 0),
                )),
                'export_photo_originals' => new Type\Integer(array(
                    'description' => t('Выгружать оригиналы фото (без водяного знака)'),
                    'checkboxview' => array(1, 0),
                )),
                t('Поля данных'),
                'offer_type' => new Type\Varchar(array(
                    'description' => t('Тип описания'),
                    'ListFromArray' => array($this->getOfferTypeNames()),
                )),
                'fieldmap' => new Type\MixedType(array(
                    'description' => t(''),
                    'visible' => true,
                    'template' => '%export%/form/profile/fieldmap.tpl'
                )),
        ));
    }  
    
    /**
    * Возвращает название расчетного модуля (типа экспорта)
    * 
    * @return string
    */
    abstract public function getTitle();
    
    /**
    * Возвращает описание типа экспорта. Возможен HTML
    * 
    * @return string
    */
    abstract public function getDescription();
    
    /**
    * Возвращает идентификатор данного типа экспорта. (только англ. буквы)
    * 
    * @return string
    */
    abstract public function getShortName();
    
    /**
    * Возвращает экспортированные данные (XML, CSV, JSON и т.п.)
    * 
    * @param ExportProfile $profile Профиль экспорта
    * @return string
    */
    abstract public function export(ExportProfile $profile);
    
    /**
    * Если для экспорта нужны какие-то специфические заголовки, то их нужно отправлять в этом методе
    */
    function sendHeaders()
    {
        header("Content-type: text/xml; charset=".static::CHARSET);
    }
    
    /**
    * Возвращает объект хранилища
    * 
    * @return \RS\Orm\Storage\AbstractStorage
    */
    protected function getStorageInstance()
    {
        return new \RS\Orm\Storage\Stub($this);
    }

    /**
     * Возвращает ссылку на файл экспорта
     *
     * @param ExportProfile $profile
     * @return string
     */
    public function getExportUrl(ExportProfile $profile)
    {
        $router = \RS\Router\Manager::obj();
        return $router->getUrl('export-front-gate', array(
            'site_id' => \RS\Site\Manager::getSiteId(),
            'export_id' => $profile['alias'] ? $profile['alias'] : $profile['id'],
            'export_type' => $profile['class'],
        ), true);
    }
    
    /**
    * Возвращает список классов типов описания
    *
    * @return \Export\Model\ExportType\AbstractOfferType[]
    */
    abstract protected function getOfferTypesClasses();
    
    /**
    * Возвращает массив доступных типов описания товарных предложений
    * 
    * @return array
    */
    protected function getOfferTypes()
    {
        if ($this->offer_types === null) {
            $export_type_name = $this->getShortName();
            $offer_types = $this->getOfferTypesClasses();
            foreach ($offer_types as $offer_type) {
                $offer_type->setExportTypeName($export_type_name);
                $this->offer_types[$offer_type->getShortName()] = $offer_type;
            }
        }
        return $this->offer_types;
    }
    
    /**
    * Возвращает массив доступных типов описания товарных предложений
    * @return array
    */
    public function getOfferTypeNames()
    {
        $result = array();
        foreach ($this->getOfferTypes() as $offer_type) {
            $result[$offer_type->getShortName()] = $offer_type->getTitle();
        }
        return $result;
    }
    
    /**
    * Возвращает массив данных по всем типам описания
    * @return array
    */
    public function getOfferTypesData()
    {
        if ($this->offer_types_data === null) {
            $this->offer_types_data = array();
            foreach ($this->getOfferTypes() as $offer_type) {
                $this->offer_types_data[$offer_type->getShortName()] = $offer_type->getEspecialTags();
            }
        }
        return $this->offer_types_data;
    }
    
    /**
    * Возвращает массив данных по всем типам описания в виде JSON
    * @return string
    */
    public function getOfferTypesJson()
    {
        return json_encode($this->getOfferTypesData());
    }
    
    /**
    * Возвращает массив соответсвия полей (fieldmap) в виде JSON
    * @return string
    */
    public function getFieldMapJson()
    {
        return json_encode($this['fieldmap']);
    }

    /**
     * Возвращает массив идентификаторов выбранных товаров
     *
     * @param ExportProfile $profile
     * @return array
     * @throws DbException
     */
    protected function getSelectedProductIds(ExportProfile $profile)
    {
        if($this->cacheSelectedProductIds != null){
            return $this->cacheSelectedProductIds;
        }
        
        $product_ids = isset($profile['data']['products']['product']) ? $profile['data']['products']['product'] : array();
        $group_ids   = isset($profile['data']['products']['group']) ? $profile['data']['products']['group'] : array();
        
        if (!$product_ids && !$group_ids) {
            //Если не выбрана ни одна группа и ни один товар, это означает, 
            //что экспортировать нужно все товары во всех группах
            $group_ids = array(0);
        }
        
        if(!empty($group_ids)){
            // Получаем все дочерние группы
            while(true){
                $subgroups_ids = \RS\Orm\Request::make()
                    ->select('id')
                    ->from(new \Catalog\Model\Orm\Dir())
                    ->whereIn('parent', $group_ids)
                    ->where('(is_virtual = 0 OR is_virtual IS NULL)')
                    ->exec()
                    ->fetchSelected(null, 'id');
                $old_count = count($group_ids);
                $group_ids = array_unique(array_merge($group_ids, $subgroups_ids));
                if($old_count == count($group_ids)) break;
            }
            // Получаем ID всех товаров в этих группах
            $ids = \RS\Orm\Request::make()
                ->select('X.product_id')
                ->from(new \Catalog\Model\Orm\Xdir(), 'X')
                ->join(new \Catalog\Model\Orm\Product(), 'P.id = X.product_id', 'P')
                ->whereIn('X.dir_id', $group_ids)
                ->where(array('P.no_export' => 0))
                ->exec()
                ->fetchSelected(null, 'product_id');
                
            // Прибавляем их к "товарам выбранными по одному"
            $product_ids = array_unique(array_merge($product_ids, $ids));
        }
        $this->cacheSelectedProductIds = $product_ids;
        return $this->cacheSelectedProductIds;
    }

    /**
     * Экспорт Товарных предложений
     *
     * @param ExportProfile $profile
     * @param \XMLWriter $writer
     * @throws OrmException
     * @throws DbException
     * @throws EventException
     */
    protected function exportOffers(ExportProfile $profile, \XMLWriter $writer)
    {
        $product_ids = $this->getSelectedProductIds($profile);
        $query = \RS\Orm\Request::make()
                    ->from(new Product, 'P')
                    ->where(array(
                        'public' => 1,
                        'site_id' => \RS\Site\Manager::getSiteId()
                    ));
        
        if ($profile->only_available) {
            $query->where('P.num > 0');
        }
        
        if(!empty($product_ids)){
            $query->whereIn('P.id', $product_ids);
        }
        
        // Добавляем ограничение по цене в базовой валюте, если оно указано
        if (!empty($profile['min_cost']) || !empty($profile['max_cost'])) {
            $query->join(new \Catalog\Model\Orm\Xcost, 'C.product_id = P.id', 'C');
            $query->where(array('C.cost_id' => CostApi::getDefaultCostId()));
            if (!empty($profile['min_cost'])) $query->where("C.cost_val >= #min_cost", array('min_cost' => $profile['min_cost']));
            if (!empty($profile['max_cost'])) $query->where("C.cost_val <= #max_cost", array('max_cost' => $profile['max_cost']));
        }

        $offset = 0;
        $pageSize = 100;
        $catalogApi = new \Catalog\Model\Api();
        
        while( $products = $query->limit($offset, $pageSize)->objects()) {

        if ($profile['consider_warehouses'] && !in_array(0, $profile['consider_warehouses'])) {
            $catalogApi->setWarehousesForDynamicNum($profile['consider_warehouses']);
        }
        $products = $catalogApi->addProductsCost($products);
        $products = $catalogApi->addProductsOffers($products);
        $products = $catalogApi->addProductsDirs($products);
        $products = $catalogApi->addProductsProperty($products);
        $products = $catalogApi->addProductsPhotos($products);
        $products = $catalogApi->addProductsMultiOffers($products);

            foreach ($products as $product) {

                $offers_count = count($product['offers']['items']);
                if($product['offers']['use'] && $offers_count > 1){
                    foreach(range(0, $offers_count - 1) as $offer_index){
                        $this->exportOneOffer($profile, $writer, $product, $offer_index);
                        if($profile->no_export_offers && (!$profile->only_available || $product->getNum($offer_index) > 0)){
                            break;
                        }
                    }
                }
                else{
                    $this->exportOneOffer($profile, $writer, $product, false);
                }
            }
            $offset += $pageSize;
        }
          
        $writer->flush();
    }

    /**
     * Экпорт одного товарного предложения
     *
     * @param ExportProfile $profile
     * @param \XMLWriter $writer
     * @param mixed $product
     * @param mixed $offer_index
     * @throws \Exception
     */
    protected function exportOneOffer(ExportProfile $profile, \XMLWriter $writer, Product $product, $offer_index)
    {
        if ($profile['only_available'] && $product->getNum($offer_index) <= 0) {
            return;
        }
        
        if($offer_index !== false && !count($product['offers'])){
            throw new \Exception(t('Товарные предложения отсутсвуют, но передан аргумент offer_index'));
        }
        
        $this->offer_types[$profile['data']['offer_type']]->writeOffer($profile, $writer, $product, $offer_index);
        $writer->flush();
    }


    /**
     * Событие, которое вызывается после записи всех твоаров
     *
     * @param string $event_name - уникальная часть итогового имени события
     * @param ExportProfile $profile - объект профиля экспорта
     * @param \XMLWriter $writer - объект библиотеки для записи XML
     * @return \RS\Event\Result
     * @throws EventException
     */
    protected function fireAfterAllOffersEvent($event_name, ExportProfile $profile, \XMLWriter $writer)
    {
        $event_name = "export.{$profile['class']}.$event_name";
        $export_params = array(
            'profile' => $profile,
            'writer' => $writer,
        );

        $event_result = \RS\Event\Manager::fire($event_name, $export_params);
        return $event_result;
    }
}
