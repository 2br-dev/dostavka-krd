<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Model\Orm\inventory;
use \RS\Orm\Type;
use \Catalog\Model\Inventory\DocumentApi as DocumentApi;
use \Catalog\Model\Orm\Inventory\Inventorization as Inventorization;
use \Catalog\Model\Orm\Inventory\Movement as Movement;

/**
 *  Таблица со связями документов
 *
 * Class LinkedDocument
 * @package Catalog\Model\Orm\inventory
 */
class LinkedDocument extends \RS\Orm\AbstractObject
{
    protected static
        $table = 'document_links'; //Имя таблицы в БД

    /**
     * Инициализирует свойства ORM объекта
     *
     * @return void
     */
    function _init()
    {
        $this->getPropertyIterator()->append(array(
            'source_id' => new Type\Integer(array(
                'description' => t('id источника'),
                'checker' => array('chkEmpty', t('Укажите id источника')),
            )),
            'source_type' => new Type\Varchar(array(
                'description' => t('тип источника'),
                'checker' => array('chkEmpty', t('Укажите тип источника')),
            )),
            'document_id' => new Type\Varchar(array(
                'description' => t('id документа'),
                'checker' => array('chkEmpty', t('Укажите id документа')),
            )),
            'document_type' => new Type\Varchar(array(
                'description' => t('тип документа'),
                'checker' => array('chkEmpty', t('Укажите тип документа')),
            )),
            'order_num' => new Type\Varchar(array(
                'description' => t('Номер заказа'),
            )),
        ));
    }

    /**
     *  Получить массив с ссылкой на редактирование свзанного документа и названием
     *
     * @return array
     */
    function getData()
    {
        $router = \RS\Router\Manager::obj();
        $controller = '';
        $title = '';

        switch ($this['document_type']){
            case Document::DOCUMENT_TYPE_ARRIVAL:
                $controller = 'catalog-inventoryarrivalctrl';
                $title = t('Оприходование');
                break;
            case Document::DOCUMENT_TYPE_RESERVE:
                $controller = 'catalog-inventoryreservationctrl';
                $title = t('Резервирование');
                break;
            case Document::DOCUMENT_TYPE_WAITING:
                $controller = 'catalog-inventorywaitingsctrl';
                $title = t('Ожидание');
                break;
            case Document::DOCUMENT_TYPE_WRITE_OFF:
                $controller = 'catalog-inventorywriteoffctrl';
                $title = t('Списание');
                break;
            case Movement::DOCUMENT_TYPE_MOVEMENT:
                $controller = 'catalog-inventorymovementctrl';
                $title = t('Перемещение');
                break;
            case Inventorization::DOCUMENT_TYPE_INVENTORY:
                $controller = 'catalog-inventorizationctrl';
                $title = t('Инвентаризация');
                break;
            case \Shop\Model\Orm\Order::DOCUMENT_TYPE_ORDER:
                $controller = 'shop-orderctrl';
                $title = t('Заказ');
                break;
        }
        $link = $router->getAdminUrl('edit', array('id' => $this->document_id), $controller);
        $data = array(
            'title' => $title,
            'link' => $link,
        );
        return $data;
    }
}