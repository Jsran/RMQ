<?php
namespace jR\C\cli\RedisMessageQueue;
trait CFGTrait
{
  static $smtpconfig = ['host' => 'stmp.qq.com', 'port' => '25', 'user' => '452815115@qq.com', 'pass' => '###', ];
  static $consume_nums = 6;
  static $consume_rmax = 10000;
  static $priorityConfig = [Start::TASK_PRIORITY_HIGH => 5, Start::TASK_PRIORITY_NORMAL => 3, Start::TASK_PRIORITY_LOW => 2, ];
  static $timer_nums = 2;
  static $timer_rmax = 10000;
  static $notify_nums = 10;
  static $redis_bug_mail = '452815115@qq.com';
  static $redis_ping_interval = 50;
  static $redis_bug_time = 30;
  static $redis_bug_nums = 5;
}
