<?php
/*
作者:moyancheng
创建时间:2013-04-01
最后更新时间:2015-07-02
*/

class pdoModel {
	public $db = 0;
	public $result = 0;
	public $select;
	public $table;
	public $condition;
	public $order;
	public $limit;
	public $sql;

    public $link;
    public $user;
    public $pass;
    public $charset;
	
	public function __construct($arrConfig) {
		mPHP::inc( MPHP_PATH.'inc/functions.php' );//加载常用函数集
		$type = $arrConfig['type'];
		$name = $arrConfig['dbname'];
		$host = $arrConfig['host'];
		$port = $arrConfig['port'];

		$this->link = "{$type}:host={$host};dbname={$name}";
		$this->user = $arrConfig['user'];
		$this->pass = $arrConfig['password'];
		$this->charset = $arrConfig['charset'];
		$this->connect();
	}

	public function connect() {
		try {
			//$this->db = new PDO($link, $user, $pass);//短链接
			$this->db = new PDO($this->link, $this->user, $this->pass, array(PDO::ATTR_PERSISTENT => true));//长链接
		} catch( PDOException $e ) {
			//echo '网站正在进行更新升级操作，为此给您带来的不便，请谅解...';
			$this->db = false;
			$err = 'PDO Connection failed: ' . $e->getMessage();
			mlog($err,LOG_PATH.'pdo_err.log');
			return false;
		}
		if( $this->charset ) {
			$this->db->exec('set names '.$this->charset);
		}
		return true;
	}

	//检测PDO是否与数据库断开连接
	public function checkConnection() {
		$msg = $this->db->getAttribute(PDO::ATTR_SERVER_INFO);
		if( $msg == 'MySQL server has gone away' || $msg === false) {
			mlog($msg);
			return false;//连接已断开
		}
		return true;
	}

