<?php
namespace Home\Controller;
use Think\Controller;
class DownController extends Controller {
	public function index(){
		header("Location: http://a.app.qq.com/o/simple.jsp?pkgname=com.duolabao");
	}
 }