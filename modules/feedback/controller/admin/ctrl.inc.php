<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Feedback\Controller\Admin;

use Feedback\Model\FieldApi as FeedbackFieldApi;
use Feedback\Model\FormApi as FeedbackFormApi;
use RS\Controller\Admin\Crud;
use RS\Html\Category;
use RS\Html\Table\Type as TableType;
use RS\Html\Toolbar\Button as ToolbarButton;
use RS\Html\Toolbar;
use RS\Html\Filter;
use RS\Html\Table;

class Ctrl extends Crud
{
    protected $dir;

    public function __construct()
    {
        parent::__construct(new FeedbackFieldApi());
        $this->setCategoryApi(new FeedbackFormApi(), t('форму'));
    }

    public function actionIndex()
    {
        //Если категории не существует, то выбираем пункт "Все"
        if ($this->dir > 0 && !$this->getCategoryApi()->getById($this->dir)) $this->dir = 0;
        if ($this->dir > 0) $this->api->setFilter('form_id', $this->dir);
        $this->getHelper()->setTopTitle(t('Формы связи'));

        return parent::actionIndex();
    }

    public function helperIndex()
    {
        $collection = parent::helperIndex();
        $this->dir = $this->url->request('dir', TYPE_INTEGER);

        $dir = $this->getCategoryApi()->getOneItem($this->dir);
        $dir_count = $this->getCategoryApi()->getListCount(); //Получим количество форм в списке всего
        if (!$dir && $dir_count) {
            $dir = $this->getCategoryApi()->getFirst();
            $this->dir = $dir['id'];
        }

        //Верхние правые кнопки добавления
        $collection->setTopHelp(t('В этом разделе можно создать формы с произвольными полями, чтобы получать необходимую обратную связь от ваших пользователей. Для каждой формы обратной связи будет сгенерирована ссылка на персональную страницу. Добавьте данную ссылку в пункт Меню или добавьте Блок "Кнопка обратной связи" на интересующую страницу через раздел <i>Веб-сайт &rarr; Конструктор сайта</i>.'));
        $collection->setTopToolbar(new Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\Dropdown(array(
                    $dir_count > 0 ? //Если форм ещё нету, то скроем кнопку добавления поля
                        array(
                            'title' => t('добавить поле'),
                            'attr' => array(
                                'href' => $this->router->getAdminUrl('add', array('dir' => $this->dir)),
                                'class' => 'btn-success crud-add'
                            )
                        ) : null,
                    array(
                        'title' => t('добавить форму'),
                        'attr' => array(
                            'href' => $this->router->getAdminUrl('categoryAdd'),
                            'class' => 'crud-add ' . (($dir_count == 0) ? 'btn-success' : '')
                        )
                    )
                )),
            )
        )));
        $collection->addCsvButton('feedback-forms');
        //Параметры таблицы в админке 
        $collection->setTable(new Table\Element(array(
            'Columns' => array(
                new TableType\Checkbox('id', array('ThAttr' => array('width' => '20'), 'TdAttr' => array('align' => 'center'))),
                new TableType\Sort('sortn', t('Порядок'), array('sortField' => 'id', 'Sortable' => SORTABLE_ASC, 'CurrentSort' => SORTABLE_ASC, 'ThAttr' => array('width' => '20'))),
                new TableType\Text('title', t('Название'), array('Sortable' => SORTABLE_BOTH, 'href' => $this->router->getAdminPattern('edit', array(':id' => '@id')), 'LinkAttr' => array('class' => 'crud-edit'))),
                new TableType\Text('show_type', t('Тип')),
                new TableType\StrYesno('required', t('Обязательное')),
                new TableType\Text('hint', t('Подпись'), array('hidden' => true)),
                new TableType\Actions('id', array(
                    new TableType\Action\Edit($this->router->getAdminPattern('edit', array(':id' => '~field~')), null, array(
                        'attr' => array(
                            '@data-id' => '@id'
                        )
                    ))
                ), array('SettingsUrl' => $this->router->getAdminUrl('tableOptions'))),
            ),
            'TableAttr' => array(
                'data-sort-request' => $this->router->getAdminUrl('move')
            )
        )));

        //Параметры фильтра
        $collection->setFilter(new Filter\Control(array(
            'Container' => new Filter\Container(array(
                'Lines' => array(
                    new Filter\Line(array('items' => array(
                        new Filter\Type\Text('id', '№', array('Attr' => array('size' => 4))),
                        new Filter\Type\Text('title', t('Название'), array('SearchType' => 'like%')),
                    )))
                ),
                'SecContainers' => array(
                    new Filter\Seccontainer(array(
                        'Lines' => array(
                            new Filter\Line(array('items' => array(
                                new Filter\Type\Text('alias', t('Псевдоним'), array('SearchType' => 'like%'))
                            )))
                        )
                    ))
                )
            )),
            'ToAllItems' => array('FieldPrefix' => $this->api->defAlias())
        )));

        //Настройки таблицы дерева форм
        $collection->setCategory(new Category\Element(array(
            'sortIdField' => 'sortn',
            'activeField' => 'id',
            'activeValue' => $this->dir,
            'unselectedTitle' => t('Не создано ни одной формы'),
            'sortable' => false,
            'mainColumn' => new TableType\Text('title', t('Название'), array('href' => $this->router->getAdminPattern(false, array(':dir' => '@id')))),
            'tools' => new TableType\Actions('id', array(
                new TableType\Action\Edit($this->router->getAdminPattern('categoryEdit', array(':id' => '~field~')), null, array(
                    'attr' => array(
                        '@data-id' => '@id'
                    )
                )),
                new TableType\Action\DropDown(array(
                    array(
                        'title' => t('показать форму на сайте'),
                        'attr' => array(
                            '@href' => $this->router->getUrlPattern('feedback-front-form', array(':form_id' => '~field~'), true),
                            'target' => '_blank'
                        )
                    ),
                    array(
                        'title' => t('удалить'),
                        'attr' => array(
                            '@href' => $this->router->getAdminPattern('categoryDel', array(':chk[]' => '~field~')),
                            'class' => 'crud-remove-one'
                        )
                    ),
                ))
            )),
            'headButtons' => array(
                array(
                    'text' => t('Название формы'),
                    'tag' => 'span',
                    'attr' => array(
                        'class' => 'lefttext'
                    )
                ),
                array(
                    'attr' => array(
                        'title' => t('Создать форму'),
                        'href' => $this->router->getAdminUrl('categoryAdd'),
                        'class' => 'add crud-add'
                    )
                )
            ),
        )), $this->getCategoryApi());

        //Нижняя панель дерева
        $collection->setCategoryBottomToolbar(new Toolbar\Element(array(
            'Items' => array(
                new ToolbarButton\Multiedit($this->router->getAdminUrl('categoryMultiEdit')),
                new ToolbarButton\Delete(null, null, array('attr' =>
                    array('data-url' => $this->router->getAdminUrl('categoryDel'))
                )),
            ))));

        $collection->setBottomToolbar($this->buttons(array('multiedit', 'delete')));
        $collection->viewAsTableCategory();
        return $collection;
    }

    public function actionAdd($primaryKeyValue = null, $returnOnSuccess = false, $helper = null)
    {
        if (!$primaryKeyValue) {
            $dir_id = $this->url->request('dir', TYPE_INTEGER);
            $element = $this->api->getElement();
            $element['form_id'] = $dir_id;
        }
        return parent::actionAdd($primaryKeyValue, $returnOnSuccess, $helper);
    }

    public function actionCategoryAdd($primaryKey = null)
    {
        if ($primaryKey === null) {
            $pid = $this->url->request('pid', TYPE_STRING, '');
            $this->getCategoryApi()->getElement()->offsetSet('form_id', $pid);
        }
        $this->getHelper()->setTopTitle($primaryKey ? t('Редактировать форму {title}') : t('Добавить форму'));

        return parent::actionAdd($primaryKey);
    }
}
