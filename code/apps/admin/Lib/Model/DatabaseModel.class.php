<?php
class DatabaseModel extends Model {
	
	public function getTableList() {
		return M('')->query('SHOW TABLE STATUS LIKE "' . C('DB_PREFIX') . '%"');
	}
	
	public function getTableSql($table, $startfrom=0, $filesize,$currentsize,$complete=true) {
		$tabledump		= '';
		$offset			= 200;
		$tablefields	= array();
		
		$tablefields = M('')->query('SHOW FULL COLUMNS FROM '.$table);
		if(!$tablefields) 
			return false;
			
		if($startfrom == 0)  {
			$createtable	= M('')->query('SHOW CREATE TABLE '.$table);
			$tabledump	   .= "DROP TABLE IF EXISTS $table;\n";
			$tabledump	   .= $createtable[0]['Create Table'].";\n\n";
		}
		
		$first_field	= $tablefields[0];
		$numrows		= $offset;
		while ($currentsize + strlen($tabledump) + 500  < $filesize && $numrows == $offset) {
			
			if ($first_field['Extra'] == 'auto_increment') {
				$sql = 'SELECT * FROM '. $table ." WHERE ".$first_field['Field']." > $startfrom LIMIT $offset";
			}else{
				$sql = 	'SELECT *FROM '. $table ." LIMIT  $startfrom,$offset";
			}
			
			$tableData	= $this->query($sql);
			$numrows	= count($tableData);
			
			if ($numrows && $tableData) {
				$linkid	= $this->db->connect();
				$query	= mysql_query($sql);
				
				while ($oneRow = mysql_fetch_assoc($query)) {
					$dumpsql = $comma = '';
					foreach($oneRow as $field => $value){
						$dumpsql .= $comma."'".mysql_escape_string($value)."'";
						$comma = ',';
					}
					
					if(strlen($dumpsql)+$currentsize+strlen($tabledump)+500 < $filesize){
							if($first_field['Extra'] == 'auto_increment') {
								$startfrom = $oneRow[$first_field['Field']];
							} else {
								$startfrom ++;
							}
							$tabledump .= 'INSERT INTO '.$table." VALUES ($dumpsql);\n";
							
					}else{
						$complete = false;
						break 2;
					}
				} // END while
			}
		} // END while
		
		$tabledump .= "\n";
		return array('complete'=>$complete,'startform'=>$startfrom,'tabledump'=>$tabledump);
	}
}