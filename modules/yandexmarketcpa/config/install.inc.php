<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Config;
use YandexMarketCpa\Model\Initialize;

/**
 * Класс отвечает за установку и обновление модуля
 */
class Install extends \RS\Module\AbstractInstall
{
    function install()
    {
        if ($result = parent::install()) {
            //Вставляем в таблицы данные по-умолчанию,
            //в рамках нового сайта, вызывая принудительно обработчик события
            //Инициализируем по умолчанию все сайты
            $sites = \RS\Site\Manager::getSiteList();
            foreach ($sites as $site) {
                Handlers::onSiteCreate(array(
                    'orm' => $site,
                    'flag' => \RS\Orm\AbstractObject::INSERT_FLAG
                ));
            }
        }
        return $result;
    }

    function update()
    {
        if ($result = parent::update()) {
            //Обновляем структуру базы данных для объекта Склад
            $warehouse = new \Catalog\Model\Orm\WareHouse();
            $warehouse->dbUpdate();

            $order = new \Shop\Model\Orm\Order();
            $order->dbUpdate();

            $delivery = new \Shop\Model\Orm\Delivery();
            $delivery->dbUpdate();
        }
        return $result;
    }

    function deferredAfterInstall($options)
    {
        $init_helper = new Initialize(1);
        $init_helper->initializeSite();

        return true;
    }
}
