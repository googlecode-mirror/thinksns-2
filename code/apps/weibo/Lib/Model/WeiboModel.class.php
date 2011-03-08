<?php 
class WeiboModel extends Model{
    var $tableName = 'weibo';
    
	/**
	 * 
	 +----------------------------------------------------------
	 * Description 微博发布
	 +----------------------------------------------------------
	 * @author Nonant nonant@thinksns.com
	 +----------------------------------------------------------
	 * @param $uid 发布者用户ID
	 * @param $data 微博主要数据
	 * @param $from 从哪发布的
	 * @param $type 微博类型
	 * @param $type_data   微博类型传来的数据
	 +----------------------------------------------------------
	 * @return return_type
	 +----------------------------------------------------------
	 * Create at  2010-9-17 下午05:02:06
	 +----------------------------------------------------------
	 */
     function publish($uid,$data,$from=0,$type=0,$type_data,$sync, $from_data){
     	$data['content'] =t( $data['content'] );
     	if($id = $this->doSaveWeibo($uid, $data, $from , $type ,$type_data, $sync, $from_data) ){
     		$this->notifyToAtme($uid,$id, $data['content'] );
     		return $id;
     	}else{
     		return false;
     	}
    }
    
    //发布微博
    function doSaveWeibo($uid,$data,$from=0,$type=0,$type_data,$sync, $from_data){
        if(!$data['content']){
        	return false;
        }
     	

        $save['uid']			= $uid;
        $save['transpond_id']	= intval( $data['transpond_id'] );
        $save['from']			= intval( $from );  //0网站 1手机网页版 2 android 3 iphone
        $save['content']		= preg_replace_callback('/((?:https?|mailto).*?)(\s|　|&nbsp;|<br|\'|\"|$)/',getContentUrl, $data['content']);
        $save['from_data']		= $from_data;
        
        if(mb_strlen($save['content'],'UTF8')>140){
        	return false;
        }
        
        
        if($type){
        	$save = array_merge( $save , $this->checkWeiboType($type, $type_data) );
        }else{
        	if($data['type']) $save['type'] = intval( $data['type'] );
        }
        
        $save['ctime']      = time();
		
        if( $id = $this->add( $save ) ){
        	if( $save['transpond_id']){
        		$this->setInc('transpond','weibo_id='.$save['transpond_id']);
        	}
        	
	        if(in_array('sina',$sync)){
	        	$opt = M('login')->where("uid=".$uid." AND type='sina'")->field('oauth_token,oauth_token_secret,is_sync')->find();
	        	//if($opt['is_sync']){
		        	include_once( SITE_PATH.'/addons/plugins/login/sina.class.php' );
					$sina = new sina();
					if($type==1){
						$sina->upload($save['content'],SITE_URL.'/data/uploads/'.$type_data,$opt);
					}elseif($type==0){
						$sina->update($save['content'],$opt);
					}
	        	//}
	        }
	        //话题处理
        	D('Topic','weibo')->addTopic( $save['content'] );
        	return $id;
        }else{
        	return false;
        }
    }
    
    //转发操作
    function transpond($uid,$data,$api=false){
    	
		$post['content']       = t( $data['content'] );
	    $post['transpond_id']  = intval( $data['transpond_id'] );
		
	    $transponInfo = $this->field('weibo_id,uid,content,type')->where('weibo_id='.$post['transpond_id'])->find();
	    $post['type'] = $transponInfo['type'];
        if( $data['reply_weibo_id'] ){ //对相应微博ID作出评论
        	foreach ( $data['reply_weibo_id'] as $value ){
				if($value == 0) continue;
				$weiboinfo = $this->field('uid')->where('weibo_id='.$value)->find();
	        	$comment['uid']       = $uid;
	        	$comment['reply_uid'] = $weiboinfo['uid'];
	        	$comment['weibo_id']  = $value;
	        	$comment['content']   = $post['content'];
	        	$comment['ctime']     = time();
	        	D('Comment','weibo')->addcomment( $comment );
	        	Model('UserCount')->addCount($weiboinfo['uid'],'comment');
        	}
        }
        
	    $id = $this ->doSaveWeibo( $uid , $post , intval($data['from']) );  
	    if($id){
	    	$this->notifyToAtme($uid,$id, $post['content'], $transponInfo['uid']);
	    	return $id;
	    }else{
	    	return false;
	    }
    }
    
    //给提到我的发通知 @诺南 
    function notifyToAtme($uid,$id,$content,$transpond_uid,$addCount=true){
    	$notify['weibo_id'] = $id;
    	$notify['content'] = $content;
    	$arrUids= array();
    	if( $transpond_uid ){
    		array_push($arrUids, $transpond_uid);
    	}
    	$arrUids = array_merge($arrUids, getUids($content) );
    	if( $arrUids ){
    		$arrUids = array_unique( $arrUids ); //去重
    		if($addCount){
    			foreach ($arrUids as $v){
    				if(M('user_blacklist')->where("uid=$v AND fid=$uid")->count()==0){
    					$atUids[] = $v;
    				}
    			}
    			Model('UserCount')->addCount($atUids,'atme');
    		}
    		D('Atme','weibo')->addAtme($arrUids,$id);
    	}
    }    
    
