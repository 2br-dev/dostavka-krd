<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Controller\Admin;

use Exchange\Config\ModuleRights;
use Exchange\Model\Api as ExchangeApi;
use \RS\Html\Toolbar;
use \RS\Html\Table\Type as TableType;
use \RS\Html\Toolbar\Button as ToolbarButton;
use \RS\Html\Tree;
use \RS\Html\Table;
use \RS\Html\Filter;
use RS\AccessControl\Rights;

class Ctrl extends \RS\Controller\Admin\Front
{
    /** @var ExchangeApi */
    public $api;
    
    function init()
    {
        $this->api = ExchangeApi::getInstance();
        $this->app->title->addSection(t('Обмен данными'));
    }
    
    function actionIndex()
    {
        // Если это POST
        if($this->url->isPost()){
            // Загружаем файлы 
            return $this->uploadFiles();
        }
        
        $helper = new \RS\Controller\Admin\Helper\CrudCollection($this, null, $this->url);
        $helper->setAppendModuleOptionsButton(false);
        $helper->setTopTitle(t('Обмен данными с 1С'));
        $helper->setTopToolbar(new \RS\Html\Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\ModuleConfig(\RS\Router\Manager::obj()->getAdminUrl('edit', array('mod' => $this->mod_name), 'modcontrol-control'), t('настройка модуля обмена данными'))
            )
        )));


        $helper->viewAsAny();
        $helper['form'] = $this->view->fetch('exchange.tpl');
        
        $helper->setBottomToolbar(new Toolbar\Element( array(
            'Items' => array(
                new ToolbarButton\Button('', t('Загрузить'), array('attr' => array('class' => 'start_import btn-success'))),
            )
        )));
                
        $helper->active();

        $this->view->assign('elements', $helper);
        return $this->result->setTemplate( $helper['template'] );
    }
    
    /**
    * Загрузка XML-файлов из формы. Обработка POST
    * 
    */
    private function uploadFiles()
    {
        if (($error = Rights::CheckRightError($this, ModuleRights::RIGHT_EXCHANGE)) !== false) {
            $this->api->addError($error);
            return $this->result->setSuccess(false)->setErrors($this->api->getDisplayErrors());
        }
        
        $this->result->addSection('success_text_timeout', 0);
        
        $expected_name  = $this->url->post("expected_name", TYPE_STRING);
        
        if(empty($_FILES)){
            $this->api->addError(t("Ни один файл не выбран"));
            return $this->result->setSuccess(false)->setErrors($this->api->getDisplayErrors());
        }
        
        foreach($_FILES as $one){
            if($one['type'] != 'text/xml'){
                $this->api->addError(t("Неверный формат файла"));
                return $this->result->setSuccess(false)->setErrors($this->api->getDisplayErrors());
            }
        }
        
        foreach($_FILES as $one){
            @unlink($this->api->getDir().DS.$one['name']);
            $name = preg_match("/import/", $one['name']) ? "import.xml" : "offers.xml";
            move_uploaded_file($one['tmp_name'], $this->api->getDir().DS.$name);
        }

        return $this->result->setSuccess(true);
    }

}