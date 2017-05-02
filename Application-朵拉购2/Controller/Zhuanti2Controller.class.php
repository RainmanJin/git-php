<?php
namespace Home\Controller;
use Think\Controller;
class Zhuanti2Controller extends Controller {
    public function index(){
    	$zz=array("901350","2644272","299422","2045288","3503890","1595870","822891","617635","615191","1741228","1265592","1451205","850073","1121491","2047076","3480397",);
    	$xmt=array("794906","934696","794881","1234403","906893","794901","1158163","769097","1158162","794919","1160140","2313137",);
    	$gat=array("763070","1454578","856850","698234","909010","909012","909013","647795","1368762","909012","880292","909004","908997",);
    	$om=array("1035054","1761874","3723872","2149293","319860","2658756","327492","1256736","680521","1533892","327492","319870","3261552",);
    	$gd=array("2355372","508388","1018781","1043781","2837142","1043780","1924698","1168231","1264680","1278087","906652","3617410",);  	
    	$where_1['cols1']=array('in',$zz);
    	$where_2['cols1']=array('in',$xmt);
    	$where_3['cols1']=array('in',$gat);
    	$where_4['cols1']=array('in',$om);
    	$where_5['cols1']=array('in',$gd);
    	$price_zz=M('MallSpecData')->join('tbl_mall_product on tbl_mall_product.id = tbl_mall_spec_data.product_id')->where($where_1)->field('cols1,discount_price,thumb_url,product_id,title')->select();
    	$price_xmt=M('MallSpecData')->join('tbl_mall_product on tbl_mall_product.id = tbl_mall_spec_data.product_id')->where($where_2)->field('cols1,discount_price,thumb_url,product_id,title')->select();
    	$price_gat=M('MallSpecData')->join('tbl_mall_product on tbl_mall_product.id = tbl_mall_spec_data.product_id')->where($where_3)->field('cols1,discount_price,thumb_url,product_id,title')->select();
    	$price_om=M('MallSpecData')->join('tbl_mall_product on tbl_mall_product.id = tbl_mall_spec_data.product_id')->where($where_4)->field('cols1,discount_price,thumb_url,product_id,title')->select();
    	$price_gd=M('MallSpecData')->join('tbl_mall_product on tbl_mall_product.id = tbl_mall_spec_data.product_id')->where($where_5)->field('cols1,discount_price,thumb_url,product_id,title')->select();
    	$this->assign('price_zz',$price_zz);
    	$this->assign('price_xmt',$price_xmt);
    	$this->assign('price_gat',$price_gat);
    	$this->assign('price_om',$price_om);
    	$this->assign('price_gd',$price_gd);   	    	    	
        $this->display();
    }

    public function get_red_id(){
        $sql="select `id` from tbl_su_red_packet where mobile is null and money=10 order by rand() limit 1";
        $red=M('SuRedPacket')->query($sql);
        echo json_encode(array('id'=>$red[0]['id'],'msg'=>'获取成功'));exit;
    }

    public function check(){
    	//检查手机验证码
		$mobile =I('mobiles');
		$yzm =I('yzm');
		$hb_id=I('hb_id');
		$is=M('SuRedPacket')->where(array('mobile'=>$mobile))->field('id')->find();
		$code=M('SuSmsLog')->where(array('mobile'=>$mobile))->field('code')->find();
		if(!isset($yzm)){
			//验证码错误
			echo json_encode(array('code'=>3,'msg'=>''));exit;
		}
		if($code['code']==$yzm){
            $num=M('SuRedPacket')->where(array('mobile'=>$mobile))->count();
            //dump($num);die;
            if($num<2){
            //领取成功
                $data['mobile']=$mobile;
                $data['start_time']=date('Y-m-d H:i:s');
                $day=M('SuRedPacket')->join('tbl_su_red_type on tbl_su_red_packet.red_type = tbl_su_red_type.id')->where(array('tbl_su_red_packet.id'=>$hb_id))->field('tbl_su_red_type.day')->find();
                $data['use_time']=date("Y-m-d H:i:s",strtotime("+".$day['day']." day"));
                $hb=M('SuRedPacket')->where(array('id'=>$hb_id))->save($data);
                if($hb){
                    echo json_encode(array('code'=>2,'msg'=>''));exit;
                }   
            }else{
		        //不能领取红包
                echo json_encode(array('code'=>1,'msg'=>''));exit;
		}}else{
			//验证码错误
			echo json_encode(array('code'=>3,'msg'=>''));exit;
		}
    }

}    