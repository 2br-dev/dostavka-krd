<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Affiliate\Controller\Front;

class Affiliates extends \RS\Controller\Front
{
    public
        $api;
        
    function init()
    {
        $this->api = new \Affiliate\Model\AffiliateApi();
        $this->api->setFilter('public', 1);
    }
    
    function actionIndex()
    {
        $referer = $this->url->request('referer', TYPE_STRING);
        
        $this->app->headers->addCookie(\Affiliate\Model\AffiliateApi::COOKIE_ALREADY_SELECT, 1, time() + 60*60*24*365*10, '/');
        
        $this->view->assign(array(
            'affiliates' => $this->api->getTreeList(0),
            'referer' => $referer
        ));
        
        return $this->result->setTemplate('affiliates.tpl');
    }
    
}
?>
