<?php
namespace DuxCms\Model;
use Think\Model;
/**
 * 字段操作
 */
class FieldModel extends Model {

    //验证
    protected $_validate = array(
        array('fieldset_id', 'require', '无法获取字段集ID', 1),
        array('name','require', '字段名称未填写', 1),
        array('field', 'validateField', '已存在相同的字段', 1, 'callback'),
        array('type','require', '字段类型未选择', 1),
        array('verify_type','require', '验证类型未选择', 1),
    );
    //完成
    protected $_auto = array (
        //全部
        array('fieldset_id','intval',3,'function'), //字段集ID
        array('name','htmlspecialchars',3,'function'), //字段名
        array('sequence','intval',3,'function'), //顺序
        array('verify_data','base64_encode',3,'function'), //验证规则
        array('verify_data_js','base64_encode',3,'function'), //JS验证规则
        //编辑
        array('field_id','intval',2,'function'), //字段ID
     );

    /**
     * 获取列表
     * @return array 列表
     */
    public function loadList($where = array()){
        return $this->where($where)->select();
    }

    /**
     * 获取信息
     * @param int $fieldId ID
     * @return array 信息
     */
    public function getInfo($fieldId)
    {
        $map = array();
        $map['field_id'] = $fieldId;
        return $this->getWhereInfo($map);
    }

