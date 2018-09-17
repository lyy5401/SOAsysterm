<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;
/**
 * 后台用户组
 */

class AdminUserGroupController extends AdminController {
    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '用户组管理',
                'description' => '管理网站后台用户组',
                ),
            'menu' => array(
                    array(
                        'name' => '用户组列表',
                        'url' => U('index'),
                        'icon' => 'list',
                    ),
                    array(
                        'name' => '添加用户组',
                        'url' => U('add'),
                        'icon' => 'plus',
                    ),
                )
            );
    }
	/**
     * 列表
     */
    public function index(){
    	if($_SESSION['rank']=="" || $_SESSION['group_id']==""){
    		$this->error('您尚未登录',U('Login/index'));
    	}
    		
        $breadCrumb = array('用户组列表'=>U());
        $this->assign('breadCrumb',$breadCrumb);
        if($_SESSION['rank']==1){
        	 $data=D('AdminGroup')->loadList();
        }else{
            // $data=D('AdminGroup')->where("parent_id=".$_SESSION['group_id'])->select();	
        }      
         foreach($data as &$value){        	
        	if($value['parent_id']==0){
        	 	$value['parent_name']="顶级分类";
        	}else{
        	   $value['parent_name']=M('AdminGroup')->where('group_id ='.$value['parent_id'])->getField('name');	
        	}        
        }
     //  dump($data);exit;
        $this->assign('list',$data);
        $this->adminDisplay();
    }

    /**
     * 增加
     */
    public function add(){
        if(!IS_POST){
            $breadCrumb = array('用户组列表'=>U('index'),'添加'=>U());
            $this->assign('breadCrumb',$breadCrumb);
            $this->assign('name','添加');
            //分类
            $classify=M("AdminGroup")->where("rank <=2")->select();
            $this->assign("classify",$classify);	
            $this->adminDisplay('info');
        }else{
        	//分组
        	if(empty($_POST['parent_id'])){
        		$this->error('请选择父级分类');
        	}
        	$parent_id=$_POST['parent_id'];
        	$rank=M("AdminGroup")->where("group_id =".$parent_id)->getField('rank');
        	$_POST['rank']=$parent_id?$rank+1:1;
            //除了admin的账号外其他的小组初始权限只有管理首页
            $_POST['menu_purview']=array("首页_管理首页");
            $_POST['group_id']=D('AdminGroup')->saveData('add');
            if(D('AdminGroup')->savePurviewData()){
                $this->success('用户组添加成功！');
            }else{
                $this->error('用户组添加失败');
            }
        }
    }

    /**
     * 修改
     */
    public function edit(){
        if(!IS_POST){
            $groupId = I('get.group_id','','intval');
            if(empty($groupId)){
                $this->error('参数不能为空！');
            }
            //获取记录
            $model = D('AdminGroup');
            $info = $model->getInfo($groupId);
            if(!$info){
                $this->error($model->getError());
            }
            $breadCrumb = array('用户组列表'=>U('index'),'修改'=>U('',array('group_id'=>$groupId)));
            $this->assign('breadCrumb',$breadCrumb);
            $this->assign('name','修改');
            $this->assign('info',$info);
            $this->adminDisplay('info');
            
            //分类
            $classify=M("AdminGroup")->where("rank <=2")->select();
            $this->assign("classify",$classify);	
            $this->adminDisplay('info');
        }else{
        	if(empty($_POST['parent_id'])){
        		$this->error('请选择父级分类');
        	}
        	$parent_id=$_POST['parent_id'];
        	$rank=M("AdminGroup")->where("group_id =".$parent_id)->getField('rank');
        	$_POST['rank']=$parent_id?$rank+1:1;
            if(D('AdminGroup')->saveData('edit')){
                $this->success('用户组修改成功！');
            }else{
                $this->error('用户组修改失败');
            }
        }
    }

    /**
     * 权限
     */
    public function purview(){
        if(!IS_POST){
            $groupId = I('get.group_id','','intval');
            if(empty($groupId)){
                $this->error('参数不能为空！');
            }
            //获取记录
            $model = D('AdminGroup');
            $info = $model->getInfo($groupId);
            if(!$info){
                $this->error($model->getError());
            }
            $AdminPurvewArray = unserialize($info['base_purview']);
            $AdminMenuArray = unserialize($info['menu_purview']);
            $breadCrumb = array('用户组列表'=>U('index'),'权限设置('.$info['name'].')'=>U('',array('group_id'=>$groupId)));
            $this->assign('breadCrumb',$breadCrumb);
            $this->assign('AdminPurvew', D('Menu')->getPurview());
            $this->assign('AdminMenu', D('Menu')->getMenu());
            $this->assign('AdminPurvewArray', $AdminPurvewArray);
            $this->assign('AdminMenuArray', $AdminMenuArray);
            $this->assign('info',$info);
            $this->adminDisplay('purview');
        }else{
            if(D('AdminGroup')->savePurviewData()){
            	$this->success('用户组修改成功',U('Login/index'));
            }else{
                $this->error('用户组修改失败');
            }
        }
    }

    /**
     * 删除
     */
    public function del(){
        $groupId = I('post.data');
        if(empty($groupId)){
            $this->error('参数不能为空！');
        }
        //获取用户数量
        $map = array();
        $map['A.group_id'] = $groupId;
        $countUser = D('AdminUser')->countList($map);
        if($countUser>0){
            $this->error('请先删除改组下的用户！');
        }
        if(D('AdminGroup')->delData($groupId)){
            $this->success('用户组删除成功！');
        }else{
            $this->error('用户组删除失败！');
        }
    }


}

