<?php 
/*
 * 商家E卡控制器
 * 
 */
namespace Tseller\Controller;
use Think\Controller;
class EcardController extends Controller {
	public $token;
	public function __construct(){
    if(!I('post.token')){
      jsonRespons('false','请传入token');
    }
    $this->token = token_check(I('post.token'));
  }

  //线下E卡收款
  public function index(){
  	$card_number=I('post.card_number');
  	$money_num=I('post.money_num');
  	$password=I('post.password');
  	$user = token_user($this->token);

  	if(!$card_number || !$money_num ||!$password){
  		jsonRespons('false','参数错误',$this->token);
  	}
  	$ecard = M('SuECard')->where(array('card_number'=>$card_number,'invalid'=>0))->find();
  	if(!$ecard){
  		jsonRespons('false','不存在此E卡',$this->token);
  	}
  	if($ecard['money'] < $money_num){
  		jsonRespons('false','支付金额不足',$this->token);
  	}
  	if($ecard['pwd'] != md5($password)){
  		jsonRespons('false','支付密码错误',$this->token);
  	}

    //支付号
    $data['pay_number'] = mkpay();
    $data['order_number'] =  mkorder();
    $data['otype'] =  2;
    $data['user_id'] =  $ecard['user_id'];
    $data['seller_business_id'] =  $user['user_id'];
    $data['pay'] =  $money_num;
    $data['amount'] =  $money_num;
    $data['create_date_time'] =  date('Y-m-d H:i:s');
    $data['update_time'] =  date('Y-m-d H:i:s');
    $data['xuni_type'] =  0;
    $data['status'] =  1;
    $data['invalid'] =  0;
    $return = M('Order')->add($data);

  	if($return){
      $type = 4;
      $bottler = M('SuBottler');
      $order = M('Order');
      $ConsumeModel = M('SuConsume');
            //零售订单
      $BusinessModel =M('SuBusiness');
      $ConsumeModel = M('SuConsume');
      $CashModel = M('SuBusinessCash');

      $series = $BusinessModel->where(array('id'=>$data['seller_business_id']))->getfield('series');
      $huokuan = round($money_num - $series*$money_num*0.01,2);
      if(($money_num - $series*$money_num*0.01) < 0.01){
            jsonRespons('true','',$this->token);
      }else{
        $bus = $BusinessModel->where(array('id'=>$data['seller_business_id']))->setInc('money',$huokuan);
        $cas = $CashModel->add(array(
          'business_id'=>$data['seller_business_id'],
          'btype'=>2,
          'order_id'=>$return,
          'money'=>$huokuan,
          'create_date_time'=>date('Y-m-d H:i:s'),
          'update_time'=>date('Y-m-d H:i:s'),
          'status'=>1,
          'invalid'=>0
          ));
      }
      if($series == 6){
        $nl = round($money_num*0.25,2);
      }elseif($series == 12){
        $nl = round($money_num*0.5,2);
      }elseif($series == 24){
        $nl = $money_num;
      }
      $con = $ConsumeModel->add(array(
        'user_id'=>$ecard['user_id'],
        'business_id'=>$data['seller_business_id'],
        'order_id'=>$return,
        'money'=>$money_num,
        'series'=>$series,
        'nl'=>$nl,
        'create_date_time'=>date('Y-m-d H:i:s'),
        'update_time'=>date('Y-m-d H:i:s'),
        'status'=>1,
        'invalid'=>0
        ));

      $bottlerId = $bottler->add(array(
        'order_id' => $return,
        'pay_number' => $data['pay_number'],
        'other_number' => '',
        'user_id' => $ecard['user_id'],
        'type' => 4,
        'create_date_time' => date('Y-m-d H:i:s'),
        'update_time' => date('Y-m-d H:i:s'),
        'status' => 1,
        'invalid' => 0
        ));
      $order->where(array('id'=>$return))->setField(array('status'=>4,'pay_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),'pay_money'=>$money_num,'pay_type'=>4));
      $reg_id = M('SuBusiness')->where(array('id'=>$data['seller_business_id']))->getfield('reg_id');
      if($reg_id){
        $name = M('SuUser')->where(array('id'=>$ecard['user_id']))->getfield('realname');
        $content = $name.'成功支付您'.$money_num.'元，请注意查收。';
        // vendor('Jpush.jpush');
        // $jpush = new \Jpush_send();
        // $receiver = array(
        //   'registration_id'=>array($reg_id)
        //   );
        // $jpush->send_pub($receiver,$content,'1');
      }
    }
        //减掉卡内余额
    M('SuECard')->where(array('card_number'=>$card_number,'status'=>1,'invalid'=>0))->setDec('money',$money_num);
    $order->where(array('id'=>$return))->setField(array('card_id'=>$ecard['id']));
                    //添加日志
    $elog_add['card_id'] = $ecard['id'];
    $elog_add['money'] = $money_num;
    $elog_add['c_type'] = 4;
    $elog_add['other_name'] = '';
    $elog_add['create_date_time'] = date('Y-m-d H:i:s');
    $elog_add['update_time'] = date('Y-m-d H:i:s');
    $elog_add['status'] = 1;
    $elog_add['invalid'] = 0;
    M('SuECardLog')->add($elog_add);
    jsonRespons('true','',$this->token);
  }
}
