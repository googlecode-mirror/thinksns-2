<?php
/**
 * 应用模型
 * 
 * @author daniel <desheng.young@gmail.com>
 */
class AppModel extends Model {
	protected $tableName = 'app';

	/**
	 * 检测APP是否安装
	 * 
	 * @param string $app_name APP英文名字
	 * @param int    $app_id   APP ID, 当指定app_id时, 该app将被排除
	 * @return boolean
	 */
	public function isAppNameExist($app_name, $app_id = 0) {
		$map['app_id']	 = array('neq', $app_id);
		$map['app_name'] = $app_name;
		return $this->where($map)->find() ? true : false;
	}

	/**
	 * 根据应用名检测应用是否开启
	 * 
	 * @param string $app_name
     * @return boolean true:开启 | false:关闭
	 */
	public function isAppNameActive($app_name) {
		$map['app_name'] = $app_name;
		$map['status']	 = array('neq', 0);
		return $this->where($map)->find() ? true : false;
	}

    /**
     * 根据应用ID检测应用是否开启
     * 
     * @param int $app_id
     * @return boolean true:开启 | false:关闭
     */
	public function isAppIdActive($app_id) {
		$map['app_id'] = $app_id;
		$map['status'] = array('neq', 0);
		return $this->where($map)->find() ? true : false;
	}

	/**
	 * 检查给定节点是否为管理后台
	 * 
	 * @param string $app_name 应用名
	 * @param string $mod_name Action控制器名
	 * @return boolean
	 */
	public function isAppAdmin($app_name, $mod_name) {
		$mod_name = $mod_name ? $mod_name : 'Admin';
		$map['app_name'] 	= $app_name;
		$admin_entry = $this->where($map)->field('admin_entry')->find();
		$admin_entry = explode('/', $admin_entry['admin_entry']);
		return $admin_entry[0] == $mod_name;
	}
	
	/**
	 * 获取所有应用
	 * 
	 * @param string $field 字段名
	 * @param string $order 结果集顺序
	 * @return array
	 */
	public function getAllApp($field = '*', $order = 'display_order ASC,app_id ASC') {
		return $this->field($field)->order($order)->findAll();
	}

	/**
	 * 获取应用列表
	 * 
	 * @param int    $limit 默认20
	 * @param string $field 默认*
	 * @param string $order 默认 展示顺序升序,应用ID升序
	 * @return array
	 */
	public function getAllAppByPage($limit = 20, $field = '*', $order = 'display_order ASC,app_id ASC') {
		return $this->field($field)->order($order)->findPage($limit);
	}

	/**
	 * 获取非关闭的应用列表
	 * 
     * @param int    $limit 默认20
     * @param string $field 默认*
     * @param string $order 默认 展示顺序升序,应用ID升序
     * @return array
	 */
	public function getOpenAppByPage($limit = 20, $field = '*', $order = 'display_order ASC,app_id ASC') {
		return $this->where('`status`<>0')->field($field)->order($order)->findPage($limit);
	}

	/**
	 * 获取有后台管理的应用
	 * 
	 * @param string $field 默认*
     * @param string $order 默认 展示顺序升序,应用ID升序
     * @return array
	 */
	public function getAdminApp($field = '*', $order = 'display_order ASC, app_id ASC') {
		$map['admin_entry'] = array('neq', '');
		return $this->where($map)->field($field)->order($order)->findAll();
	}

	/**
	 * 获取应用的详细信息(根据ID)
	 * 
	 * @param int    $app_id 应用ID
	 * @param string $field  默认*
	 * @return array
	 */
	public function getAppDetailById($app_id, $field = '*') {
		$app_id = is_array($app_id) ? $app_id : explode(',', $app_id);
		$map['app_id'] = array('in', $app_id);
		if (count($app_id) <= 1) {
			return $this->where($map)->field($field)->find();
		}else {
			return $this->where($map)->field($field)->findAll();
		}
	}

    /**
     * 获取应用的详细信息(根据应用名)
     * 
     * @param int    $app_id 应用名
     * @return array
     */	
	public function getAppDetailByName($app_name) {
		$map['app_name'] = $app_name;
		return $this->where($map)->find();
	}

	/**
	 * 删除应用信息
	 * 
	 * @param int $app_id 应用ID
	 */
	public function deleteApp($app_id) {
		$map['app_id'] = intval($app_id);
		return $this->where($map)->delete();
	}

	/**
	 * 用户添加应用
	 * 
	 * @param int $uid    用户ID
	 * @param int $app_id 应用ID
	 * @return boolean
	 */
	public function addAppForUser($uid, $app_id) {
		$this->removeAppForUser($uid, $app_id);
		
		$data['app_id'] = $app_id;
		$data['uid']	= $uid;
		$data['ctime']	= time();
		return M('user_app')->add($data);
	}

    /**
     * 用户删除应用
     * 
     * @param int $uid    用户ID
     * @param int $app_id 应用ID
     * @return boolean
     */
	public function removeAppForUser($uid, $app_id) {
		$map['uid']		= $uid;
		$map['app_id']	= $app_id;
		return M('user_app')->where($map)->delete();
	}
}