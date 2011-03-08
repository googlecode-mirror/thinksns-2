<?php 

class CommentModel extends Model{
    var $tableName = 'weibo_comment';
    //发布评论
    function addcomment($data){
         if( $id= $this->add( $data ) ){
              D('Weibo','weibo')->setInc('comment', 'weibo_id='.$data['weibo_id'] );
             return $id;
         }else{
             return false;
         }
         
    }
    
    //发布评论同时发布一条微博
    function doaddcomment($uid,$post,$api=false){
        $data['uid']     = $uid;
        $data['reply_comment_id']   = intval($post['reply_comment_id']);
        $data['weibo_id']   = intval($post['weibo_id']);
        $data['content'] = $post['content'];
        $data['ctime']   = time();
        $miniInfo = D('Weibo')->where('weibo_id='.$data['weibo_id'])->find();
        if( $data['reply_comment_id'] ){
        	$replyInfo = $this->where('comment_id='.$data['reply_comment_id'])->find();
        	$data['reply_uid'] = $replyInfo['uid'];
        }else{
        	$data['reply_uid'] = $miniInfo['uid'];
        	$notify['reply_type'] = 'weibo';
        }
        if ( $comment_id = $this->addcomment( $data ) ){

			//微博回复积分操作
			if($data['uid'] != $data['reply_uid']){
				X('Credit')->setUserCredit($data['uid'],'reply_weibo')
						   ->setUserCredit($data['reply_uid'],'replied_weibo');
			}
        	
            $data['comment'] = $miniInfo['comment'] + 1;
            $return['data'] = $data;
            $return['html'] = '<div class="position_list" id="comment_list_c_'.$comment_id.'"> <a href="'.U('home/space/index',array('uid'=>$this->mid)).'" class="pic">
            		<img class="pic30" src="'.getUserFace($uid,'s').'" /></a>
                      <p class="list_c"><a href="#">'.getUserName($uid).'</a> ' . getUserGroupIcon($uid) . ' : '.formatComment( $data['content'] ,true ).' (刚刚)</p>
                      <div class="alR clear"><a href="javascript:void(0)" onclick="ui.confirm(this,\'确认要删除此评论?\')" callback="delComment('.$comment_id.')">删除</a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="reply(\''.getUserName($uid).'\','.$data['weibo_id'].')">回复</a></div>
                    </div>';
            if( $post['transpond'] != 0 ){
            	if($miniInfo['transpond_id']!=0){
            		$transpondData['content']     	   = $data['content']." //@".getUserName($miniInfo['uid']).":".$miniInfo['content'];
            		$transpondData['transpond_id']     = $miniInfo['transpond_id'];
            		$transpondInfo = M('weibo')->where('weibo_id='.$miniInfo['transpond_id'])->find();
            		$transpondData['transpond_uid']     = $transpondInfo['uid'];
            	}else{
            		$transpondData['content']          = $data['content'];
            		$transpondData['transpond_id']     = $miniInfo['weibo_id'];
            		$transpondData['transpond_uid']     = $miniInfo['uid'];
            	}
            	$id = D('Weibo','weibo')->doSaveWeibo($uid,$transpondData,$post['from']);
			    if($id ){  //当转发的微博uid 与 回复人的uid不一致时发布@到我
			    	if( $transpondData['transpond_uid'] != $data['reply_uid'] ){
			    		D('Weibo','weibo')->notifyToAtme($id, $transpondData['content'], $transpondData['transpond_uid']);
			    	}else{
			    		D('Weibo','weibo')->notifyToAtme($id, $transpondData['content'], $transpondData['transpond_uid'],false);
			    	}
			    	
			    }
            }
            
            //添加统计
            Model('UserCount')->addCount($data['reply_uid'],'comment');
            
             
            if($api){
            	return true;
            }else{
            	return json_encode($return);
            } 
        }else{
        	return '0';
        }
    }
    
    //获取评论
    function getComment( $id, $limit = 10, $order = 'comment_id DESC' ){
    	return $this->where("weibo_id={$id}")->order($order)->findpage($limit);
    }
    
    //发出的评论
    function getCommentList($type='receive',$person='all',$uid){ 	

    	if( $type=='send' ){
	    	if($person=='follow'){
	    		$map = "reply_uid IN (SELECT fid FROM {$this->tablePrefix}weibo_follow where uid={$uid})";
	    	}else if ($person=='other'){
	    		$map = "reply_uid NOT IN (SELECT fid FROM {$this->tablePrefix}weibo_follow where uid={$uid})";
	    	}else{
	    		$map = '1=1';
	    	}    		
    		
	    	$list = $this->where($map." AND uid=".$uid." AND reply_uid<>$uid")->order('comment_id DESC')->findpage(10);
    	}else{
    		if($person=='follow'){
	    		$map = "uid IN (SELECT fid FROM {$this->tablePrefix}weibo_follow where uid={$uid})";
	    	}else if ($person=='other'){
	    		$map = "uid NOT IN (SELECT fid FROM {$this->tablePrefix}weibo_follow where uid={$uid})";
	    	}else{
	    		$map = '1=1';
	    	}    		
    		
    		$list = $this->where($map." AND reply_uid=".$uid)->order('comment_id DESC')->findpage(10);
    	}
    	
        foreach ($list['data'] as $key=>$value){
    		$list['data'][$key]['mini'] = M('weibo')->where("weibo_id=".$value['weibo_id'])->find();
    		if( !$value['reply_comment_id'] ){
    			$list['data'][$key]['reply_uid']  = $list['data'][$key]['mini']['uid'];
    			$list['data'][$key]['ismini'] = true;
    		}else{
    			$list['data'][$key]['comment'] = $this->where('comment_id='.$value['reply_comment_id'])->find();
    		}
    	}    	
    	return $list;
    }
    

    
    //删除评论
    function deleteComments($id,$uid){
    	$pMiniBlog = D('Weibo');
       	$info = $this->where('comment_id='.$id)->find();
       	$webInfo = $pMiniBlog->where('weibo_id='.$info['weibo_id'])->field('uid,comment')->find();
    	if( $info['uid']==$uid || $webInfo['uid']==$uid ){
    		if( $this->where('comment_id='.$id)->delete() ){
    			$pMiniBlog->setDec('comment', 'weibo_id='.$info['weibo_id'] );
    		}
    		$r['boolen'] = 1;
    		$r['message'] = '删除成功';
    		$r['count']   = intval( $webInfo['comment'] -1 );
    	}else{
    		$r['boolen'] = 0;
    		$r['message'] = '删除失败';
    	}	
    	return $r;
    }
    
    //批量删除评论
    function deleteMuleComments($id,$uid){
    	$pMiniBlog = D('Weibo');
    	$id = is_array($id) ? $id : explode(',', $id);
    	foreach ($id as $k=>$v){
    		$info = $this->where('comment_id='.$v)->find();
    		$webInfo = $pMiniBlog->where('weibo_id='.$info['weibo_id'])->field('uid')->find();
	    	if( $info['uid']==$uid || $webInfo['uid']==$uid ){
	    		if( $this->where('comment_id='.$v)->delete() ){
	    			$pMiniBlog->setDec('comment', 'weibo_id='.$info['weibo_id'] );
	    		}
	    	}
    	}
    	return true;
    }
}
?>