<?php
namespace jR\C\cli\RedisMessageQueue;
use jR\M;
use jR\I;
class Start 
{
  
  static $ctime = 0;
  static $stop = 0;
  static $pid = 0;

  # Redis 连接池 时间key
  static $time = [];
  # Redis 连接池 资源
  static $instance = [];

  # 消息体
  static $jobinfo   = 'jr_job_info';
  # 消息过期
  static $bucketKey = "jr_bucket_%1\$u";
  # 消费队列
  static $readyKey  = "jr_%1\$u_ready_queue";

  # 主进程，负责管理子进程的创建，销毁，回收以及信号通知
  const JR_MASTER        = 'jR-master';
  # 负责从redis的zset结构中扫描到期的消息，并负责写入ready 队列，个数可配置，一般2个就行了，因为消息在zset结构是按时间有序的
  const JR_TIMER         = 'jR-timer';
  # N: 负责从ready队列中读取消息并通知给对应回调接口，个数可配置
  const JR_CONSUME       = 'jR-consume';
  # 负责检查redis的服务状态，如果redis宕机，发送告警邮件
  const JR_REDIS_CHECKER = 'jR-RedisState';

  # 队列优先级设置
  const TASK_PRIORITY_HIGH  =1;
  const TASK_PRIORITY_NORMAL=2;
  const TASK_PRIORITY_LOW   =3;

 
  use CFGTrait;
  

  public static function processRun($title,$callback,$childnum=1)
  {
    if(self::get_process_num($title) >= $childnum || Start::$stop) return ;
    for ($i = 0; $i < $childnum; $i++){
      if($childnum > 1 && in_array($i,self::get_run_id($title))) continue;
      $ptitle = $title . ($childnum == 1 ? '':'_'.$i);
      switch (pcntl_fork())
      { # frok 处理
        case 0: # child process 
          cli_set_process_title($ptitle);
          Start::$pid = posix_getpid();
          self::install_usr1($callback,$childnum == 1 ? false :$i);
          exit;
        break;
        case -1: # fork fail;
          RunLog::write($ptitle . ' fork fail');
        break;
      }
    }
  }

  private static function install_usr1($name,$id=0)
  {
    $pname = $name . ($id ===false ?'':'_'.$id);
    RunLog::write(
      sprintf('%s install usr1 %s',$pname,
        pcntl_signal(SIGUSR1, function($signo) use($pname){
          !( SIGUSR1 === $signo ) ?: !( Start::$stop = true ) ?: RunLog::write("{$pname} process accept quiet exit sig");
        },false) ? !($LOG_TYPE = 1) ?:'succ' : !($LOG_TYPE = 2) ?: 'fail' ),
      $LOG_TYPE
    );
    self::$name($id);
  }

  private static function RedisState($id=false)
  { # Redis状态检测
    $MQR = new M\MQRedis;
    $objFlag = [];
    LOOP:
    array_map(function($obj) use($MQR){
      pcntl_signal_dispatch();
      !Start::$stop ?: exit(RunLog::write("RedisState get stop flag ,will exit now bye.."));
      try{$redis = self::getRedis($obj,true);$redis->ping();}catch(\Exception $e){
        if($e->getMessage() == 'Redis server went away'){
          $notifycall = function($num) use($obj,$e,$MQR){
            if($num > self::$redis_bug_nums){ # 同一台Redis服务器30 秒内连续 5次以上捕获异常
              # 发送redis异常报告给管理者，同时将数据状态置为不可用并且删除缓存
              # send
              # 数据置为不可用
              $MQR->update(['state' => 0])->where(['id' => $obj['id']])->run();
              # 删除缓存
              $MQR->getServersClear();
            }
            return false;
          };
          list($objFlag[$obj['id']]['time'],$objFlag[$obj['id']]['num']) = ($objFlag[$obj['id']]['time']??0) == 0 ? [time(),1]:
          (time()-$objFlag[$obj['id']]['time']>self::$redis_bug_time? ($notifycall()?:[0,0]) : [$objFlag[$obj['id']]['time'],$objFlag[$obj['id']]['num']+1]);
          RunLog::write($e->getMessage(),RunLog::EXCEPTION);
        }
      }
    }, $MQR->getServersCache180());
    usleep(1000000);
    GOTO LOOP;
  }

