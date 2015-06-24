<?php
/*
作者:moyancheng
创建时间:2012-05-01
最后更新时间:2014-08-13
$img = new imageModel();
$im->resize('./test/1.jpg','./test/'.time().'im.jpg',array(1024,682));

resize性能测试
GD：
	原图尺寸	原图大小(kb)	新图尺寸	新图大小(kb)	耗时(秒)
	3648X2432	1830			3648x2432	766				2.4695060253143
	3648X2432	1830			1024x682	87				1.6348030567169
	3648X2432	1830			512x512		39				1.2680110931396
	3648X2432	1830			512x312		29				1.1258220672607
Imagick:
	原图尺寸	原图大小(kb)	新图尺寸	新图大小(kb)	耗时(秒)
	3648X2432	1830			3648x2432	1667			1.2276549339294
	3648X2432	1830			1024x682	109				1.4863979816437
	3648X2432	1830			512x512		48				1.3441100120544
	3648X2432	1830			512x312		36				1.3035759925842
	
watermark性能测试
GD:	
原图尺寸	原图大小(kb)	logo尺寸	logo大小(kb)	大小(kb)	耗时(秒)
3648x2432	1830			250x187		13.6			768			0.54974603652954
1024x682	87				250x187		13.6			83			0.058006048202515
Imagick:	
原图尺寸	原图大小(kb)	logo尺寸	logo大小(kb)	大小(kb)		耗时(秒)
3648x2432	1830			250x187		13.6			1668		0.94336795806885	
1024x682	87				250x187		13.6			82			0.10776400566101	
*/
class imageModel {
	public $image;
	public function __construct() {
		if( `convert -version` ) $this->image = new Imagick();
		elseif( function_exists('getimagesize') ) $this->image = new GD();
		else return false;
	}
	
	/*
	功能：添加图片水印
	$src_img:原图
	$logo:水印图
	$image:生成图
	$arrConfig:相关配置
	*/
	public function watermark($src_img,$logo,$image,$arrConfig = array()) {
		return $this->image->watermark($src_img,$logo,$image,$arrConfig);
	}
	
	/*
	功能：根据尺寸生成新图
	$src_img:原图
	$image:生成图
	$arrConfig:相关配置
	*/
	public function resize($src_img,$image,$arrConfig = array()) {
		return $this->image->resize($src_img,$image,$arrConfig);
	}
	
	//生成验证码图
	public function strImage($str) {
		// 建立一幅 100X30 的图像
		$im = imagecreate(55, 30);
		// 白色背景和蓝色文本 D6BB86
		//$bg = imagecolorallocate($im, 0xd6, 0xbb, 0x86);
		$bg = imagecolorallocate($im, 0xff, 0xff, 0xff);
		$textcolor = imagecolorallocate($im, 0, 0, 0);
		// 把字符串写在图像左上角
		imagestring($im, 5, 5, 5, $str, $textcolor);
		// 输出图像
		mPHP::header('Content-Type', 'image/png');
		imagepng($im);
	}
	
	public function getInfo($img) {
		return $this->image->getInfo($img);
	}
}

class GD {
	/*
	 * $src_img:原图
	 * $logo:水印图
	 * $image:合成图
	 * $x:0~100，（原图宽度-水印图宽度） / 100 * $x = 水印在原图中的x坐标
	 * $y:0~100，（原图高度-水印图高度） / 100 * $y = 水印在原图中的y坐标
	 * $pct:水印的透明度0~100
	 */
	public function watermark($dst_img,$logo,$image,$arrConfig = array()) {
		$pct = isset($arrConfig['pct']) ? $arrConfig['pct'] : 100;
		$postion = isset($arrConfig['postion']) ? $arrConfig['postion'] : 'SouthEast';
		$intQuality = isset($arrConfig['quality']) ? $arrConfig['quality'] : 90;//压缩率
		switch($postion) {
			case 'NorthWest' : $x = 0;		$y = 0;break;//西北
			case 'NorthEast' : $x = 100;	$y = 0;break;//东北
			case 'SouthWest' : $x = 0;		$y = 100;break;//西南
			case 'SouthEast' : $x = 100;	$y = 100;break;//东南
		}
		
		list($dst_w,$dst_h,$dst_type) = getimagesize($dst_img);
		list($logo_w,$logo_h,$logo_type) = getimagesize($logo);
		$dst_x_max = $dst_w - $logo_w;
		$dst_y_max = $dst_h - $logo_h;
		
		//防止x坐标超出范围
		if($x > 100 || $x == '') $x = 100;
		elseif ($x < 0) $x = 0;
		$x = intval($dst_x_max * $x / 100);
		//防止y坐标超出范围
		if($y > 100 || $y == '') $y = 100;
		elseif($y < 0) $y = 0;
		$y = intval($dst_y_max * $y / 100);

		//防止pct透明度超出范围
		if($pct < 0) $pct = 0;
		else $pct = 100;
		
		if( $dst_type == 2 ) {
			$dst_img = imagecreatefromjpeg($dst_img);
		} elseif( $dst_type == 3 ) {
			$dst_img = imagecreatefrompng($dst_img);
		}
		
		if( $logo_type == 2 ) {
			$logo = imagecreatefromjpeg($logo);
		} elseif( $logo_type == 3 ) {
			$logo = imagecreatefrompng($logo);
		}
		
		imagecopymergegray($dst_img, $logo, $x, $y, 0, 0, $logo_w, $logo_h, $pct);
		imagejpeg($dst_img,$image,$intQuality);
	}
	
