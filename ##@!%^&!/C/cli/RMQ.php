<?php
namespace jR\C\cli;
use jR\M;
use jR\I;
use jR\C\cli\RedisMessageQueue\Start;
use jR\C\cli\RedisMessageQueue\RunLog;
class RMQ extends Base
{
  public function Start()
  { # 开启守护 nohup php index.php m=Cli s=RMQ i=Start >/dev/null 2>&1 &
    # 守护状态
    Start::process_exists(Start::JR_MASTER);
    # 信号忽略
    pcntl_signal(SIGPIPE,SIG_IGN);
    # 进程名称
    cli_set_process_title(Start::JR_MASTER);
    # 主进程PID
    Start::$pid = posix_getpid();
    // $CFGTrait =  PATH.DS.CORE.DS.'C'.DS.'cli'.DS.'RedisMessageQueue'.DS.'CFGTrait.php';
    // Start::$ctime = md5(file_get_contents($CFGTrait));
    // RunLog::write(Start::$ctime);
    # 注册信号处理
    RunLog::write(
      sprintf('master install usr2 %s',
        pcntl_signal(SIGUSR2, function($signo){
          !( SIGUSR2 === $signo ) ?: !( Start::$stop = true ) ?: RunLog::write('master process accept quiet exit sig');
        },false) ? !($LOG_TYPE = 1) ?:'succ' : !($LOG_TYPE = 2) ?: 'fail' ),
      $LOG_TYPE
    );
    LOOP:
    // $TMd5 = md5(file_get_contents($CFGTrait));
    // if($TMd5 != Start::$ctime)
    // { # 文件修改后进行参数重置
    //   $TtaitStatic = (new \ReflectionClass('jR\C\cli\RedisMessageQueue\CFGTrait'))->getStaticProperties();

    //   array_walk($TtaitStatic,function($v,$k){Start::${$k} = $v;});
    //   Start::$ctime = $TMd5;
    //   RunLog::write('CFGTrait setting succ!'.Start::$timer_rmax);
    // }
    array_map(function($v){ count($v) == 3 && Start::processRun($v[0],$v[1],$v[2]);}, 
      [[Start::JR_TIMER,'Timer',Start::$timer_nums],[Start::JR_CONSUME,'Consume',Start::$consume_nums],[Start::JR_REDIS_CHECKER,'RedisState',1]]);
    # 进程回收
    pcntl_waitpid(0, $status,WNOHANG); 
    if(!Start::$stop) GOTO WAIT; 	
    array_map(function($pid){
      RunLog::write(
        sprintf("notify child process %d to exit %s",$pid,
          # 发送信号
          posix_kill($pid, SIGUSR1) ? !($LOG_TYPE = 1) ?:'succ' : !($LOG_TYPE = 2) ?: 'fail' ),
        $LOG_TYPE
      );
    },Start::get_all_childs_pid()) ?:
    # 回收进程
    is_null(pcntl_waitpid(0, $status, WNOHANG)) ?: exit(RunLog::write('all childs process has exited,parent will exit now bye bye'));
    WAIT:
    # 检查信号
    pcntl_signal_dispatch();
    usleep(500000);
    GOTO LOOP;
    exit;
  }
  public function Stop()
  { # 信号退出
    pclose(
      popen(
        "ps -ef | grep ".Start::JR_MASTER."| grep -v grep | head -n 1 | awk '{print $2}' | xargs kill -USR2",
         'r'
      )
    );
  }
  public function Reload()
  { # 平滑重启子进程
    pclose(
      popen(
        "ps -ef | grep jR | grep -v grep | grep -v ".Start::JR_MASTER." | awk '{print $2}' | xargs kill -USR1",
         'r'
      )
    );
  }
  public function Kill()
  { # 强制杀死
    pclose(
      popen(
        "ps -ef | grep jR-| grep -v grep | awk '{print $2}' | xargs kill -s 9",
         'r'
      )
    );
  }

  public function Timer()
  { # 定时闹钟信号
    pcntl_signal(SIGALRM, array('', 'signalHandle'), false);
    pcntl_alarm(1);
  }
}