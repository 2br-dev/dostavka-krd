<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Affiliate\Controller\Block;

use Affiliate\Model\AffiliateApi;
use RS\Controller\StandartBlock;

/**
 * Блок-контроллер "Выбор филиала"
 */
class SelectAffiliate extends StandartBlock
{
    protected static $controller_title = 'Выбор филиала';
    protected static $controller_description = 'Отображает текущий филиал, а также позволяет выбрать другой';

    protected $default_params = array(
        'indexTemplate' => 'blocks/selectaffiliate/select_affiliate.tpl', //Должен быть задан у наследника
    );

    /** @var AffiliateApi */
    public $api;

    function init()
    {
        $this->api = new AffiliateApi();
        $this->api->setFilter('public', 1);
    }

    /**
     * Отображение филиалов
     */
    function actionIndex()
    {
        $this->view->assign(array(
            'current_affiliate' => $this->api->getCurrentAffiliate(),
            'need_recheck' => (int)$this->api->needRecheck()
        ));
        return $this->result->setTemplate($this->getParam('indexTemplate'));
    }
}

