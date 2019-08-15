<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Antivirus\Model;


/**
 * Класс реализует некоторые методы класса \RS\Module\Item
 * Используется для того, чтобы добавить модуль core к списку существующих модулей системы
 * добавленных в очередь на проверку
 *
 * Class FakeCoreModuleItem
 * @package Antivirus\Model
 */
class FakeCoreModuleItem
{
    public function getName()
    {
        return 'core';
    }

    public function getFolder()
    {
        return \Setup::$PATH;
    }
}