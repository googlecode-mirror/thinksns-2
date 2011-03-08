<?php 
class UserAction extends AdministratorAction {
    
    /** 用户 **/
    
    //用户管理
    public function user() {
    	$dao = model('User');
    	$res = $dao->getUserList('', true, true);
    	$this->assign($res);
        $this->display();
    }
    
    //添加用户
    public function addUser() {
    	$credit_type = X('Credit')->getCreditType();

    	$this->assign('credit_type',$credit_type);
    	$this->assign('type', 'add');
        $this->display('editUser');
    }
    
    public function doAddUser() {
    	//参数合法性检查
		$required_field = array(
			'email'		=> 'Email',
			'password'	=> '密码',
			'uname'		=> '姓名',
		);
		foreach ($required_field as $k => $v) {
			if ( empty($_POST[$k]) ) $this->error($v . '不可为空');
		}
		if ( ! isValidEmail($_POST['email']) ) {
			$this->error('Email格式错误，请重新输入');
		}
		if ( strlen($_POST['password']) < 6 || strlen($_POST['password']) > 16 ) {
			$this->error('密码必须为6-16位');
		}
		if ( ! isEmailAvailable($_POST['email']) ) {
			$this->error('Email已经被使用，请重新输入');
		}
    	if ( mb_strlen($_POST['uname'],'UTF8') > 10 ) {
			$this->error('昵称不能超过10个字符');
		}
		
		//注册用户
		$_POST['uname']		= escape($_POST['uname']);
		$_POST['password']	= md5($_POST['password']);
		$_POST['ctime']		= time();
		$_POST['is_active'] = intval($_POST['is_active']);
		$_POST['sex']		= intval($_POST['sex']);
		$uid = M('user')->add($_POST);
		if (!$uid) {
			$this->error('抱歉：注册失败，请稍后重试');
			exit;
		}
		
		//添加用户组信息
		model('UserGroup')->addUserToUserGroup( $uid, t($_POST['user_group_id']) );
		
		$this->success('注册成功');
    }
    
    //编辑用户
    public function editUser() {
    	$_GET['uid']  = intval($_GET['uid']);
    	if ($_GET['uid'] <= 0) $this->error('参数错误');

    	$map['uid']	= $_GET['uid'];
    	$user = M('user')->where($map)->find();
    	if(!$user) $this->error('无此用户');

    	$credit      = X('Credit');
    	$credit_type = $credit->getCreditType();
    	$user_credit = $credit->getUserCredit($map['uid']);

    	$this->assign($user);
    	$this->assign('credit_type',$credit_type);
    	$this->assign('user_credit',$user_credit);    	
    	$this->assign('type', 'edit');
    	$this->display();
    }
    
    public function doEditUser() {
    	//参数合法性检查
    	$_POST['uid']	= intval($_POST['uid']);
		$required_field = array(
			'uid'		=> '指定用户',
			'email'		=> 'Email',
			'uname'		=> '姓名',
		);
		foreach ($required_field as $k => $v) {
			if ( empty($_POST[$k]) ) $this->error($v . '不可为空');
		}
		if ( ! isValidEmail($_POST['email']) ) {
			$this->error('Email格式错误，请重新输入');
		}
		if ( !empty($_POST['password']) && strlen($_POST['password']) < 6 || strlen($_POST['password']) > 16 ) {
			$this->error('密码必须为6-16位');
		}
		if ( ! isEmailAvailable($_POST['email'], $_POST['uid']) ) {
			$this->error('Email已经被使用，请重新输入');
		}
    	if ( mb_strlen($_POST['uname'],'UTF8') > 10 ) {
			$this->error('昵称不能超过10个字符');
		}
		
		//保存修改
		$_POST['uname']  = escape($_POST['uname']);
		$key   			 = array('email','uname','sex','is_active','domain');
		$value 			 = array($_POST['email'], $_POST['uname'], intval($_POST['sex']), intval($_POST['is_active']),h($_POST['domain']));
		if ( !empty($_POST['password']) ) {
			$key[]   	 = 'password';
			$value[] 	 = md5($_POST['password']);
		}
		$map['uid']	= $_POST['uid'];
		$res = M('user')->where($map)->setField($key, $value);

		//保存积分设置
		$credit      = X('Credit');
		$credit_type = $credit->getCreditType();
		foreach($credit_type as $v){
			$credit_action[$v['name']] = intval($_POST[$v['name']]);
		}
		$credit->setUserCredit($map['uid'],$credit_action,'reset');
		
		//添加用户组信息
		model('UserGroup')->addUserToUserGroup( $_POST['uid'], t($_POST['user_group_id']) );
		
		$this->assign('jumpUrl', U('admin/User/user'));
		$this->success('保存成功');
    }
    
