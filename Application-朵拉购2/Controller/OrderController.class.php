<?php
namespace Test\Controller;
use Think\Controller;
class OrderController extends Controller {
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
        $c_where['user_id'] = array('in',$user['user_id']);
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
        $list = M('Order')->where($where)->field('id,order_number,pay,seller_business_id,status,refund_content,refund_money,express_fee,refund_express_id,refund_type')->limit($page*10,10)->order('update_time desc')->select();
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
            $list[$key]['list'] = $order_list->where(array('order_id'=>$value['id']))->field('product_id,nums,price,title,thumb_url,specification')->select();
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
        $info = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->field('id,order_number,name,phone,address,seller_business_id,pay,refund_money,refund_type,express_fee,express_id,express_number,remark,create_date_time,pay_time,success_time,express_time,status')->find();
        if(in_array($info['status'],array(14,15,18,19))){
            $info['status'] = 7;
        }elseif(in_array($info['status'],array(13,16)) && $info['refund_type'] == 1){
            $list[$key]['status'] = 7;
        }
        $info['other_name'] = M('SuBusinessDetail')->where(array('business_id'=>$info['seller_business_id']))->getfield('other_name');
        $count = M('OrderExpressMsg')->where(array('order_id'=>$info['id']))->count();
        $info['express_time'] = M('OrderExpressMsg')->where(array('order_id'=>$info['id']))->getfield('create_date_time');
        $info['series'] = M('SuBusiness')->where(array('id'=>$info['seller_business_id']))->getfield('series');
        $info['list'] = M('OrderList')->where(array('order_id'=>$info['id']))->field('product_id,nums,weight,price,title,thumb_url,specification')->select();
        jsonRespons('true','',$this->token,$info);
    }
    /*
     * 退款详情
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function refund_detail(){
        $id = I('post.id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);  
        }
        $info = M('Order')->where(array('id'=>$id))->field('id,order_number,refund_time,refund_content,status,refund_money,refund_shuoming,refund_type,tk_memo')->find();
        if(in_array($info['status'],array(14,15,18,19)) || (in_array($info['status'],array(13,16)) && $info['refund_type'] == 1)){
            $info['status'] = 7;
        }elseif($info['status'] == 17){
            $info['status'] = 6;
        }
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
        $is = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->field('pay,status,jd_number,red_packet_id')->find();
        if(!$is){
            jsonRespons('false','不存在此订单',$this->token);
        }
        if(in_array($is['status'],array(1,2))){
            if($is['status'] == 2){
                $return = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->setfield(array('status'=>-1,'refund_type'=>1,'refund_time'=>date('Y-m-d H:i:s'),'refund_content'=>'取消订单','refund_money'=>$is['pay'],'update_time'=>date('Y-m-d H:i:s')));
            }else{
                if($is['jd_number']){
                    //预占库存去掉
                    $token = M('JdToken')->getfield('token');
                    $data['token'] = $token;
                    $data['jdOrderId'] = $is['jd_number'];
                    // dump($data);exit;
                    curl('https://bizapi.jd.com/api/order/cancel',$data,'','json');
                }
                $return = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->setfield(array('status'=>0,'update_time'=>date('Y-m-d H:i:s')));
                //如果存在红包
                if($is['red_packet_id']){
                	 $return = M('SuRedPacket')->where(array('id'=>$is['red_packet_id']))->setfield(array('status'=>1));
                }
            } 
            if($return){
                if($is['status'] == 2){
                    //已付款取消订单之后的处理
                }
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
        $id = I('post.id');
        $type = I('post.type',1,'intval');//1是未收到货退款 2是退货退款
        if(!$id || !I('post.refund_content') || !I('post.refund_money')){
            jsonRespons('false','参数错误',$this->token);  
        }
        if($type == 2){
            // //验证是否填写了物流和物流单号
            // if(!I('post.refund_express_id') || !I('post.refund_express_number')){
            //     jsonRespons('false','参数错误','请填写物流信息');  
            // }
        }
        $user = token_user($this->token);
        $is = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->field('pay,status')->find();
        if(!$is){
            jsonRespons('false','不存在此订单',$this->token);
        }
        //判断退款金额是否大于支付金额
        if(I('post.refund_money') > $is['pay']){
            jsonRespons('false','退款金额不能大于支付金额',$this->token);
        }
        if(in_array($is['status'],array(3,6))){
            $return = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->setfield(array('status'=>7,'refund_money'=>I('post.refund_money'),'refund_content'=>I('post.refund_content'),'refund_shuoming'=>I('post.refund_shuoming'),'refund_type'=>I('post.type'),'refund_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s')));
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
     * 填写申请退款信息
     * @date: 2016-12-2 上午9:55:01
     * @editor: YU
     */
    public function refund_order_data(){
        $id = I('post.id');
        if(!$id || !I('post.refund_express_id') || !I('post.refund_express_number')){
            jsonRespons('false','参数错误',$this->token);  
        }
        
        $user = token_user($this->token);
        $is = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->field('pay,status')->find();
        if(!$is){
            jsonRespons('false','不存在此订单',$this->token);
        }
        if(in_array($is['status'],array(13,16))){
            $return = M('Order')->where(array('id'=>$id,'user_id'=>$user['user_id']))->setfield(array('refund_express_id'=>I('post.refund_express_id'),'refund_express_number'=>I('post.refund_express_number')));
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
            if($order['status'] == 5){
                //部分退款 计算的金额为实际支付金额-退款金额
                if($order['pay'] > $order['refund_money']){
                    $order['pay'] = round($order['pay']-$order['refund_money'],2);
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
                    $con = $ConsumeModel->lock(true)->add(array(
                        'user_id'=>$user['user_id'],
                        'business_id'=>$order['business_id'],
                        'order_id'=>$order['id'],
                        'series'=>$series,
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
    }
    /*
     * 确认订单
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function confirm_order(){
        $id = I('post.id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
        if(I('post.address_id')){
            $address = M('MallAddress')->where(array('id'=>I('post.address_id')))->field('id as address_id,user_name,mobile_phone,province_name,city_name,district_name,detail_address')->find();
        }else{
            //默认收货地址
            $address = M('MallAddress')->where(array('user_id'=>$user['user_id'],'is_default'=>1,'invalid'=>0))->field('id as address_id,user_name,mobile_phone,province_name,city_name,district_name,detail_address')->find();
        }
       

        $where['id'] = array('in',$id);
        $car_list = M('MallCar')->where($where)->field('id,business_id,product_id,num')->select();
        $bus = M('SuBusiness');
        $busD = M('SuBusinessDetail');
        $product = M('MallProduct');
        foreach ($car_list as $key => $value) {
            $p_info = $product->where(array('id'=>$value['product_id']))->field('title,thumb_url,discount_price,specification,weight')->find();
            $value['title'] = $p_info['title'];
            $value['thumb_url'] = $p_info['thumb_url'];
            $value['discount_price'] = $p_info['discount_price'];
            $value['specification'] = $p_info['specification'];
            $value['series'] = $bus->where(array('id'=>$value['business_id']))->getfield('series');
            $value['weight'] = $p_info['weight'];
            $array[$value['business_id']][] = $value;
        }
        foreach ($array as $key => $value) {
           // $arr[$key]['express_money'] = 0; 
           $arr[$key]['business'] = $busD->where(array('business_id'=>$key))->getfield('other_name');
           // $pay_money = 0;
           // $weight = 0;
           // foreach ($array as $key1 => $value1) {
           //     $weight += $value1['weight'] * $value1['num']; 
           //     $pay_money = $value1['discount_price'] * $value1['num'];
           // }
           // if($address){
           //      $arr[$key]['express_money'] = get_express_fee($weight,$pay_money,$address['city_name']); 
           // }else{
           //      $arr[$key]['express_money'] = 0;
           // }
           $arr[$key]['data'] = $value;
        }
        $arr = array_merge($arr);
        foreach ($arr as $arr_key => $arr_value) {
            $pay_money = 0;
            $weight = 0;
            foreach ($arr_value['data'] as $data_key => $vdata_alue) {
               $weight += $vdata_alue['weight'] * $vdata_alue['num']; 
               $pay_money += $vdata_alue['discount_price'] * $vdata_alue['num'];
            }
            if($address){
                $arr[$arr_key]['express_money'] = get_express_fee($weight,$pay_money,$address['city_name']); 
            }else{
                $arr[$arr_key]['express_money'] = 0;
            }
        }
        $data['address'] = $address;
        $data['list'] = $arr;
        jsonRespons('true','',$this->token,$data);
    }
    /*
     * 提交订单
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function submit_order(){
        $id = I('post.id');
        if(!$id || !I('post.address_id')){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
        //查询到地址信息
        $address = M('MallAddress')->where(array('id'=>I('post.address_id')))->field('user_name,mobile_phone,province_name,city_name,district_name,detail_address')->find();
        
        //不同商家生成不同订单，试用同一个支付号
        //支付号
        $pay_number = mkpay();
        //留言内容
        $leave = explode(',',I('post.leave'));
        //先查询存在几个商家
        $where['id'] = array('in',$id);
        $business_arr = M('MallCar')->where($where)->group('business_id')->field('business_id')->select();
        $car = M('MallCar');
        $product = M('MallProduct');
        $order = M('Order');
        $order->startTrans();
        $orderlist = M('OrderList');
        $is_false = 0;
        $zhifu = 0;
        //组装数据
        foreach ($business_arr as $key => $value) {
            $order_data['order_number'] =  mkorder();
            $order_data['pay_number'] =  $pay_number;
            $order_data['user_id'] =  $user['user_id'];
            $order_data['otype'] =  1;
            $order_data['seller_business_id'] =  $value['business_id'];
            $order_data['name'] =  $address['user_name'];
            $order_data['phone'] =  $address['mobile_phone'];
            $order_data['address'] =  $address['province_name'].$address['city_name'].$address['district_name'].$address['detail_address'];
            $order_data['area'] =  $address['city_name'];
            $order_data['remark'] =  $leave[$key]?:'';
            $order_data['create_date_time'] =  date('Y-m-d H:i:s');
            $order_data['update_time'] =  date('Y-m-d H:i:s');
            $order_data['status'] =  1;
            $order_data['invalid'] =  0;
            //商品数据
            $c_where['id'] = array('in',$id);
            $c_where['business_id'] = array('eq',$value['business_id']);
            $car_list = $car->where($c_where)->select();
            $total_money = 0;
            $pay_money = 0;
            $weight = 0;
            foreach ($car_list as $key1 => $value1) {
                $product_info = $product->where(array('id'=>$value1['product_id']))->field('price,discount_price,weight,status,invalid')->find();
                $total_money += $product_info['price']*$value1['num'];
                $pay_money += $product_info['discount_price']*$value1['num'];
                $weight += $product_info['weight']*$value1['num'];
                //验证一下商品是否下架或删除
                if($product_info['status'] != 1 || $product_info['invalid'] == 1){
                    $order->rollback();
                    $orderlist->rollback();
                    jsonRespons('false','商品已不存在',$this->token);
                }
                //因为表设计，在这里不能生产添加到子表的数据
            }
            //计算运费
            $express_fee = get_express_fee($weight,$pay_money,$address['city_name']);
            $pay_money += $express_fee;
            $order_data['express_fee'] =  $express_fee;
            $order_data['pay'] =  $pay_money;
            $order_data['amount'] =  $total_money;
            $zhifu += $pay_money;
            $order_id = $order->lock(true)->add($order_data);
            if($order_id){
                foreach ($car_list as $key2 => $value2) {
                    $product_info = $product->where(array('id'=>$value2['product_id']))->field('discount_price,weight,series,title,thumb_url,specification')->find();
                    //组装订单子表数据
                    $order_order_list['order_id'] = $order_id;
                    $order_order_list['product_id'] = $value2['product_id'];
                    $order_order_list['nums'] = $value2['num'];
                    $order_order_list['series'] =$product_info['series'];
                    $order_order_list['weight'] = $product_info['weight'];
                    $order_order_list['price'] = $product_info['discount_price'];
                    $order_order_list['title'] = $product_info['title'];
                    $order_order_list['thumb_url'] = $product_info['thumb_url'];
                    $order_order_list['specification'] = $product_info['specification'];
                    $order_order_list['create_date_time'] = date('Y-m-d H:i:s');
                    $order_order_list['update_time'] = date('Y-m-d H:i:s');
                    $order_order_list['status'] = 1;
                    $order_order_list['invalid'] = 0;
                    $return =$orderlist->lock(true)->add($order_order_list);
                    if(!$return){
                         $is_false = 1;
                    }
                }
                
            }else{
                $is_false = 1;
            }
            
        }
        if($is_false == 1){
            //说明之前操作数据库有失败的
            $order->rollback();
            $orderlist->rollback();
            jsonRespons('false','下单失败',$this->token);
        }else{
            $car->where($where)->setField(array('invalid'=>1));
            $order->commit();
            $orderlist->commit();
            jsonRespons('true','',$this->token,array('pay_number'=>$pay_number,'pay_money'=>$zhifu));
        }
    }
    /*
     * 立即购买
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function submit_buy(){
        $product_id = I('post.product_id');
        $num = I('post.num');
        if(!$product_id || !$num){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
       if(I('post.address_id')){
            $address = M('MallAddress')->where(array('id'=>I('post.address_id')))->field('id as address_id,user_name,mobile_phone,province_name,city_name,district_name,detail_address')->find();
        }else{
            //默认收货地址
            $address = M('MallAddress')->where(array('user_id'=>$user['user_id'],'is_default'=>1,'invalid'=>0))->field('id as address_id,user_name,mobile_phone,province_name,city_name,district_name,detail_address')->find();
        }
        $where['user_id'] = $user['user_id'];
        $where['product_id'] = $product_id;
        $where['invalid'] = 0;
        $car = M('MallCar')->where($where)->count();
        if($car){
            M('MallCar')->where($where)->delete();
        }
        $car_data['user_id'] = $user['user_id'];
        $car_data['product_id'] = $product_id;
        $car_data['business_id'] = M('MallProduct')->where(array('id'=>$product_id))->getfield('business_id');
        $car_data['num'] = $num;
        $car_data['create_date_time'] = date('Y-m-d H:i:s');
        $car_data['update_time'] = date('Y-m-d H:i:s');
        $car_data['status'] = 1;
        $car_data['invalid'] = 0;
        $id = M('MallCar')->add($car_data);
        $car_list = M('MallCar')->where(array('id'=>$id))->field('id,business_id,product_id,num')->select();
        $bus = M('SuBusiness');
        $busD = M('SuBusinessDetail');
        $product = M('MallProduct');
        foreach ($car_list as $key => $value) {
            $p_info = $product->where(array('id'=>$value['product_id']))->field('title,thumb_url,discount_price,specification,weight')->find();
            $value['title'] = $p_info['title'];
            $value['thumb_url'] = $p_info['thumb_url'];
            $value['discount_price'] = $p_info['discount_price'];
            $value['specification'] = $p_info['specification'];
            $value['series'] = $bus->where(array('id'=>$value['business_id']))->getfield('series');
            $value['weight'] = $p_info['weight'];
            $array[$value['business_id']][] = $value;
        }
        foreach ($array as $key => $value) {
           // $arr[$key]['express_money'] = 0; 
           $arr[$key]['business'] = $busD->where(array('business_id'=>$key))->getfield('other_name');
           $total_money = 0;
           // $pay_money = 0;
           // $weight = 0;
           // foreach ($value as $key1 => $value1) {
           //     $weight += $value1['weight'] * $value1['num']; 
           //     $pay_money = $value1['discount_price'] * $value1['num'];
           // }
           // if($address){
           //      $arr[$key]['express_money'] = get_express_fee($weight,$pay_money,$address['city_name']); 
           // }else{
           //      $arr[$key]['express_money'] = 0;
           // }
           $arr[$key]['data'] = $value;
        }
        $arr = array_merge($arr);
        foreach ($arr as $arr_key => $arr_value) {
            $pay_money = 0;
            $weight = 0;
            foreach ($arr_value['data'] as $data_key => $vdata_alue) {
               $weight += $vdata_alue['weight'] * $vdata_alue['num']; 
               $pay_money += $vdata_alue['discount_price'] * $vdata_alue['num'];
            }
            if($address){
                $arr[$arr_key]['express_money'] = get_express_fee($weight,$pay_money,$address['city_name']); 
            }else{
                $arr[$arr_key]['express_money'] = 0;
            }
        }
        $data['address'] = $address;
        $data['list'] = $arr;
        jsonRespons('true','',$this->token,$data);
    }
    /*
     * 提醒发货
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function remind(){
        $id = I('post.id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
        //先查询订单最后一次提醒的时间
        $order = M('Order')->where(array('id'=>$id))->field('order_number,seller_business_id')->find();
        $update_time = M('OrderRemind')->where(array('order_id'=>$id))->order('id desc')->getfield('update_time');
        if($update_time){
            if(strtotime($update_time) + 10800 > time()){
                jsonRespons('false','您最近已提醒过，请不要着急。',$this->token);
            }
            // $return = M('OrderRemind')->where(array('order_id'=>$id))->setField(array('update_time'=>date('Y-m-d H:i:s')));
        }
        $return = M('OrderRemind')->add(array('order_id'=>$id,'business_id'=>$order['seller_business_id'],'status'=>1,'invalid'=>0,'create_date_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s')));
        //发送短信
        jsonRespons('true','',$this->token,$data);
    }
     /*
     * 线下提交订单
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function line_submit(){
        $money = I('post.money');
        $business_id = I('post.business_id');
        if(!$money || !$business_id){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
        //支付号
        $data['pay_number'] = mkpay();
        $data['order_number'] =  mkorder();
        $data['otype'] =  2;
        $data['user_id'] =  $user['user_id'];
        $data['pay'] =  $money;
        $data['amount'] =  $money;
        $data['seller_business_id'] =  $business_id;
        $data['create_date_time'] =  date('Y-m-d H:i:s');
        $data['update_time'] =  date('Y-m-d H:i:s');
        $data['status'] =  1;
        $data['invalid'] =  0;
        $return = M('Order')->add($data);
        if($return){
            jsonRespons('true','',$this->token,array('pay_number'=>$data['pay_number'],'pay_money'=>$money));
        }else{
            jsonRespons('false','下单失败',$this->token);
        }
        
    }
    /*
     * 购物车支付，扫一扫支付，立即购买调用
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function pay_one(){
        if(!I('pay_number') || !I('pay_money') || !I('pay_type')){
            jsonRespons('false','参数错误',$this->token);
        }
        //验证支付金额对不对
        $order = M('Order')->where(array('pay_number'=>I('pay_number')))->field('pay')->select();
        $pay = 0;
        foreach ($order as $key => $value) {
            $pay += $value['pay'];
        }
        if($pay != I('pay_money')){
            jsonRespons('false','支付金额异常',$this->token);
        }
        vendor('ping.Ping');
        if(I('pay_type') == 1){
            $pay_type = 'alipay';
        }else{
            $pay_type = 'wx';
        }
        $ping = new \Ping;
        $reQuest = $ping->payinit(I('pay_number'),I('pay_money')*100,$pay_type);
        $reQuest = json_decode($reQuest,1);
        M('Order')->where(array('pay_number'=>I('pay_number')))->setField(array('pay_type'=>I('pay_type'),'charge_id'=>$reQuest['id']));
        jsonRespons('true','',$this->token,$reQuest);
    }
    /*
     * 订单付款使用
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function pay_two(){
        if(!I('order_id') || !I('pay_type')){
            jsonRespons('false','参数错误',$this->token);
        }
        //先更新订单的支付码
        $pay_number = mkpay();
        M('Order')->where(array('id'=>I('order_id')))->setField(array('pay_number'=>$pay_number));
        $order = M('Order')->where(array('id'=>I('order_id')))->field('pay,status,pay_number')->find();
        if($order['status'] != 1){
            jsonRespons('false','订单状态错误',$this->token);
        }
        vendor('ping.Ping');
        if(I('pay_type') == 1){
            $pay_type = 'alipay';
        }else{
            $pay_type = 'wx';
        }
        $ping = new \Ping;
        $reQuest = $ping->payinit($order['pay_number'],$order['pay']*100,$pay_type);
        $reQuest = json_decode($reQuest,1);
        $return = M('Order')->where(array('id'=>I('order_id')))->setField(array('pay_type'=>I('pay_type'),'charge_id'=>$reQuest['id']));
        if(!$return){
            jsonRespons('false','更新订单失败',$this->token);
        }
        jsonRespons('true','',$this->token,$reQuest);
    }
    /*
     * 快递信息
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function express(){
        if(!I('order_id')){
            jsonRespons('false','参数错误',$this->token);
        }
        $order = M('Order')->where(array('id'=>I('order_id')))->find();
        // dump($order);exit;
        //如果是京东订单 调用京东物流查询
        if($order['is_jd'] == 1){
            $count = M('OrderExpressMsg')->where(array('order_id'=>I('order_id'),'invalid'=>0))->count();
            if(!$count){
                jsonRespons('false','暂无物流信息',$this->token);
            }
            $msg = M('OrderExpressMsg')->where(array('order_id'=>I('order_id'),'invalid'=>0))->select();
            $num = I('num',1,'intval');
            $jd_token = M('JdToken')->getfield('token');
            $jd['token'] = $jd_token;
            $jd['jdOrderId'] =  $msg[$num]['express_number']?:$order['jd_number'];
            $data = curl('https://bizapi.jd.com/api/order/orderTrack',$jd,'','json');
            if($count > 1){
                $return['num'] = $count-1;
            }else{
                $return['num'] = '1';
            }
            
            $return['mailNo'] = '无';
            $return['expTextName'] = '京东快递';
            $return['status'] = '';
           if($data['result']['orderTrack']){
                foreach ($data['result']['orderTrack'] as $key => $value) {
                    $content[$key]['id'] = $key+1;
                    $content[$key]['context'] = $value['content'];
                    $content[$key]['time'] = $value['msgTime'];
                }
               
            }

            $content = my_sort($content,'id',SORT_DESC,SORT_NUMERIC);
            $return['content'] = $content;
        }else{
            $count = M('OrderExpressMsg')->where(array('order_id'=>I('order_id'),'invalid'=>0))->count();
            if(!$count){
                jsonRespons('false','暂无物流信息',$this->token);
            }
            $num = I('num',1,'intval');
            $num = $num - 1;
            $msg = M('OrderExpressMsg')->where(array('order_id'=>I('order_id'),'invalid'=>0))->select();
            $express = M('OrderExpress')->where(array('id'=>$msg[$num]['express_id']))->field('name,number')->find();
            $host = "http://ali-deliver.showapi.com";
            $path = "/showapi_expInfo";
            $method = "GET";
            $appcode = "989f0f3ec92c452b9fc0367e8f5139ee";
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            $querys = "com=".$express['number']."&nu=".$msg[$num]['express_number'];
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
            $return['num'] = $count?:'0';
            $return['mailNo'] = $msg[$num]['express_number'];
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
        }
        
        jsonRespons('true','',$this->token,$return);
    }
    /*
     * 退款快递信息
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function refund_express(){
        if(!I('order_id')){
            jsonRespons('false','参数错误',$this->token);
        }
        $number = M('Order')->where(array('id'=>I('order_id')))->field('refund_express_id,refund_express_number')->find();
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
     * 快递列表
     * @date: 2016-12-1 下午4:55:01
     * @editor: YU
     */
    public function express_list(){
        $where['id'] = array('in','2,3,4,5,8,13,18');
        $where['status'] = array('eq',1);
        $where['invalid'] = array('eq',0);
        $list = M('OrderExpress')->where($where)->field('id,name')->select();
        jsonRespons('true','',$this->token,$list);
    }
    /***********************************************2017-2-13*************************************************************/
    /*
     * 新确认订单
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function new_confirm_order(){
        $id = I('post.id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
        if(I('post.address_id')){
            $address = M('MallAddress')->where(array('id'=>I('post.address_id')))->field('id as address_id,user_name,mobile_phone,one_id,two_id,three_id,four_id,one_name,two_name,three_name,four_name,detail_address')->find();
        }else{
            //默认收货地址
            $address = M('MallAddress')->where(array('user_id'=>$user['user_id'],'is_default'=>1,'invalid'=>0))->field('id as address_id,user_name,mobile_phone,one_id,two_id,three_id,four_id,one_name,two_name,three_name,four_name,detail_address')->find();
        }
        $jd_token = M('JdToken')->getfield('token');
        $where['id'] = array('in',$id);
        $car_list = M('MallCar')->where($where)->field('id,business_id,skuid,product_id,num')->select();
        $bus = M('SuBusiness');
        $busD = M('SuBusinessDetail');
        $product = M('MallProduct');
        $spec = M('MallSpecData');
        $freight = M('MallFreight');
        $freight_list = M('MallFreightList');
        foreach ($car_list as $key => $value) {
            $title = $product->where(array('id'=>$value['product_id']))->field('title,is_postage,freight_id')->find();
            $p_info = $spec->where(array('cols1'=>$value['skuid'],'invalid'=>0))->field('cols7,cols5,cols6,cols8,cols9,cols10,cols11,cols12,cols13,cols14,cols15,cols16,cols17,cols18,cols19,cols20')->find();
            $value['title'] = $title['title'];
            $value['thumb_url'] = $p_info['cols7'];
            $value['discount_price'] = $p_info['cols5'];
            $value['specification'] = $p_info['cols8'].$p_info['cols9'].$p_info['cols10'].$p_info['cols11'].$p_info['cols12'].$p_info['cols13'].$p_info['cols14'].$p_info['cols15'].$p_info['cols16'].$p_info['cols17'].$p_info['cols18'].$p_info['cols19'].$p_info['cols20'];
            $business= $bus->where(array('id'=>$value['business_id']))->field('series,jd_type')->find();
            $value['series'] = $business['series'];
            $value['huowu'] = '1';
            $value['yunfei'] = '0';
            if($business['jd_type'] == 1 && $address && $value['skuid']){
                //查询京东库存
                
                $jd['token'] = $jd_token;
                $jd['skuNums'] = '[{skuId: '.$value['skuid'].',num:'.$value['num'].'}]';

                $jd['area'] = $address['one_id'].'_'.$address['two_id'].'_'.($address['three_id']?:0).'_'.($address['four_id']?:0);
                $return = curl('https://bizapi.jd.com/api/stock/getNewStockById',$jd,'','json');
                $result = json_decode($return['result'],true);
                if($result[0]['stockStateId'] != 33){
                    $value['huowu'] = '0';
                }
            }else{
                //计算运费
                if($title['is_postage'] == 1){
                    $f_moban = $freight->where(array('id'=>$title['freight_id']))->find();

                    $f_l_moban = $freight_list->where(array('freight_id'=>$f_moban,'province_name'=>$address['one_name']))->find();
                    if($f_l_moban){
                        if($value['num'] > 1){
                            $value['yunfei'] = $f_l_moban['data2']*1 + ($value['num']-1)*$f_l_moban['data4']; 
                        }else{
                            $value['yunfei'] = $f_l_moban['data2']*1;
                        }
                    }else{
                        if($value['num'] > 1){
                            $value['yunfei'] = $f_moban['data2']*1 + ($value['num']-1)*$f_moban['data4']; 
                        }else{
                            $value['yunfei'] = $f_moban['data2']*1;
                        }
                    }
                }
            }
            $value['weight'] = $p_info['cols6'];
            $array[$value['business_id']][] = $value;
        }
        foreach ($array as $key => $value) {
           // $arr[$key]['express_money'] = 0; 
           $arr[$key]['business'] = $busD->where(array('business_id'=>$key))->getfield('other_name');
           $arr[$key]['jd'] = $bus->where(array('id'=>$key))->getfield('jd_type');
           $arr[$key]['data'] = $value;
        }
        $arr = array_merge($arr);
        foreach ($arr as $arr_key => $arr_value) {
            $pay_money = 0;
            $weight = 0;
            $sku = array();
            foreach ($arr_value['data'] as $data_key => $vdata_alue) {
               $weight += $vdata_alue['weight'] * $vdata_alue['num']; 
               $pay_money += $vdata_alue['discount_price'] * $vdata_alue['num'];
               $sku[$data_key]=array('skuId'=>$vdata_alue['skuid'],'num'=>$vdata_alue['num']);
               $arr[$arr_key]['express_money'] += $vdata_alue['yunfei'];
            }
            if($address){
                if($arr_value['jd'] == 1){
                    //查询京东运费
                    if($pay_money < 99){
                        $yf['token'] = $jd_token;
                        $yf['sku'] = json_encode($sku);
                        $yf['province'] = $address['one_id'];
                        $yf['city'] = $address['two_id'];
                        $yf['county'] = $address['three_id'];
                        $yf['town'] = $address['four_id'];
                        $yf['paymentType'] = 4;
                        $return = curl('https://bizapi.jd.com/api/order/getFreight',$yf,'','json');
                        if($return['success']){
                            $arr[$arr_key]['express_money'] = $return['result']['freight'];
                        }else{
                            jsonRespons('false','运费查询失败',$this->token);
                        }
                    }else{
                        $arr[$arr_key]['express_money'] = 0;
                    }
                    
                }//else{
                    // $arr[$arr_key]['express_money'] = get_express_fee($weight,$pay_money,$address['city_name']); 
                //}
                
            }//else{
             //   $arr[$arr_key]['express_money'] = 0;
            //}

        }
        $data['address'] = $address;
        $data['list'] = $arr;
        jsonRespons('true','',$this->token,$data);
    }

    /*
     * 新确认订单(改)
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function new_confirm_order1(){
        $id = I('post.id');
        if(!$id){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
        if(I('post.address_id')){
            $address = M('MallAddress')->where(array('id'=>I('post.address_id')))->field('id as address_id,user_name,mobile_phone,one_id,two_id,three_id,four_id,one_name,two_name,three_name,four_name,detail_address')->find();
        }else{
            //默认收货地址
            $address = M('MallAddress')->where(array('user_id'=>$user['user_id'],'is_default'=>1,'invalid'=>0))->field('id as address_id,user_name,mobile_phone,one_id,two_id,three_id,four_id,one_name,two_name,three_name,four_name,detail_address')->find();
        }
        $jd_token = M('JdToken')->getfield('token');
        $where['id'] = array('in',$id);
        $car_list = M('MallCar')->where($where)->field('id,business_id,skuid,product_id,num')->select();
        $bus = M('SuBusiness');
        $busD = M('SuBusinessDetail');
        $product = M('MallProduct');
        $spec = M('MallSpecData');
        $freight = M('MallFreight');
        $freight_list = M('MallFreightList');
        foreach ($car_list as $key => $value) {
            $title = $product->where(array('id'=>$value['product_id']))->field('title,is_postage,freight_id,series')->find();
            $p_info = $spec->where(array('cols1'=>$value['skuid'],'invalid'=>0))->field('cols7,cols5,cols6,cols8,cols9,cols10,cols11,cols12,cols13,cols14,cols15,cols16,cols17,cols18,cols19,cols20')->find();
            $value['title'] = $title['title'];
            $value['thumb_url'] = $p_info['cols7'];
            $value['discount_price'] = $p_info['cols5'];
            $value['specification'] = $p_info['cols8'].$p_info['cols9'].$p_info['cols10'].$p_info['cols11'].$p_info['cols12'].$p_info['cols13'].$p_info['cols14'].$p_info['cols15'].$p_info['cols16'].$p_info['cols17'].$p_info['cols18'].$p_info['cols19'].$p_info['cols20'];
            // $business= $bus->where(array('id'=>$value['business_id']))->field('series,jd_type')->find();
            $value['series'] = $title['series'];
            $value['huowu'] = '1';
            $value['yunfei'] = '0';
            if($business['jd_type'] == 1 && $address && $value['skuid']){
                //查询京东库存
                
                $jd['token'] = $jd_token;
                $jd['skuNums'] = '[{skuId: '.$value['skuid'].',num:'.$value['num'].'}]';

                $jd['area'] = $address['one_id'].'_'.$address['two_id'].'_'.($address['three_id']?:0).'_'.($address['four_id']?:0);
                $return = curl('https://bizapi.jd.com/api/stock/getNewStockById',$jd,'','json');
                $result = json_decode($return['result'],true);
                if($result[0]['stockStateId'] != 33){
                    $value['huowu'] = '0';
                }
            }else{
                //计算运费
                if($title['is_postage'] == 1){
                    $f_moban = $freight->where(array('id'=>$title['freight_id']))->find();

                    $f_l_moban = $freight_list->where(array('freight_id'=>$f_moban,'province_name'=>$address['one_name']))->find();
                    if($f_l_moban){
                        if($value['num'] > 1){
                            $value['yunfei'] = $f_l_moban['data2']*1 + ($value['num']-1)*$f_l_moban['data4']; 
                        }else{
                            $value['yunfei'] = $f_l_moban['data2']*1;
                        }
                    }else{
                        if($value['num'] > 1){
                            $value['yunfei'] = $f_moban['data2']*1 + ($value['num']-1)*$f_moban['data4']; 
                        }else{
                            $value['yunfei'] = $f_moban['data2']*1;
                        }
                    }
                }
            }
            $value['weight'] = $p_info['cols6'];
            $array[$value['business_id']][] = $value;
        }
        foreach ($array as $key => $value) {
           // $arr[$key]['express_money'] = 0; 
           $arr[$key]['business'] = $busD->where(array('business_id'=>$key))->getfield('other_name');
           $arr[$key]['jd'] = $bus->where(array('id'=>$key))->getfield('jd_type');
           $arr[$key]['data'] = $value;
        }
        $arr = array_merge($arr);
        foreach ($arr as $arr_key => $arr_value) {
            $pay_money = 0;
            $weight = 0;
            $sku = array();
            foreach ($arr_value['data'] as $data_key => $vdata_alue) {
               $weight += $vdata_alue['weight'] * $vdata_alue['num']; 
               $pay_money += $vdata_alue['discount_price'] * $vdata_alue['num'];
               $sku[$data_key]=array('skuId'=>$vdata_alue['skuid'],'num'=>$vdata_alue['num']);
               $arr[$arr_key]['express_money'] += $vdata_alue['yunfei'];
            }
            if($address){
                if($arr_value['jd'] == 1){
                    //查询京东运费
                    if($pay_money < 99){
                        $yf['token'] = $jd_token;
                        $yf['sku'] = json_encode($sku);
                        $yf['province'] = $address['one_id'];
                        $yf['city'] = $address['two_id'];
                        $yf['county'] = $address['three_id'];
                        $yf['town'] = $address['four_id'];
                        $yf['paymentType'] = 4;
                        $return = curl('https://bizapi.jd.com/api/order/getFreight',$yf,'','json');
                        if($return['success']){
                            $arr[$arr_key]['express_money'] = $return['result']['freight'];
                        }else{
                            jsonRespons('false','运费查询失败',$this->token);
                        }
                    }else{
                        $arr[$arr_key]['express_money'] = 0;
                    }
                    
                }//else{
                    // $arr[$arr_key]['express_money'] = get_express_fee($weight,$pay_money,$address['city_name']); 
                //}
                
            }//else{
             //   $arr[$arr_key]['express_money'] = 0;
            //}

        }
        $data['address'] = $address;
        $data['list'] = $arr;
        jsonRespons('true','',$this->token,$data);
    }

    /*
     * 验证库存接口
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function check_kucun(){
        if(!I('skuid') || !I('num') || !I('post.address_id')){
            jsonRespons('false','参数错误',$this->token);
        }
         //查询是自有商品还是自营
        $where['cols1'] = array('in',I('skuid'));
        $where['status'] = array('eq',1);
        $where['invalid'] = array('eq',0);
        $num = explode(',',I('num'));
        $spec = M('MallSpecData')->where($where)->field('top_type,product_id,cols1,cols2,cols7')->select();
        $i = 0;
        $jd_token = M('JdToken')->getfield('token');
        $product = M('MallProduct');
        $address = M('MallAddress')->where(array('id'=>I('post.address_id')))->field('one_id,two_id,three_id,four_id')->find();
        foreach ($spec as $key => $value) {
            if($value['top_type'] == 2){
                if($num[$key] > $value['cols2']){
                    $title = $product->where(array('id'=>$value['product_id']))->getfield('title');
                    $array[$i]['title'] = $title;
                    $array[$i]['thumb_url'] = $value['cols7'];
                    $array[$i]['num'] = $num[$key];
                    $array[$i]['sy_num'] = $value['cols2'];
                    $i++;
                }
            }else{
                $data['token'] = $jd_token;
                $data['skuNums'] = '['.json_encode(array('skuId'=>$value['cols1'],'num'=>$num[$key])).']';
                $data['area'] = $address['one_id'].'_'.$address['two_id'].'_'.($address['three_id']?:0).'_'.($address['four_id']?:0);
                $return = curl('https://bizapi.jd.com/api/stock/getNewStockById',$data,'','json');
                // $result = json_decode($return['result'],true);
                $result = json_decode($return['result'],true);
                if($result[0]['stockStateId'] != 33){
                    $title = $product->where(array('id'=>$value['product_id']))->getfield('title');
                    // $array[$i]['title'] = $title;
                    // $array[$i]['thumb_url'] = $value['cols7'];
                    // $array[$i]['num'] = $num[$key];
                    // $array[$i]['sy_num'] = $result[0]['remainNum'] == -1?'0':$result[0]['remainNum'];
                    // $array[$i]['title'] = '';
                    // $array[$i]['thumb_url'] = '';
                    // $array[$i]['num'] = '';
                    // $array[$i]['sy_num'] = '';
                    $i++;
                }
            }
        }
        // dump($array);
        jsonRespons('true','',$this->token,$array);
    }
    /*
     * 京东下单方法
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU  
     */
    public function xiadan($order_number,$address_id,$remark,$sku,$fy_type){
        $jd_token = M('JdToken')->getfield('token');
    
        $jd['token'] = $jd_token;
        $jd['thirdOrder'] = $order_number;
        $jd['sku'] = json_encode($sku);
        
        $address = M('MallAddress')->where(array('id'=>$address_id))->field('user_name,mobile_phone,one_id,two_id,three_id,four_id,detail_address')->find();
        $jd['name'] = $address['user_name'];
        $jd['province'] = $address['one_id'];
        $jd['city'] = $address['two_id'];
        $jd['county'] = $address['three_id'];
        $jd['town'] = $address['four_id'];
        $jd['address'] = $address['detail_address'];
        $jd['mobile'] = $address['mobile_phone'];
        $jd['email'] = 'service@dorago.cn';
        $jd['remark'] = $remark;
        $jd['invoiceState'] = 2;
        $jd['invoiceType'] = $fy_type;
        $jd['selectedInvoiceTitle'] = 5;
        $jd['companyName'] = '浙江朵宝网络科技有限公司';
        $jd['invoiceContent'] = 1;
        $jd['paymentType'] = 4;
        $jd['isUseBalance'] = 1;
        $jd['submitState'] = 0;
        dump($jd);exit;
        $return = curl('https://bizapi.jd.com/api/order/submitOrder',$jd,'','json');
        jsonRespons('true','',$this->token,$array);
    }
    /*
     * 新立即购买
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function new_submit_buy(){
        $skuid = I('post.skuid');
        $product_id = I('post.product_id');
        $num = I('post.num');
        if(!$product_id || !$num || !$skuid){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
        if(I('post.address_id')){
            $address = M('MallAddress')->where(array('id'=>I('post.address_id')))->field('id as address_id,user_name,mobile_phone,one_id,two_id,three_id,four_id,one_name,two_name,three_name,four_name,detail_address')->find();
        }else{
            //默认收货地址
            $address = M('MallAddress')->where(array('user_id'=>$user['user_id'],'is_default'=>1,'invalid'=>0))->field('id as address_id,user_name,mobile_phone,one_id,two_id,three_id,four_id,one_name,two_name,three_name,four_name,detail_address')->find();
        }
        $ids = M('MallProductType')->where(array('type'=>1,'product_id'=>$product_id,'invalid'=>0,'status'=>1))->find();
        //如果是油卡  每个月只可购买一次
        if($ids){
            $where['user_id'] = array('eq',$user['user_id']);
            $where['jiayouka'] = array('neq','');
            $where['status'] = array('neq',0);
            $jiayouka = M('Order')->where($where)->field('create_date_time')->find();
            if($jiayouka){
                if(date('Y-m') == date('Y-m',strtotime($jiayouka['create_date_time']))){
                    jsonRespons('false','本月您已购买过加油卡，请下个月再购买！',$token);
                }
            }
            
        }
        //验证是否超过可最大购买数
        $xg = M('MallProduct')->where(array('id'=>$product_id))->field('xg,online_type')->find();
        if($xg['xg']){
             $order_where['user_id'] = $user['user_id'];
               $order_where['status'] = array('neq',0);
               $order = M('Order')->where($order_where)->getfield('id',true);
               $shuliang = 0;
               if($order){
                    $order = implode(',',$order);
                    $list_where['order_id'] = array('in',$order);
                    $list_where['product_id'] = array('eq',$product_id);
                    $shuliang =M('OrderList')->where($list_where)->sum('nums');
               }
            if($xg['xg']<($num+$shuliang)){
                if($shuliang){
                    $content = '此商品限购'.$xg['xg'].'件，'.'您已购买'.$shuliang.'件';
                }else{
                    $content = '此商品限购'.$xg['xg'].'件';
                }
                jsonRespons('false',$content,$token);
            }
        }
        //如果是新人产品验证他是否已存在新人产品订单
        if($xg['online_type'] > 1){
            // $new_where['user_id'] = array('eq',$user['user_id']);
            // $new_where['status'] = array('gt',1);
            // $new_order = M('Order')->where($new_where)->field('id')->find();
            // if($new_order){
            //     jsonRespons('false','此商品为新人专享',$token);
            // }
            // $p_where['online_type'] = array('neq',1);
            // $p_list = M('MallProduct')->where($p_where)->getfield('id',true);
            // //验证是否存在新人商品未支付的订单
            // $new_order_list = M('Order')->where(array('status'=>1))->field('id')->select();
            // if($new_order_list){
            //     $o_list = M('OrderList');
            //     foreach ($new_order_list as $key => $value) {
            //         //如果是新人产品的订单  那么一个订单只会存在一个产品 所有find就可以
            //         $new_product_id = $o_list->where(array('order_id'=>$value['id']))->getfield('product_id');
            //         if(in_array($new_product_id,$p_list)){
            //             jsonRespons('false','您已存在新人专享订单',$token);
            //         }
            //     }
            // }
            $is_new = M('SuUserTree')->where(array('user_id'=>$user['user_id']))->field('id')->find();
            if($is_new){
                jsonRespons('false','此商品为新人专享',$token);
            }
        }
        $where['user_id'] = $user['user_id'];
        $where['skuid'] = $skuid;
        $where['product_id'] = $product_id;
        $where['invalid'] = 0;
        $car = M('MallCar')->where($where)->count();
        if($car){
            M('MallCar')->where($where)->delete();
        }
        $car_data['user_id'] = $user['user_id'];
        $car_data['skuid'] = $skuid;
        $car_data['product_id'] = $product_id;
        $car_data['business_id'] = M('MallProduct')->where(array('id'=>$product_id))->getfield('business_id');
        $car_data['num'] = $num;
        $car_data['create_date_time'] = date('Y-m-d H:i:s');
        $car_data['update_time'] = date('Y-m-d H:i:s');
        $car_data['status'] = 1;
        
        if($ids){
            $car_data['invalid'] = 1;
        }else{
            $car_data['invalid'] = 0;
        }
        
        $id = M('MallCar')->add($car_data);
        $car_list = M('MallCar')->where(array('id'=>$id))->field('id,skuid,business_id,product_id,num')->select();
        $bus = M('SuBusiness');
        $busD = M('SuBusinessDetail');
        $product = M('MallProduct');
        $spec = M('MallSpecData');
        $freight = M('MallFreight');
        $freight_list = M('MallFreightList');
        $jd_token = M('JdToken')->getfield('token');
        foreach ($car_list as $key => $value) {
            $title = $product->where(array('id'=>$value['product_id']))->field('title,is_postage,freight_id')->find();
            $p_info = $spec->where(array('cols1'=>$value['skuid'],'invalid'=>0))->field('cols7,cols5,cols6,cols8,cols9,cols10,cols11,cols12,cols13,cols14,cols15,cols16,cols17,cols18,cols19,cols20')->find();
            $value['title'] = $title['title'];
            $value['thumb_url'] = $p_info['cols7'];
            $value['discount_price'] = $p_info['cols5'];
            $value['specification'] = $p_info['cols8'].$p_info['cols9'].$p_info['cols10'].$p_info['cols11'].$p_info['cols12'].$p_info['cols13'].$p_info['cols14'].$p_info['cols15'].$p_info['cols16'].$p_info['cols17'].$p_info['cols18'].$p_info['cols19'].$p_info['cols20'];
            $business= $bus->where(array('id'=>$value['business_id']))->field('series,jd_type')->find();
            $value['series'] = $business['series'];
            $value['huowu'] = '1';
            if($business['jd_type'] == 1 && $address && $value['skuid']){
                //查询京东库存
                
                $jd['token'] = $jd_token;
                $jd['skuNums'] = '[{skuId: '.$value['skuid'].',num:'.$value['num'].'}]';

                $jd['area'] = $address['one_id'].'_'.$address['two_id'].'_'.($address['three_id']?:0).'_'.($address['four_id']?:0);
                $return = curl('https://bizapi.jd.com/api/stock/getNewStockById',$jd,'','json');
                $result = json_decode($return['result'],true);
                if($result[0]['stockStateId'] != 33){
                    $value['huowu'] = '0';
                }
            }else{
                //自营计算运费
                if($title['is_postage'] == 1){
                    $f_moban = $freight->where(array('id'=>$title['freight_id']))->find();

                    $f_l_moban = $freight_list->where(array('freight_id'=>$f_moban,'province_name'=>$address['one_name']))->find();
                    if($f_l_moban){
                        if($value['num'] > 1){
                            $value['yunfei'] = $f_l_moban['data2']*1 + ($value['num']-1)*$f_l_moban['data4']; 
                        }else{
                            $value['yunfei'] = $f_l_moban['data2']*1;
                        }
                    }else{
                        if($value['num'] > 1){
                            $value['yunfei'] = $f_moban['data2']*1 + ($value['num']-1)*$f_moban['data4']; 
                        }else{
                            $value['yunfei'] = $f_moban['data2']*1;
                        }
                    }
                }
            }
            $value['weight'] = $p_info['cols6'];
            $array[$value['business_id']][] = $value;
        }
        foreach ($array as $key => $value) {
           $arr[$key]['business'] = $busD->where(array('business_id'=>$key))->getfield('other_name');
           $arr[$key]['jd'] = $bus->where(array('id'=>$key))->getfield('jd_type');
           
           $arr[$key]['data'] = $value;
        }
        $arr = array_merge($arr);
        foreach ($arr as $arr_key => $arr_value) {
            $pay_money = 0;
            $weight = 0;
            $sku = array();
            foreach ($arr_value['data'] as $data_key => $vdata_alue) {
               $weight += $vdata_alue['weight'] * $vdata_alue['num']; 
               $pay_money += $vdata_alue['discount_price'] * $vdata_alue['num'];
               $sku[$data_key]=array('skuId'=>$vdata_alue['skuid'],'num'=>$vdata_alue['num']);
               $arr[$arr_key]['express_money'] += $vdata_alue['yunfei'];
            }
            if($address){
                if($arr_value['jd'] == 1){
                    //查询京东运费
                    if($pay_money < 99){
                        $yf['token'] = $jd_token;
                        $yf['sku'] = json_encode($sku);
                        $yf['province'] = $address['one_id'];
                        $yf['city'] = $address['two_id'];
                        $yf['county'] = $address['three_id'];
                        $yf['town'] = $address['four_id'];
                        $yf['paymentType'] = 4;
                        $return = curl('https://bizapi.jd.com/api/order/getFreight',$yf,'','json');
                        if($return['success']){
                            $arr[$arr_key]['express_money'] = $return['result']['freight'];
                        }else{
                            jsonRespons('false','运费查询失败',$this->token);
                        }
                    }else{
                        $arr[$arr_key]['express_money'] = 0;
                    }
                    
                }//else{
                    // $arr[$arr_key]['express_money'] = get_express_fee($weight,$pay_money,$address['city_name']); 
                //}
                
            }//else{
             //   $arr[$arr_key]['express_money'] = 0;
            //}
        }
        $data['address'] = $address;
        $data['list'] = $arr;
        jsonRespons('true','',$this->token,$data);
    }
    /*
     * 新提交订单
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function new_submit_order(){
        $id = I('post.id');
        $xuni = I('post.xuni');
        if(!$id || (!I('post.address_id') && !$xuni)){
            jsonRespons('false','参数错误',$this->token);
        }

        $user = token_user($this->token);
        //查询到地址信息
        if(!$xuni){
            $address = M('MallAddress')->where(array('id'=>I('post.address_id')))->field('user_name,mobile_phone,one_id,two_id,three_id,four_id,one_name,two_name,three_name,four_name,detail_address')->find();
        }
        //不同商家生成不同订单，试用同一个支付号
        //支付号
        $pay_number = mkpay();
        //留言内容
        $leave = explode(',',I('post.leave'));
        //先查询存在几个商家
        $sku_where['id'] = array('in',$id);
        $sku_list = M('MallCar')->where($sku_where)->field('skuid,business_id')->select();
        //验证所有的京东商品是否可售
        $bus = M('SuBusiness');
        foreach ($sku_list as $key => $value) {
            $jd_type = $bus->where(array('id'=>$value['business_id']))->getfield('jd_type');
            if($jd_type == 1){
                $sku_l .= $value['skuid'].',';
            }
        }
        $jd_token = M('JdToken')->getfield('token');
        if($sku_l){
        	$nokeshou = array();
            $keshou = curl('https://bizapi.jd.com/api/product/check',array('token'=>$jd_token,'skuIds'=>$sku_l),'','json');
            foreach ($keshou['result'] as $key => $value) {
                if($value['saleState'] == 0){
                    jsonRespons('false',$value['name'].'为预售商品，暂不支持下单',$this->token);
                }
                //如果存在不可开增发票产品则记录
                if($value['isCanVAT'] != 1){
                	$sku_list['skuid'] = $value['skuId'];
                	 $sku_business_id = M('MallCar')->where($sku_list)->getfield('business_id');
                	 //用于记录此订单的商家产品内有不可开增发票的标志
                	 $nokeshou[$sku_business_id] = 1;
                }
            }
        }
        $where['id'] = array('in',$id);
        $business_arr = M('MallCar')->where($where)->group('business_id')->field('business_id')->select();
        $car = M('MallCar');
        $product = M('MallProduct');
        
        $order = M('Order');
        $spec = M('MallSpecData');
        $freight = M('MallFreight');
        $freight_list = M('MallFreightList');
        $order->startTrans();
        $orderlist = M('OrderList');
        $is_false = 0;
        $zhifu = 0;
        // $jd_token = M('JdToken')->getfield('token');
        //组装数据
        foreach ($business_arr as $key => $value) {
            $order_data['order_number'] =  mkorder();
            $order_data['pay_number'] =  $pay_number;
            $order_data['user_id'] =  $user['user_id'];
            $order_data['otype'] =  1;
            $order_data['seller_business_id'] =  $value['business_id'];
            //判断此商家是不是京东商家如果是则是京东订单
            $jd_type = $bus->where(array('id'=>$value['business_id']))->getfield('jd_type');
            $order_data['is_jd'] = 0;
            if($jd_type == 1){
                $order_data['is_jd'] = 1;
            }
            $order_data['name'] =  $address['user_name'];
            $order_data['phone'] =  $address['mobile_phone'];
            if(!$xuni){
                $order_data['address'] =  $address['one_name'].$address['two_name'].$address['three_name'].$address['four_name'].$address['detail_address'];
                if($address['one_name'] == '北京' || $address['one_name'] == '上海' || $address['one_name'] == '天津' || $address['one_name'] == '重庆'){
                    $order_data['area'] =  $address['one_name'];
                }else{
                    $order_data['area'] =  $address['two_name'];
                }
                $order_data['address_id'] =  I('post.address_id');
            }else{
                $order_data['jiayoukahao'] = I('post.jiayoukahao');
            }
            $order_data['xuni_type'] = I('post.xuni');
            $order_data['remark'] =  $leave[$key]?:'';
            $order_data['create_date_time'] =  date('Y-m-d H:i:s');
            $order_data['update_time'] =  date('Y-m-d H:i:s');
            $order_data['status'] =  1;
            $order_data['invalid'] =  0;
            //商品数据
            $c_where['id'] = array('in',$id);
            $c_where['business_id'] = array('eq',$value['business_id']);
            $car_list = $car->where($c_where)->select();
            $total_money = 0;
            $pay_money = 0;
            $weight = 0;
            $xd_sku = array();
            $sku = array();
            foreach ($car_list as $key1 => $value1) {
                $product_info = $product->where(array('id'=>$value1['product_id']))->field('status,invalid,is_postage,freight_id')->find();
                $p_info = $spec->where(array('cols1'=>$value1['skuid'],'invalid'=>0))->field('cols4,cols5,cols6')->find();
                $total_money += $p_info['cols4']*$value1['num'];
                $pay_money += $p_info['cols5']*$value1['num'];
                $weight += $p_info['cols6']*$value1['num'];
                //验证一下商品是否下架或删除
                if($product_info['status'] != 1 || $product_info['invalid'] == 1){
                    $order->rollback();
                    $orderlist->rollback();
                    jsonRespons('false','商品已不存在',$this->token);
                }
                $sku[$key1]=array('skuId'=>$value1['skuid'],'num'=>$value1['num']);
                $xd_sku[$key1]=array('skuId'=>$value1['skuid'],'num'=>$value1['num'],'bNeedAnnex'=>true,'bNeedGift'=>false);
                if($order_data['is_jd'] != 1){
                    //计算自营商品运费
                    if($product_info['is_postage'] == 1){
                        $f_moban = $freight->where(array('id'=>$product_info['freight_id']))->find();
                        $f_l_moban = $freight_list->where(array('freight_id'=>$f_moban,'province_name'=>$address['one_name']))->find();
                        if($f_l_moban){
                            if($value1['num'] > 1){
                                $express_fee += $f_l_moban['data2']*1 + ($value1['num']-1)*$f_l_moban['data4']; 
                            }else{
                                $express_fee += $f_l_moban['data2']*1;
                            }
                        }else{
                            if($value1['num'] > 1){
                                $express_fee += $f_moban['data2']*1 + ($value1['num']-1)*$f_moban['data4']; 
                            }else{
                                $express_fee += $f_moban['data2']*1;
                            }
                        }
                    }
                }
                //因为表设计，在这里不能生产添加到子表的数据
            }
            if($order_data['is_jd'] == 1 && !$xuni){
                if($pay_money < 99){
                    $yf['token'] = $jd_token;
                    $yf['sku'] = json_encode($sku);
                    $yf['province'] = $address['one_id'];
                    $yf['city'] = $address['two_id'];
                    $yf['county'] = $address['three_id'];
                    $yf['town'] = $address['four_id'];
                    $yf['paymentType'] = 4;
                    $return = curl('https://bizapi.jd.com/api/order/getFreight',$yf,'','json');
                    if($return['success']){
                        $express_fee = $return['result']['freight'];
                    }else{
                        jsonRespons('false','运费查询失败',$this->token);
                    }
                }else{
                    //满99包邮
                    $express_fee = 0;
                }
                
            }elseif(!$xuni){
                //计算运费
                // $express_fee = get_express_fee($weight,$pay_money,$order_data['area']);
                $express_fee = $express_fee;
            }
            $pay_money += $express_fee;
            $order_data['express_fee'] =  $express_fee;
            $order_data['pay'] =  $pay_money;
            $order_data['amount'] =  $total_money;
            $zhifu += $pay_money;
            $order_id = $order->lock(true)->add($order_data);
            if($order_id){
                foreach ($car_list as $key2 => $value2) {
                    $title = $product->where(array('id'=>$value2['product_id']))->getfield('title');
                    $series = $product->where(array('id'=>$value2['product_id']))->getfield('series');
                    $product_info = $spec->where(array('cols1'=>$value2['skuid'],'invalid'=>0))->field('cols7,cols5,cols6,cols8,cols9,cols10,cols11,cols12,cols13,cols14,cols15,cols16,cols17,cols18,cols19,cols20')->find();
                    //组装订单子表数据
                    $order_order_list['order_id'] = $order_id;
                    $order_order_list['skuid'] = $value2['skuid'];
                    $order_order_list['series'] = $series;
                    $order_order_list['product_id'] = $value2['product_id'];
                    $order_order_list['nums'] = $value2['num'];
                    $order_order_list['weight'] = $product_info['cols6'];
                    $order_order_list['price'] = $product_info['cols5'];
                    $order_order_list['title'] = $title;
                    $order_order_list['thumb_url'] = $product_info['cols7'];
                    $order_order_list['specification'] = $product_info['cols8'].$product_info['cols9'].$product_info['cols10'].$product_info['cols11'].$product_info['cols12'].$product_info['cols13'].$product_info['cols14'].$product_info['cols15'].$product_info['cols16'].$product_info['cols17'].$product_info['cols18'].$product_info['cols19'].$product_info['cols20'];
                    $order_order_list['create_date_time'] = date('Y-m-d H:i:s');
                    $order_order_list['update_time'] = date('Y-m-d H:i:s');
                    $order_order_list['status'] = 1;
                    $order_order_list['invalid'] = 0;
                    $return =$orderlist->lock(true)->add($order_order_list);
                    if(!$return){
                         $is_false = 1;
                    }
                }
                if($is_false != 1 && $order_data['is_jd'] == 1){
                    //去京东下单
                    //发票类型  如果存在不可开增发票的标志则是1
                    $fp_type = 2;
                    if($nokeshou[$value['business_id']] == 1){
                    	$fp_type = 1;
                    }
                    $jd_order = $this->xiadan($order_data['order_number'],I('post.address_id'),$order_data['remark'],$xd_sku,$fp_type);
                    $jd_order['result']['jdOrderId']=123456789;
                    $order->where(array('id'=>$order_id))->setField(array('jd_number'=>$jd_order['result']['jdOrderId']));
                }
            }else{
                $is_false = 1;
            }
            
        }
        if($is_false == 1){
            //说明之前操作数据库有失败的
            $order->rollback();
            $orderlist->rollback();
            jsonRespons('false','下单失败',$this->token);
        }else{
            echo 11;exit;
            $car->where($where)->setField(array('invalid'=>1));
            $order->commit();
            $orderlist->commit();
            jsonRespons('true','',$this->token,array('pay_number'=>$pay_number,'pay_money'=>$zhifu));
        }
    }

   /*
     * 加红包提交订单
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function new_submit_orders(){
        $id = I('post.id');
        $xuni = I('post.xuni');
        if(!$id || (!I('post.address_id') && !$xuni)){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
        $mobile = M('SuUser')->where(array('id'=>$user['user_id']))->getfield('mobile');
        //查询到地址信息
        if(!$xuni){
            $address = M('MallAddress')->where(array('id'=>I('post.address_id')))->field('id,user_name,mobile_phone,one_id,two_id,three_id,four_id,one_name,two_name,three_name,four_name,detail_address')->find();
        }
        //不同商家生成不同订单，试用同一个支付号
        //支付号
        $pay_number = mkpay();
        //留言内容
        $leave = explode(',',I('post.leave'));
        //红包
        $red = explode(',',I('post.redId'));
        //先查询存在几个商家
        $sku_where['id'] = array('in',$id);
        $sku_list = M('MallCar')->where($sku_where)->field('skuid,business_id')->select();
        //验证所有的京东商品是否可售
        $bus = M('SuBusiness');
        foreach ($sku_list as $key => $value) {
            $jd_type = $bus->where(array('id'=>$value['business_id']))->getfield('jd_type');
            if($jd_type == 1){
                $sku_l .= $value['skuid'].',';
            }
        }
        $jd_token = M('JdToken')->getfield('token');
        if($sku_l){
            $nokeshou = array();
            $keshou = curl('https://bizapi.jd.com/api/product/check',array('token'=>$jd_token,'skuIds'=>$sku_l),'','json');
            foreach ($keshou['result'] as $key => $value) {
                if($value['saleState'] == 0){
                    jsonRespons('false',$value['name'].'为预售商品，暂不支持下单',$this->token);
                }
                //如果存在不可开增发票产品则记录
                if($value['isCanVAT'] != 1){
                     $sku_list['skuid'] = $value['skuId'];
                     $sku_business_id = M('MallCar')->where($sku_list)->getfield('business_id');
                     // echo $sku_business_id;exit;
                     //用于记录此订单的商家产品内有不可开增发票的标志
                     $nokeshou[$sku_business_id] = 1;
                }
            }
        }
        // echo $nokeshou[23];exit;
        $where['id'] = array('in',$id);
        $business_arr = M('MallCar')->where($where)->group('business_id')->field('business_id')->select();
        $car = M('MallCar');
        $product = M('MallProduct');
        $order = M('Order');
        $spec = M('MallSpecData');
        $freight = M('MallFreight');
        $freight_list = M('MallFreightList');
        $redPacked = M('SuRedPacket');
        $redType = M('SuRedType');
        $order->startTrans();
        $orderlist = M('OrderList');
        $is_false = 0;
        $zhifu = 0;
        $jd_token = M('JdToken')->getfield('token');
        //组装数据
        foreach ($business_arr as $key => $value) {
            $order_data['order_number'] =  mkorder();
            $order_data['pay_number'] =  $pay_number;
            $order_data['user_id'] =  $user['user_id'];
            $order_data['otype'] =  1;
            $order_data['seller_business_id'] =  $value['business_id'];
            //判断此商家是不是京东商家如果是则是京东订单
            $jd_type = $bus->where(array('id'=>$value['business_id']))->getfield('jd_type');
            $order_data['is_jd'] = '0';
            if($jd_type == 1){
                $order_data['is_jd'] = '1';
            }
            $order_data['name'] =  $address['user_name'];
            $order_data['phone'] =  $address['mobile_phone'];
            if(!$xuni){
                $order_data['address'] =  $address['one_name'].$address['two_name'].$address['three_name'].$address['four_name'].$address['detail_address'];
                if($address['one_name'] == '北京' || $address['one_name'] == '上海' || $address['one_name'] == '天津' || $address['one_name'] == '重庆'){
                    $order_data['area'] =  $address['one_name'];
                }else{
                    $order_data['area'] =  $address['two_name'];
                }
            }else{
                $order_data['jiayouka'] = I('post.jiayoukahao');
            }
            $order_data['address_id'] =  I('post.address_id');
            $order_data['xuni_type'] = I('post.xuni');
            $order_data['remark'] =  $leave[$key]?:'';
            $order_data['create_date_time'] =  date('Y-m-d H:i:s');
            $order_data['update_time'] =  date('Y-m-d H:i:s');
            $order_data['status'] =  1;
            $order_data['invalid'] =  0;
            //商品数据
            $c_where['id'] = array('in',$id);
            $c_where['business_id'] = array('eq',$value['business_id']);
            $car_list = $car->where($c_where)->select();
            $total_money = 0;
            $pay_money = 0;
            $weight = 0;
            $xd_sku = array();
            $sku = array();
            foreach ($car_list as $key1 => $value1) {
                $product_info = $product->where(array('id'=>$value1['product_id']))->field('status,invalid,is_postage,freight_id,series')->find();
                $p_info = $spec->where(array('cols1'=>$value1['skuid'],'invalid'=>0))->field('cols4,cols5,cols6')->find();
                $total_money += $p_info['cols4']*$value1['num'];
                $pay_money += $p_info['cols5']*$value1['num'];
                $weight += $p_info['cols6']*$value1['num'];
                //验证一下商品是否下架或删除
                if($product_info['status'] != 1 || $product_info['invalid'] == 1){
                    $order->rollback();
                    $orderlist->rollback();
                    jsonRespons('false','商品已不存在',$this->token);
                }
                $sku[$key1]=array('skuId'=>$value1['skuid'],'num'=>$value1['num']);
                $xd_sku[$key1]=array('skuId'=>$value1['skuid'],'num'=>$value1['num'],'bNeedAnnex'=>true,'bNeedGift'=>false);
                if($order_data['is_jd'] != 1){
                    //计算自营商品运费
                    if($product_info['is_postage'] == 1){
                        $f_moban = $freight->where(array('id'=>$product_info['freight_id']))->find();
                        $f_l_moban = $freight_list->where(array('freight_id'=>$f_moban,'province_name'=>$address['one_name']))->find();
                        if($f_l_moban){
                            if($value1['num'] > 1){
                                $express_fee += $f_l_moban['data2']*1 + ($value1['num']-1)*$f_l_moban['data4']; 
                            }else{
                                $express_fee += $f_l_moban['data2']*1;
                            }
                        }else{
                            if($value1['num'] > 1){
                                $express_fee += $f_moban['data2']*1 + ($value1['num']-1)*$f_moban['data4']; 
                            }else{
                                $express_fee += $f_moban['data2']*1;
                            }
                        }
                    }
                }
                //因为表设计，在这里不能生产添加到子表的数据
            }
            if($order_data['is_jd'] == 1 && !$xuni){
                if($pay_money < 99){
                    $yf['token'] = $jd_token;
                    $yf['sku'] = json_encode($sku);
                    $yf['province'] = $address['one_id'];
                    $yf['city'] = $address['two_id'];
                    $yf['county'] = $address['three_id'];
                    $yf['town'] = $address['four_id'];
                    $yf['paymentType'] = 4;
                    $return = curl('https://bizapi.jd.com/api/order/getFreight',$yf,'','json');
                    if($return['success']){
                        $express_fee = $return['result']['freight'];
                    }else{
                        jsonRespons('false','运费查询失败',$this->token);
                    }
                }else{
                    //满99包邮
                    $express_fee = 0;
                }
            }elseif(!$xuni){
                //计算运费
                // $express_fee = get_express_fee($weight,$pay_money,$order_data['area']);
                $express_fee = $express_fee;
            }
            
            $pay_money += $express_fee;
            $order_data['express_fee'] =  $express_fee;
            $order_data['pay'] =  $pay_money;
            $order_data['amount'] =  $total_money;
            //红包验证
            if($red[$key]){
            	$hongbao = $redPacked->where(array('id'=>$red[$key]))->field('mobile,red_type,use_time,status')->find();
            	if($status == 2){
            		jsonRespons('false','该红包已使用',$this->token);
            	}
            	if(strtotime($hongbao['use_time']) < time()){
            		jsonRespons('false','红包已过期',$this->token);
            	}
            	if($hongbao['mobile'] != $mobile){
            		jsonRespons('false','红包只能本人使用',$this->token);
            	}
            	$red_leixing = $redType->where(array('id'=>$hongbao['red_type']))->field('money,condition')->find();
            	if($pay_money < $red_leixing['condition']){
            		jsonRespons('false','红包满'.$red_leixing['condition'].'使用',$this->token);
            	}
            	$pay_money = $pay_money-$red_leixing['money'];
            	$order_data['pay'] =  $pay_money;
            	//修改红包状态
            	$red_status = $redPacked->where(array('id'=>$red[$key]))->lock(true)->setField(array('status'=>2,'update_time'=>date('Y-m-d H:i:s')));
            	if(!$red_status){
            		$redPacked->rollback();
		            $order->rollback();
		            $orderlist->rollback();
		            jsonRespons('false','红包状态修改失败',$this->token);
            	}
            }
            $order_data['red_packet_id'] =  $red[$key];
            $zhifu += $pay_money;
            $order_id = $order->lock(true)->add($order_data);
            if($order_id){
                foreach ($car_list as $key2 => $value2) {
                    $title = $product->where(array('id'=>$value2['product_id']))->getfield('title');
                    $series = $product->where(array('id'=>$value2['product_id']))->getfield('series');
                    $product_info = $spec->where(array('cols1'=>$value2['skuid'],'invalid'=>0))->field('cols7,cols5,cols6,cols8,cols9,cols10,cols11,cols12,cols13,cols14,cols15,cols16,cols17,cols18,cols19,cols20')->find();
                    //组装订单子表数据
                    if(!$series){
                        jsonRespons('false','',$this->token);eixt;
                    }
                    $order_order_list['series'] = $series;                        
                    $order_order_list['order_id'] = $order_id;
                    $order_order_list['skuid'] = $value2['skuid'];

                    $order_order_list['product_id'] = $value2['product_id'];
                    $order_order_list['nums'] = $value2['num'];
                    $order_order_list['weight'] = $product_info['cols6'];
                    $order_order_list['price'] = $product_info['cols5'];
                    $order_order_list['title'] = $title;
                    $order_order_list['thumb_url'] = $product_info['cols7'];
                    $order_order_list['specification'] = $product_info['cols8'].$product_info['cols9'].$product_info['cols10'].$product_info['cols11'].$product_info['cols12'].$product_info['cols13'].$product_info['cols14'].$product_info['cols15'].$product_info['cols16'].$product_info['cols17'].$product_info['cols18'].$product_info['cols19'].$product_info['cols20'];
                    $order_order_list['create_date_time'] = date('Y-m-d H:i:s');
                    $order_order_list['update_time'] = date('Y-m-d H:i:s');
                    $order_order_list['status'] = 1;
                    $order_order_list['invalid'] = 0;
                    $return =$orderlist->lock(true)->add($order_order_list);
                    if(!$return){
                         $is_false = 1;
                    }
                }
                if($is_false != 1 && $order_data['is_jd'] == 1){
                    //去京东下单
                    //发票类型  如果存在不可开增发票的标志则是1
                    $fp_type = 2;
                    if($nokeshou[$value['business_id']] == 1){
                        $fp_type = 1;
                    }
                    // $jd_order = $this->xiadan($order_data['order_number'],I('post.address_id'),$order_data['remark'],$xd_sku,$fp_type);
                    $jd_order['result']['jdOrderId'] = 123456;
                    if($jd_order['result']['jdOrderId']){
                        $order->where(array('id'=>$order_id))->setField(array('jd_number'=>$jd_order['result']['jdOrderId']));
                    }else{
                        $is_false = 1;
                    }
                    
                }
            }else{
                $is_false = 1;
            }
            
        }
        if($is_false == 1){
            //说明之前操作数据库有失败的
            $redPacked->rollback();
            $order->rollback();
            $orderlist->rollback();
            jsonRespons('false','下单失败',$this->token);
        }else{
            $car->where($where)->setField(array('invalid'=>1));
            $redPacked->commit();
            $order->commit();
            $orderlist->commit();
            jsonRespons('true','',$this->token,array('pay_number'=>$pay_number,'pay_money'=>$zhifu));
        }
    }

    /*
     * 提交订单合并子订单修改(改)
     * @date: 2016-12-5 下午5:55:01
     * @editor: YU
     */
    public function new_submit_orders1(){
        $id = I('post.id');
        $xuni = I('post.xuni');
        if(!$id || (!I('post.address_id') && !$xuni)){
            jsonRespons('false','参数错误',$this->token);
        }
        $user = token_user($this->token);
        $mobile = M('SuUser')->where(array('id'=>$user['user_id']))->getfield('mobile');
        //查询到地址信息
        if(!$xuni){
            $address = M('MallAddress')->where(array('id'=>I('post.address_id')))->field('id,user_name,mobile_phone,one_id,two_id,three_id,four_id,one_name,two_name,three_name,four_name,detail_address')->find();
        }
        //不同商家生成不同订单，试用同一个支付号
        //支付号
        $pay_number = mkpay();
        //留言内容
        $leave = explode(',',I('post.leave'));
        //红包
        $red = explode(',',I('post.redId'));
        //先查询存在几个商家
        $sku_where['id'] = array('in',$id);
        $sku_list = M('MallCar')->where($sku_where)->field('skuid,business_id')->select();
        //验证所有的京东商品是否可售
        $bus = M('SuBusiness');
        foreach ($sku_list as $key => $value) {
            $jd_type = $bus->where(array('id'=>$value['business_id']))->getfield('jd_type');
            if($jd_type == 1){
                $sku_l .= $value['skuid'].',';
            }
        }
        $jd_token = M('JdToken')->getfield('token');
        if($sku_l){
            $nokeshou = array();
            $keshou = curl('https://bizapi.jd.com/api/product/check',array('token'=>$jd_token,'skuIds'=>$sku_l),'','json');
            foreach ($keshou['result'] as $key => $value) {
                if($value['saleState'] == 0){
                    jsonRespons('false',$value['name'].'为预售商品，暂不支持下单',$this->token);
                }
                //如果存在不可开增发票产品则记录
                if($value['isCanVAT'] != 1){
                     $sku_list['skuid'] = $value['skuId'];
                     $sku_business_id = M('MallCar')->where($sku_list)->getfield('business_id');
                     // echo $sku_business_id;exit;
                     //用于记录此订单的商家产品内有不可开增发票的标志
                     $nokeshou[$sku_business_id] = 1;
                }
            }
        }
        // echo $nokeshou[23];exit;
        $where['id'] = array('in',$id);
        $business_arr = M('MallCar')->where($where)->group('business_id')->field('business_id')->select();
        $car = M('MallCar');
        $product = M('MallProduct');
        $order = M('Order');
        $spec = M('MallSpecData');
        $freight = M('MallFreight');
        $freight_list = M('MallFreightList');
        $redPacked = M('SuRedPacket');
        $redType = M('SuRedType');
        $order->startTrans();
        $orderlist = M('OrderList');
        $is_false = 0;
        $zhifu = 0;
        $jd_token = M('JdToken')->getfield('token');
        //组装数据
        foreach ($business_arr as $key => $value) {
            $order_data['order_number'] =  mkorder();
            $order_data['pay_number'] =  $pay_number;
            $order_data['user_id'] =  $user['user_id'];
            $order_data['otype'] =  1;
            $order_data['seller_business_id'] =  $value['business_id'];
            //判断此商家是不是京东商家如果是则是京东订单
            $jd_type = $bus->where(array('id'=>$value['business_id']))->getfield('jd_type');
            $order_data['is_jd'] = '0';
            if($jd_type == 1){
                $order_data['is_jd'] = '1';
            }
            $order_data['name'] =  $address['user_name'];
            $order_data['phone'] =  $address['mobile_phone'];
            if(!$xuni){
                $order_data['address'] =  $address['one_name'].$address['two_name'].$address['three_name'].$address['four_name'].$address['detail_address'];
                if($address['one_name'] == '北京' || $address['one_name'] == '上海' || $address['one_name'] == '天津' || $address['one_name'] == '重庆'){
                    $order_data['area'] =  $address['one_name'];
                }else{
                    $order_data['area'] =  $address['two_name'];
                }
            }else{
                $order_data['jiayouka'] = I('post.jiayoukahao');
            }
            $order_data['address_id'] =  I('post.address_id');
            $order_data['xuni_type'] = I('post.xuni');
            $order_data['remark'] =  $leave[$key]?:'';
            $order_data['create_date_time'] =  date('Y-m-d H:i:s');
            $order_data['update_time'] =  date('Y-m-d H:i:s');
            $order_data['status'] =  1;
            $order_data['invalid'] =  0;
            //商品数据
            $c_where['id'] = array('in',$id);
            $c_where['business_id'] = array('eq',$value['business_id']);
            $car_list = $car->where($c_where)->select();
            $total_money = 0;
            $pay_money = 0;
            $weight = 0;
            $xd_sku = array();
            $sku = array();
            foreach ($car_list as $key1 => $value1) {
                $product_info = $product->where(array('id'=>$value1['product_id']))->field('status,invalid,is_postage,freight_id')->find();
                $p_info = $spec->where(array('cols1'=>$value1['skuid'],'invalid'=>0))->field('cols4,cols5,cols6')->find();
                $total_money += $p_info['cols4']*$value1['num'];
                $pay_money += $p_info['cols5']*$value1['num'];
                $weight += $p_info['cols6']*$value1['num'];
                //验证一下商品是否下架或删除
                if($product_info['status'] != 1 || $product_info['invalid'] == 1){
                    $order->rollback();
                    $orderlist->rollback();
                    jsonRespons('false','商品已不存在',$this->token);
                }
                $sku[$key1]=array('skuId'=>$value1['skuid'],'num'=>$value1['num']);
                $xd_sku[$key1]=array('skuId'=>$value1['skuid'],'num'=>$value1['num'],'bNeedAnnex'=>true,'bNeedGift'=>false);
                if($order_data['is_jd'] != 1){
                    //计算自营商品运费
                    if($product_info['is_postage'] == 1){
                        $f_moban = $freight->where(array('id'=>$product_info['freight_id']))->find();
                        $f_l_moban = $freight_list->where(array('freight_id'=>$f_moban,'province_name'=>$address['one_name']))->find();
                        if($f_l_moban){
                            if($value1['num'] > 1){
                                $express_fee += $f_l_moban['data2']*1 + ($value1['num']-1)*$f_l_moban['data4']; 
                            }else{
                                $express_fee += $f_l_moban['data2']*1;
                            }
                        }else{
                            if($value1['num'] > 1){
                                $express_fee += $f_moban['data2']*1 + ($value1['num']-1)*$f_moban['data4']; 
                            }else{
                                $express_fee += $f_moban['data2']*1;
                            }
                        }
                    }
                }
                //因为表设计，在这里不能生产添加到子表的数据
            }
            if($order_data['is_jd'] == 1 && !$xuni){
                if($pay_money < 99){
                    $yf['token'] = $jd_token;
                    $yf['sku'] = json_encode($sku);
                    $yf['province'] = $address['one_id'];
                    $yf['city'] = $address['two_id'];
                    $yf['county'] = $address['three_id'];
                    $yf['town'] = $address['four_id'];
                    $yf['paymentType'] = 4;
                    $return = curl('https://bizapi.jd.com/api/order/getFreight',$yf,'','json');
                    if($return['success']){
                        $express_fee = $return['result']['freight'];
                    }else{
                        jsonRespons('false','运费查询失败',$this->token);
                    }
                }else{
                    //满99包邮
                    $express_fee = 0;
                }
            }elseif(!$xuni){
                //计算运费
                // $express_fee = get_express_fee($weight,$pay_money,$order_data['area']);
                $express_fee = $express_fee;
            }
            
            $pay_money += $express_fee;
            $order_data['express_fee'] =  $express_fee;
            $order_data['pay'] =  $pay_money;
            $order_data['amount'] =  $total_money;
            //红包验证
            if($red[$key]){
                $hongbao = $redPacked->where(array('id'=>$red[$key]))->field('mobile,red_type,use_time,status')->find();
                if($status == 2){
                    jsonRespons('false','该红包已使用',$this->token);
                }
                if(strtotime($hongbao['use_time']) < time()){
                    jsonRespons('false','红包已过期',$this->token);
                }
                if($hongbao['mobile'] != $mobile){
                    jsonRespons('false','红包只能本人使用',$this->token);
                }
                $red_leixing = $redType->where(array('id'=>$hongbao['red_type']))->field('money,condition')->find();
                if($pay_money < $red_leixing['condition']){
                    jsonRespons('false','红包满'.$red_leixing['condition'].'使用',$this->token);
                }
                $pay_money = $pay_money-$red_leixing['money'];
                $order_data['pay'] =  $pay_money;
                //修改红包状态
                $red_status = $redPacked->where(array('id'=>$red[$key]))->lock(true)->setField(array('status'=>2,'update_time'=>date('Y-m-d H:i:s')));
                if(!$red_status){
                    $redPacked->rollback();
                    $order->rollback();
                    $orderlist->rollback();
                    jsonRespons('false','红包状态修改失败',$this->token);
                }
            }
            $order_data['red_packet_id'] =  $red[$key];
            $zhifu += $pay_money;
            $order_id = $order->lock(true)->add($order_data);
            if($order_id){
                foreach ($car_list as $key2 => $value2) {
                    $title = $product->where(array('id'=>$value2['product_id']))->getfield('title');
                    $series= $product->where(array('id'=>$value2['product_id']))->getfield('series');
                    $product_info = $spec->where(array('cols1'=>$value2['skuid'],'invalid'=>0))->field('cols7,cols5,cols6,cols8,cols9,cols10,cols11,cols12,cols13,cols14,cols15,cols16,cols17,cols18,cols19,cols20')->find();
                    //组装订单子表数据
                    $order_order_list['order_id'] = $order_id;
                    $order_order_list['skuid'] = $value2['skuid'];
                    $order_order_list['series'] = $series;
                    $order_order_list['product_id'] = $value2['product_id'];
                    $order_order_list['nums'] = $value2['num'];
                    $order_order_list['weight'] = $product_info['cols6'];
                    $order_order_list['price'] = $product_info['cols5'];
                    $order_order_list['title'] = $title;
                    $order_order_list['thumb_url'] = $product_info['cols7'];
                    $order_order_list['specification'] = $product_info['cols8'].$product_info['cols9'].$product_info['cols10'].$product_info['cols11'].$product_info['cols12'].$product_info['cols13'].$product_info['cols14'].$product_info['cols15'].$product_info['cols16'].$product_info['cols17'].$product_info['cols18'].$product_info['cols19'].$product_info['cols20'];
                    $order_order_list['create_date_time'] = date('Y-m-d H:i:s');
                    $order_order_list['update_time'] = date('Y-m-d H:i:s');
                    $order_order_list['status'] = 1;
                    $order_order_list['invalid'] = 0;
                    $return =$orderlist->lock(true)->add($order_order_list);
                    if(!$return){
                         $is_false = 1;
                    }
                }
                if($is_false != 1 && $order_data['is_jd'] == 1){
                    //去京东下单
                    //发票类型  如果存在不可开增发票的标志则是1
                    $fp_type = 2;
                    if($nokeshou[$value['business_id']] == 1){
                        $fp_type = 1;
                    }
                    // $jd_order = $this->xiadan($order_data['order_number'],I('post.address_id'),$order_data['remark'],$xd_sku,$fp_type);
                    $jd_order['result']['jdOrderId'] = 123456;
                    if($jd_order['result']['jdOrderId']){
                        $order->where(array('id'=>$order_id))->setField(array('jd_number'=>$jd_order['result']['jdOrderId']));
                    }else{
                        $is_false = 1;
                    }
                    
                }
            }else{
                $is_false = 1;
            }
            
        }
        if($is_false == 1){
            //说明之前操作数据库有失败的
            $redPacked->rollback();
            $order->rollback();
            $orderlist->rollback();
            jsonRespons('false','下单失败',$this->token);
        }else{
            $car->where($where)->setField(array('invalid'=>1));
            $redPacked->commit();
            $order->commit();
            $orderlist->commit();
            jsonRespons('true','',$this->token,array('pay_number'=>$pay_number,'pay_money'=>$zhifu));
        }
    } 

}