<?php
namespace Test\Controller;
use Think\Controller;
class NorderController extends Controller {
    public $token;
    public function __construct(){
      if(!I('post.token')){
        jsonRespons('false','请传入token');
      }
      $this->token = token_check(I('post.token'));
    }
    /*
     * 订单列表
     * @date: 2016-12-1 下午4:55:01
     * @editor: YU
     */
    public function order_list(){
        if(!I('post.type')){
            jsonRespons('false','参数错误',$this->token);
        }
        $page = I('post.page',0,'intval');
        $type = I('post.type');
        $user = token_user($this->token);
        //获取每一种订单状态的数量
        $data['count1'] = M('Order')->where(array('user_id'=>$user['user_id'],'status'=>1,'otype'=>1,'invalid'=>0))->count();
        $data['count2'] = M('Order')->where(array('user_id'=>$user['user_id'],'status'=>2,'otype'=>1,'invalid'=>0))->count();
        $data['count3'] = M('Order')->where(array('user_id'=>$user['user_id'],'status'=>3,'otype'=>1,'invalid'=>0))->count();
        $c_where['user_id'] = array('eq',$user['user_id']);
        $c_where['status'] = array('in','-1,6,7,13,14,15,16,17,18,19');
        $c_where['otype'] = array('eq',1);
        $c_where['invalid'] = array('eq',0);
        $data['count4'] = M('Order')->where($c_where)->count();
        
        $where['user_id'] = array('eq',$user['user_id']);
        if($type != 'all'){
            if($type == 7){
                $where['status'] = array('in','-1,5,6,7,13,14,15,16,17,18,19');
            }else{
                $where['status'] = array('eq',$type);
            }
        }
        $where['otype'] = array('eq',1);
        $where['invalid'] = array('eq',0);
        $list = M('Order')->where($where)->field('id,order_number,pay,pay_number,seller_business_id,status,refund_content,refund_money,express_fee,refund_express_id,refund_type,is_jd,xuni_type')->limit($page*10,10)->order('update_time desc')->select();
        $order_list = M('OrderList');
        $bus = M('SuBusiness');
        $busD = M('SuBusinessDetail');
        $msg = M('OrderExpressMsg');
        foreach ($list as $key => $value) {
        	$count = $msg->where(array('order_id'=>$value['id']))->count();
            if((in_array($value['status'],array(14,15,18,19)) && $count) || (in_array($value['status'],array(13,16)) && $value['refund_type'] == 1) ){
                $list[$key]['status'] = 7;
            }elseif(in_array($value['status'],array(14,15,18,19))){
            	$list[$key]['status'] = -1;
            }
            $list[$key]['express'] = $count?'1':'0';
            $list[$key]['series'] = $bus->where(array('id'=>$value['seller_business_id']))->getfield('series');
            $list[$key]['business'] = $busD->where(array('business_id'=>$value['seller_business_id']))->getfield('other_name');
            $list[$key]['list'] = $order_list->where(array('order_id'=>$value['id']))->field('id as order_detail_id,product_id,series,nums,price,title,thumb_url,specification,status')->select();
        }
        $data['list'] = $list;
        jsonRespons('true','',$this->token,$data);
    }
    /*
     * 订单详细
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function order_detail(){
        $id = I('post.id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
        $info = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->field('id,order_number,name,phone,address,seller_business_id,pay,refund_money,refund_type,express_fee,remark,create_date_time,pay_time,success_time,express_time,status,is_jd,xuni_type,jiayouka')->find();
        if(in_array($info['status'],array(14,15,18,19))){
            $info['status'] = 7;
        }elseif(in_array($info['status'],array(13,16)) && $info['refund_type'] == 1){
            $list[$key]['status'] = 7;
        }
        $info['other_name'] = M('SuBusinessDetail')->where(array('business_id'=>$info['seller_business_id']))->getfield('other_name');
        $count = M('OrderExpressMsg')->where(array('order_id'=>$info['id']))->count();
        $create_date_time = M('OrderExpressMsg')->where(array('order_id'=>$info['id']))->getfield('create_date_time');
        $info['express_time'] = $info['express_time']?:$create_date_time;
        $info['series'] = M('SuBusiness')->where(array('id'=>$info['seller_business_id']))->getfield('series');
        $info['list'] = M('OrderList')->where(array('order_id'=>$info['id']))->field('id as order_detail_id,product_id,refund_type,nums,weight,price,refund_express_id,title,thumb_url,specification,status')->select();
        jsonRespons('true','',$this->token,$info);
    }
    /*
     * 订单详细(改)
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function order_detail1(){
        $id = I('post.id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
        $info = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->field('id,order_number,name,phone,address,seller_business_id,pay,refund_money,refund_type,express_fee,remark,create_date_time,pay_time,success_time,express_time,status,is_jd,xuni_type,jiayouka')->find();
        if(in_array($info['status'],array(14,15,18,19))){
            $info['status'] = 7;
        }elseif(in_array($info['status'],array(13,16)) && $info['refund_type'] == 1){
            $list[$key]['status'] = 7;
        }
        $info['other_name'] = M('SuBusinessDetail')->where(array('business_id'=>$info['seller_business_id']))->getfield('other_name');
        $count = M('OrderExpressMsg')->where(array('order_id'=>$info['id']))->count();
        $create_date_time = M('OrderExpressMsg')->where(array('order_id'=>$info['id']))->getfield('create_date_time');
        $info['express_time'] = $info['express_time']?:$create_date_time;
        $info['series'] = M('SuBusiness')->where(array('id'=>$info['seller_business_id']))->getfield('series');
        $info['list'] = M('OrderList')->where(array('order_id'=>$info['id']))->field('id as order_detail_id,product_id,refund_type,nums,series,weight,price,refund_express_id,title,thumb_url,specification,status')->select();
        jsonRespons('true','',$this->token,$info);
    }    
    /*
     * 退款详情
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function refund_detail(){
        $id = I('post.order_detail_id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);  
        }
        $info = M('OrderList')->where(array('id'=>$id))->field('id,order_id,refund_time,refund_content,status,goods_status,refund_money,refund_express_id,refund_shuoming,refund_type,yy_not_memo,tk_memo')->find();
        $info['order_number'] = M('Order')->where(array('id'=>$info['order_id']))->getfield('order_number');
        unset($info['order_id']);
        jsonRespons('true','',$this->token,$info);
    }
    /*
     * 删除订单
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function del_order(){
        $id = I('post.id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);  
        }
        $user = token_user($this->token);
        $is = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->field('status')->find();
        if(!$is){
            jsonRespons('false','不存在此订单',$this->token);
        }
        if(in_array($is['status'],array(0,4,5))){
            $return = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->setfield(array('invalid'=>1));
            if($return){
                jsonRespons('true','',$this->token);
            }else{
                jsonRespons('false','删除失败',$this->token);
            }
        }else{
             jsonRespons('false','订单状态错误',$this->token);
        }
    }
    /*
     * 取消订单
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function cancel_order(){
        $id = I('post.id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);  
        }
        $user = token_user($this->token);
        $is = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->field('express_fee,pay,status')->find();
        if(!$is){
            jsonRespons('false','不存在此订单',$this->token);
        }
        if(in_array($is['status'],array(1,2))){
            if($is['status'] == 2){
                $return = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->setfield(array('status'=>-1,'refund_type'=>1,'refund_time'=>date('Y-m-d H:i:s'),'refund_content'=>'取消订单','refund_money'=>$is['pay'],'update_time'=>date('Y-m-d H:i:s')));
                //将所有订单子表状态也改为申请退款
                $list = M('OrderList')->where(array('order_id'=>$id))->select();
                $OrderList = M('OrderList');
                foreach ($list as $key => $value) {
                    $money = $value['nums']*$value['price'];
                    if(!$list[($key+1)]['id'] && $is['express_fee']){
                        //不存在下一条记录则将运费加入到退款金额中
                        $money = $money+$is['express_fee'];
                    }
                    $money = round($money,2);
                    $OrderList->where(array('id'=>$value['id']))->setField(array('status'=>2,'refund_status'=>-1,'refund_type'=>1,'refund_time'=>date('Y-m-d H:i:s'),'refund_content'=>'取消订单','refund_money'=>$money,'update_time'=>date('Y-m-d H:i:s')));
                }
            }else{
                $return = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->setfield(array('status'=>0,'update_time'=>date('Y-m-d H:i:s')));
            } 
            if($return){
                jsonRespons('true','',$this->token);
            }else{
                jsonRespons('false','删除失败',$this->token);
            }
        }else{
             jsonRespons('false','订单状态错误',$this->token);
        }
    }
    /*
     * 申请退款
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function refund_order(){
        $id = I('post.order_detail_id');
        $type = I('post.type',1,'intval');//1是未收到货退款 2是退货退款
        if(!$id || !I('post.refund_content')){
            jsonRespons('false','参数错误',$this->token);  
        }
        
        $user = token_user($this->token);
        $detail = M('OrderList')->where(array('id'=>$id))->find();
        //判断申请退款的数量是否大于真实数量
        if(I('post.refund_num')){
            if(I('post.refund_num') > $detail['nums']){
                jsonRespons('false','商品数量为'.$detail['nums'].'件',$this->token);
            }

        }
        $is = M('Order')->where(array('id'=>$detail['order_id'],'user_id'=>$user['user_id']))->field('area,express_fee,pay,status,jd_number')->find();
        if(!$is){
            jsonRespons('false','不存在此订单',$this->token);
        }
        //已完成订单不可申请退款
        if($is['status'] == 4){
            jsonRespons('false','已完成订单不可申请退款',$this->token);
        }
        //判断退款金额是否大于支付金额
        // if(I('post.refund_money') > $is['pay']){
        //     jsonRespons('false','',$this->token);
        // }
        //验证退款之后是否不满足运费规则
        $list = M('OrderList')->where(array('order_id'=>$detail['order_id']))->select();
        $weight = 0;
        $money = 0;
        $status = 7;
        foreach ($list as $key => $value) {
            $weight += $value['weight'] * $value['nums'];
            if(in_array($value['status'],array(1,4,7))){
                if($value['id'] != $id){
                    //如果还存在其他产品未退货 则不改变订单状态
                    $money += $value['price'] * $value['nums'];
                    $status = 0;
                }
            }
        }
        if(!$status){
            //还存在其他产品
            if($detail['jd_number']){
                //京东订单 查询是否可以申请退货
                $token = M('JdToken')->getfield('token');
                $data['token'] = $token;
                $data['param'] = json_encode(array('jdOrderId'=>$detail['jd_number'],'skuId'=>$detail['skuid']));
                // dump($data);exit;
                $return = curl('https://bizapi.jd.com/api/afterSale/getAvailableNumberComp',$data,'','json');
                if($return['success'] == false){
                    jsonRespons('false','该商品不支持退换货',$this->token);
                }
            }
            // else{
            //      $yunfei = get_express_fee($weight,$money,$is['area']);
            //     if($yunfei > $is['express_fee']){
            //         jsonRespons('false','运费规则不满足',$this->token);
            //     }
            // }
           
            $refund_money = M('Order')->where(array('id'=>$detail['order_id'],'user_id'=>$user['user_id']))->getfield('refund_money');
            $return = M('Order')->where(array('id'=>$detail['order_id'],'user_id'=>$user['user_id']))->setField(array('refund_money'=>$refund_money+round($detail['nums']*$detail['price'],2)));
        }else{
            //不存在其他产品，整个订单变成申请退款
            if($detail['jd_number']){
                //京东订单 查询是否可以申请退货
                $token = M('JdToken')->getfield('token');
                $data['token'] = $token;
                $data['param'] = json_encode(array('jdOrderId'=>$detail['jd_number'],'skuId'=>$detail['skuid']));
                // dump($data);exit;
                $return = curl('https://bizapi.jd.com/api/afterSale/getAvailableNumberComp',$data,'','json');
                if($return['success'] == false){
                    jsonRespons('false','该商品不支持退换货',$this->token);
                }
                
            }
            $return = M('Order')->where(array('id'=>$detail['order_id'],'user_id'=>$user['user_id']))->setfield(array('status'=>7,'refund_money'=>$is['pay']-$is['express_fee'],'update_time'=>date('Y-m-d H:i:s')));
        }
        if($return){
            if(I('post.refund_num')){
                $detail['nums'] = I('post.refund_num');
            }
             M('OrderList')->where(array('id'=>$id))->setField(array('refund_money'=>round($detail['nums']*$detail['price'],2),'status'=>2,'refund_status'=>-1,'refund_num'=>I('post.refund_num'),'refund_content'=>I('post.refund_content'),'goods_status'=>I('post.goods_status'),'refund_type'=>$type,'refund_shuoming'=>I('post.refund_shuoming'),'refund_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s')));
            jsonRespons('true','',$this->token);
        }else{
            jsonRespons('false','申请失败',$this->token);
        }
        
            
    }
    /*
     * 判断退款之后是否满足运费
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function jisuan_express(){
        $id = I('post.order_detail_id');
        if(!$id ){
            jsonRespons('false','参数错误',$this->token);  
        }
        $user = token_user($this->token);
        $detail = M('OrderList')->where(array('id'=>$id))->find();
        $is = M('Order')->where(array('id'=>$detail['order_id'],'user_id'=>$user['user_id']))->field('area,express_fee,is_jd')->find();
        if(!$is){
            jsonRespons('false','不存在此订单',$this->token);
        }
        //如果是京东订单  不需要验证
        if($is['is_jd'] == 1){
            jsonRespons('true','',$this->token);
        }
        $list = M('OrderList')->where(array('id'=>$id))->select();
        $weight = 0;
        $money = 0;
        foreach ($list as $key => $value) {
            $weight += $value['weight'] * $value['nums'];
            if(in_array($value['status'],array(1,4,7))){
                $money += $value['price'] * $value['nums'];
                if($value['id'] != $id){
                    //如果还存在其他产品未退货 则不改变订单状态
                    $status = 0;
                }
            }
        }
        $yunfei = get_express_fee($weight,$money,$is['area']);
        if($yunfei > $is['express_fee']){
            jsonRespons('false','运费规则不满足',$this->token);
        }else{
            jsonRespons('true','',$this->token);
        }
    }
    /*
     * 填写申请退款信息
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function refund_order_data(){
        $id = I('post.order_detail_id');
        if(!$id || !I('post.refund_express_id') || !I('post.refund_express_number')){
            jsonRespons('false','参数错误',$this->token);  
        }
        
        $user = token_user($this->token);
        $is = M('OrderList')->where(array('id'=>$id))->field('status')->find();
        if(!$is){
            jsonRespons('false','不存在此订单',$this->token);
        }
        if(in_array($is['status'],array(4))){
            $return = M('OrderList')->where(array('id'=>$id))->setfield(array('refund_express_id'=>I('post.refund_express_id'),'refund_yunfei'=>I('post.refund_yunfei'),'refund_express_number'=>I('post.refund_express_number')));
            if($return){
                //待续处理
                jsonRespons('true','',$this->token);
            }else{
                jsonRespons('false','申请失败',$this->token);
            }
        }else{
             jsonRespons('false','订单状态错误',$this->token);
        }
    }
    /*
     * 确认收货
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function recipient(){
        $id = I('post.id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);  
        }
        $user = token_user($this->token);
        $order = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->field('id,pay,seller_business_id as business_id,refund_money,status')->find();
        if(!$order){
            jsonRespons('false','不存在此订单',$this->token);
        }
        if(in_array($order['status'],array(3,5,6,17))){
            $BusinessModel =M('SuBusiness');
            $ConsumeModel = M('SuConsume');
            $CashModel = M('SuBusinessCash');
            $OrderModel = M('Order');
            $BusinessModel->startTrans();
            //增加商家的可提现金额
            $series = $BusinessModel->where(array('id'=>$order['business_id']))->getfield('series');
            $where['order_id'] = array('eq',$id);
            $where['status'] = array('eq',3);
            $refund_money = M('OrderList')->where($where)->sum('refund_money');
            if($refund_money){
                //部分退款 计算的金额为实际支付金额-退款金额
                if($order['pay'] > $refund_money){
                    $order['pay'] = round($order['pay']-$refund_money,2);
                }else{
                    jsonRespons('false','非法操作',$this->token);
                }
            }
            $huokuan = round($order['pay'] - $series*$order['pay']*0.01,2);
            if(($order['pay'] - $series*$order['pay']*0.01) < 0.01){
                $bus = 1;
            }else{
                $bus = $BusinessModel->lock(true)->where(array('id'=>$order['business_id']))->setInc('money',$huokuan);
            }
            if($bus){
                    if($series == 6){
                        $nl = round($order['pay']*0.25,2);
                    }elseif($series == 12){
                        $nl = round($order['pay']*0.5,2);
                    }elseif($series == 24){
                        $nl = $order['pay'];
                    }
                    $con = $ConsumeModel->lock(true)->add(array(
                        'user_id'=>$user['user_id'],
                        'business_id'=>$order['business_id'],
                        'order_id'=>$order['id'],
                        'series'=>$series,
                        'money'=>$order['pay'],
                        'nl'=>$nl,
                        'create_date_time'=>date('Y-m-d H:i:s'),
                        'update_time'=>date('Y-m-d H:i:s'),
                        'status'=>1,
                        'invalid'=>0
                    ));
                
                if($con){
                    if(($order['pay'] - $series*$order['pay']*0.01) < 0.01){
                        $cas = 1;
                    }else{
                    //增加商家货款
                    $cas = $CashModel->lock(true)->add(array(
                        'business_id'=>$order['business_id'],
                        'btype'=>2,
                        'order_id'=>$order['id'],
                        'money'=>$huokuan,
                        'create_date_time'=>date('Y-m-d H:i:s'),
                        'update_time'=>date('Y-m-d H:i:s'),
                        'status'=>1,
                        'invalid'=>0
                        ));
                    }
                    if($cas){
                         $return = $OrderModel->lock(true)->where(array('id'=>$id,'user_id'=>$user['user_id']))->setfield(array('status'=>4,'success_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s')));
                        if($return){
                            $BusinessModel->commit();
                            $ConsumeModel->commit();
                            $CashModel->commit();
                            $OrderModel->commit();
                            jsonRespons('true','',$this->token);
                        }else{
                            $BusinessModel->rollback();
                            $ConsumeModel->rollback();
                            $CashModel->rollback();
                            $OrderModel->rollback();
                            jsonRespons('false','确认失败',$this->token);
                        }
                    }else{
                        $BusinessModel->rollback();
                        $ConsumeModel->rollback();
                        $CashModel->rollback();
                        jsonRespons('false','确认失败',$this->token);
                    }
                   
                }else{
                    $BusinessModel->rollback();
                    $ConsumeModel->rollback();
                    jsonRespons('false','确认失败',$this->token);
                }
                
            }else{
                $BusinessModel->rollback();
                jsonRespons('false','确认失败',$this->token);
            }
            
        }else{
             jsonRespons('false','订单状态错误',$this->token);
        }
    }

    /*
     * 确认收货(改)
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function recipient1(){
        $id = I('post.id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);  
        }
        $series = M('OrderList')->where(array('order_id'=>$id))->field('series,price')->select(); 
        $user = token_user($this->token);
        $order = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->field('id,pay,seller_business_id as business_id,refund_money,status')->find();
        if(!$order){
            jsonRespons('false','不存在此订单',$this->token);
        }
        if($series[0]['series']==null){
            if(in_array($order['status'],array(3,5,6,17))){
                $BusinessModel =M('SuBusiness');
                $ConsumeModel = M('SuConsume');
                $CashModel = M('SuBusinessCash');
                $OrderModel = M('Order');
                $BusinessModel->startTrans();
                //增加商家的可提现金额
                    $series = $BusinessModel->where(array('id'=>$order['business_id']))->getfield('series');
                    $where['order_id'] = array('eq',$id);
                    $where['status'] = array('eq',3);
                    $refund_money = M('OrderList')->where($where)->sum('refund_money');
                    if($refund_money){
                        //部分退款 计算的金额为实际支付金额-退款金额
                        if($order['pay'] > $refund_money){
                            $order['pay'] = round($order['pay']-$refund_money,2);
                        }else{
                            jsonRespons('false','非法操作',$this->token);
                        }
                    }
                    $huokuan = round($order['pay'] - $series*$order['pay']*0.01,2);
                    if(($order['pay'] - $series*$order['pay']*0.01) < 0.01){
                        $bus = 1;
                    }else{
                        $bus = $BusinessModel->lock(true)->where(array('id'=>$order['business_id']))->setInc('money',$huokuan);
                    }
                if($bus){
                        // $nl=0;
                        // foreach($series as $key => $value){
                        //     $nl+=round($value['price']*$value['series']*(1/24),2);
                        // }
                        if($series == 6){
                            $nl = round($order['pay']*0.25,2);
                        }elseif($series == 12){
                            $nl = round($order['pay']*0.5,2);
                        }elseif($series == 24){
                            $nl = $order['pay'];
                        }
                        $con = $ConsumeModel->lock(true)->add(array(
                            'user_id'=>$user['user_id'],
                            'business_id'=>$order['business_id'],
                            'order_id'=>$order['id'],
                            'series'=>$series,
                            'nl'=>$nl,
                            'money'=>$order['pay'],
                            'create_date_time'=>date('Y-m-d H:i:s'),
                            'update_time'=>date('Y-m-d H:i:s'),
                            'status'=>1,
                            'invalid'=>0
                        ));
                    
                    if($con){
                        if(($order['pay'] - $series*$order['pay']*0.01) < 0.01){
                            $cas = 1;
                        }else{
                        //增加商家货款
                        $cas = $CashModel->lock(true)->add(array(
                            'business_id'=>$order['business_id'],
                            'btype'=>2,
                            'order_id'=>$order['id'],
                            'money'=>$huokuan,
                            'create_date_time'=>date('Y-m-d H:i:s'),
                            'update_time'=>date('Y-m-d H:i:s'),
                            'status'=>1,
                            'invalid'=>0
                            ));
                        }
                        if($cas){
                             $return = $OrderModel->lock(true)->where(array('id'=>$id,'user_id'=>$user['user_id']))->setfield(array('status'=>4,'success_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s')));
                            if($return){
                                $BusinessModel->commit();
                                $ConsumeModel->commit();
                                $CashModel->commit();
                                $OrderModel->commit();
                                jsonRespons('true','',$this->token);
                            }else{
                                $BusinessModel->rollback();
                                $ConsumeModel->rollback();
                                $CashModel->rollback();
                                $OrderModel->rollback();
                                jsonRespons('false','确认失败',$this->token);
                            }
                        }else{
                            $BusinessModel->rollback();
                            $ConsumeModel->rollback();
                            $CashModel->rollback();
                            jsonRespons('false','确认失败',$this->token);
                        }
                       
                    }else{
                        $BusinessModel->rollback();
                        $ConsumeModel->rollback();
                        jsonRespons('false','确认失败',$this->token);
                    }
                    
                }else{
                    $BusinessModel->rollback();
                    jsonRespons('false','确认失败',$this->token);
                }
                
            }else{
                 jsonRespons('false','订单状态错误',$this->token);
            }
        }else{
            if(in_array($order['status'],array(3,5,6,17))){
                $BusinessModel =M('SuBusiness');
                $ConsumeModel = M('SuConsume');
                $CashModel = M('SuBusinessCash');
                $OrderModel = M('Order');
                $BusinessModel->startTrans();
                //增加商家的可提现金额
                    $series1 = $BusinessModel->where(array('id'=>$order['business_id']))->getfield('series');
                    $where['order_id'] = array('eq',$id);
                    $where['status'] = array('eq',3);
                    $refund_money = M('OrderList')->where($where)->sum('refund_money');
                    if($refund_money){
                        //部分退款 计算的金额为实际支付金额-退款金额
                        if($order['pay'] > $refund_money){
                            $order['pay'] = round($order['pay']-$refund_money,2);
                        }else{
                            jsonRespons('false','非法操作',$this->token);
                        }
                    }
                    $series = M('OrderList')->where(array('order_id'=>$id,'status'=>array('neq',3)))->field('series,price')->select();
                    $money=0;
                    foreach ($series as $key => $value) {
                        $money+=$value['price']*$value['series']*0.01;
                    }
                    $huokuan = round($order['pay'] - $money,2); 
                    
                    if(($order['pay'] - $money) < 0.01){
                        $bus = 1;
                    }else{
                        $bus = $BusinessModel->lock(true)->where(array('id'=>$order['business_id']))->setInc('money',$huokuan);
                    }
                if($bus){
                        $nl=0;
                        foreach($series as $key => $value){
                            $nl+=round($value['price']*$value['series']*(1/24),2);
                        }
                        // if($series == 6){
                        //     $nl = round($order['pay']*0.25,2);
                        // }elseif($series == 12){
                        //     $nl = round($order['pay']*0.5,2);
                        // }elseif($series == 24){
                        //     $nl = $order['pay'];
                        // }
                        $con = $ConsumeModel->lock(true)->add(array(
                            'user_id'=>$user['user_id'],
                            'business_id'=>$order['business_id'],
                            'order_id'=>$order['id'],
                            'series'=>$series1,
                            'nl'=>$nl,
                            'money'=>$order['pay'],
                            'create_date_time'=>date('Y-m-d H:i:s'),
                            'update_time'=>date('Y-m-d H:i:s'),
                            'status'=>1,
                            'invalid'=>0
                        ));
                    
                    if($con){
                        if(($order['pay'] - $money) < 0.01){
                            $cas = 1;
                        }else{
                        //增加商家货款
                        $cas = $CashModel->lock(true)->add(array(
                            'business_id'=>$order['business_id'],
                            'btype'=>2,
                            'order_id'=>$order['id'],
                            'money'=>$huokuan,
                            'create_date_time'=>date('Y-m-d H:i:s'),
                            'update_time'=>date('Y-m-d H:i:s'),
                            'status'=>1,
                            'invalid'=>0
                            ));
                        }
                        if($cas){
                             $return = $OrderModel->lock(true)->where(array('id'=>$id,'user_id'=>$user['user_id']))->setfield(array('status'=>4,'success_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s')));
                            if($return){
                                $BusinessModel->commit();
                                $ConsumeModel->commit();
                                $CashModel->commit();
                                $OrderModel->commit();
                                jsonRespons('true','',$this->token);
                            }else{
                                $BusinessModel->rollback();
                                $ConsumeModel->rollback();
                                $CashModel->rollback();
                                $OrderModel->rollback();
                                jsonRespons('false','确认失败',$this->token);
                            }
                        }else{
                            $BusinessModel->rollback();
                            $ConsumeModel->rollback();
                            $CashModel->rollback();
                            jsonRespons('false','确认失败',$this->token);
                        }
                       
                    }else{
                        $BusinessModel->rollback();
                        $ConsumeModel->rollback();
                        jsonRespons('false','确认失败',$this->token);
                    }
                    
                }else{
                    $BusinessModel->rollback();
                    jsonRespons('false','确认失败',$this->token);
                }
                
            }else{
                 jsonRespons('false','订单状态错误',$this->token);
            } 
        }
    }   
    /*
     * 新退款快递信息
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function refund_express(){
        if(!I('order_detail_id')){
            jsonRespons('false','参数错误',$this->token);
        }
        $number = M('OrderList')->where(array('id'=>I('order_detail_id')))->field('refund_express_id,refund_express_number')->find();
        if(!$number){
            jsonRespons('false','暂无物流信息',$this->token);
        }
        $express = M('OrderExpress')->where(array('id'=>$number['refund_express_id']))->field('name,number')->find();
        $host = "http://ali-deliver.showapi.com";
        $path = "/showapi_expInfo";
        $method = "GET";
        $appcode = "989f0f3ec92c452b9fc0367e8f5139ee";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "com=".$express['number']."&nu=".$number['refund_express_number'];
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
        $data = json_decode((curl_exec($curl)),true);
        $return['mailNo'] = $number['refund_express_number'];
        $return['expTextName'] = $express['name'];
        switch ($data['showapi_res_body']['status']) {
            case '-1':
                $status = '待查询';
                break;
            case '0':
                $status = '查询异常';
                break;
            case '1':
                $status = '暂无记录';
                break;
            case '2':
                $status = '在途中';
                break;
            case '3':
                $status = '派送中';
                break;
            case '4':
                $status = '已签收';
                break;
            case '5':
                $status = '用户拒签';
                break;
            case '6':
                $status = '疑难件';
                break;
            case '7':
                $status = '无效单';
                break;
            case '8':
                $status = '超时单';
                break;
            case '9':
                $status = '签收失败';
                break;
            case '10':
                $status = '退回';
                break;
            default:
                $status = '暂无状态';
                break;
        }
        $return['status'] = $status;
        $order = array("\r\n", "\n", "\r");   
        if($data['showapi_res_body']['data']){
            foreach ($data['showapi_res_body']['data'] as $key => $value) {
                $content[$key]['context'] = str_replace($order, '', $value['context']);
                $content[$key]['time'] = $value['time'];
            }
        }
        $return['content'] = $content;
        jsonRespons('true','',$this->token,$return);
    }
    /*
     * 填写仲裁信息
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function zhongcai(){
        $id = I('post.order_detail_id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);  
        }
        
        $user = token_user($this->token);
        $is = M('OrderList')->where(array('id'=>$id))->field('status')->find();
        if(!$is){
            jsonRespons('false','不存在此订单',$this->token);
        }
        if(in_array($is['status'],array(5))){
            $return = M('OrderList')->where(array('id'=>$id))->setfield(array('zhongcai'=>I('post.zhongcai'),'status'=>6,'refund_status'=>7));
            if($return){
                //待续处理
                jsonRespons('true','',$this->token);
            }else{
                jsonRespons('false','申请失败',$this->token);
            }
        }else{
             jsonRespons('false','订单状态错误',$this->token);
        }
    }

}