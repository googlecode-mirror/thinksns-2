<?php
class AppsAction extends AdministratorAction {

	protected $_host_type;

	public function _initialize() {
		parent::_initialize();
		$this->_host_type = array('0'=>'本地应用', '1'=>'远程应用');
	}

	private function __getAppInfo($path_name, $using_lowercase = true) {
		$filename = SITE_PATH . '/apps/' . $path_name . '/Appinfo/info.php';
		if ( is_file($filename) ) {
			$info = include_once $filename;
			$info['HOST_TYPE_ALIAS']	= $this->_host_type[$info['HOST_TYPE']];
			$info['APP_ALIAS']			= $info['NAME'];
			$info['PATH_NAME'] 			= $path_name;
			$info['APP_NAME']			= $path_name;
			$info['CONTRIBUTOR_NAME']	= $info['CONTRIBUTOR_NAMES'];
			return $using_lowercase ? array_change_key_case($info) : array_change_key_case($info,CASE_UPPER);
		}else {
			return NULL;
		}
	}

	// 安装应用 + 编辑应用
	private function __updateApp($method, $info) {
		if ( !in_array($method, array('add','save')) ) {
			return false;
		}
		if ($method == 'add') {
			$data['host_type']					= intval($info['host_type']);
			$data['homepage_url']				= $info['homepage_url'];
			$data['sidebar_support_submenu']	= intval($info['sidebar_support_submenu']);
			$data['author_name']				= $info['author_name'];
			$data['author_email']				= $info['author_email'];
			$data['author_homepage_url']		= $info['author_homepage_url'];
			$data['contributor_name']			= $info['contributor_names'];
			$data['release_date']				= $info['release_date'];
			$data['last_update_date']			= $info['last_update_date'];
		}else {
			$data['app_id']						= intval($_POST['app_id']);
		}

		$data['app_name']					= $method=='add' ? t($_POST['path_name']) : t($_POST['app_name']);
		$data['app_alias']					= t($_POST['app_alias']);
		$data['description']				= htmlspecialchars($_POST['description']);
		$data['status']						= intval($_POST['status']);
		$data['category']					= t($_POST['category']);
		$data['app_entry']					= t($_POST['app_entry']);
		$data['icon_url']					= t($_POST['icon_url']);
		$data['large_icon_url']				= t($_POST['large_icon_url']);
		$data['admin_entry']				= t($_POST['admin_entry']);
		$data['statistics_entry']			= t($_POST['statistics_entry']);
		$data['sidebar_title']				= t($_POST['sidebar_title']);
		$data['sidebar_entry']				= t($_POST['sidebar_entry']);
		$data['sidebar_icon_url']			= t($_POST['sidebar_icon_url']);
		$data['sidebar_is_submenu_active']	= intval($_POST['sidebar_is_submenu_active']);
		$data['ctime']						= time();
		$res = model('App')->$method($data);
		if ($res && $method = 'add') {
			//为排序方便，将order = id
			model('App')->where('`app_id`='.$res)->setField('display_order', $res);
		}
		return $res;
	}

	public function applist() {
		$installed = model('App')->getAllAppByPage();
		$this->assign($installed);
		$this->display();
	}

	public function install() {
		$uninstalled = array();
		$installed 	 = model('App')->getAllApp('app_name');
		$installed   = getSubByKey($installed, 'app_name');
		
		//默认应用,不能安装卸载.
		$installed = array_merge($installed, C('DEFAULT_APPS'));
		
		require_once SITE_PATH . '/addons/libs/Io/Dir.class.php';
		$dirs	= new Dir(SITE_PATH.'/apps/');
		$dirs	= $dirs->toArray();
		foreach($dirs as $v){
			if ( $v['isDir'] && !in_array($v['filename'], $installed) ) {
				if ( $info = $this->__getAppInfo($v['filename']) ) {
					$uninstalled[]	= $info;
				}
			}
		}
		$this->assign('uninstalled', $uninstalled);
		$this->display();
	}

