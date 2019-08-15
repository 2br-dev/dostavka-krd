<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Catalog\Controller\Block;

use Catalog\Model\FavoriteApi;
use RS\Controller\StandartBlock;

/**
* Блок-контроллер збранные товары
*/
class Favorite extends StandartBlock
{
    protected static
        $controller_title       = 'Избранное',
        $controller_description = 'Отображает избранные товары';

    protected
        $default_params = array(
            'indexTemplate' => 'blocks/favorite/favorite.tpl',
        );      
    
    function actionIndex()
    {
        $countFavorites = FavoriteApi::getFavoriteCount();
        
        $this->view->assign('countFavorite', $countFavorites);
        
        return $this->result->setTemplate( $this->getParam('indexTemplate') );
    }
}
