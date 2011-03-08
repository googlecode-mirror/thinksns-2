<?php
// +----------------------------------------------------------------------
// | OpenSociax [ open your team ! ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://www.sociax.com.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: genixsoft.net <智士软件>
// +----------------------------------------------------------------------
// $Id$

/*
 * 为移植ts1.6的应用.而开发的兼容API方法.包含常用API
 *
 */
class TS16APIService extends Service {

	//获取当前登录用户
	function user_getLoggedInUser() {
		return intval($_SESSION['mid']);
	}

	//获取好友列表，2.0为我关注的列表
	function friend_get($uid=0) {
		if(intval($uid)==0){
			$uid	=	$this->user_getLoggedInUser();
		}

	}

	//获取用户信息
	function user_getInfo($uid) {
		$user	=	getUserInfo($uid);
		if($user){
			$user['name']	=	$user['uname'];
		}
		return $user;
	}

	//发布动态
	function feed_publish($type,$title,$body,$appid) {
		$data['title']	=	$title;
		$data['body']	=	$body;
		return X('Feed')->put($type,$data,$uid);
	}

	//获取评论数
	function comment_getCount() {
	}

	//发送评论通知
	function comment_notify() {
	}

	/**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @author melec制作
     * @access public
     +----------------------------------------------------------
     */
    public function __construct($text) {
		//1.判断是否安装、是否运行该服务，系统服务可以不做判断
		//2.服务初始化
		//$this->init($text);
		//$this->run();
    }

	//服务初始化
	public function init($text=''){
	}

	//运行服务
	public function run(){
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