<?php
class ContentAction extends AdministratorAction {
	
	private function __isValidRequest($field, $array = 'post') {
		$field = is_array($field) ? $field : explode(',', $field);
		$array = $array == 'post' ? $_POST : $_GET;
		foreach ($field as $v){
			$v = trim($v);
			if ( !isset($array[$v]) || $array[$v] == '' ) return false;
		}
		return true;
	}

	/** 内容管理 - 广告管理 **/

	public function ad() {
		$data = M('ad')->order('`display_order` ASC,`ad_id` ASC')->findAll();
		$this->assign('ad', $data);
		$this->assign('place_array', array('中部','头部','左侧','右侧','底部'));
		$this->display();
	}

	public function addAd() {
		$this->assign('type', 'add');
		$this->display('editAd');
	}

	public function editAd() {
		$map['ad_id'] = intval($_GET['id']);
		$ad = M('ad')->where($map)->find();
		if(empty($ad)) 
			$this->error('参数错误');
		$this->assign($ad);

		$this->assign('type', 'edit');
		$this->display('editAd');
	}

	public function doEditAd() {
		if( ($_POST['ad_id'] = intval($_POST['ad_id'])) <= 0 )
			unset($_POST['ad_id']);

		// 格式化数据
		$_POST['title']			= t($_POST['title']);
		$_POST['content']		= $_POST['content'];
		$_POST['place']			= intval($_POST['place']);
		$_POST['is_active']		= intval($_POST['is_active'])   == 0 ? '0' : '1';
		$_POST['is_closable']	= 0; // intval($_POST['is_closable']) == 0 ? '0' : '1';
		$_POST['mtime']			= time();
		if ( !isset($_POST['ad_id']) ) 
			$_POST['ctime']		= time();

		// 数据检查
		if(empty($_POST['title']))
			$this->error('标题不能为空');
		if($_POST['place'] < 0 || $_POST['place'] > 4)
			$this->error('参数错误');

		// 提交数据
		$res = isset($_POST['ad_id']) ? M('ad')->save($_POST) : M('ad')->add($_POST);

		if($res) {
			if( !isset($_POST['ad_id']) ) {
				// 为排序方便, 新建完毕后, 将display_order设置为ad_id
				M('ad')->where("`ad_id`=$res")->setField('display_order', $res);
				$this->assign('jumpUrl', U('admin/Content/addAd'));
			}else {
				$this->assign('jumpUrl', U('admin/Content/ad'));
			}
			$this->success('保存成功');
		}else {
			$this->error('保存失败');
		}
	}

	public function doDeleteAd() {
		if( empty($_POST['ids']) ) {
			echo 0;
			exit ;
		}
		$map['ad_id'] = array('in', t($_POST['ids']));
		echo M('ad')->where($map)->delete() ? '1' : '0';
	}

	public function doAdOrder() {
		$_POST['ad_id']  = intval($_POST['ad_id']);
		$_POST['baseid'] = intval($_POST['baseid']);
		if ( $_POST['ad_id'] <= 0 || $_POST['baseid'] <= 0 ) {
			echo 0;
			exit;
		}

		// 获取详情
		$map['ad_id'] = array('in', array($_POST['ad_id'], $_POST['baseid']));
		$res = M('ad')->where($map)->field('ad_id,display_order')->findAll();
		if ( count($res) < 2 ) {
			echo 0;
			exit;
		}

		//转为结果集为array('id'=>'order')的格式
    	foreach($res as $v) {
    		$order[$v['ad_id']] = intval($v['display_order']);
    	}
    	unset($res);

    	//交换order值
    	$res = 		   M('ad')->where('`ad_id`=' . $_POST['ad_id'])->setField(  'display_order', $order[$_POST['baseid']] );
    	$res = $res && M('ad')->where('`ad_id`=' . $_POST['baseid'])->setField( 'display_order', $order[$_POST['ad_id']]  );

    	if($res) echo 1;
    	else	 echo 0;
	}
	
	/** 内容管理 - 表情管理 **/
	
	public function expression() {
		$expression = model('Expression')->getExpressionByMap();
		$this->assign('data', $expression);
		$this->display();
	}
	
	public function addExpression() {
		$this->assign('type', 'add');
		$this->display('editExpression');
	}
	
	public function doAddExpression() {
		if (!$this->__isValidRequest('title,type,emotion,filename')) {
			$this->error('数据不完整');
		}
		$res = model('Expression')->add($_POST);
		if ($res) $this->success('保存成功');
		else	  $this->error('保存失败');
	}
	
