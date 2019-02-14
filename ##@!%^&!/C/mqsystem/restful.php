<?php
namespace jR\C\mqsystem;
use jR\M;
use jR\I;
use jR\C\cli\RedisMessageQueue\Start;
class restful extends Base
{
  # web server 接受任务创建、查询、删除处理
  public function Home()
  { # 简单reasful 处理
  	parent::opcache();
  	$pri_func = $_SERVER['REQUEST_METHOD'];
  	if(in_array($pri_func, ['PUT','GET','DEL','RECOVER'])) self::$pri_func();
    parent::json('invalid request type.','100110');
  }
  private function PUT()
  { # 新增数据
    // $data = ['topic' => 'order_cash','orderid' => '1200014', 'body' => 
    //   [
    //     'action' => 'recharge',
    //     'orderId' => '1200014',
    //     'uid' => 10000,
    //     'phone' => 18131153489,
    //     'money' => 500000,
    //   ]
    // ];
    array_map(function($v){if(!isset(parent::$args[$v]) || empty(parent::$args[$v])) parent::json("Param $v not set",'100201');}, ['topic','orderid','body']);
    $data = parent::$args;
    $RMQ = new M\MQRedis;
    $TopicInfo = $RMQ->getTopicInfoCache180($data['topic']);
    if(!$TopicInfo) parent::json("Topic [{$data['topic']}] is not registed",'100202');
    if($TopicInfo['status'] != 1) parent::json("Topic [{$data['topic']}] offline or deleted",'100203');
    if($RMQ->table('rmq_runlog')->select('tid')->where(['tid'=>$TopicInfo['id'],'orderid'=>$data['orderid']])->run())
      parent::json("Topic [{$data['topic']}] or orderid [{$data['orderid']}] already exists",'100204');
    $key = "{$data['topic']}:{$data['orderid']}";
    $RedisList = $RMQ->getServersCache180();
    $index = crc32($key) % count($RedisList);
    if(empty($RedisList[$index])) parent::json('Redis server is empty','100205');
    try{$redis = Start::getRedis($RedisList[$index]);}catch(\Exception $e){parent::json($e->getMessage(),'100206');}
    if($redis->hexists(Start::$jobinfo,$key)) parent::json("Topic [{$data['topic']}] and orderid [{$data['orderid']}] already exists",'100207');
    # 每个任务都会分配到不同的timer进程
    $timerId = crc32($key) % Start::$timer_nums;
    $zkey = sprintf(Start::$bucketKey,$timerId);
    $data['body']['notify_time'] = date('Y-m-d H:i:s',isset($data['body']['notify_time'])?strtotime($data['body']['notify_time']):(time()+$TopicInfo['delay']));
    if(!isset($data['body']['create_time'])) $data['body']['create_time'] = date('Y-m-d H:i:s');
    $body = json_encode($data['body']);
    $pipe = $redis->multi(\Redis::PIPELINE);
    $pipe->hSet(Start::$jobinfo, $key, $body);
    $pipe->zadd($zkey, strtotime($data['body']['notify_time']), $key);
    $result = $pipe->exec();
    $isSucc = $result[0] || $result[1];
    if(!$isSucc) parent::json('create data fail','100208');
    $body = serialize($data['body']);
    $MsgId = md5($TopicInfo['id'].$data['orderid'].$redis->rid.$timerId);
    $RMQ->table('rmq_runlog')
      ->insert([
      'MsgId' => $MsgId,
    	'tid' => $TopicInfo['id'],
    	'orderid' => $data['orderid'],
      'TimerId' => $timerId,
      'rid' => $redis->rid,
      'notify_time' => $data['body']['notify_time'],
    	'body' => $body,
      ])->duplicate(['put = put+1'])->run();
    parent::json(['MsgId' => $MsgId,'Msg' => 'create data success!']);
  }
  private function DEL()
  { # 删除数据
    $RMQ = new M\MQRedis;
    $del = function($topic,$orderid) use($RMQ){
      $key = "{$topic}:{$orderid}";
      $timerId = crc32($key) % Start::$timer_nums;
      $zkey = sprintf(Start::$bucketKey,$timerId);
      $RedisList = $RMQ->getServersCache180();
      $index = crc32($key) % count($RedisList);
      if(empty($RedisList[$index])) parent::json('Redis server is empty','100301');
      try{$redis = Start::getRedis($RedisList[$index]);}catch(\Exception $e){parent::json($e->getMessage(),'100302');}
      if(!$redis->hexists(Start::$jobinfo,$key)) parent::json("Topic [{$topic}] and orderid [{$orderid}] already not exists",'100303');
      $pipe = $redis->multi(\Redis::PIPELINE);
      $pipe->zrem($zkey, $key);
      $pipe->hdel(Start::$jobinfo,$key);
      $result = $pipe->exec();
      if($result){
        $info = $RMQ->getTopicInfoCache180($topic);
        $RMQ->table('rmq_runlog')->update(['status' => 8])->where(['tid' => $info['id'],'orderid' => $orderid])->run();
        parent::json('DEL Task succ');
      }
      parent::json('DEL Task fail!','100304');
    };
    if(isset(parent::$args['MsgId']) && !empty(parent::$args['MsgId'])) 
    { # 按消息ID删除
      if(($res = $RMQ->table('rmq_runlog a')->select('b.topic,orderid',true)->leftjoin('rmq_topic b',['a.tid=b.id'])->where(['MsgId' => parent::$args['MsgId']])->run()))
        $del($res['topic'],$res['orderid']);
      parent::json('delete MsgId data fail','100305');
    }elseif(isset(parent::$args['topic']) && isset(parent::$args['orderid']) && !empty(parent::$args['topic']) && !empty(parent::$args['orderid']))
    { # 按流水号删除
      $del(parent::$args['topic'],parent::$args['orderid']);
    }else{
      parent::json('delete data fail','100306');
    }
  }
  private function RECOVER()
  { # 恢复数据
    $Recover = function($where = []){
      $RMQ = new M\MQRedis;
      $res = $RMQ->table('rmq_runlog a')->select('a.MsgId,b.topic,a.orderid,delay,body',true)->leftjoin('rmq_topic b',['a.tid = b.id'])->where($where)->run();
      if(!$res) parent::json("MsgId not data",'100401');
      $res['body'] = unserialize($res['body']);
      $res['body']['notify_time'] = date('Y-m-d H:i:s',time()+$res['delay']);
      $key = "{$res['topic']}:{$res['orderid']}";
      $RedisList = $RMQ->getServersCache180();
      $index = crc32($key) % count($RedisList);
      if(empty($RedisList[$index])) parent::json('Redis server is empty','100402');
      try{$redis = Start::getRedis($RedisList[$index]);}catch(\Exception $e){parent::json($e->getMessage(),'100403');}
      if($redis->hexists(Start::$jobinfo,$key)) parent::json("Topic [{$res['topic']}] and orderid [{$res['orderid']}] already exists",'100407');
      $timerId = crc32($key) % Start::$timer_nums;
      $zkey = sprintf(Start::$bucketKey,$timerId);
      $pipe = $redis->multi(\Redis::PIPELINE);
      $pipe->hSet(Start::$jobinfo, $key, json_encode($res['body']));
      $pipe->zadd($zkey, strtotime($res['body']['notify_time']), $key);
      $result = $pipe->exec();
      $isSucc = $result[0] && $result[1];
      if(!$isSucc) parent::json('create data fail','100404');
      $RMQ->table('rmq_runlog')
      ->update([
        'orderid' => $res['orderid'],
        'TimerId' => $timerId,
        'rid' => $redis->rid,
        'notify_time' => $res['body']['notify_time'],
        'body' => serialize($res['body']),
        'put = put+1',
        'status' => 0,
        ])
      ->where(['MsgId' => $res['MsgId']])->run();
      parent::json('RECOVER MsgId success');
    };
    if(isset(parent::$args['MsgId']) && !empty(parent::$args['MsgId'])){ # MsgId 查恢复
      $Recover(['b.status' => 1,'a.status in(2,8)','MsgId' => parent::$args['MsgId']]);
    }
    # topic + orderid 恢复
    array_map(function($v){if(!isset(parent::$args[$v]) || empty(parent::$args[$v])) parent::json("Param $v not set",'100405');}, ['topic','orderid']);
    $Recover(['b.status' => 1,'a.status in(2,8)','b.topic' => parent::$args['topic'],'a.orderid' => parent::$args['orderid']]);
  }

