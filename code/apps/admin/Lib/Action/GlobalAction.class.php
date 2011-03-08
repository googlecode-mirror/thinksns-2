<?php
class GlobalAction extends AdministratorAction {
	
	private function __isValidRequest($field, $array = 'post') {
		$field = is_array($field) ? $field : explode(',', $field);
		$array = $array == 'post' ? $_POST : $_GET;
		foreach ($field as $v){
			$v = trim($v);
			if ( !isset($array[$v]) || $array[$v] == '' ) return false;
		}
		return true;
	}
	
	/** 系统配置 - 站点配置 **/
	
	//站点设置
	public function siteopt() {
		$site_opt = model('Xdata')->lget('siteopt');
		$this->assign($site_opt);

		require_once ADDON_PATH . '/libs/Io/Dir.class.php';
        $theme_list = new Dir(SITE_PATH.'/public/themes/');
        $this->assign('theme_list',$theme_list->toArray());
        $this->display();
	}
	
	//设置站点
	public function doSetSiteOpt() {
		if (empty($_POST)) {
			$this->error('参数错误');
		}
		
		$_POST['site_name']           		= t($_POST['site_name']);
		$_POST['site_slogan']		  		= t($_POST['site_slogan']);
		$_POST['site_header_keywords']		= t($_POST['site_header_keywords']);
		$_POST['site_header_description']	= t($_POST['site_header_description']);
		$_POST['site_closed']		 		= intval($_POST['site_closed']);
		$_POST['site_closed_reason'] 		= t($_POST['site_closed_reason']);
		$_POST['site_icp']  		 		= t($_POST['site_icp']);
		$_POST['site_verify']		 		= isset($_POST['site_verify']) ? $_POST['site_verify'] : '';
		
		$res = model('Xdata')->lput('siteopt', $_POST);
		if ($res) {
			$this->assign('jumpUrl', U('admin/Global/siteopt'));
			$this->success('保存成功');
		}else {
			$this->error('保存失败');
		}
	}
	
	/** 系统配置 - 注册配置 **/
	
	public function register() {
		$register = model('Xdata')->lget('register');
		$this->assign($register);
		$invite   = model('Invite')->getSet();
		$this->assign($invite);
		$this->display();
	}
	
	public function doSetRegisterOpt() {
		$invite_set['invite_set'] = t($_POST['invite_set']);
		unset($_POST['invite_set']);
		if ( model('Xdata')->lput('register', $_POST) && model('Xdata')->lput('inviteset', $invite_set) ) {
			$this->assign('jumpUrl', U('admin/Global/register'));
			$this->success('保存成功');
		}else {
			$this->error('保存失败');
		}
	}
	
