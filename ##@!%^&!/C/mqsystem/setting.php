<?php
namespace jR\C\mqsystem;
use jR\M;
use jR\I;
use jR\C\cli\RedisMessageQueue\Start;
class setting extends Base
{
	public function Home()
	{
		$this->Start = 'jR\C\cli\RedisMessageQueue\Start';
	}
	public function UpCFGTrait()
	{ # 更新Trait配置文件
		$file = PATH.DS.CORE.DS.'C'.DS.'cli'.DS.'RedisMessageQueue'.DS.'CFGTrait.php';
		if(is_file($file)) unlink($file);
		$data = parent::$args;
		$string = '';
      	array_map(function($v) use(& $data){if(isset($data[$v])) unset($data[$v]);}, ['m','s','i']);
      	array_walk($data, function($v,$k)use(& $string){
      		$str = $v;
      		if(is_array($v)){
      		  $str = "[";
      		  array_walk($v,function($vv,$kk) use(& $str,$k){
      		  	$pcfg = [1 => 'Start::TASK_PRIORITY_HIGH',2=>'Start::TASK_PRIORITY_NORMAL',3=>'Start::TASK_PRIORITY_LOW'];
      		  	$str .= $k == 'priorityConfig' ?  "$pcfg[$kk] => $vv, " :"'$kk' => '$vv', ";
      		  });
      		  $str .= "]";
      		}
      		$string .= "  static $".$k." = ".($k == 'redis_bug_mail' ? "'$str'":$str).";\r\n";
      	});
		file_put_contents($file, "<?php\r\nnamespace jR\C\cli\RedisMessageQueue;\r\ntrait CFGTrait\r\n{\r\n{$string}}");
		parent::opcache();
		parent::json('success');
	}
}