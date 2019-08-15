<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Antivirus\Model;

use Antivirus\Model\Orm\Event;
use Antivirus\Model\Orm\RequestCount;
use RS\Config\Loader;
use RS\Http\Request;
use RS\Orm\Request as OrmRequest;
use RS\Module\AbstractModel\BaseModel;

class MaliciousRequestDetector extends BaseModel
{
    /**
     * @var \Antivirus\Config\File
     */
    private $config;

    /**
     * Массив регулярных выражения для поиска вредоносных GET/POST параметров
     *
     * @var array
     */
    private $patterns_for_params = array(
        'eval\(',
        'UNION.*SELECT',
        '\(null\)', 'base64_',
        '\/pingserver',
        '\/config\.',
        '\/wwwroot',
        '\/makefile',
        'crossdomain\.',
        'proc\/self\/environ',
        'etc\/passwd',
        '\/https\:',
        '\/http\:',
        '\/ftp\:',
        '\/cgi\/',
        '\.cgi',
        '\.sql',
        '\.ini$',
        '\.dll',
        '\.asp',
        '\.jsp',
        '\/\.bash',
        '\/\.git',
        '\/\.svn',
        '\/\.tar',
        '\/\=',
        //'\.\.\.',
        '\+\+\+',
        //'\:\/\/',
        '\/&&',
        '\/Nt\.',
        '\;Nt\.',
        '\=Nt\.',
        '\,Nt\.',
        '\.exec\(',
        '\)\.html\(',
        '\{x\.html\(',
        '\(function\(',
        '\.\.\/',
        'loopback',
        //'\%0A',
        '\%0D',
        '\%00',
        '\%2e\%2e',
        'input_file',
        'execute',
        'mosconfig',
        'path\=\.',
        'mod\=\.',
        'alert\(',
    );

    /**
     * Массив регулярных выражения для поиска в User-Agent
     *
     * @var array
     */
    private $patterns_for_user_agent = array('binlar', 'casper', 'cmswor', 'diavol', 'finder', 'flicky', 'nutch', 'planet', 'purebot', 'pycurl', 'skygrid', 'sucker', 'turnit', 'vikspi', 'zmeu');

    static private $instance;

    static public function getInstance()
    {
        if(self::$instance === null)
        {
            self::$instance = new self;
        }
        return self::$instance;
    }

    function __construct()
    {
        $this->config = Loader::byModule($this);
    }


    /**
     * Возвращает количество вредоносных запросов, при котором происходит блокировка
     *
     * @return int
     */
    private function getTriggerRequestCount()
    {
        return (int) $this->config->proactive_trigger_malicious_request_count;
    }

    /**
     * Возвращает список параметров, которые не следует проверять на вредоносные шаблоны
     *
     * @return array
     */
    private function getExcludedParams()
    {
        return array(
            'COOKIE' => array(
                'user_source'
            )
        );
    }


    /**
     * Вызвается при выполнении любого GET/POST запроса
     */
    public function onRequest()
    {

        if(Utils::isRequestTrusted($this->config))
        {
            return;
        }

        $server_array   = Request::commonInstance()->getSource(SERVER);
        $source_names   = array(/*"GET", "POST",*/ "COOKIE");
        $info           = new MaliciousRequestInfo();

        $excluded_params = $this->getExcludedParams();

        $detected = false;

        // Проверка параметров
        foreach($source_names as $source_name)
        {
            $source = Request::commonInstance()->getSource(constant($source_name));

            foreach($source as $key => $value)
            {
                if (isset($excluded_params[$source_name])
                    && in_array($key, $excluded_params[$source_name])) {
                    continue;
                }

                if($this->detectMaliciousParam($key, $value, $info))
                {
                    $info->source_type  = $source_name;

                    $detected = true;
                    break 2;
                }
            }
        }
        
        // Проверка REQUEST_URI
        if($this->detectMaliciousRequestURI($_SERVER['REQUEST_URI'], $info))
        {
            $info->source_type  = 'REQUEST-URI';
            $detected           = true;
        }

        // Проверка user agent
        $user_agent = isset($server_array['HTTP_USER_AGENT']) ? $server_array['HTTP_USER_AGENT'] : '';
        if ($user_agent) {
            foreach($this->patterns_for_user_agent as $pattern)
            {
                if(preg_match("/{$pattern}/i", $user_agent))
                {
                    $info->source_type  = 'USER-AGENT';
                    $info->pattern      = $pattern;
                    $detected           = true;
                }
            }
        }

        // Обнаружен вредоносный запрос
        if($detected)
        {
            $info->ip           = $server_array['REMOTE_ADDR'];
            $info->url          = Request::commonInstance()->getSelfUrl();
            $info->get_array    = Request::commonInstance()->getSource(GET);
            $info->post_array   = Request::commonInstance()->getSource(POST);
            $info->cookie_array = Request::commonInstance()->getSource(COOKIE);
            $info->user_agent   = $user_agent;

            $this->onMaliciousRequestDetected($info);
        }
    }