	/*
	 * $src_im:原图
	 * $image:生成图
	 * $arrConfig['width']:生成图宽度
	 * $arrConfig['height']:生成图高度
	 */
	public function resize($src_img,$image,$arrConfig = array()) {
		//新图宽高（像素）
		$dst_w = isset($arrConfig[0]) ? $arrConfig[0] : $arrConfig['width'];
		$dst_h = isset($arrConfig[1]) ? $arrConfig[1] : $arrConfig['height'];
		$intQuality = isset($arrConfig['quality']) ? $arrConfig['quality'] : 90;//压缩率
		//原图宽高（像素）
		list($src_w,$src_h,$src_type) = getimagesize($src_img);
		
		$retio_src = $src_w / $src_h;//原图宽高比例
		if( $dst_w == 0 ) {
			$retio_dst = $retio_src;
			$dst_w = $dst_h * $retio_dst;
		} elseif( $dst_h == 0 ) {
			$retio_dst = $retio_src;
			$dst_h = $dst_w / $retio_dst;
		} else {
			$retio_dst = $dst_w / $dst_h;//新图宽高比例
		}
		
		$dst_img = imagecreatetruecolor($dst_w, $dst_h);
		if( $src_type == 1 ) {
			$src_img = imagecreatefromgif($src_img);
		} elseif( $src_type == 2 ) {
			$src_img = imagecreatefromjpeg($src_img);
		} elseif( $src_type == 3 ) {
			$src_img = imagecreatefrompng($src_img);
		}
		
		
		if( $retio_src == $retio_dst ) {
			imagecopyresampled($dst_img, $src_img,0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);//缩放
		} elseif( $retio_src > $retio_dst ) {
			$retio = $src_h / $dst_h;
			$intWidth = $src_w / $retio;
			$src_x = ($intWidth - $dst_w) / 2 ;
			$tmp_img = imagecreatetruecolor($intWidth, $dst_h);
			imagecopyresampled($tmp_img, $src_img,	0,	0,		0,	0,	$intWidth,	$dst_h, $src_w, $src_h);//缩放
			imagecopyresampled($dst_img, $tmp_img,	0,	0, $src_x,	0,	$dst_w,		$dst_h, $dst_w, $dst_h);//裁剪
		} elseif( $retio_src < $retio_dst ) {
			$retio = $src_w / $dst_w;
			$intHeight = $src_h / $retio;
			$src_y = ($intHeight - $dst_h) / 2 ;
			$tmp_img = imagecreatetruecolor($dst_w, $intHeight);
			imagecopyresampled($tmp_img, $src_img,	0,	0,	0,		0,	$dst_w,	$intHeight,	$src_w,	$src_h);//缩放
			imagecopyresampled($dst_img, $tmp_img,	0,	0,	0, $src_y,	$dst_w,		$dst_h,	$dst_w,	$dst_h);//裁剪
		}
		
		$image_type = strtolower( strrchr( $image , '.' ) );
		if( $image_type == '.gif' ) {
			imagegif($dst_img, $image);
		} elseif( $image_type == '.jpg' || $image_type == '.jpeg') {
			imagejpeg($dst_img,$image,$intQuality);
		} elseif( $image_type == '.png' ) {
			imagepng($dst_img, $image); 
		}
		
		imagedestroy($dst_img);
		if( isset($tmp_img) ) imagedestroy($tmp_img);
		$arrConfig = getimagesize($image);
		return $arrConfig;
	}
	public function getInfo($img) {
		$arrConfig = getimagesize($img);
		return $arrConfig;
	}
	
}

class Imagick {
	public $img;
	public $type;
	public $info;
		
	/*
	获取图片信息：宽度、高度、类型
	*/
	public function getInfo($img) {
		
		list($arrInfo['width'],$arrInfo['height']) =  explode('x',`identify -format "%Wx%H" $img`);
		$arrInfo['width'] = intval($arrInfo['width']);//原图宽
		$arrInfo['height'] = intval($arrInfo['height']);//原图高
		return $arrInfo;
	}
	
