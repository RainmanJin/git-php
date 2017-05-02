<?php
/*
 * 支付回调
 * @date: 2016-12-1上午10:55:01
 * @editor: YU
 */
namespace test\Controller;
use Think\Controller;
class PayController extends Controller {
    public function index(){
    }
    /*
     * 支付回调
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function refund(){
      $getParams = json_decode(file_get_contents("php://input"), true);
      if($getParams && $getParams['type'] == 'charge.succeeded'){
        $pay_number = trim($getParams['data']['object']['metadata']['pay_number']);
        /*在线支付金额*/
        $cashMoney = $getParams['data']['object']['amount']/100;
        /*支付渠道*/
        $payChannelWay = $getParams['data']['object']['channel'];
        /*支付凭证id*/
        $chargeId = $getParams['data']['object']['id'];
        /*交易号*/
        $transaction_no = $getParams['data']['object']['transaction_no'];
        $order_type = trim($getParams['data']['object']['subject']);
        if($order_type == '余额充值'){
            $yue = M('SuUserMoneyLog')->where(array('pay_number'=>$pay_number))->find();
            if($yue['status'] == 2){
              $return = M('SuUserMoneyLog')->where(array('id'=>$yue['id']))->setField(array('other_number'=>$transaction_no,'status'=>1,'update_time'=>date('Y-m-d H:i:s')));
              if($return){
                M('SuUser')->where(array('id'=>$yue['user_id']))->setInc('money',$cashMoney);
              }
            }else{
              //状态失败 或者不存在此支付号
            }
        }elseif($order_type == 'E卡充值'){
           $ecard = M('SuECardLog')->where(array('pay_number'=>$pay_number))->find();
            if($ecard['status'] == 2){
              $return = M('SuECardLog')->where(array('id'=>$ecard['id']))->setField(array('other_number'=>$transaction_no,'status'=>1,'update_time'=>date('Y-m-d H:i:s')));
              if($return){
                M('SuECard')->where(array('id'=>$ecard['card_id']))->setInc('money',$cashMoney);
              }
            }else{
              //状态失败 或者不存在此支付号
            }
        }else{
          $order_list = M('Order')
               ->where(array('pay_number'=>$pay_number))->select();
          
          // file_put_contents('log.txt',$pay_number);
          // if($order_list['pay'] != $cashMoney){
          //   file_put_contents('log.txt', round(($getOrderMsg['order_amount'] - $getOrderMsg['order_discount']),2).'/'.$cashMoney);exit;
          // }
          $token = M('JdToken')->getfield('token');
          $bottler = M('SuBottler');
          $order = M('Order');
          $ConsumeModel = M('SuConsume');
          if($order_list[0]['otype'] == 1){
            foreach ($order_list as $key => $value) {
              if($value['status'] == 1){
                  $bottlerId = $bottler->add(array(
                      'order_id' => $value['id'],
                      'pay_number' => $pay_number,
                      'other_number' => $transaction_no,
                      'user_id' => $value['user_id'],
                      'type' => $payChannelWay=='wx'?2:1,
                      'create_date_time' => date('Y-m-d H:i:s'),
                      'update_time' => date('Y-m-d H:i:s'),
                      'status' => 1,
                      'invalid' => 0
                  ));
                  //如果是虚拟产品加油卡
                  if($value['xuni_type'] == 1 && $value['jiayouka']){
                    jiayouka_recharge($value['user_id'],$value['id'],$value['order_number'],100018,$value['jiayouka']);
                    $order->where(array('id'=>$value['id']))->setField(array('status'=>3,'pay_time'=>date('Y-m-d H:i:s'),'express_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),'pay_money'=>$cashMoney));
                  }elseif($value['is_jd'] == 1){
                    //如果是京东订单
                    //确认订单
                    $order->where(array('id'=>$value['id']))->setField(array('status'=>3,'pay_time'=>date('Y-m-d H:i:s'),'express_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),'pay_money'=>$cashMoney));
                    // $data['token'] = $token;
                    // $data['jdOrderId'] = $value['jd_number'];
                    // $return = curl('https://bizapi.jd.com/api/order/confirmOrder',$data,'','json');
                    M('JdConfirmLog')->add(array(
                        'order_id'=>$value['id'],
                        'state'=>$return['resultMessage'],
                        'create_date_time'=>date('Y-m-d H:i:s'),
                      ));
                    M('OrderExpressMsg')->add(array(
                        'order_id'=>$value['id'],
                        'express_number'=>$value['jd_number'],
                        'express_memo'=>'京东配送',
                        'create_date_time'=>date('Y-m-d H:i:s'),
                        'update_time'=>date('Y-m-d H:i:s'),
                        'status'=>1,
                        'invalid'=>0,
                      ));
                  }else{
                    $order->where(array('id'=>$value['id']))->setField(array('status'=>2,'pay_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),'pay_money'=>$cashMoney));
                  }
                  //给商家发送短信提醒
                  $remind_mobile = M('SuBusiness')->where(array('id'=>$value['seller_business_id']))->getfield('remind_mobile');
                  if($remind_mobile){
                    $other_name = M('SuBusinessDetail')->where(array('business_id'=>$value['seller_business_id']))->getfield('other_name');
                    dayu_Sms2('name',$other_name,'number',$value['order_number'],$remind_mobile,'SMS_45405004');
                  }
                  
              }
            }
          }elseif($order_list[0]['otype'] == 2){
            //零售订单
            $BusinessModel =M('SuBusiness');
            $ConsumeModel = M('SuConsume');
            $CashModel = M('SuBusinessCash');
            
            $series = $BusinessModel->where(array('id'=>$order_list[0]['seller_business_id']))->getfield('series');
            $huokuan = round($order_list[0]['pay'] - $series*$order_list[0]['pay']*0.01,2);
            if(($order_list[0]['pay'] - $series*$order_list[0]['pay']*0.01) < 0.01){

            }else{
              $bus = $BusinessModel->where(array('id'=>$order_list[0]['seller_business_id']))->setInc('money',$huokuan);
              $cas = $CashModel->add(array(
                'business_id'=>$order_list[0]['seller_business_id'],
                'btype'=>2,
                'order_id'=>$order_list[0]['id'],
                'money'=>$huokuan,
                'create_date_time'=>date('Y-m-d H:i:s'),
                'update_time'=>date('Y-m-d H:i:s'),
                'status'=>1,
                'invalid'=>0
              ));
            }
            if($series == 6){
              $nl = round($order_list[0]['pay']*0.25,2);
            }elseif($series == 12){
              $nl = round($order_list[0]['pay']*0.5,2);
            }elseif($series == 24){
              $nl = $order_list[0]['pay'];
            }
            $con = $ConsumeModel->add(array(
                        'user_id'=>$order_list[0]['user_id'],
                        'business_id'=>$order_list[0]['seller_business_id'],
                        'order_id'=>$order_list[0]['id'],
                        'money'=>$order_list[0]['pay'],
                        'series'=>$series,
                        'nl'=>$nl,
                        'create_date_time'=>date('Y-m-d H:i:s'),
                        'update_time'=>date('Y-m-d H:i:s'),
                        'status'=>1,
                        'invalid'=>0
                    ));
            
            $bottlerId = $bottler->add(array(
              'order_id' => $order_list[0]['id'],
              'pay_number' => $pay_number,
              'other_number' => $transaction_no,
              'user_id' => $order_list[0]['user_id'],
              'type' => $payChannelWay=='wx'?2:1,
              'create_date_time' => date('Y-m-d H:i:s'),
              'update_time' => date('Y-m-d H:i:s'),
              'status' => 1,
              'invalid' => 0
              ));
            $order->where(array('id'=>$order_list[0]['id']))->setField(array('status'=>4,'pay_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),'pay_money'=>$cashMoney));
            $reg_id = M('SuBusiness')->where(array('id'=>$order_list[0]['seller_business_id']))->getfield('reg_id');
            if($reg_id){
              $name = M('SuUser')->where(array('id'=>$order_list[0]['user_id']))->getfield('realname');
              $content = $name.'成功支付您'.$order_list[0]['pay'].'元，请注意查收。';
              vendor('Jpush.jpush');
              $jpush = new \Jpush_send();
              $receiver = array(
                'registration_id'=>array($reg_id)
                );
              $jpush->send_pub($receiver,$content,'1');
            }
          }
        }
        
      }else{
        file_put_contents('log.txt', '未获取到数据包');exit;
      }
    }
    /*
     * E卡支付
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function pay(){
      $pay_number = I('pay_number');
      $card_number = I('card_number');
      $password = I('password');
      $type = I('post.type',3,'intval');
      if(!$pay_number || !$password){
        jsonRespons('false','参数错误',$token);  
      }
      $token = I('post.token');
      $token = token_check($token);
      $user = token_user($token);
      //有可能是支付号或订单号
      if(strlen($pay_number) > 12){
        $order_list = M('Order')
               ->where(array('pay_number'=>$pay_number))->select();
      }else{
        $order_list = M('Order')
               ->where(array('id'=>$pay_number))->select();
      }
      //订单总金额
      $money = 0;
      foreach($order_list as $k=>$v){
        $money += $v['pay'];
      }
      if($type == 3){
          //余额支付
          $balance = M('SuUser')->where(array('id'=>$user['user_id'],'status'=>1,'invalid'=>0))->field('money,pay_pass')->find();
          if($balance['pay_pass'] != md5($password)){
            jsonRespons('false','密码错误',$token);  
          }
          if($balance['money'] < $money){
            jsonRespons('false','余额不足',$token);  
          }
      }else{
          if(!$card_number){
            jsonRespons('false','请选择E卡',$token);  
          }
          //查询E卡余额
          $e_card = M('SuECard')->where(array('card_number'=>$card_number,'status'=>1,'invalid'=>0))->find();
          if($e_card['pwd'] != md5($password)){
            jsonRespons('false','密码错误',$token);  
          }
          if($e_card['money'] < $money){
            jsonRespons('false','卡内余额不足',$token);  
          }
      }
      
          $jd_token = M('JdToken')->getfield('token');
          $bottler = M('SuBottler');
          $order = M('Order');
          $ConsumeModel = M('SuConsume');
          if($order_list[0]['otype'] == 1){
            foreach ($order_list as $key => $value) {
              if($value['status'] == 1){
                  $bottlerId = $bottler->add(array(
                      'order_id' => $value['id'],
                      'pay_number' => $value['pay_number'],
                      'other_number' => '',
                      'user_id' => $value['user_id'],
                      'type' => $type,
                      'create_date_time' => date('Y-m-d H:i:s'),
                      'update_time' => date('Y-m-d H:i:s'),
                      'status' => 1,
                      'invalid' => 0
                  ));
                  //如果是虚拟产品加油卡
                  if($value['xuni_type'] == 1 && $value['jiayouka']){
                    jiayouka_recharge($value['user_id'],$value['id'],$value['order_number'],100018,$value['jiayouka']);
                    $order->where(array('id'=>$value['id']))->setField(array('status'=>3,'pay_time'=>date('Y-m-d H:i:s'),'express_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),'pay_money'=>$money,'pay_type'=>$type));
                  }elseif($value['is_jd'] == 1){
                    //如果是京东订单
                    //确认订单
                    $order->where(array('id'=>$value['id']))->setField(array('status'=>3,'pay_time'=>date('Y-m-d H:i:s'),'express_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),'pay_money'=>$money,'pay_type'=>$type));
                    // $data['token'] = $jd_token;
                    // $data['jdOrderId'] = $value['jd_number'];
                    // $return = curl('https://bizapi.jd.com/api/order/confirmOrder',$data,'','json');
                    M('JdConfirmLog')->add(array(
                        'order_id'=>$value['id'],
                        'state'=>$return['resultMessage'],
                        'create_date_time'=>date('Y-m-d H:i:s'),
                      ));
                    M('OrderExpressMsg')->add(array(
                        'order_id'=>$value['id'],
                        'express_number'=>$value['jd_number'],
                        'express_memo'=>'京东配送',
                        'create_date_time'=>date('Y-m-d H:i:s'),
                        'update_time'=>date('Y-m-d H:i:s'),
                        'status'=>1,
                        'invalid'=>0,
                      ));
                  }else{
                    $order->where(array('id'=>$value['id']))->setField(array('status'=>2,'pay_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),'pay_money'=>$money,'pay_type'=>$type));
                  }
                  //给商家发送短信提醒
                  $remind_mobile = M('SuBusiness')->where(array('id'=>$value['seller_business_id']))->getfield('remind_mobile');
                  if($remind_mobile){
                    // $other_name = M('SuBusinessDetail')->where(array('business_id'=>$value['seller_business_id']))->getfield('other_name');
                    // dayu_Sms2('name',$other_name,'number',$value['order_number'],$remind_mobile,'SMS_45405004');
                  }
                  if($type == 3){
                    M('SuUser')->where(array('id'=>$user['user_id'],'status'=>1,'invalid'=>0))->setDec('money',$value['pay']);
                    //添加日志
                    $ulog_add['user_id'] = $user['user_id'];
                    $ulog_add['money'] = $value['pay'];
                    $ulog_add['c_type'] = 4;
                    $ulog_add['create_date_time'] = date('Y-m-d H:i:s');
                    $ulog_add['update_time'] = date('Y-m-d H:i:s');
                    $ulog_add['status'] = 1;
                    $ulog_add['invalid'] = 0;
                    $return3 = M('SuUserMoneyLog')->add($ulog_add);
                  }else{
                    //减掉卡内余额
                    M('SuECard')->where(array('card_number'=>$card_number,'status'=>1,'invalid'=>0))->setDec('money',$value['pay']);
                    $order->where(array('id'=>$value['id']))->setField(array('card_id'=>$e_card['id']));
                    //添加日志
                    $name = '';
                    if($e_card['user_id'] != $value['user_id']){
                      $name = M('SuUser')->where(array('id'=>$value['user_id']))->getfield('realname');
                    }
                    $elog_add['card_id'] = $e_card['id'];
                    $elog_add['money'] = $value['pay'];
                    $elog_add['c_type'] = $name?7:4;
                    $elog_add['other_name'] = $name;
                    $elog_add['create_date_time'] = date('Y-m-d H:i:s');
                    $elog_add['update_time'] = date('Y-m-d H:i:s');
                    $elog_add['status'] = 1;
                    $elog_add['invalid'] = 0;
                    M('SuECardLog')->add($elog_add);
                  }
              }
            }
            jsonRespons('true','',$token);
          }elseif($order_list[0]['otype'] == 2){
            //零售订单
            $BusinessModel =M('SuBusiness');
            $ConsumeModel = M('SuConsume');
            $CashModel = M('SuBusinessCash');
            
            $series = $BusinessModel->where(array('id'=>$order_list[0]['seller_business_id']))->getfield('series');
            $huokuan = round($order_list[0]['pay'] - $series*$order_list[0]['pay']*0.01,2);
            if(($order_list[0]['pay'] - $series*$order_list[0]['pay']*0.01) < 0.01){

            }else{
              $bus = $BusinessModel->where(array('id'=>$order_list[0]['seller_business_id']))->setInc('money',$huokuan);
              $cas = $CashModel->add(array(
                'business_id'=>$order_list[0]['seller_business_id'],
                'btype'=>2,
                'order_id'=>$order_list[0]['id'],
                'money'=>$huokuan,
                'create_date_time'=>date('Y-m-d H:i:s'),
                'update_time'=>date('Y-m-d H:i:s'),
                'status'=>1,
                'invalid'=>0
              ));
            }
            if($series == 6){
              $nl = round($order_list[0]['pay']*0.25,2);
            }elseif($series == 12){
              $nl = round($order_list[0]['pay']*0.5,2);
            }elseif($series == 24){
              $nl = $order_list[0]['pay'];
            }
            $con = $ConsumeModel->add(array(
                        'user_id'=>$order_list[0]['user_id'],
                        'business_id'=>$order_list[0]['seller_business_id'],
                        'order_id'=>$order_list[0]['id'],
                        'money'=>$order_list[0]['pay'],
                        'series'=>$series,
                        'nl'=>$nl,
                        'create_date_time'=>date('Y-m-d H:i:s'),
                        'update_time'=>date('Y-m-d H:i:s'),
                        'status'=>1,
                        'invalid'=>0
                    ));
            
            $bottlerId = $bottler->add(array(
              'order_id' => $order_list[0]['id'],
              'pay_number' => $order_list[0]['pay_number'],
              'other_number' => '',
              'user_id' => $order_list[0]['user_id'],
              'type' => $type,
              'create_date_time' => date('Y-m-d H:i:s'),
              'update_time' => date('Y-m-d H:i:s'),
              'status' => 1,
              'invalid' => 0
              ));
            $order->where(array('id'=>$order_list[0]['id']))->setField(array('status'=>4,'pay_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),'pay_money'=>$money,'pay_type'=>$type));
            $reg_id = M('SuBusiness')->where(array('id'=>$order_list[0]['seller_business_id']))->getfield('reg_id');
            if($reg_id){
              $name = M('SuUser')->where(array('id'=>$order_list[0]['user_id']))->getfield('realname');
              // $content = $name.'成功支付您'.$order_list[0]['pay'].'元，请注意查收。';
              // vendor('Jpush.jpush');
              // $jpush = new \Jpush_send();
              // $receiver = array(
              //   'registration_id'=>array($reg_id)
              //   );
              // $jpush->send_pub($receiver,$content,'1');
            }
          }
          if($type == 3){
            M('SuUser')->where(array('id'=>$user['user_id'],'status'=>1,'invalid'=>0))->setDec('money',$money);
                    //添加日志
            $ulog_add['user_id'] = $user['user_id'];
            $ulog_add['money'] = '-'.$money;
            $ulog_add['c_type'] = 4;
            $ulog_add['create_date_time'] = date('Y-m-d H:i:s');
            $ulog_add['update_time'] = date('Y-m-d H:i:s');
            $ulog_add['status'] = 1;
            $ulog_add['invalid'] = 0;
            $return3 = M('SuUserMoneyLog')->add($ulog_add);
          }else{
            //减掉卡内余额
            M('SuECard')->where(array('card_number'=>$card_number,'status'=>1,'invalid'=>0))->setDec('money',$money);
            $order->where(array('id'=>$order_list[0]['id']))->setField(array('card_id'=>$e_card['id']));
                    //添加日志
            $name = '';
            if($e_card['user_id'] != $order_list[0]['user_id']){
              $name = M('SuUser')->where(array('id'=>$order_list[0]['user_id']))->getfield('realname');
            }
            $elog_add['card_id'] = $e_card['id'];
            $elog_add['money'] = '-'.$money;
            $elog_add['c_type'] = $name?7:4;
            $elog_add['other_name'] = $name;
            $elog_add['create_date_time'] = date('Y-m-d H:i:s');
            $elog_add['update_time'] = date('Y-m-d H:i:s');
            $elog_add['status'] = 1;
            $elog_add['invalid'] = 0;
            M('SuECardLog')->add($elog_add);
          }
          jsonRespons('true','',$token);
    }
}