	/** 系统配置 - 积分配置 **/
	//积分类别设置
	public function creditType(){
		$creditType = M('credit_type')->order('id ASC')->findAll();
		$this->assign('creditType',$creditType);
		$this->display();
	}
	public function editCreditType(){
		$type   = $_GET['type'];
		if($cid = intval($_GET['cid'])){
			$creditType = M('credit_type')->where("`id`=$cid")->find();//积分类别
			if (!$creditType) $this->error('无此积分类型');
			$this->assign('creditType',$creditType);
		}

		$this->assign('type', $type);
		$this->display();		
	}
	public function doAddCreditType(){
		if ( !$this->__isValidRequest('name') ) $this->error('数据不完整');

		$res = M('credit_type')->add($_POST);
		if ($res) {
			$db_prefix  = C('DB_PREFIX');
			$model = M('');
			$setting = $model->query("ALTER TABLE {$db_prefix}credit_setting ADD {$_POST['name']} INT(11) DEFAULT 0;");
			$user    = $model->query("ALTER TABLE {$db_prefix}credit_user ADD {$_POST['name']} INT(11) DEFAULT 0;");

			$this->assign('jumpUrl', U('admin/Global/creditType'));
			$this->success('保存成功');
		}else {
			$this->error('保存失败');
		}		
	}
	public function doEditCreditType(){
		if ( !$this->__isValidRequest('id,name') ) $this->error('数据不完整');
		$creditTypeDao = M('credit_type');
		//获取原字段名
		$oldName = $creditTypeDao->find($_POST['id']);
		//修改字段名
		$res = $creditTypeDao->save($_POST);
		if ($res) {
			$db_prefix  = C('DB_PREFIX');
			$model = M('');
			$setting = $model->query("ALTER TABLE {$db_prefix}credit_setting CHANGE {$oldName['name']} {$_POST['name']} INT(11);");
			$user    = $model->query("ALTER TABLE {$db_prefix}credit_user CHANGE {$oldName['name']} {$_POST['name']} INT(11);");

			$this->assign('jumpUrl', U('admin/Global/creditType'));
			$this->success('保存成功');
		}else {
			$this->error('保存失败');
		}		
	}
	public function doDeleteCreditType(){
		$ids = t($_POST['ids']);
		$ids = explode(',', $ids);
		if ( empty($ids) ) {echo 0; return ;}
		
		$map['id'] = array('in', $ids);
		$creditTypeDao = M('credit_type');
		//获取字段名
		$typeName = $creditTypeDao->where($map)->findAll();
		//清除type信息和对应字段
		$res = M('credit_type')->where($map)->delete();
		if ($res){
			$db_prefix  = C('DB_PREFIX');
			$model = M('');
			foreach($typeName as $v){
				$setting = $model->query("ALTER TABLE {$db_prefix}credit_setting DROP {$v['name']};");
				$user    = $model->query("ALTER TABLE {$db_prefix}credit_user DROP {$v['name']};");
			}
			echo 1;
		}else{
			echo 0;
		}
	}
	//积分规则设置
	public function credit() {
		$list = M('credit_setting')->order('type ASC')->findPage(30);
		$creditType = M('credit_type')->order('id ASC')->findAll();
		$this->assign('creditType',$creditType);
		$this->assign($list);
		$this->display();
	}	
	public function addCredit() {
		$creditType = M('credit_type')->order('id ASC')->findAll();//积分类别
		$this->assign('creditType',$creditType);
		$this->assign('type','add');
		$this->display('editCredit');
	}	
	public function doAddCredit() {
		if ( !$this->__isValidRequest('name') ) $this->error('数据不完整');

		$creditType = M('credit_type')->order('id ASC')->findAll();
		foreach($creditType as $v){
			if(!is_numeric($_POST[$v['name']])){
				$this->error($v['alias'].'的值必须为数字！');
			}
		}

		$res = M('credit_setting')->add($_POST);
		if ($res) {
			$this->assign('jumpUrl', U('admin/Global/credit'));
			$this->success('保存成功');
		}else {
			$this->error('保存失败');
		}
	}	
	public function editCredit() {
		$cid 	= intval($_GET['cid']);
		$credit	= M('credit_setting')->where("`id`=$cid")->find();
		if (!$credit) $this->error('无此积分规则');

		$creditType = M('credit_type')->order('id ASC')->findAll();//积分类别
		$this->assign('creditType',$creditType);

		$this->assign('credit', $credit);
		$this->assign('type', 'edit');
		$this->display();
	}	
	public function doEditCredit() {
		if ( !$this->__isValidRequest('id,name') ) $this->error('数据不完整');

		$creditType = M('credit_type')->order('id ASC')->findAll();
		foreach($creditType as $v){
			if(!is_numeric($_POST[$v['name']])){
				$this->error($v['alias'].'的值必须为数字！');
			}
		}

		$res = M('credit_setting')->save($_POST);
		if ($res) {
			$this->assign('jumpUrl', U('admin/Global/credit'));
			$this->success('保存成功');
		}else {
			$this->error('保存失败');
		}
	}	
	public function doDeleteCredit() {
		$ids = t($_POST['ids']);
		$ids = explode(',', $ids);
		if ( empty($ids) ) {echo 0; return ;}
		
		$map['id'] = array('in', $ids);
		$res = M('credit_setting')->where($map)->delete();
		if ($res) echo 1;
		else 	  echo 0;
	}
	//批量用户积分设置
	public function creditUser(){
		$creditType = M('credit_type')->order('id ASC')->findAll();
        $this->assign('creditType',$creditType);
        $this->assign('grounlist',model('UserGroup')->getUserGroupByMap('','user_group_id,title'));
        $this->display();
	}
	public function doCreditUser(){
		set_time_limit(0);
		//查询用户ID
		$_POST['uId'] && $map['uid'] = array('in',explode(',',t($_POST['uId'])));
		$_POST['gId']!='all' && $map['admin_level'] = intval($_POST['gId']);
		$_POST['active']!='all' && $map['is_active'] = intval($_POST['active']);
		$user = D('User')->where($map)->field('uid')->findAll();
        if($user == false){
        	$this->error('查询失败，没有这样条件的人');
        }
	    //组装积分规则
		$setCredit = X('Credit');
		$creditType = $setCredit->getCreditType();
		foreach($creditType as $v){
			$action[$v['name']] = intval($_POST[$v['name']]);	
		}

		if($_POST['action'] == 'set'){//积分修改为
			foreach($user as $v){
				$setCredit->setUserCredit($v['uid'],$action,'reset');
				if($setCredit->getInfo()===false)$this->error('保存失败');
			}
		}else{//增减积分
			foreach($user as $v){
				$setCredit->setUserCredit($v['uid'],$action);
				if($setCredit->getInfo()===false)$this->error('保存失败');
			}
		}

		$this->assign('jumpUrl', U('admin/Global/creditUser'));
		$this->success('保存成功');
	}
	
