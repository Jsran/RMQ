<?php
namespace jR\I\Traits;
trait bankTrait
{
	function getAutoStatus($id)
	{ # 自动授权范围
		# 格式：010000000000
		# 0-未授权、1-已授权；
		# 权限数值占位说明：
		# 第 1 位：自动投资
		# 第 2 位：自动还款
		# 第 3 位：自动代偿
		# 第 4 位：自动缴费
		# 第 5-12 位预留占位
		switch ($id):
			case 1: # 出借人
				$int = pow(2,0) + pow(2,3);
			break;
			case 2: # 借款人
				$int = pow(2,1) + pow(2,3);
			break;
			case 5: # 代偿户
				$int = pow(2,2) + pow(2,3);
			break;
			default:
				$int = 0;
		endswitch;
		return strrev(sprintf('%012s', decbin($int)));
	}
	private function GetOrderId($fuc)
	{ # 订单生产
		# 01 个人开户 02 法人开户 03 绑卡 04 解绑 05 销户
		# 06 更改手机 07 重置密码 08 通知配置 09 充值 0A 提现
		# 0B 项目报备 0C 项目更新 0D 用户授权 0E 验密冻结
		# 0F 授权冻结 10 验密解冻 11 授权解冻 12 转账交易
		# 13 项目查询 14 债权查询 15 交易查询 16 用户信息
		$interface = new \ReflectionClass($this);
		$itfc = $interface->getInterfaces();
		$interface = array_keys($itfc);
		$interface = array_flip(
			array_column($itfc[array_shift($interface)]->getMethods(),'name'));
		// $oid = new Order();
		// if(!($orid = $oid->insert())) self::GetOrderId();
		$orid = '000123';
		return sprintf(
			'%08s%02s%06s',
			date('Ymd'),
			strtoupper(dechex(++$interface[$fuc])),
			$orid
		);
	}
	
	private function rsaSign($data)
	{
		$res = openssl_get_privatekey(file_get_contents(self::$prikey));
		openssl_sign($data, $sign, $res);
		openssl_free_key($res);
		return base64_encode($sign);
	}
	private function rsaVerify($data, $sign)
	{
		$res = openssl_get_publickey(file_get_contents(self::$pubkey));
		$result = (bool)openssl_verify($data, base64_decode($sign), $res);
		openssl_free_key($res);
		return $result;
	}
	private function Form($url,$data,$auto=true,$method='post')
	{ # form auto submit
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml">',"\r\n",'<head>',"\r\n",'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />',"\r\n",'<title></title>',"\r\n",'</head>',"\r\n",$auto?'<body onload="document.form1.submit()">':'<body>',"\r\n",'<h5>准备跳转至金城银行接口...</h5>','<form id="form1" name="form1" method="'.$method.'" action="'.self::$host.$url.'">',"\r\n";
		foreach($data as $k=>$v)
			echo '<input type="hidden" name="'.$k.'" value=\''.$v.'\' />',"\r\n";
		echo $auto?'':'<input type="submit" value="submit" />',"\r\n","</form></body></html>";
		exit;
	}
	public function reJson()
	{
		
	}
	private function cUrl($url,$data)
	{ # 依赖JScUrl类
		\jR\I\JScUrl::open(self::$host.$url,'POST');
		if(!\jR\I\JScUrl::send($data))
		{ # 跪了
			return false;	
		}
		$res = \jR\I\JScUrl::retText();
		if(!preg_match("/<plain>([\s\S]*?)<\/plain>/i", $res, $match))
		{ # 跪了
			return false;
		}
		function xmltoarray($string)
		{
			$ob = simplexml_load_string($string);
			$json  = json_encode($ob);
			$array = json_decode($json, true);
			return $array;
		}
		$res = xmltoarray($res);
		$sign = $res['signature'];unset($res['signature']);
		return self::rsaVerify($match[0],$sign) ? $res['plain'] : false;
	}
}