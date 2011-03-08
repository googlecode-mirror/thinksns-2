<?php
class User2Model extends Model {
	protected	$tableName	=	'user';
    var $uid;
    
    //获取用户列表
    public function getUserList($map = '', $show_dept = false, $show_user_group = false, $field = '*', $order = 'id ASC', $limit = 30) {
    	$res  = $this->where($map)->field($field)->order($order)->findPage($limit);
    	$uids = getSubByKey($res['data'], 'id');
    	
    	//部门信息
    	if ($show_dept) {
    		$temp_dept = $this->getDepartmentByUser($uids);
    		
    		//转换成array($uid => $dept)的格式
    		foreach($temp_dept['data'] as $v) {
    			$dept[$v['uid']][] = $v;
    		}
    		unset($temp_dept);
    		
    		//将部门信息添加至结果集
    		foreach($res['data'] as $k => $v) {
				$res['data'][$k]['department'] = isset($dept[$v['id']]) ? $dept[$v['id']] : array();
    		}
    	}
    	
    	//用户组
    	if ($show_user_group) {
    		$temp_user_group = model('UserGroup')->getGroupByUser($uids);

    		//转换成array($uid => $user_group)的格式
    		foreach($temp_user_group as $v) {
    			$user_group[$v['uid']][] = $v;
    		}
    		unset($temp_user_group);
    		//dump($res['data']);exit;
    		//将用户组信息添加至结果集
    		foreach($res['data'] as $k => $v) {
				$res['data'][$k]['user_group'] = isset($user_group[$v['id']]) ? $user_group[$v['id']] : array();
    		}
    	}
    	return $res;
    }
    
    public function deleteUser($uids) {
    	//防止误删
    	$uids = is_array($uids) ? $uids : explode(',', $uids);
    	foreach($uids as $k => $v) {
    		if ( !is_numeric($v) ) unset($uids[$k]);
    	}
    	if ( empty($uids) ) return false;
    	
    	$map['uid'] = array('in', $uids);
    	return M('user')->where($map)->delete();
    }
    
    public function getDepartmentByUser($uids, $field = '*', $order = '', $limit = 30) {
    	$map['uid'] = array('in', $uids);
    	if (empty($order)) {
    		return M('user_department')->where($map)->field($field)->findPage($limit);
    	}else {
    		return M('user_department')->where($map)->field($field)->order($order)->findPage($limit);
    	}
    }
    
    public function getUserByDepartment($dids) {
    	
    }
	
	//更新操作
	function upDate($type){
	    return $this->$type();
	}
	
	//更新基本信息
	private function upbase( ){
	    $data['name'] = t( $_POST['name'] );
	    $data['sex']  = intval( $_POST['sex'] );
	    return $this->where("id={$this->uid}")->data($data)->save();
	}
	
	protected function data_field($module){
        $list = $this->table(C('DB_PREFIX').'user_set')->where("status=1")->findall();
        foreach ($list as $value){
            $data[$value['module']][$value['fieldkey']] = $value['fieldname'];
        }
	    return ($module)?$data[$module]:$data;
	}
}