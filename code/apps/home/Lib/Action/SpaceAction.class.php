<?php 
/**
 * 个人空间
 */
class SpaceAction extends Action{
	
	function _initialize(){
	   if( is_string($_GET['uid']) ){
	   		$domainuser = M('user')->where("domain='".h($_GET['uid'])."'")->find();
	   		if($domainuser){
	   			$this->uid = $domainuser['uid'];
	   			$this->assign('uid',$this->uid);
	   		}
	   }
	   
	   if( ACTION_NAME=='detail'){
			$intId = intval( $_GET['id'] );
	    	$data['mini']      =  D('Operate','weibo')->getOneLocation( $intId );
	    	if(!$data['mini']) $this->error('提交错误参数');
			$data['comment']   =  D('Comment','weibo')->getComment( $intId );
			$data['privacy'] = D('UserPrivacy','home')->getPrivacy($this->mid,$data['mini']['uid']);
    		$this->assign( $data );	    	
	    	$this->uid = $data['mini']['uid'];	   	
	   }
	   
   	   $userInfo = M('user')->where('uid='.$this->uid)->find();
   	   if(!$userInfo) $this->error('用户不存在或提交了错误参数');
   	   $this->assign('userinfo',$userInfo);
       $this->getSpaceCount( $this->uid );
	}

	private function getSpaceCount($uid){
		$followInfo = getUserFollow( $uid );
		$data['followstate'] 	=  D('Follow','weibo')->getState( $this->mid , $uid , 0 );
		$data['isBlackList']    = isBlackList($this->mid,$uid);
		$data['privacy']        = D('UserPrivacy','home')->getPrivacy($this->mid,$uid);
		$data['spaceCount']['miniblog']   = M('weibo')->where('uid='.$uid)->count();
		$data['spaceCount']['following']  = $followInfo['following'];
		$data['spaceCount']['follower']   = $followInfo['follower'];
		$data['spaceCount']['message']   = 0;
		$data['hotTopic'] =  D('Topic','weibo')->getHot();
		$data['usertags'] =  D('UserTag')->getUserTagList( $this->uid );
		$this->assign( $data );
	}
	
    //用户空间首页
    function index(){
    	$strType = h($_GET['type']);
        $data['list'] =  D('Operate','weibo')->getSpaceList( $this->uid , $strType );
        $data['user'] =  M('User')->where('uid='.$this->uid)->find();
        $data['type'] = $strType;
        
		//被浏览积分
		if($this->uid!=$this->mid)
			X('Credit')->setUserCredit($this->uid,'space_visited');

        $this->assign( $data );
    	$this->display();
    }
    
    //查看微博详细 
    function detail(){
    	$this->display();
    }
   
    //关注
    function follow(){
    	$data['type'] = ($_GET['type']=='follower')?'follower':'following';
    	$data['list'] = D('Follow','weibo')->getList($this->uid,$data['type']);
    	$this->assign($data);
    	$this->display();
    }
    

    
    //个人资料
    function profile(){
    	$pUserProfile = D('UserProfile');
    	$pUserProfile->uid = $this->uid;
    	$data['userInfo']         = $pUserProfile->getUserInfo();
    	$this->assign( $data );
    	$this->display();
    }
}

?>