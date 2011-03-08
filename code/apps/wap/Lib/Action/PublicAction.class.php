<?php
class PublicAction extends Action {
	
	public function login() {
		// 登录验证
		$passport = service('Passport');
		if ($passport->isLogged()) {
			redirect(U('wap/Index/index'));
		}
		global $ts;
		$this->assign('site_name', $ts['site']['site_name'].' WAP版');
		$this->display();
	}
	
	public function doLogin() {
		//使用url+sessionId的方式解决手机不支持cookie的情况
		//http://topic.csdn.net/u/20091116/15/594891e2-9046-40b2-b324-b2220af31c4e.html?70417
		if (empty($_POST['email']) || empty($_POST['password'])) {
			redirect(U('wap/Public/login'), 3, '用户名和密码不能为空');
		}
		if (!isValidEmail($_POST['email'])) {
			redirect(U('wap/Public/login'), 3, 'Email格式错误，请重新输入');
		}
		if ($user = service('Passport')->getLocalUser($_POST['email'], $_POST['password'])) {
			if ($user['is_active'] == 0) {
				redirect(U('wap/Public/login'), 3, '帐号尚未激活，请激活后重新登录');
			}
			$this->setSessionAndCookie($user['uid'], $user['uname'], $user['email'], intval($_POST['remember']) === 1);
            $this->recordLogin($user['uid']);
            redirect(U('wap/Index/index'));
		}else {
			redirect(U('wap/Public/login'), 3, '帐号或密码错误，请重新输入');
		}
	}
	
	public function logout() {
		service('Passport')->logoutLocal('');
		redirect(U('wap/Public/login'));
	}
	
	public function setSessionAndCookie($uid, $uname, $email, $remember = false) {
        $_SESSION['mid']    = $uid;
        $_SESSION['uname']  = $uname;
        $remember ? 
			cookie('LOGGED_USER',base64_encode('thinksns.'.$uid),(3600*24*365)) : 
			cookie('LOGGED_USER',base64_encode('thinksns.'.$uid),(3600*2));
    }
    
    //登录记录
    public function recordLogin($uid) {
        $data['uid']    = $uid;
        $data['ip']     = get_client_ip();
        $data['place']  = convert_ip($data['ip']);
        $data['ctime']  = time();
        M('login_record')->add($data);
    }
}