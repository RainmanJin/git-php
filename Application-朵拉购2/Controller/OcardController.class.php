<?php
namespace Test\Controller;
use Think\Controller;
class OcardController extends Controller {
    public function index(){
    	$refund = jiayouka_recharge('201702214565593',100018,1000113300016869508);
    	dump($refund);
    }
    /*
    *
	*加油卡充值回调
	* 
    */
    function refund() {
       $getParams = json_decode(file_get_contents("php://input"), true);
       if($getParams['status'] == 1 || $getParams['status'] ==2){
       	 M('JiayoukaLog')->where(array('order_number'=>$getParams['orderid']))->setField(array('state'=>$getParams['status'],'update_time'=>date('Y-m-d H:i:s')));
       }
       
    }
}