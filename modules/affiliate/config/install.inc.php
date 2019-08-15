<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Affiliate\Config;

class Install extends \RS\Module\AbstractInstall
{
    function install()
    {
        if ($result = parent::install()) {
            //Обновляем объект склада
            $warehouse = new \Catalog\Model\Orm\WareHouse();
            Handlers::ormInitCatalogWarehouse($warehouse);
            $warehouse->dbUpdate();
            
            //Обновляем объект меню
            $menu = new \Menu\Model\Orm\Menu();
            Handlers::ormInitMenuMenu($menu);
            $menu->dbUpdate();
        }
        
        return $result;
    }
    
    /**
    * Возвращает true, если модуль может вставить демонстрационные данные
    * 
    * @return bool
    */
    function canInsertDemoData()
    {
        return true;
    }
    
    /**
    * Добавляет демонстрационные данные
    * 
    * @param array $params - произвольные параметры. 
    * @return boolean|array
    */
    function insertDemoData($params = array())
    {
        return $this->importCsvFiles(array(
            array('\Affiliate\Model\CsvSchema\Affiliate', 'affiliate'),            
         ), 'utf-8', $params);
    }
    

    /**
    * Выполняется, после того, как были установлены все модули. 
    * Здесь можно устанавливать настройки, которые связаны с другими модулями.
    * 
    * @param array $options параметры установки
    * @return bool
    */
    function deferredAfterInstall($options)
    {
        //Изменяем тип пункта меню - Контакты на Контакты филиала
        $menu = \Menu\Model\Orm\Menu::loadByWhere(array(
            'site_id' => \RS\Site\Manager::getSiteId(),
            'alias'   => 'kontakty'
        ));
        
        $menu['site_id'] = \RS\Site\Manager::getSiteId();
        $menu['alias']   = 'kontakty';
        $menu['title']   = t('контакты');
        $menu['parent']  = 0;
        $menu['public']  = 1;
        $menu['typelink'] = 'affiliate';
        
        $menu->replace();
        return true;
    }    
    
}
?>
