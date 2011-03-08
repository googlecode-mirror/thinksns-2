<?php
/**
 * 用户组模型
 * 
 * @author daniel <desheng.young@gmail.com>
 */
class UserGroupModel extends Model {
	protected	$tableName	=	'user_group';

	/**
	 * 添加用户组
	 * 
	 * @param string $title 用户组名称
	 * @param string $icon  用户组图标 范例“v_01.gif”
	 * @return boolean
	 */
	public function addUserGroup($title,$icon) {
		$data['title']		= $title;
		$data['icon']		= $icon;
		$data['ctime']		= time();
		return $this->add($data);
	}

	/**
	 * 删除用户组
	 * 
	 * @param string $gids 用户组ID
	 * @return boolean
	 */
	public function deleteUserGroup($gids) {
		//防误操作
		if (empty($gids)) return false;
		
		$map['user_group_id']	= array('in', $gids);
		M('user_group')		->where($map)->delete();
		M('user_group_link')->where($map)->delete();
		return true;
	}

	/**
	 * 按照查询条件获取用户组
	 * 
	 * @param array  $map   查询条件
	 * @param string $field 字段 默认*
	 * @param string $order 排序 默认 以用户组ID升序排列
	 * @return array 用户组信息
	 */
	public function getUserGroupByMap($map = '', $field = '*', $order = 'user_group_id ASC') {
		return $this->field($field)->where($map)->order($order)->findAll();
	}

	/**
	 * 根据IDs获取用户组信息
	 * 
     * @param array  $gids  用户组ID
     * @param string $field 字段 默认*
     * @param string $order 排序 默认空
     * @return array 用户组信息
	 */
	public function getUserGroupById($gids, $field = '*', $order = '') {
		$map['user_group_id']	= array('in', $gids);
		return $this->getUserGroupByMap($map, $field, $order);
	}

	/**
	 * 根据用户ID获取用户组
	 * 
	 * @param array $uids 用户ID
	 * @return array 用户和用户组关系信息
	 */
	public function getUserGroupByUid($uids) {
		$map['uid']	= array('in', $uids);
		return M('user_group_link')->where($map)->order('user_group_id ASC')->findAll();
	}

	/**
	 * 获取制定用户组内的用户
	 * 
	 * @param array $gids 用户组ID
	 * @return array 用户和用户组关系信息,数组的键替换为用户ID
	 */
	public function getUidByUserGroup($gids) {
		$map['user_group_id']	= array('in', $gids);
		return getSubByKey( M('user_group_link')->where($map)->findAll(), 'uid' );
	}

	/**
	 * 将用户添加至用户组
	 * 
	 * @param array|string $uids 多个ID可为数组也可用“,”分隔
	 * @param array|string $gids 多个ID可为数组也可用“,”分隔
	 * @return boolean
	 */
    public function addUserToUserGroup($uids, $gids) {
    	$gids = is_array($gids) ? $gids : explode(',', $gids);
    	$uids = is_array($uids) ? $uids : explode(',', $uids);
    	
    	//用户信息
        $map['uid'] = array('in', $uids);
        $users = model('User')->getUserList($map, false, false, 'uid', '', count($uids));
        unset($map);
        if (!$users)
            return false;
        $users = $users['data'];
    	
        //删除旧数据
        $map['uid'] = array('in', $uids);
        M('user_group_link')->where($map)->delete();
        unset($map);
    	
    	//用户组信息
    	$groups = $this->getUserGroupById($gids);
    	if (!$groups) 
    		return false;
    	
    	//组装SQL，插入新数据
    	$sql = "INSERT INTO `" . C('DB_PREFIX') . "user_group_link` (`user_group_id`,`user_group_title`,`uid`) VALUES ";
    	foreach($groups as $group) {
    		foreach($users as $user) {
    			$sql .= "('{$group['user_group_id']}', '{$group['title']}', '{$user['uid']}'),";
    		}
    	}
    	$sql = rtrim($sql, ',');
    	return $this->execute($sql);
    }

    /**
     * 检测用户组是否存在
     * 
     * @param unknown_type $title 用户组名称
     * @param unknown_type $gid   用户组ID 该函数里为非该用户组ID
     * @return boolean
     */
	public function isUserGroupExist($title, $gid = 0) {
		$map['user_group_id']	= array('neq', $gid);
		$map['title']			= $title;
    	return M('user_group')->where($map)->find();
    }

    /**
     * 指定用户组下是否存在用户
     * 
     * @param array $gids 用户组ID
     * @return boolean
     */
    public function isUserGroupEmpty($gids) {
    	$map['user_group_id']	= array('in', $gids);
    	return ! M('user_group_link')->where($map)->find();
    }

    /**
     * 检测指定用户是否属于指定用户组
     * 
     * @param int   $uid  用户ID
     * @param array $gids 用户组ID
     * @return boolean
     */
    public function isUserInUserGroup($uid, $gids) {
    	$map['uid']			  	= $uid;
    	$map['user_group_id']	= array('in', $gids);
    	return M('user_group_link')->where($map)->find();
    }

    /**
     * 获取指定用户的用户组图标
     * 
     * @param int $uid 用户ID
     * @return string  返回用户组图标的img标签
     */
    public function getUserGroupIcon($uid) {
    	$groupIcon   = $this->where("user_group_id IN (SELECT user_group_id FROM ".C('DB_PREFIX')."user_group_link WHERE uid=$uid)")->field('icon,title')->findall();
    	if($groupIcon){
    		foreach ($groupIcon as $v){
    			if($v['icon']){
    				$html.="<img class='ts_icon' src=".THEME_URL."/images/".$v['icon']." title=".$v['title'].">";
    			}
    		}
    		return $html;
    	}else{
    		return '';
    	}
    }
}