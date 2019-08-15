<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Task\AfterImport;
use \Exchange\Model\Log;
use \RS\Helper\Tools as Tools;
use \Catalog\Model\Orm\Property\ItemValue;

/**
* Импортирует многомерные комплектации
* Объект этого класса хранится в сессии, соотвественно все свойства объекта доступны 
* не только до окончания выполнения скрипта, но и в течении всей сессии
*/

class MultiOffers extends \Exchange\Model\Task\AbstractTask
{
    
    protected
        $new_props_dir_name = 'Характеристики комплектаций', // Имя категории характеристик куда будут сохраняться новые характеристики добавленные
        $exclude_props_name = 'Выключить многомерные комплектации', // Имя характеристики выключающей использование многомерных комплектаций у товара, берётся из 1С, если таковое присутсвует
        $exclude_property   = null,    // Характеристика выключающая использование многомерных комплектаций у товара
        $new_props_dir_id   = null,    // id категории характеристик хранящих новые характеристики
        $prop_values        = array(), // Массив хэш проверки
        $prop_insert        = array(), // Массив характеристик со значениями для записи в базу
        $props              = array(), // Массив характеристик
        $part_products      = array(), // Массив с id товаров которые импортируются
        $offset             = 0,       // С какого элемента начинать
        $productPartNum     = 100,     // Количество товаров для обработки вконце импорта
        $site_id,     // id текущего сайта с которым работаем
        $filename;  
    
    
    public function __construct($filename)
    {
        $this->filename = $filename;
    }
    
    public function exec($max_exec_time = 0)
    {
        //Вызовем хук
        \RS\Event\Manager::fire('exchange.task.afterimport.multioffers', array(
            'filename' => $this->filename
        ));
        if (preg_match("/offers/", $this->filename)) {
            // Получим конфиг
            /**
            * @var \Exchange\Config\File
            */
            $config           = \RS\Config\Loader::byModule('exchange');
            $need_multioffers = $config['allow_insert_multioffers'];
            
            // Получим характеристику выключающую использование многомерных комплектаций
            $this->site_id = \RS\Site\Manager::getSiteId(); //Текущий id сайта
            $offMultiOffersProperty = $this->getOffMultiOffersProperty();

            // Если стоит галка использовать импорт многомерных комплектаций
            if ($need_multioffers && !empty($_SESSION[\Exchange\Model\Importers\CatalogProduct::SESS_KEY_GOODS_IDS])) { 
               Log::w(t("Начинаем обработку импорта многомерных комплектаций"));
               $product_api = new \Catalog\Model\Api();
               $part_ids = array_slice($_SESSION[\Exchange\Model\Importers\CatalogProduct::SESS_KEY_GOODS_IDS], $this->offset, $this->productPartNum);//id изменённых товаров
               
               while(!empty($part_ids)) {
                   
                   // Собственно обновим количество товара
                   $q = \RS\Orm\Request::make()
                        ->from(new \Catalog\Model\Orm\Product(),"P")
                        ->whereIn('id',$part_ids);
                        
                   // Если импортирована характеристика "Выключить многомерные комплектации"
                   if ($offMultiOffersProperty) { //Добавим сведения об этой характеристике
                       $q->leftjoin(new \Catalog\Model\Orm\Property\Link(),'P.id=L.product_id AND L.prop_id='.$offMultiOffersProperty['id'],'L')
                       ->select('P.*,L.val_str as off_multi_offers');
                   }
                   
                        
                   $products = $q->objects('\Catalog\Model\Orm\Product','id'); 
                   $products = $product_api->addProductsOffers($products); // Подгрузим всем товарам комплектации
                        
                   // Перебираем товары     
                   foreach ($products as $k=>$product) {
                      Log::w(t("Обрабатываем многомерные для товара - ").$product['id']); 
                      $this->processProductOffers($product);  // Начинаем обработку
                   }
                   
                   $this->insertProperties();
                   $this->offset += $this->productPartNum;
                   
                   // Добавим многомерные комплектации к товарам
                   $this->addMultiOffersToProducts($products);
                   
                   // Если превышено время выполнения
                   if ($this->isExceed()) {
                        Log::w(t("Превышено время выполнения импорта многомерных комплектаций 1"));
                        return false;
                   }
                   $this->prop_values = array(); //Массив хэш проверки обнулим
                   $this->prop_insert = array(); //Массив мульти вставки обнулим
                   $this->part_products = array();
                   $part_ids = array_slice($_SESSION[\Exchange\Model\Importers\CatalogProduct::SESS_KEY_GOODS_IDS],$this->offset,$this->productPartNum);
               }
               // Очистим сессию
               unset($_SESSION[\Exchange\Model\Importers\CatalogProduct::SESS_KEY_GOODS_IDS]); 
               
               if ($this->isExceed()) {
                    Log::w(t("Превышено время выполнения импорта многомерных комплектаций 2"));
                    return false;
               }
               
               // Обозначим доступные значения и добавим если они отсутсвуют
               Log::w(t("Импорт многомерных комплектаций успешно завершен"));
               return true; 
            }
            
            Log::w(t("Импорт многомерных комплектаций пропущен"));
            return true; 
        }
        
        return true;
   }
   
