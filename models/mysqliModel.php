<?php
/*
作者:moyancheng
最后更新时间:2013-05-13
*/
class mysqliModel
{
	public $db = 0;
	
	public function __construct()
	{
		if(!($arrConfig = C('db','mysqli')))
		{
			echo '读取配置失败';exit;
		}
		$this->db = new mysqli($arrConfig['host'], $arrConfig['user'], $arrConfig['password'], $arrConfig['dbname']);

		if($this->db->connect_errno)
		{
			echo '连接到数据库出错，错误信息: '. mysqli_connect_error();exit;
		}
		
		$this->db->set_charset($arrConfig['charset']);
	}
	
	public function select($select,$table,$condition = '')
	{
		++$GLOBALS['arrRun']['db']['select']['totle'];
		$strSql = $condition == '' ?
			"select $select from `$table`":  
			"select $select from `$table` where $condition";
		
		if($reulst = $this->db->query($strSql))
		{
			return $reulst;
		}
		++$GLOBALS['arrRun']['db']['select']['error'];
		$this->error($strSql);
		return false;
	}
	/*
	一次插入一条数据：insert into `table` (`id`,`title`) values ('1','标题')；
	用第一种方法：
	$arrData = array('id'=>1,'title'=>'标题');
	$this->insert('table',$arrData); 
	一次插入多条数据：insert into `table` (`id`,`title`) values ('1','标题'),('2','标题2')；
	用第二种方法：
	$arrData = array(array('id'=>1,'title'=>'标题'),array('id'=>2,'title'=>'标题2'));
	$this->insert('table',$arrData,1); 
	*/
	public function insert($table,$arrDate,$true = false)
	{
		++$GLOBALS['arrRun']['db']['insert']['totle'];
		$name = $values = '';
		$flag = $flagV = 1;
		if($true)
		{
			foreach($arrDate as $arr)
			{
				$values .= $flag ? '(' : ',(';
				foreach($arr as $key => $value)
				{
					if($flagV)
					{
						if($flag) $name .= "`$key`";
						$values .= "'$value'";
						$flagV = 0;
					}
					else
					{
						if($flag) $name .= ",`$key`";
						$values .= ",'$value'";
					}
					
				}
				$values .= ') ';
				$flag = 0;
				$flagV = 1;
			}
		}
		else
		{
			foreach($arrDate as $key => $value)
			{
				if($flagV)
				{
					$name = "`$key`";
					$values = "('$value'";
					$flagV = 0;
				}
				else
				{
					$name .= ",`$key`";
					$values .= ",'$value'";
				}
				
			}
			$values .= ") ";
		}
		
		$strSql = "insert into `$table` ($name) values $values";
		if($reulst = $this->db->query($strSql))
		{
			return $reulst;
		}
		++$GLOBALS['arrRun']['db']['insert']['error'];
		$this->error($strSql);
		return false;
	}
	
	/*
	如果实现sql语句：update `table` set `title` = '新标题' where `id` = '1';
	按下边操作即可
	$arrData = array('title'=>'新标题');
	$this->update('table',$arrData,"`id` = '1'");
	*/
	public function update($table,$arrData,$condition)
	{
		$flag = 1;
		foreach($arrData as $key => $value)
		{
			if($flag)
			{
				$data = "`$key` = '$value'";
				$flag = 0;
			}
			else $data .= ",`$key` = '$value'";
		}
		++$GLOBALS['arrRun']['db']['update']['totle'];
		$strSql = "update `$table` set $data  where  $condition";
		if($reulst = $this->db->query($strSql))
		{
			return $reulst;
		}
		++$GLOBALS['arrRun']['db']['update']['error'];
		$this->error($strSql);
		return false;
	}
	
	//根据提供的表名与条件删除数据
	public function delete($table,$condition)
	{
		++$GLOBALS['arrRun']['db']['delete']['totle'];
		$strSql = "delete from `$table` where $condition";
		$this->db->query($strSql);
		if($reulst = $this->db->query($strSql))
		{
			return $reulst;
		}
		++$GLOBALS['arrRun']['db']['delete']['error'];
		$this->error($strSql);
		return false;
	}
	
	//返回最后插入数据的id
	public function insert_id()
	{
		return $this->db->insert_id;
	}

	//返回影响的行数。
	public function affected_rows()
	{
		return $this->db->affected_rows;
	}
	
	//断开连接
	public function __destruct()
	{
		if($this->db) $this->db->close();
	}
	
	//提示错误信息
	private function error($strSql)
	{
		echo '<div style="background: none repeat scroll 0 0 #FFFFB0;"><p><font style="color:red">运行出错:</font>',$strSql,
			'</p><p><font style="color:red">错误信息：</font>',$this->db->error,'</p></div>';
		return false;
	}
	
	public function autocommit($true = false)
	{
		$this->db->autocommit($true);
	}
	
	public function commit()
	{
		$this->db->commit();
	}
}

