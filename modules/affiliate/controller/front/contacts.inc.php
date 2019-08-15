<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Affiliate\Controller\Front;

use Affiliate\Model\AffiliateApi;
use RS\Controller\ExceptionPageNotFound;
use RS\Controller\Front;
use RS\Controller\Result\Standard as ResultStandard;
use RS\Exception as RSException;

/**
 * Контроллер - контакты филиала
 */
class Contacts extends Front
{
    /** @var AffiliateApi */
    public $api;

    function init()
    {
        $this->api = new AffiliateApi();
    }

    /**
     * @return ResultStandard
     * @throws ExceptionPageNotFound
     * @throws RSException
     */
    function actionIndex()
    {
        $affiliate_id = $this->url->get('affiliate', TYPE_STRING);
        $affiliate = $this->api->getById($affiliate_id);

        if (!$affiliate['public']) $this->e404(t('Филиал не найден'));

        //Записываем в маршрут текущий филиал для блок-контроллеров
        $this->router->getCurrentRoute()->addExtra('affiliate', $affiliate);

        $this->app->breadcrumbs->addBreadCrumb(t('Контакты'));

        $meta_title = $affiliate['meta_title'] ?: t('Контакты в городе %0', array($affiliate['title']));

        $this->app->title->addSection($meta_title);
        $this->app->meta->addKeywords($affiliate['meta_keywords']);
        $this->app->meta->addDescriptions($affiliate['meta_description']);

        $this->view->assign(array(
            'affiliates' => $this->api->getTreeList(0),
            'affiliate' => $affiliate
        ));

        return $this->result->setTemplate('affiliate_contacts.tpl');
    }
}
