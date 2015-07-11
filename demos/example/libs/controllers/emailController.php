<?php
class emailController {
	public function indexAction() {
		//如果邮件不能正常发送，请根据错误提示寻找原因
		//常见的问题就是没有开启IMAP/SMTP服务
		$time = microtime(1);
		$host = 'smtp.qq.com';
		$pors = 25;
		$user = 'QQ邮箱';
		$pass = '密码';

		$smtp = new emailModel($host,$pors,$user,$pass); 

		$to = '接收邮箱';
		$from = $user;
		$subject = 'PHP邮件测试'.$time;//邮件主题
		$body = '测试成功';//邮件内容
		//定义如下两项后，发件人内容显示类似：PHP邮件测试<QQ邮箱>
		$from_email = $user;
		$from_name = 'PHP邮件测试';
			
		if($smtp->send($to,$from,$subject,$body,$from_email,$from_name) ) {
			echo '总耗时：',(microtime(1) - $time);
		}
		echo '<h1>控制器源代码：</h1><hr />';
		highlight_file(__FILE__); 
	}
}