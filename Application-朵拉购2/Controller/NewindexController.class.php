<?php
namespace Test\Controller;
use Think\Controller;
class NewindexController extends Controller {
    // public $token;
    public function __construct(){
      // if(!I('post.token')){
      //   jsonRespons('false','请传入token');
      // }
      // $this->token = token_check(I('post.token'));
    }

    /*
     * 人气推荐
     * 
     */
    public function rqtj(){
    	$token = I('post.token');
    }
    /*
     * 天天特价
     * 
     */
    public function tttj(){
    	$token = I('post.token');
    }

    /*
     * 专题精选
     * 
     */
    public function ztjx(){
    	$token = I('post.token');
    }
}    