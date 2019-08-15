<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Feedback\Model\Orm;

use RS\Application\Auth as AppAuth;
use RS\Captcha\Manager as CaptchaManager;
use RS\Config\Loader as ConfigLoader;
use RS\Orm\OrmObject;
use RS\Orm\Request as OrmRequest;
use RS\Orm\Type;

/**
 * Класс ORM-объектов "Формы отправки".
 * Наследуется от объекта \RS\Orm\OrmObject, у которого объявлено свойство id
 */
class FormItem extends OrmObject
{
    protected static $table = 'connect_form'; //Имя таблицы в БД

    private $hvalues = array();  //Скрытые дополнительные поля
    private $values = array();  //Массив с значениями для существующих полей

    private $fields_cache;

    /**
     * Инициализирует свойства ORM объекта
     *
     * @return void
     */
    function _init()
    {
        parent::_init()->append(array(
            'site_id' => new Type\CurrentSite(), //Создаем поле, которое будет содержать id текущего сайта
            'title' => new Type\Varchar(array(
                'maxLength' => '150',
                'checker' => array('chkEmpty', t('Необходимо указать Название')),
                'description' => t('Название')
            )),
            'sortn' => new Type\Integer(array(
                'description' => t('Сортировочный индекс'),
                'maxLength' => '11',
                'visible' => false,
            )),
            'email' => new Type\Varchar(array(
                'maxLength' => '250',
                'description' => t('Email получения писем'),
                'hint' => t('Укажите E-mail для отправки. Или несколько через запятую. Пример ivan@mail.ru. Если оставить поле пустым, то письмо будет отправлено на Email администратора'),
            )),
            'subject' => new Type\Varchar(array(
                'maxLength' => '255',
                'description' => t('Заголовок письма'),
                'hint' => t('Будет подставлено в subject письма'),
                'default' => t('Получение письма из формы')
            )),
            'template' => new Type\Template(array(
                'maxLength' => '255',
                'description' => t('Путь к шаблону письма'),
                'hint' => t('Шаблон, который будет использоваться для отправки на E-mail'),
                'default' => '%feedback%/mail/default.tpl'
            )),
            'successMessage' => new Type\Varchar(array(
                'description' => t('Сообщение об успешной отправке формы')
            )),
            'use_captcha' => new Type\Integer(array(
                'maxLength' => 1,
                'description' => t('Использовать каптчу'),
                'checkboxView' => array(1, 0),
                'hint' => t('Каптча используется, если пользователь не авторизован')
            )),
            'use_csrf_protection' => new Type\Integer(array(
                'description' => t('Использовать CSRF защиту'),
                'hint' => t('Защищает от самых примитивных спам-роботов. Доступно в новых версиях шаблонов.'),
                'checkboxView' => array(1, 0)
            ))
        ));
    }


    /**
     * Передаёт в форму массив с значениями для подстановки в поля
     *
     * @param array $valueArray - массив с значениями для подстановки в поля
     * @return void
     */
    function setValues($valueArray = array())
    {
        $this->values = $valueArray;
    }

    /**
     * Возвращает значение поля по его ключу
     *
     * @param string $key - ключ массива
     * @return string
     */
    function getValue($key)
    {
        return (isset($this->values[$key])) ? $this->values[$key] : null;
    }

    /**
     * Передаёт в форму массив с дополнительными полями для отправки
     *
     * @param array $hValueArray - массив со значениями для скрытой отправки дополнительных полей
     * @return void
     */
    function setHiddenValues($hValueArray = array())
    {
        $this->hvalues = $hValueArray;
    }

    /**
     * Возвращает массив с дополнительными полями-значениями
     *
     * @return array
     */
    function getHiddenValues()
    {
        return $this->hvalues;
    }

    /**
     * Возвращает поля для формы в виде ORM объектов
     *
     * @param bool $cache - использовать кэш
     * @return \Feedback\Model\Orm\FormFieldItem|false
     * @throws \RS\Orm\Exception
     */
    function getFields($cache = true)
    {
        if (!$this['id']) {
            return false;
        }

        if (!$cache || $this->fields_cache === null) {
            $q = new OrmRequest();

            $this->fields_cache = $q->from(new FormFieldItem())
                ->where('form_id = ' . $this['id'])
                ->orderby('sortn ASC')
                ->objects();

            //Присвоем уже подготовленные значения указанные в шаблоне
            foreach ($this->fields_cache as $k => $field) {
                /** @var FormFieldItem $field */
                $field->setDefault($this->getValue($field['alias']));
                $this->fields_cache[$k] = $field;
            }

            if ($this['use_captcha'] && !AppAuth::isAuthorize()) {
                $captcha = new FormFieldItem();
                $captcha['form_id'] = $this['id'];
                $captcha['title'] = CaptchaManager::currentCaptcha()->getFieldTitle();
                $captcha['alias'] = 'captcha';
                $captcha['show_type'] = FormFieldItem::SHOW_TYPE_CAPTCHA;
                $this->fields_cache[] = $captcha;
            }
        }

        return $this->fields_cache;
    }

    /**
     * Возвращает адреса получателя(ей) в виде массива
     * Если у данной формы Email не задан, то берется Email администратора из настроек сайта.
     *
     * @return array
     */
    function getAdressArray()
    {
        $site_config = ConfigLoader::getSiteConfig();
        $emails = $this['email'] ? $this['email'] : $site_config['admin_email'];
        return explode(",", $emails);
    }
}
