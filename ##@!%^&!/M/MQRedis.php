<?php
namespace jR\M;
use jR\M;
class MQRedis extends M
{
  public $table = "rmq_redis";

  public function getServers()
  { # redis服务器列表
    return $this->query("select id,info from rmq_redis where state = 1 order by id desc")
      ->fetchAll(\PDO::FETCH_FUNC,
        function($id,$info){
          return ['id' => $id] + unserialize($info);
        }
      );
  }
  public function getLogall($where = null,$limit = '0,10')
  {
    return $this->query('select a.MsgId,a.orderid,a.rid,a.TimerId,a.status,a.create_time,a.notify_time,b.topic from rmq_runlog a left join rmq_topic b on a.tid = b.id ' . $where . ' order by update_time desc limit '.$limit )->fetchAll();
  }
  public function getTopicPriority($topic)
  { # Topic Priority
  	return $this->query('select priority from rmq_topic where topic = :topic',[':topic' => $topic])
      ->fetch(\PDO::FETCH_COLUMN);
  }
  public function getTopicInfo($topic)
  { # 
  	return $this->query('select * from rmq_topic where topic = :topic',[':topic' => $topic])
      ->fetch(\PDO::FETCH_ASSOC);
  }
  public function setLog($id,$orderid,$time=null,$status=0)
  { # update
    return $this->table('rmq_runlog')->update(['notify=notify+1','status' => $status]+($time == null ?[] : ['notify_time' => $time]))->where(['tid' => $id,'orderid' => $orderid])->run();
  }
  public function getRedisAlldata()
  {
    if($res = $this->table('rmq_redis')->select('count(1) c,sum(push) p1,sum(repush) p2,sum(pop) pop',true)->run())
      $res['rep'] = sprintf("%01.2f", $res['p2']/($res['p1']+$res['p2'])*100);
    return $res??null;
  }

  public function getRedisAlldatas($where = null,$limit = '0,10')
  {
    return $this->query('select id,name,info,push,repush,pop,repop,state from rmq_redis ' . $where . ' limit '.$limit)
      ->fetchAll(\PDO::FETCH_FUNC,
        function($id,$name,$info,$push,$repush,$pop,$repop,$state){
          return ['id' => $id,'name' => $name,'push' => $push,'repush' => $repush,'pop' => $pop,'repop' => $repop,'state' => $state,
            'rep' => $push > 0 ? sprintf("%01.2f",$repush/($push+$repush) * 100) : 0,
            'reps' => $pop > 0 ? sprintf("%01.2f",$repop/($pop+$repop) * 100) : 0] + unserialize($info);
        }
      );
  }

  public function getTopicAlldata($where = [],$limit ='0,10')
  {
    return $this->table('rmq_topic')->select()->where($where)->limit($limit)->run();
  }
}