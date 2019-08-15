<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model\Importers;

use Exchange\Model\Log as ExchangeLog;
use RS\Config\Loader as ConfigLoader;
use Shop\Model\UserStatusApi;
use Shop\Model\Orm\Order;

/**
 * Импорт документа заказа
 */

class Document extends AbstractImporter
{
    static public $pattern = '/КоммерческаяИнформация\/Документ$/i';
    static public $title    = 'Импорт заказа';


    public function import(\XMLReader $reader)
    {
        static $statuses = array();
        if (empty($statuses)) {
            $status_api = new UserStatusApi();
            $statuses = $status_api->getList();
        }

        ExchangeLog::w(t("Импорт документа заказа: ").$this->getSimpleXML()->Номер);
        $config = ConfigLoader::byModule($this);
        $order_id = (int)$this->getSimpleXML()->Номер;
        $order = new Order($order_id);
        if ($order['id']) {
            ExchangeLog::w(t("Заказ найден, проверка статусов"));
            $old_status = $order['status'];
            // Проведен ли заказ
            $is_provided  = false;
            $is_payed     = false;
            $is_shipped   = false;
            $is_success   = false;
            $is_cancelled = false;
            foreach($this->getSimpleXML()->ЗначенияРеквизитов->ЗначениеРеквизита as $one){
                switch ($one->Наименование) {
                    case 'Проведен':
                        if($one->Значение == 'true') {
                            $is_provided = true;
                        }
                        break;
                    case 'Номер оплаты по 1С':
                        if(!empty($one->Значение)) {
                            $is_payed = true;
                            $order['is_payed'] = true;
                        }
                        break;
                    case 'Номер отгрузки по 1С':
                        if(!empty($one->Значение)) {
                            $is_shipped = true;
                        }
                        break;
                    case 'Статус заказа':
                        if ($one->Значение == 'Исполнен') {
                            $is_success = true;
                        }
                        break;
                    case $config['order_flag_cancel_requisite_name']:
                        if($one->Значение == 'true') {
                            $is_cancelled = true;
                        }
                        break;
                }

                // Обновляем статус заказа, если включена соответствующая опция
                if ($one->Наименование == 'Статус заказа' && $config['order_update_status']) {
                    $new_status_id = null;
                    foreach ($statuses as $one_status){
                        if ($one_status['title'] == (string)$one->Значение) {
                            $new_status_id = $one_status['id'];
                        }
                    }
                    if ($new_status_id) {
                        $order['status'] = $new_status_id;
                    }
                }
            }

            // Если проведен и стоит настройка для переключения в нужный статус заказа

            // Устанавливаем статус заказа в соответствии с настройкой "Статус, в который переводить заказ при получении флага "Проведён" от "1С:Предприятие"
            if ($is_provided && $config['sale_final_status_on_delivery']) {
                $order['status'] = (string) $config['sale_final_status_on_delivery'];
            }
            // Устанавливаем статус заказа в соответствии с настройкой "Статус, в который переводить заказ при получении оплаты от "1С:Предприятие"
            if ($is_payed && $config['sale_final_status_on_pay']) {
                $order['status'] = (string) $config['sale_final_status_on_pay'];
            }
            // Устанавливаем статус заказа в соответствии с настройкой "Статус, в который переводить заказ при получении отгрузки от "1С:Предприятие"
            if ($is_shipped && $config['sale_final_status_on_shipment']) {
                $order['status'] = (string) $config['sale_final_status_on_shipment'];
            }
            // Устанавливаем статус заказа в соответствии с настройкой "Статус, в который переводить заказ при получении ставтуса "Исполнено" от "1С:Предприятие""
            if ($is_success && $config['sale_final_status_on_success']) {
                $order['status'] = (string) $config['sale_final_status_on_success'];
            }
            // Устанавливаем статус заказа в соответствии с настройкой "Статус, в который переводить заказ при получении флага "Отменён" "1С:Предприятие"
            if ($is_cancelled && $config['sale_final_status_on_cancel']) {
                $order['status'] = (string) $config['sale_final_status_on_cancel'];
            }

            if ($old_status != $order['status']){
                $order->update();
                ExchangeLog::w(t("Статус изменен"));
            }
        }
    }
}
