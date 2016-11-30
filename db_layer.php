<?

/**
 * undocumented class
 *
 * @package default
 * @author
 **/

class Database
{
	var $link = null;

	function Database($Server, $User, $Password, $DB)
	{
		$this->link = mysqli_connect($Server, $User, $Password)
			or die(mysqli_error());

		mysqli_select_db($this->link, $DB)
		or die(mysqli_error());
	}


	public function query($Q)
	{

		$r = mysqli_query($this->link, $Q)
			or die(mysql_error());
		return $r;
	}

	function getObject($r)
	{
		$o = mysqli_fetch_object($r);
		return $o;
	}

	function getArray($r)
	{
		$o = mysqli_fetch_assoc($r);
		return $o;
	}

	function numRecords($r)
	{
		$n = mysqli_num_rows($r);
		return $n;
	}

	function encrypt($password)
	{
		return crypt(md5($password), md5($password));
	}

	function encode( $str )
	{

		return trim( mysqli_real_escape_string( $this->link, htmlentities( $str ) ) );
	}

	function decode( $str )
	{
		return stripslashes(html_entity_decode( $str ));
	}

	function lastInsertId()
	{

		return mysqli_insert_id($this->link);
	}


	function saveRecord($fields, $table, $pk='id')
	{

		if( isset($fields[$pk]) && $this->isExists($table,$pk,$fields[$pk]) )
		{
			$sql = "UPDATE `$table` SET ";

			foreach($fields as $k=>$v){
				if($k==$pk) continue;
				else if($v==='now()')
					$sql .= "`$k` = now(), ";
				else
					$sql .= "`$k` = '".trim( mysqli_real_escape_string($this->link, $v))."', ";
			}
			$sql = rtrim($sql,", ");

			if(is_array($fields[$pk]) && !empty($fields[$pk]) )
				$sql .= " where `$pk` in ('".implode("','",$fields[$pk]) ."') ";
			else
				$sql .= " where `$pk` = '".trim( mysqli_real_escape_string($this->link, $fields[$pk]))."'";
			//echo $sql; return $fields[$pk];
			$this->query($sql);

			return $fields[$pk];
		}
		else
		{
			$sql = "INSERT INTO `$table` (";
			$values = ") VALUES (";

			foreach($fields as $k=>$v)
			{
				$sql .= "`$k`, ";
				if($v==='now()')
					$values .= " now(), ";
				else
					$values .= "'".trim( mysqli_real_escape_string($this->link, $v))."', ";
			}

			$sql = rtrim($sql,", ");
			$values = rtrim($values,", ").")";
			//echo $sql.$values;		return 1;
			if($this->query($sql.$values))
				return $this->lastInsertID();
			else
				return NULL;
		}
	}


	/*
	* $selectedValue: whose value you require, a table column
	* $table: table name
	* $where: where clause
	* $returnValue: if no record found it will $return be returned
	*/
	function getFieldValue($selectedValue, $table, $where, $returnValue=false )
	{
		$r = $this->query("select `$selectedValue` from `$table` where $where");
		if( $this->numRecords($r)>0 )
		{
			$o = $this->getObject($r);
			return $this->decode($o->$selectedValue);
		}
		else
			return $returnValue;
	}

	/*
	* $from: table name
	* $where: where clause
	* return: number of rows
	*/
	function getCount($from, $where='')
	{
		if( !empty($where) ) {
			if(is_array($where)){
				$final_where = array();;
				foreach($where as $field=>$val)
					$final_where[] = "{$field} = '{$val}' ";
				$where = " where ".implode(' AND ',$final_where);
			}
			else
				$where = " where $where";
		}

		//echo "select count(*) as rows from `$from` $where";
		$r = $this->query("select count(*) as rows from $from $where");
		$o = $this->getObject($r);
		return $o->rows;
	}

	/*
	* $select: select clause
	* $from: table name
	* $where: where clause
	* $order_by: order by clause e.g. 'name asc'
	* $start: starting record for limits
	* $records_per_page: number of records after $start
	* $do_paging: if set do paging and return ARRAY. if set then It ignor "start"
	* return: db result set
	*/
	function getResults($select, $from, $where='', $order_by=NULL, $start=NULL, $records_per_page=10 )
	{
		if( !empty($where) ) {
			if(is_array($where)){
				$final_where = array();;
				foreach($where as $field=>$val)
					$final_where[] = "{$field} = '{$val}' ";
				$where = " where ".implode(' AND ',$final_where);
			}
			else
				$where = " where $where";
		}

		//if($group_by !== NULL)			$where .= " group by $group_by";

		if(!empty($order_by))
			$where .= " order by $order_by";

		if($start <0)
			$start = 0;

		if($start !== NULL)		$where .= " limit $start, $records_per_page ";

		$sql = "select $select from $from $where ";
		//echo $sql;
		/*if ($do_paging == "Y")
			return custom_paging($sql, $records_per_page, $user_friendly_url);
		else*/
		{
			$r = $this->query($sql);
			return $r;
		}
	}

	function getResult($select, $from, $where='', $order_by=NULL,$returnObj=true)
	{
		$results = $this->getResults($select, $from, $where, $order_by);
		if(!$results)
			return $results;

		return $returnObj ? $this->getObject($results) : $this->getArray($results);
	}


	function deleteRecord($table, $id, $condition='', $pk='id') //condition will start with "and"
	{
		$sql = "delete from $table where ".$pk."='" . $this->encode($id) . "' " . $condition;

		if( $this->query( $sql ) )
			return true;
		else
			return false;
	}

	function SoftDeleteRecord($table, $id, $pk='id', $softDelete=true, $condition='')
	{
		if(!empty($condition))
			$condition = ' and '.$condition;

		if($softDelete)
			$sql = "update $table set is_deleted='1' where ".$pk."='" . $this->encode($id) . "' " . $condition;
		else
			$sql = "delete from $table where ".$pk."='" . $this->encode($id) . "' " . $condition;
		if( Query( $sql ) )
			return true;
		else
			return false;
	}

	function isExists( $table, $col, $val, $attr='' )
	{
		$where = "where `$col`='".$this->encode($val)."' ";
		if(!empty($attr))
			$where .= " and $attr";

		$sql = "select `$col` from `$table` $where ";
		$rp = $this->query( $sql );
		if( $this->numRecords( $rp ) > 0 )
			return true;
		return false;
	}

} // END Database class
?>
