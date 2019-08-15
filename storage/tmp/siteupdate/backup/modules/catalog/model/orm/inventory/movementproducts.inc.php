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
 *  Таблица с товарами документов перемещения
 *
 * Class MovementProducts
 * @package Catalog\Model\Orm\inventory
 */
class MovementProducts extends \RS\Orm\OrmObject
{
    protected static
        $table = 'document_movement_products'; //Имя таблицы в БД

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
            'amount' => new Type\Integer(array(
                'description' => t('Количество'),
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