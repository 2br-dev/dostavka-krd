<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model;

/**
 * Класс содержит функции логгирования
 */
class Log
{
    const DEFAULT_LOG = 'exchange/exchange.log';// Лог-файл по умолчанию
    const LIFE_TIME = 3600;                     // Время жизни лог-файла в секундах

    static private $enable = true;              // Если true, то логирование включено
    static private $indent = 0;                 // Текущий отступ в лог-файле (количество символов \t перед строкой)
    static private $logLines = array();         // Записи в лог за текущую сессию

    /**
     * Включает/выключает логирование
     *
     * @param bool $bool - значение
     * @return void
     */
    static public function setEnable($bool)
    {
        self::$enable = $bool;
    }

    /**
     * Запись строки в log-файл
     *
     * @param string $text - Строка для записи
     * @param string|bool $log_file - Имя лог-файла (необязательно)
     * @return void
     */
    static public function w($text, $log_file = false)
    {
        if (!self::$enable) return;
        if (!$log_file) $log_file = self::DEFAULT_LOG;
        $indent = str_repeat("\t", self::$indent);
        $str = date("[Y.m.d H:i:s] ") . $indent . $text . "\n";
        $file_path = \Setup::$ROOT . \Setup::$STORAGE_DIR . DS . $log_file;
        if (file_exists($file_path) && filemtime($file_path) < time() - self::LIFE_TIME) {
            @unlink($file_path);
        }
        file_put_contents($file_path, $str, FILE_APPEND | LOCK_EX);
        self::$logLines[] = $str;
    }

    /**
     * Очистить лог-файл
     *
     * @param string|bool $log_file Имя лог-файла (необязательно)
     * @return void
     */
    static public function clear($log_file = false)
    {
        if (!$log_file) $log_file = self::DEFAULT_LOG;
        @unlink(\Setup::$ROOT . \Setup::$STORAGE_DIR . DS . $log_file);
    }

    /**
     * Увеличить отступ в лог файле для последующих записей
     *
     * @return void
     */
    static public function indentInc()
    {
        self::$indent++;
    }

    /**
     * Уменьшить отступ в лог файле для последующих записей
     *
     * @return void
     */
    static public function indentDec()
    {
        if (self::$indent <= 0) return;
        self::$indent--;
    }

    /**
     * Получить массив записей в лог файл за текущую сессию
     *
     * @return array
     */
    static public function getLogLines()
    {
        return self::$logLines;
    }

    /**
     * Получить текст записей в лог-файл за текущую сессию
     *
     * @return string
     */
    static public function getLog()
    {
        return join("", self::getLogLines());
    }

    /**
     * Представить массив в виде [key=val, key2=val2]
     *
     * @param array $arr
     * @return string
     */
    static public function arr2str($arr)
    {
        $lines = array();
        foreach ($arr as $key => $val) {
            $lines[] = "{$key}={$val}";
        }
        return '[' . join(', ', $lines) . ']';
    }
}
