<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Feedback\Controller\Front;

use Feedback\Model\FormApi;
use RS\Controller\Front;

class Form extends Front
{
    function actionIndex()
    {
        $form_id = $this->url->request('form_id', TYPE_INTEGER);

        $api = new FormApi();
        $form = $api->getOneItem($form_id);
        $errors = array();
        $request = null;        

        if ($this->isMyPost()&&($form->id>0)) {//Если результат пришёл и форма такая существует
            /** @var FormApi $form */
            if ($api->send($form, $this->url)) { //OK
                $this->view->assign('success', true); 
            } else { //Если есть ошибки
                $errors  = $api->getErrors();
                $request = $this->url;
            }
        }
        
        $this->view->assign(array(
            'form'         => $form,
            'error_fields' => $errors,
            'request'      => $request
        ));
        
        return $this->result->setTemplate( 'form.tpl' );
    }
}

