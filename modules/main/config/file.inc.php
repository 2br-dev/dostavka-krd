<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/

namespace Main\Config;
use \RS\Orm\Type;

/**
* Конфигурационный файл модуля
* @ingroup Main
*/
class File extends \RS\Orm\ConfigObject
{
    function _init()
    {
        parent::_init();
        $this->getPropertyIterator()->append(array(
            t('Изображения'),
            'image_quality' => new Type\Integer(array(
                'description' => t('Качество генерируемых фото (от 0 до 100). 100 - самое лучшее.'),
            )),
            'watermark' => new Type\File(array(
                'maxLength' => '255',
                'max_file_size' => 10000000,
                'allow_file_types' => array('image/png'),
                'description' => t('Водяной знак (PNG)'),
                'storage' => array(\Setup::$ROOT, \Setup::$STORAGE_DIR.'/watermark/')
            )),
            'wmark_min_width' => new Type\Integer(array(
                'description' => t('Минимальная ширина изображения на которую будет установлен водяной знак'),
                'Attr' => array(array('size' => '4')),
            )),
            'wmark_min_height' => new Type\Integer(array(
                'description' => t('Минимальная высота изображения на которую будет установлен водяной знак'),
                'Attr' => array(array('size' => '4')),
            )),
            'wmark_pos_x' => new Type\Varchar(array(
                'maxLength' => '10',
                'description' => t('Позиция по горизонтали'),
                'Attr' => array(array('size' => '1')),
                'ListFromArray' => array(array('left' => t('Слева'), 'center' => t('По центру'), 'right' => t('Справа'))),
            )),
            'wmark_pos_y' => new Type\Varchar(array(
                'maxLength' => '10',
                'description' => t('Позиция по вертикали'),
                'Attr' => array(array('size' => '1')),
                'ListFromArray' => array(array('top' => t('Сверху'), 'middle' => t('По центру'), 'bottom' => t('Снизу'))),
            )),
            'wmark_opacity' => new Type\Integer(array(
                'description' => t('Процент непрозрачности водяного знака при наложении. от 1 до 100. 100 - водяной знак будет наложен как есть'),
                'Attr' => array(array('size' => '4')),
            )),
            'webp_generate_only' => new Type\Integer(array(
                'description' => 'Генерировать для сайта миниатюры только в формате WebP?',
                'checkboxView' => array(1, 0),
                'hint' => 'Доступно только на версии PHP не ниже 7.1.0<br>Для работы опции необходимо, чтобы PHP был собран с поддержкой WebP.<br>Рекомендуем удалить миниатюры изображений перед включением данной опции в модуле "Блок фотографий"',
            )),
            t('CSV импорт/экспорт'),
                'csv_charset' => new Type\Varchar(array(
                    'description' => t('Кодировка CSV файлов'),
                    'listFromArray' => array(array(
                        'utf-8' => 'UTF-8',
                        'windows-1251' => 'WINDOWS-1251'
                    ))
                )),
                'csv_delimiter' => new Type\Varchar(array(
                    'description' => t('Разделитель'),
                    'listFromArray' => array(array(
                        ';' => t('; (точка с запятой)'),
                        ',' => t(', (запятая)')
                    ))
                )),
                'csv_check_timeout' => new Type\Integer(array(
                    'description' => t('Использовать пошаговую загрузку?'),
                    'maxLength' => 1,
                    'default' => 1,
                    'CheckboxView' => array(1, 0),
                )),
                'csv_timeout' => new Type\Integer(array(
                    'description' => t('Время одного шага импорта'),
                    'maxLength' => 11,
                    'default' => 26,
                )),
            t('Геолокация'),
                'geo_ip_service' => new Type\Varchar(array(
                    'description' => t('Сервис для определения ближайшего филиала по IP'),
                    'list' => array(array('Main\Model\GeoIpApi', 'getGeoIpServicesName'))
                )),
                'dadata_token' => new Type\Varchar(array(
                    'description' => t('Ключ API от DaData.ru'),
                    'hint' => t('Зарегистрируйтесь и получите ключ на сайте DaData.ru'),
                    'template' => '%main%/form/config/dadata_token.tpl'
                )),
            t('Сервер событий'),
                'long_polling_can_enable' => new Type\Integer(array(
                    'description' => t('Разрешить включение сервера событий'),
                    'hint' => t('Сервер событий позволяет держать "долгое" соединение между браузером администратора и сервером и мгновенно доставлять события в браузер.')
                )),
                'long_polling_timeout_sec' => new Type\Integer(array(
                    'description' => t('Максимальное время соединения с сервером одного потока'),
                    'hint' => t('Сервер будет держать соединение с браузером заданное количество секунд, если не произойдет ни одно событие. После обрыва, соединение опять устанавливается.'),
                    'listFromArray' => array(array(
                        '5' => '5 секунд',
                        '10' => '10 секунд',
                        '20' => '20 секунд',
                        '25' => '25 секунд'
                    ))
                )),
                'long_polling_event_listen_interval_sec' => new Type\Integer(array(
                    'description' => t('Интервал запроса новых событий на сервере'),
                    'listFromArray' => array(array(
                        '1' => '1 секунда',
                        '2' => '2 секунды',
                        '3' => '3 секунды',
                        '4' => '4 секунды'
                    ))
                ))
        ));


        if(version_compare(phpversion(),'7.1.0') < 0 || !function_exists('imagewebp'))
            $this['__webp_generate_only']->setAttr(array(
                'disabled' => true,
            ));
    }

    /**
     * Возвращает значения свойств по-умолчанию
     *
     * @return array
     */
    public static function getDefaultValues()
    {
        $router = \RS\Router\Manager::obj();

        return parent::getDefaultValues() + array(
            'tools' => array(
                array(
                    'url' => $router->getAdminUrl('CreateLangFilesDialog', array(), 'main-lang'),
                    'title' => t('Создание языковых файлов'),
                    'description' => t('Создает в каждом модуле и теме оформления файлы локализации, для перевода на другие языки'),
                    'class' => 'crud-add crud-sm-dialog',
                ),
                array(
                    'url' => $router->getAdminUrl('DownloadLangFileArchive', array(), 'main-lang'),
                    'title' => t('Скачать архив с файлами для перевода'),
                    'description' => t('Возвращает в одном архиве все файлы для перевода, которые присутствуют в системе'),
                    'class' => 'crud-add crud-sm-dialog',
                ),
                array(
                    'url' => $router->getAdminUrl('ajaxRecalculatePositions', array(), 'main-widgets'),
                    'title' => t('Исправить позиции виджетов'),
                    'description' => t('Пересчитывает сортировочные индексы виджетов. Необходимо вызывать, если наблюдаются проблемы с сортировкой виджетов.'),
                ),
            )
        );
    }
}