    //删除用户
    public function doDeleteUser() {
    	$_POST['uid'] = t($_POST['uid']);
    	$_POST['uid'] = explode(',', $_POST['uid']);
    	
    	//ts_user
    	$res = model('User')->deleteUser($_POST['uid']);
    	if($res) {echo 1;		  }
    	else 	 {echo 0; return ;}
    }
    
    //搜索用户
    public function doSearchUser() {
    	//为使搜索条件在分页时也有效，将搜索条件记录到SESSION中
		if ( !empty($_POST) ) {
			$_SESSION['admin_searchUser'] = serialize($_POST);
		}else if ( isset($_GET[C('VAR_PAGE')]) ) {
			$_POST = unserialize($_SESSION['admin_searchUser']);
		}else {
			unset($_SESSION['admin_searchUser']);
		}
    	
		//组装搜索条件
    	$fields	= array('email','uid','sex','is_active');
    	$map	= array();
    	foreach($fields as $v)
    		if ( isset($_POST[$v]) && $_POST[$v] != '' )
    			$map[$v]	= array('in', explode(',', $_POST[$v]));
    	
    	//姓名时，模糊查询
    	if ( isset($_POST['uname']) && $_POST['uname'] != '' ) {
    		$map['uname']	= array('exp', 'LIKE "%'.$_POST['uname'].'%"');
    	}
    	
    	//按用户组搜索
    	if ( !empty($_POST['user_group_id']) ) {
    		$uids		= model('UserGroup')->getUidByUserGroup($_POST['user_group_id']);
    		$uids		= array_unique( $uids );
    		//同时按部门和按用户组时，取交集
    		$uids		= empty($map['uid']) && !empty($uids) ? $uids : array_intersect($uids, $map['uid'][1]);
    		$map['uid']	= array('in', $uids);
    	}

    	$res = model('User')->getUserList($map, true, true);
    	$this->assign($res);
    	
    	$this->assign('type', 'searchUser');
    	$this->assign($_POST);
    	$this->display('user');
    }
    
    
    //字段配置
    public function setField() {
        $data['list'] = D('UserSet')->getFieldList();
        $this->assign( $data );
        $this->display();
    }
    
    //添加字段
    public function addfield() {
        if( $_POST ){
            if( D('UserSet')->addfield() ){
                $this->success('添加成功');
            }else{
                $this->error( D('UserSet')->getError() );
            }
        }else{
            $this->display();
        }
    }
    
    public function deleteField() {
    	echo D('UserSet')->deleteField(intval($_POST['ids'])) ? '1' : '0';
    }
    
    //消息群发
    public function message() {
    	// 用户组列表
    	$user_group_list = model('UserGroup')->field('`user_group_id`,`title`')->findAll();
    	$this->assign('user_group_list', $user_group_list);
        $this->display();
    }
    
