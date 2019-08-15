<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Orm\inventory;
use \RS\Orm\Type;

/**
 *  таблица с товарами архивных документов
 *
 * Class DocumentProductsArchive
 * @package Catalog\Model\Orm\inventory
 */
class DocumentProductsArchive extends \Catalog\Model\Orm\Inventory\DocumentProducts
{
    protected static
        $table = 'document_products_archive'; //Имя таблицы в БД

}