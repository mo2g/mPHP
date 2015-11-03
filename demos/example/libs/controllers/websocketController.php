<?php
/*
需要启用swoole拓展
无法连接websocket服务器，请确保启动服务
启动demos目录下的PHP文件，启动方式例如：
php /web/mPHP/demos/swoole_server.php

nginx配置
server {
	listen   80; 
	server_name  im.mo2g.com;
	set $root /web/mPHP/demos/example/;
	root $root;

	index index.php  index.html index.htm;

	proxy_http_version 1.1;
	proxy_set_header Connection "keep-alive";

	location ~ \.php {
		proxy_pass http://127.0.0.1:8059;
	}   

	location / { 
		if ( !-e $request_filename) {
			proxy_pass http://127.0.0.1:8059;
		}
	}
}
*/

class websocketController {
	//聊天室入口
	public function indexAction() {
		$view = new view();//声明视图类
		$view->data['title'] = 'mPHP websocket demo ';
		$view->loadTpl('websocket/join');
	}

	//聊天室
	public function roomAction() {
		$session = new sessionModel();
		$session->start();
		if( !isset($_SESSION['username']) ) {
			if( !isset($_POST['username']) || strlen($_POST['username']) < 1 ) {
				mPHP::status(302);
				mPHP::header('Location','/?c=websocket&a=index');
				return;
			} else {
				$_SESSION['username'] = $_POST['username'];
			}
		}
		$view = new view();//声明视图类
		$view->data['time'] = time();
		$view->data['username'] = $_SESSION['username'];
		$view->loadTpl('websocket/room');
	}

	//解析websocket传递过来的消息，执行对应的方法
	public function dispatchAction() {
		if( !isset(mPHP::$swoole['frame']) ) {
			//非法访问
			return;
		}

		$msg = json_decode(mPHP::$swoole['frame']->data, true);
		if( empty($msg['cmd']) ) {
			//错误处理
			return;
		}

		$func = 'cmd_'.$msg['cmd'];
		if( method_exists($this, $func) ) {
			$this->$func(mPHP::$swoole['frame']->fd, $msg);
		} else {
			//错误处理
			return;
		}
	}

	public function cmd_login($client_id,$msg) {
		mPHP::$swoole['ws_session'][ $client_id ]['session'] += [
			'login_time' => time(),
		];
		$jsonMsg = json_encode([
			'cmd' => 'login',
			'id' => $client_id,
			'username' => mPHP::$swoole['ws_session'][ $client_id ]['session']['username']
		]);
		$this->broadcast($client_id,$jsonMsg);
	}

	public function cmd_logout($client_id) {
		$jsonMsg = json_encode([
			'cmd' => 'logout',
			'id' => $client_id,
			'username' => mPHP::$swoole['ws_session'][ $client_id ]['session']['username'],
		]);
		$this->broadcast($client_id,$jsonMsg);
	}

	public function cmd_msg($client_id,$msg) {
		$jsonMsg = json_encode([
			'cmd' => 'msg',
			'id' => $client_id,
			'username' => mPHP::$swoole['ws_session'][ $client_id ]['session']['username'],
			'msg' => $msg['msg']
		]);
		$this->broadcast($client_id,$jsonMsg);
	}

	//广播操作
	public function broadcast($client_id,$msg) {
		foreach (mPHP::$swoole['server']->connections as $fd) {
			if( $fd != $client_id ) {
				mPHP::$swoole['server']->push($fd, $msg);//发送消息给其他人
			}
		}
	}

	//简单demo
	public function demoAction() {
		$view = new view();//声明视图类
		$view->data['title'] = 'mPHP websocket demo ';
		$view->loadTpl('websocket/websocket');//加载模版
	}
}