   /**
   * Добавляет к товарам многомерные комплектации
   * 
   * @param array $part_ids - массив товаров для которых будут добавляться многомерные комплектации
   */
   private function addMultiOffersToProducts($products)
   {
      $moffer_api = new \Catalog\Model\MultiOfferLevelApi();
      $levels     = null; // Уровни многомерных комплектаций
      
      if (!empty($this->props)) {
          foreach ($this->props as $prop_name => $property) { // Добавим известные нам уровни
             $levels[] = array(
                'title' => $property['title'],
                'prop'  => $property['id']
             );
          }
          
          if (!empty($products)) {
              foreach($products as $product) {
                 if (!$product['off_multi_offers']) {
                     //Подготовим уровни многомерных комплектаций для товара
                     $levels_by_product = $moffer_api->prepareRightMOLevelsToProduct($product['id'], $levels, true);
                     
                     //Сохранение уровней мн. комплектаций
                     Log::w(t("Добавляем многомерные комплектации к id товара ").$product['id']);
                     $moffer_api->saveMultiOfferLevels($product['id'], $levels_by_product);
                 } 
              }
          }
      }
      
   }


   /**
   * Получает характеристику "Выключить многомерные комплектации"
   * 
   */
   private function getOffMultiOffersProperty()
   {
      if ($this->exclude_property === null) { // Если ещё не запрашивалось
         $this->exclude_property = \RS\Orm\Request::make()
            ->from(new \Catalog\Model\Orm\Property\Item())
            ->where(array(
                'site_id' => $this->site_id,
                'title' => $this->exclude_props_name,
            ))
            ->object();
      } 
      
      return $this->exclude_property;
   }
   
   
   /**
   * Создаёт категорию для новых характеристик
   * 
   */
   private function createNewPropertyDir()
   {
      $dir = new \Catalog\Model\Orm\Property\Dir();
      $dir['title']  = $this->new_props_dir_name; 
      $dir->insert();
      return $dir;
   }
   
   /**
   * Получает характеристику по имени, если таковой нет, то создаёт её
   * 
   * @param string $prop_name - имя характеристики, которую ищем
   */
   private function getPropertyByNameOrCreate($prop_name)
   {
       
       if (!isset($this->props[$prop_name])) {
           // Проверим такую в БД
           $property = \RS\Orm\Request::make()
                ->from(new \Catalog\Model\Orm\Property\Item())
                ->where(array(
                    'site_id' => $this->site_id,
                    'title' => $prop_name,
                ))->object();  
           
                
           if (!$property) { // Если характеристики нет, то создадим её.
              // Проверим есть ли директрия
              if (!$this->new_props_dir_id) {
                  $prop_dir = \RS\Orm\Request::make()
                        ->from(new \Catalog\Model\Orm\Property\Dir())
                        ->where(array(
                            'site_id'=> $this->site_id,
                            'title'=> $this->new_props_dir_name,
                        ))
                        ->object();    
                  if (!$prop_dir) {
                      $prop_dir = $this->createNewPropertyDir();
                  }
                  $this->new_props_dir_id = $prop_dir['id']; 
              } 
              $property = new \Catalog\Model\Orm\Property\Item();
              
              $property['title']     = $prop_name;
              $property['type']      = 'list';
              $property['parent_id'] = $this->new_props_dir_id;
              $property->insert();
           } elseif (!$property->isListType()) {  //Если характеристика не списковая оказалась переводим в списковую
              $property['type'] = 'list';
              $property->update(); 
           }  
           $this->props[$prop_name] = $property;                     
       }
       
       return $this->props[$prop_name];
   }
   
