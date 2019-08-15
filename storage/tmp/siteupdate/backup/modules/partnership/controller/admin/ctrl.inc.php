<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Partnership\Controller\Admin;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Table;

class Ctrl extends \RS\Controller\Admin\Crud
{
    function __construct()
    {
        parent::__construct(new \Partnership\Model\Api());
    }
    
    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Партнерские сайты'));
        $helper->setTopHelp($this->view->fetch('top_help.tpl'));
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('Добавить партнерский сайт'))));
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Text('title', t('Наименование'), array('href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'linkAttr' => array('class' => 'crud-edit') )),
                new TableType\Text('domains', t('Доменные имена')),
                new TableType\Text('theme', t('Тема оформления')),
                new TableType\Text('price_inc_value', t('Увеличение цены, %')),
                new TableType\Text('theme', t('Тема оформления')),
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                        'attr' => array(
                            '@data-id' => '@id'
                        )
                    )),
                    new TableType\Action\DropDown(array(
                        array(
                            'title' => t('Редактировать дизайн'),
                            'attr' => array(
                                '@href' => $this->router->getAdminPattern(false, array(':context' => 'partner-{id}'), 'templates-blockctrl')
                            )
                        ),
                        array(
                            'title' => t('Клонировать партнёра'),
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('clone', array(':id' => '~field~')),
                            )
                        ),  
                    ))
                ))
            )
        )));        
        
        return $helper;
    }
    
    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        $this->api->getElement()->tpl_module_folders = \RS\Module\Item::getResourceFolders('templates');
        return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
    }
    
    /**
    * Метод для клонирования
    * 
    */ 
    function actionClone()
    {
        $this->setHelper( $this->helperAdd() );
        $id = $this->url->get('id', TYPE_INTEGER);
        
        $elem = $this->api->getElement();
        
        if ($elem->load($id)) {
            $clone_id = null;
            if (!$this->url->isPost()) {
                $clone = $elem->cloneSelf();
                $this->api->setElement($clone);
                $clone_id = $clone['id']; 
            }
            unset($elem['id']);
            return $this->actionAdd($clone_id);
        } else {
            return $this->e404();
        }
    }
}
