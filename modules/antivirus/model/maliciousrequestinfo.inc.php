<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Antivirus\Model;

/**
 * Класс описывает объект, содержащий детали атаки с помощью вредноносного запроса
 *
 * Class MaliciousRequestInfo
 * @package Antivirus\Model
 */
class MaliciousRequestInfo
{
    /**
     * IP-адрес, с которого пришел вредоносный запрос
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

    /**
     * Где обнаружен вредоносный парметр: GET|POST|COOKIE|USER-AGENT
     *
     * @var string
     */
    public $source_type;

    /**
     * Имя вредоносного параметра
     *
     * @var string
     */
    public $param_name;

    /**
     * Значаение вредоносного параметра
     *
     * @var string
     */
    public $param_value;

    /**
     * Регулярное выражение, по которому обнаружена вредоносность параметра/user-agent
     *
     * @var string
     */
    public $pattern;

    /**
     * Копия массива $_GET
     *
     * @var array
     */
    public $get_array;

    /**
     * Копия массива $_POST
     *
     * @var array
     */
    public $post_array;

    /**
     * Копия массива $_COOKIE
     *
     * @var array
     */
    public $cookie_array;

    /**
     * Заголовок запроса User-Agent
     *
     * @var string
     */
    public $user_agent;

}