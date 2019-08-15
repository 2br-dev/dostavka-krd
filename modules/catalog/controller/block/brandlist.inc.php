<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Controller\Block;

use Catalog\Model\BrandApi;
use RS\Controller\StandartBlock;
use RS\Debug\Action as DebugAction;
use RS\Debug\Tool as DebugTool;
use RS\Orm\Type;

/**
 * Класс выводящи все бренды
 */
class BrandList extends StandartBlock
{
    protected static $controller_title = 'Все бренды';  //Краткое название контроллера
    protected static $controller_description = 'Выводит список всех брендов';  //Описание контроллера

    protected $default_params = array(
        'indexTemplate' => 'blocks/brands/brands.tpl',
    );

    /** @var BrandApi */
    public $api;

    function init()
    {
        $this->api = new BrandApi();
    }

    /**
     * Возвращает ORM объект, содержащий настриваемые параметры или false в случае,
     * если контроллер не поддерживает настраиваемые параметры
     * @return \RS\Orm\ControllerParamObject | false
     */
    function getParamObject()
    {
        return parent::getParamObject()->appendProperty(array(
            'pageSize' => new Type\Integer(array(
                'description' => t('Количество элементов для отображения. 0 - все'),
                'default' => 0,
            )),
        ));
    }

    function actionIndex()
    {
        $pageSize = $this->getParam('pageSize', 0);
        $brands = $this->api->getBrandsForBlock($pageSize);

        if ($debug_group = $this->getDebugGroup()) {
            $create_href = $this->router->getAdminUrl('add', array(), 'catalog-brandctrl');
            $debug_group->addDebugAction(new DebugAction\Create($create_href));
            $debug_group->addTool('create', new DebugTool\Create($create_href));
        }

        $this->view->assign(array(
            'brands' => $brands
        ));
        return $this->result->setTemplate($this->getParam('indexTemplate'));
    }
}
