<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace YandexMarketCpa\Model\Notice;

/**
* Уведомление - изменился статус заказа
*/
class OrderSuccess extends \Alerts\Model\Types\AbstractNotice
                 implements \Alerts\Model\Types\InterfaceEmail, 
                            \Alerts\Model\Types\InterfaceDesktopApp
{
    public
        $order;
        
    public function getDescription()
    {
        return t('Пользователь подтвердил заказ на Яндекс.Маркете');
    } 

    function init(\Shop\Model\Orm\Order $order)
    {
        $this->order = $order;
    }
    
    function getNoticeDataEmail()
    {
        $config = \RS\Config\Loader::getSiteConfig();
        
        $notice_data = new \Alerts\Model\Types\NoticeDataEmail();
        
        $notice_data->email     = $config['admin_email'];
        $notice_data->subject   = t('Подтверждён заказ N%0 на сайте %1', array($this->order['order_num'], \RS\Http\Request::commonInstance()->getDomainStr()));
        $notice_data->vars      = $this;
        
        return $notice_data;
    }
    
    function getTemplateEmail()
    {
        return '%yandexmarketcpa%/notice/toadmin_ordersuccess.tpl';
    }
    
    /**
    * Возвращает путь к шаблону уведомления для Desktop приложения.
    * Уведомление не имеет детального просмотра. Не будет сохранено в истории Desktop приложения.
    * 
    * @return string
    */
    public function getTemplateDesktopApp()
    {}
    
    /**
    * Возвращает данные, которые необходимо передать при инициализации уведомления
    * 
    * @return \Alerts\Model\Types\NoticeDataDesktopApp
    */
    public function getNoticeDataDesktopApp()
    {
        $notice_data = new \Alerts\Model\Types\NoticeDataDesktopApp();
        $notice_data->title = t('Заказа №%num от %date подтверждён', array(
            'num' => $this->order->order_num,
            'date' => date('d.m.Y', strtotime($this->order->dateof))
        ));
        $notice_data->short_message = t('%user %nlподтвердил заказ №%num', array(
            'nl' => "\n",
            'user' => $this->order->getUser()->getFio(),
            'num' => $this->order->order_num
        ));
        $notice_data->link = \RS\Router\Manager::obj()->getAdminUrl('edit', array('id' => $this->order->id), 'shop-orderctrl', true);
        $notice_data->link_title = t('Перейти к заказу');
        
        return $notice_data;
    }
}