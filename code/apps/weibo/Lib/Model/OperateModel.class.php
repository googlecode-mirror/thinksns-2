<?php 
require_once(SITE_PATH.'/apps/weibo/Lib/Model/WeiboModel.class.php');
class OperateModel extends WeiboModel{
    
    var $tableName = 'weibo';
    
   function getSavePath(){
        $savePath = SITE_PATH.'/data/uploads/miniblog';
        if( !file_exists( $savePath ) ) mk_dir( $savePath  );
        return $savePath;
    }    
    
    //删除一条微博
    function deleteMini($id,$uid){
    	if( $info = $this->where("weibo_id=$id AND uid=$uid")->find() ){
    		//关联操作
    		if( $info['transpond_id'] ){
    			$this->setDec('transpond','weibo_id='.$info['transpond_id']);
    		}
    		$this->where('weibo_id='.$info['weibo_id'])->delete();
    		//同时删除@用户的微博数据
    		D('Atme','weibo')->where('weibo_id='.$info['weibo_id'])->delete();
    		
    		//同时删除收藏
    		D('Favorite','weibo')->where('weibo_id='.$info['weibo_id'])->delete();
    		
    		//同时删除评论
    		D('Comment','weibo')->where('weibo_id='.$info['weibo_id'])->delete();
    		return true;
    	}else{
    		return false;
    	}
    }
    
	//搜索话题
    function doSearch($key,$type){
    	global $ts;
    	$key = t($key);
    	if(!$key){
    		$list['count'] = 0;
    		return $list;
    	}
    	switch ($type){
    		case '':
    			$list = $this->where("content LIKE '%{$key}%'")->order('weibo_id DESC')->findpage(20);
    			break;
    			
    		case 'location':
    			
    			$user = M('user')->where('uid='.$ts['user']['uid'])->field('province')->find();
    			$list = $this->where("uid IN (SELECT uid FROM ts_user WHERE province=".$user['province'].") AND content LIKE '%{$key}%'")->order('weibo_id DESC')->findpage(20);
    			break;
    			
    		case 'follow':
    			$list = $this->where("uid IN (SELECT fid FROM ts_weibo_follow WHERE uid=".$ts['user']['uid'].") AND content LIKE '%{$key}%'")->order('weibo_id DESC')->findpage(20);
    			break;
    			
    		case 'original':
    			$list = $this->where("transpond_id=0 AND content LIKE '%{$key}%'")->order('weibo_id DESC')->findpage(20);
    			break;
    			
    		case 'image':
    			$list = $this->where("type=1 AND content LIKE '%{$key}%'")->order('weibo_id DESC')->findpage(20);
    			break;
    			
    		case 'music':
    			$list = $this->where("type=4 AND content LIKE '%{$key}%'")->order('weibo_id DESC')->findpage(20);
    			break;
    			
    		case 'video':
    			$list = $this->where("type=3 AND content LIKE '%{$key}%'")->order('weibo_id DESC')->findpage(20);
    			break;
    	}
    	
	    	
    	foreach($list['data'] as $key=>$value){
    		$list['data'][$key] = $this->getOne('',$value);
    	}
    	return $list;
    }
    
	//Topic搜索
	function doSearchTopic($map,$order,$uid){
		$list = $this->where($map)->order($order)->findpage(20);
		foreach($list['data'] as $key=>$value){
			$value['is_favorited'] = isfavorited($value['weibo_id'], $uid);
			$list['data'][$key] = $this->getOne('', $value);
		}
		return $list;
	}
	
	//获取未取出来的新微博条数
	function countNew($uid,$lastId){
    	$map="weibo_id>$lastId";
    	$map.=" AND ( uid IN (SELECT fid FROM {$this->tablePrefix}weibo_follow WHERE uid=$uid) OR uid=$uid )";
    	$list = $this->where($map)->order('weibo_id DESC')->findAll();
    	return $list;
	}
	
	function loadNew($uid,$lastId,$limit){
    	$map="weibo_id<=$lastId";
    	$map.=" AND ( uid IN (SELECT fid FROM {$this->tablePrefix}weibo_follow WHERE uid=$uid) OR uid=$uid )";
    	$list = $this->where($map)->order('weibo_id DESC')->limit($limit)->findAll();
        foreach( $list as $key=>$value){
 			$result[] = $this->getOne('',$value);
        }
        $return['data'] = $result;
        return $return; 
	}
  
