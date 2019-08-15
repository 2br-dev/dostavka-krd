<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Controller\Admin;

use Antivirus\Model\ExcludedFileApi;
use Antivirus\Model\Libs\Diff;
use Antivirus\Model\Libs\Diff\Renderer\Html\SideBySide;
use Antivirus\Model\Orm\Event;
use Antivirus\Model\ServerApi;
use antivirus\model\Utils;
use RS\Config\Loader;
use RS\Controller\Admin\Crud;
use RS\Controller\Admin\Helper\CrudCollection;
use \RS\Html\Table\Type as TableType;
use \RS\Html\Toolbar;
use \RS\Html\Toolbar\Button as ToolbarButton;
use \RS\Html\Filter;
use \RS\Html\Table;
use \RS\AccessControl\Rights;
use \RS\AccessControl\DefaultModuleRights;
    
class Events extends Crud
{
    /**
     * @var ExcludedFileApi
     */
    private $exclude_api;


    /**
     * @var \Antivirus\Config\File
     */
    private $config;

    function __construct()
    {
        $api = new \Antivirus\Model\EventApi;
        parent::__construct($api);

        $this->exclude_api  = new ExcludedFileApi();
        $this->config       = Loader::byModule($this);
    }

    function helperIndex()
    {
        $helper = parent::helperIndex();
        $helper->setTopTitle(t('Найденные угрозы безопасности'));
        $helper->setTopHelp(t('В этом разделе отображаются угрозы безопасности, обнаруженные всеми компонентами модуля Антивирус. Если вы используете самую новую версию модуля, в котором обнаружен подозрительный файл, возможно автоматизированное лечение. В ходе лечения файл будет загружен с серверов обновления ReadyScript и перезаписан поверх поврежденного файла. Автоматизированное лечение файлов возможно только при наличии активной подписки на получение обнолений.'));
        $helper->setTopToolbar(new \RS\Html\Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\Button($this->router->getAdminUrl(null, null, 'antivirus-excludedfiles'), t('Исключения'))            
            )
        )));
        
        $exclude_api = $this->exclude_api;
        
        $helper->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('showSelectAll' => true)),
                new TableType\Viewed('viewed', $this->api->getMeterApi()),
                new TableType\Datetime('dateof', t('Дата'),array('Sortable' => SORTABLE_BOTH, 'CurrentSort' => SORTABLE_DESC)),
                new TableType\Text('component', t('Компонент'),array('Sortable' => SORTABLE_BOTH)),
                new TableType\Text('type', t('Тип'),array('Sortable' => SORTABLE_BOTH)),
                new TableType\Usertpl('details', t('Детали'), '%antivirus%/event_col_details.tpl', array('TdAttr' => array('class' => 'cell-small'))),
                new TableType\Yesno('viewed', t('Просмотрено'), array('toggleUrl' => $this->router->getAdminPattern('ajaxToggleViewed', array(':id' => '@id')), 'Sortable' => SORTABLE_BOTH)),
                new TableType\Actions('id', array(
                    new TableType\Action\DropDown(array(
                        array(
                            'title' => t('Лечить'),
                            'hidden' => function($action) {
                                $event = $action->getContainer()->getRow();
                                $is_signed = Utils::isFileSigned($event['file']);
                                $is_sig_db = Utils::isFileSignaturesDatabase($event['file']);
                                $visible = ($event->type == Event::TYPE_PROBLEM) && ($is_signed || $is_sig_db);
                                return !$visible;
                            },
                            'attr' => array(
                                'class' => 'crud-get',
                                'data-confirm-text' => t('Вы действительно хотите восстановить файл?'),
                                '@href' => $this->router->getAdminPattern('recover', array(':id' => '~field~')),
                            )
                        ),
                        array(
                            'title' => t('Удалить файл'),
                            'hidden' => function($action) {
                                $event = $action->getContainer()->getRow();
                                $is_signed = Utils::isFileSigned($event['file']);
                                $is_sig_db = Utils::isFileSignaturesDatabase($event['file']);
                                $visible = ($event->component == Event::COMPONENT_ANTIVIRUS) 
                                            && ($event->type == Event::TYPE_PROBLEM) && (!$is_signed && !$is_sig_db);
                                return !$visible;
                            },
                            'attr' => array(
                                'class' => 'crud-get',
                                'data-confirm-text' => t('Вы действительно хотите удалить файл?'),
                                '@href' => $this->router->getAdminPattern('delete', array(':id' => '~field~')),
                            )
                        ),                        
                        array(
                            'title' => t('Показать изменения'),
                            'hidden' => function($action) {
                                $event = $action->getContainer()->getRow();
                                $is_signed = Utils::isFileSigned($event['file']);
                                return $event->type != Event::TYPE_PROBLEM || !$is_signed;
                            },
                            'attr' => array(
                                'class' => 'crud-add',
                                '@href' => $this->router->getAdminPattern('showDiff', array(':id' => '~field~')),
                            )
                        ),
                        array(
                            'title' => t('Добавить в исключения'),
                            'hidden' => function($action) use($exclude_api) {
                                $event = $action->getContainer()->getRow();
                                if($event['component'] == Event::COMPONENT_PROACTIVE) return true;
                                $excluded = $exclude_api->isFileExcluded($event['file'], $event['component']);
                                return $event->type != Event::TYPE_PROBLEM || $excluded;
                            },
                            'attr' => array(
                                'class' => 'crud-get',
                                '@href' => $this->router->getAdminPattern('ajaxExclude', array(':id' => '~field~')),
                            )
                        ),
                        array(
                            'title' => t('Блокировать IP-адрес'),
                            'hidden' => function($action) {
                                $event = $action->getContainer()->getRow();
                                if($event['component'] != Event::COMPONENT_PROACTIVE) return true;
                                if($event['type'] == Event::TYPE_FIXED) return true;
                                return false;
                            },
                            'attr' => array(
                                'class' => 'crud-get',
                                '@href' => $this->router->getAdminPattern('ajaxBlockIp', array(':id' => '~field~')),
                            )
                        ),
                        array(
                            'title' => t('Скрыть угрозу'),
                            'attr' => array(
                                'class' => 'crud-get',
                                'data-confirm-text' => t('Вы действительно хотите скрыть информацию об угрозе?'),
                                '@href' => $this->router->getAdminPattern('del', array(':id' => '~field~')),
                            )
                        ),

                    )),
                ),
                    array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))
                ),
            ),
            'TableAttr' => array(
                'data-sort-request' => $this->router->getAdminUrl('move')
            ))));



        $helper->setFilter(new Filter\Control( array(
            'Container' => new Filter\Container( array(
                'Lines' =>  array(
                    new Filter\Line( array('items' => array(
                        new Filter\Type\Select('type', t('Тип'), array(''=>t('Любой')) + Event::getTypeList()),
                        new Filter\Type\Select('component', t('Компонент'), array(''=>t('Любой')) + Event::getComponentList()),
                    )
                    ))
                ),
            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias())
        )));

        
        $helper->setBottomToolbar(new Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\Button('javascript:;', t('Лечить'), array('attr'=>array(
                    'class'=>'fixSelected',
                    'data-url' => $this->router->getAdminUrl('multiFix', null, 'antivirus-events'),
                ))),
                new ToolbarButton\Button('javascript:;', t('Отметить просмотренными'), array('attr'=>array(
                    'class'=>'crud-post-selected',
                    'data-url' => $this->router->getAdminUrl('multiSetViewed', null, 'antivirus-events'),
                ))),
                new ToolbarButton\Delete(null, t('Скрыть'), array('attr' => 
                    array(
                        'data-url' => $this->router->getAdminUrl('del'),
                        'data-confirm-text' => t('Вы действительно хотите скрыть выбранные элементы (%count)?')
                    )
                ))
            )
        )));
        
        $this->app->addJs( $this->mod_js.'event_list.js', null, BP_ROOT);

        return $helper;
    }

    public function actionDelete()
    {
        if ($access_error = Rights::CheckRightError($this, DefaultModuleRights::RIGHT_DELETE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
        
        $id = $this->request('id', TYPE_INTEGER);
        $server_api = ServerApi::getInstance();
        $event = $this->api->getById($id);
        
        $server_api->deleteFiles(array($event->file));
        
        if ($server_api->hasError()) 
        {
            $this->result->addEMessage(join("\n", $server_api->getErrors()));
        } else {
            $this->result->addMessage(t('Файл удален'));
            $event['type'] = Event::TYPE_FIXED;
            $event->update();
        }
        
        return $this->result->setSuccess(true);
    }

    public function actionRecover()
    {
        if ($access_error = Rights::CheckRightError($this, DefaultModuleRights::RIGHT_CREATE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
        
        $id = $this->request('id', TYPE_INTEGER);
        $server_api = ServerApi::getInstance();
        $event = $this->api->getById($id);

        $server_api->recoverFiles(array($event->file));

        if($server_api->hasError())
        {
            $this->result->addEMessage(join("\n", $server_api->getErrors()));
        }
        else
        {
            $this->result->addMessage(t('Файл восстановлен'));
            $event['type'] = Event::TYPE_FIXED;
            $event->update();
        }

        return $this->result->setSuccess(true);
    }


    public function actionShowDiff()
    {
        $id = $this->request('id', TYPE_INTEGER);
        $server_api = ServerApi::getInstance();
        $event = $this->api->getById($id);

        // Options for generating the diff
        $options = array(
            //'ignoreWhitespace' => true,
            //'ignoreCase' => true,
        );

        if(file_exists(\Setup::$PATH . '/' . $event->file))
        {
            $content1 = file_get_contents(\Setup::$PATH . '/' . $event->file);
        }
        else
        {
            $content1 = t('Файл отсутствует');
        }

        $content2 = $server_api->downloadOneFileContent($event->file);
        if ($content2 !== false) {
            // Initialize the diff class
            $diff = new Diff(explode("\n",$content1), explode("\n",$content2), $options);
            $renderer = new SideBySide;
            $this->view->assign(array(
                'renderedDiff' => $diff->Render($renderer)
            ));
        } else {
            $this->view->assign(array(
                'error' => $server_api->getErrorsStr()
            ));
        }

        $this->view->assign(array(
            'file' => $event->file
        ));

        $helper = new CrudCollection($this);
        $helper->setTopTitle(t('Сравнение файлов'));
        $helper->viewAsForm();

        $helper['form'] = $this->view->fetch('show_diff.tpl');
        $this->result->setTemplate( $helper['template'] );

        return $this->result;
    }


    /**
     * Добавление файла в список исключений
     *
     * @return \RS\Controller\Result\Standard
     */
    public function actionAjaxExclude()
    {
        if ($access_error = Rights::CheckRightError($this, DefaultModuleRights::RIGHT_DELETE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
        
        $id = $this->request('id', TYPE_INTEGER);
        $event = $this->api->getOneItem($id);
        $this->exclude_api->add($event['file'], $event['component']);
        $event->delete();
        $this->result->addMessage(t('Файл добавлен в исключения'));
        return $this->result->setSuccess(true);
    }

    function actionAjaxToggleViewed()
    {
        if ($access_error = Rights::CheckRightError($this, DefaultModuleRights::RIGHT_UPDATE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
        
        $id = $this->url->get('id', TYPE_STRING);
        $event = $this->api->getOneItem($id);
        if ($event)
        {
            $event['viewed'] = !$event['viewed'];
            $event->update();
        }

        return $this->result->setSuccess(true)
                            ->addSection(array(
                                'meters' => array(
                                    $this->api->getMeterApi()->getMeterId() => $this->api->getUnreadEventCount(null, true)
                                )
                            ));
    }

    function actionAjaxBlockIp()
    {
        if ($access_error = Rights::CheckRightError($this, DefaultModuleRights::RIGHT_CREATE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
                
        $id = $this->url->get('id', TYPE_STRING);
        $event = $this->api->getOneItem($id);
        if(!$event)
        {
            return $this->result->setSuccess(false)->addEMessage(t('Объект не найден'));
        }
        $details = $event->getDetails();
        if(!$details || empty($details->ip))
        {
            return $this->result->setSuccess(false)->addEMessage(t('Не удалось получить IP-адрес'));
        }


        // Блокировка
        $duration           = (int)$this->config->proactive_block_duration;

        $blocked            = new \Main\Model\Orm\BlockedIp();
        $blocked['ip']      = $details->ip;
        $blocked['expire']  = $duration ? date('Y-m-d H:i:s', time() + $duration) : null;
        $blocked['comment'] = t('Модуль Antivirus: Ручная блокировка');
        $blocked->insert();

        // Помечаем событие как "FIXED"
        $event['type']      = Event::TYPE_FIXED;
        $event['viewed']    = true;
        $event->update();

        return $this->result->setSuccess(true)
                                ->addMessage(t('IP-адрес успешно заблокирован'))
                                ->addSection(array(
                                    'meters' => array(
                                        $this->api->getMeterApi()->getMeterId() => $this->api->getUnreadEventCount(null, true)
                                    )
                                ));
    }

    /**
     * Групповое лечение/восстановление элементов
     * Эта акция может вызываться несколько раз подряд, если время выполнения превышает лимит.
     *
     */
    function actionMultiFix()
    {
        if ($access_error = Rights::CheckRightError($this, DefaultModuleRights::RIGHT_UPDATE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
                
        $max_step_time = 15;
        $start_time = time();

        $ids = $this->modifySelectAll( $this->url->request('chk', TYPE_ARRAY, array()) );

        if ($this->url->isPost() && !empty($ids))
        {
            $this->api->setFilter('id', $ids, 'IN');
            $events = $this->api->getList();
            $filtered_events = array();

            // Фильтруем
            foreach($events as $event)
            {
                if($event['component'] == Event::COMPONENT_INTEGRITY || $event['component'] == Event::COMPONENT_ANTIVIRUS)
                {
                    $is_signed = Utils::isFileSigned($event['file']);
                    $is_sig_db = Utils::isFileSignaturesDatabase($event['file']);

                    if($is_signed || $is_sig_db)
                    {
                        $filtered_events[] = $event;
                    }
                }
            }

            if(empty($filtered_events))
            {
                $this->result->addSection('retry', false);
                $this->result->addEMessage(t('Нет выбранных файлов, поддающихся лечению'));
                return $this->result;
            }

            $files = array_map(function($e){ return $e->file; }, $filtered_events);

            $completed = false;

            // Пока время выполнения не превысило лимит, восстанавливаем файлы по частям
            while(time() < $start_time + $max_step_time)
            {
                $res = ServerApi::getInstance()->recoverFilesBySteps($files);

                // Если восстановление завершено
                if ($res === true)
                {
                    $completed = true;
                    break;
                }
            }

            $has_errors = ServerApi::getInstance()->hasError();

            // Если Server Api сообщил об ошибках
            if($has_errors)
            {
                $this->result->addSection('retry', false);
                $this->result->addEMessage(ServerApi::getInstance()->getErrorsStr());
                return $this->result;
            }

            // Если восстановление завершено, то помечаем проблемы исправленными
            if($completed)
            {
                foreach($filtered_events as $event)
                {
                    $event['viewed'] = true;
                    $event['type'] = Event::TYPE_FIXED;
                    $event->update();
                }

                $this->result->addSection('retry', false);
                $this->result->addMessage(t('Восстановление завершено'));
                $this->result->addSection(array(
                    'meters' => array(
                        $this->api->getMeterApi()->getMeterId() => $this->api->getUnreadEventCount(null, true)
                    )
                ));
                return $this->result;
            }


            $this->result->addSection('retry', !$completed);
        }

        return $this->result;
    }
    
    function actionMultiSetViewed()
    {
        if ($access_error = Rights::CheckRightError($this, DefaultModuleRights::RIGHT_UPDATE)) {
            return $this->result->setSuccess(false)->addEMessage($access_error);
        }
                
        $ids = $this->modifySelectAll( $this->url->request('chk', TYPE_ARRAY, array()) );
        if ($ids) {
            $new_counter = $this->api->markAsViewed(null, $ids);
            return $this->result->setSuccess(true)
                                ->addSection(array('meters' => array(
                                    $this->api->getMeterApi()->getMeterId() => $new_counter
                                )))
                                ->addMessage(t('Обновлено %0 событий', array(count($ids))));
        }
        
        return $this->result->setSuccess(false);
    }

}
