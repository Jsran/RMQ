<?php
namespace jR\C\mqsystem;
use jR\M;
use jR\I;
use jR\C\cli\RedisMessageQueue\Start;
class message extends Base
{
  public function Track()
  {
  	if($_SERVER['REQUEST_METHOD'] == 'PUT'){
  	  $obj = new M\MQRedis;
  	  if(isset(parent::$args['name'])){
  	  	$where = "where ".(parent::$args['status'] != 'all' ? 'a.status = '.parent::$args['status'] .' and ':'').parent::$args['type']." like '".parent::$args['name']."%'";
  	  }else{
  	  	$where = '';
  	  }
  	  $limit = ((parent::$args['page']-1)*10).",".parent::$args['limit'];
  	  parent::json($obj->getLogall($where,$limit));
  	}
  	$this->Tnums = Start::$timer_nums;
  	$this->Cnums = Start::$consume_nums;
  }
  public function Create()
  { # 手动创建任务
    $obj = new M\MQRedis;
    $this->val = $obj->table('rmq_topic')->select('topic,name')->run();
  }
  public function show()
  { # 任务详情
    $obj = new M\MQRedis;
    $res = $obj->table('rmq_runlog')->select('*',true)->where(['MsgId' => parent::$args['MsgId']])->run();
    if($res){
      $res['body'] = unserialize($res['body']);
      $res['body']['notify_time'] = $res['notify_time'];
      if($res['notify'] >1 && $res['status'] != 1)
        $res['body']['notify_nums'] = $res['notify'];
    }
    $this->val = $res??[];
    #dump($res);
  }
}