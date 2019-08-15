<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Importers;
use \Exchange\Model\Log; 
/**
* Импорт типа цены
*/

class Catalog extends \Exchange\Model\Importers\AbstractImporter
{
    static public $pattern = '/КоммерческаяИнформация\/Каталог$/i';
    static public $title    = 'Импорт аттрибутов каталога';
    
    const CONTAINS_ONLY_CHANGES_SESSION_KEY = 'import_contains_only_changes';
    
    public function import(\XMLReader $reader)
    {  
        $attributes = \Exchange\Model\XMLTools::getAttributes($reader);

        Log::w('Импорт аттрибутов каталога: '.Log::arr2str($attributes));
        
        // Если существует аттрибут "СодержитТолькоИзменения". Атрибут существует только начиная с версии схемы 2.04
        if(isset($attributes['СодержитТолькоИзменения'])){
            // Сохраняем содержит ли классификатор только изменения. Сохраняем в сессию, так как импорт идет во много шагов, и переменные будут потеряны
            if($attributes['СодержитТолькоИзменения'] == 'true'){
                \Exchange\Model\Log::w(t('Запись в сессию флага, что выгрузка содержит только изменения'));
                $_SESSION[self::CONTAINS_ONLY_CHANGES_SESSION_KEY] = true;
            }
            else{
                \Exchange\Model\Log::w(t('Запись в сессию флага, что это полная выгрузка'));
                $_SESSION[self::CONTAINS_ONLY_CHANGES_SESSION_KEY] = false;
            }
        }
        else{
            // Аттрибута "СодержитТолькоИзменения" не существует
            // Ничего не делаем
            // В этом случае флаг "СодержитТолькоИзменения" будет импортирован классом CatalogContainsOnlyChanges
        }
    }
    
    static public function containsOnlyChanges()
    {
        if(!isset($_SESSION[self::CONTAINS_ONLY_CHANGES_SESSION_KEY])){
            throw new \Exception(t("Переменная сессии %0 не установлена", array(self::CONTAINS_ONLY_CHANGES_SESSION_KEY)));
        }
        return $_SESSION[self::CONTAINS_ONLY_CHANGES_SESSION_KEY];
    }
}