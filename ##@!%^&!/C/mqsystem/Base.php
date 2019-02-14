<?php
namespace jR\C\mqsystem;
use jR\C;
class Base extends C
{
  public $layout = null;

  public function JsGo()
  {
    self::VerifyLogin();
    self::VerifyLoginNo();
    if(static::class != __class__ && method_exists(static::class, 'init')) static::init();
  }
  public function VerifyLogin()
  { # 验证继承该base的类必须登录才能访问的模块
    $clist = [
      'Main',
      'message',
      'mredis',
      'topic',
    ];
    if(preg_grep(sprintf("/^%1\$s$|^%1\$s\/%2\$s$/i",S,I),$clist) && !isset($_SESSION[SE_NAME]['Uinfo'])){
      if(CLI) exit(dump('no login'));
      exit(header('Refresh: 0; url='.url(M."/login","home")));
    }
  }
  public function VerifyLoginNo()
  { # 验证继承该base的类登录后不能访问的模块
    $clist = [
      'login/home',
      'login/sign',
    ];
    if(preg_grep(sprintf("/^%1\$s$|^%1\$s\/%2\$s$/i",S,I),$clist) && isset($_SESSION[SE_NAME]['Uinfo'])){
      exit(header('Refresh: 0; url='.url(M."/main","home")));
    }
  }
  public function jump()
  {
    echo "我是 base 的jump\r\n";
  }
  public function json($msg,$code = '000000')
  {
    exit(json_encode(['State' => $code,'Msg' => $msg]));
  }
  public function success($msg, $url = 'javascript:history.go(-1)',$time=5)
  { # 消息提示
    $this->msg = $msg;
    $this->url = $url;
    $this->time = $time;
    exit($this->display('success.html'));
  }

  public function opcache()
  {
    $call = function_exists('opcache_reset')? 'opcache_reset' : (function_exists('accelerator_reset')? 'accelerator_reset' : null);
    if($call) call_user_func($call);
  }

  public function Assigns($args = [])
  { # 渲染变量
    $this->val = $args;
  }
}