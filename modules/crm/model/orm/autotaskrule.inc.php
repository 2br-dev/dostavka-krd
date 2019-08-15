<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Crm\Model\Orm;

use Crm\Config\ModuleRights;
use Crm\Model\AutoTask\RuleIf\AbstractIfRule;
use Crm\Model\Autotask\TaskTemplate;
use RS\Orm\OrmObject;
use RS\Orm\Type;

/**
 * ORM Объект одного правила для создания автозадачи
 */
class AutoTaskRule extends OrmObject
{
    protected static
        $table = 'crm_autotaskrule';

    function _init()
    {
        parent::_init()->append(array(
            'title' => new Type\Varchar(array(
                'description' => t('Название'),
                'checker' => array('chkEmpty', t('Укажите название правила'))
            )),
            'enable' => new Type\Integer(array(
                'description' => t('Включено'),
                'checkboxView' => array(1,0)
            )),
            'rule_if_class' => new Type\Varchar(array(
                'description' => t('Когда создавать задачи?'),
                'hint' => t('Выберите модуль, который будет инициировать событие на создание автозадач'),
                'list' => array(array(__CLASS__, 'getIfRulesNames')),
                'default' => 'crm-createorder',
                'template' => '%crm%/form/autotaskrule/rule_if_class.tpl',
                'checker' => array('chkEmpty', t('Необходимо выбрать, когда создавать задачи'))
            )),
            'rule_if_data' => new Type\Text(array(
                'description' => t('Дополнительные параметры'),
                'visible' => false
            )),
            'rule_if_data_arr' => new Type\ArrayList(array(
                'description' => '',
                'visible' => false
            )),

            'rule_then_data' => new Type\Text(array(
                'description' => t('Данные, которые описывают создание связанных задач'),
                'visible' => false
            )),
            'rule_then_data_arr' => new Type\ArrayList(array(
                'description' => 'Задачи',
                'template' => '%crm%/form/autotaskrule/rule_then_data.tpl',
                'checker' => array('chkEmpty', t('Уточните задачи, которые необходимо создавать'))
            ))
        ));
    }

    /**
     * Вызывается перед сохранением объекта в БД
     *
     * @param string $flag
     */
    public function beforeWrite($flag)
    {
        $this['rule_if_data'] = serialize($this['rule_if_data_arr']);
        $this['rule_then_data'] = serialize($this['rule_then_data_arr']);
    }

    /**
     * Вызывается сразу после чтения объекта из БД
     */
    public function afterObjectLoad()
    {
        $this['rule_if_data_arr'] = @unserialize($this['rule_if_data']) ?: array();
        $this['rule_then_data_arr'] = @unserialize($this['rule_then_data']) ?: array();
    }

    /**
     * Возвращает объекты шаблонов задач
     *
     * @return array
     */
    function getTasks()
    {
        $result = array();
        if ($this['rule_then_data_arr']) {

            $if_rule_object = $this->getRuleIfObject();

            foreach ($this['rule_then_data_arr'] as $item) {
                $task_template = new TaskTemplate();
                $task_template->getFromBase64($item);
                $if_rule_object->initTaskTemplate($task_template);

                $result[] = $task_template;
            }
        }

        return $result;
    }

    /**
     * Возвращает список всех зарегистрированных в системе классов с условиями
     *
     * @return array
     */
    public static function getIfRulesNames()
    {
        $rules = AbstractIfRule::getAllRules();
        $result = array();
        foreach($rules as $rule) {
            $result[$rule->getId()] = $rule->getTitle();
        }

        return $result;
    }

    /**
     * Возвращает объект класса-условия
     * @return AbstractIfRule
     */
    public function getRuleIfObject()
    {
        return AbstractIfRule::makeById($this['rule_if_class']);
    }

    /**
     * Возвращает идентификатор права на чтение для данного объекта
     *
     * @return string
     */
    public function getRightRead()
    {
        return ModuleRights::AUTOTASK_READ;
    }

    /**
     * Возвращает идентификатор права на создание для данного объекта
     *
     * @return string
     */
    public function getRightCreate()
    {
        return ModuleRights::AUTOTASK_CREATE;
    }

    /**
     * Возвращает идентификатор права на изменение для данного объекта
     *
     * @return string
     */
    public function getRightUpdate()
    {
        return ModuleRights::AUTOTASK_UPDATE;
    }

    /**
     * Возвращает идентификатор права на удаление для данного объекта
     *
     * @return string
     */
    public function getRightDelete()
    {
        return ModuleRights::AUTOTASK_DELETE;
    }
}