	private function quoteIdentifier($name) {
		if( preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) ) {
			return "`{$name}`";
		}
		return $name;
	}

	private function quoteValue($value) {
		if( $value === null ) return 'NULL';
		if( is_bool($value) ) return $value ? '1' : '0';
		if( is_int($value) || is_float($value) ) return (string)$value;
		if( $this->db ) return $this->db->quote((string)$value);
		return "'" . addslashes((string)$value) . "'";
	}

	private function buildCondition($condition) {
		if( !is_array($condition) ) return $condition;
		$parts = [];
		foreach($condition as $key => $val) {
			$op = '=';
			$value = $val;
			if( is_array($val) ) {
				$op = isset($val['op']) ? strtoupper(trim($val['op'])) : $op;
				$value = array_key_exists('value',$val) ? $val['value'] : null;
			}

			$column = $this->quoteIdentifier($key);
			if( $value === null ) {
				if( $op === '!=' || $op === '<>' ) {
					$parts[] = "{$column} IS NOT NULL";
				} else {
					$parts[] = "{$column} IS NULL";
				}
				continue;
			}

			if( $op === 'IN' && is_array($value) ) {
				$in_vals = [];
				foreach($value as $v) $in_vals[] = $this->quoteValue($v);
				$parts[] = "{$column} IN (" . implode(',', $in_vals) . ")";
				continue;
			}

			$allowed_ops = ['=','>','<','>=','<=','!=','<>','LIKE'];
			if( !in_array($op,$allowed_ops,true) ) $op = '=';
			$parts[] = "{$column} {$op} " . $this->quoteValue($value);
		}
		return implode(' AND ', $parts);
	}
	
	public function query($strSql) {
		if( $this->db ) {
			$result = $this->db->query($strSql);
			if($result === false) {
				if( $this->checkConnection() === false ) {
					//连接已断开，重新连接数据库
					if( $this->connect() ) {
						$result = $this->db->query($strSql);//重新执行一次sql语句
					}
				}
			}

			$this->sql = $strSql;
			$this->result = $result;
			return $this;
		} else {
			goto_503();
		}
	}

	public function exec($strSql) {
		if( $this->db ) {
			$result = $this->db->exec($strSql);
			if($result === false) {
				if( $this->checkConnection() === false ) {
					//连接已断开，重新连接数据库
					if( $this->connect() ) {
						$result = $this->db->exec($strSql);//重新执行一次sql语句
					}
				}
			}

			$this->sql = $strSql;
			$this->result = $result;
			return $result;
		} else {
			goto_503();
		}
	}
	
	//查询数据
	public function select($select,$table,$condition = '',$order = array(), $limit = '') {
		if( $this->db ) {
			++$GLOBALS['CFG']['db']['select']['totle'];
			$condition = $this->buildCondition($condition);
			if( is_array($order) && isset($order['order']) && ( $order['order'] == 'desc' || $order['order'] == 'asc' ) && isset($order['field']) ) {
				$order = "order by $order[field] $order[order]";
			} elseif( is_string($order) ) {
				$order = "order by {$order}";
			} else $order = '';
			$this->sql = $strSql = $condition == '' ?
				"select $select 
				from $table 
				$order 
				$limit"	
				:  
				"select $select 
				from $table 
				where $condition 
				$order 
				$limit";
			if($this->result = $this->db->query($strSql)) {
				return $this;
			}
			++$GLOBALS['CFG']['db']['select']['error'];
			$this->error($strSql);
			return $this;
		} else {
			goto_503();
		}
	}
	//一次性获取数据
	public function fetch_all() {
		$arrData = array();
		if( $this->result ) {
			$arrData = $this->result->fetchAll(PDO::FETCH_ASSOC);
		}
		return $arrData;
	}
	
	public function fetch() {
		$arrData = array();
		if( $this->result ) {
			$arrData = $this->result->fetch(PDO::FETCH_ASSOC);
		}
		return $arrData;
	}
	
		/*
	一次插入一条数据：insert into table (id,title) values ('1','标题')；
	用第一种方法：
	$arrData = array('id'=>1,'title'=>'标题');
	$this->insert('table',$arrData); 
	一次插入多条数据：insert into table (id,title) values ('1','标题'),('2','标题2')；
	用第二种方法：
	$arrData = array(array('id'=>1,'title'=>'标题'),array('id'=>2,'title'=>'标题2'));
	$this->insert('table',$arrData,1); 
	*/
	public function insert($table,$arrData,$true = false) {
		if( $this->db ) {
			++$GLOBALS['CFG']['db']['insert']['totle'];
			$name = $values = '';
			$flag = $flagV = 1;
			if($true) {
				foreach($arrData as $arr) {
					$values .= $flag ? '(' : ',(';
					foreach($arr as $key => $value) {
						if($flagV) {
							if($flag) $name .= $this->quoteIdentifier($key);
							$values .= $this->quoteValue($value);
							$flagV = 0;
						} else {
							if($flag) $name .= ',' . $this->quoteIdentifier($key);
							$values .= ',' . $this->quoteValue($value);
						}
					}
					$values .= ') ';
					$flag = 0;
					$flagV = 1;
				}
			} else {
				foreach($arrData as $key => $value) {
					if($flagV) {
						$name = $this->quoteIdentifier($key);
						$values = '(' . $this->quoteValue($value);
						$flagV = 0;
					} else {
						$name .= ',' . $this->quoteIdentifier($key);
						$values .= ',' . $this->quoteValue($value);
					}
				}
				$values .= ") ";
			}
			
			$this->sql = $strSql = "insert into $table ($name) values $values";
			if( ($this->result = $this->exec($strSql) ) > 0 ) {
				return $this;
			}
			++$GLOBALS['CFG']['db']['insert']['error'];
			$this->error($strSql);
			return false;
		} else {
			goto_503();
		}
	}
	public function insert2($table,$arrData) {
		if( $this->db ) {
			++$GLOBALS['CFG']['db']['insert']['totle'];
			$name = $values = '';
			$flag = $flagV = 1;
			$true = is_array( current($arrData) );
			if($true) {
				foreach($arrData as $arr) {
					$values .= $flag ? '(' : ',(';
					foreach($arr as $key => $value) {
						if($flagV) {
							if($flag) $name .= $this->quoteIdentifier($key);
							$values .= $this->quoteValue($value);
							$flagV = 0;
						} else {
							if($flag) $name .= ',' . $this->quoteIdentifier($key);
							$values .= ',' . $this->quoteValue($value);
						}
					}
					$values .= ') ';
					$flag = 0;
					$flagV = 1;
				}
			} else {
				foreach($arrData as $key => $value) {
					if($flagV) {
						$name = $this->quoteIdentifier($key);
						$values = '(' . $this->quoteValue($value);
						$flagV = 0;
					} else {
						$name .= ',' . $this->quoteIdentifier($key);
						$values .= ',' . $this->quoteValue($value);
					}
				}
				$values .= ") ";
			}
			
			$this->sql = $strSql = "insert into $table ($name) values $values";
			if( ($this->result = $this->db->exec($strSql) ) > 0 ) {
				return $this;
			}
			++$GLOBALS['CFG']['db']['insert']['error'];
			$this->error($strSql);
			return false;
		} else {
			goto_503();
		}
	}
	
	/*
	如果实现sql语句：update table set title = '新标题' where id = '1';
	按下边操作即可
	$arrData = array('title'=>'新标题');
	$this->update('table',$arrData,"id = '1'");
	*/
	public function update($table,$arrData,$condition) {
		if( $this->db ) {
			$flag = 1;
			foreach($arrData as $key => $value) {
				if($flag) {
					$data = $this->quoteIdentifier($key) . ' = ' . $this->quoteValue($value);
					$flag = 0;
				} else $data .= ',' . $this->quoteIdentifier($key) . ' = ' . $this->quoteValue($value);
			}
			$condition = $this->buildCondition($condition);
			++$GLOBALS['CFG']['db']['update']['totle'];
			$this->sql = $strSql = "update $table set $data where $condition";
			$this->result = $this->exec($strSql);
			if( $this->result !== false) {
				return $this->result;
			}
			++$GLOBALS['CFG']['db']['update']['error'];
			$this->error($strSql);
			return false;
		} else {
			goto_503();
		}
	}
	
	//根据提供的表名与条件删除数据
	public function delete($table,$condition) {
		if( $this->db ) {
			++$GLOBALS['CFG']['db']['delete']['totle'];
			$condition = $this->buildCondition($condition);
			$this->sql = $strSql = "delete from $table where $condition";
			$this->result = $this->exec($strSql);
			if( $this->result !== false) {
				return $this->result;
			}
			++$GLOBALS['CFG']['db']['delete']['error'];
			$this->error($strSql);
			return false;
		} else {
			goto_503();
		}
	}
	
	//返回最后插入数据的id
	public function insert_id($flag = '') {
		return $this->db->lastInsertId($flag);
	}
	
	//提示错误信息
	private function error($strSql) {
		$errorInfo = $this->db ? $this->db->errorInfo() : array();
		$errMsg = isset($errorInfo[2]) ? $errorInfo[2] : 'Unknown database error';
		if( class_exists('mPHP') ) {
			mPHP::log('ERROR', 'SQL_ERROR: ' . $errMsg, array('sql' => $strSql, 'error_info' => $errorInfo));
		}
		if( isset($GLOBALS['CFG']['debug']) && $GLOBALS['CFG']['debug'] ) {
			echo "<div style='color:red;'>SQL_ERROR: {$errMsg}</div><br>SQL: {$strSql}";
		}
	}
	
	/*
	功能：根据写好的sql语句初始化数据库
	$file:sql文件
	*/
	public function initDb($file) {
		global $CFG;
		$strSql = file_get_contents($file);//获取sql语句
		$strSql = str_replace('@table_prefix@',$CFG['table_prefix'],$strSql);//替换数据表前缀
		$strSql = str_replace('@engine@',$CFG['engine'],$strSql);//替换数据表引擎
		$strSql = str_replace('@charset@',$CFG['charset'],$strSql);//替换数据表字符集
		//$strSql = preg_replace('/#.+\n/','',$strSql);//过滤注释
		$arrSql = explode(';',$strSql);
		$this->beginTransaction();
		foreach( $arrSql as $strSql) $this->query($strSql);//写入数据库
		$this->commit();
	}
	
	/*
	功能：
	1：提高执行多条exec语句效率
	2：设定回滚起点
	*/
	public function beginTransaction() {
		return $this->db->beginTransaction();
	}
	/*
	功能：
	1：提高执行多条exec语句效率
	2：如果出错，则回滚到起点
	*/
	public function commit() {
		if( $this->db->commit() !== TRUE ) {
			 $this->rollBack();
			 return false;
		} else {
			return true;
		}
	}
	
	/*
	功能：回滚到起点
	*/
	public function rollBack(){
		return $this->db->rollBack();
	}

}
