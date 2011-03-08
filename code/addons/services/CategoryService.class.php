<?php
/**
 * 无限级分类
 * 
 * [备用]
 * 
 * @author thinksns
 *
 */
class CategoryService extends Service {
	private $namespace = 'default';
	private $order = 'DESC';
	private static $nodeCache = array ();
	
	public function setNamespace($namespace) {
		$this->namespace = $namespace;
		return $this;
	}

	/**
	 * 获得父分类
	 * @param unknown_type $id
	 */
	public function getParent($id, $path = true) {
		if ($path) {
			$result = $this->getPath ( $id, "<" );
		} else {
			if (isset ( self::$nodeCache [$id] )) {
				$data = self::$nodeCache [$id];
			} else {
				$data = $this->getNode ( $id );
			}
			$sql = "SELECT `name`,`tid`,`id`,`corder`,`cleft`,`cright`
				   FROM `ts_category`
				   WHERE `cleft` < {$data['cleft']} AND `cright` > {$data['cright']}
				   ORDER BY cleft DESC
				   LIMIT 1";
			$data = M ( '' )->query ( $sql );
			$result = $data[0];
		}
		return $result;
	}
	/**
	 * 获得子分类
	 * @param unknown_type $id
	 */
	public function getChildren($id, $self = false) {
		$result = $this->getPath ( $id, ">" );
		if ($self) {
			$data = $this->getNode($id);
			$data ['children'] = $result;
			$result = $data;
		}
		return $result;
	}
	
	public function getChidrenId($id){
		if (isset ( self::$nodeCache [$id] )) {
			$data = self::$nodeCache [$id];
		} else {
			$data = $this->getNode ( $id );
		}
		$sql = "SELECT `id`
				   FROM `ts_category`
				   WHERE `cleft` > {$data['cleft']} AND `cright` < {$data['cright']}
				   ORDER BY cleft";
		$data = M ( '' )->query ( $sql );
		if($data){
			$data = getSubByKey($data,'id');
			return $data;
		}else{
			return false;
		}
	}
	
	/**
	 * 获得同级的所有分类
	 * @param unknown_type $id
	 */
	public function getContent($id) {
		$parent = $this->getParent ( $id, false );
		return $this->getChildren($parent['id']);
	}
	/**
	 * 获得完整列表
	 */
	public function getList($id) {
		return $this->getChildren ( $id );
	}
	
	/**
	 * 增加子分类
	 */
	public function addChildren($id, $name,$tid = 0, $order = 0) {
		
		//修改其他节点
		$data = $this->getNode ( $id );
		if (! $data) {
			$left = 0;
			$right = 1;
		} else {
			if ($order == 0) {
				$right = $data ['cright'];
				$left = $data ['cleft'];
			} else {
				$children = $this->getChildren ( $id );
				
				//排序
				if (! $children) {
					$right = $data ['cright'];
					$left = $data ['cleft'];
				} else {
					foreach ( $children as $value ) {
						if ($value ['corder'] > $order)
							continue;
						$right = $value ['cright'];
						$left = $value ['cleft'];
					}
				}
			}
		}
		$sql1 = "UPDATE `ts_category` SET  `cleft` = cleft + 2 WHERE  (`cleft` > {$right}) AND (`namespace` = '{$this->namespace}')";
		$sql2 = "UPDATE `ts_category` SET `cright` = cright + 2 WHERE (`cright`>={$right}) AND (`namespace` = '{$this->namespace}')";
		//增加新的节点
		$result1 = M ( '' )->execute ( $sql1 );
		$result2 = M ( '' )->execute ( $sql2 );
		$left = $right + 1;
		$sql3 = "INSERT INTO `ts_category` SET `tid`={$tid},`corder` = {$order},`cleft` = {$right} , `cright` = {$left}, `name` = '{$name}',`namespace`= '{$this->namespace}' ";
		$result3 = M ( '' )->execute ( $sql3 );
		return M ( '' )->getLastInsID ();
	
	}
	
	/**
	 * 删除节点
	 * @param unknown_type $id
	 */
	public function remove($id) {
		if (isset ( self::$nodeCache [$id] )) {
			$data = self::$nodeCache [$id];
		} else {
			$data = $this->getNode ( $id );
		}
		//删除节点。包括子节点
		$sql = "DELETE FROM `ts_category` WHERE ( `cleft` >= {$data['cleft']} AND `cright` <= {$data['cright']}) AND (`namespace` = '{$this->namespace}')";
		//对后面的节点左右值重新计算
		$result = M('')->execute($sql);
		if($result){
			$step = $data['cright'] - $data['cleft'] + 1;
			$sql1 = "UPDATE `ts_category` SET  `cleft` = cleft - {$step} WHERE  (`cleft` > {$data['cright']}) AND (`namespace` = '{$this->namespace}')";
			$sql2 = "UPDATE `ts_category` SET `cright` = cright - {$step} WHERE (`cright`>={$data['cright']}) AND (`namespace` = '{$this->namespace}')";
			
			$result1 = M('')->execute($sql1);
			$result2 = M('')->execute($sql2);
			$result = $result1 == $result2 ? true:false;
		}
		return $result;
		
	}
	