    public function doSendMessage() {
    	$_POST['user_group_id'] = intval($_POST['user_group_id']);
    	$_POST['type']			= intval($_POST['type']);
    	
    	// 收件人
    	if ($_POST['user_group_id'] == 0) {
    		// 全部用户
    		$_POST['to'] = M('user')->where('`is_active`=1 AND `is_init`=1')->field('`uid`,`email`')->findAll();
    		$_POST['to'] = $_POST['type'] == 1 ? getSubByKey($_POST['to'], 'email') : getSubByKey($_POST['to'], 'uid');
    	}else {
    		// 指定用户组
    		$_POST['to'] = model('UserGroup')->getUidByUserGroup($_POST['user_group_id']);
    		if ($_POST['type'] == 1) {
    			$map['uid']  = array('in', $_POST['to']);
    			$_POST['to'] = M('user')->where($map)->field('email')->findAll();
    			$_POST['to'] = getSubByKey($_POST['to'],'email');
    		}
    	}
    	unset($_POST['user_group_id']);

    	$res = false;
    	if ( $_POST['type'] == 0 ) {
    		// 站内信
    		$res = model('Message')->postMessage($_POST, $this->mid);
    		$res = !empty($res);
    		
    	}else {
    		// Email
    		$service = service('Mail');
    		$_POST['title']		= t($_POST['title']);
    		$_POST['content']	= t($_POST['content']);
    		$_POST['to'] = array('desheng.young@qq.com');
    		foreach($_POST['to'] as $v)
    			$res = $res || $service->send_email($v, $_POST['title'], $_POST['content']);
    	}
    	if ($res)
    		$this->success('发送成功');
    	else
    		$this->error('发送失败');
    }
    
    private function __sendMessage() {
    	
    }
    
    //用户等级
    public function level() {
    	echo '<h2>这里是用户等级</h2>';
        //$this->display();
    }
    
    /** 权限 **/
    
    //用户组列表
    public function userGroup() {
    	$user_groups = model('UserGroup')->getUserGroupByMap();
    	$this->assign('user_groups', $user_groups);
    	$this->display();
    }
    
    //添加or编辑用户组
    public function editUserGroup() {
    	$_GET['gid'] = intval($_GET['gid']);
    	if ($_GET['gid'] > 0) {
    		//编辑时，显示原用户组名称
    		$user_group = model('UserGroup')->getUserGroupById($_GET['gid']);
    		$this->assign('user_group', $user_group[0]);
    	}
    	$this->display();
    }
    
    public function doAddUserGroup() {
    	$_POST['title'] = escape($_POST['title']);
    	if ( empty($_POST['title']) ) {
    		echo 0;
    		return ;
    	}
    	
    	$dao = model('UserGroup');
    	if ( $dao->isUserGroupExist($_POST['title']) ) {
    		echo -1; // 用户组已存在
    	}else {
	    	$res = $dao->addUserGroup($_POST['title'],$_POST['icon']);
	    	if($res) echo intval($res);
	    	else	 echo 0;
    	}
    }
    
    public function doEditUserGroup() {
    	$gid = intval($_POST['gid']);
    	$dao = model('UserGroup');
    	$data['title'] = escape($_POST['title']);
    	$data['icon']  = escape($_POST['icon']);
    	
    	if ( $dao->isUserGroupExist($data['title'], $gid) ) {
    		echo -1; // 用户组已存在
    	}else {
    		$res = M('user_group')->where('user_group_id='.$gid)->data($data)->save();
    		$res = M('user_group_link')->where('user_group_id='.$gid)->setField('user_group_title', $data['title']) && $res;
	    	if($res) echo 1;
	    	else     echo 0;
    	}
    }
    
	//转移用户组
    public function changeUserGroup() {
    	$_GET['uids'] = explode(',', t($_GET['uids']));
    	foreach($_GET['uids'] as $k => $v) 
    		if( ! is_numeric($v) || intval($v) <= 0 )
    			unset($_GET['uids'][$k]);
    	$count = count($_GET['uids']);
    	
    	$_GET['uids'] = implode(',', $_GET['uids']);
    	$this->assign('uids', $_GET['uids']);

    	$map['uid']   = array('in', $_GET['uids']);
    	$users = model('User')->getUserList($map, false, false, 'uname', '', $count>3?3:$count);
    	$users = implode(', ', getSubByKey($users['data'], 'uname'));
    	$users = $count > 3 ? "$users 等共{$count}人" : "$users 共{$count}人";

    	$this->assign('unames', $users);
        $this->display();
    }
    
	public function doChangeUserGroup() {
    	$_POST['gid'] = explode(',', t($_POST['gid']));
    	$_POST['uid'] = explode(',', t($_POST['uid']));
    	if ( empty($_POST['gid']) || empty($_POST['uid']) ) {
    		echo 0;
    		return ;
    	}
    	
    	if ( model('UserGroup')->addUserToUserGroup($_POST['uid'], $_POST['gid']) ) {
    		echo 1;
    	}else {
    		echo 0;
    	}
    }
    