	public function preinstall() {
		$info = $this->__getAppInfo($_GET['path_name']);
		$this->assign($info);
		$this->display('edit');
	}

	public function doInstall() {
		$_POST['path_name'] = t($_POST['path_name']);
		$info = $this->__getAppInfo($_POST['path_name']);
		if (!$info) {
			$this->error('参数错误');
		}

		if ( model('App')->isAppNameExist($_POST['path_name']) ) {
			$this->error('应用已存在');
		}

		$install_script = SITE_PATH . "/apps/{$info['path_name']}/Appinfo/install.php";
		if ( is_file($install_script) ) {
			include_once $install_script;
		}

		if ( ! $this->__updateApp('add', $info) ) {
			$this->error('安装失败');
		}

		$this->assign('jumpUrl', U('admin/Apps/install'));
		$this->success('安装成功');
	}

	public function edit() {
		$info = model('App')->getAppDetailById(intval($_GET['app_id']));
		$info['path_name']			= $info['app_name'];
		$info['host_type_alias']	= $this->_host_type[$info['host_type']];
		$this->assign($info);
		$this->display();
	}

	public function doEdit() {
		if (! is_file(SITE_PATH . '/apps/' . $_POST['app_name'] . '/Appinfo/info.php') ) {
			$this->error('应用名称“'.$_POST['app_name'].'”错误，请确认apps目录下存在该应用');
		}
		if ( model('App')->isAppNameExist($_POST['app_name'], intval($_POST['app_id'])) ) {
			$this->error('应用名称“'.$_POST['app_name'].'”已存在');
		}
		if ( ! $this->__updateApp('save') ) {
			$this->error('保存失败');
		}else {
			$this->assign('jumpUrl', U('admin/Apps/applist'));
			$this->success('保存成功');
		}
	}

	public function uninstall() {
		$_POST['app_id'] = intval($_GET['app_id']);
		$app_name = model('App')->where('`app_id`='.$_POST['app_id'])->getField('app_name');

		if ( ! $app_name ) {
			$this->error('应用不存在');
		}

		$uninstall_script = SITE_PATH . "/apps/{$app_name}/Appinfo/uninstall.php";
		if ( is_file($uninstall_script) ) {
			include_once $uninstall_script;
		}

		if ( ! model('App')->deleteApp($_POST['app_id']) ) {
			$this->error('卸载失败');
		}

		$this->assign('jumpUrl', U('admin/Apps/applist'));
		$this->success('卸载成功');
	}

	public function doSetStatus() {
		$post['app_id'] = intval($_POST['app_id']);
		$post['status'] = intval($_POST['status']);
		$res = M('app')->save($post);
		echo $res ? '1' : '0';
	}

	public function doAppOrder() {
		$_POST['app_id'] = intval($_POST['app_id']);
		$_POST['baseid'] = intval($_POST['baseid']);
		if ( $_POST['app_id'] <= 0 || $_POST['baseid'] <= 0 ) {
			echo 0;
			exit;
		}
		$dao = model('App');
		$res = $dao->getAppDetailById( array($_POST['app_id'], $_POST['baseid']), 'app_id,display_order' );
		if ( count($res) < 2 ) {
			echo 0;
			exit;
		}

		//转为结果集为array('id'=>'order')的格式
    	foreach($res as $v) {
    		$order[$v['app_id']] = intval($v['display_order']);
    	}
    	unset($res);

    	//交换order值
    	$res = 		   $dao->where('`app_id`=' . $_POST['app_id'])->setField( 'display_order', $order[$_POST['baseid']] );
    	$res = $res && $dao->where('`app_id`=' . $_POST['baseid'])->setField( 'display_order', $order[$_POST['app_id']] );

    	if($res) echo 1;
    	else	 echo 0;
	}
}
?>