    /**
     * 获取信息
     * @param array $where 条件
     * @return array 信息
     */
    public function getWhereInfo($where)
    {
        return $this->where($where)->find();
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
        //字段集信息
        $fieldsetInfo=D('DuxCms/Fieldset')->getInfo($data['fieldset_id']);
        //获取字段类型属性
        $typeField = $this->typeField();
        $propertyField = $this->propertyField();
        $typeData = $typeField[$data['type']];
        $property = $propertyField[$typeData['property']];
        if($property['decimal']){
            $property['decimal']=','.$property['decimal'];
        }else{
            $property['decimal']='';
        }
        if($type == 'add'){
            //插入字段
            $sqlText="
            ALTER TABLE __PREFIX__ext_{$fieldsetInfo['table']} ADD {$data['field']} {$property['name']}({$property['maxlen']}{$property['decimal']}) DEFAULT NULL
            ";
            $sql = $this->execute($sqlText);

            if($sql === false){
                return false;
            }
            //写入数据
            return $this->data($data)->add();
        }
        if($type == 'edit'){
            if(empty($data['field_id'])){
                return false;
            }
            //获取信息
            $info = $this->getInfo($data['field_id']);
             //修改字段
            if($info['type']<>$data['type']||$info['field']<>$data['field']){
                $sql="
                ALTER TABLE __PREFIX__ext_{$fieldsetInfo['table']} CHANGE {$info['field']} {$data['field']} {$property['name']}({$property['maxlen']}{$property['decimal']})
                ";
                $statusSql = $this->execute($sql);
                if($statusSql === false){
                    return false;
                }
            }
            //修改数据
            $status = $this->where('field_id='.$data['field_id'])->data($data)->save();
            if($status === false){
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * 删除信息
     * @param int $fieldId ID
     * @return bool 删除状态
     */
    public function delData($fieldId)
    {
        $map = array();
        $map['field_id'] = $fieldId;
        //获取信息
        $info = $this->getWhereInfo($map);
        $fieldsetInfo = D('DuxCms/Fieldset')->getInfo($info['fieldset_id']);
        if(empty($fieldsetInfo)){
            return false;
        }
        //删除字段
        $sql="
             ALTER TABLE __PREFIX__ext_{$fieldsetInfo['table']} DROP {$info['field']}
            ";
        $statusSql = $this->execute($sql);
        if($statusSql === false){
            return false;
        }
        //删除数据
        return $this->where($map)->delete();
    }

    /**
     * 验证字段是否重复
     * @param int $field 字段名
     * @return bool 状态
     */
    public function validateField($field)
    {
        if(empty($field)){
            return false;
        }
        $fieldsetId = I('post.fieldset_id',0);
        $fieldId = I('post.field_id',0);
        $map = array();
        $map['field'] = $field;
        $map['fieldset_id'] = $fieldsetId;
        if($fieldId){
            $map['field_id'] = array('NEQ',$fieldId);
        }
        $info = $this->getWhereInfo($map);
        if(empty($info)){
            return true;
        }else{
            return false;
        }

    }

    /**
     * 字段类型
     * @param int $fieldsetId ID
     * @return bool 删除状态
     */
    public function typeField()
    {
        $list=array(
            1=> array(
                'name'=>'文本框',
                'property'=>1,
                'html'=>'text',
                ),
            2=> array(
                'name'=>'多行文本',
                'property'=>3,
                'html'=>'textarea',
                ),
            3=> array(
                'name'=>'编辑器',
                'property'=>3,
                'html'=>'editor',
                ),
            4=> array(
                'name'=>'文件上传',
                'property'=>1,
                'html'=>'fileUpload',
                ),
            5=> array(
                'name'=>'单图片上传',
                'property'=>1,
                'html'=>'imgUpload',
                ),
            6=> array(
                'name'=>'多图上传',
                'property'=>3,
                'html'=>'imagesUpload',
                ),
            7=> array(
                'name'=>'下拉菜单',
                'property'=>3,
                'html'=>'select',
                ),
            8=> array(
                'name'=>'单选',
                'property'=>3,
                'html'=>'radio',
                ),
            9=> array(
                'name'=>'多选',
                'property'=>3,
                'html'=>'checkbox',
                ),
            10=> array(
                'name'=>'日期和时间',
                'property'=>2,
                'html'=>'textTime',
                ),
            11=> array(
                'name'=>'货币',
                'property'=>4,
                'html'=>'currency',
                ),
            
        );
        return $list;
    }

    /**
     * 字段SQL属性
     * @param int $type 字段类型
     * @return array 字段类型列表
     */
    public function propertyField()
    {
        $list=array(
            1=> array(
                'name'=>'varchar',
                'maxlen'=>250,
                'decimal'=>0,
                ),
            2=> array(
                'name'=>'int',
                'maxlen'=>10,
                'decimal'=>0,
                ),
            3=> array(
                'name'=>'text',
                'maxlen'=>0,
                'decimal'=>0,
                ),
            4=> array(
                'name'=>'decimal',
                'maxlen'=>10,
                'decimal'=>2,
                ),
        );
        return $list;
    }

    /**
     * 字段验证属性
     * @param int $type 字段类型
     * @return array 字段类型列表
     */
    public function typeVerify()
    {
        return array(
            1 => array(
                'name' => '正则验证(可用内置)',
                'data' => 'regex',
                ),
            2 => array(
                'name' => '验证长度(1,2)',
                'data' => 'length',
                ),
            );
    }

    /**
     * 字段验证规则
     * @param int $type 字段类型
     * @return array 字段类型列表
     */
    public function ruleVerify()
    {
        return array(
            0 => array(
                'name' => '必填',
                'data' => 'require',
                ),
            1 => array(
                'name' => '邮箱',
                'data' => 'email',
                ),
            2 => array(
                'name' => '网址',
                'data' => 'url',
                ),
            3 => array(
                'name' => '货币',
                'data' => 'currency',
                ),
            4 => array(
                'name' => '数字',
                'data' => 'number',
                ),
            );
    }

    /**
     * JS字段验证规则
     * @param int $type 字段类型
     * @return array 字段类型列表
     */
    public function ruleVerifyJs()
    {
        return array(
            0 => array(
                'name' => '必填',
                'data' => '*',
                ),
            1 => array(
                'name' => '数字',
                'data' => 'n',
                ),
            2 => array(
                'name' => '字符串',
                'data' => 's',
                ),
            3 => array(
                'name' => '邮政编码',
                'data' => 'p',
                ),
            4 => array(
                'name' => '手机号码',
                'data' => 'm',
                ),
            5 => array(
                'name' => '邮箱',
                'data' => 'e',
                ),
            6 => array(
                'name' => 'url',
                'data' => '网址',
                ),
            );
    }

    /**
     * 完整信息HTML
     * @param array $value 字段信息
     * @param string $data 字段值
     * @param string $model 其他模块
     * @return string HTML信息
     */
    public function htmlFieldFull($value,$data,$model = 'DuxCms/Field')
    {
        //获取字段属性
        $typeField=$this->typeField();
        //生成新配置
        $config=array();
        $config['type']=$typeField[$value['type']]['html'];
        $config['title']=$value['name'];
        $config['name']='Fieldset_'.$value['field'];
        if($data){
            $config['value']=$data;
        }else{
            $config['value']=$value['default'];
        }
        $config['verify_data_js']=base64_decode($value['verify_data_js']);
        $config['tip']=$value['tip'];
        $config['errormsg']=$value['errormsg'];
        $config['config']=$value['config'];
        $itemList=array();
        if(!empty($config['config'])){
            $list = explode(',', $config['config']);
            foreach ($list as $key => $value) {
                $itemList[]=$key+1;
            }
        }
        $config['item']=implode(',', $itemList);
        //返回字段HTML
        return D($model)->htmlField($config);
    }

    /**
     * 字段HTML
     * @param array $config 字段配置
     * @return string HTML信息
     */
    public function htmlField($config)
    {
        //设置统一JS验证
        if($config['verify_data_js']){
            $verifyHtml = 'datatype="'.$config['verify_data_js'].'" errormsg="'.$config['errormsg'].'"';
        }
        //设置HTML
        $html = '<admin:formrow title="'.$config['title'].'" tip="'.$config['tip'].'">';
        switch ($config['type']) {
            case 'text':
                $html .= '
                    <admin:text name="'.$config['name'].'" value="'.$config['value'].'" '.$verifyHtml.' width="large" />
                ';
                break;
            case 'textarea':
                $html .= '
                    <admin:textarea name="'.$config['name'].'" rows="3"  '.$verifyHtml.' >'.$config['value'].'</admin:textarea>
                ';
                break;
            case 'editor':
                $html .= '
                    <admin:textarea name="'.$config['name'].'" class="u-editor" rows="20" >'.htmlspecialchars_decode($config['value']).'</admin:textarea>
                ';
                break;
            case 'fileUpload':
                $html .= '
                    <admin:text name="'.$config['name'].'" type="text" value="'.$config['value'].'" '.$verifyHtml.' width="medium" />
                    <a class="u-btn u-btn-primary u-file-upload" data="'.$config['name'].'" id="'.$config['name'].'_upload" href="javascript:;">上传</a>
                ';
                break;
            case 'imgUpload':
                $html .= '
                    <admin:text name="'.$config['name'].'" type="text" value="'.$config['value'].'" '.$verifyHtml.' width="medium" />
                    <a class="u-btn u-btn-primary u-img-upload" data="'.$config['name'].'" id="'.$config['name'].'_upload" preview="'.$config['name'].'_preview" href="javascript:;">上传</a>
                    <a class="u-btn u-btn-primary" id="'.$config['name'].'_preview" href="javascript:;">预览</a>
                ';
                break;
            case 'imagesUpload':
                $html .= '
                    <a class="u-btn u-btn-primary u-multi-upload" type="button" data="'.$config['name'].'" id="'.$config['name'].'_button">上传</a>
                    <span class="suffix">上传后可拖动图片进行排序</span>
                    <div class="m-multi-image f-cb" id="'.$config['name'].'">';
                    if(!empty($config['value'])){
                        $list = unserialize($config['value']);
                        if(is_array($list)&&!empty($list)){
                            foreach ($list as $value) {
                                $html.='
                                <li>
                                    <a class="close" href="javascript:;">×</a>
                                    <div class="img"><span class="pic"><img src="'.$value['url'].'" width="80" height="80" /></span></div>
                                    <div class="title">
                                        <input name="'.$config['name'].'[url][]" type="hidden" value="'.$value['url'].'" />
                                        <input name="'.$config['name'].'[title][]" type="text" value="'.$value['title'].'" />
                                    </div>
                                </li>
                                ';
                            }
                        }
                    }
                $html .= '</div>
                ';
                break;
            case 'select':
                $html .= '
                    <admin:select name="'.$config['name'].'"  item="'.$config['config'].'" value="'.$config['item'].'" selected="'.$config['value'].'" />
                ';
                break;
            case 'radio':
                $html .= '
                    <admin:radio name="'.$config['name'].'"  item="'.$config['config'].'" value="'.$config['item'].'" checked="'.$config['value'].'" />
                ';
                break;
            case 'checkbox':
                $html .= '
                    <admin:checkbox name="'.$config['name'].'"  item="'.$config['config'].'" value="'.$config['item'].'" checked="'.$config['value'].'" />
                ';
                break;
            case 'textTime':
                if(!empty($config['value'])){
                    $config['value'] = date('Y/m/d H:i',$config['value']);
                }
                $html .= '
                    <admin:text name="'.$config['name'].'" class="u-time" value="'.$config['value'].'" '.$verifyHtml.' width="large" />
                ';
                break;
            case 'currency':
                $html .= '
                    <admin:text name="'.$config['name'].'" value="'.number_format($config['value'], 2, '.', '').'" '.$verifyHtml.' width="large" />
                ';
                break;
        }
        $html .= '</admin:formrow>';
        return $html;

    }

}