  private static function Timer($id)
  { # 消息出列检测
    $MQR = new M\MQRedis;
    $loopout = 0;
    LOOP:
    array_map(function($obj) use($id,$MQR,&$loopout){
      try{$redis = self::getRedis($obj);}catch(\Exception $e){
        RunLog::write($e->getMessage(),RunLog::EXCEPTION);
        GOTO ELOOP2;
      }
      $bucketKey = sprintf(self::$bucketKey,$id);
      $start = 0;
      LOOP2:
      pcntl_signal_dispatch();
      !Start::$stop ?: exit(RunLog::write("Timer_$id get stop flag ,will exit now bye.."));
      if($loopout >= self::$timer_rmax) exit(RunLog::write("Timer_$id run task nums $loopout,will exit now bye.."));
      $slave = $slave = empty($redis->slave) ? $redis : $redis->slave[array_rand($redis->slave)];
      # $data = $slave->zRange($bucketKey, $start, $start, true);
      $data = $slave->zRangeByScore($bucketKey,'-inf',time(),['withscores' => TRUE,'limit'=>[0,1]]);
      if(empty($data)) GOTO ELOOP2;
      $key = key($data);
      list($topic,$rid) = explode(':',$key);
      if($data[$key] > time()) GOTO ELOOP2;
      $readyKey = sprintf(self::$readyKey,$MQR->getTopicPriorityCache180($topic));
      $lockKey  = 'lock:'.$key;
      if($redis->setnx($lockKey,1))
      { # 加锁
        RunLog::write('move to consume '.$readyKey.', rpush value=' . $key.', max='.self::$timer_rmax.', cur='.$loopout);
        !$redis->zrem($bucketKey, $key)?:$redis->rpush($readyKey, $key)?:
        RunLog::write('move to ready queue failed,id=' . $key . ',data=' . $slave->hget(self::$jobinfo, $key), RunLog::EXCEPTION);
        $redis->delete($lockKey)?:RunLog::write('delete lock fail,key='.$lockKey,RunLog::EXCEPTION);
      }
      $start++;$loopout++;
      GOTO LOOP2;
      ELOOP2:
    }, $MQR->getServersCache180());
    usleep(100000);
    GOTO LOOP;
  }
  