   /**
   * Вставляет характеристики товара в БД взятые из комплектаций
   * 
   */
   private function insertProperties()
   {
      if (!empty($this->prop_insert)){
          $property_obj = new \Catalog\Model\Orm\Property\Link(); 
          foreach ($this->prop_insert as $prop_id => $product_values) {
              //Предварительно удалим характеристики
              $q = \RS\Orm\Request::make()
                    ->from(new \Catalog\Model\Orm\Property\Link())
                    ->where(array(
                        'site_id' => $this->site_id,
                        'prop_id' => $prop_id,
                    ))
                    ->whereIn('product_id', $this->part_products)
                    ->delete()
                    ->exec();
                    
              
              //Вставляем мутивставкой характеристики
              Log::w(t('Мульти вставка характеристики, id = ').$prop_id);
                 
              $q = \RS\Db\Adapter::sqlExec(
                'INSERT INTO #table (`product_id`,`prop_id`,`val_str`,`val_list_id`, `available`, `site_id`, `xml_id`) 
                    VALUES '.implode(",",$product_values).' ',
                    array(
                          'table' => $property_obj->_getTable(),                                                                                        
                    ));
          }
      } 
   }
   
   /**
   * Сортирует комплектации товара ставя в начало те комплектации, что есть в наличии
   * 
   * @param mixed $a
   * @param mixed $b
   */
   private function sortOffersByNumCallback($a, $b)
   {
       if ($a['num'] == $b['num']) {
            return 0;
       }
       return ($a['num'] > $b['num']) ? -1 : +1;
   }
   
   /**
   * Обрабатывает комплектацию товара вытаскивая значения для характеристик
   * 
   * @param \Catalog\Model\Orm\Product $product
   */
   private function processProductOffers($product)
   {
        $site_id = \RS\Site\Manager::getSiteId();
        if ($product->isOffersUse()) {
             $this->part_products[] = $product['id'];
             //Поставим вначало только те характеристики, что есть в наличии
             $offers = $product['offers'];
             usort($offers['items'], array($this, 'sortOffersByNumCallback'));
             $product['offers'] = $offers;
             
             // Перебираем уже добавленные комплектации 
             foreach ($product['offers']['items'] as $offer) {  
                 //Получиим данные характеристик
                 if (!empty($offer['propsdata_arr'])) {
                     foreach ($offer['propsdata_arr'] as $prop_name => $prop_value){
                         // Определим по имени что за характеристика
                         $property = $this->getPropertyByNameOrCreate($prop_name);
                         
                         
                         if (!empty($prop_value)) {      
                            // Готовим для мульти вставки 
                            $data = \RS\Helper\Tools::arrayQuote(array(
                                $product['id'],
                                $property['id'],
                                $prop_value,
                                ItemValue::getIdByValue($property['id'], $prop_value),
                                (int)($offer['num']>0), //available
                                $site_id,
                                $property['xml_id'],
                            ));
                            
                            
                            $ready_data_insert = "(".implode(',', $data).")"; // Данные для множественной вставки 
                            
                            if (!isset($this->prop_values[$property['id']][$product['id']][$prop_value])) {
                                $this->prop_values[$property['id']][$product['id']][$prop_value] = 1;  // Установим хэш проверки
                                $this->prop_insert[$property['id']][] = $ready_data_insert;
                            }        
                         }
                     } 
                 }
             }
        }
   }
   
   /**
   * Возвращает конфиг модуля
   * 
   */
   private function getConfig()
   {
       return \RS\Config\Loader::byModule($this);
   }
}