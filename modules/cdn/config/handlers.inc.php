<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace CDN\Config;

use cdn\model\UrlRewriter;
use RS\Config\Loader;

class Handlers extends \RS\Event\HandlerAbstract
{
    /**
     * @var \CDN\Config\File
     */
    static $config;

    static function staticConstruct()
    {
        self::$config = Loader::byModule(__CLASS__);
    }


    function init()
    {
        if (in_array(\CDN\Config\File::CDN_ELEMENT_IMG, (array)self::$config['cdn_elements'])) {
            $this->bind('controller.beforewrap');
        }
        if (in_array(\CDN\Config\File::CDN_ELEMENT_CSS, (array)self::$config['cdn_elements'])) {
            $this->bind('getcss');
        }
        if (in_array(\CDN\Config\File::CDN_ELEMENT_JS, (array)self::$config['cdn_elements'])) {
            $this->bind('getjs');
        }
    }

    static public function isCdnEnabled()
    {
        return self::$config->enabled && self::$config->domain && trim(self::$config->domain) !== "";
    }


    /**
     * Обработка всего вывода
     *
     * @param array $params
     * @return array
     */
    public static function controllerBeforeWrap(array $params)
    {
        if(self::isCdnEnabled())
        {
            $params['body'] = UrlRewriter::processImages($params['body'], self::$config->domain);
        }

        return $params;
    }


    /**
     * Обработка путей к CSS файлам
     *
     * @param array $params
     * @return array
     */
    public static function getCss(array $params)
    {
        if(self::isCdnEnabled() && isset($params['css_array']))
        {
            foreach($params['css_array'] as &$one)
            {
                $one['file'] = UrlRewriter::processUrl($one['file'], self::$config->domain);
            }
        }

        return $params;
    }

    /**
     * Обработка путей к Javascript файлам
     *
     * @param array $params
     * @return array
     */
    public static function getJs(array $params)
    {
        if(self::isCdnEnabled() && isset($params['js_array']))
        {
            foreach($params['js_array'] as &$one)
            {
                $one['file'] = UrlRewriter::processUrl($one['file'], self::$config->domain);
            }
        }

        return $params;
    }



}

Handlers::staticConstruct();