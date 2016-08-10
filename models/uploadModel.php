<?php
/*
作者:moyancheng
创建时间:2013-05-13
最后更新时间:2013-05-18
功能:文件上传
用法1:
<form enctype="multipart/form-data" action="" method="post">
<input name="file" type="file"><br>
<input type="submit" name="submit" value="上传文件">
</form>
$save_name = array('/tmp/1.jpg');
$file = new uploadModel('file',$save_name);

用法2:
<form enctype="multipart/form-data" action="" method="post">
<input name="file[]" type="file"><br>
<input name="file[]" type="file"><br>
<input type="submit" name="submit" value="上传文件">
</form>
$save_name = array('/tmp/1.jpg','/tmp/2.jpg');
$file = new uploadModel('file',$save_name);
$file->save();
*/
class uploadModel {
	public $info = array();
	public $error = array();//错误代码
	public $error_msg = array(
		0 => '上传文件成功',
		1 => '上传文件体积过大，错误码：1',//上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。
		2 => '上传文件体积过大，错误码：2',//上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。
		3 => '上传文件意外丢失，错误码：3',//文件只有部分被上传。
		4 => '上传文件为空',				//上传队列为空。
		5 => '上传文件体积为空',			//上传文件大小为0.
		100 => '不合法的上传渠道，错误码100',
		101 => '上传文件类型不合法，错误码101',
		102 => '上传文件体积超出限制，错误码102',
		103 => '上传文件保存过程发生意外错误，错误码103',
	);
	
	public $save_name = array();//文件保存名
	public $safe_type = array(
		//jpg文件
		'image/jpeg',
		'image/jpg',
		'image/jpe',
		'image/pjpeg',
		//gif
		'image/gif',
		//png
		'image/png',
		'image/x-png',
		//zip文件
		'application/zip',
		'application/x-zip-compressed',
		
		'application/octet-stream',//二进制文件,待优化判断
		'application/pdf',
		'application/msword',// doc
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',//docx
		'application/vnd.ms-excel',//xls
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',//xlsx
		'application/vnd.ms-powerpoint',//ppt
		'application/vnd.openxmlformats-officedocument.presentationml.presentation',//pptx
	);//安全类型
	public $safe_size;//安全上传文件大小
	public $file;
	
	public function __construct($key,$save_name,$safe_size = 5242880) {
		$this->files = $_FILES[$key];
		if( !is_array($save_name) ) $this->save_name[] = $save_name;
		else $this->save_name = $save_name;
		$this->safe_size = $safe_size;
		$this->getInfo();
		$this->save();
	}
	
	/*
	获取上传文件信息
	*/
	public function getInfo() {
		$arrFiles = $this->files;
		foreach($arrFiles as $key => $data) {
			if( is_array($data) ) {
				foreach($data as $val) {
					$this->info[$key][] = $val;
				}
			} else {
				$this->info[$key][0] = $data;
			}
		}
		return $arrFiles;
	}
	
	/*
	检测上传文件合法性
	*/
	public function check() {
		if( ($error =  $this->error() ) !== true ) return $error;
		$info = $this->info;
		$safe_type = $this->safe_type;
		$safe_size = $this->safe_size;
		
		//上传渠道检测
		if( is_array($info['tmp_name']) ) {
			foreach($info['tmp_name'] as $key => $val) {
				if( !is_uploaded_file($val) ) {
					$this->error[$key] = 100;
					return false;
				}
			}
		} else {
			if( !is_uploaded_file($info['tmp_name'][0]) ) {
				$this->error[0] = 100;
				return false;
			}
		}
		
		//上传文件类型检测
		if( is_array($info['type']) ) {
			foreach($info['type'] as $key => $val) {
				if( !in_array($val,$safe_type) ) {
					$this->error[$key] = 101;
					return false;
				}
			}
		} else {
			if( !in_array($info['type'],$safe_type) ) {
				$this->error[0] = 101;
				return false;
			}
		}
		
		//上传文件大小检测
		if( is_array($info['size']) ) {
			foreach($info['size'] as $key => $val) {
				if( $val > $safe_size ) {
					$this->error[$key] = 102;
					return false;
				}
			}
		} else {
			if( $val > $safe_size ) {
				$this->error[0] = 102;
				return false;
			}
		}
		
		return true;
	}
	
	/*
	保存上传文件
	*/
	public function save() {
		if( $this->check() ) {
			$info = $this->info;
			$save_name = $this->save_name;
			if( is_array($info['tmp_name']) ) {
				foreach($info['tmp_name'] as $key => $tmp_name) {
					if( !move_uploaded_file($tmp_name,$save_name[$key]) ) {
						$this->error[$key] = 103;
						break;
					}
				}
			} else {
				if( !move_uploaded_file($info['tmp_name'][0],$save_name[0]) ) {
					$this->error[0] = 103;
				}
			}
		}
		return $this->error();
	}
	
	/**
	* 获取文件扩展名
	* @return string
	*/
	public function getFileExt() {
		return strtolower( strrchr( $this->file[ "name" ] , '.' ) );
	}
	
	/*
	错误提示
	*/
	public function error() {
		$info = $this->info;
		$error = $this->error;
		$error_msg = $this->error_msg;
		foreach($error as $key => $val) {
			if( $val !== 0 ) return "{$info['name'][$key]}:{$error_msg[$val]}";
		}
		return true;
	}
	
}