<?php
class PublicAction extends Action{
	
	public function _initialize() {
		
	}
	
	public function adminlogin() {
		if ( service('Passport')->isLoggedAdmin() ) {
			redirect(U('admin/Index/index'));
		}
		
		$this->display();
	}
	
	public function doAdminLogin() {
		// 检查验证码
		if ( md5($_POST['verify']) != $_SESSION['verify'] ) {
			$this->error('验证码错误');
		}
		
		// 数据检查
		if ( empty($_POST['password']) ) {
			$this->error('密码不能为空');
		}
		if ( isset($_POST['email']) && ! isValidEmail($_POST['email']) ) {
			$this->error('email格式错误');
		}
		
		// 检查帐号/密码
		$is_logged = false;
		if ( isset($_POST['email']) ) {
			$is_logged = service('Passport')->loginAdmin(NULL, $_POST['email'], $_POST['password']);
		}else if ( $this->mid > 0 ) {
			$is_logged = service('Passport')->loginAdmin($this->mid, NULL, $_POST['password']);
		}else {
			$this->error('参数错误');
		}

		// 提示消息不显示头部
		$this->assign('isAdmin','1');
				
		if ($is_logged) {
			$this->assign('jumpUrl', U('admin/Index/index'));
			$this->success('登陆成功');
		}else {
			$this->assign('jumpUrl', U('home/Public/adminlogin'));
			$this->error('登陆失败');
		}
	}
	
	public function login() {
		// 已登录
		if ( service('Passport')->isLogged() ) {
			U('home/Space/index','',true);
		}
		unset($_SESSION['sina'], $_SESSION['key'], $_SESSION['douban'], $_SESSION['open_platform_type']);
		
		//验证码
		$opt_verify = model('Xdata')->lget('siteopt');
		$opt_verify = $opt_verify['site_verify'];
		$opt_verify = in_array('login', $opt_verify);
		if ($opt_verify) {
			$this->assign('register_verify_on', 1);
		}
		
		$data['email'] = t($_REQUEST['email']);
		$data['uid'] = t($_REQUEST['uid']);
		$data['list'] = D('Operate','weibo')->getIndex(3);
		
		// 豆瓣登陆
		include_once SITE_PATH.'/addons/plugins/login/douban.class.php';
		$douban = new douban();
		$this->assign('doubanurl', $douban->getUrl());
		
		// 新浪登陆
		include_once( SITE_PATH.'/addons/plugins/login/sina.class.php' );
		$sina = new sina();
		$this->assign('sinaurl',$sina->getUrl());
		
		$this->assign($data);
		$this->assign('regInfo',model('Xdata')->lget('register'));
		$this->display();
	}
	
	//外站帐号登陆
	public function otherlogin(){
		if ( !in_array($_SESSION['open_platform_type'], array('sina', 'douban')) ) {
			$this->error('授权失败');
		}
		
		$type = $_SESSION['open_platform_type'];
		include_once( SITE_PATH."/addons/plugins/login/{$type}.class.php" );
		$platform = new $type();
		$userinfo = $platform->userInfo();
		
		// 检查是否成功获取用户信息
		if ( !is_numeric($userinfo['id']) || !is_string($userinfo['uname']) ) {
			$this->assign('jumpUrl', SITE_URL);
			$this->error('获取用户信息失败');
		}
		
		if ( $info = M('login')->where("type_uid=".$userinfo['id']." AND type='{$type}'")->find() ) {
			$user = M('user')->where("uid=".$info['uid'])->find();
			
			if (empty($user)) {
				// 未在本站找到用户信息, 删除用户站外信息,让用户重新登陆
				M('login')->where("type_uid=".$userinfo['id']." AND type='{$type}'")->delete();
			}else {
				if ( $info['oauth_token'] == '' ) {
					$syncdata['login_id']        	= $info['login_id'];
					$syncdata['oauth_token']        = $_SESSION[$type]['access_token']['oauth_token'];
					$syncdata['oauth_token_secret'] = $_SESSION[$type]['access_token']['oauth_token_secret'];
					M('login')->save($syncdata);
				}
				
				$this->setSessionAndCookie($user['uid'], $user['uname'], '', FALSE );
				$this->recordLogin($user['uid']);
				redirect(U('home/User/index'));
			}
		}
		$this->assign('user',$userinfo);
		$this->assign('type',$type);
		$this->display();
	}
	
