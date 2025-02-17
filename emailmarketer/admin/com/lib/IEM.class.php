<?php
class IEM
{
	const VERSION = '6.1.0';
	const DATABASE_VERSION = '20100731';
	const SESSION_NAME = 'IEMSESSIONID';
	final private function __construct()
	{
	}
	final static public function init($reset = false)
	{
		$GLOBALS['ApplicationUrl'] = SENDSTUDIO_APPLICATION_URL;
		if (defined('SENDSTUDIO_IS_SETUP') && SENDSTUDIO_IS_SETUP && !InterspireEvent::eventExists('IEM_MARKER_20090701')) {
			IEM_Installer::RegisterEventListeners();
			require_once IEM_ADDONS_PATH . '/interspire_addons.php';
			$addons = new Interspire_Addons();
			$addons->FixEnabledEventListeners();
			InterspireEvent::eventCreate('IEM_MARKER_20090701');
		}
		if (!self::configInit($reset)) {
			return false;
		}
		if (!self::sessionInit($reset)) {
			return false;
		}
		if (!self::userInit($reset)) {
			return false;
		}
		$tempUser         = IEM::getCurrentUser();
		$tempUserLanguage = 'default';
		if (!empty($tempUser->user_language) && is_dir(IEM_PATH . "/language/{$tempUser->user_language}")) {
			$tempUserLanguage = $tempUser->user_language;
		}
		require_once(IEM_PATH . "/language/{$tempUserLanguage}/whitelabel.php");
		require_once(IEM_PATH . "/language/{$tempUserLanguage}/language.php");
		self::$_enableInfoTips = false;
		if (isset($tempUser->infotips) && $tempUser->infotips) {
			self::$_enableInfoTips = true;
		}
		unset($tempUserLanguage);
		unset($tempUser);
	}
	static private $_configInitFlag = false;
	static private $_configVariables = array();
	static private $_enableInfoTips = false;
	final static public function enableInfoTipsGet()
	{
		return intval(self::$_enableInfoTips);
	}
	final static public function configInit($reset = false)
	{
		if (self::$_configInitFlag && !$reset) {
			return false;
		}
		self::$_configInitFlag = true;
		return true;
	}
	final static public function configGet()
	{
	}
	final static public function configSet()
	{
	}
	final static public function configSave()
	{
		return true;
	}
	final static public function configReset($values = array())
	{
	}
	static private $_sessionInitFlag = false;
	static private $_sessionReference = false;
	final static public function sessionInit($reset = false)
	{
		if (self::$_sessionInitFlag && !$reset) {
			return false;
		}
		self::$_sessionInitFlag = true;
		if (session_id()) {
			@session_write_close();
		}
		if (!defined('IEM_NO_SESSION') && !IEM_CLI_MODE) {
			session_name(IEM::SESSION_NAME);
			ini_set('session.use_cookies', 1);
			ini_set('session.gc_probability', 1);
			ini_set('session.gc_divisor', 100);
			ini_set('session.gc_maxlifetime', 3600);
			@session_start();
		}
		if (isset($_SESSION)) {
			self::$_sessionReference =& $_SESSION;
		} else {
			self::$_sessionReference = array();
		}
		if (!array_key_exists('initialized', self::$_sessionReference)) {
			self::$_sessionReference = array(
				'initialized' => true,
				'storage' => array(),
				'user' => array()
			);
		}
		return true;
	}
	final static public function sessionGet($variableName, $defaultValue = null)
	{
		if (array_key_exists($variableName, self::$_sessionReference['storage'])) {
			return self::$_sessionReference['storage'][$variableName];
		}
		return $defaultValue;
	}
	final static public function sessionSet($variableName, $value, $expiry = 0)
	{
		self::$_sessionReference['storage'][$variableName] = $value;
		return true;
	}
	final static public function sessionRemove($variableName)
	{
		if (!array_key_exists($variableName, self::$_sessionReference['storage'])) {
			return false;
		}
		unset(self::$_sessionReference['storage'][$variableName]);
		return true;
	}
	final static public function sessionReset($resetLogin = false)
	{
		if (session_id() && !headers_sent()) {
			session_regenerate_id();
		}
		self::$_sessionReference['storage'] = array();
		self::$_sessionReference['user']    = array();
		return true;
	}
	final static public function sessionDestroy()
	{
		self::$_sessionReference = array();
		if (session_id()) {
			@session_destroy();
		}
	}
	final static public function sessionID()
	{
		$id = session_id();
		if (!$id) {
			return 'no_session_id_CLI';
		} else {
			return $id;
		}
	}
	static private $_userCacheObject = false;
	static private $_userStack = array();
	static private $_userInit = false;
	final static public function userInit($reset = false)
	{
		if (self::$_userInit && !$reset) {
			return false;
		}
		self::$_userInit        = true;
		self::$_userCacheObject = false;
		self::$_userStack       = self::sessionGet('__IEM_SYSTEM_CurrentUser_Stack', array(), 'intval');
		return true;
	}
	final static public function userLogin($userid, $recordLastLogin = true, $clearStack = false)
	{
		$userid = intval($userid);
		if (empty($userid)) {
			return false;
		}
		$user = GetUser($userid);
		if (!$user) {
			return false;
		}
		if ($recordLastLogin) {
			$rand_check                   = uniqid(true);
			$user->settings['LoginCheck'] = $rand_check;
			$user->SaveSettings();
			$user->UpdateLoginTime();
		}
		if ($clearStack) {
			self::$_userStack = array();
		}
		self::$_userStack[]     = $user->userid;
		self::$_userCacheObject = $user;
		IEM::sessionReset();
		IEM::sessionSet('__IEM_SYSTEM_CurrentUser_Stack', self::$_userStack);
		return true;
	}
	final static public function userLogout($compleLogout = false)
	{
		if (empty(self::$_userStack)) {
			return false;
		}
		if ($compleLogout) {
			self::$_userStack = array();
		} else {
			array_pop(self::$_userStack);
		}
		self::userFlushCache();
		return IEM::sessionSet('__IEM_SYSTEM_CurrentUser_Stack', self::$_userStack);
	}
	final static public function userFlushCache()
	{
		self::$_userCacheObject = false;
	}
	final static public function userGetStack($object = false)
	{
	}
	final static public function userGetCurrent()
	{
		if (!self::$_userCacheObject instanceof Users_API) {
			$userStack = self::$_userStack;
			if (empty($userStack)) {
				return false;
			}
			$userID      = array_pop($userStack);
			$currentUser = new User_API($userID);
			if ($currentUser->userid != $userID) {
				return false;
			}
			self::$_userCacheObject = $currentUser;
		}
		return self::$_userCacheObject;
	}
	static public function requestGetPOST($variableName, $defaultValue = '', $callback = null)
	{
		$value = $defaultValue;
		if (isset($_POST) && array_key_exists($variableName, $_POST)) {
			$value = $_POST[$variableName];
		}
		return self::_requestProcess($value, $callback);
	}
	static public function requestGetGET($variableName, $defaultValue = '', $callback = null)
	{
		$value = $defaultValue;
		if (isset($_GET) && array_key_exists($variableName, $_GET)) {
			$value = $_GET[$variableName];
		}
		return self::_requestProcess($value, $callback);
	}
	static public function requestGetCookie($cookieName, $devaultValue = '', $callback = null)
	{
		$value = $devaultValue;
		if (isset($_COOKIE) && array_key_exists($cookieName, $_COOKIE)) {
			$value = @unserialize(base64_decode($_COOKIE[$cookieName]));
		}
		return self::_requestProcess($value, $callback);
	}
	static public function requestSetCookie($cookieName, $cookieValue, $expiry = 0)
	{
		$value = @serialize($cookieValue);
		if ($value === false) {
			return false;
		}
		$expiry = intval(abs($expiry));
		if ($expiry != 0) {
			$expiry += time();
		}
		return @setcookie($cookieName, base64_encode($value), $expiry, '/');
	}
	static public function requestRemoveCookie($cookieName)
	{
		return @setcookie($cookieName, '', time() - 100000, '/');
	}
	static private function _requestProcess($variable, $callback = null)
	{
		if (empty($callback)) {
			return $variable;
		}
		if (is_array($variable)) {
			foreach ($variable as &$each) {
				$each = self::_requestProcess($each, $callback);
			}
			return $variable;
		} else {
			return $callback($variable);
		}
	}
	static private $_langLoaded = array();
	static public function langLoad($language)
	{
		$user          = IEM::userGetCurrent();
		$user_language = 'default';
		$language      = strtolower($language);
		if (in_array($language, self::$_langLoaded)) {
			return true;
		}
		if (!empty($user->user_language)) {
			$users_language = $user->user_language;
		}
		if (empty($users_language) || !is_dir(IEM_PATH . "/language/{$users_language}")) {
			$users_language = 'default';
		}
		$langfile = IEM_PATH . "/language/{$users_language}/{$language}.php";
		if (!is_file($langfile)) {
			trigger_error("No Language file for {$language} area", E_USER_WARNING);
			return false;
		}
		include_once $langfile;
		self::$_langLoaded[] = $language;
		return true;
	}
	static public function langLoaded()
	{
	}
	static public function langGet($section, $name)
	{
		if (!self::langLoad($section)) {
			return $name;
		}
		return GetLang($name);
	}
	final static public function getDatabase($overwriteDB = null)
	{
		static $db = null;
		static $characterset_defined = false;
		if (!is_null($overwriteDB) && ($overwriteDB instanceof Db)) {
			$db = $overwriteDB;
		}
		if (is_null($db)) {
			while (true) {
				$required_constants = array(
					'SENDSTUDIO_DATABASE_TYPE',
					'SENDSTUDIO_DATABASE_HOST',
					'SENDSTUDIO_DATABASE_USER',
					'SENDSTUDIO_DATABASE_PASS',
					'SENDSTUDIO_DATABASE_NAME'
				);
				$all_ok             = false;
				foreach ($required_constants as $required) {
					if (!defined($required)) {
						break;
					}
					$all_ok = true;
				}
				if (!$all_ok) {
					break;
				}
				try {
					$db              = IEM_DBFACTORY::manufacture(SENDSTUDIO_DATABASE_TYPE, SENDSTUDIO_DATABASE_HOST, SENDSTUDIO_DATABASE_USER, SENDSTUDIO_DATABASE_PASS, SENDSTUDIO_DATABASE_NAME);
					$db->TablePrefix = SENDSTUDIO_TABLEPREFIX;
				}
				catch (Exception $e) {
					$db = false;
					break;
				}
				break;
			}
			if (is_null($db)) {
				$db = false;
			}
		}
		while (!$characterset_defined) {
			if (is_null($db) || $db === false || !defined('SENDSTUDIO_DATABASE_TYPE')) {
				break;
			}
			if (SENDSTUDIO_DATABASE_TYPE != 'mysql') {
				$characterset_defined = true;
				break;
			}
			if (!defined('SENDSTUDIO_CHARSET')) {
				break;
			}
			if (SENDSTUDIO_CHARSET != 'UTF-8') {
				$characterset_defined = true;
				break;
			}
			if (!defined('SENDSTUDIO_DATABASE_UTF8PATCH')) {
				define('SENDSTUDIO_DATABASE_UTF8PATCH', false);
			}
			if (!SENDSTUDIO_DATABASE_UTF8PATCH) {
				$characterset_defined = true;
				break;
			}
			$db->Query("SET NAMES 'utf8'");
			$db->charset          = 'utf8';
			$characterset_defined = true;
			break;
		}
		return $db;
	}
	final static public function getCurrentUser()
	{
		return self::userGetCurrent();
	}
	final static public function logUserActivity($url, $icon = '', $text = '')
	{
		static $activitylog = null;
		if (is_null($activitylog)) {
			require_once IEM_PUBLIC_PATH . '/functions/api/useractivitylog.php';
			$activitylog = new UserActivityLog_API();
		}
		$status = $activitylog->LogActivity($url, $icon, $text);
		if (!$status) {
			trigger_error('Unable to log activity', E_USER_NOTICE);
		}
	}
	final static public function urlFor($page, $params = array(), $relative = true)
	{
		$base_url = 'index.php';
		if (!$relative) {
			$base_url = SENDSTUDIO_APPLICATION_URL . '/admin/' . $base_url;
		}
		$url = $base_url . '?Page=' . urlencode($page);
		if (is_array($params) && count($params)) {
			foreach ($params as $key => $value) {
				$url .= '&' . urlencode($key) . '=' . urlencode($value);
			}
		}
		return $url;
	}
	final static public function redirectTo($page, $params = array())
	{
		$url = self::urlFor($page, $params, false);
		if (!headers_sent()) {
			header("Location: {$url}");
			exit();
		}
		echo "<script>window.location.href = '{$url}';</script>";
		exit();
	}
	final static public function ifsetor(&$var, $default = null)
	{
		if (isset($var)) {
			$tmp = $var;
		} else {
			$tmp = $default;
		}
		return $tmp;
	}
	final static public function encrypt($s, $key)
	{
		if (empty($s) || empty($key)) {
			return false;
		}
		while (strlen($key) < strlen($s)) {
			$key .= $key;
		}
		return $s ^ $key;
	}
	final static public function decrypt($s, $key)
	{
		return self::encrypt($s, $key);
	}
	final static public function timeGetUserDisplayString($format = null, $time = null)
	{
		if (is_null($format)) {
			$format = GetLang('TimeFormat', 'F j Y, g:i a');
		}
		if (is_null($time)) {
			$time = time();
		}
		$offset = 0;
		$user   = IEM::getCurrentUser();
		if ($user instanceof User_API) {
			if (preg_match('/GMT(\-|\+)(\d+)\:(\d+)/', $user->usertimezone, $matches)) {
				list(, $tempSign, $tempHour, $tempMinute) = $matches;
				$offset = ($tempHour * 3600) + ($tempMinute * 60);
				if ($tempSign == '-') {
					$offset *= -1;
				}
			}
			if (preg_match('/(\-|\+)(\d+)\:(\d+)/', date('P'), $matches)) {
				list(, $tempSign, $tempHour, $tempMinute) = $matches;
				$tempOffset = ($tempHour * 3600) + ($tempMinute * 60);
				if ($tempSign == '-') {
					$tempOffset *= -1;
				}
				$offset -= $tempOffset;
			}
		}
		return date($format, ($time + $offset));
	}
	final static public function isInstalled()
	{
		if (defined('SENDSTUDIO_IS_SETUP') && SENDSTUDIO_IS_SETUP) {
			return true;
		}
		$configFile = realpath(dirname(__FILE__) . '/../../includes/config.php');
		$hasConfig  = is_file($configFile);
		if (!$hasConfig) {
			return false;
		}
		return (bool) preg_match('/define/', file_get_contents($configFile));
	}
	final static public function hasUpgrade()
	{
		$db = IEM::getDatabase();
		if (!$db) {
			return false;
		}
		$res = $db->Query('SELECT * FROM [|PREFIX|]settings;');
		if (!$res) {
			return false;
		}
		$settings   = $db->Fetch($res);
		$newVersion = (int) self::DATABASE_VERSION;
		$oldVersion = (int) $settings['database_version'];
		return $newVersion > $oldVersion;
	}
	final static public function isInstalling()
	{
		if (!isset($_GET['Page'])) {
			return false;
		}
		$page = strtolower($_GET['Page']);
		return $page === 'install' || $page === 'installer';
	}
	final static public function isUpgrading()
	{
		if (!isset($_GET['Page'])) {
			return false;
		}
		return strtolower($_GET['Page']) === 'upgradenx';
	}
	final static public function isCompletingUpgrade()
	{
		return isset($_GET['Step']) && $_GET['Step'] == 3;
	}
}
class exceptionIEM extends Exception
{
}