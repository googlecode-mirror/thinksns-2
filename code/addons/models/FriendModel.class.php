<?php
/**
 * 好友模型
 * 
 * @author daniel <desheng.young@gmail.com>
 */
class FriendModel extends Model {
	protected $tableName = 'friend';
	protected $default_group_name = '未分组';

	/**
	 * 查询好友表
	 * 
	 * @param array|string $map   查询条件
	 * @param string       $field 默认*
	 * @param int          $limit 默认空
	 * @param string       $order 默认空
	 * @param boolean      $is_find_page 是否分页,默认true
	 * @return array
	 */
	public function getFriedByMap($map = array(), $field = '*', $limit = '', $order = '', $is_find_page = true) {
		if ($is_find_page) {
			return $this->where($map)->field($field)->order($order)->findPage($limit);
		}else {
			return $this->where($map)->field($field)->order($order)->limit($limit)->findAll();
		}
	}

	/**
	 * 获取好友列表
	 * 
	 * @param int $uid 				用户ID
	 * @param int $friend_group_id  好友分组ID 默认null则不分组查找
	 * @param int $limit 			默认20
	 * @param string $order 		默认 以好友名字升序，好友ID升序
	 * @return array
	 */
	public function getFriendList($uid, $friend_group_id = null, $limit = 20, $order = 'friend_uname ASC, friend_uid ASC') {
		if ( !isset($friend_group_id) ) {
			return $this->where("`uid`=$uid AND `status`=1")->order($order)->findPage($limit);
		}else {
			return M('friend_group_link')->where("`uid`=$uid AND `friend_group_id`=$friend_group_id AND `status`=1")
										 ->order($order)
										 ->findPage($limit);
		}
	}

	/**
	 * 添加好友
	 * 
	 * @param int     $uid 					 用户ID
	 * @param int     $friend_uid 			 好友ID
	 * @param array   $friend_group_id 		 好友分组ID
	 * @param boolean $require_authorization 是否需要请求,false:不需要,true:需要
	 * @param string  $message 				 请求信息
	 * @return boolean
	 */
	public function addFriend($uid, $friend_uid, $friend_group_id = 0, $require_authorization = false, $message = '') {
		//ts_friend
		$data['uid'] 			= $uid;
		$data['friend_uid'] 	= $friend_uid;
		$data['friend_uname'] 	= getUserName($friend_uid);
		$data['status'] 		= $require_authorization ? 0 : 1;
		$data['message']		= $message;
		$data['ctime']			= time();
		$res = $this->add($data);
		
		//ts_friend_group_link
		unset($data['message']);
		$friend_group_id = $friend_group_id === 0 ? array('0') : $friend_group_id;
		
		foreach ($friend_group_id as $v) {
			unset($data['friend_group_id']);
			$data['friend_group_id'] = $v;
			M('friend_group_link')->add($data);
		}
		
		return $res;
	}

	/**
	 * 接受好友请求
	 *  
     * @param int   $uid			 用户ID
     * @param int   $friend_uid		 好友ID
     * @param array $friend_group_id 好友分组ID
     * @return boolean
	 */
	public function acceptFriend($uid, $friend_uid, $friend_group_id = 0) {
		
	}

	/**
	 * 删除好友
	 * 
	 * @param int     $uid 用户ID
	 * @param int     $friend_uid 好友ID
	 * @param boolean $require_authorization 是否发送通知,双方面删除
	 * @return boolean
	 */
	public function deleteFriend($uid, $friend_uid, $require_authorization = false) {
		if ($require_authorization) {
			//双方面删除
			//发送通知
		}else {
			//单方面删除
		}
	}

	/**
	 * 判断是否为好友
	 * 
	 * @param int $uid        用户ID
	 * @param int $friend_uid 对方ID
	 * @return boolean
	 */
	public function isFriends($uid, $friend_uid) {
		return $this->where("`uid`=$uid AND `friend_uid`=$friend_uid AND `status`=1")->find();
	}

