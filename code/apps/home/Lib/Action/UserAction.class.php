<?php 
class UserAction extends Action{
	
	function _initialize(){
        $data['hotTopic'] =  D('Topic','weibo')->getHot();
        $data['followTopic'] =  D('Follow','weibo')->getTopicList($this->mid);
        $this->assign($data);
	}
	
    //个人首页
    function index(){
   		$strType = h($_GET['type']); 
   		$data['show_feed'] = ( isset( $_COOKIE['feed'] )  )? intval($_COOKIE['feed']):0;
   		if($data['show_feed']){
   			$data['list']     =  X('Feed')->get($this->mid);
   		}else{
       		$data['list']     =  D('Operate','weibo')->getHomeList($this->mid , $strType,'','' );
   		}
   		
        $bind = M('login')->where('uid='.$this->mid)->findall();
        foreach ($bind as $v){
        	$data['login_bind'][$v['type']] = $v['is_sync'];
        }
        $data['type'] = $strType;
        $this->assign( $data );
        
        // 公告
        if(cookie("announcement_closed_{$this->mid}") != 1)
        	$this->assign('announcement', model('Xdata')->lget('announcement'));
        
        $this->display();
    }
    
    //提到我的
    function atme(){
    	model('UserCount')->setZero($this->mid,'atme');
        $data['list']   =  D('Operate','weibo')->getAtme($this->mid); 
        $data['type']   = $strType;
        $this->assign( $data );
        $this->assign('userCount',x('Notify')->getCount($this->mid));
        $this->display('index');
    } 
    
    //我的收藏
    function collection(){
        $data['list']   =  D('Operate','weibo')->getCollection($this->mid);
        $data['type']   = $strType;
        $this->assign( $data );
        $this->display('index');    	
    }
    
    //评论列表
    function comments(){
    	$data['type']		= ( $_GET['type'] == 'send'  ) ? 'send'  : 'receive';
    	$data['from_app']	= ( $_GET['from_app']  == 'other' ) ? 'other' : 'weibo';
    	
    	// 优先展示微博，优先展示有未读from_app
    	if ( model('UserCount')->getUnreadCount($this->mid, 'comment') <= 0 && model('GlobalComment')->getUnreadCount($this->mid) > 0 )
    		$data['from_app'] = 'other';
    	
    	if ($data['from_app'] == 'weibo') {
	    	$data['type'] == 'receive' && model('UserCount')->setZero($this->mid,'comment');
	    	
	    	//$data['person'] = (in_array( $_GET['person'] , array('all','follow','other')) )?$_GET['person']:'all';
	    	$data['person'] = 'all';
	    	$data['list']   = D('Comment','weibo')->getCommentList( $data['type'] , $data['person'] , $this->mid );
    	}else {
    		$dao = model('GlobalComment');
    		$data['type'] == 'receive' && $dao->setUnreadCountToZero($this->mid);
    		
    		$data['person']	= 'all';
    		$data['list']	= $dao->getCommentList( $data['type'], $this->mid );
    		foreach($data['list']['data'] as $k => $v) 
    			$data['list']['data'][$k]['data'] = unserialize($v['data']);
    	}
    	
	    $this->assign( $data );
	    $this->assign('userCount',x('Notify')->getCount($this->mid));
    	$this->display();
    }
    
    private function __getSearchKey() {
    	$key = '';
	    // 为使搜索条件在分页时也有效，将搜索条件记录到SESSION中
		if ( isset($_REQUEST['k']) ) {
			$key = t( urldecode($_REQUEST['k']) );
			// 关键字不能超过30个字符
			if ( mb_strlen($key, 'UTF8') > 30 )
				$key = mb_substr($key, 0, 30, 'UTF8');
			$_SESSION['home_user_search_key'] = serialize( $key );
			
		}else if ( isset($_GET[C('VAR_PAGE')]) ) {
			$key = unserialize( $_SESSION['home_user_search_key'] );
			
		}else {
			unset($_SESSION['home_user_search_key']);
		}
		
		return $key;
    }
    
    //查找话题
    function search(){
    	$data['search_key']  = $this->__getSearchKey();
    	$data['followState'] = D('Follow','weibo')->getTopicState( $this->mid , $data['search_key'] );
        $data['type'] =  t($_REQUEST['type']);    	
    	$data['list'] = D('Operate','weibo')->doSearch( $data['search_key'] ,$data['type'] );
		$data['hotTopic'] =  D('Topic','weibo')->getHot();
        $data['followTopic'] =  D('Follow','weibo')->getTopicList($this->mid);    	
    	$this->assign($data);
    	$this->display();
    }

    //查找用户
    function searchuser(){
     	$data['search_key']  = $this->__getSearchKey();
    	$data['list'] = D('Follow','weibo')->doSearchUser( $data['search_key'] );
    	$data['hotTopic'] =  D('Topic','weibo')->getHot();
        $data['followTopic'] =  D('Follow','weibo')->getTopicList($this->mid); 
    	$this->assign($data);
    	$this->display();
    }
    
    //查找用户
    function searchtag(){
     	$data['search_key']  = $this->__getSearchKey();
    	$data['list'] = D('UserTag')->doSearchTag( $data['search_key'] );
    	$data['hotTopic'] =  D('Topic','weibo')->getHot();
        $data['followTopic'] =  D('Follow','weibo')->getTopicList($this->mid); 
    	$this->assign($data);
    	$this->display();
    }

