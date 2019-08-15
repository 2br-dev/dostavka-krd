<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Importers;
use \RS\Helper\Tools as Tools;

/**
* Импорт склада из файла с предложениями
*/

class Warehouse extends \Exchange\Model\Importers\AbstractImporter
{
    const SESS_KEY_WAREHOUSE_IDS = "warehouse_ids"; //Сессионный ключ для массива c складами
    
    static public $pattern  = '/Склад$/i';
    static public $title    = 'Импорт складов';

    
    public function import(\XMLReader $reader)
    {  
        \Exchange\Model\Log::w(t("Импорт склада: ").$this->getSimpleXML()->Наименование);
        
        // Получаем xml_id
        $xml_id = (string) $this->getSimpleXML()->Ид;
        
        $warehouse = new \Catalog\Model\Orm\WareHouse();
        $warehouse->title   = Tools::toEntityString($this->getSimpleXML()->Наименование); 
        $warehouse->alias   = str_replace(" ","-",\RS\Helper\Transliteration::rus2translit($warehouse->title)); 
        $warehouse->xml_id  = $xml_id;
        $warehouse->site_id = \RS\Site\Manager::getSiteId();
           
       
        // Вставка _ИЛИ_ обновление склада
        $warehouse->insert(false, array('xml_id'), array('site_id','xml_id'));
        
        //Запишем в сессию id склада по XML_ID
        if (!isset($_SESSION[self::SESS_KEY_WAREHOUSE_IDS][$warehouse['xml_id']])){
           $_SESSION[self::SESS_KEY_WAREHOUSE_IDS][$warehouse['xml_id']] = $warehouse['id']; 
        }
    }
    
}