  private function GET()
  { # 查询数据
    $RMQ = new M\MQRedis;
  	# $data = ['topic' => 'order_recharge_task','orderid' => '1200001'];
    if(isset(parent::$args['MsgId']) && !empty(parent::$args['MsgId'])){
      $res = $RMQ->table('rmq_runlog')->select('notify,notify_time,body,status',true)->where(['MsgId' => parent::$args['MsgId']])->run();
      if(!$res) parent::json("MsgId not data",'100101');
      $res['body'] = unserialize($res['body']);
      $res['body']['notify_time'] = $res['notify_time'];
      if($res['notify'] >1 && $res['status'] != 1)
          $res['body']['notify_nums'] = $res['notify'];
      parent::json($res['body']); 
    }
  	array_map(function($v){if(!isset(parent::$args[$v]) || empty(parent::$args[$v])) parent::json("Param $v not set",'100102');}, ['topic','orderid']);
  	$data = parent::$args;
  	$key = "{$data['topic']}:{$data['orderid']}";
  	$RedisList = $RMQ->getServersCache180();
    $index = crc32($key) % count($RedisList);
    if(empty($RedisList[$index])) parent::json('Redis server is empty','100103');
    try{$redis = Start::getRedis($RedisList[$index]);}catch(\Exception $e){parent::json($e->getMessage(),'100104');}
    $data = $redis->hget(Start::$jobinfo,$key);
    if($data) parent::json($data); 
    $res = $RMQ->table('rmq_runlog a')->select('notify,notify_time,body,a.status',true)->leftjoin('rmq_topic b',['a.tid = b.id'])->where(['a.orderid' => parent::$args['orderid'],'b.topic' => parent::$args['topic']])->run();
    if(!$res) parent::json("get not data",'100105');
    $res['body'] = unserialize($res['body']);
    $res['body']['notify_time'] = $res['notify_time'];
    if($res['notify'] >1 && $res['status'] != 1)
        $res['body']['notify_nums'] = $res['notify'];
    parent::json($res['body']);
  }
}