<?php
define('SITE_PATH', getcwd());
require(SITE_PATH.'/core/sociax.php');
//实例化一个网站应用实例
$App = new App();
$App->run();
?>