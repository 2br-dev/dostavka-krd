<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Task;
use \Exchange\Model\Log;

/**
* Класс задачи, которая выполяется перед импортом import.xml
* Сбрасывает значение поля processed у товаров и групп в NULL
* 
* Объект этого класса хранится в сессии, соотвественно все свойства объекта доступны 
* не только до окончания выполнения скрипта, но и в течении всей сессии
*/

class BeforeImportTask extends \Exchange\Model\Task\AbstractTask
{
    protected $filename;  
    
    public function __construct($filename)
    {
        $this->filename = $filename;
    }
    
    public function exec($max_exec_time = 0)
    {
        if(!preg_match('/import/iu', $this->filename)) return true;
        
        /*
        * Поле processed != NULL означает, что этот товар (или группа) только что импортирован.
        * Для этого мы перед импортом устанавливаем у всех товаров (групп) processed = NULL
        * Таким образом после импорта мы можем отделить то, что мы импортировали от уже существовавших товаров (групп)
        * Это нужно для настроек "Что делать элементами, отсутствующими в файле импорта"
        */
        
        \Exchange\Model\Api::removeSessionIdFile(); //Удалим сессионый файл
        
        //Если сессионого файла нет
        if (!\Exchange\Model\Api::checkSessionFile()){
           //Очистим сессию с id товаров для обновления 
           $_SESSION[\Exchange\Model\Importers\CatalogProduct::SESS_KEY_GOODS_IDS] = array();
           //Очистим флаг - не использовать Характеристики товара в комплектациях
           $_SESSION[\Exchange\Model\Importers\CatalogProduct::SESS_KEY_NO_OFFER_PARAMS_FLAG] = false;
           \Exchange\Model\Api::saveSessionIdIntoFile();  
        }
        
        // Сбрасываем параметр "processed" у всех товаров в NULL
        \RS\Orm\Request::make()
            ->update(new \Catalog\Model\Orm\Product())
            ->set(array('processed' => null))
            ->exec();
            
        // Сбрасываем параметр "processed" у всех комплектаций в NULL
        \RS\Orm\Request::make()
            ->update(new \Catalog\Model\Orm\Offer())
            ->set(array('processed' => null))
            ->exec();

        // Сбрасываем параметр "processed" у всех категорий в NULL
        \RS\Orm\Request::make()
            ->update(new \Catalog\Model\Orm\Dir())
            ->set(array('processed' => null))
            ->exec();
            
        return true;
    }
}