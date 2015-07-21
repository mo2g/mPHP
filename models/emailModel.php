<?php
/*
作者:moyancheng
创建时间：2013-11-01
最后更新时间:2013-11-12
*/
/*
$time = time();
$host = 'smtp.qq.com';
$pors = 25;
$user = 'email@mo2g.com';
$pass = '***';

$smtp = new emailModel($host,$pors,$user,$pass); 

$to = 'moyancheng@gmail.com';
$from = 'email@mo2g.com';
$subject = '邮件测试'.$time;
$body = '测试成功';
$from_email = 'email@mo2g.com';
$from_name = '磨途歌';
	
if($smtp->send($to,$from,$subject,$body,$from_email,$from_name) ) {
	echo $time;
}
*/
/*
 $smtp->smtp('smtp.qq.com');           //邮件发送服务器 
 $smtp->pors(25);                      //邮件服务器端口 
 $smtp->login( '123@qq.com');     //邮件服务器登录用户 
 $smtp->pass('***');             //邮件服务器登录密码 
 $smtp->mails( 'moyancheng@gmail.com');    //邮件接收人 
 $smtp->from( '123@qq.com');      //邮件发送人 
 $smtp->come( '磨途歌<email@mo2g.com>');  //接收邮件回复地址 
 $smtp->title('这是一封邮件测试地址邮件'.time());  //邮件主题 
 $smtp->body('这是一封邮件测试内容邮件');   //邮件内容 
 echo $smtp->send();                   //发送邮件并输出发送状态 
*/
 
 
/*
SMTP发送邮件类
fsockopen()
set_socket_blocking();0是非阻塞，1是阻塞
fgets
fputs
*/
class emailModel {
	private $fp;
	private $host;
	private $post;
	private $user;
	private $pass;
	
	/*
	$host	服务器地址
	$post	端口
	$user	用户名
	$pass	密码
	*/
	public function __construct($host = '',$post = '',$user = '',$pass = '') {
		$this->fp = false;
		$this->host = $host;
		$this->post = $post;
		$this->user = $user;
		$this->pass = $pass;
	}
	
	//跟邮箱服务器通信
	public function connection($host = '',$post = '',$user = '',$pass = '') {
		if( $this->fp == false) {
			$host = $host ? $host : $this->host;
			$post = $post ? $post : $this->post;
			$user = $user ? $user : $this->user;
			$pass = $pass ? $pass : $this->pass;
			
			$fp = fsockopen( $host, $post);
			// @set_socket_blocking($fp, true);
			
			//向邮件服务器发送连接请求
			$Server = fgets($fp, 512);
			$this->check($Server,'220');
			
			//向服务器发送会话请求
			fputs($fp, "HELO phpsetmail\r\n");
			$Server = fgets($fp, 512);
			$this->check($Server,'250');
			
			//请求验证用户名，密码
			fputs($fp, "AUTH LOGIN\r\n");
			$Server = fgets($fp, 512);
			$this->check($Server,'334');
			
			//发送用户名
			fputs($fp, base64_encode($user) . "\r\n");
			$Server = fgets($fp, 512);
			$this->check($Server,'334');
			
			//发送密码
			fputs($fp, base64_encode($pass) . "\r\n");
			$Server = fgets($fp, 512);
			$this->check($Server,'235');
			
			$this->fp = $fp;
		}
		return $this;
	}
	
	//关闭连接
	public function close() {
		if( $this->fp != false ) {
			fputs($this->fp, "QUIT\r\n");
			if( $this->fp ) fclose($this->fp);
			$this->fp = false;
		}
	}
	
	/*
	$to	接收地址
	$from	发送地址
	$subject	主题
	$body	内容
	$from_email	自定义发送地址
	$from_name	自定义发送名称
	*/
	public function send($to,$from,$subject,$body,$from_email = '',$from_name = '') {
		$this->connection();
		
		//指定邮件发送邮箱
		fputs($this->fp, "MAIL FROM:{$from}\r\n");
		$Server = fgets($this->fp, 512);
		$this->check($Server,'250');
	
		//指定邮件接收邮箱
		fputs($this->fp, "RCPT TO:{$to}\r\n");
		$Server = fgets($this->fp, 512);
		$this->check($Server,'250');
		
		//发送邮件数据
		fputs($this->fp, "DATA\r\n");
		$Server = fgets($this->fp, 512);
		$this->check($Server,'354');
		
		$header = "MIME-Version:1.0\r\n";
		$header .= "Content-Type:text/html;charset=\"utf-8\"\r\n";
		$header .= "Content-Transfer-Encoding: base64\r\n";
		if( $from_email == '' ) {
			$header .= 'From: =?utf-8?b?'.base64_encode($from)."?=\r\n";//邮件回复地址
		} else {
			$header .= 'From: =?utf-8?b?'.base64_encode($from_name)."?=<{$from_email}>\r\n";//邮件回复地址
		}
		$subject = '=?utf-8?b?'.base64_encode($subject).'?=';//编码邮件标题，解决乱码问题
		
		$body = base64_encode($body);//编码邮件标题，解决乱码问题
		$header .= "Subject:{$subject}\r\n";//邮件主题
		$header .= "To:{$to}\r\n";//邮件接收人
		$strEmail = "{$header}\r\n{$body}\r\n.\r\n";
		
		fputs($this->fp, $strEmail);
		$Server = fgets($this->fp, 512);
		
		$this->check($Server,'250');
		
		//fputs($this->fp, "QUIT\r\n");
		//fclose($this->fp);
		return true;
	}
	
	//检测服务器返回值
	public function check($strInfo,$strKey) {
		if( substr($strInfo,0,3) == $strKey ) {
			return true;
		} else {
			echo $strInfo;
			if( $this->fp ) fclose($this->fp);
			exit;
		}
	}
	
}