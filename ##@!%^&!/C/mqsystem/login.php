<?php
namespace jR\C\mqsystem;
use jR\M;
use jR\I;
class login extends Base
{
  #
  public function Home()
  { 
    #
  }
  public function sign()
  { # 登录
    if($_SERVER['REQUEST_METHOD'] == 'PUT'){
      $obj = new M;
      $res = $obj->table('rmq_admin')->select('id,user','true')->where(['user' => parent::$args['user'],'pass' => parent::$args['pass']])->run();
      if($res){
        $_SESSION[SE_NAME]['Uinfo'] = $res;
        parent::json('login sussess');
      }
      parent::json('login fail','100101');
    }
  }

  public function lout()
  {
  	if(isset($_SESSION[SE_NAME]['Uinfo']))
    { # 清理SESSION
      unset($_SESSION);
      session_unset();
      session_destroy();
      header('Location: '.url('mqsystem/login','home'));
    }
  }
}