	// 激活外站登陆
	public function initotherlogin(){
		if ( ! in_array($_POST['type'], array('douban','sina')) ) {
			$this->error('参数错误');
		}
		
		$type = $_POST['type'];
		include_once( SITE_PATH."/addons/plugins/login/{$type}.class.php" );
		$platform = new $type();
		$userinfo = $platform->userInfo();
		
		// 检查是否成功获取用户信息
		if ( !is_numeric($userinfo['id']) || !is_string($userinfo['uname']) ) {
			$this->assign('jumpUrl', SITE_URL);
			$this->error('获取用户信息失败');
		}
		
		// 检查是否已加入本站
		$map['type_uid'] = $userinfo['id'];
		$map['type']     = $type;
		if ( ($local_uid = M('login')->where($map)->getField('uid')) && (M('user')->where('uid='.$local_uid)->find()) ) {
			$this->assign('jumpUrl', SITE_URL);
			$this->success('您已经加入本站');
		}
		
		// 初使化用户信息, 激活帐号
		$data['uname']        = $userinfo['uname'];
		$data['province']     = intval($userinfo['province']);
		$data['city']         = intval($userinfo['city']);
		$data['location']     = $userinfo['location'];
		$data['sex']          = intval($userinfo['sex']);
		$data['is_active']    = 1;
		$data['is_init']      = 1;
		$data['is_synchronizing']  = ($type == 'sina') ? '1' : '0'; // 是否同步新浪微博. 目前仅能同步新浪微博
		
		if ( $id = M('user')->add($data) ) {
			// 记录至同步登陆表
			$syncdata['uid']                = $id;
			$syncdata['type_uid']           = $userinfo['id'];
			$syncdata['type']               = $type;
			$syncdata['oauth_token']        = $_SESSION[$type]['access_token']['oauth_token'];
			$syncdata['oauth_token_secret'] = $_SESSION[$type]['access_token']['oauth_token_secret'];
			M('login')->add($syncdata);
			
			//转换头像
			D('Avatar')->saveAvatar($id,$userinfo['userface']);
			
			// 将用户添加到myop_userlog，以使漫游应用能获取到用户信息
			$userlog = array(
				'uid'		=> $id,
				'action'	=> 'add',
				'type'		=> '0',
				'dateline'	=> time(),
			);
			M('myop_userlog')->add($userlog);
			
			$this->recordLogin($id);
			$this->setSessionAndCookie($id, $data['uname'], '', FALSE );
			$this->registerRelation($id);
			
			redirect( U('home/public/followuser') );
		}else{
			$this->error('同步帐号发生错误');
		}
	}
	
	public function bindaccount() {
		if ( ! in_array($_POST['type'], array('douban','sina')) ) {
			$this->error('参数错误');
		}
		
		$psd  = ($_POST['passwd']) ? $_POST['passwd'] : true;
		$type = $_POST['type'];
		
		if ( $user = service('Passport')->getLocalUser($_POST['email'], $psd) ) {
			include_once( SITE_PATH."/addons/plugins/login/{$type}.class.php" );
			$platform = new $type();
			$userinfo = $platform->userInfo();
			
			// 检查是否成功获取用户信息
			if ( !is_numeric($userinfo['id']) || !is_string($userinfo['uname']) ) {
				$this->assign('jumpUrl', SITE_URL);
				$this->error('获取用户信息失败');
			}
			
			// 检查是否已加入本站
			$map['type_uid'] = $userinfo['id'];
			$map['type']     = $type;
			if ( ($local_uid = M('login')->where($map)->getField('uid')) && (M('user')->where('uid='.$local_uid)->find()) ) {
				$this->assign('jumpUrl', SITE_URL);
				$this->success('您已经加入本站');
			}
			
			$syncdata['uid']      = $user['uid'];
			$syncdata['type_uid'] = $userinfo['id'];
			$syncdata['type']     = $type;
			if ( M('login')->add($syncdata) ) {
				$this->setSessionAndCookie($user['uid'], $user['uname'], '', FALSE );
				$this->recordLogin($user['uid']);
				$this->assign('jumpUrl', U('home/User/index'));
				$this->success('绑定成功');
				
			}else {
				$this->assign('jumpUrl', SITE_URL);
				$this->error('绑定失败');
			}
		}else {
			$this->error('帐号输入有误');
		}
	}
	