	/**
	 * 获取给定用户的所有好友分组
	 * 
	 * @param int     $uid
	 * @param boolean $show_count 统计好友数量
	 * @return array
	 */
	public function getGroupList($uid, $show_count = false) {
		$res = M('friend_group')->where("`uid`=$uid OR `uid`=0")->order('friend_group_id DESC')->findAll();

		if ($show_count && $res) {
			$sql = 'SELECT count(friend_uid) AS count, friend_group_id FROM ' . C('DB_PREFIX') . 'friend_group_link 
					WHERE `uid` = ' . $uid . ' AND `status` = 1 GROUP BY friend_group_id';
			$tmp = $this->query($sql);
			//格式化统计数据
			foreach ($tmp as $v) {
				$count[$v['friend_group_id']] = $v['count'];
			}
			
			foreach ($res as $k => $v) {
				$res[$k]['count'] = intval($count[$v['friend_group_id']]);
			}
			//未分组的
			if ($count[0] > 0 ) {
				$res[] = array('friend_group_id'=>0,'title'=>$this->default_group_name,'count'=>$count[0]);
			}
		}

		return $res;
	}

	/**
	 * 获取给定用户的给定好友所在的分组
	 * 
	 * @param int $uid
	 * @param int $friend_uid
	 * @return array
	 */
	public function getGroupOfFriend($uid, $friend_uid) {
		$friend_uid = !is_array($friend_uid) ? $friend_uid : implode(',', $friend_uid);
		$db_prefix	= C('DB_PREFIX');
		$field 		= "l.friend_uid AS friend_uid, g.friend_group_id AS friend_group_id, g.title AS title";
		$join 		= "INNER JOIN {$db_prefix}friend_group_link AS l ON g.friend_group_id = l.friend_group_id";
		$where 		= "l.uid = $uid AND l.friend_uid IN ( $friend_uid ) AND l.status = 1";
		$res = $this->table("{$db_prefix}friend_group AS g")->field($field)->join($join)->where($where)->findAll();
		
		//格式化输出
		foreach ($res as $v) {
			$group[$v['friend_uid']][] = $v;
		}
		return $group;
	}

	/**
	 * 可能认识的人（可能认识的人 = 不为好友的“好友的好友” || 不为好友的“IP相近用户”）
	 * 
	 * @param int $uid 用户ID
	 * @param int $max 获取的最大人数
	 * @return boolean|array
	 */
	public function getRelatedUser($uid, $max = 100) {
		if ( ($uid = intval($uid)) <= 0 ) {
			return false;
		}
		
		//现有的好友和已关注
		$prefix				= C('DB_PREFIX');
		$sql_friend			= "SELECT f.friend_uid,follow.fid FROM {$prefix}friend AS f 
							   LEFT JOIN {$prefix}weibo_follow AS follow 
							   ON f.uid = follow.uid 
							   WHERE f.uid = $uid";
		$sql_follow			= "SELECT f.friend_uid,follow.fid FROM {$prefix}weibo_follow AS follow  
							   LEFT JOIN {$prefix}friend AS f
							   ON f.uid = follow.uid 
							   WHERE follow.uid = $uid";
		$res 				= M('')->query($sql_friend);
		$res				= !empty($res) ? $res : M('')->query($sql_follow);
		foreach($res as $v) {
			isset($v['friend_uid']) && $fuid[] = $v['friend_uid'];
			isset($v['fid'])  		&& $fuid[] = $v['fid'];
		}
		//自己也不应该出现在“可能认识对人”中
		$fuid[]				= $uid;
		
		//不为好友的“好友的好友”
		$related_uid_friend	= $this->getRelatedUserFromFriend($fuid);
		
		//不为好友的“IP相近用户”
		$related_uid_ip		= $this->getRelatedUserFromIp($fuid);
		
		//计算最后结果集
		$user				= $this->getRelatedUid($related_uid_friend, $related_uid_ip, $max, $fuid);
		return $user;
	}
	
