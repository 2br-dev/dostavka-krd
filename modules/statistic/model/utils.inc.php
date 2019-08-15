<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model;

use Shop\Model\Orm\Order;

class Utils
{

    protected static $query_cache = array(); //Кэш запросов


    /**
     * Возвращает значение из кэша или null
     *
     * @param string $cache_id - идентификатор кэша
     * @return mixed
     */
    protected static function getFromQueryCache($cache_id)
    {
        isset(self::$query_cache[$cache_id]) ? self::$query_cache[$cache_id] : null;
    }

    /**
     * Добавляет значени в кэш запросов и возвращает его
     *
     * @param string $cache_id - идентификатор кэша
     * @param mixed $value - значение
     * @return mixed
     */
    protected static function addToCache($cache_id, $value)
    {
        return self::$query_cache[$cache_id] = $value;
    }

    /**
     * Возвращает часть цвета из матрицы цветов (00, AE, FF)
     *
     * @return string
     */
    static function randomColorPart()
    {
        return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Возвращает функцию для группировки для колонки "col"
     *
     * @param string $group_type - тип группировки
     * @return string
     */
    static function getDateColumnByGroupType($group_type)
    {
        $col = "";
        switch($group_type){
            case "day":
                $col = "DATE_FORMAT(dateof, '%d.%m.%Y') as col";
                break;
            case "week":
                $col = "WEEK(dateof) as col, YEAR(dateof) as y";
                break;
            case "monthname":
                $col = "MONTHNAME(dateof) as col, YEAR(dateof) as y";
                break;
            case "year":
                $col = "YEAR(dateof) as col";
                break;
        }
        return $col;
    }

    /**
     * Возвращает случайным образом цвет
     *
     * @param int $seed - ядро для смены формулы случайных чисел
     * @return string
     */
    static function randomColor($seed = null)
    {
        if($seed !== null)
        {
            mt_srand($seed);
        }

        return
            self::randomColorPart() .
            self::randomColorPart() .
            self::randomColorPart();
    }

    /**
     * Изменяет запрос таким образом, чтобы получить понедельное пояснение из результатов запроса
     *
     * @param \RS\Orm\Request $sub_req - базовый подготовленный запрос
     * @param string $cost_column_name - название колонки для оси X
     * @return \RS\Orm\Request
     */
    static function addWeekRequestPrepare(\RS\Orm\Request $sub_req, $cost_column_name = 'totalcost')
    {
        //Отформатируем строки для понедельного отображения (Например 07.11.2017-14.11.2017)
        $req = \RS\Orm\Request::make()
                    ->select("CONCAT(
                                             DATE_FORMAT(STR_TO_DATE(CONCAT(sub.y, sub.col, ' Monday'), '%X%V %W'), '%d.%m.%Y'), 
                                             ' - ', 
                                             DATE_FORMAT(STR_TO_DATE(CONCAT(sub.y, sub.col, ' Monday'), '%X%V %W') + INTERVAL 7 DAY, '%d.%m.%Y')
                                       ) as col, $cost_column_name ")
                    ->from("(".$sub_req->toSql().")", 'sub');

        return $req;
    }

    /**
     * Изменяет запрос таким образом, чтобы получить помесячное пояснение из результатов запроса
     *
     * @param \RS\Orm\Request $sub_req - базовый подготовленный запрос
     * @param string $cost_column_name - название колонки для оси X
     * @return \RS\Orm\Request
     */
    static function addMonthRequestPrepare(\RS\Orm\Request $sub_req, $cost_column_name = 'totalcost')
    {
        //Отформатируем строки для понедельного отображения (Например 07.11.2017-14.11.2017)
        $req = \RS\Orm\Request::make()
            ->select("CONCAT(
                                            col, ' ', y 
                                       ) as col, $cost_column_name ")
            ->from("(".$sub_req->toSql().")", 'sub');

        return $req;
    }

    /**
     * Отсортировать массив по образцу.
     *
     * @param array $array массив для сортировки
     * @param array $keys_order массив содержащий желаемую последовательнос ключей
     * @return array отсортированный массив
     */
    static function sortArrayByArray(array $array, array $keys_order)
    {
        $intersected_keys = array_intersect($keys_order, array_keys($array));
        return array_merge(array_flip($intersected_keys), $array);
    }

    /**
     * Возвращат тело базового запроса для посчета
     *
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return \RS\Orm\Request
     */
    static function getBaseRequest($date_from, $date_to, $site_id = null)
    {
        $config = \RS\Config\Loader::byModule(__CLASS__);
        
        $req = \RS\Orm\Request::make();
        $req->from(new Order);
        $req->where(array( 
            'site_id' => $site_id ?: \RS\Site\Manager::getSiteId()
        ));
        $req->where("dateof >= '#date_from' AND dateof <= '#date_to'", array(
            'date_from' => $date_from,
            'date_to' => $date_to.' 23:59:59'
        ));
        
        if (!in_array(0, (array)$config['consider_orders_status'])) {
            $req->whereIn('status', (array)$config['consider_orders_status']);
        }
        
        return $req;
    }

    /**
     * Возвращат тело базового запроса для посчета с указанием направления сортировки
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param string $order - Направление сортировки
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return \RS\Orm\Request
     */
    static function getBaseRequestWithSortOrder($date_from, $date_to, $order, $site_id = null)
    {
        $req = self::getBaseRequest($date_from, $date_to, $site_id);
        $req->orderby("#order", array(
            'order' => $order
        ));
        return $req;
    }

    /**
     * Возвращает выручку(общую сумму) по заказам за определенный промежуток времени
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return integer
     */
    static function getOrdersSummaryCost($date_from, $date_to, $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))){
            $req = self::getBaseRequest($date_from, $date_to, $site_id);
            $req->select('SUM(totalcost) as summary');
            $row = $req->exec()->fetchRow();

            return self::addToCache($cache_id, (int)$row['summary']);
        }
        return $values;
    }

    /**
     * Возвращает выручку по заказам за определенный промежуток времени для построения синусоиды
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param string $group_type - тип группировки (day - по дням, week - неделям, monthname - месяцам, year - годам)
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return array
     */
    static function getOrdersSummaryCostWaveData($date_from, $date_to, $group_type = 'day', $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $req = self::getBaseRequestWithSortOrder($date_from, $date_to, 'dateof ASC', $site_id);
            $req->select("IFNULL(SUM(totalcost), 0) as totalCost, " . self::getDateColumnByGroupType($group_type));
            $req->groupby('col');

            switch ($group_type) {
                case 'week': //Неделя
                    $req = self::addWeekRequestPrepare($req, 'totalCost');
                    break;
                case 'monthname': //Месяц
                    $req = self::addMonthRequestPrepare($req, 'totalCost');
                    break;
            }

            return self::addToCache($cache_id, $req->exec()->fetchSelected('col', 'totalCost'));
        }
        return $values;
    }

    /**
     * Возвращает доход(общая сумма-цена за товары) по заказам за определенный промежуток времени
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return integer
     */
    static function getOrdersSummaryProfit($date_from, $date_to, $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $req = self::getBaseRequest($date_from, $date_to, $site_id);
            $req->select('SUM(profit) as summary');
            $row = $req->exec()->fetchRow();
            return self::addToCache($cache_id, (int)$row['summary']);
        }
        return $values;
    }

    /**
     * Возвращает доход(общая сумма-цена за товары) по заказам за определенный промежуток времени для построения синусоиды
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param string $group_type - тип группировки (day - по дням, week - неделям, monthname - месяцам, year - годам)
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return array
     */
    static function getOrdersSummaryProfitWaveData($date_from, $date_to, $group_type = 'day', $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $req = self::getBaseRequestWithSortOrder($date_from, $date_to, 'dateof ASC', $site_id);
            $req->select("IFNULL(SUM(profit), 0) as profit, ".self::getDateColumnByGroupType($group_type));
            $req->groupby('col');

            switch($group_type){
                case 'week': //Неделя
                    $req = self::addWeekRequestPrepare($req, 'profit');
                    break;
                case 'monthname': //Месяц
                    $req = self::addMonthRequestPrepare($req, 'profit');
                    break;
            }

            return self::addToCache($cache_id, $req->exec()->fetchSelected('col', 'profit'));
        }
        return $values;
    }

    /**
     * Возвращает количество повторных заказов за определенный промежуток времени
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return integer
     */
    static function getRepeatedOrdersCount($date_from, $date_to, $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $req = self::getBaseRequest($date_from, $date_to, $site_id);
            $req->select('COUNT(DISTINCT user_id) as distinct_count, COUNT(*) as total_count');
            $req->where("user_id > 0");
            $row = $req->exec()->fetchRow();

            $distinct_count = (int) $row['distinct_count'];
            $total_count    = (int) $row['total_count'];

            return self::addToCache($cache_id,  $total_count - $distinct_count);
        }
        return $values;
    }

    /**
     * Возвращает количество повторных заказов за определенный промежуток времени для построения синусоиды
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param string $group_type - тип группировки (day - по дням, week - неделям, monthname - месяцам, year - годам)
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return array
     */
    static function getRepeatedOrdersCountWaveData($date_from, $date_to, $group_type = 'day', $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $sub_req = \RS\Orm\Request::make()
                            ->select('COUNT(B.id) as CNT')
                            ->from(new \Shop\Model\Orm\Order(), 'B')
                            ->where(array(
                                "B.site_id" => $site_id ?: \RS\Site\Manager::getSiteId()
                            ))
                            ->where("B.dateof < A.dateof")
                            ->where('B.user_id = A.user_id');


            $req = self::getBaseRequestWithSortOrder($date_from, $date_to, 'dateof ASC', $site_id)
                        ->select('user_id, ('.$sub_req->toSql().') as ordersRepeatCount, '.self::getDateColumnByGroupType($group_type))
                        ->asAlias('A')
                        ->where("A.user_id > 0")
                        ->groupby("col");


            switch($group_type){
                case 'week'://Если группировка понедельная, то нужна своя обработка
                    $req = self::addWeekRequestPrepare($req, 'ordersRepeatCount');
                    break;
                case 'monthname'://Если группировка помесячная, то нужна своя обработка
                    $req = self::addMonthRequestPrepare($req, 'ordersRepeatCount');
                    break;
            }

            return self::addToCache($cache_id,  $req->exec()->fetchSelected('col', 'ordersRepeatCount'));
        }
        return $values;
    }

    /**
     * Возвращает количество повторных покупателей за определенный промежуток времени
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return integer
     */
    static function getRepeatedUsersCount($date_from, $date_to, $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $req = self::getBaseRequest($date_from, $date_to, $site_id);
            $req->select('user_id, COUNT(*) as total_count');
            $req->where("user_id > 0");
            $req->groupby('user_id');
            $req->having('total_count > 1');

            $cnt = \RS\Orm\Request::make()
                        ->select('COUNT(*) as cnt')
                        ->from('('.$req->toSql().')', 'sub')
                        ->exec()->getOneField('cnt', 0);

            return self::addToCache($cache_id, $cnt);
        }
        return $values;
    }

    /**
     * Возвращает количество заказов за определенный промежуток времени
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return int
     */
    static function getOrdersCount($date_from, $date_to, $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $req = self::getBaseRequest($date_from, $date_to, $site_id);
            return self::addToCache($cache_id, (int)$req->count());
        }
        return $values;
    }

    /**
     * Возвращает количество заказов за определенный промежуток времени для построения синусоиды
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param string $group_type - тип группировки (day - по дням, week - неделям, monthname - месяцам, year - годам)
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return array
     */
    static function getOrdersCountWaveData($date_from, $date_to, $group_type = 'day', $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $req = self::getBaseRequestWithSortOrder($date_from, $date_to, 'dateof ASC', $site_id);
            $req->select("COUNT(id) as ordersCount, ".self::getDateColumnByGroupType($group_type));
            $req->groupby('col');

            switch($group_type){
                case 'week'://Если группировка понедельная, то нужна своя обработка
                    $req = self::addWeekRequestPrepare($req, 'ordersCount');
                    break;
                case 'monthname'://Если группировка помесячная, то нужна своя обработка
                    $req = self::addMonthRequestPrepare($req, 'ordersCount');
                    break;
            }

            return self::addToCache($cache_id, $req->exec()->fetchSelected('col', 'ordersCount'));
        }
        return $values;
    }

    /**
     * Возвращает средний чек за определенный промежуток времени
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return float
     */
    public static function getOrderAverageCost($date_from, $date_to, $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $req = self::getBaseRequest($date_from, $date_to, $site_id);
            $req->select('AVG(totalcost) as average');
            $row = $req->exec()->fetchRow();
            return self::addToCache($cache_id,  round($row['average'], 2));
        }
        return $values;
    }

    /**
     * Возвращает LTV показатель заказов за определённый период (Среднее количество заказов * Средний чек)
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return float
     */
    public static function getOrderLTV($date_from, $date_to, $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $sub_req = self::getBaseRequest($date_from, $date_to, $site_id)
                            ->select("COUNT(*) as CNT, DATE_FORMAT(dateof, '%d.%m.%Y') as col")
                            ->groupby('col');

            $req = \RS\Orm\Request::make()
                        ->select('AVG(CNT) as average')
                        ->from('('.$sub_req->toSql().')', 'O');
            $row = $req->exec()->fetchRow();

            $order_average = self::getOrderAverageCost($date_from, $date_to, $site_id);

            return self::addToCache($cache_id, round($row['average'] * $order_average, 2));
        }
        return $values;
    }

    /**
     * Возвращает показатель LVT (ср. кол-во продаж * средний чек) за определенный промежуток времени
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return float
     */
    public static function getOrderLTVCount($date_from, $date_to, $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $req = self::getBaseRequest($date_from, $date_to, $site_id);
            $req->select('AVG(totalcost) as average');
            $row = $req->exec()->fetchRow();
            return self::addToCache($cache_id,  round($row['average'], 2));
        }
        return $values;
    }

    /**
     * Возвращает средний чек за определенный промежуток времени для построения синусоиды
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param string $group_type - тип группировки (day - по дням, week - неделям, monthname - месяцам, year - годам)
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return array
     */
    static function getOrderAverageWaveData($date_from, $date_to, $group_type = 'day', $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $req = self::getBaseRequestWithSortOrder($date_from, $date_to, 'dateof ASC', $site_id);
            $req->select("round(AVG(totalcost), 2) as avgCost, ".self::getDateColumnByGroupType($group_type));
            $req->groupby('col');

            switch($group_type){
                case 'week'://Если группировка понедельная, то нужна своя обработка
                    $req = self::addWeekRequestPrepare($req, 'avgCost');
                    break;
                case 'monthname'://Если группировка помесячная, то нужна своя обработка
                    $req = self::addMonthRequestPrepare($req, 'avgCost');
                    break;
            }

            return self::addToCache($cache_id,  $req->exec()->fetchSelected('col', 'avgCost'));
        }
        return $values;
    }

    /**
     * Возвращает LTV показатель за определенный промежуток времени для построения синусоиды
     *
     * @param string $date_from - Дата с
     * @param string $date_to - Дата по
     * @param string $group_type - тип группировки (day - по дням, week - неделям, monthname - месяцам, year - годам)
     * @param integer $site_id - id нужного сайта. Если нет, то по умолчанию текущий
     * @return array
     */
    static function getOrderLTVAverageCostWaveData($date_from, $date_to, $group_type = 'day', $site_id = null)
    {
        $cache_id = implode("", func_get_args());
        if (is_null($values = self::getFromQueryCache($cache_id))) {
            $sub_req = self::getBaseRequestWithSortOrder($date_from, $date_to, 'dateof ASC', $site_id);

            $order_average_count_arr = array();
            switch($group_type){
                case 'day'://Если группировка понедельная, то нужна своя обработка
                    $sub_req->select("COUNT(*) as CNT, DATE_FORMAT(dateof, '%d.%m.%Y %H') as sub_col")
                            ->groupby('sub_col');

                    $req = \RS\Orm\Request::make()
                        ->select("AVG(CNT) as CNT, DATE_FORMAT(STR_TO_DATE(O.sub_col, '%d.%m.%Y %H'), '%d.%m.%Y') as col")
                        ->from('('.$sub_req->toSql().')', 'O')
                        ->groupby('col');

                    $order_average_count_arr = $req->exec()->fetchSelected('col', 'CNT');
                    break;
                case 'week'://Если группировка понедельная, то нужна своя обработка
                    $sub_req1 = \RS\Orm\Request::make()
                                    ->select("COUNT(*) as CNT, DATE_FORMAT(dateof, '%Y-%m-%d 00:00:00') as myday")
                                    ->from(new Order())
                                    ->where(array(
                                        "site_id" => $site_id ?: \RS\Site\Manager::getSiteId()
                                    ))
                                    ->where("dateof >= '#date_from' AND dateof <= '#date_to'", array(
                                        'date_from' => $date_from,
                                        'date_to' => $date_to.' 23:59:59'
                                    ))->groupby('myday');
                    $suq_req2 = \RS\Orm\Request::make()
                                    ->select("AVG(O.CNT) as CNT, WEEK(myday) as col, YEAR(myday) as y")
                                    ->from('('.$sub_req1->toSql().')', 'O')
                                    ->groupby('col');
                    $order_average_count_arr = \RS\Orm\Request::make()
                                    ->select("CONCAT( DATE_FORMAT(STR_TO_DATE(CONCAT(sub.y, sub.col, ' Monday'), '%X%V %W'), '%d.%m.%Y'), ' - ', DATE_FORMAT(STR_TO_DATE(CONCAT(sub.y, sub.col, ' Monday'), '%X%V %W') + INTERVAL 7 DAY, '%d.%m.%Y') ) as col, CNT")
                                    ->from('('.$suq_req2->toSql().')', 'sub')
                                    ->exec()->fetchSelected('col', 'CNT');
                    break;
                case 'monthname'://Если группировка помесячная, то нужна своя обработка
                    $sub_req1 = \RS\Orm\Request::make()
                                ->select("COUNT(*) as CNT, DATE_FORMAT(dateof, '%Y-%m-%d 00:00:00') as myday")
                                ->from(new Order())
                                ->where(array(
                                    "site_id" => $site_id ?: \RS\Site\Manager::getSiteId()
                                ))
                                ->where("dateof >= '#date_from' AND dateof <= '#date_to'", array(
                                    'date_from' => $date_from,
                                    'date_to' => $date_to.' 23:59:59'
                                ))->groupby('myday');
                    $suq_req2 = \RS\Orm\Request::make()
                                ->select("AVG(O.CNT) as CNT, MONTHNAME(myday) as col, YEAR(myday) as y")
                                ->from('('.$sub_req1->toSql().')', 'O')
                                ->groupby('col');
                    $order_average_count_arr = \RS\Orm\Request::make()
                                ->select("CONCAT(sub.col, ' ', sub.y) as col, CNT")
                                ->from('('.$suq_req2->toSql().')', 'sub')
                                ->exec()->fetchSelected('col', 'CNT');
                    break;

                case 'year'://Если группировка помесячная, то нужна своя обработка
                    $sub_req1 = \RS\Orm\Request::make()
                        ->select("COUNT(*) as CNT, DATE_FORMAT(dateof, '%Y-%m-%d 00:00:00') as myday")
                        ->from(new Order())
                        ->where(array(
                            "site_id" => $site_id ?: \RS\Site\Manager::getSiteId()
                        ))
                        ->where("dateof >= '#date_from' AND dateof <= '#date_to'", array(
                            'date_from' => $date_from,
                            'date_to' => $date_to.' 23:59:59'
                        ))->groupby('myday');
                    $order_average_count_arr = \RS\Orm\Request::make()
                        ->select("YEAR(myday) as col, AVG(O.CNT) as CNT")
                        ->from('('.$sub_req1->toSql().')', 'O')
                        ->groupby('col')
                        ->exec()->fetchSelected('col', 'CNT');
                    break;
            }

            $arr = array();
            $order_average_arr = self::getOrderAverageWaveData($date_from, $date_to, $group_type, $site_id);

            if (!empty($order_average_arr)){
                foreach ($order_average_arr as $day=>$avgCost){
                    $arr[$day] = round($avgCost * $order_average_count_arr[$day], 2);
                }
            }

            //var_dump($req->toSql());
            //exit();

            return self::addToCache($cache_id,  $arr);
        }
        return $values;
    }


    /**
     * Возвращает количество прецентов на которое уменьшилось или увеличилось число с предыдущего до текущего
     *
     * @param float|int $previous - значения предыдущего периода
     * @param float|int $first - значения первого периоды
     *
     * @return integer
     */
    public static function getPercentageValue($previous, $first)
    {
        if ($first == $previous){
            return 0;
        }
        if ($previous == 0){
            if ($first > 0){
                return 100;
            }else{
                return -100;
            }
        }
        return round( ((($first) / $previous) -1) * 100);
    }

    /**
     * Возвращает минимальное значение
     *
     * @param array $data_arr - массив данных из базы
     * @param string $search_column_title - имя колонки в которой надо искать минимальное значение
     * @return float|integer
     */
    public static function getMinValueOfData($data_arr, $search_column_title)
    {
        $min = 100000000;
        foreach ($data_arr as $data){
            if ($data[$search_column_title] < $min){
                $min = $data[$search_column_title];
            }
        }
        return $min;
    }


    /**
     * Возвращает минимальное значение
     *
     * @param array $data_arr - массив данных из базы
     * @param string $search_column_title - имя колонки в которой надо искать минимальное значение
     * @return float|integer
     */
    public static function getMaxValueOfData($data_arr, $search_column_title)
    {
        $max = -100000000;
        foreach ($data_arr as $data){
            if ($data[$search_column_title] > $max){
                $max = $data[$search_column_title];
            }
        }
        return $max;
    }

    /**
     * Подменяет в тексте название месяца на правильное на русском языке
     *
     * @param string $month_text - текст с названием месяца
     * @return string
     */
    public static function prepareMonth($month_text)
    {
        $arr = array(
            'January' => t('Январь'),
            'February' => t('Февраль'),
            'March' => t('Март'),
            'April' => t('Апрель'),
            'May' => t('Май'),
            'June' => t('Июнь'),
            'Jule' => t('Июль'),
            'August' => t('Август'),
            'September' => t('Сентябрь'),
            'October' => t('Октябрь'),
            'November' => t('Ноябрь'),
            'December' => t('Декабрь')
        );
        return str_replace(array_keys($arr), array_values($arr), $month_text);
    }
}