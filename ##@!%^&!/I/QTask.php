<?php
namespace jR\I;
use SplQueue;
use Generator;
Class QTask
{
  protected $TaskQueue;
  protected $Tid = 0;

  public function __construct()
  { # 创建队列
    $this->TaskQueue = new SplQueue();
  }
  public function addTask(Generator $Task)
  { # 生产一个包含Generator接口的协同程序对象
    $this->TaskQueue->enqueue((object) ['Tid' => $this->Tid,'Coroutine' => $Task,'Fist' => true,'Send' => null]);
    $this->Tid++;
    return $this->Tid-1;
  }
  public function runing(& $Task)
  { # 执行协同程序
  	$retval = $Task->Fist ?
  	  $Task->Coroutine->current() :
  	  $Task->Coroutine->send($Task->Send);
  	list($Task->Fist,$Task->Send) = [false,null];
  	return $retval;
  }
  public function run()
  { # 执行队列中协同程序
    RES:
    if(!$this->TaskQueue->isEmpty()):
      $Task = $this->TaskQueue->dequeue();
      $retval = $this->runing($Task);
      if ($Task->Coroutine->valid())
      	$this->TaskQueue->enqueue($Task);
      GOTO RES;
    endif;
  }
}

