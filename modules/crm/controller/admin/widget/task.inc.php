<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Crm\Controller\Admin\Widget;

use Crm\Config\ModuleRights;
use Crm\Model\TaskApi;
use Crm\Model\TaskFilterApi;
use RS\AccessControl\Rights;
use RS\Controller\Admin\Widget;
use RS\Helper\Paginator;

/**
 * Виджет задач
 */
class Task extends Widget
{
    const
        PAGE_SIZE = 15;

    protected
        $info_title = 'Задачи', //Определить у наследников название виджета.
        $info_description = 'Виджет отображает список отобранных задач'; //Определить у наследников описание виджета

    function actionIndex()
    {
        if (Rights::hasRight($this, ModuleRights::TASK_READ)) {
            $cookie_filter_var = 'task_active_filter';
            $default_filter = $this->url->cookie($cookie_filter_var, TYPE_STRING, 'all');
            $task_active_filter = $this->myRequest('task_active_filter', TYPE_STRING, $default_filter);

            $cookie_expire = time()+60*60*24*730;
            $cookie_path = $this->router->getUrl('main.admin');
            $this->app->headers->addCookie($cookie_filter_var, $task_active_filter, $cookie_expire, $cookie_path);

            $page = $this->myRequest('p', TYPE_INTEGER, 1);
            $page_size = $this->getModuleConfig()->widget_task_pagesize;

            $task_filters_api = new TaskFilterApi();
            $task_api = new TaskApi();
            $task_api->initRightsFilters();

            if ($task_active_filter != 'all') {
                $filter = $task_filters_api->getById($task_active_filter);
                if ($filter) {
                    $task_api->applyFilter($filter);
                }
            }

            $tasks = $task_api->getList($page, $page_size);
            $paginator = new Paginator($page,
                $task_api->getListCount(),
                $page_size,
                'main.admin',
                array(
                    'mod_controller' => $this->getUrlName()
                )
            );

            $this->view->assign(array(
                'task_filters' => $task_filters_api->getSelectList(array('all' => t('Все'))),
                'task_active_filter' => $task_active_filter,
                'tasks' => $tasks,
                'paginator' => $paginator
            ));
        } else {
            $this->view->assign(array(
                'no_rights' => true
            ));
        }

        return $this->result->setTemplate('admin/widget/task.tpl');
    }

    /**
     * Возвращает массив с кнопками, которые будут отображаться в шапке виджета.
     * Все элементы массива будут добавлены как атрибуты к тегу <a>
     *
     * @return array
     * Пример:
     * array(
     *     array(
     *          'title' => t('Обновить'),
     *          'class' => 'zmdi zmdi-refresh'
     *     )
     * );
     */
    function getTools()
    {
        $router = \RS\Router\Manager::obj();
        return array(
            array(
                'title' => t('Добавить задачу'),
                'class' => 'zmdi zmdi-plus crud-add',
                'href' => $router->getAdminUrl('add', array('context' => 'widget'), 'crm-taskctrl'),
                '~data-crud-options' => "{ \"updateBlockId\": \"crm-widget-task\" }",
                'id' => 'crm-add-task'
            ),
            array(
                'title' => t('Все задачи'),
                'class' => 'zmdi zmdi-open-in-new',
                'href' => $router->getAdminUrl(false, array(), 'crm-taskctrl')
            )
        );
    }
}