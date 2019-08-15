<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Orm\Inventory;

/**
 *  Таблица с количеством архивных товаров
 *
 * Class StartNum
 * @package Catalog\Model\Orm\Inventory
 */
class StartNum extends \Catalog\Model\Orm\Xstock
{
    protected static
        $table = 'document_products_start_num'; //Имя таблицы в БД
}