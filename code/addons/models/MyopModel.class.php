<?php
/**
 * 漫游应用模型
 * 
 * @author daniel <desheng.young@gmail.com>
 */
class MyopModel extends Model {

	/**
	 * 获取指定用户已安装的Myop应用列表(分页)
	 * 
	 * @param int    $uid   用户ID
	 * @param int    $limit 每页显示条数 默认20
	 * @param string $order 排序,默认 展示顺序升序,应用ID升序
	 * @return array
	 */
	public function getInstalledByUser($uid, $limit = '20', $order = 'displayorder ASC, appid ASC') {
		$map['uid']	= $uid;
		return M('myop_userapp')->where($map)->order($order)->findPage($limit);
	}

    /**
     * 获取指定用户已安装的Myop应用列表(不分页)
     * 
     * @param int    $uid   用户ID
     * @param string $order 排序,默认 展示顺序升序,应用ID升序
     * @return array
     */
	public function getAllInstalledByUser($uid, $order = 'displayorder ASC, appid ASC') {
		$map['uid'] = $uid;
		return M('myop_userapp')->where($map)->order($order)->findAll();
	}

	/**
	 * 获取默认应用列表(分页)
	 * 
	 * @param int    $limit 每页显示条数 默认20
     * @param string $order 排序,默认 展示顺序升序,应用ID升序
     * @return array
	 */
	public function getDefaultApp($limit = '20', $order = 'displayorder ASC, appid ASC') {
		$map['flag']	= 1;
		return M('myop_myapp')->where($map)->order($order)->findPage($limit);
	}

    /**
     * 获取默认应用列表(不分页)
     * 
     * @param string $order 排序,默认 展示顺序升序,应用ID升序
     * @return array
     */
	public function getAllDefaultApp($order = 'displayorder ASC, appid ASC') {
		$map['flag']	= 1;
		return M('myop_myapp')->where($map)->order($order)->findAll();
	}
}