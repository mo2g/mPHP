<?php
/*
作者:moyancheng
创建时间:2015-09-24
最后更新时间:2015-09-24

功能：初始化mPHP目录、数据库 等操作
*/
class initModel {
	public static function initMainDir() {
		if(!is_dir(CACHE_PATH)) {
			mkdir(CACHE_PATH,0755,true);
			file_put_contents(CACHE_PATH.'index.html','');
		}
		/*
		if(!is_dir(CONF_PATH)) {
			mkdir(CONF_PATH);
			file_put_contents(CONF_PATH.'index.html','');
		}
		*/
		if(!is_dir(CONTROLLERS_PATH)) {
			mkdir(CONTROLLERS_PATH,0755,true);
			file_put_contents(CONTROLLERS_PATH.'index.html','');
		}
		if(!is_dir(MODELS_PATH)) {
			mkdir(MODELS_PATH,0755,true);
			file_put_contents(MODELS_PATH.'index.html','');
		}
		if(!is_dir(SERVICES_PATH)) {
			mkdir(SERVICES_PATH,0755,true);
			file_put_contents(SERVICES_PATH.'index.html','');
		}
		if(!is_dir(DAOS_PATH)) {
			mkdir(DAOS_PATH,0755,true);
			file_put_contents(DAOS_PATH.'index.html','');
		}
		if(!is_dir(TPL_PATH)) {
			mkdir(TPL_PATH,0755,true);
			file_put_contents(TPL_PATH.'index.html','');
		}
		if(!is_dir(TPL_C_PATH.'admin')) {
			mkdir(TPL_C_PATH.'admin',0755,true);
			file_put_contents(TPL_C_PATH.'index.html','');
			file_put_contents(TPL_C_PATH.'admin/index.html','');
		}
		if(!is_dir(STATIC_PATH.'merger') ) {
			mkdir(STATIC_PATH.'merger',0755,true);
			file_put_contents(STATIC_PATH.'index.html','');
			file_put_contents(STATIC_PATH.'merger/index.html','');
		}
		foreach(mPHP::$CFG['main_dir'] as $dir) {
			directoryModel::createDirs($dir['path'],$dir['totle']);
		}
	}
	
	//初始化数据库
	public static function initDb() {
		$db = db::init();
		$db->initDb('initdata/tables.sql');
		unset($db);
	}
}