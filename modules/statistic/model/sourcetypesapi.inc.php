<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Statistic\Model;
 
/**
* API функции для работы с типами источников
*/
class SourceTypesApi extends \RS\Module\AbstractModel\EntityList
{
    public static
            $source_types_cache_by_host = array(); //список подгруженных источников по хосту

    protected
        $sources_limit = 100;

    function __construct()
    {
        parent::__construct(new Orm\SourceType(),array(
            'name_field' => 'title',
            'id_field' => 'id',
            'sort_field' => 'sortn',
            'multisite' => true,
            'defaultOrder' => 'sortn DESC'
        ));
    }

    /**
     * Сортирует список типов источников, чтобы первыми были те у которых нет параметров
     *
     * @param Orm\SourceType $a - первый тип источника
     * @param Orm\SourceType $b - второй тип источника
     *
     * @return integer
     */
    private static function sortTypes($a, $b)
    {
        if (!empty($a['params_arr']) == !empty($b['params_arr'])) {
            return 0;
        }
        return (!empty($a['params_arr'])) ? -1 : 1;
    }

    /**
     * Возвращает список типов источников подходящих под хост адреса
     *
     * @param string $host - текущий хост
     * @return array
     */
    public static function getTypesListByHost($host)
    {
        if (!isset(self::$source_types_cache_by_host[$host])){
            $_this = new self();
            self::$source_types_cache_by_host[$host] =
                $_this->setFilter(array(
                    'referer_site' => $host
                ))->setOrder('sortn DESC')
                ->getList();

            uasort(self::$source_types_cache_by_host[$host], array('self', 'sortTypes'));
        }

        return self::$source_types_cache_by_host[$host];
    }

    /**
     * Возвращает true, если были найдены паметры типа источника в пареметрах запроса
     *
     * @param Orm\SourceType $source_type - тип источника
     * @param array $params - параметры запроса
     *
     * @return bool
     */
    private function isHaveTypeParamsInSource(Orm\SourceType $source_type, $params)
    {
        $found = false;
        foreach ($source_type['params_arr'] as $key=>$val){
            if (isset($params[$key]) && ($params[$key] == $val)){
                $found = true;
            }else{
                $found = false;
                break;
            }
        }
        return $found;
    }

    /**
     * Устанавливает тип источника к источнику ищя по правилам
     *
     * @param Orm\Source $source - источник
     */
    function setSourceTypeToSource(Orm\Source $source)
    {
        //Если есть referer, то смотрим что можно по нему достать
        if (!empty($source['referer_site'])){
            $types = self::getTypesListByHost($source['referer_site']);

            if (!empty($types)){
                foreach ($types as $source_type){
                    /**
                     * @var \Statistic\Model\Orm\SourceType $source_type
                     */
                    if (!empty($source_type['params_arr'])){ //Если есть доп. параметры в посещённой странице, смотрим их наличие
                        parse_str(parse_url($source['landing_page'], PHP_URL_QUERY), $referer_params);

                        if (!empty($referer_params)){ //Есть параметры

                            //Посмотрим совпадает ли всё
                            if ($this->isHaveTypeParamsInSource($source_type, $referer_params)){
                                $source['source_type'] = $source_type['id'];
                                break;
                            }
                        }else{ //Тогда смотрим параметры из самого источника
                            $referer_params = array(); //Подстрахуемся
                            parse_str(parse_url($source['referer_source'], PHP_URL_QUERY), $referer_params);

                            if (!empty($referer_params) && $this->isHaveTypeParamsInSource($source_type, $referer_params)){
                                $source['source_type'] = $source_type['id'];
                                break;
                            }
                        }
                    }else{
                        $source['source_type'] = $source_type['id'];
                        break;
                    }
                }
            }
        }
    }

    /**
     * Обновляет типы источников в источниках
     *
     * @throws \RS\Orm\Exception
     */
    function updateSourceTypesInAllSources()
    {
        $q = \RS\Orm\Request::make()
                    ->from(new Orm\Source())
                    ->where(array(
                        'site_id' => \RS\Site\Manager::getSiteId()
                    ))
                    ->limit($this->sources_limit);

        $offset = 0;

        while($sources = $q->offset($offset)->objects()){
            foreach ($sources as $source){
                /**
                 * @var Orm\Source $source
                 */
                $source['source_type'] = 0;
                $this->setSourceTypeToSource($source);
                $source->update();
            }
            $offset += $this->sources_limit;
        }
    }
}