<?php
namespace Admin\Model;
use Think\Model;
/**
 * 用户操作
 */
class ClientModel extends Model {
    //验证
    protected $_validate = array(
        array('phone','require','客户手机号码不能为空！'), //默认情况下用正则进行验证
        array('wechat','require','客户微信号不能为空！'), 
        array('name','require','客户姓名不能为空！'), 
        array('company','require','客户公司不能为空！')
    );

    /**
     * 获取列表
     * @return array 列表
     */
    public function loadList($where = array(), $limit = 0){

        $data   = $this->where($where)
                    ->limit($limit)
                    ->order('id desc')
                    ->group('phone,salesman')
                    ->select();
        return $data;
    }

    /**
     * 获取数量
     * @return int 数量
     */
    public function countList($where = array()){

        return $this->distinct(true) ->where($where)->count();
    }

    /**
     * 获取信息
     * @param int $userId ID
     * @return array 信息
     */
    public function getInfo($userId = 1)
    {
        $map = array();
        $map['user_id'] = $userId;
        return $this->where($map)->find();
    }

    /**
     * 获取信息
     * @param array $where 条件
     * @return array 信息
     */
    public function getWhereInfo($where)
    {
        return $this->table("__ADMIN_USER__ as A")
                    ->join('__ADMIN_GROUP__ as B ON A.group_id = B.group_id')
                    ->field('A.*,B.status as group_status,B.name as group_name,B.base_purview,B.menu_purview')
                    ->where($where)
                    ->find();
    }

    /**
     * 更新信息
     * @param string $type 更新类型
     * @return bool 更新状态
     */
    public function saveData($type = 'add'){
        $data = $this->create();
        if(!$data){
            return false;
        }
        if($type == 'add'){
            return $this->add();
        }
        if($type == 'edit'){
            if(empty($data['user_id'])){
                return false;
            }
            if (!empty($this->password)){ //密码非空，处理密码加密
                $this->password = md5($this->password);
            }
            $status = $this->save();
            if($status === false){
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * 更新权限
     * @param string $type 更新类型
     * @return bool 更新状态
     */
    public function savePurviewData(){
        $this->_auto = array();
        $data = $this->create();
        $this->menu_purview = serialize($this->menu_purview);
        $this->base_purview = serialize($this->base_purview);
        $status = $this->save();
        if($status === false){
            return false;
        }
        return true;
    }

    /**
     * 删除信息
     * @param int $userId ID
     * @return bool 删除状态
     */
    public function delData($data)
    {
        $map = array();
        $map['phone'] = $data[0];
        $map['salesman']=$data[1];
        return $this->where($map)->delete();
    }

    /**
     * 登录用户
     * @param int $userId ID
     * @return bool 登录状态
     */
    public function setLogin($userId)
    {
        // 更新登录信息
        $data = array(
            'user_id' => $userId,
            'last_login_time' => NOW_TIME,
            'last_login_ip' => get_client_ip(),
        );
        $this->save($data);
        //写入系统记录
        api('Admin','AdminLog','addLog','登录系统');
        //设置cookie
        $auth = array(
            'user_id' => $userId,
        );
        session('admin_user', $auth);
        session('admin_user_sign', data_auth_sign($auth));
        return true;
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout(){
        session('admin_user', null);
        session('admin_user_sign', null);
    }

}