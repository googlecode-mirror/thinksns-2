<?php
if (!defined('SITE_PATH')) exit();

return array(
	/* 常用配置 */
	'DB_TYPE'			=>	'mysql',			// 数据库类型
	'DB_HOST'			=>	'服务器地址',		// 数据库服务器地址
	'DB_NAME'			=>	'数据库名',			// 数据库名
	'DB_USER'			=>	'数据库用户名',		// 数据库用户名
	'DB_PWD'			=>	'数据库密码',		// 数据库密码
	'DB_PORT'			=>	3306,				// 数据库端口
	'DB_PREFIX'			=>	'数据库表前缀',		// 数据库表前缀（因为漫游的原因，数据库表前缀必须写在本文件）
	'DB_CHARSET'		=>	'utf8',				// 数据库编码
	'DB_FIELDS_CACHE'	=>	true,				// 启用字段缓存
	//'COOKIE_DOMAIN'		=>	'.thinksns.com',	//cookie域,请替换成你自己的域名 以.开头

	/* 默认应用 */
    'DEFAULT_APPS'		=> array('api','admin','home','myop','weibo','wap'),
);