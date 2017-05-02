<?php
namespace Test\Controller;
use Think\Controller;
class ShareController extends Controller {
    public function index(){

        $this->display();
    }
    public function yzm(){
        
    	$is = M('SuSmsLog')->where(array('mobile'=>I('mobile')))->count();
    	$rand = rand(100000,999999);
    	if($is){
    		$return = M('SuSmsLog')->where(array('mobile'=>I('mobile')))->setfield(array('code'=>$rand,'update_time'=>date('Y-m-d H:i:s')));
    	}else{
    		$data['mobile'] = I('mobile');
    		$data['code'] = $rand;
    		$data['create_date_time'] = date('Y-m-d H:i:s');
    		$data['update_time'] = date('Y-m-d H:i:s');
    		$data['status'] = 1;
    		$data['invalid'] = 0;
    		$return = M('SuSmsLog')->add($data);
    	}
    	if($return){
    		// $resp = dayu_Sms1('code',$rand,I('mobile'),'SMS_37810016');
    	}
        $this->display();
    }
    public function check_user(){
        //查询该号码是否已注册
        $is = M('SuAccount')->where(array('mobile'=>I('post.mobile'),'invalid'=>0))->count();
        if($is){
            echo json_encode(array('code'=>0,'msg'=>'该号码已注册'));exit;
        }
        echo json_encode(array('code'=>1,'msg'=>''));exit;
    }
    public function yanzheng(){
    	
    	$return = M('SuSmsLog')->where(array('mobile'=>I('mobile')))->find();
    	if($return['code'] == I('code')){
    		echo json_encode(array('code'=>1,'msg'=>I('code')));exit;
    	}else{
    		echo json_encode(array('code'=>0,'msg'=>'验证码错误'));exit;
    	}
    }
    public function zhuce(){
          if(!preg_match("/^1[34578]{1}\d{9}$/",I('post.mobile'))){ 
            echo json_encode(array('code'=>0,'msg'=>'请输入正确的手机号码'));exit;
          }
          if(I('post.t_mobile')){
            //查询是否存在推荐人
              $tuijian = M('SuUser')->where(array('mobile'=>I('post.t_mobile'),'invalid'=>0))->field('id,realname')->find();
              if(!$tuijian){
                echo json_encode(array('code'=>0,'msg'=>'不存在此推荐人'));exit;
              }
          }else{
              $tuijian = M('SuUser')->where(array('mobile'=>'13221099029','invalid'=>0))->field('id,realname')->find();
          }
          
          //查询该号码是否已注册
          $is = M('SuAccount')->where(array('mobile'=>I('post.mobile'),'invalid'=>0))->count();
          if($is){
            echo json_encode(array('code'=>0,'msg'=>'该号码已注册'));exit;
          }
          $data['mobile'] = I('post.mobile');
          $data['login_pwd'] = md5(I('post.password'));
          $data['card_number'] = M('SuUser')->order('id desc')->getfield('card_number')+1;
          $data['realname'] = I('post.name');
          $data['referrer_phone'] = I('post.t_mobile')?:'13221099029';
          $data['balance'] = 0;
          $data['referrer'] = $tuijian['realname'];
          $data['referrer_id'] = $tuijian['id'];
          $data['create_date_time'] = date('Y-m-d H:i:s');
          $data['update_time'] = date('Y-m-d H:i:s');
          $data['status'] = 1;
          $data['invalid'] = 0;
          $return = M('SuUser')->add($data);
          if($return !== false){
            M('SuAccount')->add(array('mobile'=>I('post.mobile'),'atype'=>1,'create_date_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),'status'=>1,'invalid'=>0));
            //给用户和推荐人添加种子
            $add[0]['user_id'] = $return;
            $add[0]['seed_count'] = 10;
            $add[0]['seed_id'] = 3;
            $add[0]['create_date_time'] = date('Y-m-d H:i:s');
            $add[0]['update_time'] = date('Y-m-d H:i:s');
            $add[0]['status'] = 1;
            $add[0]['invalid'] = 0;
            $add[1]['user_id'] = $tuijian['id'];
            $add[1]['seed_count'] = 10;
            $add[1]['seed_id'] = 3;
            $add[1]['create_date_time'] = date('Y-m-d H:i:s');
            $add[1]['update_time'] = date('Y-m-d H:i:s');
            $add[1]['status'] = 1;
            $add[1]['invalid'] = 0;
            M('SuUserStimulate')->addAll($add);
            //更新种子累计表
            $zhongzi_add['user_id'] = $return;
            $zhongzi_add['seed_count'] = 10;
            $zhongzi_add['create_date_time'] = date('Y-m-d H:i:s');
            $zhongzi_add['update_time'] = date('Y-m-d H:i:s');
            $zhongzi_add['status'] = 1;
            $zhongzi_add['invalid'] = 0;
            M('SuSumSeed')->add($zhongzi_add);
            M('SuSumSeed')->where(array('user_id'=>$tuijian['id']))->setInc('seed_count',10);
            //注册云旺客服账号
            vendor('tongxun.TopSdk');
            $c = new \TopClient;
            $c->appkey = '23592273';
            $c->secretKey = '90bbe0ea1258b2ae727c6d8969a977a3';
            $req = new \OpenimUsersAddRequest;
            $userinfos = new \Userinfos;
            $userinfos->nick=$data['realname'];
            $userinfos->userid=$data['mobile'];
            $userinfos->password=$data['referrer_phone'];
            // $userinfos->icon_url=$info['avatar_img'];
            $req->setUserinfos(json_encode($userinfos));
            $resp = $c->execute($req);
            // $resp = objectToArray($resp);
            echo json_encode(array('code'=>1,'msg'=>'注册成功'));exit;
          }else{
            echo json_encode(array('code'=>0,'msg'=>'注册失败'));exit;
          }

    }
}