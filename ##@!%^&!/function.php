<?php

/**
 * @param $type   String 类型 可选范围 I M
 * @param $class  String 类名 
 * @param $static Bool   是否静态
 */
function J($type,$class = null,$static = false)
{ # 实例化对象
	$type = strtoupper($type);
	if(!in_array($type, ['I','M']))
	{
		return false;
	}
	$n = "\\jR\\". $type . (!is_null($class) ? "\\{$class}" : null);
	return $static ? $n : new $n;
}
function ISPUT()
{ # 
	return WEB && $_SERVER['REQUEST_METHOD'] == 'POST';
}

/**
 * @param $var      String 变量名
 * @param $default  String 默认值 
 * @param $type     String 转换类型
 */
function args(&$var,$default=null,$type = null)
{ # 获取变量值
	$str = $var??$default??null;
	switch ($type??gettype($str)) {
		case 'a': # 数组
		case 'array':
			$var = (array) $str;
		break;
		case 'd': # 数字
		case 'integer':
			$var =	(int) $str;
		break;
		case 'f': # 浮点
		case 'double':
			$var = (float) $str;
		break;
		case 'b': # 布尔
		case 'boolean':
			$var = (bool) $str;
		break;
		case 's': # 字符串
		case 'string':
		default:
			$str = gettype($str)=='array'?serialize($str):$str;
			$var = (string) $str;
	}

	return $var;
}
function setContentReplace($arr)
{ # 自定义内容替换
	$GLOBALS['view']['contentReplace'] = is_array($arr)?array_merge($GLOBALS['view']['contentReplace'],$arr):$GLOBALS['view']['contentReplace'];
}
function styleget($str)
{ # 替换指定模板地址
	return array_key_exists($str, $GLOBALS['view']['style'])?$GLOBALS['view']['style'][$str]:str_replace('/' ,'' , $str);
}
function isMobile($flag = true)
{ # 是否手机版
	$_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';  
	  $mobile_browser = '0';   
	  if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))   
	    $mobile_browser++;   
	  if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))   
	    $mobile_browser++;   
	  if(isset($_SERVER['HTTP_X_WAP_PROFILE']))   
	    $mobile_browser++;   
	  if(isset($_SERVER['HTTP_PROFILE']))   
	    $mobile_browser++;   
	  $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));   
	  $mobile_agents = array(   
	        'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',   
	        'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',   
	        'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',   
	        'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',   
	        'newt','noki','oper','palm','pana','pant','phil','play','port','prox',   
	        'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',   
	        'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',   
	        'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',   
	        'wapr','webc','winw','winw','xda','xda-'  
	        );   
	  if(in_array($mobile_ua, $mobile_agents))   
	    $mobile_browser++;   
	  if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)   
	    $mobile_browser++;   
	  // Pre-final check to reset everything if the user is on Windows   
	  if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)   
	    $mobile_browser=0;   
	  // But WP7 is also Windows, with a slightly different characteristic   
	  if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)   
	    $mobile_browser++;   
	  if($mobile_browser>0)   
	    return $flag;   
	  else 
	    return false;
}
function get_client_ip()
{ # 获取客户端IP
	if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
		$ip = getenv("HTTP_CLIENT_IP");
	else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
		$ip = getenv("HTTP_X_FORWARDED_FOR");
	else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
		$ip = getenv("REMOTE_ADDR");
	else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
		$ip = $_SERVER['REMOTE_ADDR'];
	else
		$ip = "unknown";
	return($ip);
}
function url($s = 'main', $i = 'index', $param = array())
{ # 地址美化工程
	if(is_array($s)){
		$param = $s;
		if(isset($param['m'])) {
			$s = $param['m'] . '/' . $param['s'];
			unset($param['m'], $param['s']);
		} else {
			$s = $param['s']; unset($param['s']);
		}
		$i = $param['i']; unset($param['i']);
	}
	$params = empty($param) ? '' : '&'.http_build_query($param);
	if(strpos($s, '/') !== false){
		list($m, $s) = explode('/', $s);
		$route = "$m/$s/$i";
		$url = $_SERVER["SCRIPT_NAME"]."?m=$m&s=$s&i=$i$params";
	}else{
		$m = '';
		$route = "$s/$i";
		$url = $_SERVER["SCRIPT_NAME"]."?s=$s&i=$i$params";
	}
	if(!empty($GLOBALS['rewrite']) && WEB){
		if(!isset($GLOBALS['url_array_instances'][$url])){
			foreach($GLOBALS['rewrite'] as $rule => $mapper){
				$mapper = '/^'.str_ireplace(array('/', '<i>', '<s>', '<m>'), array('\/', '(?P<i>\w+)', '(?P<s>\w+)', '(?P<m>\w+)'), $mapper).'/i';
				if(preg_match($mapper, $route, $matchs)){
					$rule = str_ireplace(array('<i>', '<s>', '<m>'), array($i, $s, $m), $rule);
					$match_param_count = 0;
					$param_in_rule = substr_count($rule, '<');
					if(!empty($param) && $param_in_rule > 0){
						foreach($param as $param_key => $param_v){
							if(false !== stripos($rule, '<'.$param_key.'>'))$match_param_count++;
						}
					}
					if($param_in_rule == $match_param_count){
						$GLOBALS['url_array_instances'][$url] = $rule;
						if(!empty($param)){
							$_args = array();
							foreach($param as $arg_key => $arg){
								$count = 0;
								$GLOBALS['url_array_instances'][$url] = str_ireplace('<'.$arg_key.'>', $arg, $GLOBALS['url_array_instances'][$url], $count);
								if(!$count)$_args[$arg_key] = $arg;
							}
							$GLOBALS['url_array_instances'][$url] = preg_replace('/<\w+>/', '', $GLOBALS['url_array_instances'][$url]). (!empty($_args) ? '?'.http_build_query($_args) : '');
						}
						
						if(0!==stripos($GLOBALS['url_array_instances'][$url], SCHEME)){
							$GLOBALS['url_array_instances'][$url] = SCHEME.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/\\') .'/'.$GLOBALS['url_array_instances'][$url];
						}
						return $GLOBALS['url_array_instances'][$url];
					}
				}
			}
			return isset($GLOBALS['url_array_instances'][$url]) ? $GLOBALS['url_array_instances'][$url] : $url;
		}
		return $GLOBALS['url_array_instances'][$url];
	}
	return CLI ? HOST . chr(32) . str_replace([chr(63),chr(38)], chr(32), $url) : $url;
}
function is_available_classname($name)
{ # 检查文件名称
	return preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $name);
}
function _err_router($msg)
{ # 路由处理
	$Base = PACK."\\C\\".M."\\Base";
	if(!method_exists($Base, 'err404')):
		err($msg);
	else:
		exit($Base::err404($msg));
	endif;
}
function _exc_handler($e)
{ # 异常捕获
	err(sprintf("ERROR: %s in %s on line %d",$e->getMessage(),$e->getFile(),$e->getLine()),[['file' => $e->getFile(),'line' => $e->getLine()]] + $e->getTrace());
}
function _fal_handler()
{ # 致命捕获
	$e = error_get_last();
	if($e && ($e["type"]===($e["type"] & (E_ERROR|E_USER_ERROR|E_CORE_ERROR|E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_PARSE)))):
        _err_handle($e["type"],$e["message"],$e["file"],$e["line"]);
    endif;
}
function _err_handle($errno, $errstr, $errfile, $errline)
{ # 错误处理
	if(0 === error_reporting() || 30711 === error_reporting())return false;
	$msg = "ERROR";
	if($errno == E_WARNING)$msg = "WARNING";
	if($errno == E_NOTICE)$msg = "NOTICE";
	if($errno == E_STRICT)$msg = "STRICT";
	if($errno == E_DEPRECATED)$msg = "DEPRECATED";
	err("$msg: $errstr in $errfile on line $errline");
}
function err($msg,$traces = null)
{ # 错误日志
	defined('ERR') or define('ERR',1);
	$msg = htmlspecialchars($msg);
	$traces = $traces ?? debug_backtrace();
	if(!$GLOBALS['debug']) exit(error_log($msg));
	if(CLI) exit(dump($msg));
	if (ob_get_contents()) ob_end_clean();
	function _err_highlight_code($code){
		if(preg_match('/\<\?(php)?[^[:graph:]]/i', $code)){return highlight_string($code, TRUE);}else{return preg_replace('/(&lt;\?php&nbsp;)+/i', "", highlight_string("<?php ".$code, TRUE));}}
	$_err_getsource = function($file,$line){
		if(!(file_exists($file) && is_file($file))) {return '';}$data = file($file);$count = count($data) - 1;$start = $line - 5;if ($start < 1) {$start = 1;}$end = $line + 5;if ($end > $count) {$end = $count + 1;}$returns = array();for($i = $start; $i <= $end; $i++) {if($i == $line){$returns[] = "<div id='current'>".$i.".&nbsp;"._err_highlight_code($data[$i - 1], TRUE)."</div>";}else{$returns[] = $i.".&nbsp;"._err_highlight_code($data[$i - 1], TRUE);}}return $returns;};
	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><meta name="robots" content="noindex, nofollow, noarchive" /><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title><?php echo $msg;?></title><style>body{padding:0;margin:0;word-wrap:break-word;word-break:break-all;font-family:Courier,Arial,sans-serif;background:#EBF8FF;color:#5E5E5E;}div,h2,p,span{margin:0; padding:0;}ul{margin:0; padding:0; list-style-type:none;font-size:0;line-height:0;}#body{width:918px;margin:0 auto;}#main{width:918px;margin:13px auto 0 auto;padding:0 0 35px 0;}#contents{width:918px;float:left;margin:13px auto 0 auto;background:#FFF;padding:8px 0 0 9px;}#contents h2{display:block;background:#CFF0F3;font:bold 20px;padding:12px 0 12px 30px;margin:0 10px 22px 1px;}#contents ul{padding:0 0 0 18px;font-size:0;line-height:0;}#contents ul li{display:block;padding:0;color:#8F8F8F;background-color:inherit;font:normal 14px Arial, Helvetica, sans-serif;margin:0;}#contents ul li span{display:block;color:#408BAA;background-color:inherit;font:bold 14px Arial, Helvetica, sans-serif;padding:0 0 10px 0;margin:0;}#oneborder{width:800px;font:normal 14px Arial, Helvetica, sans-serif;border:#EBF3F5 solid 4px;margin:0 30px 20px 30px;padding:10px 20px;line-height:23px;}#oneborder span{padding:0;margin:0;}#oneborder #current{background:#CFF0F3;}</style></head><body><div id="main"><div id="contents"><h2><?php echo $msg?></h2><?php foreach($traces as $trace){if(is_array($trace)&&!empty($trace["file"])){$souceline = $_err_getsource($trace["file"], $trace["line"]);if($souceline){?><ul><li><span><?php echo $trace["file"];?> on line <?php echo $trace["line"];?> </span></li></ul><div id="oneborder"><?php foreach($souceline as $singleline)echo $singleline;?></div><?php }}}?></div></div><div style="clear:both;padding-bottom:50px;" /></body></html><?php
	exit;
}
function dump($var, $exit = false)
{ # 调试输出
	$output = print_r($var, true);
	if(CLI && !WEB):
		echo $output , "\r\n";
	else:
		$output = print_r($var, true);
		# if(!$GLOBALS['debug'])return error_log(str_replace("\n", '', $output));
		echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body><div align=left><pre>" .htmlspecialchars($output). "</pre></div></body></html>";
	endif;
	if($exit) exit();
}