<?php
/*
	游客访问控制黑/白名单
*/
return array(
	"access"	=>	array(
		'home/User/countNew'	=> true,
		'home/Public/*'			=> true,
		'home/Space/*'      	=> true,
		'phptest/*/*'			=> true,
		'api/*/*'				=> true,
		'wap/*/*'				=> true,
		'admin/*/*'				=> true, // 管理后台的权限由它自己控制
	)
);
?>