<?php
/*
 * 用户控制器
 * @date: 2016-12-1上午10:55:01
 * @editor: YU
 */
namespace Tseller\Controller;
use Think\Controller;
class UserController extends Controller {
    public $token;
    public function __construct(){
      parent::__construct();
      if(I('post.token')){
        $this->token = token_check(I('post.token'));
      }
      
    }
    public function index(){
    }
    /*
	   * 用户登录
	   * @date: 2016-12-1上午10:55:01
	   * @editor: YU
	   */
    public function login(){
        if(I('post.mobile') && I('post.password')){
       		$info = M('SuBusiness')->where(array('mobile'=>I('post.mobile'),'btype'=>2,'invalid'=>0))->field('id,login_pwd,status')->find();
       		if(!$info){
       			jsonRespons('false','不存在此账号');
       		}
       		if($info['login_pwd'] != md5(I('post.password'))){
       			jsonRespons('false','账号密码错误');
       		}
          if($info['status'] == 2){
            jsonRespons('false','您的账号正在审核中。');
          }
          if($info['status'] == 3){
            jsonRespons('false','您的账号已被拉入黑名单，请联系客服。');
          }
          if($info['status'] == 4){
            jsonRespons('false','您的账号审核未通过，请联系客服。');
          }
       		$create_uuid = create_uuid();
 			    //更新用户uuid
          $token_info = M('SuBusinessToken')->where(array('user_id'=>$info['id']))->field('id')->find();
          if($token_info){
            $return = M('SuBusinessToken')->where(array('id'=>$token_info['id']))->setfield(array('update_time'=>date('Y-m-d H:i:s'),'token'=>$create_uuid));
          }else{
            $data['user_id'] = $info['id'];
            $data['token'] = $create_uuid;
            $data['create_date_time'] = date('Y-m-d H:i:s');
            $data['update_time'] = date('Y-m-d H:i:s');
            $return = M('SuBusinessToken')->add($data);
          }
          if($return){
            M('SuBusiness')->where(array('id'=>$info['id']))->setfield(array('reg_id'=>I('post.reg_id')));
            jsonRespons('true','',$create_uuid);
          }else{
            jsonRespons('false','登录失败');
          }
        }else{
       		jsonRespons('false','参数错误');
        }
    }
    /*
     * 获取短信验证码
     * @date: 2016-12-2下午1:55:01
     * @editor: YU
     */
    public function getCode(){
        if(I('post.mobile')){
          if(preg_match("/^1[34578]{1}\d{9}$/",I('post.mobile'))){  
            $is = M('SuSmsLog')->where(array('mobile'=>I('post.mobile')))->count();
            $rand = rand(100000,999999);
            if($is){
              $return = M('SuSmsLog')->where(array('mobile'=>I('post.mobile')))->setfield(array('code'=>$rand,'update_time'=>date('Y-m-d H:i:s')));
            }else{
              $data['mobile'] = I('post.mobile');
              $data['code'] = $rand;
              $data['create_date_time'] = date('Y-m-d H:i:s');
              $data['update_time'] = date('Y-m-d H:i:s');
              $data['status'] = 1;
              $data['invalid'] = 0;
              $return = M('SuSmsLog')->add($data);
            }
            if($return){
              $resp = dayu_Sms1('code',$rand,I('post.mobile'),'SMS_37810016');
              if($resp['result']['success'] == true){
                jsonRespons('true','获取成功',$this->token);
              }else{
                jsonRespons('false','操作失败',$this->token);
              }
            }else{
              jsonRespons('false','获取短信验证码失败',$this->token);
            }
          }else{  
              jsonRespons('false','请输入正确的手机号码',$this->token);
          }  
        }else{
          jsonRespons('false','参数错误',$this->token);
        }
    }
    
    /*
     * 退出登录
     * @date: 2016-12-2下午1:55:01
     * @editor: YU
     */
    public function cancel(){
        if(I('post.token')){
          $return = M('SuBusinessToken')->where(array('token'=>I('post.token')))->setfield(array('token'=>''));
          if($return){
            jsonRespons('true','退出成功');
          }else{
            jsonRespons('false','退出失败',$this->token);
          }
        }else{
          jsonRespons('false','参数错误',$this->token);
        }
    }
    /*
     * 忘记密码
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function memory(){
        if(I('post.mobile') && I('post.password') && I('post.password1') && I('post.code')){
          if(I('post.password') != I('post.password1')){
            jsonRespons('false','两次密码不一致',$this->token);
          }
          //验证验证码是否有效
          $up_time = M('SuSmsLog')->where(array('mobile'=>I('post.mobile'),'code'=>I('post.code')))->getfield('update_time');
          if(!$up_time){
            jsonRespons('false','验证码错误',$this->token);
          }
          if(strtotime($up_time)+300 < time()){
            jsonRespons('false','验证码已超时',$this->token);
          }

          $is = M('SuBusiness')->where(array('mobile'=>I('post.mobile'),'invalid'=>0))->count();
          if(!$is){
            jsonRespons('false','不存在此账号',$this->token);
          }
          $return = M('SuBusiness')->where(array('mobile'=>I('post.mobile'),'invalid'=>0))->setfield(array('login_pwd'=>md5(I('post.password'))));
          if($return !== false){
            jsonRespons('true','',$this->token);
          }else{
            jsonRespons('false','修改失败',$this->token);
          }
        }else{
          jsonRespons('false','参数错误',$this->token);
        }
    }
    /*
    *分享注册前介绍页面
    *
    */
   public function share(){
     $this->display('User/share');
   }
   /*
    *用户协议页面
    *
    */
   public function agreement(){
     $this->display('User/agreement');
   }
   /*
    *版本信息
    *
    */
   public function edition(){
     $data['new_edition'] = '1.0.2';
     $data['url'] = 'http://home.dorabox.net/mohe1.9.5.apk';
     $data['logo'] = 'http://img.dorabox.net/upload/image/system/logo.png';
     $data['list'] = array('1 修复bug','2 更新');
     jsonRespons('true','',$this->token,$data);
   }
   /*
    *安卓版本信息
    *
    */
   public function Android_edition(){
     $data['new_edition'] = '1.0.1';
     $data['url'] = 'http://bao.dorabox.net/sj_test.apk';
     $data['logo'] = 'http://img.dorabox.net/upload/image/system/logo.png';
     $data['list'] = array('1 修复bug','2 更新');
     jsonRespons('true','',$this->token,$data);
   }
}