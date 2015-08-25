<?php
/*
作者:moyancheng
最后更新时间:2013-05-13
最后更新时间:2014-08-13

error_reporting(E_ALL ^ E_NOTICE);
$a = new pageModel();

$a->intPage = $_GET['page'];
$a->intPages = 8;
$a->intTotalData =  4;
$a->intPageData = 1;
$a->strUrl = 'pageModel.php?';
$a->true = false;
$p = $a->type(1);

或

$a->set($_GET['page'],8,4,1,'pageModel.php?');
$p->get();

echo $p;
*/
class pageModel
{
	public $intPage;//当前页码
	public $intPages;//页面最多能显示的页码数
	public $intTotalData;//总数据量
	public $intPageData;//每页显示数据量
	public $strUrl;//翻页处理程序url
	public $true;//默认关闭地址重写功能,如果开启地址重写，可把该值设为array('suffix' => '.html')
	
	public function __construct()
	{
		$this->true = array();
	}
	
	public function set($intPage,$intPages,$intTotalData,$intPageData,$strUrl,$true = false)
	{
		$this->intPage = $intPage;
		$this->intPages = $intPages;
		$this->intTotalData = $intTotalData;
		$this->intPageData = $intPageData;
		$this->strUrl = $strUrl;
		$this->true = $true;
	}
	
	public function get($intType = 1)
	{
		return $this->type($intType);
	}
	
	public function type($intType)
	{
		switch($intType)
		{
			case 1:
				return $this->page1();
				break;
		}
	}
	
	/*
	功能：生成翻页html代码
	$this->intPage：当前页码
	$this->intPages：页面最多能显示的页码数 - 2
	$this->intTotalData：总数据量
	$this->intPageData：每页显示数据量
	$this->strUrl：翻页处理程序
	$true：默认关闭地址重写功能,如果没开启地址重写，可把该值设为false
		例：www.mphp.com?page=1  默认生成地址为www.mphp.com/1
	*/
	//function page1($this->intPage,$this->intPages,$this->intTotalData,$this->intPageData,$this->strUrl,$true = true)
	public function page1() {
		if( !empty($this->true) && isset(mPHP::$CFG['url_type']) ) {
			$suffix = $this->true['suffix'];
			$this->strUrl .= '/';
		} else {
			$this->strUrl .= '&page=';//翻页处理程序
		}
		$strHtmlHead = $strHtmlMain = $strHtmlFoot = '';//翻页HTML代码头、主、尾
		$intTotalPage = ceil( $this->intTotalData / $this->intPageData );//总页数
		if($intTotalPage < 2 ) return false;//小于2页不显示分页
		$intBegin = $intEnd = 1;
		//防止当前选中页出错
		if($this->intPage < 1) $this->intPage = 1;
		elseif($this->intPage > $intTotalPage) $this->intPage = $intTotalPage;
		
		$intRange = $this->intPages / 2;
		$intBegin = $this->intPage - $intRange;
		if($intBegin < 1) $intBegin = 1;
		$intEnd = $this->intPage + $intRange;
		if($intEnd < $this->intPages + 1) $intEnd = $this->intPages + 1;
		
		if($intTotalPage < $intEnd) $intEnd = $intTotalPage;
		
		if($intEnd - $intBegin < $this->intPages) {
			$intBegin -= $this->intPages - ($intEnd - $intBegin);
			if($intBegin < 1) $intBegin = 1;
		}
		if($intEnd - $intBegin + 1 > $this->intPages) $intEnd = $intBegin + $this->intPages - 1;
		if ($this->intPage <= $intRange + 1) {
			if($this->intPage == 1)
		 		$strHtmlHead .= "<span class='last'>上一页</span>\n<span class='selected'>1</span>\n";
			elseif($this->intPage > 1)
				$strHtmlHead .= "<a href='" . $this->strUrl . ($this->intPage - 1) . $suffix . "'>上一页</a>
					<a href='" . $this->strUrl ."1{$suffix}'>1</a>\n";
			
			if($intTotalPage > $this->intPages + 1)
	 			$strHtmlFoot .= "<a href='" . $this->strUrl . $intTotalPage . $suffix . "'>...{$intTotalPage}</a>
	 				<a href='" . $this->strUrl . ($this->intPage + 1) . $suffix . "'>下一页</a>\n";
	 		else {
	 			if($intTotalPage == 1) $strHtmlFoot .= "<span class='next'>下一页</span>\n";
	 			elseif($intTotalPage == $this->intPage)  $strHtmlFoot .= "<span class='selected'>{$this->intPage}</span><span class='next'>下一页</span>\n";
	 			else $strHtmlFoot .= "<a href='" . $this->strUrl . $intTotalPage . $suffix . "'>{$intTotalPage}</a>
	 				<a href='" . $this->strUrl . ($this->intPage + 1) .  $suffix . "'>下一页</a>\n";
			}
		} else {
			if($intTotalPage > $this->intPages + 1)
	 			$strHtmlHead .= "<a href='" . $this->strUrl . ($this->intPage - 1). "{$suffix}'>上一页</a><a href='" . $this->strUrl ."1{$suffix}'>1...</a>\n";
	 		else
	 			$strHtmlHead .= "<a href='" . $this->strUrl . ($this->intPage - 1). "{$suffix}'>上一页</a><a href='" . $this->strUrl ."1{$suffix}'>1</a>\n";
	 			
	 		if($this->intPage + $intRange < $intTotalPage)
				$strHtmlFoot .= "<a href='" . $this->strUrl . $intTotalPage . $suffix . "'>...{$intTotalPage}</a><a href='" . $this->strUrl . ($this->intPage + 1) . $suffix . "'>下一页</a>\n";
			elseif($this->intPage < $intTotalPage)
				$strHtmlFoot .= "<a href='" . $this->strUrl . $intTotalPage . $suffix . "'>{$intTotalPage}</a><a href='" . $this->strUrl . ($this->intPage + 1) . $suffix . "'>下一页</a>\n";
			elseif($this->intPage == $intTotalPage)
				$strHtmlFoot .= "<span class='selected'>{$intTotalPage}</span><span class='next'>下一页</span>\n";
		}
		for($i = $intBegin;$i <= $intEnd && $i < $intTotalPage; ++$i) {
			if($i == 1) continue;
			if($i == $this->intPage) $strHtmlMain .= "<span class='selected'>{$i}</span>\n";
			else $strHtmlMain .= "<a href='$this->strUrl{$i}{$suffix}'>{$i}</a>\n";
		}
		//$strHtml = $strHtmlHead . $strHtmlMain . $strHtmlFoot . "<span> 共".$intTotalPage."页 </span><span>到第<input id='jumpPage' type='text' class='next_input'>页</span>\n";
		$strHtml = $strHtmlHead . $strHtmlMain . $strHtmlFoot . "<span> 共".$intTotalPage."页 </span>\n";
		return $strHtml;
	}
}