	//
	public function callback(){
		include_once( SITE_PATH.'/addons/plugins/login/sina.class.php' );
		$sina = new sina();
		$sina->checkUser();
		redirect(U('home/public/otherlogin'));
	}
	
	public function doubanCallback() {
		if ( !isset($_GET['oauth_token']) ) {
			$this->error('Error: No oauth_token detected.');
			exit;
		}
		require_once SITE_PATH . '/addons/plugins/login/douban.class.php';
		$douban = new douban();
		if ( $douban->checkUser($_GET['oauth_token']) ) {
			redirect(U('home/Public/otherlogin'));
		}else {
			$this->assign('jumpUrl', SITE_URL);
			$this->error('验证失败');
		}
	}
	
	public function doLogin($username = '', $password = '') {
		//检查验证码
		$opt_verify = model('Xdata')->lget('siteopt');
		$opt_verify = $opt_verify['site_verify'];
		$opt_verify = in_array('login', $opt_verify);
		if ($opt_verify && md5($_POST['verify'])!=$_SESSION['verify']) {
			$this->error('验证码错误');
		}
		
		$username =	empty($username) ? $_POST['email']  : $username;
		$password =	empty($password) ? $_POST['password'] : $password;
		
		if(!$password){
			$this->error('请输入密码');
		}
		$passport =	service('Passport');
		$user	  =	$passport->getLocalUser($username,$password);

		if($user) {
			//检查是否激活
			if ($user['is_active'] == 0) {
				redirect(U('home/public/login',array('t'=>'unactive','email'=>$username,'uid'=>$user['uid'])));
				exit;
				/**
				//是否需要Email激活
				$opt_email_activate = model('Xdata')->lget('register');
				$opt_email_activate = $opt_email_activate['register_email_activate'];
				if ($opt_email_activate == 1) {
					$this->activate($user['uid'], $user['email'], '', 1);
					exit;
				}else {
					//自动激活
					$map['uid']		= $user['uid'];
					M('user')->where($map)->setField('is_active', 1);
				}
				**/
			}
			
			$this->setSessionAndCookie($user['uid'], $user['uname'], $user['email'], intval($_POST['remember']) === 1);
			
			$this->recordLogin($user['uid']);
			
			//跳转至登录前输入的url
			if ( $_SESSION['refer_url'] != '' ) {
				$refer_url	=	$_SESSION['refer_url'];
				unset($_SESSION['refer_url']);
			}else {
				$refer_url = U('home/User/index');
			}
			$this->assign('jumpUrl',$refer_url);
			$this->success($username.' 登录成功');
		}else {
			$this->error('登录失败');
		}
	}
	
	public function logout() {
		service('Passport')->logoutLocal();
		$this->assign('jumpUrl',U('home/index'));
		$this->success('成功退出');
	}
	
	public function logoutAdmin() {
		// 成功消息不显示头部
		$this->assign('isAdmin','1');
		
		service('Passport')->logoutLocal();
		$this->assign('jumpUrl',U('home/Public/adminlogin'));
		$this->success('成功退出');
	}
	
