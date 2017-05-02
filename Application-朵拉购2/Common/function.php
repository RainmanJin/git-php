<?php 
/*
 * @朵拉宝
 * @功能说明：框架层多应用函数库
 * @更新说明：暂无更新
 * @文件名 function.php
 * @编码 UTF-8
 * @创建时间 2016-12-1 上午10:41:42
 * @创建人 YU
 */
/*
 * 阿里大于短信
 * @date: 2016-12-1上午10:55:01
 * @editor: YU
 */
if(!function_exists('dayu_Sms')){
	function dayu_Sms($bianliang,$val,$mobile,$muban){
		vendor('dayu.TopSdk');
		$c = new \TopClient;
		$c->appkey = '23416512';
		$c->secretKey = 'd27f279256f9415cebf0c09883439d79';
		$req = new \AlibabaAliqinFcSmsNumSendRequest;
		$req->setSmsType("normal");
		$req->setSmsFreeSignName("朵拉管家");
		$req->setSmsParam("{\"".$bianliang."\":\"".$val."\"}");
		$req->setRecNum($mobile);
		$req->setSmsTemplateCode($muban);
		$resp = $c->execute($req);
		$resp = objectToArray($resp);
		return $resp;
	}
}
/*
 * 阿里大于短信
 * @date: 2016-12-1上午10:55:01
 * @editor: YU
 */
if(!function_exists('dayu_Sms1')){
	function dayu_Sms1($bianliang,$val,$mobile,$muban){
		vendor('gou_dayu.TopSdk');
		$c = new \TopClient;
		$c->appkey = '23587750';
		$c->secretKey = 'a88934a5832483f8858ea4c3e5fa326e';
		$req = new \AlibabaAliqinFcSmsNumSendRequest;
		$req->setSmsType("normal");
		$req->setSmsFreeSignName("朵拉购");
		$req->setSmsParam("{\"".$bianliang."\":\"".$val."\"}");
		$req->setRecNum($mobile);
		$req->setSmsTemplateCode($muban);
		$resp = $c->execute($req);
		$resp = objectToArray($resp);
		return $resp;
	}
}
/*
 * 根据token查询用户信息
 * @date: 2016-12-1上午10:55:01
 * @editor: YU
 */
if(!function_exists('token_user')){
	function token_user($token){
		$user_id = M('SuBusinessToken')->where(array('token'=>$token))->find();
		return $user_id;
	}
}
/*
 * token验证
 * @date: 2016-12-1上午10:55:01
 * @editor: YU
 */
if(!function_exists('token_check')){
	function token_check($token){
		$token = M('SuBusinessToken')->where(array('token'=>$token))->getfield('token');
		if(!$token){
			jsonRespons('false','请重新登录');exit;
		}
		return $token;
	}
}
/*
 * 生成UUID
 * @date: 2015-3-21下午1:55:01
 * @editor: YU
 * @param：$prefix前缀信息
 */
if(!function_exists('create_uuid')){
	function create_uuid($prefix=''){
		$str = md5(uniqid(mt_rand(), true));   
	    $uuid  = substr($str,0,8);   
	    $uuid .= substr($str,8,4);   
	    $uuid .= substr($str,12,4);   
	    $uuid .= substr($str,16,4);   
	    $uuid .= substr($str,20,12);   
    	return $prefix . $uuid;
	}
}
/*
 * JSON串统一返回出口
 * @date: 2016-12-1 上午10:41:42
 * @editor: YU
 */
if(!function_exists('jsonRespons')){
	function jsonRespons($success='false',$message='',$token='',$result=''){
		echo json_encode(array(
			'success' => $success,
			'message' => $message?:'',	
			'token' => $token?:'',
			'result' => $result
		));exit;
	}
}
/*
 * 对象转数组
 * @date: 2016-12-1下午1:33:12
 * @editor: YU
 */
function objectToArray($e){
	$e=(array)$e;
	foreach($e as $k=>$v){
		if( gettype($v)=='resource' ) return;
		if( gettype($v)=='object' || gettype($v)=='array' )
			$e[$k]=(array)objectToArray($v);
	}
	return $e;
}
/**
*  @desc 根据两点间的经纬度计算距离
*  @param float $lat 纬度值
*  @param float $lng 经度值
*/
 function getDistance($lat1, $lng1, $lat2, $lng2)
 {
     $earthRadius = 6367000; //approximate radius of earth in meters

     /*
       Convert these degrees to radians
       to work with the formula
     */

     $lat1 = ($lat1 * pi() ) / 180;
     $lng1 = ($lng1 * pi() ) / 180;

     $lat2 = ($lat2 * pi() ) / 180;
     $lng2 = ($lng2 * pi() ) / 180;

     /*
       Using the
       Haversine formula

       http://en.wikipedia.org/wiki/Haversine_formula

       calculate the distance
     */

     $calcLongitude = $lng2 - $lng1;
     $calcLatitude = $lat2 - $lat1;
     $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);  $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
     $calculatedDistance = $earthRadius * $stepTwo;

     return round($calculatedDistance);
 }
 /*
 * CURL异步请求
 * @date: 2016-12-1 上午10:41:42
 * @editor: YU
 */
if(!function_exists('curl')){
	function curl($action,$params=array(),$httpHeader=array(),$format='') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $action);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$ret = curl_exec($ch);
		if (false === $ret) {
			$ret =  curl_errno($ch);
		}
		curl_close($ch);
		if ($format=='json') {
			$ret = json_decode($ret);
		}
		
		return $ret;
	}
}
/*
 * 订单生成码
 * @date: 2016-12-4 下午4:51:01
 * @editor: yu
 */
if(!function_exists('mkorder')){
	function mkorder(){
		$creatOid = date("Y").substr(time(),4).rand(10000,99999);
		$isExists = M('Order')->where(array('order_number'=>$creatOid))->count();
		if($isExists > 0)
			self::mkorder();
		else
			return $creatOid;
	}
}
/*
 * 支付号生成码
 * @date: 2016-12-4 下午4:51:01
 * @editor: yu
 */
if(!function_exists('mkpay')){
	function mkpay(){
		$creatOid = date("Ymd").substr(time(),3).rand(1000,9999);
		$isExists = M('Order')->where(array('pay_number'=>$creatOid))->count();
		if($isExists > 0)
			self::mkorder();
		else
			return $creatOid;
	}
}
/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author 
 */
function list_to_trees($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0) {
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId =  $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

?>