	/**
	 * 移动节点
	 * @param unknown_type $data
	 */
	public function move($id,$targetId,$order = 0) {
		//获取两个分类信息。
		$selfCategory = $this->getNode($id);
		$newCategory  = $this->getNode($targetId);
		
		$chidren = $this->getChidrenId($id);
		//处理需要移动的Id
		$ids[] = $selfCategory['id'];
		if($chidren){
			$ids = array_merge($ids,$chidren);
		}

		$ids = implode(',',$ids);
		$parentLft=$newCategory['cleft']; 
		$parentRgt=$newCategory['cright']; 
		$selfLft=$selfCategory['cleft']; 
		$selfRgt=$selfCategory['cright'];
		$value=$selfRgt-$selfLft;
		
		//判断是移到左边。还是移到右边
		if($parentRgt > $selfLft){  //右边
			$step = $value + 1;
			$tmpValue=$parentRgt-$selfRgt-1; 
			$updateLeft  = "UPDATE `ts_category` SET  `cleft` = cleft - {$step} WHERE  (`cleft` > {$selfRgt})  AND (`cright` <= {$parentRgt}) AND (`namespace` = '{$this->namespace}')";
			$updateRight = "UPDATE `ts_category` SET  `cright` = cright - {$step} WHERE  (`cright` > {$selfRgt})  AND (`cright` < {$parentRgt}) AND (`namespace` = '{$this->namespace}')";
			$updateSelf = "UPDATE `ts_category` SET  `cright` = cright + {$tmpValue},`cleft` = cleft+{$tmpValue}
						   WHERE  `id` IN ($ids)";
		}else{    //左边
			$step = $value + 1;
			$tmpValue=$parentRgt-$selfRgt; 
			$updateLeft  = "UPDATE `ts_category` SET  `cleft` = cleft + {$step} WHERE  (`cleft` > {$parentRgt})  AND (`cleft` < {$selfLeft}) AND (`namespace` = '{$this->namespace}')";
			$updateRight = "UPDATE `ts_category` SET  `cright` = cright + {$step} WHERE  (`cright` > {$parentRgt})  AND (`cright` < {$selfLft}) AND (`namespace` = '{$this->namespace}')";
			$updateSelf = "UPDATE `ts_category` SET  `cright` = cright - {$tmpValue},`cleft` = cleft-{$tmpValue}
						   WHERE  `id` IN ($ids)";
		}
		$result1 = M('')->execute($updateLeft);
		//dump(M('')->getLastSql());
		$result2 = M('')->execute($updateRight);
		//		dump(M('')->getLastSql());
		
		$result3 = M('')->execute($updateSelf);
		//		dump(M('')->getLastSql());
		
		return 1;		
	}
	
	private function format($data) {
		foreach ( $data as $key1 => $value ) {
			$list [] = $value;
			foreach ( $list as $key2 => $v ) {
				if ($value ['cleft'] > $v ['cleft'] && $value ['cright'] < $v ['cright']) {
					$list [$key2] ['chidren'] [] = $value;
					array_pop ( $list );
				}
			}
		}
		foreach ( $list as &$value ) {
			if (isset ( $value ['chidren'] )) {
				$value ['chidren'] = $this->format ( $value ['chidren'] );
			}
		}
		return $list;
	}
	
	/**
	 * 获得路径
	 * @param unknown_type $id
	 */
	private function getPath($id, $method) {
		$tempMethod = $method == "<" ? ">" : "<";
		if (isset ( self::$nodeCache [$id] )) {
			$data = self::$nodeCache [$id];
		} else {
			$data = $this->getNode ( $id );
		}
		$sql = "SELECT `name`,`id`,`corder`,`cleft`,`cright`
				   FROM `ts_category`
				   WHERE `cleft` {$method} {$data['cleft']} AND `cright` {$tempMethod} {$data['cright']}
				   ORDER BY cleft";
		$data = M ( '' )->query ( $sql );
		return $this->format ( $data );
	}
	
	private function getNode($id) {
		$sql = "SELECT `name`,`id`,`corder`,`cleft`,`cright`
				FROM `ts_category`
				WHERE `id`={$id}";
		$result = M ( '' )->query ( $sql );
		self::$nodeCache [$id] = $result [0];
		return $result [0];
	}
	
	//服务初始化
	public function init($data = '') {
	}
	
	//运行服务，系统服务自动运行
	public function run() {
	}
}
?>