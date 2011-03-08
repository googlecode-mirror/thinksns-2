<?php
/**
 * 通行证服务
 */
class PassportService extends Service {

	/**
	 * 验证用户是否已登录
	 * @return boolean
	 */
	public function isLogged(){
		$_cookie_user		=	t(cookie('LOGGED_USER'));
		$_session_user		=	t($_SESSION['uname']);
		$_session_user_id	=	intval($_SESSION['mid']);
		
		// 验证本地系统登录
		if($_session_user_id){
			return true;
		}elseif ($_cookie_user){
			return $this->cookieLoginLocal($_cookie_user);
		}else{
			return false;
		}
	}

	/**
	 * 根据Email和未加密的密码获取本地用户 (密码为false时只验证Email)
	 * @param string         $email
	 * @param string|boolean $password
	 */
	public function getLocalUser($email,$password=false) {
		$map['email']		 = $email;
		if($password !== false) {
			$map['password'] = md5($password);
		}
		$user = model('User')->where($map)->find();
		if(!$user){
			return false;
		}else{
			return $user;
		}
	}

	/**
	 * 使用cookie登陆
	 * 
	 * @param string $cookieId
	 * @return boolean
	 */
	public function cookieLoginLocal($cookieId){
		$cookieId = explode( '.', base64_decode($cookieId) );
		if ($cookieId[0] !== 'thinksns') {
			return false;
		}else {
			$userInfo = M('user')->where('uid='.$cookieId[1])->find();
			return $this->loginLocal($userInfo['email']);
		}
	}

	/**
	 * 登陆到本地 (密码为false时仅验证Email)
	 * @param string         $email
	 * @param string|boolean $password
	 * @return boolean
	 */
	public function loginLocal($email,$password=false) {
		// 获取账号信息
		$user	=	$this->getLocalUser($email,$password);
		
		if(!$user) {
			return false;
		}else{
			//注册session
			$_SESSION['mid']		=	$user['uid'];
			$_SESSION['uname']		=	$user['uname'];

			//登录记录
			$this->recordLogin($user['uid']);
			return true;
		}
	}
	
	/**
	 * 检查是否登陆后台
	 */
	public function isLoggedAdmin() {
		return $_SESSION['ThinkSNSAdmin'] == '1';
	}
	
	/**
	 * 登陆后台
	 * 
	 * @param int    $uid      用户ID,不能和email同时为空
	 * @param string $email    用户Email,不能和用户ID同时为空
	 * @param string $password 未加密的密码,不能为空
	 * @return boolean
	 */
	public function loginAdmin($uid = NULL, $email = NULL, $password) {
		// uid和email必须至少有一个为合法值，密码必须不为空
		if ( (($uid = intval($uid)) <= 0 && empty($email)) || empty($password) ) {
			return false;
		}
		
		// 检查用户名/密码
		$uid > 0		&& $map['uid']	 = $uid;
		!empty($email)	&& $map['email'] = $email;
		$map['password'] = md5($password);
		if ( ! ($user = M('user')->where($map)->find()) ) {
			unset($_SESSION['ThinkSNSAdmin']);
			return false;
		}
		
		// 检查是否拥有admin/Index/index权限
		if ( service('SystemPopedom')->hasPopedom($user['uid'], 'admin/Index/index', false) ) {
			$_SESSION['ThinkSNSAdmin']	= 1;
			$_SESSION['mid']			= $user['uid'];
			$_SESSION['uname']			= $user['uname'];

			//登录记录
			$this->recordLogin($user['uid']);
			return true;
		}else {
			unset($_SESSION['ThinkSNSAdmin']);
			return false;
		}
	}
	
	/**
	 * 注销本地登录
	 * @return void
	 */
	public function logoutLocal() {
		//注销session
		unset($_SESSION['mid']);
		unset($_SESSION['uname']);
		unset($_SESSION['userInfo']);
		unset($_SESSION['AppUserInfo']);

		//注销cookie
		cookie('LOGGED_USER',NULL);
		
		// 注销管理员
		unset($_SESSION['ThinkSNSAdmin']);
	}
	
	/**
	 * 登录记录
	 * 
	 * @param int $uid 用户ID
	 */
	public function recordLogin($uid) {
		$data['uid']	= $uid;
		$data['ip']		= get_client_ip();
		$data['place']	= convert_ip($data['ip']);
		$data['ctime']	= time();
		M('login_record')->add($data);
	}

	/* 后台管理相关方法 */
	
	// 运行服务，系统服务自动运行
	public function run(){
		return;
	}

	//启动服务，未编码
	public function _start(){
		return true;
	}

	//停止服务，未编码
	public function _stop(){
		return true;
	}

	//卸载服务，未编码
	public function _install(){
		return true;
	}

	//卸载服务，未编码
	public function _uninstall(){
		return true;
	}
}
?>