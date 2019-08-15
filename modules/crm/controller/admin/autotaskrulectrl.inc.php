<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Crm\Controller\Admin;

use Crm\Config\ModuleRights;
use Crm\Model\AutoTask\RuleIf\AbstractIfRule;
use Crm\Model\Autotask\TaskTemplate;
use Crm\Model\Orm\Status;
use RS\Controller\Admin\Crud;
use RS\Controller\Admin\Helper\CrudCollection;
use RS\Html\Table;
use RS\Html\Table\Type as TableType;
use RS\Html\Filter;

class AutoTaskRuleCtrl extends Crud
{
    function __construct()
    {
        //Устанавливаем, с каким API будет работать CRUD контроллер
        parent::__construct(new \Crm\Model\AutoTaskRuleApi());
    }

    function helperIndex()
    {
        $helper = parent::helperIndex(); //Получим helper по-умолчанию
        $helper->setTopTitle('Правила для создания автозадач'); //Установим заголовок раздела
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить правило'))));
        $helper->setTopHelp(t('Здесь описываются правила, согласно которым система будет автоматически создавать задачи при наступлении выбранных событий'));

        //Отобразим таблицу со списком объектов
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id'),
                new TableType\Text('title', t('Название'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                new TableType\Yesno('enable', t('Включен'), array('toggleUrl' => $this->router->getAdminPattern('ajaxTogglePublic', array(':id' => '@id')))),

                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                ),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                )
            ))));

        //Добавим фильтр значений в таблице по названию
        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array(
                'Lines' =>  array(
                    new Filter\Line( array('items' => array(
                        new Filter\Type\Text('title', t('Название'), array('SearchType' => '%like%')),
                    )
                    ))
                ),
            ))
        )));

        return $helper;
    }

    function actionAjaxTogglePublic()
    {
        if ($access_error = \RS\AccessControl\Rights::CheckRightError($this, ModuleRights::AUTOTASK_UPDATE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
        $id = $this->url->get('id', TYPE_STRING);

        $action_template = $this->api->getOneItem($id);
        if ($action_template) {
            $action_template['enable'] = !$action_template['enable'];
            $action_template->update();
        }
        return $this->result->setSuccess(true);
    }

    /**
     * Возвращает HTML-код дополнительных форм для условия задачи
     */
    function actionAjaxGetRuleFormHtml()
    {
        $rule_if_class = $this->url->get('rule_if_class', TYPE_STRING);
        $rule_id_object = AbstractIfRule::makeById($rule_if_class);

        $this->view->assign('rule_if_object', $rule_id_object);
        return $this->result->setTemplate( 'form/autotaskrule/rule_data_form.tpl' );
    }

    function helperAddTaskRule()
    {
        $helper = new CrudCollection($this);
        $helper->setTopTitle(t('Добавить автозадачу'));
        $helper->setBottomToolbar($this->buttons(array(
            'save',
            'cancel'
        )));
        $helper->viewAsForm();

        return $helper;
    }

    /**
     * Открывает диалог добавления шаблона задачи
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionAddTaskRule()
    {
        $task_template_values_base64 = $this->url->get('task_template_values', TYPE_STRING);
        $rule_if_id = $this->url->get('rule_if_id', TYPE_STRING);

        $helper = $this->getHelper();
        $rule_if_object = AbstractIfRule::makeById($rule_if_id);

        $task_template = new TaskTemplate();
        $task_template->getFromBase64($task_template_values_base64); //Загружаем значения по умолчанию
        $rule_if_object->initTaskTemplate($task_template);

        if ($task_template_values_base64) {
            $helper->setTopTitle( t('Редактировать автозадачу {title}'), array('title' => $task_template['title'] ));
        }

        if ($this->url->isPost()) {
            if ($task_template->checkData()) {

                $this->view->assign(array(
                    'task_tpl' => $task_template
                ));

                return $this->result
                    ->setSuccess(true)
                    ->addSection('task_template_block', $this->view->fetch('%crm%/form/autotaskrule/rule_then_data_item.tpl'));

            } else {
                return $this->result->setSuccess(false)->setErrors($task_template->getDisplayErrors());
            }
        }


        $helper->setFormObject($task_template);

        $this->view->assign(array(
            'elements' => $helper->active(),
        ));
        return $this->result->setTemplate( $helper['template'] );
    }

    /**
     * Возвращает HTML одного правила для смены статуса
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionAjaxGetAutochangeStatusRule()
    {
        $statuses = Status::getStatusesTitles('crm-task');
        $first_status = count($statuses) > 1 ? reset($statuses) : 0;

        $uniq = uniqid(time());
        $rules_data = array(
            $uniq => array(
                'set_status' => $first_status,
                'groups' => array(
                    0 => array(
                        'items' => array(
                            0 => array(
                                'task_index' => '1',
                                'task_status' => $first_status
                            )
                        )
                    )
                )
            )
        );

        $this->view->assign(array(
            'statuses' => $statuses,
            'rules' => $rules_data
        ));

        return $this->result->setTemplate('form/tasktemplate/autochange_rule_parts/rule_item.tpl');

    }

    /**
     * Возвращает HTML одной группы для смены статуса
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionAjaxGetAutochangeStatusGroupItem()
    {
        $rule_uniq = $this->url->get('rule_uniq', TYPE_STRING);
        $statuses = Status::getStatusesTitles('crm-task');
        $first_status = count($statuses) > 1 ? reset($statuses) : 0;
        $uniq = uniqid(time());

        $groups_data = array(
            $uniq => array(
                'items' => array(
                    0 => array(
                        'task_index' => '1',
                        'task_status' => $first_status
                    )
                )
            )
        );

        $this->view->assign(array(
            'statuses' => $statuses,
            'rule_uniq' => $rule_uniq,
            'groups' => $groups_data
        ));

        return $this->result->setTemplate('form/tasktemplate/autochange_rule_parts/group_item.tpl');
    }

    /**
     * Возвращает HTML одной записи в группе для смены статуса
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionAjaxGetAutochangeStatusOrItem()
    {
        $rule_uniq = $this->url->get('rule_uniq', TYPE_STRING);
        $group_uniq = $this->url->get('group_uniq', TYPE_STRING);

        $statuses = Status::getStatusesTitles('crm-task');
        $first_status = count($statuses) > 1 ? reset($statuses) : 0;
        $uniq = uniqid(time());

        $groups_items_data = array(
            $uniq => array(
                'task_index' => '1',
                'task_status' => $first_status
            )
        );

        $this->view->assign(array(
            'statuses' => $statuses,
            'rule_uniq' => $rule_uniq,
            'group_uniq' => $group_uniq,
            'group_items' => $groups_items_data
        ));

        return $this->result->setTemplate('form/tasktemplate/autochange_rule_parts/or_item.tpl');

    }

}