    //获取首页微博列表
    function getHomeList( $uid , $type='index' ,$since ,$row=10){
    	$row = $row?$row:10;
		$followCount = M('weibo_follow')->where("uid=".$uid." AND type=0")->count();
    	if($type=='original'){  //原创
			$map = 'transpond_id=0';
    		if($since){
    			$map.=" AND weibo_id<$since";
    		}
    		if( $followCount ){
    			$map.=" AND ( uid IN (SELECT fid FROM {$this->tablePrefix}weibo_follow WHERE uid=$uid) OR uid=$uid )";
    		}
    		$list = $this->where($map)->order('weibo_id DESC')->limit($row)->findAll();
    	}else if($type=='index' || $type==''){   // 默认全显
    	    if($since){
    			$map="weibo_id < $since";
    		}else{
    			$map = '1=1';
    		}
    		if( $followCount ){
    			$map.=" AND ( uid IN (SELECT fid FROM {$this->tablePrefix}weibo_follow WHERE uid=$uid) OR uid=$uid)";
    		}
    		$list = $this->where($map)->order('weibo_id DESC')->limit($row)->findAll();
    	}else{
    		if($since){
    			$map="weibo_id < $since";
    		}else{
    			$map = '1=1';
    		}
    		if( $followCount ){
    			$map.=" AND ( uid IN (SELECT fid FROM {$this->tablePrefix}weibo_follow WHERE uid=$uid) OR uid=$uid)";
    		}
			$map.=" AND type=".$type;
    		$list = $this->where($map)->order('weibo_id DESC')->limit($row)->findAll();
    	}

        foreach( $list as $key=>$value){
        	$value['is_favorited'] = isfavorited($value['weibo_id'], $uid);
 			$result[] = $this->getOne('',$value);
        }
        $return['data'] = $result;
        return $return;
    }
    	
	function getSpaceList($uid,$type){
    	if($type=='original'){  //原创
    		$map = 'transpond_id=0 AND uid='.$uid;
    		$list = $this->where($map)->order('weibo_id DESC')->findpage(20);
    	}else if($type==''){   // 默认全显
    		$map = "uid=$uid";
    		$list = $this->where($map)->order('weibo_id DESC')->findpage(20);
    	}else{    //其它类型
    		$map = "uid=$uid AND type=".$type;
    		$list = $this->where($map)->order('weibo_id DESC')->findpage(20);
    	}

        foreach( $list['data'] as $key=>$value){
        	$value['is_favorited'] = isfavorited($value['weibo_id'], $uid);
 			$list['data'][$key] = $this->getOne('',$value);
        }
        return $list;
    }
    
    //首页滚动新微博
    function getIndex($num=10){
    	$list = $this->where("transpond_id=0 AND type=0")->limit($num)->order('ctime DESC')->findall();
    	return $list;
    }
    
    //提到我的
    function getAtme($uid,$api){
    	$list = $this->where("weibo_id IN (SELECT weibo_id FROM {$this->tablePrefix}weibo_atme WHERE uid=$uid) AND uid NOT IN (SELECT fid FROM {$this->tablePrefix}user_blacklist WHERE uid=$uid)")->order('ctime DESC')->findpage(10);
        foreach( $list['data'] as $key=>$value){
        	$value['is_favorited'] = isfavorited($value['weibo_id'], $uid);
 			$list['data'][$key] = $this->getOneLocation('',$value);
        }
        return $list;
    }
    
    //我收藏的
    function getCollection($uid,$api){
    	$list = $this->where("weibo_id IN (SELECT weibo_id FROM {$this->tablePrefix}weibo_favorite WHERE uid=$uid)")->order('weibo_id DESC')->findpage(10);
        foreach( $list['data'] as $key=>$value){
        	$value['is_favorited'] = isfavorited($value['weibo_id'], $uid);
 			$list['data'][$key] = $this->getOneLocation('',$value);
        }
        return $list;
    }
    
    
    
    //获取手机
    function getMobile($pre,$next,$count=20,$page=1){
    		if($pre){
    			$list = $this->query("SELECT a.* FROM {$this->tablePrefix}weibo a LEFT JOIN {$this->tablePrefix}weibo b ON a.transpond_id = b.weibo_id WHERE ( b.type=0 OR b.type=1 ) AND b.is_feed=0 AND a.weibo_id>$pre UNION SELECT * FROM {$this->tablePrefix}weibo WHERE transpond_id=0 AND is_feed=0 AND weibo_id>$pre AND ( type =0 OR type=1) ORDER BY weibo_id ASC  LIMIT $count ");
    			$list = array_reverse($list);
    			
    		}elseif($next){
    			
    			$list = $this->query("SELECT a.* FROM {$this->tablePrefix}weibo a LEFT JOIN {$this->tablePrefix}weibo b ON a.transpond_id = b.weibo_id WHERE ( b.type=0 OR b.type=1 ) AND b.is_feed=0 AND a.weibo_id<$next UNION SELECT * FROM {$this->tablePrefix}weibo WHERE transpond_id=0 AND is_feed=0 AND weibo_id<$next AND ( type =0 OR type=1) ORDER BY weibo_id DESC  LIMIT $count ");
    			
    		}else{
    			
    			$list = $this->query("SELECT a.* FROM {$this->tablePrefix}weibo a LEFT JOIN {$this->tablePrefix}weibo b ON a.transpond_id = b.weibo_id WHERE ( b.type=0 OR b.type=1 ) AND b.is_feed=0 UNION SELECT * FROM {$this->tablePrefix}weibo WHERE transpond_id=0 AND is_feed=0 AND ( type =0 OR type=1)  ORDER BY weibo_id DESC  LIMIT $count ");
    			
    		}
    	    
    	    foreach($list as $k=>$v){
				$result[$k] = $this->getOneApi('', $v);
	    	}	

    	return $result;
    }
    

	
}
?>