    //广场 
    function topic(){
    	$data['type'] = $_GET['type']?$_GET['type']:'index';
    	switch ($data['type']){
    		case 'transpond':
    			$order = 'transpond DESC';
    			break;
    		case 'comment':
    			$order = 'comment DESC';
    			break;
    		default:
    			$order = 'weibo_id DESC';
    			break;
    	}
    	$data['list'] = D('Operate','weibo')->doSearchTopic($map,$order,$this->mid);
    	$res = model('Xdata')->lget('weibo');
    	$data['aboutkey'] = $res['todaytopic'];
    	$data['keylist'] = M('weibo')->where("transpond_id=0 AND content LIKE '%".$data['aboutkey']."%'")->limit(1)->order('ctime DESC')->findall();
		foreach($data['keylist'] as $key=>$value){
			$data['keylist'][$key]['userinfo'] = M("user")->where('uid='.$value['uid'])->find();
			$data['keylist'][$key]['following'] = M('weibo_follow')->where('uid='.$value['uid'].' AND type=0')->count();
			$data['keylist'][$key]['follower']  = M('weibo_follow')->where('fid='.$value['uid'].' AND type=0')->count();
			$data['keylist'][$key]['followState']  = getFollowState( $this->mid , $value['uid'] );
		}
		
		
    	$data['hotTopic'] =  D('Topic','weibo')->getHot();
        $data['followTopic'] =  D('Follow','weibo')->getTopicList($this->mid); 
    	$this->assign($data);
    	$this->display();
    }
    
    function findfriend(){
    	$data['type'] = ($_GET['type'])?$_GET['type']:'newjoin';
    	switch ($data['type']){
    		case 'followers':
    			$data['list'] = M()->query("SELECT fid as uid,count(uid) as count FROM ts_weibo_follow WHERE fid NOT IN (SELECT fid FROM ts_weibo_follow WHERE uid={$this->mid} AND type=0) AND fid<>{$this->mid} AND type=0 GROUP BY fid ORDER by count(uid) DESC LIMIT 10");
    			foreach ($data['list'] as $key=>$value){
    				$data['list'][$key] = M('user')->where('uid='.$value['uid'])->field('uid,location,ctime')->find();
    				$data['list'][$key]['follower']  = $value['count'];
    				$data['list'][$key]['followstate']  = getFollowState($this->mid, $value['uid']);
    			}
    			break;
    			
    		case 'hot':
				$data['list'] = M()->query("SELECT uid,count(weibo_id) as weibo_num FROM ts_weibo where uid NOT IN (SELECT fid FROM ts_weibo_follow WHERE uid={$this->mid} AND type=0) AND uid!={$this->mid} GROUP BY uid ORDER by count(weibo_id) DESC LIMIT 10");
    			foreach ($data['list'] as $key=>$value){
    				$data['list'][$key] = M('user')->where('uid='.$value['uid'])->field('uid,location,ctime')->find();
    				$data['list'][$key]['follower']  = M('weibo_follow')->where('fid='.$value['uid'])->count();
    				$data['list'][$key]['weibo_num']  = $value['weibo_num'];
    				$data['list'][$key]['followstate']  = getFollowState($this->mid, $value['uid']);
    			}
    			break;
    			
    		case 'understanding':
				$data['list'] = model('Friend')->getRelatedUser($this->mid, $max = 10);
    			foreach ($data['list'] as $key=>$value){
    				$data['list'][$key] = M('user')->where('uid='.$value)->field('uid,location,ctime')->find();
    				$data['list'][$key]['follower']  = M('weibo_follow')->where('fid='.$value)->count();
    				$data['list'][$key]['followstate']  = getFollowState($this->mid, $value);
    			}
    			break;
    			
    		case 'newjoin':
    			$data['list'] = M("user")->where("is_active=1 AND is_init=1 AND uid NOT IN (SELECT fid FROM ts_weibo_follow WHERE uid={$this->mid}) AND uid!={$this->mid}")->order('uid DESC')->field('uid,location,ctime')->limit(10)->findall();
    			foreach ($data['list'] as $key=>$value){
    				$data['list'][$key]['follower']  = M('weibo_follow')->where('fid='.$value['uid'])->count();
    				$data['list'][$key]['followstate']  = getFollowState($this->mid, $value['uid']);
    			}
    			break;
    	}
    	$data['topfollow'] = M()->query("SELECT fid as uid,count(uid) as count FROM ts_weibo_follow  GROUP BY fid ORDER by count(uid) DESC LIMIT 10");
    	$this->assign( $data );
    	$this->display();
    }
    
    //表情
    function emotions(){
    	$list = M('expression')->field('title,emotion,filename,type')->findall();
    	exit( json_encode($list) );
    }
    
    //获取统计数据
    function countNew(){
    	exit( json_encode( x('Notify')->getCount($this->mid) ) );
    }
    
    // 删除动态
    public function doDeleteMini() {
    	echo X('Feed')->deleteOneFeed($this->mid, intval($_POST['id'])) ? '1' : '0';
    }
    
    public function closeAnnouncement() {
    	cookie("announcement_closed_{$this->mid}",'1');
    }
}
?>