<?php
class websocketController {
	public function indexAction() {
		$view = new view();//声明视图类
		$view->data['title'] = 'mPHP websocket demo ';
		$view->loadTpl('websocket');//加载模版
	}

	//解析websocket传递过来的消息，执行对应的方法
	public function dispatchAction() {
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
			'username' => $msg['username'],
			'login_time' => time(),
		];
		$jsonMsg = json_encode([
			'cmd' => 'login',
			'username' => $msg['username']
		]);
		$this->broadcast($client_id,$jsonMsg);
	}

	public function cmd_msg($client_id,$msg) {
		$jsonMsg = json_encode([
			'cmd' => 'msg',
			'username' => mPHP::$swoole['ws_session'][ $client_id ]['session']['username'],
			'msg' => $msg['msg']
		]);
		$this->broadcast($client_id,$jsonMsg);
	}

	public function cmd_getOnline() {
		$arrData = mPHP::$swoole['server']->connection_list();
		print_r($arrData);
	}

	//广播操作
	public function broadcast($client_id,$msg) {
		$start_fd = 0;
		while(true) {
			$conn_list = mPHP::$swoole['server']->connection_list($start_fd);
			if($conn_list === false or count($conn_list) === 0) {
				break;
			}
			$start_fd = end($conn_list);
			foreach($conn_list as $fd) {
				if( $fd != $client_id ) {
					mPHP::$swoole['server']->push($fd, $msg);
				}
			}
		}
		return;
	}

	public function logout() {

	}

	public function send($msg) {
		mPHP::$swoole['server']->push(mPHP::$swoole['frame']->fd, time());
	}
}