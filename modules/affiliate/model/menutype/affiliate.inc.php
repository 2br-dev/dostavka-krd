<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Affiliate\Model\MenuType;

use Affiliate\Model\AffiliateApi;
use Menu\Model\MenuType\AbstractType as MenuAbstractType;
use \RS\Router\Manager as RouterManager;

class Affiliate extends MenuAbstractType
{
    /**
     * Возвращает уникальный идентификатор для данного типа
     *
     * @return string
     */
    public function getId()
    {
        return 'affiliate';
    }

    /**
     * Возвращает название данного типа
     *
     * @return string
     */
    public function getTitle()
    {
        return t('Контакты филиала');
    }

    /**
     * Возвращает описание данного типа
     *
     * @return string
     */
    public function getDescription()
    {
        return t('Пункт меню будет ссылаться на персональную страницу контактов филиала');
    }

    /**
     * Возвращает ссылку, на которую должен вести данный пункт меню
     *
     * @param $absolute
     * @return string
     */
    public function getHref($absolute = false)
    {
        $affiliate = AffiliateApi::getCurrentAffiliate();
        $affiliate_id = $affiliate['alias'] ?: $affiliate['id'];
        return RouterManager::obj()->getUrl('affiliate-front-contacts', array('affiliate' => $affiliate_id), $absolute);
    }

    /**
     * Возвращает true, если пункт меню активен в настоящее время
     *
     * @return bool
     */
    public function isActive()
    {
        if ($route = RouterManager::obj()->getCurrentRoute()) {
            return $route->getId() == 'affiliate-front-contacts';
        }
        return false;
    }
}