    public function doDeleteUserGroup() {
    	$_POST['gid'] = t($_POST['gid']);
    	//不为空时，不允许删除
    	if ( ! model('UserGroup')->isUserGroupEmpty($_POST['gid']) ) {
    		echo 0;
    		return ;
    	}
    	//提交删除
    	$res = model('UserGroup')->deleteUserGroup( $_POST['gid'] );
    	if($res) echo 1;
    	else     echo 0;
    }
    
    public function isUserGroupExist() {
    	$res = model('UserGroup')->isUserGroupExist( $_POST['title'], intval($_POST['gid']));
    	if($res) echo 1;
    	else	 echo 0;
    }
    
    public function isUserGroupEmpty() {
    	$res = model('UserGroup')->isUserGroupEmpty( $_POST['gid'] );
    	if($res) echo 1;
    	else	 echo 0;
    }
    
    public function node() {
    	$node = D('Node')->getAllNode();
		$this->assign($node);    	
    	$this->display();
    }
    
    public function addNode() {
    	$this->assign('type', 'add');
    	$this->display('editNode');
    }
    
    public function doAddNode($old_nid = 0) {
    	//module为*时，action被忽略
    	$_POST['act_name']	   = $_POST['mod_name'] == '*' ? $_POST['mod_name'] : $_POST['act_name'];
    	
    	if (!$this->__isValidRequest('app_name,mod_name,act_name'))
    		$this->error('参数不完整');
    	
    	//action为*时，subAction被忽略
    	$_POST['subAction'] = ($_POST['act_name'] == '*') ? array() : $_POST['subAction'];
    	
    	foreach($_POST['subAction'] as $k => $v) {
    		if (empty($v)) unset($_POST['subAction'][$k]);
    		if ($v == '*') $this->error('参数错误：模块和方法名不为“*”时，关联方法名不可为“*”');
    	}
    	$_POST['parent_node_id'] = 0;
    	unset($_POST['node_id']);
    	$res = D('Node')->add($_POST);
    	$nid = $res;

    	//添加关联节点
    	if ( !empty($_POST['subAction']) ) {
    		$prefix = C('DB_PREFIX');
    		$sql = "INSERT INTO `{$prefix}node` (`app_name`,`app_alias`,`mod_name`,`mod_alias`,`act_name`,`act_alias`,`description`,`parent_node_id`) VALUES";
    		foreach ($_POST['subAction'] as $v) {
    			$sql .= " ('{$_POST['app_name']}','{$_POST['app_alias']}','{$_POST['mod_name']}','{$_POST['mod_alias']}','{$v}','{$_POST['act_alias']}_关联方法','{$_POST['description']}','{$nid}'),";
    		}
    		$sql = rtrim($sql, ',');
    		$res = $nid && M('')->execute($sql);
    	}

    	//编辑时，删除旧记录
		if ($res && $old_nid) {
			D('Node')->where("`node_id`=$old_nid OR `parent_node_id`=$old_nid")->delete();
			//更新权限表
			M('user_group_popedom')->where("`node_id`=$old_nid")->setField('node_id', $nid);
		}
		
		if ($res) {
			//编辑时，跳转至节点列表页
			$old_nid && $this->assign('jumpUrl', U('admin/User/node'));
			$this->success('保存成功');
		}else {
    		$this->error('保存失败');
    	}
    }
    
    public function editNode() {
    	$nid  = intval($_GET['nid']);
    	$node = D('Node')->getNodeDetailById($nid);
    	if (!$node) $this->error('不存在此节点');

    	$this->assign($node);
    	$this->assign('type', 'edit');
    	$this->display();
    }
    
    public function doEditNode() {
    	//删除旧记录，添加新记录
    	$this->doAddNode( intval($_POST['node_id']) );
    	exit;
    }
    
