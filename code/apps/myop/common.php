<?php
define('IN_MYOP', TRUE);
error_reporting(0);

// 初始化session
session_start();

require_once './api/define.php';
require_once './api/function.php';
require_once './function.php';

//所有URL的后面都不带“/”
define('SITE_PATH', 			SITE_ROOT);
define('MYOP_URL',				getmyopurl());
define('UC_URL',				MYOP_URL);
define('SITE_URL',				substr( MYOP_URL, 0, -(strlen(APPS_DIR_NAME) + strlen(MYOP_DIR_NAME) + 2) ));
define('PUBLIC_URL',			SITE_URL . '/public');

//系统配置
$_SITE_CONFIG					= array();
refreshConfig();

//公共模版
define('THEME_URL',				PUBLIC_URL . '/themes/' . $_SITE_CONFIG['site_theme']);
//MYOP模版
define('MYOP_THEME_PATH', 		MYOP_ROOT . '/themes/' . $_SITE_CONFIG['site_theme']);

//检查用户是否登录
if ( !$_SITE_CONFIG['uid'] ) {
	redirect(SITE_URL, 5, '请先登录系统。系统将在5秒后自动跳转至登录页面');
}

//检查站点是否关闭
if ( $_SITE_CONFIG['site_closed'] ) {
	redirect(SITE_URL);
}

//检查用户是否初始化
if ( ! $_SITE_CONFIG['userInfo']['is_init'] ) {
	redirect(U('home/Public/userinfo'), 5, '请先完善个人资料');
}

//漫游平台的全局变量
$_MY_GLOBAL						= array();
$_MY_GLOBAL['timestamp']		= time();
$_MY_GLOBAL['my_apps_url']		= 'http://apps.manyou.com/';
$_MY_GLOBAL['my_uchome_url']	= 'http://uchome.manyou.com';
$_MY_GLOBAL['my_api_url']		= 'http://api.manyou.com';
$_MY_GLOBAL['my_register_url']	= $_MY_GLOBAL['my_api_url'] . '/uchome.php';

//TODO
defined('IN_MYOP_ADMIN',	true); //formhash