	/**
	 * 不为好友的“好友的好友”（取100个）
	 * 
	 * @param array $fuid
	 * @return array
	 */
	public function getRelatedUserFromFriend($fuid) {
		$map['uid']		= array('in', $fuid);
		$map['fuid']	= array('not in', $fuid);
		$map['status']	= 1;
		$res			= model('Friend')->getFriedByMap($map, 'fuid', 100, '', false);
		return getSubByKey( $res, 'fuid' );
	}
	
	/**
	 * 不为好友的“IP相近用户”（取100个）
	 * 
	 * @param array $fuid
	 * @return array
	 */
	public function getRelatedUserFromIp($fuid) {
		$map['uid']			= array('not in', $fuid);
		$map['login_place']	= convert_ip( get_client_ip() );
		$res = M('login_record')->where($map)->field('uid')->limit(100)->findAll();
		return getSubByKey($res, 'uid');
	}

	/**
	 * 可能认识的人结果集处理
	 * 
	 * @param array $related_uid_friend 不为好友的“好友的好友”
	 * @param array $related_uid_ip     不为好友的“IP相近用户”
	 * @param int   $max                获取最大条数
	 * @param array $fuid               好友
	 * @return array
	 */
	public function getRelatedUid($related_uid_friend, $related_uid_ip, $max, $fuid) {
		$max						= intval($max);
		$is_empty_frind				= empty($related_uid_friend);
		$is_empty_ip				= empty($related_uid_ip);
		
		//优先使用交集
		if ( !$is_empty_frind && !$is_empty_ip ) {
			$related_uid			= array_intersect($related_uid_friend, $related_uid_ip);
		}else if ( $is_empty_frind ) {
			$related_uid			= $related_uid_ip;
		}else if ( $is_empty_ip ) {
			$related_uid			= $related_uid_friend;
		}
		//随机取max条
		$related_uid				= $this->_getRandomSubArray($related_uid, $max);
		
		if ( count($related_uid) < $max ) {
			//如果结果集数量过少，使用并集补充
			$diff_count				= $max - count($related_uid);
			$temp_related_uid		= array_diff( array_unique( array_merge($related_uid_friend, $related_uid_ip) ), $related_uid );
			$related_uid			= array_merge( $related_uid, array_slice($temp_related_uid, 0, $diff_count) );
			unset($temp_related_uid);
			
			//如果结果集仍然过少，随机取用户补充
			$diff_count				= $max - count($related_uid);
			if ( $diff_count > 0 ) {
				$map['uid']			= array( 'not in', array_merge($related_uid, $fuid) );
				$map['is_active']	= 1;
				$map['is_init']		= 1;
				$rand_order			= $this->_getRandomOrder(array('uid','email','uname','ctime'));
				$rand_res			= model('User')->getUserByMap($map, 'uid', $diff_count, $rand_order, false);
				$related_uid		= array_merge( $related_uid, getSubByKey($rand_res, 'uid') );
			}
		}else {
			//如果结果集数量过多，随机取max条
			$related_uid			= array_rand($related_uid, $max);
		}
		
		return $related_uid;
	}

	/**
	 * 随机字段,随机排序
	 * 
	 * @param array $field
	 * @param array $order
	 * @return string
	 */
	protected function _getRandomOrder($field = array(''), $order = array('ASC', 'DESC')) {
		if ( empty($field) || empty($order) ) 
			return '';
		else 
			return $field[ array_rand($field) ] . ' ' . $order[ array_rand($order) ];
	}

	/**
	 * 随机获取数组的单元
	 * 
	 * @param array $source_array 原数组
	 * @param int   $numOfRequst  要获取的单元数量
	 * @return array
	 */
	protected function _getRandomSubArray($source_array, $numOfRequst = 1) {
		$res		= array();
		$random_id	= array_rand($source_array, $numOfRequst);
		foreach($random_id as $v) {
			$res[]	= $source_array[$v];
		}
		return $res;
	}
}
?>