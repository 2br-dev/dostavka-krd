<?php
/**
* ReadyScript (http://readyscript.ru)
*
* @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
* @license http://readyscript.ru/licenseAgreement/
*/
namespace Exchange\Model;

/**
* Класс авторизации через Base Http Auth
*/

class BasicAuth{
	private $message = "Enter Password";
	private $users = array(
        /*
        'username1' => 'pass1',
		'username2' => 'pass2',
        */
	);

    /**
    * Авторизован ли пользователь
    * @return bool
    */
	public function isAuthorized()
    {
        $user = $this->getUser();
        $pass = $this->getPass();        
        
		if(empty($user)) return false;
		if(empty($pass)) return false;
		
		if(!array_key_exists ($user, $this->users)) return false;
		if($this->users[$user] !== $pass) return false;
		return true;
	}
	
    /**
    * Добавить пользователя
    * @return void
    */
	public function addUser($name, $pass)
    {
		$this->users[$name] = $pass;
	}
    
    /**
    * Получить имя пользователя
    * @return string
    */
    public function getUser()
    {
        $user = false;
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $user = $_SERVER['PHP_AUTH_USER'];
        } elseif (preg_match('/^Basic\s+(.*)$/i', $_GET['http_authorization'], $user_pass)) {
            list($user, $pass)=explode(':',base64_decode($user_pass[1]));
        }        
        
        return $user;
    }

    /**
    * Получить пароль
    * @return string
    */
    public function getPass()
    {
        $pass = false;
        if (isset($_SERVER['PHP_AUTH_PW'])) {
            $pass = $_SERVER['PHP_AUTH_PW'];
        } elseif (preg_match('/^Basic\s+(.*)$/i', $_GET['http_authorization'], $user_pass)) {
            list($user, $pass)=explode(':',base64_decode($user_pass[1]));
        }        
                
        return $pass;
    }
    
    /**
    * Установить сообщение
    */
    public function setMessage($text)
    {
        $this->message = $text;
    }

    /**
    * Показать окно авторизации
    */
	public function authorize()
    {
		if(!$this->isAuthorized()){
			header('WWW-Authenticate: Basic realm="'.$this->message.'"');
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1 style="color:red">Access denied!</h1>';
			exit;
		}
	}

	public function https()
    {
		if(strpos($_SERVER["HTTP_HOST"], ".ru") === false) return;
		if(empty($_SERVER["HTTP_SSL"]) || $_SERVER["HTTP_SSL"] != 'on'){
			$link = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			header("Location: $link");
			die("<a href='$link'>$link</a>");
		}
	}
}
?>