<?php 
class UserApi extends Api{
	
	
	//按用户UID或昵称返回用户资料，同时也将返回用户的最新发布的微博
	function show(){
		$data[0] = getUserInfo($this->user_id, $this->user_name,$this->mid,true);
		return $data;
	}
}
?>