   	private function checkWeiboType($type,$type_data){
   	    if( $type_data && $type !=0 ){
   	     	$pluginInfo = M('weibo_plugin')->where('plugin_id='.$type)->field('plugin_path')->find();
   	     	$do_type = 'publish';
   	     	include SITE_PATH.'/apps/weibo/Lib/Plugin/'.$pluginInfo['plugin_path'].'/control.php';
	        $save['type'] = $type;
	        $save['type_data']    = serialize( $typedata );
        }else{
        	$save['type']       = 0;
        }
        return $save;
   	}
   	
   	protected  function getOne($id,$value,$api=false){
   		if($api){
   			return $this->getOneApi($id,$value);
   		}else{
   			return $this->getOneLocation($id, $value);
   		}
   	}

    //返回一个站内使用的解析微博
    function getOneLocation($id,$value){
    	if(!$value) $value = $this->where('weibo_id='.$id)->find();
    	if(!$value) return false;
       	$result['id']          = $value['weibo_id'];
        $result['uid']         = $value['uid'];
        $result['content']     = $value['content'];
        $result['ctime']       = $value['ctime'];
        $result['comment']     = $value['comment'];
        $result['from']        = $value['from'];
        $result['transpond_id']     = $value['transpond_id'];
        $result['transpond']     = $value['transpond'];
        $result['is_favorited'] = intval( $value['is_favorited'] );
        if( $result['transpond_id'] ){
        	$result['expend']      = $this->getOne( $result['transpond_id'] ); 
        }else{
        	$result['expend']      = $this->__parseTemplate( $value ); 
        }
        $result['from_data'] = unserialize($value['from_data']);
          
        return $result; 	
    	
    }
    
    //返回一个Api使用的微博信息
    function getOneApi($id,$info){
			if(!$info) $info = $this->where('weibo_id='.$id)->find();
			if(!$info) return false;
       		$info['uname'] = getUserName($info['uid']);
    		$info['face'] =  getUserFace($info['uid']);
    		if( $info['type']==1 && $info['transpond_id']==0 ){
    			$info['type_data'] = unserialize($info['type_data']);
    			$info['type_data']['picurl'] = SITE_URL.'/data/uploads/'.$info['type_data']['picurl'];
    			$info['type_data']['thumbmiddleurl'] = SITE_URL.'/data/uploads/'.$info['type_data']['thumbmiddleurl'];
    			$info['type_data']['thumburl'] = SITE_URL.'/data/uploads/'.$info['type_data']['thumburl'];
    		}
    		$info['transpond_data'] = ($info['transpond_id']!=0)?$this->getOneApi($info['transpond_id']):'';
    		$info['timestamp'] = $info['ctime'];
    		$info['ctime'] = date('Y-m-d H:i',$info['ctime']);
        	$info['from_data'] = unserialize($info['from_data']);
    		return $info;
    }
    
    private function __parseTemplate( $value ){
    	static $rand;
    	if( $rand ){
    		$rand++;
    	}else{
    		$rand = time().$value['transpond_id'];
    	}
    	$typedata = unserialize( $value['type_data'] );
    	$type     = $value['type'];
    	if($type==3){
    		$typedata['flashimg'] = ( $typedata['flashimg'] )?$typedata['flashimg']:__THEME__.'/images/nocontent.png';
    	}
    	$template = $this->templateForType($type);
    	if(!$template) return '';
    	//$template = preg_replace('/{(.*?)}/eis',"\$this->parseLiteral('\\1')",$template);
    	$template = preg_replace('/{data\.(.*?)}/eis',"\$typedata['\\1']",$template);
    	$template = preg_replace('/{rand}/eis',$rand,$template);
    	$template = preg_replace('/{(.*?)}/eis',"\$value['\\1']",$template);
    	return $template;
    }
    
    //解析类型模板
    private	function templateForType($type){
    	/**
		include(SITE_PATH.'/addons/libs/Io/Dir.class.php');
		$list = new Dir(  );
		foreach( $list->getList(APP_PATH.'/Lib/Plugin/') as $key=>$value ){
			if( is_dir( APP_PATH.'/Lib/Plugin/'.$value ) && is_file( APP_PATH.'/Lib/Plugin/'.$value.'/template.php' )){
				$file[$value] = require APP_PATH.'/Lib/Plugin/'.$value.'/template.php';
			}
		}
		return $file;
		**/
    	$info = M('weibo_plugin')->where('plugin_id='.$type)->field('plugin_path')->find();
		if(!$info) return false;
    	$r =require SITE_PATH.'/apps/weibo/Lib/Plugin/'.$info['plugin_path'].'/template.php';
    	return $r;
    }
    
}
?>