	/** 系统配置 - 邀请配置 **/
	
	//邀请配置
	function invite(){
		$data = model('Invite')->getSet();
		$this->assign( $data );
		$this->display();
	}
	
	//邀请码发放
	function invitecode(){
		$num = intval($_POST['send_type_num']);
		$user = t($_POST['send_type_user']);
		if($_POST['send_type']==1){
			$user = M('user')->where('is_init=1 AND is_active=1')->field('uid')->findall();
			foreach ($user as $key=>$value){
				model('Invite')->sendcode($value['uid'],$num);
			}
		}else{
			$user = explode(',', $user);
			foreach ($user as $k=>$v){
				model('Invite')->sendcode($v,$num);
				x('Notify')->sendIn($v,'admin_sendinvitecode',array('num'=>$num)); //通知发送
			}					
		}
		$this->success('操作成功');
	}
	
	/** 系统配置 - 公告配置 **/
	
	public function announcement() {
		if ($_POST) {
			unset($_POST['__hash__']);
			model('Xdata')->lput('announcement',$_POST);
			$this->assign('jumpUrl', U('admin/Global/announcement'));
			$this->success('保存成功');
		}else {
			$announcement = model('Xdata')->lget('announcement');
			$this->assign($announcement);
			$this->display();
		}
	}
	
	/** 系统配置 - 邮件配置 **/
	
	public function email(){
		if($_POST){
			unset($_POST['__hash__']);
			model('Xdata')->lput('email',$_POST);
			$this->assign('jumpUrl', U('admin/Global/email'));
			$this->success('保存成功');
		}else{
			$email = model('Xdata')->lget('email');
			$this->assign($email);
			$this->display();
		}
	}
	
	/** 系统配置 - 附件配置 **/
	
	public function attachConfig() {
		if ($_POST) {
			$_POST['attach_path_rule']		 = t($_POST['attach_path_rule']);
			$_POST['attach_max_size']		 = floatval($_POST['attach_max_size']);
			$_POST['attach_allow_extension'] = t($_POST['attach_allow_extension']);
			$this->assign('jumpUrl', U('admin/Global/attachConfig'));
			if ( model('Xdata')->lput('attach', $_POST) )
				$this->success('保存成功');
			else
				$this->error('保存失败');
			
		}else {
			$data = model('Xdata')->lget('attach');
			$this->assign($data);
			$this->display();
		}
	}
	