    /**
     * Метод вызывается по крону каждую минуту
     */
    public function onCron()
    {
        // Сброс счетчиков (malicious_count) на ноль

        $reset_interval = 120; // секунды

        \RS\Orm\Request::make()
            ->update(new RequestCount())
            ->where('`last_time` < ' . (time() - $reset_interval) * 1000)
            ->set(array('malicious_count' => 0))
            ->exec();
    }

    /**
     * Проверить отдельно взятый параметр на вредоносность
     *
     * @param $key
     * @param $value
     * @param MaliciousRequestInfo $info объект информации об угрозе, для его заполнения
     * @return bool true - если параметр вредоносен
     */
    private function detectMaliciousParam($key, $value, MaliciousRequestInfo $info)
    {
        if(is_array($value))
        {
            $value = http_build_query($value);
        }

        foreach($this->patterns_for_params as $pattern)
        {
            $detected_1 = preg_match("/{$pattern}/i", $key);
            $detected_2 = preg_match("/{$pattern}/i", $value);

            if($detected_1 || $detected_2)
            {
                $info->pattern      = $pattern;
                $info->param_name   = $key;
                $info->param_value  = $value;

                return true;
            }
        }
        return false;
    }


    private function detectMaliciousRequestURI($uri, MaliciousRequestInfo $info)
    {
        foreach($this->patterns_for_params as $pattern)
        {
            $detected = preg_match("/{$pattern}/i", $uri);

            if($detected)
            {
                $info->pattern = $pattern;
                return true;
            }
        }
        return false;
    }

    /**
     * Вызывается, когда обнаружен вредоносный запрос
     *
     * @param MaliciousRequestInfo $info
     * @throws \RS\Db\Exception
     * @throws \RS\Orm\Exception
     */
    private function onMaliciousRequestDetected(MaliciousRequestInfo $info)
    {

        $obj    = new RequestCount();
        $trigger_request_count = $this->getTriggerRequestCount();

        // текущее время в миллисекундах
        $milli_time = round(microtime(true) * 1000);

        // Выполнение запроса на вставку или обновление счетчика вредоносных запросов
        $sql = "INSERT INTO {$obj->_getTable()} (`ip`,`last_time`,`malicious_count`)
                VALUES ('{$info->ip}', {$milli_time}, 0)
                ON DUPLICATE KEY UPDATE
                  `malicious_count` = `malicious_count`+1,
                  `last_time` = {$milli_time}";

        \RS\Db\Adapter::SQLExec($sql);

        // Вставка информации об угрозе в таблицу events
        $event              = new Event();
        $event['dateof']    = date('Y-m-d H:i:s');
        $event['component'] = Event::COMPONENT_PROACTIVE;
        $event['type']      = Event::TYPE_INFO;
        $event->setDetails($info);

        //Исключаем отображение атак в виджете в демо-режиме
        if (defined(\Setup::$SECRET_SALT.'_demo')) {
            $event['viewed'] = 1;
        }
        
        $event->insert();

        $obj = OrmRequest::make()->from($obj)->where(array('ip'=>$info->ip))->object();

        // Если превышен лимит на количество вредоносных запросов
        if((int)$obj['malicious_count'] >= $trigger_request_count)
        {
            $this->onMaliciousRequestCountLimitExceeded($info);

            // Обнуление счетчика запросов
            $obj['malicious_count'] = 0;
            $obj->update();

        }
    }

    /**
     * Вызывается, когда превышен лимит на количество вредоносных запросов.
     *
     * @param MaliciousRequestInfo $info
     */
    public function onMaliciousRequestCountLimitExceeded(MaliciousRequestInfo $info)
    {
        // Вставка записи об успешном отражении атаки в таблицу events
        $event              = new Event();
        $event['dateof']    = date('Y-m-d H:i:s');
        $event['component'] = Event::COMPONENT_PROACTIVE;
        $event['type']      = Event::TYPE_PROBLEM;
        $event->setDetails($info);
        
        //Исключаем отображение атак в виджете в демо-режиме
        if (defined(\Setup::$SECRET_SALT.'_demo')) {
            $event['viewed'] = 1;
        }
        
        $event->insert();

        // Если включено "Автоматически блокировать вредоносные запросы"
        if($this->config->proactive_auto_block)
        {
            // Блокировка
            $duration           = (int)$this->config->proactive_block_duration;

            $blocked            = new \Main\Model\Orm\BlockedIp();
            $blocked['ip']      = $info->ip;
            $blocked['expire']  = $duration ? date('Y-m-d H:i:s', time() + $duration) : null;
            $blocked['comment'] = t('Обнаружен вредносный параметр по шаблону %0', array($info->pattern));
            $blocked->insert();

            // Помечаем событие как "FIXED"
            $event['type']      = Event::TYPE_FIXED;
            $event->update();
        }

    }

}