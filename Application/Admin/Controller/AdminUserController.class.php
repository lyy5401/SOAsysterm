<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;
/**
 * 后台用户
 */
class AdminUserController extends AdminController {

    /**
     * 当前模块参数
     */
    protected function _infoModule(){
    	if($_SESSION['rank']<=2){
        return array(
            'info'  => array(
                'name' => '用户管理',
                'description' => '管理网站后台管理员',
                ),
            'menu' => array(
                    array(
                        'name' => '用户列表',
                        'url' => U('index'),
                        'icon' => 'list',
                    ),
                    array(
                        'name' => '添加用户',
                        'url' => U('add'),
                        'icon' => 'plus',
                    ),  
                ),
            );
     }else{
     	return array(
            'info'  => array(
                'name' => '用户管理',
                'description' => '管理网站后台管理员',
                )
                );
     }
    }
	/**
     * 列表
     */
    public function index(){
    	if($_SESSION['rank']=="" || $_SESSION['group_id']==""){
    		$this->error('您尚未登录',U('Login/index'));
    	}
    	
        //筛选条件
        $where = array();
        $keyword = I('request.keyword','');

          //如果是分公司  权限等级为二的管理员管理用户,只显示它底下的员工及其自身信息
        if($_SESSION['rank']==2){
        	$where['_string']='(B.group_id ='.$_SESSION['group_id'].') OR (B.parent_id ='.$_SESSION['group_id'].')';
        } 
        //如果是员工本身  管理用户 只显示他自身
        if($_SESSION['rank']==3){
            $where['_string']='(A.user_id ='.$_SESSION['user_id'].')';
        }

        if(!empty($keyword)){           
            $where['A.username']=array('like',$keyword);
        }
        //筛选分则
        $groupId = I('request.group_id','');
        if(!empty($groupId)){
            $where['A.group_id'] = $groupId;
        }
    
        //URL参数
        $pageMaps = array();
        $pageMaps['keyword'] = $keyword;
        $pageMaps['group_id'] = $groupId;
        //查询数据
        $count = D('AdminUser')->countList($where);
        $limit = $this->getPageLimit($count,20);
        $list = D('AdminUser')->loadList($where,$limit);
        //位置导航
        $breadCrumb = array('用户列表'=>U());
        //模板传值
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);
        $this->assign('groupList',D('AdminGroup')->loadList());
        $this->assign('page',$this->getPageShow($pageMaps));
        $this->assign('keyword',$keyword);
        $this->assign('groupId',$groupId);
        $this->adminDisplay();
    }

    /**
     * 增加
     */
    public function add(){
        if(!IS_POST){
        	if($_SESSION['rank']=="" || $_SESSION['group_id']==""){
    		$this->error('您尚未登录',U('Login/index'));
    	    }
    	    
            $breadCrumb = array('用户列表'=>U('index'),'添加'=>U());
            $this->assign('breadCrumb',$breadCrumb);
            $this->assign('name','添加');
            
            if($_SESSION['rank'] !=1){
            	$classify=M("AdminGroup")->where("parent_id =".$_SESSION['group_id'])->select();
            }else{
                $classify=M("AdminGroup")->order('rank asc')->select();	
            }  
            $this->assign('groupList',$classify);
           
            $this->adminDisplay('info');
        }else{
            if($_POST['oppassword']==""){
                $_POST['oppassword']='123456';
            }
            if(D('AdminUser')->saveData('add')){
                $this->success('用户添加成功！');
            }else{
                $msg = D('AdminUser')->getError();
                if(empty($msg)){
                    $this->error('用户添加失败');
                }else{
                    $this->error($msg);
                }
                
            }
        }
    }

    /**
     * 修改
     */
    public function edit(){
        if(!IS_POST){
        if($_SESSION['rank']=="" || $_SESSION['group_id']==""){
    		$this->error('您尚未登录',U('Login/index'));
    	}
        	
            $userId = I('get.user_id','','intval');
            if(empty($userId)){
                $this->error('参数不能为空！');
            }
            //获取记录
            $model = D('AdminUser');
            $info = $model->getInfo($userId);
            if(!$info){
                $this->error($model->getError());
            }
            $breadCrumb = array('用户列表'=>U('index'),'修改'=>U('',array('user_id'=>$userId)));
            $this->assign('breadCrumb',$breadCrumb);
            $this->assign('name','修改');
            //用户组 如果是分公司 权限等级为二 不能选择分组  改变分组只能总公司有权限
            if($_SESSION['rank'] !=1){
            	 $classify=M('admin_group')->where('group_id='.$info['group_id'])->select();
            }else{
            	 $classify=M('admin_group')->order('rank asc')->select();
            }
          
            $this->assign('groupList',$classify);
            
            $this->assign('info',$info);
            $this->adminDisplay('info');
        }else{
            if(D('AdminUser')->saveData('edit')){
                $this->success('用户修改成功！');
            }else{
                $msg = D('AdminUser')->getError();
                if(empty($msg)){
                    $this->error('用户修改失败');
                }else{
                    $this->error($msg);
                }
            }
        }
    }

    /**
     * 删除
     */
    public function del(){
        $userId = I('post.data');
        if(empty($userId)){
            $this->error('参数不能为空！');
        }
        if($userId == 1){
            $this->error('保留用户无法删除！');
        }
        //获取用户数量
        if(D('AdminUser')->delData($userId)){
            $this->success('用户删除成功！');
        }else{
            $msg = D('AdminUser')->getError();
            if(empty($msg)){
                $this->error('用户删除失败！');
            }else{
                $this->error($msg);
            }
        }
    }


}

