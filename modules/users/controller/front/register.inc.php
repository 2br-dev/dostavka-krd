<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Controller\Front;

use RS\Application\Auth as AppAuth;
use RS\Controller\Front;
use RS\Helper\Tools as HelperTools;
use RS\Orm\Type;
use RS\Site\Manager as SiteManager;
use Users\Model\Orm\User;

class Register extends Front
{
    /** Поля, которые следует ожидать из POST */
    public $use_post_keys = array('is_company', 'company', 'company_inn', 'fio', 'name', 'surname', 'midname', 'phone', 'e_mail', 'openpass', 'openpass_confirm', 'captcha', 'data');

    function actionIndex()
    {
        $this->app->breadcrumbs->addBreadCrumb(t('Регистрация'));

        $user = new User();
        $user->usePostKeys($this->use_post_keys);
        //Добавим объекту пользователя 2 виртуальных поля
        $user->getPropertyIterator()->append(array(
            'openpass_confirm' => new Type\Varchar(array(
                'name' => 'openpass_confirm',
                'maxLength' => '100',
                'description' => t('Повтор пароля'),
                'runtime' => true,
                'Attr' => array(array('size' => '20', 'type' => 'password', 'autocomplete' => 'off')),
            )),
        ));

        $referer = HelperTools::cleanOpenRedirect(urldecode($this->url->request('referer', TYPE_STRING)));

        /** @var \Users\Config\File $config */
        $config = $this->getModuleConfig();
        $conf_userfields = $config->getUserFieldsManager()
            ->setErrorPrefix('userfield_')
            ->setArrayWrapper('data');

        //Включаем капчу
        $user['__captcha']->setEnable(true);

        if ($this->isMyPost()) {
            $user['changepass'] = 1;

            $user->checkData();
            $password = $this->url->request('openpass', TYPE_STRING);
            $password_confirm = $this->url->request('openpass_confirm', TYPE_STRING);

            if (strcmp($password, $password_confirm) != 0) {
                $user->addError(t('Пароли не совпадают'), 'openpass');
            }

            //Сохраняем дополнительные сведения о пользователе
            if (!$conf_userfields->check($user['data'])) {
                //Переносим ошибки в объект order
                foreach ($conf_userfields->getErrors() as $form => $errortext) {
                    $user->addError($errortext, $form);
                }
            }

            if (!$user->hasError() && $user->save()) {
                //Если пользователь уже зарегистрирован
                if (AppAuth::login($user['login'], $password)) {
                    if ($this->url->request('dialogWrap', TYPE_INTEGER)) {
                        return $this->result->addSection('closeDialog', true)->addSection('reloadPage', true);
                    } else {
                        if (empty($referer)) $referer = SiteManager::getSite()->getRootUrl();
                        $this->app->redirect($referer);
                    }
                }
            }
        }

        $this->view->assign(array(
            'conf_userfields' => $conf_userfields,
            'user' => $user,
            'referer' => urlencode($referer)
        ));

        return $this->result->setTemplate('register.tpl');
    }
}