	public function register() {
		//检查是否允许注册
		$opt_register = model('Xdata')->lget('register');
		$opt_register = $opt_register['register_type'];
		if ( $opt_register === 'closed' ) {
			$this->error('抱歉：本站已关闭注册');
		} else if ( $opt_register === 'invite' ) {
//			if ( empty($_GET['validationid']) || empty($_GET['validationcode']) ) {
//				$this->error('抱歉：目前仅接受邀请注册，请向已注册的用户索要邀请链接');
//			}else if ( ! $invite = service('Validation')->getValidation() ) {
//				$this->error('抱歉：邀请码错误');
//			}
			$invite = h($_REQUEST['invite']);
			$inviteSet = model('Invite')->getSet();
			if($inviteSet['invite_set']=='close'){
				$this->error('邀请注册功能已关闭');
			}elseif ($inviteSet['invite_set']=='common') {
				if ( !$invite ) {
					$this->error('抱歉：目前仅接受邀请注册，请向已注册的用户索要邀请链接');
				}else {
					// 检查邀请码是否合法(邀请码即为用户ID)
					if( ! M('user')->where('`uid`='.intval($invite))->find() ) {
						$this->error('抱歉：邀请码错误');
					}
				}
			}elseif ($inviteSet['invite_set']=='invitecode'){
				if ( !$invite ) {
					$this->error('抱歉：目前仅接受邀请注册，请向已注册的用户索要邀请链接');
				}else{
					$info = model('Invite')->checkInviteCode($invite);
					if(!$info){
						$this->error('抱歉：邀请码错误');
					}
					if($info['is_used']==1){
						$this->error('邀请码已被使用');
					}
					$this->assign('inviteinfo',$info);
				}
			}
		}
		
		if ($invite) {
			$this->assign('invite', $invite);
		}
		
		//验证码
		$opt_verify = model('Xdata')->lget('siteopt');
		$opt_verify = $opt_verify['site_verify'];
		$opt_verify = in_array('register', $opt_verify);
		if ($opt_verify) {
			$this->assign('register_verify_on', 1);
		}
		
		$this->display();
	}
	
	// 创建帐号
	public function doRegister() {
		//$invite = service('Validation')->getValidation();
		
		//检查是否允许注册
		$opt_register = model('Xdata')->lget('register');
		$opt_register = $opt_register['register_type'];
		if ( $opt_register === 'closed' ) {
			$this->error('抱歉：本站已关闭注册');
		} else if ( $opt_register === 'invite' ) {
//			if ( empty($_POST['validationid']) || empty($_POST['validationcode']) ) {
//				$this->error('抱歉：目前仅接受邀请注册，请向已注册的用户索要邀请链接');
//			}else if ( !$invite ) {
//				$this->error('抱歉：邀请码错误');
//			}
			$invite = h($_REQUEST['invitecode']);
			$inviteSet = model('Invite')->getSet();
			if($inviteSet['invite_set']=='close'){
				$this->error('邀请注册功能关闭');
			}elseif($inviteSet['invite_set']=='common'){
				$inviteinfo['uid'] = $invite;
			}else{
				$inviteinfo = model('Invite')->checkInviteCode($invite);
				if(!$inviteinfo){
					$this->error('抱歉：邀请码错误');
				}
				if($inviteinfo['is_used']==1){
					$this->error('邀请码已被使用');
				}
			}
		}
		//参数合法性检查
		$required_field = array(
			'email'		=> 'Email',
			'password'	=> '密码',
			'repassword'=> '密码',
		);
		foreach ($required_field as $k => $v) {
			if ( empty($_POST[$k]) ) $this->error($v . '不可为空');
		}
		
		//验证码
		$opt_verify = model('Xdata')->lget('siteopt');
		$opt_verify = $opt_verify['site_verify'];
		$opt_verify = in_array('register', $opt_verify);
		if ( $opt_verify && md5($_POST['verify'])!=$_SESSION['verify'] ) {
			$this->error('验证码错误');
		}
		
		if ( ! $this->isValidEmail($_POST['email']) ) {
			$this->error('Email格式错误，请重新输入');
		}
		if( strlen($_POST['password']) < 6 || strlen($_POST['password']) > 16 || $_POST['password'] != $_POST['repassword'] ) {
			$this->error('密码必须为6-16位，且两次必须相同');
		}
		if ( ! $this->isEmailAvailable($_POST['email']) ) {
			$this->error('Email已经被使用，请重新输入');
		}

		
		// 是否需要Email激活
		$opt_email_activate = model('Xdata')->lget('register');
		$opt_email_activate = $opt_email_activate['register_email_activate'];
		
		// 注册
		$_POST['password']  = md5($_POST['password']);
		$_POST['ctime']	    = time();
		$_POST['is_active'] = $opt_email_activate == 1 ? 0 : 1;
		$dao = M('user');
		$uid = $dao->add($_POST);
		if (!$uid) $this->error('抱歉：注册失败，请稍后重试');

		// 将用户添加到myop_userlog，以使漫游应用能获取到用户信息
		$userlog = array(
			'uid'		=> $uid,
			'action'	=> 'add',
			'type'		=> '0',
			'dateline'	=> time(),
		);
		M('myop_userlog')->add($userlog);
		
		// 将邀请码设置已用
		model('Invite')->setInviteCodeUsed($invite);
		
		// 互相关注好友
		if ( $inviteinfo['uid'] ) {
			D('Follow','weibo')->dofollow($uid,$inviteinfo['uid']);
			D('Follow','weibo')->dofollow($inviteinfo['uid'],$uid);
			//邀请人积分操作
			X('Credit')->setUserCredit($inviteinfo['uid'],'invite_friend');
		}
		
		// 邮件激活
		if ( $opt_email_activate == 1 ) {
			$this->activate($uid, $_POST['email'], $invite);
		}else {
			
			$this->setSessionAndCookie($uid, $_POST['uname'], $_POST['email']);
			
			$this->recordLogin($uid);
			
			// 关联操作
			$this->registerRelation($uid, $invite);
			
			//service('Validation')->unsetValidation();	

			//注册完毕，跳转至帐号修改页
			redirect( U('home/public/userinfo') );
		}
	}
	
