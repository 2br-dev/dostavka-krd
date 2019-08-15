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
 *  Таблица с товарами докумаентов инвентаризации
 *
 * Class InventorizationProducts
 * @package Catalog\Model\Orm\inventory
 */
class InventorizationProducts extends \RS\Orm\OrmObject
{
    protected static
        $table = 'document_inventory_products'; //Имя таблицы в БД

    /**
     * Инициализирует свойства ORM объекта
     *
     * @return void
     */
    function _init()
    {
        parent::_init()->append(array(
            'title' => new Type\Varchar(array(
                'maxLength' => '250',
                'description' => t('Название'),
            )),
            'fact_amount' => new Type\Integer(array(
                'description' => t('Фактическое кол-во'),
                'visible' => false,
            )),
            'calc_amount' => new Type\Integer(array(
                'description' => t('Расчетное кол-во'),
                'visible' => false,
            )),
            'dif_amount' => new Type\Integer(array(
                'description' => t('Разница'),
                'visible' => false,
            )),
            'uniq' => new Type\Varchar(array(
                'maxLength' => '250',
                'description' => t('uniq'),
            )),
            'product_id' => new Type\Integer(array(
                'maxLength' => '250',
                'description' => t('id товара'),
            )),
            'offer_id' => new Type\Integer(array(
                'maxLength' => '250',
                'description' => t('id комплектации'),
            )),
            'document_id' => new Type\Integer(array(
                'maxLength' => '250',
                'description' => t('id документа'),
            )),
        ));
    }
}