	/*
		图片水印
		$src_img:原图路径
		$logo:水印图路径
		$image:生成图保存路径
		$arrConfig['postion']:水印图位置
			'NorthWest' //西北
			'NorthEast' //东北
			'SouthWest' //西南
			'SouthEast' //东南
	*/
	public function watermark($src_img,$logo,$image,$arrConfig = array()) {
		$postion = isset($arrConfig['postion']) ? $arrConfig['postion'] : 'SouthEast';
		`composite -gravity $postion $logo $src_img  $image`;
	}
	
	/*
	文字水印
	*/
	public function watermark_str() {
		/*
		convert -font Arial -stroke green -fill red -draw "text 50,60 www.hist.edu.cn" -pointsize 14 07.jpg hist.png
		-draw “text 10,10  String"  在以图片左上角为原点坐标的10,10位置处添加文字
		-font 指定字体-stroke 描边用的颜色，-fill 填充用的颜色，这里用none就可以画出空心字了，-pointsize 字体像素大小，-font Arial 将注释的字体设置为Arial。也可以在此处指定字体文件的路径。但它是使用位于非标准位置的字体来完成该任务的：
		convert -font c:\windows\fonts\1900805.ttf -fill white -pointsize 36 -draw ‘text 10,475 “ylmf.com”’ floriade.jpg stillhq.jpg 
		-fill white 用白色而不是标准的黑色来填充字母。
		-pointsize 36 以点为单位指定字母的大小。一英寸等于 72 点。
		*/
		`convert -font fonts/1900805.ttf -fill white -pointsize 36 -draw 'text 10,475 "stillhq.com"' `;
	}
	
	/*
		基于imagemagick转换图片大小，目前只针对新图为jpg
		$src_img:原图路径
		$image:生成图保存路径
		$arrConfig['width']:生成图宽度
		$arrConfig['height']:生成图高度
		$arrConfig['quality']:图片压缩率
	*/
	public function resize($src_img,$image,$arrConfig) {
		$int_dst_w = isset($arrConfig[0]) ? $arrConfig[0] : $arrConfig['width'];
		$int_dst_h = isset($arrConfig[1]) ? $arrConfig[1] : $arrConfig['height'];
		$intQuality = isset($arrConfig['quality']) ? $arrConfig['quality'] : 80;//压缩率
		$arrInfo =  $this->getInfo($src_img);//原图宽高（像素）
		$retio_src = $arrInfo['width'] / $arrInfo['height'];//原图宽高比例
		
		if( $int_dst_w && $int_dst_h ) {
			$retio_dst = $int_dst_w / $int_dst_h;//目标图宽高比例
		}
		
		if( $retio_src == $retio_dst || !$int_dst_w || !$int_dst_h ) {
			//新图宽高比例不变，则等比例缩放
			//如果原图宽高大于$int_dst_w,$int_dst_h则进行转换
			if( !$int_dst_w ) {
				`convert -resize '$int_dst_h >' $src_img $image`;
			} else {
				`convert -resize '$int_dst_w >' $src_img $image`;
			}
			
		} elseif($retio_src > $retio_dst)  {
			//新图宽高比例小于原图比例，则先等比例缩放，再按指定的尺寸裁剪
			$retio = $arrInfo['height'] / $int_dst_h;
			$intWidth = $arrInfo['width'] / $retio;
			$x = ($intWidth - $int_dst_w) / 2 ;
			`convert -resize ' x $int_dst_h >' -crop '$int_dst_w x $int_dst_h + $x + 0 ' -quality $intQuality +profile "*" $src_img $image`;
		} elseif($retio_src < $retio_dst) {
			//新图宽高比例大于原图比例，则先等比例缩放，再按指定的尺寸裁剪
			$retio = $arrInfo['width'] / $int_dst_w;
			$intHeight = $arrInfo['height'] / $retio;
			$y = ($intHeight - $int_dst_h) / 2 ;
			`convert -resize ' $int_dst_w ' -crop '$int_dst_w x $int_dst_h + 0 + $y ' -quality $intQuality +profile "*" $src_img $image`;
		}
		$arrConfig =  $this->getInfo($image);//生成图宽高（像素）
		return $arrConfig;
	}
	
	/*
		基于imagemagick剪切图片
		$strPicSrc:原图路径
		$strPicDst:生成图保存路径
		$intW:生成图宽度
		$intH:生成图高度
	*/
	public function crop($strSrc,$strDst) {
		$strCommand = "convert $strSrc -crop '$intW x $intH >' $strDst";
		exec($strCommand);
	}
}