	public function editExpression() {
		$map['expression_id']  = intval($_GET['expression_id']);
		$expression = model('Expression')->getExpressionByMap($map);
		$this->assign('expression', $expression[0]);
		$this->assign('type', 'edit');
		$this->display();
	}
	
	public function doEditExpression() {
		if (!$this->__isValidRequest('expression_id,title,type,emotion,filename')) {
			$this->error('数据不完整');
		}
		$res = model('Expression')->save($_POST);
		if ($res) {
			$this->assign('jumpUrl', U('admin/Content/expression'));
			$this->success('保存成功');
		}else{
			$this->error('保存失败');
		}
	}
	
	public function doDeleteExpression() {
		$map['expression_id'] = array('in', t($_POST['expression_id']));
		$res	   = model('Expression')->where($map)->delete();
    	if($res) {echo 1; }
    	else 	 {echo 0; }
	}
	
	/** 内容 - 模板管理 */
	
	//模板管理
	public function template() {
		$list   = model('Template')->getTemplate();
		$this->assign($list);
		$action = isset($_GET['action']) ? $_GET['action'] : 'list';
		$this->assign('action', $action);
		$this->display();
	}
	
	public function addTemplate() {
		$this->assign('type', 'add');
		$this->display('editTemplate');
	}
	
	public function doAddTemplate() {
		if (! $this->__isValidRequest('name')) $this->error('资料不完整');
		
		$_POST['is_cache'] = intval($_POST['is_cache']);
		$res = model('Template')->addTemplate($_POST);
		if ($res) {
			$this->success('保存成功');
		}else {
			$this->error('保存失败');
		}
	}
	
	public function editTemplate() {
		$tid = intval($_GET['tid']);
		$dao = model('Template');
		$template = M('template')->where("`tpl_id` = $tid")->find();
		if (!$template) $this->error('无此模板');
		
		$this->assign('template', $template);
		$this->assign('type', 'edit');
		$this->display();
	}
	
	public function doEditTemplate() {
		if (! $this->__isValidRequest('tpl_id, name')) $this->error('资料不完整');

		$_POST['tpl_id']   = intval($_POST['tpl_id']);
		$_POST['is_cache'] = intval($_POST['is_cache']);
		$res = model('Template')->save($_POST);
		if ($res) {
			$this->assign('jumpUrl', U('admin/Content/template'));
			$this->success('保存成功');
		}else {
			$this->error('保存失败');
		}
	}
	
	public function doDeleteTemplate() {
    	echo  model('Template')->deleteTemplate( t($_POST['ids']) ) ? '1' : '0';
	}
	
	/** 内容 - 附件管理 */
	
	public function attach($map) {
		$dao = model('Attach');
		$attaches   = $dao->getAttachByMap($map);
		$extensions = $dao->enumerateExtension();
		$this->assign($attaches);
		$this->assign('extensions', $extensions);
		
		$this->assign($_POST);
		$this->assign('isSearch', empty($map)?'0':'1');
		$this->display('attach');
	}
	
	public function doSearchAttach() {
		$map = $this->_getSearchMap(array('in' => array('id', 'userId', 'extension')));
		$this->attach($map);
	}
	
	public function doDeleteAttach() {
		if( empty($_POST['ids']) ) {
			echo 0;
			exit ;
		}
		echo model('Attach')->deleteAttach( t($_POST['ids']), intval($_POST['withfile']) ) ? '1' : '0';
	}
	
	/** 内容 - 留言管理 */
	
	public function comment() {
    	$_GET['from_app']	= ( $_GET['from_app']  == 'other' ) ? 'other' : 'weibo';
    	$limit = 20;
    	
    	if ($_GET['from_app'] == 'weibo') {
	    	$data = M('weibo_comment')->order('comment_id DESC')->findPage($limit);
    	}else {
    		$data = M('comment')->order('id DESC')->findPage($limit);
    	}
	    $this->assign( $this->__formatComment($_GET['from_app'], $data) );
	    $this->assign('from_app', $_GET['from_app']);
    	$this->display();
	}
	
