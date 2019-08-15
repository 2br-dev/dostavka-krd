<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Users\Model\Orm;

use Alerts\Model\Manager as AlertsManager;
use Catalog\Model\CurrencyApi;
use RS\Config\Cms as RSConfig;
use RS\Config\Loader as ConfigLoader;
use RS\Helper\CustomView;
use RS\Orm\OrmObject;
use RS\Orm\Request as OrmRequest;
use RS\Orm\Type;
use RS\Router\Manager as RouterManager;
use Shop\Model\TransactionApi;
use Site\Model\Orm\Site;
use Users\Config\File as UsersConfig;
use Users\Model\GroupApi;
use Users\Model\Notice\UserRegisterAdmin as NoticeUserRegisterAdmin;
use Users\Model\Notice\UserRegisterUser as NoticeUserRegisterUser;

/**
* Объект - пользователь системы.
*/
class User extends OrmObject
{
    const SESSION_LAST_VISIT_VAR = 'last_visit';
    const PASSWORD_LEN = 4;
    const SUPERVISOR_GROUP = 'supervisor';

    protected static $table = "users";

    protected $default_group = 'guest'; //Группа, к которой определять пользователя по умолчанию
    protected $authorized_user_group = 'clients'; //Группа, к которой относятся все авторизованные пользователи
    protected $access_menu_table;
    protected $access_module_table;
    protected $cache_mod_access;
    protected $cache_menu_access;
    protected $cache_admin_menu_access;
    protected $cache_allow_sites;
    protected $groups;

    function __construct($id = null, $cache = true)
    {
        $this->access_menu_table = \Setup::$DB_TABLE_PREFIX.'access_menu';
        $this->access_module_table = \Setup::$DB_TABLE_PREFIX.'access_module';
        parent::__construct($id, $cache);
    }

