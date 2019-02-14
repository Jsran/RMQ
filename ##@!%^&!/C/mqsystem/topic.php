<?php
namespace jR\C\mqsystem;
use jR\M;
use jR\I;
use Redis;
class topic extends Base
{
  public function List()
  {
    if($_SERVER['REQUEST_METHOD'] == 'PUT'){
      $obj = new M\MQRedis;
      $where = !isset(parent::$args['name'])?[]:[parent::$args['type']." like '".parent::$args['name']."%'"];
      $limit = (parent::$args['page']-1).",".parent::$args['limit'];
      $res = $obj->getTopicAlldata($where,$limit);
      parent::json($res);
    }
  	
  }
  public function create()
  {
    if($_SERVER['REQUEST_METHOD'] == 'PUT')
    { # 新增数据
      $obj = new M\MQRedis;
      $data = parent::$args;
      array_map(function($v) use(& $data){if(isset($data[$v])) unset($data[$v]);}, ['m','s','i']);
      if($obj->table('rmq_topic')->insert($data)->run()){
        parent::json('Topic Create success!');
      }
      parent::json('Topic Create fail','100101');
    }
  }

  public function edit()
  {
    $obj = new M\MQRedis;
    if($_SERVER['REQUEST_METHOD'] == 'PUT')
    { # 更新
      $data = parent::$args;
      array_map(function($v) use(& $data){if(isset($data[$v])) unset($data[$v]);}, ['m','s','i','id']);
      if($obj->table('rmq_topic')->update($data)->where(['id' =>  parent::$args['id']])->run()){
        $obj->getTopicInfoClear(parent::$args['topic']);
        parent::json('Topic update success!');
      }
      parent::json('Topic update fail','100201');
    }else
    { # 渲染信息
      $res = $obj->table('rmq_topic')->select('*',true)->where(['id' => parent::$args['id']])->run();
      $this->val = $res;
    }
  }

  public function show()
  {
    
    dump($this->val);exit;
  }

  public function status()
  {
    if($_SERVER['REQUEST_METHOD'] == 'PUT'){
      $obj = new M\MQRedis;
      if($obj->table('rmq_topic')->update(['status'=> parent::$args['status']])->where(['id'=>parent::$args['id']])->run()){
        $obj->getTopicInfoClear(parent::$args['topic']);
        parent::json('update status success');
      }
      parent::json('update state fail','100102');
    }
  }
  public function code()
  {
    $obj = new M\MQRedis;
    $this->val = $obj->getTopicInfoCache180(parent::$args['id']);
  }
}