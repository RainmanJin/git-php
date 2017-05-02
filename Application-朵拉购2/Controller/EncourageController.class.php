<?php
/*
 * 激励控制器
 * @date: 2016-12-1上午10:55:01
 * @editor: YU
 */
namespace Tseller\Controller;
use Think\Controller;
class EncourageController extends Controller {
    public $token;
    public function __construct(){
      if(!I('post.token')){
        jsonRespons('false','请传入token');
      }
      $this->token = token_check(I('post.token'));
    }
    /*
     * 激励信息
     * @date: 2016-12-6上午10:55:01
     * @editor: YU
     */
    public function index(){
      $user = token_user($this->token);
      //今日交易额
      $today_money = M('SuConsume')->where("business_id = ".$user['user_id']." and invalid = 0 and DATE_FORMAT(create_date_time,'%Y-%m-%d') = '".date('Y-m-d')."'")->sum('money');
      $data['today_money'] = $today_money?:0;
      //今日激励的种子数
      $today = M('SuStimulateLog')->where("business_id = ".$user['user_id']." and invalid = 0 and DATE_FORMAT(create_date_time,'%Y-%m-%d') = '".date('Y-m-d')."'")->field('seed_count')->find();
      $data['today'] = $today['seed_count']?:0;
      //种子数量、可提现金额
      $info = M('SuBusiness')->where(array('id'=>$user['user_id']))->field('balance,money,series')->find();
      $data['balance'] = $info['balance']?:0;
      $data['money'] = $info['money']?:0;
      $data['series'] = $info['series'];
      //幸运日值
      $xingyunzhi = M('SuLuck')->where("luck_type = 2 and invalid = 0  and  DATE_FORMAT(create_date_time,'%Y-%m-%d') = '".date('Y-m-d')."'")->getfield('luck_count');
      $data['xingyun'] = $xingyunzhi?:0;
      //幸运树
      $tree = M('SuBusinessTree')->where(array('business_id'=>$user['user_id'],'intval'=>0))->sum('tree_count');
      $data['tree'] = $tree?:0;
      //我的种子数
      $zhongzi = M('SuSumSeed')->where(array('business_id'=>$user['user_id']))->getfield('seed_count');
      $data['zhongzi'] = $zhongzi?:0;
      //是否有未读消息
      $is = M('SysNotice')->where(array('business_id'=>$user['user_id'],'intval'=>0,'status'=>0))->count();
        if($is){
           $data['read'] = '1';
        }else{
           $data['read'] = '0';
        }
      jsonRespons('true','',$this->token,$data);
    }
    /*
     * 提现信息
     * @date: 2016-12-6上午10:55:01
     * @editor: YU
     */
    public function cash_data(){
      $user = token_user($this->token);
      $info = M('SuBusiness')->where(array('id'=>$user['user_id']))->field('bank_card_number,pay_pass,bank_name,money')->find();
      $data['money'] = round($info['money'],2)?:0;
      $data['pay_pass'] = $info['pay_pass']?1:0;
      unset($info['money'],$info['pay_pass']);
      $data['yinhang'] = $info;
      jsonRespons('true','',$this->token,$data);
    }
    /*
	   * 提现列表
	   * @date: 2016-12-1上午10:55:01
	   * @editor: YU
	   */
    public function cash_list(){
      $page = I('page',0,'intval');
      $type = I('type',1,'intval');
      $user = token_user($this->token);
      $where['business_id'] = array('eq',$user['user_id']);
      $where['btype'] = array('eq',1);
      switch ($type) {
        case '1':
          $where['status'] = array('eq',1);
          break;
        case '2':
          $where['status'] = array('in','5,6,7');
          break;
        case '3':
          $where['status'] = array('eq',2);
          break;
        case '4':
          $where['status'] = array('in','3,4');
          break;
        default:
          # code...
          break;
      }
      $where['intval'] = array('eq',0);
      $list = M('SuBusinessCash')->where($where)->field('cash_number,money,memo,cw_memo,buy3_error,create_date_time')->order('id desc')->limit($page*10,10)->select();
      foreach ($list as $key => $value) {
        $list[$key]['memo'] = $value['memo']?:$value['buy3_error'];
        unset($list[$key]['buy3_error']);
        $list[$key]['create_date_time'] = date('Y-m-d H:i',strtotime($value['create_date_time']));
      }
      jsonRespons('true','',$this->token,$list);
    }
    /*
     * 申请提现
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function apply_cash(){
      if(I('post.money') && I('post.pay_pass')){
        $user = token_user($this->token);
        //验证是否存在银行卡
        $info = M('SuBusiness')->where(array('id'=>$user['user_id']))->field('bank_card_number,bank_name,pay_pass,money,status')->find();
        //验证商家状态是否蒸菜
        if($info['status'] != 1){
        	jsonRespons('false','账号状态异常',$this->token);
        }
        if(!$info['bank_card_number'] || !$info['bank_name']){
          jsonRespons('false','不存在银行卡信息',$this->token);
        }
        //验证支付密码是否正确
        if($info['pay_pass'] != md5(I('post.pay_pass'))){
          jsonRespons('false','支付密码错误',$this->token);
        }
        
        //验证提现金额是否多余2位小数
        $is = I('post.money')*100;
        if(!(floor($is)==$is)){
          jsonRespons('false','提现金额最多两位小数',$this->token);
        }
        $CashModel = M('SuBusinessCash');
        $BusinessModel = M('SuBusiness');
        //查询可回提现金额
        if($info['money'] < I('post.money')){
           jsonRespons('false','申请金额大于可提现金额',$this->token);
         }
        $cash['business_id'] = $user['user_id'];
        $cash['btype'] = 1;
        $cash['cash_number'] = date("Y").substr(time(),4).rand(10000,99999);
        $cash['money'] = I('post.money');
        $cash['create_date_time'] = date('Y-m-d H:i:s');
        $cash['update_time'] = date('Y-m-d H:i:s');
        $cash['status'] = 2;
        $cash['invalid'] = 0;
        $cash_id = $CashModel->add($cash);
        if($cash_id){
            $up_user = $BusinessModel->where(array('id'=>$user['user_id']))->setDec('money',I('post.money'));
            if($up_user){
              jsonRespons('true','',$this->token);
            }else{
            	jsonRespons('false','修改数据失败',$this->token);
            }
        }else{
          jsonRespons('false','添加记录失败',$this->token);
        }
      }else{
        jsonRespons('false','参数错误',$this->token);
      }
      
    }
    /*
     * 回购信息
     * @date: 2016-12-6上午10:55:01
     * @editor: YU
     */
    public function back(){
      $user = token_user($this->token);
      $info = M('SuBusiness')->where(array('id'=>$user['user_id']))->field('bank_card_number,pay_pass,bank_name,balance')->find();
      $data['putong'] = $info['balance']?:0;
      $data['pay_pass'] = $info['pay_pass']?1:0;
      unset($info['balance'],$info['pay_pass']);
      $data['yinhang'] = $info;
      jsonRespons('true','',$this->token,$data);
    }
    /*
	   * 回购列表
	   * @date: 2016-12-1上午10:55:01
	   * @editor: YU
	   */
    public function back_list(){
      $page = I('page',0,'intval');
      $type = I('type',2,'intval');
      $user = token_user($this->token);
      $where['business_id'] = array('eq',$user['user_id']);
      switch ($type) {
        case '1':
          $where['status'] = array('eq',1);
          break;
        case '2':
          $where['status'] = array('eq',2);
          break;
        case '3':
          $where['status'] = array('in','3,6,7');
          break;
        case '4':
          $where['status'] = array('in','4,5');
          break;
        default:
          # code...
          break;
      }
      $where['intval'] = array('eq',0);
      $list = M('SuBuyBack')->where($where)->field('buyback_number,seed_count,seed_id,buy_error,buy2_error,buy3_error,create_date_time')->order('id desc')->limit($page*10,10)->select();
      foreach ($list as $key => $value) {
        $list[$key]['seed_id'] = '普通种子';
        if($value['buy2_error']){
          $list[$key]['buy_error'] = $value['buy2_error'];
        }elseif($value['buy3_error']){
          $list[$key]['buy_error'] = $value['buy3_error'];
        }
        unset($list[$key]['buy2_error'],$list[$key]['buy3_error']);
        $list[$key]['create_date_time'] = date('m-d H:i',strtotime($value['create_date_time']));
      }
      jsonRespons('true','',$this->token,$list);
    }
    /*
     * 申请回购
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function apply_back(){
      if(I('post.number') && I('post.pay_pass')){
        $user = token_user($this->token);
        //验证是否存在银行卡
        $info = M('SuBusiness')->where(array('id'=>$user['user_id']))->field('bank_card_number,bank_name,pay_pass,balance')->find();
        if(!$info['bank_card_number'] || !$info['bank_name']){
          jsonRespons('false','不存在银行卡信息',$this->token);
        }
        //验证支付密码是否正确
        if($info['pay_pass'] != md5(I('post.pay_pass'))){
          jsonRespons('false','支付密码错误',$this->token);
        }
        
        //验证回购数量是否是100的整数
        $is = I('post.number')/100;
        if(!is_int($is)){
          jsonRespons('false','回购种子数量不是100的整数',$this->token);
        }
        $BuyBackModel = M('SuBuyBack');
        $StimulateModel = M('SuBusinessStimulate');
        $BusinessModel = M('SuBusiness');
        if($info['balance'] < I('post.number')){
          jsonRespons('false','回购种子数量大于可回购数量',$this->token);
        }
        $BuyBackModel->startTrans();
        $fee = 5;
        $buyback['business_id'] = $user['user_id'];
        $buyback['buyback_number'] = date('Ymd').rand(100000,999999);
        $buyback['seed_count'] = I('post.number');
        $buyback['seed_id'] = 1;
        $buyback['tax'] = 0;
        $buyback['fee'] = $fee;
        $buyback['actual_seed_count'] = I('post.number')-$fee;
        $buyback['create_date_time'] = date('Y-m-d H:i:s');
        $buyback['update_time'] = date('Y-m-d H:i:s');
        $buyback['status'] = 1;
        $buyback['invalid'] = 0;
        $buyback_id = $BuyBackModel->add($buyback);
        if($buyback_id){
          $stimulate['business_id'] = $user['user_id'];
          $stimulate['fee'] = $fee;
          $stimulate['seed_count'] = '-'.I('post.number');
          $stimulate['seed_id'] = 4;
          $stimulate['sy_count'] = 0;//$seed_count-I('post.number');
          $stimulate['create_date_time'] = date('Y-m-d H:i:s');
          $stimulate['update_time'] = date('Y-m-d H:i:s');
          $stimulate['status'] = 1;
          $stimulate['invalid'] = 0;
          $us_id = $StimulateModel->add($stimulate);
          if($us_id){
            $up_user = $BusinessModel->where(array('id'=>$user['user_id']))->setDec('balance',I('post.number'));
            if($up_user){
              $BuyBackModel->commit();
              $StimulateModel->commit();
              $BusinessModel->commit();
              jsonRespons('true','',$this->token);
            }else{
              $BuyBackModel->rollback();
              $StimulateModel->rollback();
              $BusinessModel->rollback();
              jsonRespons('false','添加数据失败',$this->token);
            }
          }else{
            $BuyBackModel->rollback();
            $StimulateModel->rollback();
            jsonRespons('false','添加数据失败',$this->token);
          }
        }else{
          $BuyBackModel->rollback();
          jsonRespons('false','添加数据失败',$this->token);
        }
      }else{
        jsonRespons('false','参数错误',$this->token);
      }
      
    }
    
     /*
     * 添加银行卡
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function add_bank_card(){
        if(I('post.name') && I('post.cardid') && I('post.bank_card_number') && I('post.bank_open_name') && I('post.bank_name') && I('post.mobile') && I('post.code')){
          $user = token_user($this->token);
          $info = M('SuBusiness')->where(array('id'=>$user['user_id']))->find();
          //验证手机号码是否是登录号码
          if($info['mobile'] != I('post.mobile')){
            jsonRespons('false','请绑定手机号码为登录账号的银行卡',$this->token);
          }
          //验证验证码是否有效
          $up_time = M('SuSmsLog')->where(array('mobile'=>I('post.mobile'),'code'=>I('post.code')))->getfield('update_time');
          if(!$up_time){
            jsonRespons('false','验证码错误',$this->token);
          }
          if(strtotime($up_time)+300 < time()){
            jsonRespons('false','验证码已超时',$this->token);
          }
          
          
          //验证是否已填写银行卡信息
          if($info['bank_card_number'] || $info['bank_name']){
            jsonRespons('false','已添加银行卡',$this->token);
          }
          //验证银行卡和姓名是否匹配
          $host = "http://jisubank4.market.alicloudapi.com";
          $path = "/bankcardverify4/verify";
          $method = "GET";
          $appcode = "b4a8945154b8435bb0d6d9828e741a51";
          $headers = array();
          array_push($headers, "Authorization:APPCODE " . $appcode);
          $querys = "bankcard=".I('post.bank_card_number')."&idcard=".I('post.cardid')."&mobile=".I('post.mobile')."&realname=".I('post.name');
          $bodys = "";
          $url = $host . $path . "?" . $querys;

          $curl = curl_init();
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
          curl_setopt($curl, CURLOPT_URL, $url);
          curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
          curl_setopt($curl, CURLOPT_FAILONERROR, false);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl, CURLOPT_HEADER, false);
          if (1 == strpos("$".$host, "https://"))
          {
              curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
              curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
          }
          $yanzheng = json_decode((curl_exec($curl)),true);
          if($yanzheng['result']['verifystatus'] == 0){
            //,'bank_phone'=>I('post.mobile')
            $return = M('SuBusiness')->where(array('id'=>$user['user_id']))->setfield(array('cardid'=>I('post.cardid'),'account_name'=>I('post.name'),'bank_card_number'=>I('post.bank_card_number'),'bank_open_name'=>I('post.bank_open_name'),'bank_name'=>I('post.bank_name')));
            if($return !== false){
              jsonRespons('true','',$this->token);
            }else{
              jsonRespons('false','添加失败',$this->token);
            }
          }else{
            jsonRespons('false','银行卡信息不匹配',$this->token);
          }
          
        }else{
          jsonRespons('false','参数错误',$this->token);
        }
    }
    /*
     * 我的种子
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function my_seed(){
      $page = I('post.page',0,'intval');
      $type = I('post.type',0,'intval');
      $user = token_user($this->token);
      $where['business_id'] = array('eq',$user['user_id']);
      if($type){
        $where['seed_id'] = array('eq',$type);
      }
      $where['status'] = array('eq',1);
      $where['invalid'] = array('eq',0);
      $list = M('SuBusinessStimulate')->where($where)->field('seed_count,seed_id,create_date_time')->limit($page*10,10)->order('id desc')->select();
      foreach ($list as $key => $value) {
        $list[$key]['create_date_time'] = date('Y-m-d',strtotime($value['create_date_time']));
      }
      //累计获取总数
      $zhongzi = M('SuSumSeed')->where(array('business_id'=>$user['user_id']))->getfield('seed_count');
      $data['count'] = $zhongzi?:0;
      $data['list'] = $list;
      jsonRespons('true','',$this->token,$data);
    }
    /*
     * 我的幸运树
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function my_tree(){
      $page = I('post.page',0,'intval');
      $user = token_user($this->token);
      $where['business_id'] = array('eq',$user['user_id']);
      $where['btype'] = array('eq',1);
      $where['invalid'] = array('eq',0);
      $list = M('SuBusinessTree')->where($where)->field("tree_count,DATE_FORMAT(create_date_time,'%Y-%m-%d')  as create_date_time")->limit($page*10,10)->order('id desc')->select();
      $count = M('SuBusinessTree')->where($where)->sum('tree_count');
      $jiashu = M('SuBusinessAddTree')->where(array('business_id'=>$user['user_id'],'invalid'=>0))->getfield('total_count');
      $jianshu = M('SuBusinessSubTree')->where(array('business_id'=>$user['user_id'],'invalid'=>0))->getfield('total_count');
      $data['count'] = $count?:0;
      $data['jiashu'] = $jiashu?:0;
      $data['jianshu'] = $jianshu?:0;
      $data['list'] = $list;
      jsonRespons('true','',$this->token,$data);
    }
    /*
     * 交易明细
     * @date: 2016-12-1上午10:55:01
     * @editor: YU
     */
    public function deal(){
      $page = I('post.page',0,'intval');
      $user = token_user($this->token);
      $where = 'business_id = '.$user['user_id'].' and status = 1 and invalid = 0';
      if(I('post.search')){
        if(I('post.search') == 1){
          $where = $where." and DATE_FORMAT(create_date_time,'%Y-%m-%d') = '".date('Y-m-d')."'";
        }elseif(I('post.search') == 2){
          $where = $where." and DATE_FORMAT(create_date_time,'%Y-%m-%d') >= '".date('Y-m-d',strtotime('-7 day'))."'";
        }elseif(I('post.search') == 3){
          $where = $where." and DATE_FORMAT(create_date_time,'%Y-%m-%d') >= '".date('Y-m-d',strtotime('-1 month'))."'";
        }elseif(I('post.search') == 4){
          $where = $where." and DATE_FORMAT(create_date_time,'%Y-%m-%d') >= '".date('Y-m-d',strtotime('-3 month'))."'";
        }elseif(I('post.search') == 5){
          $where = $where." and DATE_FORMAT(create_date_time,'%Y-%m-%d') >= '".date('Y-m-d',strtotime('-6 month'))."'";
        }
        
      }
      $list = M('SuConsume')->where($where)->field("user_id as user,order_id,money,create_date_time")->limit($page*10,10)->order('id desc')->select();
      $order = M('Order');
      $user = M('SuUser');
      foreach($list as $key=>$value){
        $list[$key]['order_id'] = $order->where(array('id'=>$value['order_id']))->getfield('order_number');
        $list[$key]['user'] = $user->where(array('id'=>$value['user']))->getfield('realname');
      }
      jsonRespons('true','',$this->token,$list);
    }
}