<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Catalog\Controller\Block;

use Catalog\Model\DirApi;
use RS\Controller\StandartBlock;
use RS\Module\AbstractModel\TreeList\AbstractTreeListIterator;
use RS\Orm;

class TopCategories extends StandartBlock
{
    protected static $controller_title = 'Выборка категорий';
    protected static $controller_description = 'Отображает изображения и названия некоторых выбранных категорий';

    protected $default_params = array(
        'indexTemplate' => 'blocks/topcategories/top_categories.tpl', //Должен быть задан у наследника
        'category_ids' => array(),
        'sort_by_title' => ''
    );

    /** @var Dirapi */
    public $dir_api;

    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'category_ids' => new Orm\Type\ArrayList(array(
                'description' => t('Отображать категории'),
                'tree' => array(array('\Catalog\Model\DirApi', 'staticTreeList')),
                'attr' => array(array(
                    AbstractTreeListIterator::ATTRIBUTE_MULTIPLE => true,
                )),
            )),
            'sort' => new Orm\Type\Varchar(array(
                'description' => t('Сортировка'),
                'listFromArray' => array(array(
                    '' => t('Без сортировки'),
                    'id' => t('Идентификатор'),
                    'name' => t('Наименование'),
                    'sortn' => t('Порядок в административной панели')
                ))
            ))
        ));
    }

    function init()
    {
        $this->dir_api = new Dirapi();
    }

    function actionIndex()
    {
        $sort_by_title = $this->getParam('sort');
        if ($dir_ids = $this->getParam('category_ids')) {
            $this->dir_api->setFilter('id', $dir_ids, 'in');
            if (!empty($sort_by_title)) {
                $this->dir_api->setOrder($this->getParam('sort'));
            }
        }

        $this->view->assign(array(
            'categories' => $this->dir_api->getList()
        ));

        return $this->result->setTemplate($this->getParam('indexTemplate'));
    }
}
