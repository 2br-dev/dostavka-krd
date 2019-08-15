<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace EmailSubscribe\Controller\Block;

use EmailSubscribe\Model\Api;
use RS\Config\Loader as ConfigLoader;
use RS\Controller\StandartBlock;

/**
 * Блок контроллер - Форма подписки на рассылку
 */
class SubscribeButton extends StandartBlock
{
    protected static $controller_title = 'Подписка на рассылку';
    protected static $controller_description = 'Отображает блок подписки на рассылку';

    protected $default_params = array(
        'indexTemplate' => 'blocks/button/button.tpl', //Должен быть задан у наследника
    );

    public function actionIndex()
    {
        $errors = array();
        if ($this->isMyPost()) { //Если E-mail передан
            $email = $this->request('email', TYPE_STRING, false);

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $api = new Api();
                if (!$api->checkEmailPresent($email)) {
                    $config = ConfigLoader::byModule($this);
                    if ($config['send_confirm_email']) {
                        $api->sendSubscribeToEmail($email);
                        $this->view->assign(array(
                            'success' => t('На Ваш E-mail отправлено письмо с дальнейшей инструкцией для подтверждения подписки')
                        ));
                    } else {
                        $this->view->assign(array(
                            'success' => t('Спасибо! Вы успешно подписаны на рассылку')
                        ));
                    }
                }
                $errors[] = t("Ваш E-mail (%0) уже присутствует в списке подписчиков", array($email));
            } else {
                $errors[] = t('Укажите правильный E-mail');
            }
        }
        $this->view->assign(array(
            'errors' => $errors
        ));
        return $this->result->setTemplate($this->getParam('indexTemplate'));
    }
}
