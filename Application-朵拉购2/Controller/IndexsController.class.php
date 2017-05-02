<?php
namespace Home\Controller;
header('Access-Control-Allow-Origin:*');
use Think\Controller;
class IndexsController extends Controller {

    public function index(){
    	$this->display();
    }

    public function make_info(){
    	$data['name']=I("post.name");
    	$data['tel_phone']=I("post.tel_phone");
    	$data['business']=I("post.business");
    	$data['brand']=I("post.brand");
    	$data['mail']=I("post.mail");
    	$data['creat_date_time']=date('Y-m-d H:i:s');
    	$id=M('ApplyInfo')->add($data);
    	if($id){
    		echo json_encode(array('code'=>true,'msg'=>''));
    	}else{
    		echo json_encode(array('code'=>false,'msg'=>''));
    	}
    }

}    