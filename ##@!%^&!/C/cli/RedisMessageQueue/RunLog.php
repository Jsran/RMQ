<?php
namespace jR\C\cli\RedisMessageQueue;
class RunLog
{
    # 延时队列运行日志记录
    # js@jsran.cn
    # by 2018-12-26 司丙然
    
    # 正常日志
    const NORMAL       = 1;
    # 错误日志
    const EXCEPTION    = 2;
    # 通知日志
    const NOTIFY_START = 3;
    const NOTIFY_FAIL  = 4;
    const NOTIFY_SUCC  = 5;
    # 请求日志
    const REQUEST      = 6;
    # 日志路径
    const LOG_PATH = PATH.DS.'O'.DS.'DelayQueueLog/%08d/';

    private static $LogName = [
        self::NORMAL       => 'notice',
        self::EXCEPTION    => 'error',
        self::NOTIFY_START => 'notify',
        self::NOTIFY_FAIL  => 'notify',
        self::NOTIFY_SUCC  => 'notify',
        self::REQUEST      => 'request',
    ];

    public static function write( string $str, int $flag = self::NORMAL) : int
    {
        $dir = sprintf(self::LOG_PATH,date('Ymd'));
        # 检测目录并循环创建
        !is_dir($dir) && mkdir($dir,0755,true);
        # 写入日志文件
        return file_put_contents(
            $dir.self::$LogName[$flag].".log", 
            json_encode( ['Date' => date('H:i:s'),'PName' => cli_get_process_title(),'PID' => posix_getpid(), 'Log' => $str]) . "\n", FILE_APPEND
        );
    }
}