	//个人次料完成
	function userinfo(){

		if( $_POST ){
			$data['uname'] = t( $_POST['nickname'] );
			if(mb_strlen($data['uname'],'UTF8')>10){
				$this->error('昵称不能超过10个字符');
			}
			$data['sex']   		= intval( $_POST['sex'] );
			$data['province']   = intval( $_POST['area_province'] );
			$data['city']   	= intval( $_POST['area_city'] );
			$data['location']   = getLocation($data['province'],$data['city']);
			$data['is_init']   	= 1;
			M('user')->where('uid='.$this->mid)->data($data)->save();
			redirect( U('home/public/followuser') );
		}else{
			$this->display();
		}
	}
	
	//关注推荐用户
	function followuser(){
		if($_POST){
			if($_POST['followuid']){
				foreach ($_POST['followuid'] as $value){
					D('Follow','weibo')->dofollow($this->mid,$value,0);
				}
			}
			if($_POST['doajax']){
				echo '1';
			}else{
				redirect(U('home/user/index'));
			}
		}else{
			//$data['commenduser'] = M('user')->where('is_active=1 AND is_init=1 AND uid<>'.$this->mid)->limit(12)->findall();
			$data['commenduser'] = M()->query("SELECT fid,count(uid) as count FROM ts_weibo_follow WHERE fid NOT IN(SELECT fid FROM ts_weibo_follow WHERE uid={$this->mid} AND type=0) AND fid<>{$this->mid} AND type=0 GROUP BY fid ORDER by count(uid) DESC LIMIT 12");
			foreach ($data['commenduser'] as $key=>$value){
				$data['commenduser'][$key] = M('user')->where('uid='.$value['fid'])->find();
				if(!$data['commenduser'][$key]['is_init']) {
					unset($data['commenduser'][$key]);
					continue;
				}
				$data['commenduser'][$key]['follower_count'] = $value['count'];
				$data['commenduser'][$key]['followstate'] = getFollowState($this->mid, $value['fid']);
			}
			$this->assign( $data );
			$this->display();
		}
	}
	
	//使用邀请码注册
	public function inviteRegister() {
		if ( ! $invite = service('Validation')->getValidation() ) {
			$this->error('邀请码错误');
		}
			
		if ( "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"] != $invite['target_url'] ) {
			$this->error('URL错误');
		}
		$this->assign('invite', $invite);
		
		$invite['data']			= unserialize($invite['data']);
		$map['tpl_record_id']	= $invite['data']['tpl_record_id'];
		$tpl_record 			= model('Template')->getTemplateRecordByMap($map, '', 1);
		$tpl_record 			= $tpl_record['data'][0]['data'];
		$this->assign('template', $tpl_record);

		//邀请人的好友
		$friend = model('Friend')->getFriendList($invite['from_uid'], null, 9);
		$this->assign($friend);
		
