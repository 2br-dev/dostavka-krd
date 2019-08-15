<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Antivirus\Model;


/**
 * Класс описывает объект, содержащий детали атаки частоными запросами
 *
 * Class RequestCountAttackInfo
 * @package Antivirus\Model
 */
class RequestCountAttackInfo
{
    /**
     * IP-адрес, с которого произведена атака
     *
     * @var string
     */
    public $ip;

    /**
     * Относительный URL запроса
     *
     * @var string
     */
    public $url;

}