<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace antivirus\model;


use RS\Helper\PersistentState;
use RS\Helper\PersistentStateHashStore;

abstract class ResumableTask
{
    /**
     * Уникальный идентификатор задачи вида Name.Space.ClassName
     *
     * @var mixed
     */
    protected $id;

    /**
     * Размер шага (сколько элементов должно быть обработано за один вызов метода execute())
     *
     * @var int
     */
    protected $step_size = 1000;

    /**
     * Текущая позиция
     *
     * @var int
     */
    protected $position = 0;

    /**
     * Общее число элементов
     *
     * @var int
     */
    protected $total_count;

    /**
     * Хранилище состояния
     *
     * @var PersistentState
     */
    protected $state;


    protected function __construct()
    {
        $this->id       = str_replace('\\', '.', get_class($this));

        $this->state    = new PersistentStateHashStore($this->id . '.');
        $this->loadState();
    }

    protected function loadState()
    {
        $this->position = (int) $this->state->position;
    }

    protected function saveState()
    {
        $this->state->position = (int) $this->position;
    }

    protected function lock()
    {
        $lock_file = \Setup::$PATH . \Setup::$STORAGE_DIR . '/locks/' . $this->id;
        file_put_contents($lock_file, date('Y-m-d H:i:s'));
    }

    protected function unlock()
    {
        $lock_file = \Setup::$PATH . \Setup::$STORAGE_DIR . '/locks/' . $this->id;
        if (file_exists($lock_file)) {
            unlink($lock_file);
        }
    }

    protected function isLocked()
    {
        $lock_file = \Setup::$PATH . \Setup::$STORAGE_DIR . '/locks/' . $this->id;
        return file_exists($lock_file);
    }

    public function log($msg)
    {
        if($msg == "") {
            Utils::logNewLine();
            return;
        }
        $ref = new \ReflectionClass($this);
        $out = "{$ref->getShortName()}: {$msg}";
        Utils::log($out);
    }

    public function execute()
    {
        if($this->isLocked())
        {
            $this->log(t('Задание \'%0\' уже выполняется из другого потока', $this->id));
            return;
        }

        $this->lock();

        $start_time                     = microtime(true);
        $total_count                    = $this->getTotalCount();
        $full_cycle_completed           = false;
        $worked_count_in_this_step      = 0;

        $this->log("");
        $this->log(t('Начинаем выполнять задание с позиции %0, всего: %1, шаг: %2', array($this->position, $total_count, $this->step_size)));

        // Выполняем работу до тех пор, пока количество обработанных элементов не станет равным размеру шага
        while($worked_count_in_this_step != $this->step_size)
        {
            $limit = $this->step_size - $worked_count_in_this_step;

            // Выполнение работы
            $worked_count = $this->doWork($this->position, $limit);

            // Если doWork вернул 0, это означает что мы достигли конца задачи раньше положенного
            if($worked_count == 0)
            {
                $full_cycle_completed = true;
                break;
            }

            if($worked_count > $limit)
            {
                throw new \Exception(t('Превышен лимит выполнения'));
            }


            $worked_count_in_this_step += $worked_count;
            $this->position += $worked_count;

            if($this->position == $total_count)
            {
                $full_cycle_completed = true;
                break;
            }
        }

        $exec_time = round(microtime(true) - $start_time, 3);
        $this->log(t("Раунд завершен. Время выполнения раунда: %0 сек.", array($exec_time)));

        // Если завершен полный цикл
        if($full_cycle_completed)
        {
            $this->position = 0;
            $this->onFullCycleCompleted();
            $this->log(t('Полный цикл завершен'));
        }
        $this->saveState();

        $this->unlock();
    }


    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Метод выполняет какую-то полезную работу
     *
     * @param int $offset
     * @param int $limit
     * @return int количество обработаных элементов. 0 в случае если задачу необходимо прервать
     *
     */
    abstract protected function doWork($offset, $limit);


    /**
     * Метод должен возвращать общее число элементов
     *
     * @return int общее число элементов
     */
    abstract protected function getTotalCount();


    /**
     * Вызывается, когда задание полностью завершено
     *
     * @return mixed
     */
    abstract protected function onFullCycleCompleted();

}