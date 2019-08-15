<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace CDN\Controller\Admin;


use cdn\model\RegistrationForm;
use Cdn\Model\Utils;
use RS\Helper\Mailer;
use RS\Helper\PhpMailer\PHPMailer;
use RS\Html\Toolbar\Button\Button;
use RS\Html\Toolbar\Button\Save;
use RS\Html\Toolbar\Button\SaveForm;
use RS\Html\Toolbar\Button\Cancel;
use RS\Html\Toolbar\Element;
use \RS\Orm\Type;
use RS\Controller\Admin\Helper\CrudCollection;
use RS\Orm\FormObject;
use RS\Orm\PropertyIterator;
use RS\View\Engine;

class Ctrl extends \RS\Controller\Admin\Front
{
    function actionRegistrationForm()
    {
        $form_object = new RegistrationForm();

        $form_object->name = str_replace('.', '', $_SERVER['HTTP_HOST']);

        $helper = new CrudCollection($this);
        $helper->setTopTitle(t('Заявка на подключение к провайдеру CDNVideo'));
        $helper->setFormObject($form_object);
        $helper->viewAsForm();
        $helper->setHeaderHtml($this->view->fetch('registration_form_info.tpl'));

        $helper->setBottomToolbar(new Element(array(
            'items' => array(
                new SaveForm(null, t('Отправить заявку')),
                new Cancel($this->router->getAdminUrl('edit', array('mod' => 'cdn'), 'modcontrol-control'))
            ),
        )));

        if($this->url->isPost())
        {
            if ($form_object->checkData())
            {
                $sent = $form_object->sendRegistrationEmail();
                if($sent)
                {
                    $this->result->setTemplate('registration_sent.tpl');
                    return $this->result->setSuccess(true);
                }
                else
                {
                    return $this->result->setErrors($form_object->getDisplayErrors())->setSuccess(false);
                }
            }
            else
            {
                return $this->result->setErrors($form_object->getDisplayErrors())->setSuccess(false);
            }
        }

        $this->result->setTemplate( $helper['template'] );

        return $this->result;
    }

}
