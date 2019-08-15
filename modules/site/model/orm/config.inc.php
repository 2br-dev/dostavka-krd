<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Site\Model\Orm;

use RS\Orm\AbstractObject;
use RS\Orm\Type;
use RS\Router\Manager as RouterManager;
use RS\Site\Manager as SiteManager;
use RS\Theme\Manager as ThemeManager;

/**
* Конфигурационный файл одного сайта.
* @ingroup Site
*/
class Config extends AbstractObject
{
    protected static 
        $table = 'site_options',
        $activeSiteInstance;
    
    function _init()
    {
        $router = RouterManager::obj();
        $this->getPropertyIterator()->append(array(
            'site_id' => new Type\CurrentSite(array(
                'primaryKey' => true
            )),
            t('Основные'),
                'admin_email' => new Type\Varchar(array(
                    'maxLength' => '150',
                    'description' => t('E-mail администратора(ов)'),
                    'hint' => t('На этот ящик будут приходить уведомления о событиях в системе(заказы, покупки в 1 клик, обращениях пользователей, и т.д.). <br>Допустимо указывать несколько E-mail адресов через запятую.<br> Например: admin@example.com или admin@example.com,manager@example.com'),
                )),
                'admin_phone' => new Type\Varchar(array(
                    'maxLength' => '150',
                    'description' => t('Телефон администратора'),
                    'hint' => t('На этот телефон будут приходить SMS уведомления(опционально). <br>Допустимо указывать несколько телефонов через запятую.<br> Например: +79112223334 или +79112223334,+79222333411'),
                )),
                'theme' => new Type\Varchar(array(
                    'Attr' => array(array('readonly' => 'readonly')),
                    'maxLength' => '150',
                    'description' => t('Тема'),
                    'template' => '%site%/form/options/theme.tpl'
                )),
                'favicon' => new Type\File(array(
                    'description' => t('Иконка сайта 16x16 (PNG, ICO)'),
                    'hint' => t('Отображается возле заголовка страницы в браузерах'),
                    'max_file_size' => 100000, //100 Кб
                    'allow_file_types' => array('image/png', 'image/x-icon'),
                    'storage' => array(\Setup::$ROOT, \Setup::$FOLDER.'/storage/favicon/')
                )),
            t('Организация'),
                'logo' => new Type\Image(array(
                    'maxLength' => '200',
                    'description' => t('Логотип'),
                    'max_file_size' => 10000000,
                    'allow_file_types' => array('image/pjpeg', 'image/jpeg', 'image/png', 'image/gif'),
                    'PreviewSize' => array(275,80),
                )),
                'slogan' => new Type\Varchar(array(
                    'description' => t('Лозунг')
                )),
                'firm_name' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Наименование организации'),
                )),
                'firm_inn' => new Type\Varchar(array(
                    'maxLength' => '12',
                    'description' => t('ИНН организации'),
                    'Attr' => array(array('size' => 20)),
                )),
                'firm_kpp' => new Type\Varchar(array(
                    'maxLength' => '12',
                    'description' => t('КПП организации'),
                    'Attr' => array(array('size' => 20)),
                )),
                'firm_bank' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Наименование банка'),
                )),
                'firm_bik' => new Type\Varchar(array(
                    'maxLength' => '10',
                    'description' => t('БИК'),
                )),
                'firm_rs' => new Type\Varchar(array(
                    'maxLength' => '20',
                    'description' => t('Расчетный счет'),
                    'Attr' => array(array('size' => 25)),
                )),
                'firm_ks' => new Type\Varchar(array(
                    'maxLength' => '20',
                    'description' => t('Корреспондентский счет'),
                    'Attr' => array(array('size' => 25)),
                )),
                'firm_director' => new Type\Varchar(array(
                    'maxLength' => '70',
                    'description' => t('Фамилия, инициалы руководителя'),
                )),
                'firm_accountant' => new Type\Varchar(array(
                    'maxLength' => '70',
                    'description' => t('Фамилия, инициалы главного бухгалтера'),
                )),
                'firm_v_lice' => new Type\Varchar(array(
                    'description' => t('Компания представлена в лице ...'),
                    'hint' => t('например: директора Иванова Ивана Ивановича. Пустое поле означает - "в собственном лице"')
                )),
                'firm_deistvuet' => new Type\Varchar(array(
                    'description' => t('действует на основании ...'),
                    'hint' => t('например: устава или свидетельства о регистрации физ.лица в качестве ИП, ОГРНИП:0000000000')
                )),
                'firm_address' => new Type\Varchar(array(
                    'description' => t('Фактический адрес компании'),
                )),
                'firm_legal_address' => new Type\Varchar(array(
                    'description' => t('Юридический адрес компании'),
                )),
                'firm_email' => new Type\Varchar(array(
                    'description' => t('Официальный Email компании'),
                )),
            t('Уведомления'),
                'notice_from' => new Type\Varchar(array(
                    'maxLength' => 255,
                    'description' => t("Будет указано в письме в поле  'От'"),
                    'hint' => t('Например: "Магазин ReadyScript&lt;robot@ваш-магазин.ru&gt;" или просто "robot@ваш-магазин.ru"'),
                    'checker' => array('chkPattern', t('Неправильно указан email<br>Например: "Магазин ReadyScript&lt;robot@ваш-магазин.ru&gt;" или просто "robot@ваш-магазин.ru"'), '/^.+\&lt\;{1}[a-z0-9_.-]+\@[a-z0-9_.-]+.[a-z.]{2,6}\&gt\;{1}$|^[a-z0-9_.-]+\@[a-z0-9_.-]+.[a-z.]{2,6}$|^$/'),

                )),
                'notice_reply' => new Type\Varchar(array(
                    'maxLength' => 255,
                    'description' => t("Куда присылать ответные письма? (поле Reply)"),
                    'hint' => t('Например: "Магазин ReadyScript&lt;support@ваш-магазин.ru&gt;" или просто "support@ваш-магазин.ru"')
                )),
                'smtp_is_use' => new Type\Integer(array(
                    'description' => t('Использовать SMTP для отправки писем'),
                    'checkboxView' => array(1,0),
                    'template' => '%main%/form/sysoptions/smtp_is_use.tpl',
                    'hint' => t('Если включена - все поля из настроек сайта перекроют соответствующие поля настроек системы'),
                )),
                'smtp_host' => new Type\Varchar(array(
                    'description' => t('SMTP сервер')
                )),
                'smtp_port' => new Type\Varchar(array(
                    'description' => t('SMTP порт'),
                    'maxLength' => 10
                )),
                'smtp_secure' => new Type\Varchar(array(
                    'description' => t('Тип шифрования'),
                    'listFromArray' => array(array(
                        '' => t('Нет шифрования'),
                        'ssl' => 'SSL',
                        'tls' => 'TLS'
                    ))
                )),
                'smtp_auth' => new Type\Integer(array(
                    'description' => t('Требуется авторизация на SMTP сервере'),
                    'checkboxView' => array(1,0)
                )),
                'smtp_username' => new Type\Varchar(array(
                    'description' => t('Имя пользователя SMTP'),
                    'maxLength' => 100
                )),
                'smtp_password' => new Type\Varchar(array(
                    'description' => t('Пароль SMTP'),
                    'maxLength' => 100
                )),
                'dkim_is_use' => new Type\Integer(array(
                    'description' => t('Устанавливать DKIM подпись с помощью ReadyScript'),
                    'checkboxView' => array(1,0),
                    'template' => '%main%/form/sysoptions/dkim_is_use.tpl',
                )),
                'dkim_domain' => new Type\Varchar(array(
                    'description' => t('DKIM домен'),
                    'hint' => t('Укажите его, если вы желаете генерировать DKIM подпись средствами ReadyScript. Оставьте пустое поле, если желаете передать эту функцию на сторону ПО для отправки почты.')
                )),
                'dkim_private' => new Type\File(array(
                    'description' => t('Приватный ключ DKIM'),
                )),
                'dkim_selector' => new Type\Varchar(array(
                    'description' => t('Селектор DKIM записи в доменной зоне'),
                    'hint' => t('То, что находится до _domainkey, например: селектор "dkim", если запись имеет ключ dkim._domainkey.ВАШ ДОМЕН.')
                )),
                'dkim_passphrase' => new Type\Varchar(array(
                    'description' => t('Пароль для приватного ключа (если есть)')
                )),
            t('Социальные ссылки'),
                'facebook_group' => new Type\Varchar(array(
                    'description' => t('Ссылка на группу в Facebook')
                )),
                'vkontakte_group' => new Type\Varchar(array(
                    'description' => t('Ссылка на группу ВКонтакте')
                )),
                'twitter_group' => new Type\Varchar(array(
                    'description' => t('Ссылка на страницу в Twitter')
                )),
                'instagram_group' => new Type\Varchar(array(
                    'description' => t('Ссылка на страницу в Instagram')
                )),
                'youtube_group' => new Type\Varchar(array(
                    'description' => t('Ссылка на страницу в YouTube')
                )),
                'viber_group' => new Type\Varchar(array(
                    'description' => t('Ссылка на Viber')
                )),
                'telegram_group' => new Type\Varchar(array(
                    'description' => t('Ссылка на Telegram')
                )),
                'whatsapp_group' => new Type\Varchar(array(
                    'description' => t('Ссылка на WhatsApp')
                )),
            t('Персональные данные'),
                'policy_personal_data' => new Type\Richtext(array(
                    'description' => t('Политика обработки персональных данных (ссылка /policy/)'),
                    'hint' => t('Разместите ссылку на данную политику, принятую в вашей органиции на вашем сайте, в меню или в подвале'),
                    'template' => '%site%/form/site/policy.tpl',
                    'loadDefaultUrl' => $router->getAdminUrl("LoadDefaultDocument", array("doc_id" => "policy_personal_data"), 'site-personaldata')
                )),
                'agreement_personal_data' => new Type\Richtext(array(
                    'description' => t('Соглашение на обработку персональных данных (ссылка /policy-agreement/)'),
                    'hint' => t('Документ, который пользователь акцептует при сообщении своих персональных данных'),
                    'template' => '%site%/form/site/policy.tpl',
                    'loadDefaultUrl' => $router->getAdminUrl("LoadDefaultDocument", array("doc_id" => "agreement_personal_data"), 'site-personaldata')
                )),
                'enable_agreement_personal_data' => new Type\Integer(array(
                    'description' => t('Включить отображение соглашения на обработку персональных данных в формах'),
                    'checkboxView' => array(1,0)
                ))
        ));
    }
    
    function getPrimaryKeyProperty()
    {
        return 'site_id';
    }
    
    function _initDefaults()
    {
        $this['theme'] = 'default';
    }

    public static function getActualInstance()
    {
        if (!isset(self::$activeSiteInstance)) {
            self::$activeSiteInstance = new self();
            self::$activeSiteInstance->load(SiteManager::getSiteId());
        }
        return self::$activeSiteInstance;
    }
    
    function getThemeName()
    {
        $theme_data = ThemeManager::parseThemeValue($this['theme']);
        return $theme_data['theme'];
    }
}