    protected function _init()
    {
        parent::_init();

        $for_company = array('is_company' => 1);
        $chk_depend = array(get_class($this), 'chkDepend');

        $this->getPropertyIterator()->append(array(
            t('Основные'),
                'fio' => new Type\Varchar(array(
                    'description' => t('Ф.И.О.'),
                    'visible' => false,
                    'runtime' => true,
                    'checker' => array(function($user, $value, $error_text){
                        if (!empty($value)) {
                            $fio_parts = self::explodeFio($value);
                            if (empty($fio_parts[0]) || empty($fio_parts[1])) {
                                return $error_text;
                            }
                        }
                        return true;
                    }, t('Укажите, пожалуйста, Ф.И.О.')),
                )),
                'name' => new Type\Varchar(array(
                    'maxLength' => '100',
                    'description' => t('Имя'),
                    'meVisible' => false,
                    'checker' => array(function($user, $value, $error_text){
                        return (empty($user['fio']) && empty($value)) ? $error_text : true;
                    }, t('Укажите, пожалуйста, имя')),
                )),
                'surname' => new Type\Varchar(array(
                    'maxLength' => '100',
                    'description' => t('Фамилия'),
                    'meVisible' => false,
                    'checker' => array(function($user, $value, $error_text){
                        return (empty($user['fio']) && empty($value)) ? $error_text : true;
                    }, t('Укажите, пожалуйста, фамилию')),
                )),
                'midname' => new Type\Varchar(array(
                    'maxLength' => '100',
                    'description' => t('Отчество'),
                    'meVisible' => false,
                )),
                'e_mail' => new Type\Varchar(array(
                    'maxLength' => '150',
                    'description' => 'E-mail',
                    'unique' => true,
                    'Checker' => array('chkEmail', t('Неверно заполнен e-mail')),
                    'meVisible' => false,
                    'trimString' => true,
                )),
                'login' => new Type\Varchar(array(
                    'maxLength' => '64',
                    'unique' => true,
                    'description' => t('Логин'),
                    'meVisible' => false,
                )),
                'openpass' => new Type\Varchar(array(
                    'template' => '%users%/form/user/chpass.tpl',
                    'maxLength' => '100',
                    'description' => t('Новый пароль'),
                    'runtime' => true,
                    'Attr' => array(array('size' => '20', 'type' => 'password', 'autocomplete'=>'off')),
                    'Checker' => array(array(__CLASS__, 'checkOpenPassword'), ''),
                    'appVisible' => false,
                    'meVisible' => false,
                )),
                'pass' => new Type\Varchar(array(
                    'maxLength' => '32',
                    'description' => t('Пароль'),
                    'Attr' => array(array('size' => '20', 'type' => 'password', 'autocomplete'=>'off')),
                    'listenPost' => false,
                    'visible' => false,
                    'meVisible' => false,
                )),
                'phone' => new Type\Varchar(array(
                    'maxLength' => '50',
                    'description' => t('Телефон'),
                    'Checker' => array('chkPattern', t('Неверно указан телефон'), '/^[0-9()\-\s+,]+$/'),
                    'meVisible' => false,
                )),
                'sex' => new Type\Varchar(array(
                    'maxLength' => '1',
                    'description' => t('Пол'),
                    'ListFromArray' => array(array('' => t('Не выбрано'), 'M' => t('Мужской'), 'F' => t('Женский'))),
                    'Attr' => array(array()),
                    'meVisible' => false,
                )),
                'hash' => new Type\Varchar(array(
                    'maxLength' => '64',
                    'index' => true,
                    'description' => t('Ключ'),
                    'visible' => false,
                    'meVisible' => false,
                )),
                'subscribe_on' => new Type\Integer(array(
                    'maxLength' => '1',
                    'description' => t('Получать рассылку'),
                    'CheckBoxView' => array(1,0),
                    'meVisible' => false,
                )),
                'dateofreg' => new Type\Datetime(array(
                    'description' => t('Дата регистрации'),
                    'meVisible' => false,
                )),
                'balance' => new Type\Decimal(array(
                    'allowEmpty' => false,
                    'readOnly' => true,
                    'listenPost' => false,
                    'maxLength' => '15',
                    'decimal' => 2,
                    'description' => t('Баланс'),
                    'appVisible' => false,
                    'meVisible' => false,
                )),
                'balance_sign' => new Type\Varchar(array(
                    'visible' => false,
                    'listenPost' => false,
                    'description' => t('Подпись баланса'),
                    'meVisible' => false,
                )),
                'ban_expire' => new Type\Datetime(array(
                    'description' => t('Заблокировать до ...'),
                    'template' => '%users%/form/user/ban_expire.tpl',
                    'meVisible' => false,
                )),
                'ban_reason' => new Type\Varchar(array(
                    'description' => t('Причина блокировки'),
                    'visible' => false,
                    'meVisible' => false,
                )),
                'last_visit' => new Type\Datetime(array(
                    'description' => t('Последний визит'),
                    'meVisible' => false,
                )),
                'last_ip' => new  Type\Varchar(array(
                    'description' => t('Последний IP, который использовался'),
                    'maxLength' => 100,
                    'meVisible' => false,
                )),
                'registration_ip' => new Type\Varchar(array(
                    'description' => t('IP пользователя при регистрации'),
                    'maxLength' => 100,
                    'meVisible' => false,
                )),
            t('Организация'),
                'is_company' => new Type\Integer(array(
                    'maxLength' => '1',
                    'description' => t('Это юридическое лицо?'),
                    'ListFromArray' => array(array('0' => t('Нет'), '1' => t('Да'))),
                    'meVisible' => false,
                )),
                'company' => new Type\Varchar(array(
                    'maxLength' => '255',
                    'description' => t('Название организации'),
                    'condition' => $for_company,
                    'Checker' => array($chk_depend, t('Не указано название организации'), 'chkEmpty', $for_company),
                    'meVisible' => false,
                )),
                'company_inn' => new Type\Varchar(array(
                    'maxLength' => '12',
                    'description' => t('ИНН организации'),
                    'condition' => array('is_company' => 1),
                    'Checker' => array($chk_depend, t('ИНН должен состоять из 10 или 12 цифр'), 'chkPattern', $for_company, array('/^(\d{10}|\d{12})$/')),
                    'attr' => array(array(
                        'size' => 20
                    )),
                    'meVisible' => false,
                )),
            t('Группы'),
                '__groups__' => new Type\UserTemplate('%users%/form/user/groups.tpl', '%users%/form/user/megroups.tpl', array(
                    'meVisible' => true,
                )),
                'groups' => new Type\ArrayList(array(
                    'description' => t('Группы'),
                    'appVisible' => false
                )),
            t('Дополнительные сведения'),
                'data' => new Type\ArrayList(array(
                    'description' => '',
                    'template' => '%users%/form/user/userfields.tpl',
                    'meVisible' => false,
                )),
                'changepass' => new Type\Integer(array(
                    'description' => t('Сменить пароль'),
                    'runtime' => true,
                    'CheckBoxView' => array(1,0),
                    'visible' => false,
                    'Attr' => array(array('id' => 'chpass')),
                )),
                '_serialized' => new Type\Text(array(
                    'visible' => false,
                )),
                'captcha' => new Type\Captcha(array(
                    'enable' => false
                )),
        ));
    }