	private function __formatComment($from_app, $data) {
		foreach($data['data'] as $k => $v) {
			if ($from_app == 'weibo') {
				unset($data['data'][$k]);
				$data['data'][$k]	=  array(
					'comment_id'	=> $v['comment_id'],
					'type'			=> 'weibo',
					'content'		=> $v['content'],
					'uid'			=> $v['uid'],
					'to_uid'		=> $v['reply_uid'],
					'url'			=> U('home/Space/detail',array('id'=>$v['weibo_id'])),
					'ctime'			=> $v['ctime'],
				);
			}else if ($from_app == 'other') {
				unset($data['data'][$k]);
				$v['data'] = unserialize($v['data']);
				$data['data'][$k]	=  array(
					'comment_id'	=> $v['id'],
					'type'			=> $v['type'],
					'content'		=> $v['comment'],
					'uid'			=> $v['uid'],
					'to_uid'		=> $v['to_uid'],
					'url'			=> $v['data']['url'],
					'ctime'			=> $v['cTime'],
				);
			}
		}
		return $data;
	}
	
	public function doDeleteComment() {
		$_POST['from_app']	= $_POST['from_app'] == 'other' ? 'other' : 'weibo';
		$_POST['ids']		= explode(',', t($_POST['ids']));
		
        if ( empty($_POST['ids']) )
       		return ;
       	
       	if ($_POST['from_app'] == 'weibo') {
       		$dao = D('Comment', 'weibo');
       		$comments = array();
       		
       		$map['comment_id'] = array('in', $_POST['ids']);
       		$res = $dao->where($map)->field('comment_id,uid')->findAll();
       		
       		// 转换成 array('uid'=>$comment) 的形式
       		foreach ($res as $v)
       			$comments[$v['uid']][] = $v['comment_id'];
       			
       		// 循环批量删除
       		foreach ($comments as $uid => $ids)
       			$dao->deleteMuleComments($ids, $uid);
       			
			unset($res);       			
       		echo 1;
       			
       	}else if ($_POST['from_app'] == 'other') {
       		echo model('GlobalComment')->deleteComment($_POST['ids']) ? '1' : '0';
       		
       	}else {
       		echo 0;
       	}
	}
	
	/** 内容 - 短消息管理 */
	
	public function message($map) {
		$msg = model('Message')->getMessageByMap($map);
		$this->assign($msg);
		
		$this->assign($_POST);
		$this->assign('isSearch', empty($map)?'0':'1');
		$this->display('message');
	}
	
	public function doSearchMessage() {
		// 标题模糊查询
    	if ( isset($_POST['title']) && $_POST['title'] != '' ) {
    		$_POST['title']	= '%' . $_POST['title'] . '%';
    	}
    	$map = $this->_getSearchMap( array('in'=>array('message_id','from_uid','to_uid'), 'like'=>array('title')) );
    	$this->message($map);
	}
	
	public function doDeleteMessage() {
		if( empty($_POST['ids']) ) {
			echo 0;
			exit ;
		}
		echo model('Message')->deleteMessage( t($_POST['ids']) ) ? '1' : '0';
	}
	
	/** 内容 - 通知管理 */
	
	public function notify($map) {
		$dao    = service('Notify');
		$notify = $dao->get($map,20,false);
		$types  = $dao->enumerateType();
		$this->assign($notify);
		$this->assign('types', $types);
		
		$this->assign($_POST);
		$this->assign('isSearch', empty($map)?'0':'1');
		$this->display('notify');
	}
	
	public function doSearchNotify() {
		$map = $this->_getSearchMap(array('in' => array('notify_id', 'from', 'receive', 'type')));
		$this->notify($map);
	}
	
	public function doDeleteNotify() {
		if( empty($_POST['ids']) ) {
			echo 0;
			exit ;
		}
		echo service('Notify')->deleteNotify( t($_POST['ids']) ) ? '1' : '0';
	}
	
	/** 内容 - 通知管理 */
	
	public function feed($map) {
		$dao   = service('Feed');
		$feed  = $dao->getFeedByMap($map);
		$types = $dao->enumerateType();
		$this->assign($feed);
		$this->assign('types', $types);
		
		$this->assign($_POST);
		$this->assign('isSearch', empty($map)?'0':'1');
		$this->display('feed');
	}
	
	public function doSearchFeed() {
		$map = $this->_getSearchMap(array('in'=>array('feed_id','uid','type')));
		$this->feed($map);
	}
	
	public function doDeleteFeed() {
		if( empty($_POST['ids']) ) {
			echo 0;
			exit ;
		}
		echo service('Feed')->deleteFeed( t($_POST['ids']) ) ? '1' : '0';
	}
}