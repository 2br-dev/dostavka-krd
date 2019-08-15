<?php
/**
 * Этот файл должен быть поставлен в крон для запуска ежеминутно
 */

use RS\Cron\Manager as CronManager;
use RS\File\Tools as FileTools;
use RS\HashStore\Api as HashStoreApi;

$readyscript_root = realpath(__DIR__ . '/../../');

//Подключаем файл с локальными настройками, если таковой существует.
if (file_exists('_local_settings.php')) {
    include_once('_local_settings.php');
}

require $readyscript_root . '/setup.inc.php';

//Актуально для облачной версии ReadyScript
if (defined('ACCOUNT_EXPIRED')) {
    die(t('Аккаунт заблокирован'));
}

class CronEventRiser
{
    const LOCK_FILE = '/storage/locks/cron';
    const ERROR_LOG_FILE = '/cron_errors.log';

    private $current_time;
    private $last_execution_timestamp;
    private $errors;

    public function __construct()
    {
        $this->current_time = time();
        $this->last_execution_timestamp = HashStoreApi::get(CronManager::LAST_TIME_KEY, $this->current_time - 60);

        if ($this->current_time - $this->last_execution_timestamp > 1440 * 60) {
            //Разрыв от предыдущего запуска не может превышать 1 суток
            $this->last_execution_timestamp = $this->current_time - 1440 * 60;
        }
    }

    /**
     * Запускает планировщик
     *
     * @return void
     * @throws Throwable
     */
    public function run()
    {
        if (!\Setup::$CRON_ENABLE) {
            echo t('Cron отключен в настройках системы (\Setup::$CRON_ENABLE)');
            return;
        }

        if ($this->isLocked()) {
            echo 'Cron locked';
            return;
        }

        // Устанавливаем блокировку
        $this->errors = '';
        $this->lock();

        // Сохраняем время начала выполнения
        RS\HashStore\Api::set(CronManager::LAST_TIME_KEY, (string)$this->current_time);

        // устанавливаем обработчин небросаемых ошибок
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            $this->errors .= date('[d.m.Y H:i:s] ') . self::handbookErrnoStr($errno) . ": $errstr in $errfile on line $errline\n\n";
            return false;
        });

        try {
            RS\Event\Manager::fire('cron', array(
                'last_time' => $this->last_execution_timestamp,
                'current_time' => $this->current_time,
                'minutes' => $this->getArrayOfMinutesFromPeriod($this->last_execution_timestamp, $this->current_time)
            ));
        } catch (\Throwable $e) {
            $this->errors .= date('[d.m.Y H:i:s]') . "\n" . $e->__toString();
            $this->unlock();

            throw $e;
        }
        // Убираем блокировку
        $this->unlock();

        echo 'complete';
    }

    /**
     * Создает файл блокировки, препятствующий одновременному запуску двух планировщиков
     *
     * @return void
     */
    private function lock()
    {
        $lock_file = \Setup::$PATH . self::LOCK_FILE;
        FileTools::makePath($lock_file, true);
        file_put_contents($lock_file, date('Y-m-d H:i:s'));
    }

    /**
     * Улаляет файл блокировки, записывает ошибки в лог-файл
     *
     * @return void
     */
    private function unlock()
    {
        $lock_file = \Setup::$PATH . self::LOCK_FILE;
        unlink($lock_file);

        if (!empty($this->errors)) {
            $error_log_file = \Setup::$PATH . \Setup::$LOGS_DIR . self::ERROR_LOG_FILE;
            FileTools::makePath($error_log_file, true);
            file_put_contents($error_log_file, $this->errors);
        }
    }

    /**
     * Возвращает true, если планировщик уже работает в данное время
     *
     * @return bool
     */
    private function isLocked()
    {
        $lock_file = \Setup::$PATH . self::LOCK_FILE;

        if (file_exists($lock_file)) {
            // Если файл блокировки слишком давно не перезаписовался, то удаляем его
            if (time() > filemtime($lock_file) + CronManager::LOCK_EXPIRE_INTERVAL) {
                @unlink($lock_file);
            }
        }

        return file_exists($lock_file);
    }


    /**
     * Возвращает массив с перечислением минут прошедших от предыдущего запуска
     *
     * @param integer $start_time
     * @param integer $end_time
     * @return array
     */
    private function getArrayOfMinutesFromPeriod($start_time, $end_time)
    {
        $start_time_rounded = strtotime(date('Y-m-d H:i', $start_time));

        $arr = array();

        $time = $start_time_rounded;

        while ($time < $end_time) {
            $time += 60;

            $arr[] = $this->getNumberOfMinute($time);
        }

        return $arr;
    }

    /**
     * Возвращает номер минуты относительно 0 часов для заданного времени
     *
     * @param integer $time
     * @return integer
     */
    private function getNumberOfMinute($time)
    {
        return date('H', $time) * 60 + date('i', $time);
    }

    /**
     * Возвращает тип ошибки текстом
     *
     * @param int $key - код уровня ошибки
     * @return string|int
     */
    private static function handbookErrnoStr(int $key)
    {
        $values = [
            1 => 'Error',
            2 => 'Warning',
            4 => 'Parse',
            8 => 'Notice',
            16 => 'Core error',
            32 => 'Core warning',
            128 => 'Compile error',
            256 => 'Compile warning',
            512 => 'User error',
            1024 => 'User warning',
        ];

        return $values[$key] ?? $key;
    }
}

$cronEventRiser = new CronEventRiser();
$cronEventRiser->run();