  private static function Consume($id)
  { # 信息消费
    $MQR = new M\MQRedis;
    $loopout = 0;
    LOOP:
    $consume_nums_per_cycle=0;
    $rlist = $MQR->getServersCache180();
    array_map(function($obj) use($MQR,$id,& $consume_nums_per_cycle,&$loopout){
      try{$redis = self::getRedis($obj);}catch(\Exception $e){
        RunLog::write($e->getMessage().'line:'.$e->getLine(),RunLog::EXCEPTION);
        GOTO ELOOP2;
      }
      reset(self::$priorityConfig);
      LOOP2:
      if(!(list($priority,$nums)=each(self::$priorityConfig))) GOTO ELOOP2;
      $readyKey = sprintf(self::$readyKey,$priority);
      FOR1:
      $consume_nums_per_cycle++;
      pcntl_signal_dispatch();
      !Start::$stop ?: exit(RunLog::write("Consume_$id get stop flag ,will exit now bye.."));
      if($loopout >= self::$consume_rmax) exit(RunLog::write("Consume_$id run task nums $loopout,will exit now bye.."));
      $slave = $slave = empty($redis->slave) ? $redis : $redis->slave[array_rand($redis->slave)];
      $key = $redis->lpop($readyKey);
      if(empty($key)) GOTO LOOP2;
      # RunLog::write('ready pop key = '.$key);
      $data = json_decode($slave->hget(self::$jobinfo, $key),true);
      if(empty($data)) GOTO FOR1;
      RunLog::write('ready pop key = '.$key . ' data = '.json_encode($data));
      list($topic,$rid) = explode(':',$key);
      $info = $MQR->getTopicInfoCache180($topic);
      I\JScUrl::open($info['callback'],$info['method']);
      I\JScUrl::setOption(CURLOPT_TIMEOUT_MS,$info['timeout']);
      # RSA加密数据 设置自定义报文头进行传输
      #I\JScUrl::setHeader('X_POWERED_SIGN_BY:'.I\RSA::encrypt());
      if(I\JScUrl::send($data) && self::callbackInterpreter($info['backtype'],$info['succflag'],I\JScUrl::retText()))
      { # 交互成功且响应处理完成
      	# 记录通知
      	$redis->hdel(self::$jobinfo,$key);
        $MQR->setLog($info['id'],$rid,null,1);
        RunLog::write("notify id = {$info['id']} return ".I\JScUrl::retText(),RunLog::NOTIFY_SUCC);
      }else
      {
        $data['notify_nums'] = ($data['notify_nums']??0)+1;
        if($data['notify_nums'] > self::$notify_nums) {
          $redis->hdel(self::$jobinfo,$key);$MQR->setLog($info['id'],$rid,null,2);
          # 发送异常信息到邮件
          GOTO FI;
        }
        RunLog::write("notify id = {$key} return ".I\JScUrl::retText(),RunLog::NOTIFY_FAIL);
        $data['notify_time'] = date('Y-m-d H:i:s',time()+60*(2*$data['notify_nums']+1));
        
        $pipe = $redis->multi(\Redis::PIPELINE);
        $pipe->hSet(self::$jobinfo, $key, json_encode($data));
        $zkey = sprintf(self::$bucketKey,crc32($key) % self::$timer_nums);
        $pipe->zadd($zkey, strtotime($data['notify_time']), $key);
        $result = $pipe->exec();
        # 写入队列失败的话，置为异常数据 用户可主动恢复
        !$result[0] && $result[1] ? $MQR->setLog($info['id'],$rid,$data['notify_time']) : $MQR->setLog($info['id'],$rid,null,2);
        FI:
      }
      $loopout++;
      if(--$nums) GOTO FOR1;
      GOTO LOOP2;
      ELOOP2:
    }, $rlist);
    if($consume_nums_per_cycle == count($rlist)*count(self::$priorityConfig)) usleep(1000000);
    GOTO LOOP;
  }

  public static function getRedis($info,$force=false)
  { # Redis连接池
    $rid = $info['id'];
    $info = array_merge([$info['master']],$info['slave']??[]);
    $i = 0;
    $mkey = $info[$i]['host'].":".$info[$i]['port'].":".$i;
    $instance = & self::$instance[$mkey];
    LOOP:
    $key =  $info[$i]['host'].":".$info[$i]['port'].":".$i;
    if(!I\RegExp::Check('isIp',$info[$i]['host']) || !I\RegExp::Check('isPort',$info[$i]['port']))
      throw new \Exception("Redis:{$rid} : {$key} empty host or port");
    try{
      if(!$force && (time() - (self::$time[$rid][$key] ?? 0) ) > self::$redis_ping_interval && isset($instance))
      { # 心跳检测
        self::$time[$rid][$key] = time();
        if($instance->ping()!='+PONG'){
          unset($instance);
          RunLog::write('redis disconnect and server will reconnect..',RunLog::EXCEPTION);
        }
      }
    } catch (\Exception $e){
      unset($instance);
      RunLog::write($e->getMessage().'line:'.$e->getLine(),RunLog::EXCEPTION);
    }
    if(isset($instance)) GOTO CHECK;
    $redis = new \Redis;
    if($i == 0) $redis->rid = $rid;
    @$redis->connect($info[$i]['host'], $info[$i]['port'], 3);
    if(error_get_last()){ # 屏蔽错误捕获连接是否存在问题 redis状态置为不可用
      unset($instance);
      (new M\MQRedis)->update(['state' => 0])->where(['id' => $rid])->run();
      (new M\MQRedis)->getServersClear();
      throw new \Exception("Redis:{$rid} : {$key} Connection refused ".json_encode(error_get_last()));
    }
    if (!empty($info[$i]['auth'])){
      if(!$redis->auth($info[$i]['auth'])) {
        unset($instance);
        (new M\MQRedis)->update(['state' => 0])->where(['id' => $rid])->run();
        (new M\MQRedis)->getServersClear();
        throw new \Exception("Redis:{$rid} : {$key} NOAUTH Authentication required");
      }
    }
    self::$time[$rid][$key] = time();
    $instance = $redis;
    CHECK:
    if(( ++$i) < count($info)){ $instance = & self::$instance[$mkey]->slave[$i-1]; GOTO LOOP;}
    return self::$instance[$mkey];
  }
  
