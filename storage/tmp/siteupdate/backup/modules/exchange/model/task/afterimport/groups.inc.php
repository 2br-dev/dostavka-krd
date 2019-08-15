<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Task\AfterImport;
use Catalog\Model\Dirapi;
use \Exchange\Model\Log;

/**
* Объект этого класса хранится в сессии, соотвественно все свойства объекта доступны 
* не только до окончания выполнения скрипта, но и в течении всей сессии
*/

class Groups extends \Exchange\Model\Task\AbstractTask
{
    protected $filename;  
    
    public function __construct($filename)
    {
        $this->filename = $filename;
    }
    
    public function exec($max_exec_time = 0)
    {
        //Вызовем хук
        \RS\Event\Manager::fire('exchange.task.afterimport.groups', array(
            'filename' => $this->filename
        )); 
        
        // Только для import.xml
        if(!preg_match('/import/iu',$this->filename)){ 
            Log::w(t("Ничего не делаем, так как это не import.xml"));
            return true;
        }

        //Обновляем кэш сведения о вложенности элементов
        Dirapi::updateLevels();
        
        // Если классификатор содержит только изменения, то ничего не делаем
        if(\Exchange\Model\Importers\Catalog::containsOnlyChanges()){
            Log::w(t("Ничего не делаем, так как классификатор содержит только изменения"));
            return true;
        }

        $config = \RS\Config\Loader::byModule($this);
        
        // Если установлена настройка "Что делать с разделами, отсутствующими в файле импорта -> Ничего не делать"
        if($config->catalog_section_action == \Exchange\Config\File::ACTION_NOTHING){
            Log::w(t("Ничего не делаем, так как установлена настройка Ничего не делать c элементами, отсутсвующими в файле импорта"));
            return true;
        }
        
        // Если установлена настройка "Что делать с разделами, отсутствующими в файле импорта -> Удалять"
        if($config->catalog_section_action == \Exchange\Config\File::ACTION_REMOVE)
        {
            Log::w(t("Удаление категорий, которые не учавствуют в файле импорта..."));
            while(true)
            {
                // Удалению подлежат только категориии импортированные ранее, не являющиеся "спец-категориями"
                $dir = \Catalog\Model\Orm\Dir::loadByWhere(
                    'site_id = #site_id and is_spec_dir = "N" and processed is null and xml_id > ""',
                    array('site_id' => \RS\Site\Manager::getSiteId())
                );
                
                // Если не осталось больше объектов для удаления
                if(!$dir->id){
                    Log::w(t("Нет больше категорий для удаления"));
                    return true;
                }

                // Если привышено время выполнения
                if($this->isExceed()){
                    return false;
                }

                Log::w(t("Удаление категории ").$dir->name);
                $dir->delete();
            }
        }

        // Если установлена настройка "Что делать с разделами, отсутствующими в файле импорта -> Деактивировать"
        if($config->catalog_section_action == \Exchange\Config\File::ACTION_DEACTIVATE)
        {
            Log::w(t("Деактивация категорий, которые не учавствуют в файле импорта..."));

            // Скрываем категории
            $affected = \RS\Orm\Request::make()
                ->update(new \Catalog\Model\Orm\Dir())
                ->set(array('public' => 0))
                ->where(array( 
                    'site_id'   => \RS\Site\Manager::getSiteId(),
                    'processed' => null,
                ))
                ->exec()->affectedRows();
            Log::w(t("Дактивировано категорий: ").$affected);
            return true;
        }
        
        throw new \Exception('Impossible 1!');
    }
}