		$this->display('invite');
	}
	
	public function resendEmail() {
		$invite = service('Validation')->getValidation();
		$this->activate(intval($_GET['uid']), $_GET['email'], $invite, 1);
	}
	
	//发送激活邮件
	public function activate($uid, $email, $invite = '', $is_resend = 0) {
		//设置激活路径
		$activate_url  = service('Validation')->addValidation($uid, '', U('home/Public/doActivate'), 'register_activate', serialize($invite));
		if ($invite) {
			$this->assign('invite', $invite);
		}
		$this->assign('url',$activate_url);

		//设置邮件模板
		$body = <<<EOD
感谢您的注册!<br>

请马上点击以下注册确认链接，激活您的帐号！<br>

<a href="$activate_url" target='_blank'>$activate_url</a><br/>

如果通过点击以上链接无法访问，请将该网址复制并粘贴至新的浏览器窗口中。<br/>

如果你错误地收到了此电子邮件，你无需执行任何操作来取消帐号！此帐号将不会启动。
EOD;
		// 发送邮件
		global $ts;
		$email_sent = service('Mail')->send_email($email, "激活{$ts['site']['site_name']}帐号",$body);
		
		// 渲染输出
		if ($email_sent) {
			$email_info = explode("@", $email);
			switch ($email_info[1]) {
				case "qq.com"    : $email_url = "mail.qq.com";break;
				case "163.com"   : $email_url = "mail.163.com";break;
				case "126.com"   : $email_url = "mail.126.com";break;
				case "gmail.com" : $email_url = "mail.google.com";break;
				default          : $email_url = "mail.".$email_info[1];
			}
			
			$this->assign("uid",$uid);
			$this->assign('email', $email);
			$this->assign('is_resend', $is_resend);
			$this->assign("email_url",$email_url);
			$this->display('activate');
		}else {
			$this->assign('jumpUrl', U('home/Index/index'));
			$this->error('抱歉：邮件发送失败，请稍后重试');
		}
	}
	
	public function doActivate() {
		$invite = service('Validation')->getValidation();
		if (!$invite) {
			$this->assign('jumpUrl', U('home/Public/register'));
        	$this->error('抱歉：激活码错误，请重新注册');
		}
		$uid = $invite['from_uid'];
        
        $user = M('user')->where("`uid`=$uid")->find();
        if ($user['is_active'] == 1) {
        	$this->assign('jumpUrl', U('home/Public/login'));
        	$this->success('您的帐号已激活');
        	exit;
        } else if ($user['is_active'] == 0) {
        	//激活帐户
        	$res = M('user')->where("`uid`=$uid")->setField('is_active', 1);
        	if (!$res) $this->error('抱歉：激活失败');
        	
			$this->setSessionAndCookie($user['uid'], $user['uname'], $user['email']);
			
			$this->recordLogin($user['uid']);
			
			//关联操作
			$this->registerRelation($user['uid'], $invite);
			
			service('Validation')->unsetValidation();

			$this->assign('jumpUrl', U('home/Account/index'));
			$this->success("恭喜：激活成功");
        } else {
        	$this->assign('jumpUrl', U('home/Public/register'));
        	$this->error('抱歉：激活码错误，请重新注册');
        }
	}
	
	public function sendPassword() {
		$this->display();
	}
	
	public function doSendPassword() {
		$_POST["email"]	= t($_POST["email"]);
		if ( !$this->isValidEmail($_POST['email']) )
			$this->error('邮箱格式错误');
		
		$user =	M("user")->where('`email`="' . $_POST['email'] . '"')->find();
		
        if(!$user) {
        	$this->error("该邮箱没有注册");
        }else {
            $code = base64_encode( $user["uid"] . "." . md5($user["uid"] . '+' . $user["password"]) );
            $url  = U('home/Public/resetPassword', array('code'=>$code));
            $body = <<<EOD
<strong>{$user["uname"]}，你好：</strong><br/>

您只需通过点击下面的链接重置您的密码：<br/>

<a href="$url">$url</a><br/>

如果通过点击以上链接无法访问，请将该网址复制并粘贴至新的浏览器窗口中。<br/>

如果你错误地收到了此电子邮件，你无需执行任何操作来取消帐号！此帐号将不会启动。
EOD;
			
			global $ts;
			$email_sent = service('Mail')->send_email($user['email'], "重置{$ts['site']['site_name']}密码", $body);
			
            if ($email_sent) {
	            $this->assign('jumpUrl', SITE_URL);
	            $this->success("已把密码发送到你的邮箱$email中，请注意查收");
            }else {
            	$this->error('抱歉：邮件发送失败，请稍好重试');
            }
		}
	}
	
	public function resetPassword() {
		$code = explode('.', base64_decode($_GET['code']));
        $user = M('user')->where('`uid`=' . $code[0])->find();
        
        if ( $code[1] == md5($code[0].'+'.$user["password"]) ) {
	        $this->assign('email',$user["email"]);
	        $this->assign('code', $_GET['code']);
	        $this->display();
        }else {
        	$this->error("抱歉：链接错误");
        }
	}
	
	public function doResetPassword() {
		if($_POST["password"] != $_POST["repassword"]) {
        	$this->error("输入的两次密码必须一致，请重新输入");
        }
        
		$code = explode('.', base64_decode($_POST['code']));
        $user = M('user')->where('`uid`=' . $code[0])->find();
        
        if ( $code[1] == md5($code[0] . '+' . $user["password"]) ) {
	        $user['password'] = md5($_POST['password']);
	        $res = M('user')->save($user);
	        if ($res) {
	        	$this->assign('jumpUrl', U('home/Public/login'));
	        	$this->success('保存成功');
	        }else {
	        	$this->error('抱歉：保存失败，请稍后重试');
	        }
        }else {
        	$this->error("抱歉：安全码错误");
        }
	}
	
	public function doModifyEmail() {
    	if ( !$validation = service('Validation')->getValidation() ) {
    		$this->error('验证码错误');
    	}
    	if ( "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"] != $validation['target_url'] ) {
    		$this->error('URL错误');
		}
    	
    	$validation['data'] = unserialize($validation['data']);
    	$map['uid']			= $validation['from_uid'];
    	$map['email']		= $validation['data']['oldemail'];
		if ( M('user')->where($map)->setField('email', $validation['data']['email']) ) {
			service('Validation')->unsetValidation();
			service('Passport')->logoutLocal();
			$this->assign('jumpUrl', SITE_URL);
			$this->success('激活新Email成功，请重新登录');
		}else {
			$this->error('抱歉：激活新Email失败');
		}
    }
	
	//检查Email地址是否合法
	public function isValidEmail($email) {
		return preg_match("/[_a-zA-Z\d\-\.]+@[_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+$/i", $email) !== 0;
	}
	
	//检查Email是否可用
	public function isEmailAvailable($email = null) {
		$return_type = empty($email) ? 'ajax' 		   : 'return';
		$email		 = empty($email) ? $_POST['email'] : $email;
		
		$res = M('user')->where('`email`="'.$email.'"')->find();
		
		if ( !$res ) {
			if ($return_type === 'ajax') echo 'success';
			else return true;
		}else {
			if ($return_type === 'ajax') echo '邮箱已被占用';
			else return false;
		}
	}
	
	//检查昵称是否为唯一
	
	public function isValidNickName( $name ){
		$name		  = empty($name) 		 ? t($_POST['nickname']) 				: $name;
		$res = M('user')->where("uname='{$name}'")->count();
		if ( !$res ) {
			echo 'success';
		}else {
			if ($return_type === 'ajax') echo '已经有使用过此昵称';
			else return false;
		}
	}
	
	//检查是否真实姓名。支持ajax和return
	public function isValidRealName($name = null, $opt_register = null) {
		$return_type  = empty($name) 		 ? 'ajax' 							: 'return';
		$name		  = empty($name) 		 ? t($_POST['uname']) 				: $name;
		$opt_register = empty($opt_register) ? model('Xdata')->lget('register') : $opt_register;
		
		if ($opt_register['register_realname_check'] == 1) {
			$lastname = explode(',', $opt_register['register_lastname']);
			$res = in_array( substr($name, 0, 3), $lastname ) || in_array( substr($name, 0, 6), $lastname );
		}else {
			$res = true;
		}
		
		if ($res) {
			if ($return_type === 'ajax') echo 'success';
			else return true;
		}else {
			if ($return_type === 'ajax') echo 'fail';
			else return false;
		}
	}
	
	public function isValidInviteCode($invitecode) {
		return true;
	}
	
	//成功登录后，设置Session和Cookie
	public function setSessionAndCookie($uid, $uname, $email, $remember = false) {
		$_SESSION['mid']	= $uid;
		$_SESSION['uname']	= $uname;
		$remember ? 
			cookie('LOGGED_USER',base64_encode('thinksns.'.$uid),(3600*24*365)) : 
			cookie('LOGGED_USER',base64_encode('thinksns.'.$uid),(3600*2));
	}
	
	//登录记录
	public function recordLogin($uid) {
		$data['uid']	= $uid;
		$data['ip']		= get_client_ip();
		$data['place']	= convert_ip($data['ip']);
		$data['ctime'] 	= time();
		M('login_record')->add($data);
		//登陆积分
		X('Credit')->setUserCredit($uid,'user_login');
	}
	
	//注册的关联操作
    public function registerRelation($uid, $invite = '') {
    	if ( empty($uid) ) return ;

    	// 使用邀请码，建立与邀请人的关系
		
        // 默认关注的好友
    	$dao = D('Follow','weibo');
		$auto_freind = model('Xdata')->lget('register');
		$auto_freind['register_auto_friend'] = explode(',', $auto_freind['register_auto_friend']);
		foreach($auto_freind['register_auto_friend'] as $v) {
			if ( ($v = intval($v)) <= 0 )
				continue ;
			$dao->dofollow($v,$uid);
			$dao->dofollow($uid,$v);
		}
        
        // 默认添加的群组
        
		// 添加动态
		
		// 开通个人空间
		$data['uid'] = $uid;
		model('Space')->add($data);

		//注册成功 初始积分
		X('Credit')->setUserCredit($uid,'init_default');
	}
	
	public function verify() {
        require_once(SITE_PATH.'/addons/libs/Image.class.php');
        require_once(SITE_PATH.'/addons/libs/String.class.php');
    	Image::buildImageVerify();
	}
	
    //上传图片
    public function uploadpic(){
    	if( $_FILES['pic'] ){
    		//执行上传操作
    		$savePath =  $this->getSaveTempPath();
    		$filename = md5( time().'teste' ).'.'.substr($_FILES['pic']['name'],strpos($_FILES['pic']['name'],'.')+1);
	    	if(@copy($_FILES['pic']['tmp_name'], $savePath.'/'.$filename) || @move_uploaded_file($_FILES['pic']['tmp_name'], $savePath.'/'.$filename)) 
	        {
	        	$result['boolen']    = 1;
	        	$result['type_data'] = 'temp/'.$filename;
	        	$result['picurl']    = __UPLOAD__.'/temp/'.$filename;
	        } else {
	        	$result['boolen']    = 0;
	        	$result['message']   = '上传失败';
	        }
    	}else{
        	$result['boolen']    = 0;
        	$result['message']   = '上传失败';
    	}
    	
    	exit( json_encode( $result ) );
    }
    
    //上传临时文件
	public function getSaveTempPath(){
        $savePath = SITE_PATH.'/data/uploads/temp';
        if( !file_exists( $savePath ) ) mk_dir( $savePath  );
        return $savePath;
    }
    
    // 地区管理
    public function getArea() {
    	echo json_encode(model('Area')->getAreaTree());
    }
    
	/**  文章  **/
	public function document() {
		$list	= array();
		$detail = array();
		$res    = M('document')->where('`is_active`=1')->order('`display_order` ASC,`document_id` ASC')->findAll();

		// 获取content为url且在页脚显示的文章
		global $ts;
		$ids_has_url = array();
		foreach($ts['footer_document'] as $v)
			if( !empty($v['url']) )
				$ids_has_url[] = $v['document_id'];

		$_GET['id'] = intval($_GET['id']);

		foreach($res as $v) {
			// 不显示content为url且在页脚显示的文章
			if ( in_array($v['document_id'], $ids_has_url) )
				continue ;

			$list[] = array('document_id'=>$v['document_id'], 'title'=>$v['title']);

			// 当指定ID，且该ID存在，且该文章的内容不是url时，显示指定的文章。否则显示第一篇
			if ( $v['document_id'] == $_GET['id'] || empty($detail) ) {
				$v['content'] = htmlspecialchars_decode($v['content']);
				$detail = $v;
			}
		}
		unset($res);

		$this->assign('detail', $detail);
		$this->assign('list', $list);
		$this->display();
	}
	
	/** 错误页面 **/
	public function error404() {
		$this->display('404');
	}
}
