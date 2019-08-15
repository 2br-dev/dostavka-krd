<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Affiliate\Controller\Block;

/**
* Блок контроллер, выводящий информацию о филиале
*/
class ShortInfo extends \RS\Controller\StandartBlock
{
    protected static
        $controller_title = 'Краткие контакты филиала',
        $controller_description = 'Отображает краткую контактную информацию о текущем филиале';
    
    public
        $api;
        
    protected
        $default_params = array(
            'indexTemplate' => 'blocks/shortinfo/short_info.tpl',
        );
        
    function init()
    {
        $this->api = new \Affiliate\Model\AffiliateApi();
    }    
    
    function actionIndex()
    {
        $this->view->assign(array(
            'current_affiliate' => $this->api->getCurrentAffiliate()
        ));
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
}