    public function doDeleteNode() {
    	$_POST['ids'] = t($_POST['ids']);
    	//不为空时，不允许删除
    	if ( ! D('Node')->isNodeEmpty($_POST['ids']) ) {
    		echo 0;
    		return ;
    	}
    	//提交删除
    	$res = D('Node')->deleteNode( $_POST['ids'] );
    	if($res) echo 1;
    	else     echo 0;
    }
    
    public function popedom() {
    	//获取主节点
    	$node	= D('Node')->getNodeByMap('`parent_node_id`=0', 'app_name ASC, mod_name ASC, act_name ASC, node_id ASC');
    	
    	//获取节点与用户组的对应关系
    	$nids	= getSubByKey($node['data'], 'node_id');
    	$prefix	= C('DB_PREFIX');
    	$where	= 'p.node_id IN ( ' . implode(',', $nids) . ' )';
    	$sql 	= "SELECT p.node_id,g.title FROM {$prefix}user_group_popedom AS p INNER JOIN {$prefix}user_group AS g ON p.user_group_id = g.user_group_id WHERE $where";
    	$res	= M('')->query($sql);
    	$node_usergroup	= array();
    	foreach ($res as $v) {
    		$node_usergroup[$v['node_id']][] = $v['title'];
    	}
    	$this->assign($node);
    	$this->assign('node_usergroup', $node_usergroup);
    	$this->display();
    }
    
    public function setPopedom() {
    	$_GET['nids'] 	= t($_GET['nids']);
    	$_GET['nids']	= explode(',', $_GET['nids']);
    	foreach ($_GET['nids'] as $k => $v) {
    		if ( !is_numeric($v) )
    			unset($_GET['nids'][$k]);
    	}
    	$count			= count($_GET['nids']);
    	$this->assign('nids', implode(',', $_GET['nids']));
    	$this->assign('count', $count);
    	
    	if ($count == 1) {
	    	$map['node_id']	= array('in', $_GET['nids']);
	    	$user_group		= M('user_group_popedom')->where($map)->findAll();
	    	$user_group		= getSubByKey($user_group, 'user_group_id');
	    	$this->assign('user_group', $user_group);
    	}
    	$this->display();
    }
    
    public function doSetPopedom() {
    	$_POST['gid'] = explode(',', $_POST['gid']);
    	$_POST['nid'] = explode(',', $_POST['nid']);
    	foreach ($_POST['gid'] as $k => $v)
    		if ( !is_numeric($v) || intval($v) <= 0 )
    			unset($_POST['gid'][$k]);
    	if (empty($_POST['gid'])) {echo 0; return ;}
    	
    	foreach ($_POST['nid'] as $k => $v)
    		if ( !is_numeric($v) || intval($v) <= 0 )
    			unset($_POST['nid'][$k]);
    	if (empty($_POST['nid'])) {echo 0; return ;}
    	
    	//获取节点的关联节点ID
    	$map['parent_node_id'] = array('in', $_POST['nid']);
    	$nids = D('Node')->where($map)->field('node_id')->findAll();
    	$nids = getSubByKey($nids, 'node_id');
    	$nids = array_merge($nids, $_POST['nid']);
    	if (empty($nids)) {echo 0; return ;}
    	
    	//删除旧记录
    	M('user_group_popedom')->where('`node_id` IN ( '.implode(',', $nids).' )')->delete();
    	
    	//组装插入SQL
    	$sql = "INSERT INTO `" . C('DB_PREFIX') . "user_group_popedom` (`user_group_id`,`node_id`) VALUES ";
    	foreach($nids as $nid) {
    		foreach($_POST['gid'] as $gid) {
    			$sql .= "('$gid', '$nid'),";
    		}
    	}
    	$sql = rtrim($sql, ',');
    	$res = M('')->execute($sql);
		if ($res) {
    		echo 1;
    	}else {
    		echo 0;
    	}
    }
	
	private function __isValidRequest($field, $array = 'post') {
		$field = is_array($field) ? $field : explode(',', $field);
		$array = $array == 'post' ? $_POST : $_GET;
		foreach ($field as $v){
			$v = trim($v);
			if ( !isset($array[$v]) || $array[$v] == '' ) return false;
		}
		return true;
	}
}
?>