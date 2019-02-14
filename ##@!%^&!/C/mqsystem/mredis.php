<?php
namespace jR\C\mqsystem;
use jR\M;
use jR\I;
class mredis extends Base
{
  public function List()
  {
    if($_SERVER['REQUEST_METHOD'] == 'PUT'){
      $obj = new M\MQRedis;
      $where = !isset(parent::$args['name'])?'': "where name like '".parent::$args['name']."%'";
      $limit = (parent::$args['page']-1).",".parent::$args['limit'];
      parent::json($obj->getRedisAlldatas($where,$limit));
    }
  }
  public function create()
  {
    if($_SERVER['REQUEST_METHOD'] == 'PUT')
    { # 新增数据
      $obj = new M\MQRedis;
      $info = serialize(parent::$args['info']);
      $data = ['name' => parent::$args['name'],'info' => $info];
      if($obj->insert($data??['master'=>['host'=>'','port' =>'','auth'=>''],'slave'=>[['host'=>'','port' =>'','auth'=>'']]])->run()){
        # 清理缓存
        $obj->getServersClear();
        parent::json('Redis Create success!');
      }
      parent::json('fail','100101');
    }
  }

  public function edit()
  {
    $obj = new M\MQRedis;
    if($_SERVER['REQUEST_METHOD'] == 'PUT')
    { # 更新
      $info = serialize(parent::$args['info']);
      if($obj->update(['name'=> parent::$args['name'],'info' => $info])->where(['id' =>  parent::$args['id']])->run()){
        $obj->getServersClear();
        parent::json('Redis update success!');
      }
      parent::json('fail','100201');
    }else
    { # 渲染信息
      $res = $obj->select('id,name,info',true)->where(['id' => parent::$args['id']])->run();
      if($res)
        $res = ['id' => $res['id'],'name' => $res['name']]+unserialize($res['info']);
      $this->val = $res;
    }
  }
  public function show()
  {

  }
  public function state()
  {
    if($_SERVER['REQUEST_METHOD'] == 'PUT'){
      $obj = new M\MQRedis;
      if($obj->update(['state'=> parent::$args['state']])->where(['id'=>parent::$args['id']])->run()){
        $obj->getServersClear();
        parent::json('success');
      }
      parent::json('update state fail','100102');
    }
  }
}