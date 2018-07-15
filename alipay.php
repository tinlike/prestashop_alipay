<?php

class Alipay {
	public $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
	public $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
	public $payment_type = '1';
	public $sign_type = 'MD5';
	public $input_charset = 'utf-8';
	public $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';
	public $transport = 'http';
	public $cacert;

	var $alipay_config;

	function __construct($alipay_config){
		$this->alipay_config = $alipay_config;
		$this->cacert = __DIR__.'\\cacert.pem';
	}
	
	public function buildRequestPara($para_temp) {
		$temp = array(
		    'payment_type' => $this->payment_type,
			'sign_type' => $this->sign_type,
			'_input_charset' => $this->input_charset,
			'service' => $this->alipay_config['service'],
			'partner' => $this->alipay_config['partner'],
			'notify_url' => $this->alipay_config['notify_url'],
			'return_url' => $this->alipay_config['return_url'],
			'seller_email' => $this->alipay_config['seller_email'],
		);	
		$para_temp = array_merge($temp, $para_temp);
		$para_filter = $this->paraFilter($para_temp);

		$para_sort = $this->argSort($para_filter);

		$mysign = $this->buildRequestMysign($para_sort);
		
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->sign_type));
		
		return $para_sort;
	}
	
	public function buildRequestMysign($para_sort) {
	
		$prestr = $this->createLinkstring($para_sort);
			
		$mysign = "";
		switch (strtoupper(trim($this->sign_type))) {
			case "MD5" :
				$mysign = $this->md5Sign($prestr, $this->alipay_config['key']);
				break;
			default :
				$mysign = "";
		}
		
		return $mysign;
	}
	
	function verifyNotify(){
		if(empty($_POST)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			$isSign = $this->getSignVeryfy($_POST, $_POST["sign"]);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($_POST["notify_id"])) {$responseTxt = $this->getResponse($_POST["notify_id"]);}
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}

	function verifyReturn(){
		if(empty($_GET)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			$isSign = $this->getSignVeryfy($_GET, $_GET["sign"]);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true'; 
			if (! empty($_GET["notify_id"])) {$responseTxt = $this->getResponse($_GET["notify_id"]);}

			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}

	function getSignVeryfy($para_temp, $sign) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->argSort($para_filter);

		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->createLinkstring($para_sort);
		$isSgin = false;
		switch (strtoupper(trim($this->sign_type))) {
		case "MD5" :
			$isSgin = $this->md5Verify($prestr, $sign, $this->alipay_config['key']);
			break;
		default :
			$isSgin = false;
		}

		return $isSgin;
	}

	function getResponse($notify_id) {
		$transport = strtolower(trim($this->transport));
		$partner = trim($this->alipay_config['partner']);
		$veryfy_url = '';
		if($this->transport == 'https') {
			$veryfy_url = $this->https_verify_url;
		}
		else {
			$veryfy_url = $this->http_verify_url;
		}
		$veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
		$responseTxt = $this->getHttpResponseGET($veryfy_url, $this->cacert);

		return $responseTxt;
	}

	function createLinkstring($para) {
		$arg  = "";
		while (list ($key, $val) = each ($para)) {
			$arg.=$key."=".$val."&";
		}
		//去掉最后一个&字符
		$arg = substr($arg,0,count($arg)-2);

		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

		return $arg;
	}

	function createLinkstringUrlencode($para) {
		$arg  = "";
		while (list ($key, $val) = each ($para)) {
			$arg.=$key."=".urlencode($val)."&";
		}
		//去掉最后一个&字符
		$arg = substr($arg,0,count($arg)-2);

		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

		return $arg;
	}

	function paraFilter($para) {
		$para_filter = array();
		while (list ($key, $val) = each ($para)) {
			if($key == "sign" || $key == "sign_type" || $val == "" || $key == 'module' || $key == 'controller' || $key == 'fc')continue;
			else	$para_filter[$key] = $para[$key];
		}
		return $para_filter;
	}

	function argSort($para) {
		ksort($para);
		reset($para);
		return $para;
	}

	function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '') {

		if (trim($input_charset) != '') {
			$url = $url."_input_charset=".$input_charset;
		}
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
		curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
		curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt($curl,CURLOPT_POST,true); // post传输数据
		curl_setopt($curl,CURLOPT_POSTFIELDS,$para);// post传输数据
		$responseText = curl_exec($curl);
		//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		curl_close($curl);

		return $responseText;
	}

	function getHttpResponseGET($url,$cacert_url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
		curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
		$responseText = curl_exec($curl);
		//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		curl_close($curl);

		return $responseText;
	}

	function md5Sign($prestr, $key) {
		$prestr = $prestr . $key;
		return md5($prestr);
	}

	function md5Verify($prestr, $sign, $key) {
		$prestr = $prestr . $key;
		$mysgin = md5($prestr);

		if($mysgin == $sign) {
			return true;
		}
		else {
			return false;
		}
	}
}
?>
