<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Partnership\Controller\Front;

use RS\Controller\Front;
use Partnership\Model\Api as PartnershipApi;

class Profile extends Front
{
    function actionIndex()
    {   
        $this->app->breadcrumbs->addBreadCrumb(t('Профиль партнера'));
        $partner = PartnershipApi::getCurrentPartner();
        if (!$partner) return $this->e404();
        
        if ( $this->isMyPost() ) {
            $partner->usePostKeys(array('price_inc_value', 'logo', 'slogan', 'contacts'));
            if ($partner->save($partner['id'])) {
                $_SESSION['user_profile_result'] = t('Изменения сохранены');
                $this->refreshPage();
            }
        }
        
        if (isset($_SESSION['user_profile_result'])) {
            $this->view->assign('result', $_SESSION['user_profile_result']);
            unset($_SESSION['user_profile_result']);
        }        
        
        $this->view->assign('partner', $partner);      
        return $this->result->setTemplate('profile.tpl');
    }    
}
