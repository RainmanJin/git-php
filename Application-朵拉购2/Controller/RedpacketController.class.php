<?php 
namespace Test\Controller;
use Think\Controller;
class RedpacketController extends Controller {
	// public $token;
 //    public function __construct(){
 //      if(!I('post.token')){
 //        jsonRespons('false','请传入token');
 //      }
 //      $this->token = token_check(I('post.token'));
 //    }
	
	public function index(){
		$user = M('SuUser')->where(array('mobile'=>I('mobile')))->field('realname,avatar_img')->find();
		$this->assign('user',$user);
		$this->display();
	}

	public function redPacketInstruction(){
		$this->display();
	}

	public function getpacket(){
		// $count=M('SuRedPacket')->query('select count(*) as count from tbl_su_red_packet where mobile is null');
		// $suiji=rand(0,$count[0]['count']);
		$sql="select `money`,`id` from tbl_su_red_packet where mobile is null order by rand() limit 1";
		$money = M('SuRedPacket')->query($sql);
		$this->assign('money',$money);
		$this->display();
	}

	public function yzm(){
		$is = M('SuSmsLog')->where(array('mobile'=>I('mobiles')))->count();
    	$rand = rand(100000,999999);
    	if($is){
    		$return = M('SuSmsLog')->where(array('mobile'=>I('mobiles')))->setfield(array('code'=>$rand,'update_time'=>date('Y-m-d H:i:s')));
    	}else{
    		$data['mobile'] = I('mobiles');
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

        $this->display('getPacket');	
	}

	public function check(){
		$mobile =I('mobiles');
		$yzm =I('yzm');
		$tj=I('mobile');
		$hb_id=I('hb_id');
		$is=M('SuRedPacket')->where(array('mobile'=>$mobile))->field('id')->find();
		$code=M('SuSmsLog')->where(array('mobile'=>$mobile))->field('code')->find();
		if(!isset($yzm)){
			//验证码错误
			echo json_encode(array('code'=>3,'msg'=>''));exit;
		}
		if($code['code']==$yzm){
			if($mobile==$tj){
				//不能邀请自己
				echo json_encode(array('code'=>0,'msg'=>''));exit;
			}
			if($is){
				//你已经领取红包了
				echo json_encode(array('code'=>1,'msg'=>''));exit;
			}else{
				//领取成功
				$tjr=M('SuUser')->where(array('mobile'=>$tj))->find();
				$data['user_id']=$tjr['id'];
				$data['mobile']=$mobile;
				$data['start_time']=date('Y-m-d H:i:s');
				$day=M('SuRedPacket')->join('tbl_su_red_type on tbl_su_red_packet.red_type = tbl_su_red_type.id')->where(array('tbl_su_red_packet.id'=>$hb_id))->field('tbl_su_red_type.day')->find();
				$data['use_time']=date("Y-m-d H:i:s",strtotime("+".$day['day']." day"));
				$hb=M('SuRedPacket')->where(array('id'=>$hb_id))->save($data);
				if($hb){
					echo json_encode(array('code'=>2,'msg'=>''));exit;
				}
			}
		}else{
			//验证码错误
			echo json_encode(array('code'=>3,'msg'=>''));exit;
		}

	}

	

}

