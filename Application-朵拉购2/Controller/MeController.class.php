<?php
/*
 * 我的控制器
 * @date: 2016-12-1上午10:55:01
 * @editor: YU
 */
namespace Tseller\Controller;
use Think\Controller;
class MeController extends Controller {
    public $token;
    public function __construct(){
      if(!I('post.token')){
        jsonRespons('false','请传入token');
      }
      $this->token = token_check(I('post.token'));
    }
    /*
     * 我的
     * @date: 2016-12-6上午10:55:01
     * @editor: YU
     */
    public function index(){
      $user = token_user($this->token);
      $info = M('SuBusiness')->where(array('id'=>$user['user_id']))->field('img,name,btype,mobile,pay_pass,series,salesman_id')->find();
      $data['img'] = $info['img'];
      $data['name'] = $info['name'];
      $data['shenfen'] = $info['btype'] == 1?'企业型商家':'零售型商家';
      $data['pay_pass'] = $info['pay_pass']?1:0;
      $data['mobile'] = $info['mobile'];
      $data['series'] = $info['series'];
      //获取业务员信息
      $sales = M('SuSalesman')->where(array('id'=>$info['salesman_id']))->field('mobile,realname')->find();
      $data['referrer'] = $sales['realname'];
      $data['referrer_phone'] = $sales['mobile'];
      //是否存在未读消息
      $is = M('SysNotice')->where(array('business_id'=>$user['user_id'],'intval'=>0,'status'=>0))->count();
      if($is){
        $data['read'] = 1;
      }else{
        $data['read'] = 0;
      }
      //商家名称
      $other_name = M('SuBusinessDetail')->where(array('business_id'=>$user['user_id']))->getfield('other_name');
      $data['qr_url'] = '朵拉宝：'.$other_name.'：'.$user['user_id'];
      $data['name'] = $other_name;
      jsonRespons('true','',$this->token,$data);
    }
    /*
	   * 消息列表
	   * @date: 2016-12-1上午10:55:01
	   * @editor: YU
	   */
    public function notice_list(){
      $page = I('page',0,'intval');
      $user = token_user($this->token);
      $list = M('SysNotice')->where(array('business_id'=>$user['user_id'],'intval'=>0))->field('content,create_date_time')->order('id desc')->limit($page*10,10)->select();
      foreach ($list as $key => $value) {
            $list[$key]['create_date_time'] = date('Y月m日',strtotime($value['create_date_time']));
      }
      M('SysNotice')->where(array('business_id'=>$user['user_id'],'status'=>0))->setfield(array('status'=>1));
      jsonRespons('true','',$this->token,$list);
    }
    /*
     * 上传极光Id
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function up_reg(){
      if(I('post.reg_id')){
        $user = token_user($this->token);
        M('SuBusiness')->where(array('id'=>$user['user_id']))->setfield(array('reg_id'=>I('post.reg_id')));
      }
      jsonRespons('true','',$this->token);
    }
    /*
     * 修改密码
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function up_password(){
        if(I('post.password') && I('post.password1') && I('post.password2')){
          if(I('post.password1') != I('post.password2')){
            jsonRespons('false','两次密码不一致',$this->token);
          }
          $user = token_user($this->token);
          $info = M('SuBusiness')->where(array('id'=>$user['user_id'],'invalid'=>0))->field('id,login_pwd')->find();
          if($info['login_pwd'] != md5(I('post.password'))){
            jsonRespons('false','原密码错误',$this->token);
          }
          $return = M('SuBusiness')->where(array('id'=>$user['user_id'],'invalid'=>0))->setfield(array('login_pwd'=>md5(I('post.password1'))));
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
     * 设置支付密码
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function set_pay_pass(){
        if(I('post.password') && I('post.password1')){
          if(I('post.password1') != I('post.password')){
            jsonRespons('false','两次密码不一致',$this->token);
          }
          $user = token_user($this->token);
          $return = M('SuBusiness')->where(array('id'=>$user['user_id'],'invalid'=>0))->setfield(array('pay_pass'=>md5(I('post.password1'))));
          if($return){
            jsonRespons('true','',$this->token);
          }else{
            jsonRespons('false','修改失败',$this->token);
          }
        }else{
          jsonRespons('false','参数错误',$this->token);
        }
    }
    /*
     * 修改支付密码
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function up_pay_pass(){
        if(I('post.password') && I('post.password1') && I('post.password2')){
          if(I('post.password1') != I('post.password2')){
            jsonRespons('false','两次密码不一致',$this->token);
          }
          $user = token_user($this->token);
          $info = M('SuBusiness')->where(array('id'=>$user['user_id'],'invalid'=>0))->field('id,pay_pass')->find();
          if($info['pay_pass'] != md5(I('post.password'))){
            jsonRespons('false','原密码错误',$this->token);
          }
          $return = M('SuBusiness')->where(array('id'=>$user['user_id'],'invalid'=>0))->setfield(array('pay_pass'=>md5(I('post.password1'))));
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
     * 忘记支付密码
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
          $return = M('SuBusiness')->where(array('mobile'=>I('post.mobile'),'invalid'=>0))->setfield(array('pay_pass'=>md5(I('post.password'))));
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
     * 修改头像
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function up_head(){
        if($_FILES['img']){
          vendor('upload.UpLoad');
          $upload = new \UpLoad;
          $getUpImd = '';
          $userImg = $upload->upimgs($_FILES,'img');
          $getUpImd = $userImg[0];
          $user = token_user($this->token);
          $return = M('SuBusiness')->where(array('id'=>$user['user_id'],'invalid'=>0))->setfield(array('img'=>$getUpImd));
          $data['url'] = $getUpImd;
          if($return){
            jsonRespons('true','',$this->token,$data);
          }else{
            jsonRespons('false','保存失败',$this->token);
          }
        }else{
          jsonRespons('false','参数错误',$this->token);
        }
    }
    /*
     * 店铺信息
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function sellerDetail(){
        $user = token_user($this->token);
        $info = M('SuBusinessDetail')->where(array('business_id'=>$user['user_id'],'invalid'=>0))->field('id,other_name,open_time,address,phone,longitude,latitude,open_time')->find();
        if(!$info){
          jsonRespons('false','不存在此商家',$this->token);  
        }
        $detail = M('SuBusiness')->where(array('id'=>$user['user_id']))->field('series,operation_title')->find();
        $img_list = M('SuBusinessDetailBanner')->where(array('business_detail_id'=>$info['id']))->field('img_url')->select();
        $info['banner'] = $img_list;
        unset($info['id']);
        $info['series'] = $detail['series'];
        $info['operation_title'] = $detail['operation_title'];
        jsonRespons('true','',$this->token,$info);
    }
    /*
     * 首页弹窗消息
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function xiaoxi(){
      $token = I('post.token');
      $data = array();
      if($token){
        $token = token_check($token);
        $user = token_user($token);
        $data = '';
        if($user['user_id']){
            $sys = M('SysNotice')->where(array('business_id'=>$user['user_id'],'notice_type_id'=>2,'invalid'=>0))->order('id desc')->find();
            if($sys){
                $data['id'] = $sys['id'];
                $data['name'] = $sys['name']?:'';
                $data['content'] = $sys['content'];
                $data['url'] = 'http://bao.dorabox.net/?c=user&a=xiaoxi&id='.$sys['id'];
            }
        }
      }
      jsonRespons('true','',$token,$data);
    }
    /*
     * 新消息列表
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function notice_list_new(){
      $page = I('page',0,'intval');
      $user = token_user($this->token);
      $list = M('SysNotice')->where(array('business_id'=>$user['user_id'],'intval'=>0))->field('id,name,status,create_date_time')->order('id desc')->limit($page*10,10)->select();
      foreach ($list as $key => $value) {
        $list[$key]['url'] = 'http://bao.dorabox.net/?c=user&a=xiaoxi&id='.$value['id'];
        $list[$key]['create_date_time'] = date('m月d日',strtotime($value['create_date_time']));
      }
      // M('SysNotice')->where(array('business_id'=>$user['user_id'],'status'=>0))->setfield(array('status'=>1));
      jsonRespons('true','',$this->token,$list);
    }
    /*
     * 标记为已读
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function set_notice(){
      $user = token_user($this->token);
      M('SysNotice')->where(array('business_id'=>$user['user_id'],'status'=>0))->setfield(array('status'=>1));
      jsonRespons('true','',$this->token);
    }
}