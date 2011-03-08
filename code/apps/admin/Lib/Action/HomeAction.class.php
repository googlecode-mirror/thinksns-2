<?php
class HomeAction extends AdministratorAction {
	
	// 统计信息
	public function statistics() {
		$statistics = array();
		
		/** 为了防止与应用别名重名，“服务器信息”、“用户信息”、“开发团队”作为key前面有空格 **/
		
		// 服务器信息
		$serverInfo['核心版本']        	= 'ThinkSNS 2.0';
        $serverInfo['服务器系统及PHP版本']	= PHP_OS.' / PHP v'.PHP_VERSION;
        $serverInfo['服务器软件'] 			= $_SERVER['SERVER_SOFTWARE'];
        $serverInfo['最大上传许可']     	= ( @ini_get('file_uploads') )? ini_get('upload_max_filesize') : '<font color="red">no</font>';
        
        $mysqlinfo = M('')->query("SELECT VERSION() as version");
        $serverInfo['MySQL版本']			= $mysqlinfo[0]['version'] ;
        
        $t = M('')->query("SHOW TABLE STATUS LIKE '".C('DB_PREFIX')."%'");
        foreach ($t as $k){
            $dbsize += $k['Data_length'] + $k['Index_length'];
        }
        $serverInfo['数据库大小']			= byte_format( $dbsize );
        $statistics[' 服务器信息'] = $serverInfo;
        unset($serverInfo);
        
        
        // 用户信息
        $user['当前在线'] = getOnlineUserCount();
        $user['注册用户'] = M('user')->where('`is_active` = 1 AND `is_init` = 1')->count();
        $statistics[' 用户信息'] = $user;
        unset($user);
        
        // 应用统计
        $applist = array();
        $res = model('App')->where('`statistics_entry`<>""')->field('app_name,app_alias,statistics_entry')->order('display_order ASC')->findAll();
        foreach ($res as $v) {
        	$d = explode('/', $v['statistics_entry']);
        	$d[1] = empty($d[1]) ? 'index' : $d[1];
        	$statistics[$v['app_alias']] = D($d[0], $v['app_name'])->$d[1]();
        }
        
        // 开发团队
        $statistics[' 开发团队'] = array(
        	'版权所有'	=> '<a href="http://www.zhishisoft.com" target="_blank">智士软件（北京）有限公司</a>',
        	'项目经理'	=> '冯涛',
        	'美工设计'	=> '赵杰',
        	'开发团队'	=> '冷浩然、杨德升、刘晓庆、王祚、彭灵俊、韦心红、陈伟川',
        );
        
        $this->assign('statistics', $statistics);
        $this->display();
	}
}