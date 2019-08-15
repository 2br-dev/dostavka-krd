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
* Импорт типа цены
*/

class PriceType extends \Exchange\Model\Importers\AbstractImporter
{
    static public $pattern = '/ТипыЦен\/ТипЦены$/i';
    static public $title   = 'Импорт Типов цен';
    
    private $sXML;
    
    public function import(\XMLReader $reader)
    {  
        \Exchange\Model\Log::w(t("Импорт типа цены: ").$this->getSimpleXML()->Наименование);
        
        $typecost = new \Catalog\Model\Orm\Typecost();
        $typecost->site_id    = \RS\Site\Manager::getSiteId(); 
        $typecost->xml_id     = $this->getSimpleXML()->Ид;
        $typecost->title      = Tools::toEntityString($this->getSimpleXML()->Наименование);
        $typecost->type       = 'manual';
        $typecost->insert(false, array('xml_id', 'title', 'type'), array('xml_id', 'site_id'));

    }
}