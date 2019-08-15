<?php
namespace OnePageOrder\Config;
use \RS\Router;
use \RS\Orm\Type as OrmType;

class Handlers extends \RS\Event\HandlerAbstract
{
    function init()
    {
        $this
            ->bind('orm.init.partnership-partner')
            ->bind('getroute', null, null, 1);
    }
    
    /**
    * Возвращает маршруты данного модуля
    */
    public static function getRoute(array $routes) 
    {
        //Страница оформления заказа
        $routes[] = new Router\Route('shop-front-checkout', array(
            '/checkout/{Act}/', 
            '/checkout/'
        ), array(
            'controller' => 'onepageorder-front-checkout',
        ), t('Оформление заказа'));
        
        return $routes;
    }
    
    /**
    * Расширяем объект партнёрского сайта
    * 
    * @param \Partnership\Model\Orm\Partner $partner - партнёрский сайт
    */
    public static function ormInitPartnershipPartner(\Partnership\Model\Orm\Partner $partner)
    {
        $partner->getPropertyIterator()->append(array(
            t('Оформление заказа на одной странице'),
                'onepageorder_theme' => new OrmType\Varchar(array(
                    'description' => t('Тема оформления для заказа на одной странице'),
                    'list' => array(array('\OnePageORder\Model\Api', 'staticGetDefaultTemplates')),
                    'default' => 'flatlines' // тема по умолчанию?
                )),
        ));
    }
}