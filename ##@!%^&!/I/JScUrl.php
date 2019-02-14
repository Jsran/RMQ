<?php
namespace jR\I;
class JScUrl
{
	# HTTP协议类
	# js@jsran.cn
	# by 2017-05-16 司丙然
	# 仅PHP 7.0+

	private static $link = null;

	private static $head,$info,$errs,$ifopt = 0,$rstr = [];

	private static $opts = [
		# 请求地址
		CURLOPT_URL 				=> null,
		# 开启自动跳转
		CURLOPT_FOLLOWLOCATION		=> true,
		# 开启自动设置Referer
		CURLOPT_AUTOREFERER			=> true,
		# 数组内状态码程序认为是正确的响应
		CURLOPT_HTTP200ALIASES		=> [200,301,302],
		# 尝试连接等待时间 毫秒
		CURLOPT_CONNECTTIMEOUT_MS	=> 3000,
		# 设置最长执行时间 毫秒
		CURLOPT_TIMEOUT_MS			=> 3000,
		# 显示协议头 true 显示 false 不显示
		CURLOPT_HEADER				=> true,
		# 不显示BODY false 显示 true 不显示
		CURLOPT_NOBODY				=> false,
		# 开启直接输出返回流
		CURLOPT_RETURNTRANSFER		=> true
	];

	public static function open($url, $method = 'GET')
	{
		self::$link = curl_init();
		self::$opts[CURLOPT_URL] = $url;
		$pe = parse_url($url);
		if(args($pe['scheme'],'http','s') == 'https')
			# 设置不检查证书
			self::$opts[CURLOPT_SSL_VERIFYPEER] = false;
		$method = strtoupper($method);
		switch ($method) {
			case 'GET': # 设置常规GET请求
				self::$opts[CURLOPT_HTTPGET] = true;
			break;
			case 'POST': # 设置常规POST请求
				self::$opts[CURLOPT_POST] = true;
			break;
			case 'PUT':
			case 'DEL':
			case 'PATCH':
			case 'DELETE':
			default:
				self:$head[] = "X-HTTP-Method-Override: {$method}";
			break;
		}
		self::$opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_NONE;
		self::$opts[CURLOPT_PORT] =  args($pe['port'],$pe['scheme'] == 'https' ? 443 : 80,'d');
		self::$opts[CURLOPT_CUSTOMREQUEST] = $method;
	}
	public static function setOption($opt,$val)
	{
		self::$opts[$opt] = $val;
	}
	public static function setInfoOpt($opt)
	{
		self::$ifopt = $opt;
	}
	public static function setHeader($head)
	{
		self::$head[] = $head;
	}
	public static function setCookie($string)
	{
		self::$opts[CURLOPT_COOKIE] = $string;
	}
	public static function setProxy($proxy,$port,$u_p = null)
	{ # 代理设置
		# 是否启用 http connect方法
		self::$opts[CURLOPT_HTTPPROXYTUNNEL] = false;
		# 代理验证方式 CURLAUTH_BASIC or CURLAUTH_NTLM
		self::$opts[CURLOPT_PROXYAUTH] = CURLAUTH_BASIC;
		# 使用HTTP代理模式
		self::$opts[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
		# HTTP代理通道
		self::$opts[CURLOPT_PROXY] = $proxy;
		# HTTP代理服务器的端口
		self::$opts[CURLOPT_PROXYPORT] = $port;
		# 代理服务器需要密码的话 user:pass
		if(!is_null($u_p))
			self::$opts[CURLOPT_PROXYUSERPWD] = $u_p;
	}
	public static function send($data = null)
	{
		if(!empty($data) && args(self::$opts[CURLOPT_HTTPGET],false,'b') == false)
			self::$opts[CURLOPT_POSTFIELDS] = $data;
		if(!empty(self::$head))
			self::$opts[CURLOPT_HTTPHEADER] = self::$head;
			self::$opts[CURLINFO_HEADER_OUT] = true;
		curl_setopt_array(self::$link,self::$opts);
		$res = curl_exec(self::$link);
		self::$errs = [
			'errno' => curl_errno(self::$link),
			'error' => curl_error(self::$link),
			'strer' => null,
			'hcode' => curl_getinfo(self::$link,CURLINFO_HTTP_CODE)
		];
		self::$info = self::$ifopt > 0 ? curl_getinfo(self::$link,self::$ifopt) : curl_getinfo(self::$link);
		curl_close(self::$link);
		if(self::$errs['errno'] > 0)
		{
			self::$errs['strer'] = curl_strerror(self::$errs['errno']);
			return false;
		}
		if(self::$ifopt == 0)
		{
			self::$rstr['responseHead'] = substr($res, 0, self::$info['header_size']);
			self::$rstr['responseBody'] = substr($res, self::$info['header_size']);
		}
		return true;
	}
	public static function resHead()
	{ # 获取请求返回的协议头信息
		return args(self::$rstr['responseHead']);
	}
	public static function retText()
	{ # 获取请求返回的流信息
		return args(self::$rstr['responseBody']);
	}
	public static function reqHead()
	{ # 获取请求时的协议头信息
		return self::$info['request_header'];
	}
	public static function retInfo()
	{
		return self::$info;
	}
	public static function error()
	{ # 获取错误信息
		return self::$errs;
	}
	public static function getinfo()
	{ # 获取最后一次传输的相关信息
		return self::$info;
	}

############################使用方法############################

#	# 打开一个网址
#	JScUrl::open('http://www.baicaif.com/user/seting.do','GET');
#	# 设置协议头模拟手机访问
#	JScUrl::setHeader('User-Agent:Mozilla/5.0 Android');
#	# 设置来源页面
#	JScUrl::setHeader('Referer: http://www.baicaif.com/account.do');
#	# 设置语言属性
#	JScUrl::setHeader('Accept-Language: zh-CN');
#	# 使用代理服务器
#	JScUrl::setProxy("124.133.230.254",80);
#	# 模拟cookies无需登录 即可进行非法操作
#	JScUrl::setCookie('PHPSESSID=nd9s6gppr8v08runvruqqdf0d7');
#	# 发送至服务器
#	if(JScUrl::send())
#	{ # 打印服务器返回的信息
#		dump(JScUrl::reqHead());
#		dump(JScUrl::resHead())
#		dump(JScUrl::retText());
#	}else
#	{ # 打印错误信息
#		dump(JScUrl::error());
#	}

############################使用方法############################

}