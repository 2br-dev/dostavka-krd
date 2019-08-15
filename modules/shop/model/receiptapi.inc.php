<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Shop\Model;
use RS\Orm\Request;
use Shop\Model\Orm\Receipt;
use \Shop\Model\Orm\Transaction,
    \Shop\Model\Orm\Order,
    \Shop\Model\PaymentType\PersonalAccount;

/**
* API функции для работы с чеками
*/
class ReceiptApi extends \RS\Module\AbstractModel\EntityList
{
    
    function __construct()
    {
        parent::__construct(new \Shop\Model\Orm\Receipt(), 
        array(
            'nameField' => 'title',
            'defaultOrder' => 'dateof DESC',
            'multisite' => true
        ));
    }
    
    
    /**
    * Возвращает объект транзакции для заказа у которого есть выбитые чеки возврата или false если нет
    * 
    * @param integer $order_id - id заказа
    * 
    * @return \Shop\Model\Orm\Transaction|false
    */
    public static function getTransactionForRefundReceiptByOrderId($order_id)
    {
        $transaction_api = new \Shop\Model\TransactionApi();
        /**
        * @var \Shop\Model\Orm\Transaction $transaction
        */
        $transaction = $transaction_api->setFilter('order_id', $order_id)
                                       ->setFilter('status', Transaction::STATUS_SUCCESS)
                                       ->getFirst();
        
        if ($transaction){ //Если транзакция есть, то запросим чеки
            $_this = new self();
            $list = $_this->setFilter('transaction_id', $transaction['id'])
                          ->setFilter('type', \Shop\Model\Orm\Receipt::TYPE_REFUND)
                          ->getList();
            return (count($list)) ? $transaction : false;
        }
        
        return false;
    }


    /**
     * Проверяет чеки, которые в статусе ожидания отправляют запрос на проверку чека.
     *
     * @param integer|null $site_id - текущий id сайта
     * @throws \RS\Orm\Exception
     * @return void
     */
    function checkWaitReceipts($site_id = null)
    {
        if (!$site_id){
            $site_id = \RS\Site\Manager::getSiteId();
        }
        $list = \RS\Orm\Request::make()
                ->from(new \Shop\Model\Orm\Receipt())
                ->where(array(
                    'site_id' => $site_id,
                    'type' => \Shop\Model\Orm\Receipt::TYPE_SELL,
                    'status' => \Shop\Model\Orm\Receipt::STATUS_WAIT
                ))->objects();

        if (!empty($list)){
            foreach ($list as $receipt){
                try {
                    $cashregister_api = new \Shop\Model\CashRegisterApi();
                    /**
                     * @var \Shop\Model\CashRegisterType\AbstractType $provider
                     */
                    $provider = $cashregister_api->getTypeByShortName($receipt['provider']);
                    $provider->getReceiptStatus($receipt);
                }catch(\Exception $e){
                    //Ничего не делаем. Пока.
                }
            }
        }
    }

    /**
     * Возвращает чек по подписи
     *
     * @param $sign
     */
    function getReceiptBySign($sign)
    {
        return Request::make()
            ->from(new Receipt())
            ->where(array(
                'sign' => $sign
            ))->object();
    }
}