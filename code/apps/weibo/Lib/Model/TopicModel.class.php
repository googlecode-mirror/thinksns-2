<?php 
class TopicModel extends Model{
	var $tableName = 'weibo_topic';
	
	//添加话题
	function addTopic( $content ){
		preg_match_all('/#(.*)#/isU',$content,$arr);
		$arr = array_unique($arr[1]);
		foreach($arr as $v){
			$this->addKey($v);
		}
	}

	//添加话题
	private function addKey($key){
		$map['name'] = $key;
		if( $this->where($map)->count() ){
			$this->setInc('count',$map);
		}else{
			$map['count'] = 1;
			return $this->add($map);
		}
	}
	
	//获取话题ID
	function getTopicId( $name ){
		$map['name'] = $name;
		$info = $this->where($map)->find();
		if( $info['topic_id'] ){
			return $info['topic_id'];
		}else{
			if($map['name']){
				$map['count'] = 0;
				return $this->add($map);
			}
		}
	}
	
	function getHot(){
		return $this->where('count>0')->order('count DESC')->limit(10)->findall();
	}
	
	//最新话题
	function getNew($num){
		return $this->order('cTime DESC')->limit($num)->findall();
	}
}
?>