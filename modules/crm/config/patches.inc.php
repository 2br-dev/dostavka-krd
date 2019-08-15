<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Crm\Config;

use Crm\Model\Orm\Deal;
use Crm\Model\Orm\Task;
use RS\Module\AbstractPatches;
use RS\Orm\Request;

class Patches extends AbstractPatches
{
    /**
     * Возвращает массив имен патчей.
     * В классе должны быть пределены методы:
     * beforeUpdate<ИМЯ_ПАТЧА> или
     * afterUPDATE<ИМЯ_ПАТЧА>
     *
     * @return array
     */
    function init()
    {
        return array(
            '405'
        );
    }

    /**
     * Устанавливаем сортировочный индекс по умолчанию
     */
    function afterUpdate405()
    {
        Request::make()
            ->update(new Task)
            ->set('board_sortn = id')
            ->where('board_sortn IS NULL')
            ->exec();

        Request::make()
            ->update(new Deal)
            ->set('board_sortn = id')
            ->where('board_sortn IS NULL')
            ->exec();
    }
}