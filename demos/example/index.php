<?php
define('INIT_MPHP','mo2g.com');//常量值可以随便定义
define('INDEX_PATH',	__DIR__.'/');
define('MPHP_PATH',	realpath(INDEX_PATH.'../../').'/');	//框架根目录

include MPHP_PATH . 'mPHP.php';
$mPHP = mPHP::init();
$mPHP -> run();
