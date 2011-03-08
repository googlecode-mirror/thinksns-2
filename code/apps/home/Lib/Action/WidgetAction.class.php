<?php
class WidgetAction extends Action {
	private $__type_website = '0';
	
	public function renderWidget() {
		$_REQUEST['name']  = t($_REQUEST['name']);
		$_REQUEST['param'] = unserialize(urldecode($_REQUEST['param']));
		echo W($_REQUEST['name'], $_REQUEST['param']);
	}
	
	// 关注“可能感兴趣的人”
	public function doFollowRelatedUser() {
		$_POST['uid']	= intval($_POST['uid']);
		
		if (0 == $_POST['uid']) {
			echo 0;
		}else {
			D('Follow', 'weibo')->dofollow($this->mid, $_POST['uid']);
			$related_user = unserialize($_SESSION['related_user']);
			if ( empty($related_user) ) {
				echo '';
				return;
			}else {
				$shifted_user = array_shift($related_user);
				$_SESSION['related_user'] = serialize($related_user);
				
				$html = '';
	            $html .= '<li id="related_user_' . $shifted_user . '">';
	            $html .= '<span class="userPic"><a title="" href="' . U("home/Space/index",array("uid"=>$shifted_user)) . '">';
	            $html .= '<img src="' . getUserFace($shifted_user, 's') . '" card="1">';
	            $html .= '</a></span>';
	            $html .= '<div class="name"><a href="' . U("home/Space/index",array("uid"=>$shifted_user)) . '">' . getUserName($shifted_user). '</a></div>';
	            $html .= '<div><a href="javascript:void(0);" class="cGray2" onclick="subscribe(' . $shifted_user . ');">加关注</a></div>';
	            $html .= '</li>';
	            echo $html;
			}
		}
	}

	// 发微博
	public function weibo() {
		// 解析参数
		$_GET['param']	= unserialize(urldecode($_GET['param']));
		$active_field	= $_GET['param']['active_field'] == 'title' ? 'title' : 'body';
		$this->assign('has_status', $_GET['param']['has_status']);
		$this->assign('is_success_status', $_GET['param']['is_success_status']);
		$this->assign('status_title', t($_GET['param']['status_title']));

		// 解析模板(统一使用模板的body字段)
		$_GET['data']	= unserialize(urldecode($_GET['data']));
		$content		= model('Template')->parseTemplate(t($_GET['tpl_name']), array($active_field=>$_GET['data']));
		$this->assign('content', $content[$active_field]);

		$this->assign('type',$_GET['data']['type']);
		$this->assign('type_data',$_GET['data']['type_data']);
		$this->assign('button_title', t(urldecode($_GET['button_title'])));
		$this->display();
	}
	
	public function doPostWeibo() {
		$data['content']   =  $_POST['content'];
		$_POST['type']     && $type=intval($_POST['type']);
		$_POST['typedata'] && $type_data=$_POST['typedata'];
        $id = D('Weibo','weibo')->publish( $this->mid , $data, $this->__type_website,$type,$type_data);
        if ($id) {
        	X('Credit')->setUserCredit($this->mid,'share_to_weibo');
        	echo '1';
        }else {
        	echo '0';
        }
	}

	/**
	 * 勋章Widget - 关闭提示消息
	 */
	public function medalCloseAlert() {
		$_POST['medal_id']	= intval($_POST['medal_id']);
		$medal_path_name	= M('medal')->where('`medal_id`='.$_POST['medal_id'])->getField('path_name');
		medal($medal_path_name)->closeMedalAlert($this->mid, $_POST['medal_id']);
	}
}