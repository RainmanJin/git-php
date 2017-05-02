<?php
/*
 * 注册控制器
 * @date: 2016-12-1上午10:55:01
 * @editor: YU
 */
namespace Tseller\Controller;
use Think\Controller;
class RegisteredController extends Controller {
   
    public function index(){ 
      if(I('mobile')){
        $categorys = M('SuHy')->where('invalid = 0 and htype=1 and parent_id = 0')->field('id,name')->select();
        $this->assign('categorys',$categorys);
        $lingshou = M('SuHy')->where('invalid = 0 and htype=2 and parent_id = 0')->field('id,name')->select();
        $this->assign('lingshou',$lingshou);
        $address = M('SuRegion')->where('grade = 1 ')->field('id,name')->select();
        $this->assign('address',$address);
        // dump($option);exit;
        $info = M('SuSalesman')->where(array('mobile'=>I('mobile')))->field('realname,mobile')->find();
        if($info){
          $this->assign('info',$info);
          $this->display();
        }
        
      }
      
    }
    public function getHy(){
      if(I('parent_id')){
      	$categorys = M('SuHy')->where('invalid = 0 and parent_id = '.I('parent_id'))->field('id,name')->select();
      	if($categorys){
      		foreach ($categorys as $key => $value) {
      			$html .="<option value=" . $value["id"] . ">"  . $value['name'] . "</option>";
      		}
      	}
      }
      echo json_encode(array('code'=>1,'data'=>$html));exit;
    }
    public function getshi(){
      if(I('parent_id')){
        $categorys = M('SuRegion')->cache(true)->where(array('parent'=>I('parent_id')))->field('id,name')->select();
        if($categorys){
          foreach ($categorys as $key => $value) {
            $html .="<option value=" . $value["id"] . ">"  . $value['name'] . "</option>";
          }
        }
      }
      echo json_encode(array('code'=>1,'data'=>$html));exit;
    }
    public function tijiao(){
      $post = I('post.');
      //进行验证
      $salesman_id = M('SuSalesman')->where(array('mobile'=>$post['tjmobile'],'status'=>1,'invalid'=>0))->getfield('id');
      if(!$salesman_id){
        echo json_encode(array('code'=>0,'msg'=>'无业务员推荐或业务员状态异常'));exit;
      }
      if(!$post['leixing']){
        echo json_encode(array('code'=>0,'msg'=>'请选择商家类型'));exit;
      }
      $business_data['salesman_id'] = $salesman_id;
      $business_data['btype'] = $post['leixing'];
      if(!preg_match("/^1[34578]{1}\d{9}$/",$post['mobile'])){  
        echo json_encode(array('code'=>0,'msg'=>'请填写正确的注册手机号'));exit;
      }
      //验证一下手机号码是否已被注册
      $is = M('SuAccount')->where(array('mobile'=>$post['mobile'],'invalid'=>0))->count();
      if($is){
        echo json_encode(array('code'=>0,'msg'=>'该号码已注册'));exit;
      }
      $business_data['mobile'] = $post['mobile'];
      $password = rand(100000,999999);
      $business_data['login_pwd'] = md5($password);
      if(!$post['name']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写商家名称'));exit;
      }
      $business_data['name'] = $post['name'];
      if(!$post['rangli']){  
        echo json_encode(array('code'=>0,'msg'=>'请选择让利类型'));exit;
      }
      $business_data['series'] = $post['rangli'] == 12?12:24;
      if(!$post['fanwei']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写经营范围'));exit;
      }
      $business_data['range'] = $post['fanwei'];
      if(!$post['operation_title']){  
        echo json_encode(array('code'=>0,'msg'=>'主营业务'));exit;
      }
      $business_data['operation_title'] = $post['operation_title'];
      if(!$post['datetimepicker3']){  
        echo json_encode(array('code'=>0,'msg'=>'请选择营业期限时间'));exit;
      }
      $business_data['start_time'] = $post['datetimepicker3'];
      $business_data['end_time'] = $post['datetimepicker4'];
      if($post['leixing'] == 1){
        if(!$post['yiji']){  
          echo json_encode(array('code'=>0,'msg'=>'请选择行业类型'));exit;
        }
        $business_data['hy_first_id'] = $post['yiji'];
      }else{
        if(!$post['yiji']){  
          echo json_encode(array('code'=>0,'msg'=>'请选择行业类型'));exit;
        }
        $business_data['hy_first_id'] = $post['yiji'];
        if(!$post['erji']){  
          echo json_encode(array('code'=>0,'msg'=>'请选择行业类型'));exit;
        }
        $business_data['hy_second_id'] = $post['erji'];
      }
      if(!$post['person_name']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写负责人姓名'));exit;
      }
      $business_data['person_name'] = $post['person_name'];
      if(!$post['person_mobile']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写负责人电话'));exit;
      }
      $business_data['person_mobile'] = $post['person_mobile'];
      if(!preg_match("/^(\d{18,18}|\d{15,15}|\d{17,17}x)$/",$post['cardid'])){  
        echo json_encode(array('code'=>0,'msg'=>'请填写正确的负责人身份证号'));exit;
      }
      $business_data['cardid'] = $post['cardid'];
      if(!preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i",$post['mailbox'])){  
        echo json_encode(array('code'=>0,'msg'=>'请填写正确的邮箱'));exit;
      }
      $business_data['mailbox'] = $post['mailbox'];
      if(!$post['swdjz_number']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写社会信用代码或注册号'));exit;
      }
      $business_data['swdjz_number'] = $post['swdjz_number'];
      if(!$post['nsleibie']){  
        echo json_encode(array('code'=>0,'msg'=>'请选择纳税人类别'));exit;
      }
      $business_data['nsr_type'] = $post['nsleibie'];
      if(!$post['account_name']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写开户名'));exit;
      }
      $business_data['account_name'] = $post['account_name'];
      if(!$post['bank_card_number']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写银行卡号'));exit;
      }
      $business_data['bank_card_number'] = $post['bank_card_number'];
      if(!$post['bank_open_name']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写支行名称'));exit;
      }
      $business_data['bank_open_name'] = $post['bank_open_name'];
      if(!$post['bank_name']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写银行名称'));exit;
      }
      $business_data['bank_name'] = $post['bank_name'];
      if(!$post['fapiao']){  
        echo json_encode(array('code'=>0,'msg'=>'请选择发票类型'));exit;
      }
      $business_data['nsr_type_fp'] = $post['fapiao'];
      if(!$post['reg_address']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写注册地址'));exit;
      }
      $business_data['reg_address'] = $post['reg_address'];
      if(!$post['finance_phone']){
        echo json_encode(array('code'=>0,'msg'=>'请填写财务电话'));exit;
      }
      $business_data['finance_phone'] = $post['finance_phone'];
      // if((preg_match("/^([0-9]{3,4}-)?[0-9]{7,8}$/",$post['finance_phone'])) || (preg_match("/^1[34578]{1}\d{9}$/",$post['finance_phone']))){  
      //   $business_data['finance_phone'] = $post['finance_phone'];
      // }else{
      //   echo json_encode(array('code'=>0,'msg'=>'请填写正确的联系电话'));exit;
      // }
      // if($post['hezhao_img']){
      //   $business_data['new_yyzz'] = $post['hezhao_img'];
      // }else{
      //   $business_data['yyzz_img'] = $post['yingye_img'];
      //   $business_data['swdjz_img'] = $post['shuiwu_img'];
      //   $business_data['jgdm_img'] = $post['zuzhi_img'];
      //   $business_data['crs_img'] = $post['chengnuo_img'];
      //   $business_data['nsrzm_img'] = $post['nashui_img'];
      //   $business_data['scsfz_img'] = $post['shenfen_img'];
      // }
      $business_data['new_yyzz'] = $post['hezhao_img'];
      $business_data['nsrzm_img'] = $post['nashui_img'];
      if(!$post['shenfen_img']){  
        echo json_encode(array('code'=>0,'msg'=>'请上传手持身份证照'));exit;
      }
      $business_data['scsfz_img'] = $post['shenfen_img'];
      if(!$post['chengnuo_img']){  
        echo json_encode(array('code'=>0,'msg'=>'请上传承诺书'));exit;
      }
      $business_data['crs_img'] = $post['chengnuo_img'];
      $business_data['create_date_time'] = date('Y-m-d H:i:s');
      $business_data['update_time'] = date('Y-m-d H:i:s');
      $business_data['status'] = 2;
      $business_data['invalid'] = 0;
      //店铺信息
      if(!$post['other_name']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写店铺名称'));exit;
      }
      $detail_data['other_name'] = $post['other_name'];
      if(!$post['menmian']){  
        echo json_encode(array('code'=>0,'msg'=>'请上传店铺门店照片'));exit;
      }
      $detail_data['store_img'] = $post['menmian'];
      if(!$post['sheng']){  
        echo json_encode(array('code'=>0,'msg'=>'请选择省'));exit;
      }
      $detail_data['provice_name'] = $post['sheng'];
      if(!$post['shi']){  
        echo json_encode(array('code'=>0,'msg'=>'请选择市'));exit;
      }
      $detail_data['city_name'] = $post['shi'];
      $detail_data['area'] = $post['qu'];
      if(!$post['dizhi']){  
        echo json_encode(array('code'=>0,'msg'=>'请填写店铺详细地址'));exit;
      }
      $detail_data['address'] = $post['dizhi'];
       if(!$post['datetimepicker1'] && !$post['datetimepicker2']){  
        echo json_encode(array('code'=>0,'msg'=>'请选择营业时间'));exit;
      }
      if(!$post['phone']){
        echo json_encode(array('code'=>0,'msg'=>'请填写客服电话'));exit;
      }
      $detail_data['phone'] = $post['phone'];
      // if((preg_match("/^([0-9]{3,4}-)?[0-9]{7,8}$/",$post['phone'])) || (preg_match("/^1[34578]{1}\d{9}$/",$post['phone']))){  
      //   $detail_data['phone'] = $post['phone'];
      // }else{
      //   echo json_encode(array('code'=>0,'msg'=>'请填写正确的联系电话'));exit;
      // }
      $detail_data['open_time'] = $post['datetimepicker1'].'-'.$post['datetimepicker2'];
      $detail_data['address'] = $post['dizhi'];
      $detail_data['create_date_time'] = date('Y-m-d H:i:s');
      $detail_data['update_time'] = date('Y-m-d H:i:s');
      $detail_data['status'] = 2;
      $detail_data['invalid'] = 0;
      //店铺轮播图信息
      $lunbo = $post['lunbo'];
      $lunbo = explode(';',$lunbo);
      $lunbo_length = count($lunbo) - 1;
      if($lunbo_length<3){
        echo json_encode(array('code'=>0,'msg'=>'最少上传三张产品或经营场所照片'));exit;
      }
      $BusinessModel = M('SuBusiness');
      $BusinessDetailModel = M('SuBusinessDetail');
      $BannerModel = M('SuBusinessDetailBanner');
      $AccountModel = M('SuAccount');
      $BusinessModel->startTrans();
      $business_id = $BusinessModel->lock(true)->add($business_data);
      if($business_id){
        $detail_data['business_id'] = $business_id;
        $detail_id = $BusinessDetailModel->lock(true)->add($detail_data);
        if($detail_id){
          //商家轮播图
          for ($i=0; $i < $lunbo_length; $i++) { 
            if($lunbo[$i]){
              $banner_data[$i]['business_detail_id'] = $detail_id;
              $banner_data[$i]['img_url'] = $lunbo[$i];
              $banner_data[$i]['create_date_time'] = date('Y-m-d H:i:s');
              $banner_data[$i]['update_time'] = date('Y-m-d H:i:s');
              $banner_data[$i]['status'] = 1;
              $banner_data[$i]['invalid'] = 0;
            }
          }
          $banner = $BannerModel->lock(true)->addAll($banner_data);
          if($banner){
            $account = $AccountModel->lock(true)->add(array('mobile'=>$post['mobile'],'atype'=>2,'create_date_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),'status'=>1,'invalid'=>0));
            if($account){
              //发送短信
              $resp = dayu_Sms1('passwordf',$password,$post['mobile'],'SMS_37610245');
              $BusinessModel->commit();
              $BusinessDetailModel->commit();
              $BannerModel->commit();
              $AccountModel->commit();
              echo json_encode(array('code'=>1,'msg'=>''));exit;
            }else{
              $BusinessModel->rollback();
              $BusinessDetailModel->rollback();
              $BannerModel->rollback();
              $AccountModel->rollback();
              echo json_encode(array('code'=>0,'msg'=>'申请失败'));exit;
            }
          }else{
            $BusinessModel->rollback();
            $BusinessDetailModel->rollback();
            $BannerModel->rollback();
            echo json_encode(array('code'=>0,'msg'=>'申请失败'));exit;
          }
        }else{
          $BusinessModel->rollback();
          $BusinessDetailModel->rollback();
          echo json_encode(array('code'=>0,'msg'=>'申请失败'));exit;
        }
      }else{
        $BusinessModel->rollback();
        echo json_encode(array('code'=>0,'msg'=>'申请失败'));exit;
      }
    }
     public function yanzheng(){
      if(I('mobile')){
        if(!preg_match("/^1[34578]{1}\d{9}$/",I('mobile'))){  
          echo json_encode(array('code'=>1,'data'=>'*请填写正确的注册手机号'));exit;
        }
        $is = M('SuAccount')->where(array('mobile'=>I('mobile'),'invalid'=>0))->count();
        if($is){
          echo json_encode(array('code'=>1,'data'=>'*该号码已注册'));exit;
        }else{
          echo json_encode(array('code'=>1,'data'=>'*该号码未注册'));exit;
        }
      }
      
    }
}