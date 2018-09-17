<?php
namespace Admin\Controller;
use Admin\Controller\AdminController;
/**
 * 后台用户
 */
class ClientController extends AdminController {

    /**
     * 当前模块参数
     */
    protected function _infoModule(){
        return array(
            'info'  => array(
                'name' => '客户管理',
                'description' => '管理网站客户信息',
                ),
            'menu' => array(
                    array(
                        'name' => '客户列表',
                        'url' => U('index'),
                        'icon' => 'list',
                    ),
                    array(
                        'name' => '导入数据',
                        'url' => U('exchange'),
                        'icon' => 'exchange',
                    ),
                    
                ),
            );
    }
    
    function exchange(){
      if($_SESSION['rank']=="" || $_SESSION['group_id']==""){
    		$this->error('您尚未登录',U('Login/index'));
    	}
    	$this->adminDisplay();
    }
    
   /**方法**/

public function exportExcel($expTitle,$expCellName,$expTableData){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $_SESSION['account'].date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");
       
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        
//      $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
//      $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle);  
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i][1]); 
        } 
          // Miscellaneous glyphs, UTF-8   
        for($i=0;$i<$dataNum;$i++){
          for($j=0;$j<$cellNum;$j++){
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2), $expTableData[$i][$expCellName[$j][0]]);
          }             
        }  
        ob_end_clean();
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  
        $objWriter->save('php://output'); 
        exit;   
    }
    
    
    function import_data(){
      if (!empty($_FILES)) {
            $upload = new \Think\Upload();// 实例化上传类
           
            $upload->maxSize   =     3145728 ;// 设置附件上传大小
            $upload->exts      =     array('xlsx','xls');// 设置附件上传类型
            $upload->rootPath  =     './'; // 设置附件上传根目录
            $upload->savePath  =     'Public/upload/'; // 设置附件上传（子）目录
            $info  =  $upload->upload();
            if(!$info) {
                $res['res']= $upload->getError();
                 $this->error('参数错误！');
                exit;
            }else{// 上传成功
                 
                foreach($info as $file){
                    vendor("PHPExcel.PHPExcel"); //导入的包
                    $file_name=$file['savepath'].$file['savename'];
 
                   
                     $objReader = \PHPExcel_IOFactory::createReader('Excel5');//设置已Excel5格式
                    $objPHPExcel = $objReader->load($file_name,$encode='utf-8');
                    $sheet = $objPHPExcel->getSheet(0);//读取第一个工作表
                    $highestRow = $sheet->getHighestRow(); // 取得总行数
                    $highestColumn = $sheet->getHighestColumn(); // 取得总列数
                for($i=2,$j=0;$i<=$highestRow;$i++)
                {                     	    
                	 //同个销售人员(仅限销售人员)每天至多只能导入50条数据
                      if($_SESSION['rank']==3){
                      	$start=date("Y-m-d 00:00:00");
                      	$end=date("Y-m-d 23:59:59");
                      	$map['create_time']  = array('between',array($start,$end));
                      	$map['salesman']=$_SESSION['user_id'];
                      	$times=M('client')->where($map)->count();
                      	if($times>=200){
                      	  break;
                      	}  
                      }
                	
                	 
                	  $xlsModel = M('client');
                	//数据
                      $data['phone'] = $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();
                      $data['wechat'] = $objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue()?$objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue():'';
                      $data['name'] = $objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue(); 
                      $data['company'] = $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue()?$objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue():'';
                      $data['status']=0;
                      $data['salesman']=$_SESSION['user_id'];
                      if($_SESSION['rank']<3){
                      	$data['sale_company']=$_SESSION['group_id'];
                      }else{
                      	$data['sale_company']=$_SESSION['parent_id'];
                      }

                      $data['create_time']=date("Y-m-d h:i:s");
                     //限制保存
                      //一个客户手机号只能被同家公司不同销售人员导入3次,只能被所有公司导入6次.
                       $allcount=M('client')->where('phone='.$data['phone'])->count();
                       //如果这个客户已经合作，则后续不能再导入             
                       $allow=M('client')->where('phone='.$data['phone'])->getField('status');
                       //查询客户电话跟销售人员的唯一性  已经导入的不能在导入 一个销售人员只能导入一次
                       $yy['phone']=$data['phone'];
                       $yy['salesman']=$data['salesman'];
                       $yy['sale_company']=$data['sale_company'];
                       $isdao=M('client')->where($yy)->count();
                if(preg_match("/^0{0,1}(13[0-9]|145|147|15[0-9]|16[0-9]|17[0-9]|18[0-9]|19[0-9]|19[0-8])[0-9]{8}$/i",$data['phone'], $matches)){
                    if($allow==0){
//                       if($allcount <6){
                       	if($isdao==0){                      		                    
                       	 $sale['phone']=$data['phone'];
                       	 $sale['sale_company']=$data['sale_company'];
//                         $fencount=M('client')->where($sale)->count();
//                         if($fencount<3){
                         	 $sucess=$xlsModel->add($data);	
                             if($sucess){
                      	     $j++;
                           }    
//                         }
                        }
//                       }
                     } 
                     }                                                                   
                } 
               $this->success('成功导入'.$j.'条数据');
            }
             
            }                    
        } 
           $this->error('请选择文件！');   
    }
    
    /**
     *
     * 导出Excel
     */
    function expUser(){//导出Excel
    	$where=$_SESSION['where'];          	
        $xlsName  = "客户名单";
        $xlsCell  = array(         
            array('phone','客户手机号'),
            array('wechat','客户微信号'),
            array('name','客户姓名'),
            array('company','客户公司'),
            array('status','客户合作状态'),
            array('salesman','销售人员'),
            array('create_time','创建时间'),
            array('update_time','更新时间'),
            array('sale_company','所属公司'),            
            array('remark','备注')
        );
        $xlsModel = M('client');      
        $xlsData  = $xlsModel->where($where)->order('create_time asc')->select();
        foreach($xlsData as &$value){
        	$value['salesman']=M('admin_user')->where('user_id ='.$value['salesman'])->getField('nicename');
        	$value['sale_company']=M('admin_group')->where('group_id ='.$value['sale_company'])->getField('name');
        	if($value['status']==1){
        		$value['status']="已合作";
        	}else{
        		$value['status']="未合作";
        	}
        }        
        $this->exportExcel($xlsName,$xlsCell,$xlsData);
         
    }
    
    
   /**
     *
     * 下载excel模板
     */
    function downexcel(){//导出Excel   	  	
        $xlsName  = "客户名单";
        $xlsCell  = array(         
            array('phone','*手机号(必填)'),
            array('wechat','微信号'),
            array('name','*姓名(必填)'),
            array('company','公司'),
        );
        $xlsModel = M('excel');      
        $xlsData  = $xlsModel->select();    
        $this->exportExcel($xlsName,$xlsCell,$xlsData);
         
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
        //URL参数
        $pageMaps = array();

       //全局搜索
        $overall=I('request.overall','');
        if($overall){
            $where['phone']=$overall;
            $pageMaps['overall'] = $overall;
            $this->assign('overall',$overall);
        }else{
            //模糊搜索
            $guanjiazi=I('request.keyword','');
            if(!empty($guanjiazi)){
                $where['_string'] = '(phone like "%'.$guanjiazi.'%") OR (wechat like "%'.$guanjiazi.'%") OR (name like "%'.$guanjiazi.'%") ';
            }

            $keyword = I('request.phone','');
            //电话筛选
            if(!empty($keyword)){
                $where['phone']=$keyword;
            }

            //公司组筛选
            $groupId = I('request.group_id','');
            if(!empty($groupId)){
                $where['sale_company'] = $groupId;
            }
            //员工组筛选
            $salesman=I('request.salesman','');
            if(!empty($salesman)){
                $where['salesman'] =$salesman;
            }

            //筛选创建时间的数据
            $start=I('request.start_time','');
            $end=I('request.end_time','');
            if($start){
                if(!$end){
                    $endtime=date("Y-m-d h:i:s");
                }else{
                    $endtime=strtotime($end)+86399;
                    $endtime=date("Y-m-d h:i:s",$endtime);
                }
                $where['create_time']  = array('between',array($start,$endtime));
            }

            //筛选单子状态  1已成交  0未成交
            $status=I('request.result','');
            if($status==1){
                $where['status']=1;
            }
            if($status==10){
                $where['status']=0;
            }
            //搜索赋值
            $this->assign('groupId',$groupId);
            $this->assign('phone',$keyword);
            $this->assign('rank',$_SESSION['rank']);
            $this->assign('salesman',$salesman);
            $this->assign('status',$status);
            $this->assign('start',$start);
            $this->assign('end',$end);
            $this->assign('keyword',$keyword);
            $this->assign('guanjianzi',$guanjiazi);
            //权限查看数据量
            if($_SESSION['rank']==2){
                //查找登陆的该用户的公司名字
                $where['sale_company']=$_SESSION['group_id'];
            }
            if($_SESSION['rank']==3){
                $where['salesman']=$_SESSION['user_id'];
            }

            $pageMaps['keyword'] = $keyword;
            $pageMaps['salesman'] = $salesman;
            $pageMaps['start_time'] = $start;
            $pageMaps['end_time'] = $end;
            $pageMaps['result'] = $status;
            $pageMaps['group_id'] = $groupId;
        }


        //将搜索条件用session保存
        session('where',$where);
        //查询数据
        $count = D('Client')->countList($where);
        $limit = $this->getPageLimit($count,6);      
        $list = D('Client')->loadList($where,$limit); 
         
        //将通过员工ID和员工公司ID查找处对应的名称
        foreach($list as &$value){
        	$value['sales']=M('admin_user')->where('user_id='.$value['salesman'])->getField('nicename');
        	$value['sale_company']=M('admin_group')->where('group_id='.$value['sale_company'])->getField('name');
        }
              
        //位置导航
        $breadCrumb = array('用户列表'=>U());
        
        //职员公司列表
        $grouplist=M('admin_group')->where('rank <3')->select();
        //公司员工列表
        if($_SESSION['rank']==2){
        	 $childID=M('admin_group')->where('parent_id ='.$_SESSION['group_id'])->getField('group_id');
             $workerlist=M('admin_user')->where('group_id ='.$childID)->select();
        }
         if($_SESSION['rank']==1){
           $workerlist=M('admin_user')->select();
        } 	
        
        //模板传值
        $this->assign('sum',$count);
        $this->assign('breadCrumb',$breadCrumb);
        $this->assign('list',$list);        
        $this->assign('groupList',$grouplist);
        $this->assign('page',$this->getPageShow($pageMaps));
        $this->assign('workerlist',$workerlist);
        $this->adminDisplay();


    }

    /**
     * 删除
     */
    public function del(){
        $data = I('post.data');
        $data=explode("+",$data);
        if(empty($data)){
            $this->error('参数不能为空！');
        }
        $where['phone']=$data[0];
        $where['salesman']=$data[1];
        if(M('Client')->where($where)->delete()){
            $this->success('数据删除成功！');
        }else{
            $msg = D('Client')->getError();
            if(empty($msg)){
                $this->error('数据删除失败！');
            }else{
                $this->error($msg);
            }
        }
    }
    
    
    /*
     * 成交
     */
    public function strike(){

       $where['phone'] = I('post.phone');
       $data['status']=1;
       $data['remark']=I('post.remark');
       $data['update_time']=date("Y-m-d h:i:s"); 
       $res=M('client')->where($where)->save($data);
       if($res){
       	 echo $res;
       }else{
       	 echo 0;
       }	  
    }
    
    /*
     * 取消成交
     */
    public function initial(){
    	 $where['phone'] = I('post.phone');
    	 $data['status']=0;
    	 $data['remark']="";
    	 $data['update_time']=date("Y-m-d h:i:s"); 
    	 $res=M('client')->where($where)->save($data);
    	 if($res){
       	 echo $res;
       }else{
       	 echo 0;
       }	
    }
    
    
  //批量操作
	public function batchAction(){
		$ids  = I('post.ids',''); //接收所选中的要操作id	
		$type = I('post.type');//接收要操作的类型   如删除。。。
		$remark = I('post.remark');//接收要操作的类型   如删除。。。
		//将电话号码单独给弄成一个数组
		$newids=$ids;
		 foreach($ids as $key=>$value){
		 	$data=explode("+",$value);
		 	$newids[$key]=$data[0];
		} 		
		//将电话号码和销售人员ID作为删除的唯一条件
		foreach($ids as $key=> &$value){	
		   $data=explode("+",$value);
		   $value="";	
		   $ids[$key]['phone']=$data[0];
		   $ids[$key]['salesman']=$data[1];  			  	   
		}					
		if(empty($ids)||empty($type)){
			$this -> error('参数不能为空');
		}
			
		//删除
		if($type == 1){
			$i=0;
			foreach($ids as $val){
			  $where['phone']=$val['phone'];
			  $where['salesman']=$val['salesman'];			 
			  $res = M("client") -> where($where) -> delete();
			  if($res){
			  	$i++;
			  }	
			}			
			if($i){
				$this->success('删除成功,共删除'.$i.'条数据');
			}else{
				$this->error('删除数据失败');
			}			
		}elseif($type==2){
			//批量更改状态为已合作
			$num=0;			
			
			
			foreach($newids as $value){
				$where['phone']=$val['phone'];			
			    $mydata['status']=1;
			    $mydata['update_time']=date("Y-m-d h:i:s");
			    $mydata['remark']=$remark; 
				$res = M("client") -> where("phone=".$value) ->save($mydata);
				if($res){
    	      	 $num+=$res;
    	      }
			}			
			if($num){
				$this->success('更新状态成功,共更新'.$num.'条数据');
			}else{
				$this->error('更新状态失败');
			}	
           
		}elseif($type==3){			
			//批量更改状态为禁止
			$mydata['status']=0;
			$mydata['update_time']=date("Y-m-d h:i:s"); 
			$mydata['remark']="";
			$j=0;
			foreach($newids as $value){
				$res = M("client") -> where("phone=".$value) ->save($mydata);
				if($res){
					$j+=$res;
				}
			}		
			if($j){
				$this->success('更新状态成功,共更新'.$j .'条数据');
			}else{
				$this->error('更新状态失败');
			}
		}
	}
 
 /*
  * 获取操作密码
  */
    public function getoppword(){
    	$userid=$_SESSION['user_id'];
    	$oppword=M('AdminUser')->where('user_id='.$userid)->getField('oppassword');
        echo $oppword;
    }
    
}

