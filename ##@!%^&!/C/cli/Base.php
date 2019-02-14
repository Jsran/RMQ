<?php
namespace jR\C\cli;
use jR\C;
class Base extends C
{
	public $layout = null;
	public $_auto_display = false;
	
	public function JsGo()
	{
		if(!CLI) exit(dump('not cli'));
	}

	function jump($url, $delay = 0)
    {
        echo "<html><head><meta http-equiv='refresh' content='{$delay};url={$url}'></head><body></body></html>";
        exit;
    }

	public static function err404($msg)
	{
		# header("HTTP/1.0 404 Not Found");
		dump($msg);
		exit;
	}

	public function opcache()
	{
		$call = function_exists('opcache_reset')? 'opcache_reset' : (function_exists('accelerator_reset')? 'accelerator_reset' : null);
		if($call) call_user_func($call);
	}
}