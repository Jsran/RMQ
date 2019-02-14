<?php
namespace jR\C\home;
use jR\M;
use jR\I;
use DOMDocument;
use DOMXPAth;
use jR\C\cli\RedisMessageQueue\Start;
class demo extends Base
{
	public $layout = null;
	
	static $instance;
	static $time = [];

	
	public function index()
	{
		parent::opcache();
		
		// $Formula = new I\InterestFormula;
		// dump( 
		//   $Formula->
		// 	Money(5000)->
		// 	# 利率 8%
		// 	Rate(0.1)->
		// 	# 期数
		// 	Period(12)->
		// 	# 回息公式
		// 	Formula(4)->
		// 	# 利息管理 1.5%
		// 	Manage(0.015)->
		// 	# 平台加息 1.7%
		// 	AIRate(0.020)->
		// 	# 额外加息 0.5%
		// 	EIRate(0.005)->
		// 	# 计息时间
		// 	InterestDate(1514682002)->
		// 	Run()
		// );
		// $a = 0.1/12;
		// dump($a*9000);
		// dump(bcadd('1.2333','1.666',2));

		// function echoTimes($msg, $max) {
		//     for ($i = 1; $i <= $max; $i++) {
		//         echo "$msg ...... $i\r\n<br/>";
		//         yield;
		//     }
		//     return "$msg the end value : $i\r\n<br/>";
		// }
		 
		// function task1() {
		//     $a = yield from echoTimes('task1', 10);
		//     echo $a;
		   
		// }

		// function task2()
		// {
		// 	$b = echoTimes('task2', 5);
		// 	yield from $b;
		// 	echo $b->getReturn();
		// }

		// function task3() {
		//     $c = echoTimes('task3', 6);
		// 	yield from $c;
		   
		// }

		// $scheduler = new I\QTask;
		// $scheduler->addTask(task1());

		// $scheduler->addTask(task2());
		
		// $scheduler->addTask(task3());
		// $scheduler->run();





		// $class = new class{ public $demo = 'test demos!'; public function demo(){return 'hehe demo';}};

		// $func = function(){return $this->demo();};

		// dump($func->call($class));

		// $demo =$func->bindTo($class);
		// dump($demo);
		// dump($demo());
		
		
		// $stat = $ob->query('select * from `v_user` limit 0,500000000');
		// while ($row = $stat->fetch(\PDO::FETCH_ASSOC)) {
		// 	yield $row;
		// }
		// foreach ($ob->oneSql('select * from s_chapter limit 0,5000',[],\PDO::FETCH_ASSOC,true) as $row)
		// {
		// 	dump($row);
		// }

		// $res = $ob->oneSql('select Id,Title from s_chapter limit 0,10000000',[],\PDO::FETCH_ASSOC,true);
		// $i = 0;
		// $res->rewind();
		// while ($res->valid())
		// {
		// 	if($i==0){
		// 		dump($res->current());
		// 	}elseif($i == 5)
		// 	{
		// 		dump($res->send('end'));
		// 	}else{
		// 		dump($res->send('next'));
		// 	}
		// 	$i++;
		// }
		// $ob = new I\formula();
		// #dump($ob->calculate(['type' => 4,'amount' => 10000,'rate' => 0.06,'deadline' => 6,'date' =>'2017-12-31']));
		// dump($this->EqualEndMonth(['period' => 6,'account' => 10000,'apr' => 0.06,'time' => 1514682002,'type' => null]));
	}
	
}