    /**
     * Проверяет пароль на соответствие требованиям безопасности
     *
     * @param User $_this - проверяемый ORM - объект
     * @param mixed $value - проверяемое значение
     * @return bool(true) | string возвращает true в случае успеха, иначе текст ошибки
     */
    public static function checkOpenPassword($_this, $value)
    {
        if ($_this['changepass']) {
            if (mb_strlen($value) < self::PASSWORD_LEN) {
                return t('Пароль должен содержать не менее %len знаков', array('len' => self::PASSWORD_LEN));
            }
        }
        return true;
    }

    /**
     * Действия перед записью объекта
     *
     * @param string $flag - insert или update
     * @return bool
     */
    public function beforeWrite($flag)
    {
        if ($this['id'] < 0) {
            $this['_tmpid'] = $this['id'];
            unset($this['id']);
        }

        $ret = true;
        //Создаем новый произвольный ключ при каждом сохранении
        $this->updateHash(false);
        $this['_serialized'] = serialize($this['data']);

        if ($flag == self::INSERT_FLAG) {
            $this['dateofreg'] = date('Y-m-d H:i:s');
            $this['registration_ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }

        if (!empty($this['fio'])) {
            $fio_parts = self::explodeFio($this['fio']);
            $this['surname'] = (isset($fio_parts[0])) ? $fio_parts[0] : '';
            $this['name'] = (isset($fio_parts[1])) ? $fio_parts[1] : '';
            $this['midname'] = (isset($fio_parts[2])) ? $fio_parts[2] : '';
        }

        $this['e_mail'] = trim($this['e_mail']);
        if (!$this->isModified('login')) {
            $this['login'] = $this['e_mail']; //Если логин не задан, то устанавливаем его таким же как email
        }

        if (!empty($this['openpass'])) {
            $this['pass'] = self::cryptPass($this['openpass']);
        }

        if ($this['ban_expire'] == '') {
            $this['ban_expire'] = null;
        }

        //Проверяем, не занят ли E-mail
        $q = OrmRequest::make()->from($this)
            ->where(array(
                'e_mail' => $this['e_mail'],
            ));
        if ($flag == self::UPDATE_FLAG) {
            $q->where("id != '#this_id'", array('this_id' => $this['id']));
        }

        if ($q->count()) {
            $this->addError(t('такой E-mail уже занят'), 'e_mail');
            $ret = false;
        }

        //Сохраняем дополнительные сведения о пользователе
        $conf_userfields = $this->getUserFieldsManager();
        $uf_err = $conf_userfields->check($this['data']);
        if (!$uf_err && !$this['no_validate_userfields']) {
            foreach ($conf_userfields->getErrors() as $form => $errortext) {
                $this->addError($errortext, $form);
            }
            $ret = false;
        }

        return $ret;
    }

    /**
     * Разделяет ФИО на фамилию, имя и отчество
     *
     * @param string $value - строка с ФИО
     * @return string[]
     */
    public static function explodeFio($value)
    {
        return explode(' ', preg_replace('/^\s*([\wа-яА-Я]+)\s+([\wа-яА-Я]+)\s*([\wа-яА-Я]*).*$/iu', '\1 \2 \3', $value));
    }

    /**
    * Действия после записи объекта
    *
    * @param string $flag - insert или update
    */
    public function afterWrite($flag)
    {
        if ($flag == self::INSERT_FLAG && \Setup::$INSTALLED && !$this['no_send_notice']) {

            // Уведомление пользователю
            $notice = new NoticeUserRegisterUser;
            $notice->init($this, $this['openpass']);
            AlertsManager::send($notice);

            $site_config = ConfigLoader::getSiteConfig();
            if ($site_config['admin_email']) {
                // Уведомление администратору
                $notice = new NoticeUserRegisterAdmin;
                $notice->init($this, $this['openpass']);
                AlertsManager::send($notice);
            }
        }

        if ($this->isModified('groups')) {
            $this->linkGroup($this['groups']);
        }

    }

    /**
     * Действия после того как подгружен объект
     *
     * @return void
     */
    public function afterObjectLoad()
    {
        $this['data'] = (array)@unserialize($this['_serialized']);
    }

    /**
     * Возвращает группы, в которых состоит пользователь
     * В ReadyScript предусмотрены системные группы пользователей, к которым
     * пользователь автоматически причисляется при следующих условиях:
     * Гость  - присваивается всем пользователям, находящимся в клиентской части
     * Клиент - присваивается всем авторизованным пользователям, находящимся в клиентской части
     *
     * @param boolean $returnAliases - Если true, то возвращает массив с alias'ами групп, иначе array
     * @return array
     */
    public function getUserGroups($returnAliases = true)
    {
        if (!isset($this->groups)) //Не запрашиваем дважды группы у юзера
        {
            $this->groups = array();
            $is_admin_zone = RouterManager::obj()->isAdminZone();

            if (!$is_admin_zone) {
                $this->groups += $this->getClientSideGroups();
            }

            if ($this['id'] > 0) {
                $this->groups += OrmRequest::make()->select('G.*')
                    ->from(new UserGroup())->asAlias('G')
                    ->from(new UserInGroup())->asAlias('I')
                    ->where("I.group = G.alias AND I.user='#user'", array('user' => $this['id']))
                    ->objects('\Users\Model\Orm\UserGroup', 'alias');
            }
        }

        return $returnAliases ? array_keys($this->groups) : $this->groups;
    }

    /**
     * Возвращает системные группы пользователей, к которым пользователь автоматически причисляется при нахождении в клиенской части:
     * Гость  - присваивается всем пользователям,
     * Клиент - присваивается всем авторизованным пользователям,
     *
     * return UserGroup[]
     */
    public function getClientSideGroups()
    {
        $groups = array(
            $this->default_group => new UserGroup($this->default_group),
        );
        if ($this['id'] > 0) {
            $groups[$this->authorized_user_group] = new UserGroup($this->authorized_user_group);
        }
        return $groups;
    }

    /**
     * Проверяет состоит ли пользователь в группе
     *
     * @param string $group_alias - идентификатор группы пользователей
     * @return bool
     */
    public function inGroup($group_alias)
    {
        $groups = $this->getUserGroups(false);
        return isset($groups[$group_alias]);
    }

    /**
     * Возвращает True, если пользователь состоит в группе с отметкой "Администратор"
     * Пользователи такой группы могут входить в административную панель.
     *
     * @return bool
     */
    public function isAdmin()
    {
        $groups = $this->getUserGroups(false);
        $is_admin = false;
        foreach ($groups as $group) {
            $is_admin = $is_admin || $group['is_admin'];
        }
        return $is_admin;
    }

    /**
     * Помещает пользователя в группу
     *
     * @param array $groups - массив групп в которые нужно добавить
     * @return void
     */
    public function linkGroup(array $groups)
    {
        $uig = new UserInGroup();
        $uig->linkUserToGroup($this['id'], $groups);
    }

    /**
     * Добавляет группу к уже существующим и пользователя группам
     *
     * @param string $groupid - алиас группы
     * @return void
     */
    function addGroup($groupid)
    {
        $user_groups = OrmRequest::make()
            ->from(new UserInGroup())
            ->where(array(
                'user' => $this['id']
            ))
            ->objects();
        if ($user_groups) { //Если состоит в группах
            foreach ($user_groups as $group) {
                if ($groupid == $group['group']) { //Если такая группа уже существует
                    return;
                }
            }
        }

        $user_group = new UserInGroup();
        $user_group['user'] = $this['id'];
        $user_group['group'] = $groupid;
        $user_group->insert();
    }

    /**
     * Удаляет пользователя из группы $group_id или из всех групп
     *
     * @param $group_id - ID группы
     * @return void
     */
    function unlinkGroup($group_id = null)
    {
        OrmRequest::make()
            ->delete()
            ->from(new UserInGroup())
            ->where(array('user' => $this['id'])
                + ($group_id ? array('group' => $group_id) : array())
            )->exec();
    }

    /**
     * Возвращает права доступа ко всем модулям с учетом группы,
     * к которой принадлежит пользователь.
     *
     * @return array
     */
    function getModuleAccess()
    {
        if (isset($this->cache_mod_access)) return $this->cache_mod_access;

        $groups = $this->getUserGroups();
        if (in_array(self::SUPERVISOR_GROUP, $groups)) {
            $mod_right[AccessModule::FULL_MODULE_ACCESS] = AccessModule::MAX_ACCESS_RIGHTS;
        } else {
            $q = OrmRequest::make()
                ->select('module, BIT_OR(access) as access')
                ->from($this->access_module_table)
                ->where(empty($this['id']) ? null : "user_id={$this['id']}")
                ->groupby('module');

            if ($groups) {
                $q->whereIn('group_alias', $groups, "OR");
            }

            $mod_right = $q->exec()->fetchSelected('module', 'access');
        }

        $this->cache_mod_access = $mod_right;
        return $this->cache_mod_access;
    }

    /**
     * Возвращет права оступа к конкретному модулю, с учетом группы к кторой принадлежит пользователь
     *
     * @param string $module - название модуля
     * @return int 0 - нет прав, 255 - полный права
     */
    function getRight($module)
    {
        $module = strtolower($module);
        $mod_access = $this->getModuleAccess();
        if (isset($mod_access['all'])) return $mod_access['all'];
        return isset($mod_access[$module]) ? $mod_access[$module] : 0;
    }

    /**
     * Проверяет наличие у пользователя переданного права к модулю
     *
     * @param string $module - имя модуля
     * @param string $right - идентификатор права
     * @return bool;
     */
    function checkModuleRight($module, $right)
    {
        $groups = $this->getUserGroups();

        if (in_array(self::SUPERVISOR_GROUP, $groups)) {
            return RSConfig::ACCESS_ALLOW;
        }

        $rights = GroupApi::getRights($groups);
        if (isset($rights[$module][$right]) && count($rights[$module][$right]) == 1) {
            $result = reset($rights[$module][$right]);
        } else {
            $config_cms = ConfigLoader::getSystemConfig();
            $result = $config_cms['access_priority'];
        }

        return $result == RSConfig::ACCESS_ALLOW;
    }

    /**
     * Возвращает права доступа к пунктам меню пользовательской части с учетом группы,
     * к которой принадлежит пользователь
     *
     * @return array
     */
    function getMenuAccess()
    {
        if (isset($this->cache_menu_access)) return $this->cache_menu_access;
        $groups = $this->getUserGroups();

        if (in_array(self::SUPERVISOR_GROUP, $groups)) {
            $menu_access = array(
                AccessMenu::FULL_USER_ACCESS
            );
        } else {
            $q = OrmRequest::make()
                ->select('menu_id')
                ->from($this->access_menu_table)
                ->where(array('menu_type' => 'user'))
                ->openWGroup()
                ->where(empty($this['id']) ? null : "user_id={$this['id']}");

            if ($groups) {
                $q->whereIn('group_alias', $groups, "OR");
            }

            $q->closeWGroup()
                ->groupby('menu_id');

            $menu_access = $q->exec()->fetchSelected(null, 'menu_id');
        }
        $this->cache_menu_access = $menu_access;

        return $this->cache_menu_access;
    }

    /**
     * Возвращает права доступа к пунктам меню административной панели с учетом группы,
     * к которой принадлежит пользователь
     *
     * @return array
     */
    function getAdminMenuAccess()
    {
        if (isset($this->cache_admin_menu_access)) return $this->cache_admin_menu_access;
        $groups = $this->getUserGroups();

        if (in_array(self::SUPERVISOR_GROUP, $groups)) {
            $menu_access = array(
                AccessMenu::FULL_ADMIN_ACCESS
            );
        } else {
            $q = OrmRequest::make()
                ->select('menu_id')
                ->from($this->access_menu_table)
                ->where(array('menu_type' => 'admin'))
                ->openWGroup()
                ->where(empty($this['id']) ? null : "user_id={$this['id']}");
            if ($groups) {
                $q->whereIn('group_alias', $groups, "OR");
            }
            $q->closeWGroup()
                ->groupby('menu_id');

            $menu_access = $q->exec()->fetchSelected(null, 'menu_id');
        }

        $this->cache_admin_menu_access = $menu_access;

        return $this->cache_admin_menu_access;
    }

    /**
     * Удаляет пользователя
     *
     * @return bool
     */
    function delete()
    {
        if ($ret = parent::delete()) {
            $this->unlinkGroup();
        }
        return $ret;
    }

    /**
     * Возвращает хэш от пароля.
     *
     * @param string $password - пароль в открытом виде
     * @return string
     */
    public static function cryptPass($password)
    {
        // для совместимости с магазинами, начинавшими с RS 1.0 разрешаем задать другой способ расчета хеша в классе конфигурации
        if (is_callable(array('\Setup', 'cryptPassword'))) {
            return \Setup::cryptPassword($password);
        }
        return md5($password . sha1(\Setup::$SECRET_SALT));
    }

    /**
     * Вернет true, если пользователь будет создан,
     * В случае ошибки - false. Вызовите $this->getLastError(), чтобы увидеть текст ошибки
     * Объект должен быть заполнен даными перед вызовом данного метода
     *
     * @return bool
     */
    public function create()
    {
        if (!$this->checkCreateData()) return false;
        return $this->insert();
    }

    /**
     * Возвращает клонированный объект пользователя
     *
     * @return self
     */
    public function cloneSelf()
    {
        $this['usergroup'] = $this->getUserGroups(); //заполним группы 
        /** @var \Users\Model\Orm\User $clone */
        $clone = parent::cloneSelf();
        $clone->setTemporaryId();

        unset($clone['e_mail']);
        unset($clone['login']);
        unset($clone['openpass']);
        unset($clone['pass']);

        return $clone;
    }

    /**
     * Проверяет, можно ли создать пользователя с текущими данными.
     * (проверяется уникальность логина, уникальность e-mail'а)
     *
     * @return bool
     */
    public function checkCreateData()
    {
        $res = OrmRequest::make()
            ->select('*')->from($this)
            ->where(array(
                'login' => $this['login'],
                'e_mail' => $this['e_mail']
            ), null, 'OR')
            ->exec();

        if ($res->rowCount()) {
            $user_row = $res->fetchRow();

            if (!empty($this['login']) && stristr($user_row['login'], $this['login']) !== false)
                $this->addError(t('Пользователь с таким логином уже существует'), 'login');

            if (!empty($this['e_mail']) && stristr($user_row['e_mail'], $this['e_mail']) !== false)
                $this->addError(t('Пользователь с таким E-mail`ом уже существует'), 'e_mail');

            return false;
        }
        return true;
    }

    /**
     * Возвращает массив с данными пользовательских полей
     *
     * @return array
     */
    public function getUserFields()
    {
        $struct = $this->getUserFieldsManager()->getStructure();

        foreach ($struct as &$field) {
            $field['current_val'] = isset($this['data'][$field['alias']]) ? $this['data'][$field['alias']] : $field['val'];
        }

        return $struct;
    }

    /**
     * Возвращает объект - менеджер произвольных полей
     * @return \RS\Config\UserFieldsManager
     */
    public function getUserFieldsManager()
    {
        /** @var UsersConfig $config */
        $config = ConfigLoader::byModule($this);
        return $config->getUserFieldsManager()
            ->setErrorPrefix('userfield_')
            ->setArrayWrapper('data')
            ->setValues((array)$this['data']);
    }

    /**
     * Возвращает Фамилию, имя, отчество в одну строку
     *
     * @return string
     */
    public function getFio()
    {
        return trim($this['surname'] . ' ' . $this['name'] . ' ' . $this['midname']);
    }

    /**
     * Обновляет секретный хэш пользователя. Хэш используется для восстановления пароля
     *
     * @param bool $commit - если true, то изменения будут сразу сохранены в базе.
     * @return void
     */
    public function updateHash($commit = true)
    {
        $newhash = md5(uniqid(mt_rand(), true)) . md5(uniqid(mt_rand(), true));
        $this['hash'] = $newhash;
        if ($commit) {
            OrmRequest::make()
                ->update($this)
                ->set(array(
                    'hash' => $this['hash']
                ))
                ->where("id='{$this['id']}'")
                ->exec();
        }
    }

    /**
     * Возвращает true, если пароль соответствует требованиям, иначе текст ошибки
     *
     * @param string $password - пароль
     * @return bool(true) | string
     */
    public static function checkPassword($password)
    {
        if (mb_strlen($password) < self::PASSWORD_LEN) {
            return t('Пароль должен содержать не менее %len знаков', array('len' => self::PASSWORD_LEN));
        }
        return true;
    }

    /**
     * Возвращает true, если пользователь является супервизором
     *
     * @return bool
     */
    public function isSupervisor()
    {
        return $this->inGroup(self::SUPERVISOR_GROUP);
    }

    /**
     * Возвращает true, если у пользователя есть права на данный сайт
     * @param integer $site_id - ID сайта
     *
     * @return bool
     */
    public function checkSiteRights($site_id)
    {
        $allow_sites = $this->getAllowSites();
        return in_array($site_id, array_keys($allow_sites));
    }

    /**
     * Возвращает список сайтов, доступных пользователю
     *
     * @return Site[]
     */
    public function getAllowSites()
    {
        if (!isset($this->cache_allow_sites)) {
            $groups = $this->getUserGroups();

            if ($this->isSupervisor()) {
                $q = OrmRequest::make()
                    ->from(new Site());
            } else {
                $q = OrmRequest::make()
                    ->select('S.*')
                    ->from(new AccessSite())->asAlias('A')
                    ->join(new Site(), 'A.site_id = S.id', 'S')
                    ->where(empty($this['id']) ? null : "user_id={$this['id']}");
                if ($groups) {
                    $q->whereIn('group_alias', $groups, "OR");
                }
            }

            $this->cache_allow_sites = $q->objects('\Site\Model\Orm\Site', 'id');
        }
        return $this->cache_allow_sites;
    }

    /**
     * Возвращает текущий остаток на лицевом счете пользователя
     *
     * @param bool $use_currency - если true, то значение будет возвращено в текущей валюте, иначе в базовой
     * @param bool $format - если true, то форматировать возвращаемое значение, приписывать символ валюты
     * @return string
     */
    public function getBalance($use_currency = false, $format = false)
    {
        if ($this->checkBalanceSign()) {
            $balance = ($use_currency) ? CurrencyApi::applyCurrency($this['balance']) : $this['balance'];
        } else {
            $balance = 0;
        }
        if ($use_currency) {
            return $format ? CustomView::cost($balance, CurrencyApi::getCurrecyLiter()) : $balance;
        } else {
            $base_currency = CurrencyApi::getBaseCurrency();
            return $format ? CustomView::cost($balance, $base_currency['stitle']) : $balance;
        }
    }

    /**
     * Возвращает true, если подпись к балансу является корректной
     *
     * @return bool
     */
    public function checkBalanceSign()
    {
        if ($this['balance'] == 0) return true;
        $transApi = new TransactionApi();
        $balance_sign = $transApi->getBalanceSign($this['balance'], $this['id']);
        return $balance_sign == $this['balance_sign'];
    }

    /**
     * Возвращает идентификатор группы пользователей, которая присваивается обязательно всем
     * пользователям в клиентской части сайта.
     *
     * @return string
     */
    public function getDefaultGroup()
    {
        return $this->default_group;
    }

    /**
     * Возвращает идентификатор группы пользователей, которая присваивается всем
     * авторизованным пользователям в клиентской части сайта.
     *
     * @return string
     */
    public function getAuthorizedGroup()
    {
        return $this->authorized_user_group;
    }

    /**
     * Обновляет дату последнего посещения в БД не чаще, чем 1 раз в 4 часа
     *
     * @return void
     */
    public function saveVisitDate()
    {
        $delay = 60 * 60 * 4;
        $datetime = time();

        if ($this['id'] > 0
            && (!isset($_SESSION[self::SESSION_LAST_VISIT_VAR])
                || $datetime - $_SESSION[self::SESSION_LAST_VISIT_VAR] > $delay)
        ) {
            $_SESSION[self::SESSION_LAST_VISIT_VAR] = $datetime;
            $this['last_visit'] = date('Y-m-d H:i:s', $datetime);
            $this['last_ip'] = $_SERVER['REMOTE_ADDR'];

            OrmRequest::make()
                ->update($this)
                ->set(array(
                    'last_visit' => $this['last_visit'],
                    'last_ip' => $this['last_ip']
                ))
                ->where(array(
                    'id' => $this['id']
                ))
                ->exec();
        }
    }
}
