<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Crm\Controller\Admin;

use Crm\Model\Orm\Interaction;
use Crm\Model\Orm\Link;
use RS\Config\Loader;
use \RS\Html\Table\Type as TableType,
    \RS\Html\Toolbar\Button as ToolbarButton,
    \RS\Html\Filter,
    \RS\Html\Table;
use RS\Orm\AbstractObject;
use RS\Orm\Request;

/**
 * Контроллер Управление списком магазинов сети
 */
class InteractionCtrl extends \RS\Controller\Admin\Crud
{
    function __construct()
    {
        //Устанавливаем, с каким API будет работать CRUD контроллер
        parent::__construct(new \Crm\Model\InteractionApi());
        $this->api->initRightsFilters();
    }

    /**
     * Формирует визуальный хелпер для построения страницы index
     *
     * @return \RS\Controller\Admin\Helper\CrudCollection
     */
    function helperIndex()
    {
        $helper = parent::helperIndex(); //Получим helper по-умолчанию
        $helper->setTopTitle('Взаимодействия'); //Установим заголовок раздела
        $helper->setTopToolbar($this->buttons(array('add'), array('add' => t('добавить взаимодействие'))));
        $helper->setTopHelp(t('Взаимодействие - это один ваш контакт с клиентом, один телефонный разговор или одна встреча. Опишите во взаимодействии, как прошло ваше взаимодействие с клиентом, какие результаты были достигнуты. Здесь отображаются все взаимодействия в системе, независимо от выбранного мультисайта.'));
        $helper->addCsvButton('crm-interaction');

        $field_manager = Loader::byModule($this)->getInteractionUserFieldsManager();
        $custom_table_columns = $this->api->getCustomTableColumns($field_manager);

        //Отобразим таблицу со списком объектов
        $helper->setTable(new Table\Element(array(
            'Columns' => array_merge(array(
                new TableType\Checkbox('id'),
                new TableType\Text('title', t('Короткое описание'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                new TableType\Text('date_of_create', t('Дата'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                new TableType\User('creator_user_id', t('Создатель'), array('Sortable' => SORTABLE_BOTH)),
                new TableType\Text('duration', t('Продолжительность'), array('Sortable' => SORTABLE_BOTH, 'hidden' => true)),
                new TableType\Usertpl('links', t('Связи'), '%crm%/admin/table/links.tpl', array('hidden' => true))
            ),
            $custom_table_columns,
            array(
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~'))),
                ),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            )))));

        $this->api->addCustomFieldsData($helper['table'], $this->api->getElement()->getShortAlias());

        //Добавим фильтр значений в таблице по названию
        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array(
                'Lines' =>  array(
                    new Filter\Line( array('items' => array(
                        new Filter\Type\Text('title', t('Название'), array('SearchType' => '%like%')),
                        new Filter\Type\DateRange('date_of_create', t('Дата создания')),
                        new Filter\Type\Text('duration', t('Продолжительность'), array('SearchType' => '%like%')),
                        new Filter\Type\User('creator_user_id', t('Создатель')),
                        new \Crm\Model\FilterType\CustomFields('custom_fields', $field_manager, $this->api->getElement()->getShortAlias()),
                        new \Crm\Model\FilterType\Links('links', Interaction::getAllowedLinkTypes(), Interaction::getLinkSourceType())
                    )
                    ))
                ),
            ))
        )));

        return $helper;
    }

    /**
     * Добавляет взаимодействие
     *
     * @param null $primaryKeyValue
     * @param bool $returnOnSuccess
     * @param null $helper
     * @return bool|\RS\Controller\Result\Standard
     */
    function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        $link_type = $this->url->get('link_type', TYPE_STRING);
        $link_id = $this->url->get('link_id', TYPE_INTEGER);

        $helper = $this->getHelper();
        $element = $this->api->getElement();

        if (!$primaryKeyValue) { //Если создание сделки
            $helper->setTopTitle(t('Добавить взаимодействие'));

            $element['creator_user_id'] = $this->user->id;
            $element['date_of_create'] = date('Y-m-d H:i:s');
            $element->setTemporaryId();

            if ($link_type && $link_id) {
                $element['__links']->setVisible(false);
            }
            $element->initUserRights(AbstractObject::INSERT_FLAG);
        } else {
            $helper->setTopTitle(t('Редактировать взаимодействие {title}'));
            $element->initUserRights(AbstractObject::UPDATE_FLAG);
        }

        if ($link_type && $link_id) {
            $this->user_post_data['links'] = array(
                $link_type => array($link_id)
            );
        }

        return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
    }

    /**
     * Удаляет несвязанные взаимодействия
     *
     * @return \RS\Controller\Result\Standard
     */
    function actionDeleteUnlinked()
    {
        $affected = Request::make()
            ->delete('I')
            ->from(new Interaction(), 'I')
            ->leftjoin(new Link(), "L.source_id = I.id AND L.source_type = '".Interaction::getLinkSourceType()."'", "L")
            ->where('L.source_type IS NULL')
            ->exec()->affectedRows();

        return $this->result->setSuccess(true)->addEMessage(t('Удалено %0 [plural:%0:взаимодействие|взаимодействия|взаимодействий]', array($affected)));
    }
}
