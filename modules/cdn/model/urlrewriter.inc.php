<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Cdn\Model;


use CDN\Config\File;
use RS\Config\Loader;

class UrlRewriter
{

    /**
     * Замена ссылок на картинки на CDN-домен
     *
     * @param string $html
     * @param string $domain
     * @return string
     * @throws \RS\Exception
     */
    static public function processImages($html, $domain)
    {
        if (\RS\Module\Manager::staticModuleEnabled('kaptcha')) {
            $captcha_url = \RS\Router\Manager::obj()->getUrl('kaptcha');
        } else {
            $captcha_url = false;
        }

        $callback = function($matches)use($domain, $captcha_url){
            $url        = $matches[1];
            if ($captcha_url && strpos($url, $captcha_url) == 0) {
                return $matches[0];
            }

            $result_url = self::processUrl($url, $domain);
            return str_replace($url, $result_url, $matches[0]);
        };

        $out = preg_replace_callback('#<img\s+?src\s*=\s*[\'"]([^\'"]+?)[\'"].*?>#i', $callback, $html);
        return $out;
    }

    /**
     * Преобразование любой ссылки (относительной или абсолютной) в сслыку на CDN домен
     *
     * @param $url
     * @param $domain
     * @return string
     */
    static public function processUrl($url, $domain)
    {
        $path_info  = parse_url($url);

        // Если относительный путь, либо хост совпадает с текущим
        if(!isset($path_info['host']) || $path_info['host'] == \Setup::$DOMAIN)
        {
            $path_info['scheme'] = "*";                 // вместо схемы два слеша в начале адреса
            $path_info['host'] = $domain;
            $result_url = self::buildUrl($path_info);
        }
        else
        {
            // Оставляем Url без изменений
            $result_url = $url;
        }
        return $result_url;
    }

    /**
     * Функция, обратная parse_url();
     * Собирает URL из частей
     *
     * @param $parse_url
     * @return string
     */
    static private function buildUrl($parse_url)
    {
        return
            ((isset($parse_url['scheme'])) ? ($parse_url['scheme'] == '*' ? '//' : $parse_url['scheme']. '://') : '')
            .((isset($parse_url['user'])) ? $parse_url['user'] .((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') .'@' : '')
            .((isset($parse_url['host'])) ? $parse_url['host'] : '')
            .((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
            .((isset($parse_url['path'])) ? $parse_url['path'] : '')
            .((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
            .((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
            ;
    }

}