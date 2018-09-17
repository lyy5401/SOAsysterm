<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;
/**
 * 后台首页
 */
class IndexController extends AdminController {

    /**
     * 当前模块参数
     */
	protected function _infoModule(){
		return array(
            'menu' => array(
            		array(
	                    'name' => '管理首页',
	                    'url' => U('Index/index'),
	                    'icon' => 'dashboard',
                    )
                ),
            'info' => array(
                    'name' => '管理首页',
                    'description' => '站点运行信息',
            		'icon' => 'home',
                )
            );
	}
	/**
     * 首页
     */
    public function index(){
    	//设置目录导航
    	$breadCrumb = array('首页'=>U('Index/index'));
        $this->assign('breadCrumb',$breadCrumb);
        
           //总公司
           if($_SESSION['rank']==1){
           	//数据总数
           	 $allsum=M('Client')->count('distinct(phone)');
           	//合作总数
           	 $allhz=M('Client')->where('status=1')->count('distinct(phone)'); 
           	 //未合作总数
           	 $allwhz=M('Client')->where('status=0')->count('distinct(phone)');            	 
           }
           //分公司
           if($_SESSION['rank']==2){
           	//数据总数
              $where['sale_company']=$_SESSION['group_id'];
              $allsum=M('client')->where($where)->count('distinct(phone)');
              //合作总数
              $where['status']=1;
           	  $allhz=M('Client')->where($where)->count('distinct(phone)'); 
           	   //未合作总数
           	   $where['status']=0;
           	  $allwhz=M('Client')->where($where)->count('distinct(phone)');              	  
            }
           //个人
           if($_SESSION['rank']==3){
              $where['salesman']=$_SESSION['user_id'];
              $allsum=M('client')->where($where)->count('distinct(phone)');
              //合作总数
              $where['status']=1;
           	  $allhz=M('Client')->where($where)->count('distinct(phone)'); 
               //未合作总数
           	   $where['status']=0;
           	  $allwhz=M('Client')->where($where)->count('distinct(phone)');     
           }  
        
        
        //未合作总数 
        
        //赋值输出
        $this->assign('allsum',$allsum);
        $this->assign('allhz',$allhz);
        $this->assign('allwhz',$allwhz);
        $this->adminDisplay();
    }
}

