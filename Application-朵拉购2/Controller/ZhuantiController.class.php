<?php
namespace Test\Controller;
use Think\Controller;
class ZhuantiController extends Controller {
    public function index(){

        $this->display();
    }

    // public function get_product(){
    // 	$token = I('post.token');
    // 	//取出二十个商品的id
    // 	$list=M('MallProduct')->where('status=1')->field('id')->limit(20)->select();
    // 	dump($list);
    // 	jsonRespons('true','',$token,$list);
    // }

    public function tuijian(){
    	$token = I('post.token');

    	$list = M('MallProduct')->where('status=1')->field('id,title,thumb_url,discount_price')->limit(6)->select();
    	//dump($list);die;
    	jsonRespons('true','',$token,$list);
    }
}    