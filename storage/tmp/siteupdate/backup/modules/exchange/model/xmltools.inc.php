<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model;

class XMLTools{
    
    /**
    * Получить аттрибуты текущего xml-элемента
    * 
    * @param \XMLReader $reader
    * @return array
    */
    static public function getAttributes(\XMLReader $reader)
    {
        if(!$reader->hasAttributes) return array();
        $attrs = array();
        
        $reader->moveToFirstAttribute();
        $attrs[$reader->name] = $reader->value;
        
        while($reader->moveToNextAttribute()){
            $attrs[$reader->name] = $reader->value;
        }
        $reader->moveToElement();
        return $attrs;
    }
    
    /**
    * Добавить один SimpleXMLElement в другой
    * 
    * @param \SimpleXMLElement $to
    * @param \SimpleXMLElement $from
    * @return void
    */
    static function sxmlAppend(\SimpleXMLElement $to, \SimpleXMLElement $from) 
    {
        $toDom   = dom_import_simplexml($to);
        $fromDom = dom_import_simplexml($from);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
    }
}