  public static function callbackInterpreter($Type = 'string',$succ_flag,$callbackstring)
  { # 回调内容解释器
    if(empty($callbackstring)) return false;
    switch ($Type)
    {
      case 'string':
        return $succ_flag == trim($callbackstring);
      break;
      case 'json':
        LOOP:
        $succ_flag = preg_replace('/({\s*((?!\s*}).)*?)([\$\w\_\"\'\[\]]+?)\.(\w+)(.*?})/','$1'.(isset($i)?'$3':!($i=1)?:'$json').'[\'$4\']$5',trim($succ_flag),-1,$count);
        if($count>0) GOTO LOOP;
        $succ_flag = preg_replace('/{\s*(\$[\$\w\.\"\'\[\]]+?)\s*}/', '$1', $succ_flag,-1);
        return create_function(null,"\$json = json_decode('".$callbackstring."',true);if(is_array(\$json) && !empty(current(\$json))) return ".$succ_flag.";\nreturn false;")();
      break;
      case 'xml':
        AGAIN:
        $succ_flag = preg_replace('/({\s*((?!\s*}).)*?)([\$\w\_\"\'\[\]]+?)\.(\w+)(.*?})/','$1'.(isset($i)?'$3':!($i=1)?:'$xml').'[\'$4\']$5',trim($succ_flag),-1,$count);
        if($count>0) GOTO AGAIN;
        $succ_flag = preg_replace('/{\s*(\$[\$\w\.\"\'\[\]]+?)\s*}/', '$1', $succ_flag,-1);
        return create_function(null,"\$parser = xml_parser_create();\nif(!xml_parse(\$parser, '".$callbackstring."')){xml_parser_free(\$parser); return false;}\$xml = json_decode(json_encode(simplexml_load_string('".$callbackstring."')),true);\nreturn ".$succ_flag.";")();
      break;
      default:
        return false;
    }
  } 
  private static function grep_process($name,callable $callback)
  { # 
    $res = [];
    if(!($f = popen("ps -ef | grep '$name' | grep -v grep | awk '{print $3,$8,$2}' ", 'r')) && feof($f)) GOTO END;
    # 当前进程PID
    $current_pid = posix_getpid();
    AGIN:
    $_line = trim(stream_get_line($f, 1024,"\n"));
    if(empty($_line) || is_null($_line)) GOTO END;
    $res[] = $callback(explode(" ",$_line),$current_pid);
    GOTO AGIN;
    END:
    fclose($f);
    return array_filter($res,function($v){if($v === 0 || $v) return true;});
  }
  public static function process_exists($name)
  { # 检测服务进程是否存在并停止
    return self::grep_process($name,function($arr)use($name){
      if(trim($arr[1])==$name) exit(0);
    });
  }
  public static function get_run_id($name)
  { # 检测指定服务运行中的序列
    return self::grep_process($name,function($arr,$current_pid) use($name){
      if(trim($arr[0])==$current_pid && preg_match("/^${name}/",trim($arr[1]))){
        $tmp = explode('_',$arr[1]);
        if(isset($tmp[1]) && is_numeric($tmp[1])){
          return intval($tmp[count($tmp)-1]);
        }
      }
    });
  }
  public static function get_child_pid($name)
  { # 获取子进程的PID
    return self::grep_process($name,function($arr,$current_pid) use($name){
      if(preg_match("/^${name}/",trim($arr[1]))){
        return $arr[2];
      }
    });
  }
  public static function get_process_num($name)
  { # 获取指定服务进程个数
    return count(self::get_child_pid($name));
  }
  public static function get_all_childs_pid()
  {
    $childPids= self::get_child_pid(self::JR_TIMER);
    $childPids= array_merge($childPids,self::get_child_pid(self::JR_CONSUME));
    $childPids= array_merge($childPids,self::get_child_pid(self::JR_REDIS_CHECKER));
    return $childPids;
  }
}