	/** 系统配置 - 平台配置 **/

	public function platform() {
		if($_POST) {
			foreach($_POST as $k => $v)
				$_POST[$k] = t($v);

			$this->assign('jumpUrl', U('admin/Global/platform'));
			if( model('Xdata')->lput('platform', $_POST) )
				$this->success('保存成功');
			else
				$this->error('保存失败');
		}else {
			$this->assign( model('Xdata')->lget('platform') );
			$this->display();
		}
	}

	/** 系统配置 - 文章配置 **/

	public function document() {
		$data = M('document')->order('`display_order` ASC,`document_id` ASC')->findAll();
		$this->assign('data', $data);
		$this->display();
	}

	public function addDocument() {
		$this->assign('type', 'add');
		$this->display('editDocument');
	}

	public function editDocument() {
		$map['document_id'] = intval($_GET['id']);
		$document = M('document')->where($map)->find();
		if ( empty($document) )
			$this->error('该文章不存在');
		$this->assign($document);

		$this->assign('type', 'edit');
		$this->display();
	}

	public function doEditDocument() {
		if ( ($_POST['document_id'] = intval($_POST['document_id'])) <= 0 )
			unset($_POST['document_id']);

		// 格式化数据
		$_POST['title']			= t($_POST['title']);
		$_POST['content']		= $_POST['content'];
		$_POST['is_active']		= intval($_POST['is_active']);
		$_POST['is_on_footer']	= intval($_POST['is_on_footer']);
		$_POST['last_editor_id']= $this->mid;
		$_POST['mtime']			= time();
		if ( !isset($_POST['document_id']) ) {
			// 新建文章
			$_POST['author_id']	= $this->mid;
			$_POST['ctime']		= $_POST['mtime'];
		}

		// 数据检查
		if ( empty($_POST['title']) )
			$this->error('标题不能为空');
		
		// 提交
		$res = isset($_POST['document_id']) ? M('document')->save($_POST) : M('document')->add($_POST);

		if($res) {
			if ( isset($_POST['document_id']) ) {
				$this->assign('jumpUrl', U('admin/Global/document'));
			}else {
				// 为排序方便, 新建完毕后, 将display_order设置为ad_id
				M('document')->where("`document_id`=$res")->setField('display_order', $res);
				$this->assign('jumpUrl', U('admin/Global/addDocument'));
			}
			$this->success('保存成功');
		}else {
			$this->error('保存失败');
		}
	}

	public function doDeleteDocument() {
		if( empty($_POST['ids']) ) {
			echo 0;
			exit ;
		}
		$map['document_id'] = array('in', t($_POST['ids']));
		echo M('document')->where($map)->delete() ? '1' : '0';
	}

	public function doDocumentOrder() {
		$_POST['document_id']	= intval($_POST['document_id']);
		$_POST['baseid']		= intval($_POST['baseid']);
		if ( $_POST['document_id'] <= 0 || $_POST['baseid'] <= 0 ) {
			echo 0;
			exit;
		}

		// 获取详情
		$map['document_id'] = array('in', array($_POST['document_id'], $_POST['baseid']));
		$res = M('document')->where($map)->field('document_id,display_order')->findAll();
		if ( count($res) < 2 ) {
			echo 0;
			exit;
		}

		//转为结果集为array('id'=>'order')的格式
    	foreach($res as $v) {
    		$order[$v['document_id']] = intval($v['display_order']);
    	}
    	unset($res);

    	//交换order值
    	$res = 		   M('document')->where('`document_id`=' . $_POST['document_id'])->setField(  'display_order', $order[$_POST['baseid']] );
    	$res = $res && M('document')->where('`document_id`=' . $_POST['baseid'])->setField( 'display_order', $order[$_POST['document_id']]  );

    	if($res) echo 1;
    	else	 echo 0;
	}
}