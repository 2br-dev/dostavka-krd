<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Alerts\Model\SMS;

class Manager
{
    /**
    * Отправляет SMS сообщение
    * 
    * @param array | string $phone_numbers - массив телефонов, или строка телефонов, разделенных запятой 
    * @param string $tpl - путь к шаблону сообщения
    * @param mixed $data - параметры, передаваемые в шаблон
    * @param bool $suppress_exception - если true, по подавляет исключения
    * @return void
    */
    public static function send($phone_numbers, $tpl, $data, $suppress_exception = true)
    {
        if(!is_array($phone_numbers)){
            $phone_numbers = explode(',', (string)$phone_numbers);
        }
        
        $phone_numbers = array_map('trim', $phone_numbers);
        
        $config = \RS\Config\Loader::byModule('alerts');
        
        $view = new \RS\View\Engine();
        $view->assign('data', $data);
        $content = $view->fetch($tpl);
        
        $sender_class = $config['sms_sender_class'];
        $api = new \Alerts\Model\Api();
        
        if ($sender_class){
            /**
            * @var AbstractSender
            */
            if ($sender = $api->getSenderByShortName($sender_class)) {
                if ($config['sms_sender_log']) { // пишем лог
                    $log = \RS\Helper\Log::file(self::getLogFileName(), true);
                    $log_text = t('Попытка отправки SMS').'; '.t('получатели').' - '.implode(', ', $phone_numbers).'; '.t('содержимое').' - "'.$content.'"';
                    $log->append($log_text);
                }
                try {
                    $result = $sender->send($content, $phone_numbers);
                    
                    if ($config['sms_sender_log']) { // пишем лог
                        $log_text = ($result) ?: t('Результат неизвестен');
                        $log->append($log_text);
                    }
                } catch (\Exception $e) {
                    if ($config['sms_sender_log']) { // пишем лог
                        $log->append(t('Ошибка').' - '.$e->getMessage());
                    }
                    
                    if (!$suppress_exception) {
                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * Возвращает путь к лог-файлу SMS
     *
     * @return string
     */
    public static function getLogFileName()
    {
        return \Setup::$PATH . \Setup::$STORAGE_DIR . '/logs/alerts_sms_log.log';
    }
}
