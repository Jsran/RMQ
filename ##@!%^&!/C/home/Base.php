<?php
namespace jR\C\home;
use jR\C;
class Base extends C
{
	public $layout = "layout.html";

	public function jump()
	{
		echo "我是 base 的jump\r\n";
	}
	public function nosql()
	{
		dump('注入玩的不赖,干的漂亮,Beautiful,摸摸哒!');
	}
	public static function err404($msg)
	{
		# header("HTTP/1.0 404 Not Found");
		dump($msg);
	}

	public function opcache()
	{
		$call = function_exists('opcache_reset')? 'opcache_reset' : (function_exists('accelerator_reset')? 'accelerator_reset' : null);
		if($call) call_user_func($call);
	}
}