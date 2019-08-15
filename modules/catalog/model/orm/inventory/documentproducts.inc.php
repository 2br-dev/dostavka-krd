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
 *  Таблица с товарами документов: оприходование, списание, резервирование, ожидание
 *
 * Class DocumentProducts
 * @package Catalog\Model\Orm\inventory
 */
class DocumentProducts extends \RS\Orm\OrmObject
{
    protected static
        $table = 'document_products'; //Имя таблицы в БД

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
            'amount' => new Type\Varchar(array(
                'maxLength' => '250',
                'description' => t('Количество'),
            )),
            'uniq' => new Type\Varchar(array(
                'maxLength' => '250',
                'description' => t('Уникальный Идентификатор'),
            )),
            'product_id' => new Type\Integer(array(
                'maxLength' => '250',
                'description' => t('Id товара'),
            )),
            'offer_id' => new Type\Integer(array(
                'maxLength' => '250',
                'description' => t('Id комплектации'),
            )),
            'warehouse' => new Type\Integer(array(
                'maxLength' => '250',
                'description' => t('Id склада'),
            )),
            'document_id' => new Type\Integer(array(
                'maxLength' => '250',
                'description' => t('Id документа'),
                'index' => true
            )),
        ));
        $this->addIndex(array('product_id'));
        $this->addIndex(array('product_id', 'offer_id', 'warehouse'));
    }
}