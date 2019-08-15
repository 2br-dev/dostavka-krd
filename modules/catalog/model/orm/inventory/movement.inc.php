<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Orm\Inventory;
use RS\Orm\Request;
use \RS\Orm\Type;

/**
 *  Таблица с документами перемещения
 *
 * Class Movement
 * @package Catalog\Model\Orm\Inventory
 */
class Movement extends \RS\Orm\OrmObject
{
    const
        DOCUMENT_TYPE_MOVEMENT = 'movement';

    protected static
        $table = 'document_movement'; //Имя таблицы в БД

    /**
     * Инициализирует свойства ORM объекта
     *
     * @return void
     */
    function _init()
    {
        parent::_init()->append(array(
            t('Основные'),
            'site_id' => new Type\CurrentSite(),
            'title' => new Type\Varchar(array(
                'runtime' => true,
                'visible' => false,
                'description' => t('Наименование'),
                'template' => '%catalog%/form/inventory/field_movement_title.tpl',
            )),
            'applied' => new Type\Integer(array(
                'checkboxview' => array(1, 0),
                'description' => t('Проведен'),
                'default' => 1,
            )),
            'comment' => new Type\Text(array(
                'description' => t('Комментарий'),
            )),
            'warehouse_from' => new Type\Integer(array(
                'list' => array(array('Catalog\Model\Warehouseapi', 'staticSelectList')),
                'maxLength' => '250',
                'description' => t('Со склада'),
                'checker' => array('chkEmpty', t('Укажите начальный склад')),
            )),
            'warehouse_to' => new Type\Integer(array(
                'list' => array(array('Catalog\Model\Warehouseapi', 'staticSelectList')),
                'maxLength' => '250',
                'description' => t('На склад'),
                'checker' => array('chkEmpty', t('Укажите конечный склад')),
            )),
            'date' => new Type\Datetime(array(
                'maxLength' => '250',
                'description' => t('Дата'),
                'checker' => array('chkEmpty', t('Укажите дату')),
            )),
            'linked_documents' => new Type\Varchar(array(
                'runtime' => true,
                'description' => t('Связанный документ'),
                'template' => '%catalog%/form/inventory/field_linked_document.tpl',
            )),
            'products' =>  new Type\Varchar(array(
                'runtime' => true,
                'description' => t('Товары'),
                'template' => '%catalog%/form/inventory/products.tpl',
            )),
            'type' =>  new Type\Varchar(array(
                'description' => t('Тип документа'),
                'visible' => false,
            )),
            'items' => new Type\ArrayList(array(
                'runtime' => true,
                'description' => t('Товары'),
                'visible' => false,
            )),
        ));
    }

    /**
     * Вызывается перед сохранением объекта в storage
     * @param string $save_flag insert|update|replace
     *
     * @return null | false Если возвращено false, то сохранение не произойдет
     */
    function beforeWrite($save_flag)
    {
        $this['type'] = self::DOCUMENT_TYPE_MOVEMENT;
        $api = new \Catalog\Model\Inventory\MovementApi();
        $result = $api->checkSaveErrors($this, $this['items']);
        if($result !== true){
            $this->addError($result['text'], $result['field']);
            return false;
        }
    }

    /**
     * Вызывается после сохранения объекта в storage
     * @param string $save_flag insert|update|replace
     *
     * @return void
     */
    function afterWrite ($save_flag)
    {
        $this->deleteProductsByDocument($this['id']);
        $api = new \Catalog\Model\Inventory\MovementApi();
        $api->saveLinkedDocuments($this, $this['items'], $save_flag);
        $result = $api->saveProducts($this['items'], $this['id'], $this['doc']);
    }

    /**
     * Удаляет объект из хранилища
     * @return boolean - true, в случае успеха
     */
    function delete()
    {
        $this->deleteLinkedDocuments($this['id']);
        $this->deleteProductsByDocument($this['id']);
        return parent::delete();
    }

    /**
     *  Удалить товары принадлежащие документу инвентаризации
     *
     * @param $document_id
     * @return void
     */
    function deleteProductsByDocument($document_id)
    {
        \RS\Orm\Request::make()
            ->delete()
            ->from(new \Catalog\Model\Orm\Inventory\MovementProducts())
            ->where(array('document_id' => $document_id))
            ->exec();
    }

    /**
     *  Удалить связанные документы
     *
     * @param $movement_id
     * @return void
     */
    function deleteLinkedDocuments($movement_id){
        $manager = new \Catalog\Model\DocumentLinkManager($movement_id, \Catalog\Model\Orm\Inventory\Inventorization::DOCUMENT_TYPE_INVENTORY);
        $manager->deleteLinkedDocuments();
    }

    /**
     *  Получить объект api
     *
     * @return \Catalog\Model\inventory\DocumentApi
     */
    function getApi()
    {
        return new \Catalog\Model\inventory\DocumentApi();
    }

    /**
     *  Получить связанные документы
     *
     * @return array|bool
     */
    function getLinkedDocuments()
    {
        $manager = new \Catalog\Model\DocumentLinkManager($this['id'], $this['type']);
        return $manager->getLinks();
    }

    /**
     *  Получить количество товаров в документе
     *
     * @return integer
     */
    function getProductsAmount()
    {
        $result = \RS\Orm\Request::make()
            ->select('sum(item.amount) as amount_sum')
            ->from(new \Catalog\Model\Orm\Inventory\Movement(), 'doc')
            ->leftjoin(new \Catalog\Model\Orm\Inventory\MovementProducts(), 'doc.id = item.document_id', 'item')
            ->where(array('doc.id' => $this->id))
            ->exec()
            ->getOneField('amount_sum');
        return $result;
    }

}