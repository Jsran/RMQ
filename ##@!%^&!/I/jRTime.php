<?php
namespace jR\I;
class jRTime
{
	public $initTime = [];

	private $actTime = [];

	private $endTime = [];

	# 运行模式 1 常规模式
	private $mod = 1;

	public function __construct($time = null)
	{
		if(!is_null($time)) $this->initTime = [$time,date('Ymd',$time)];
	}
	public function setTime($time)
	{ # 设置时间戳
		$this->initTime = [$time,date('Ymd',$time)];
		$this->actTime = [];
		$this->endTime = [];
	}
	public function getTime()
	{ # 获取时间戳
		return $this->initTime[0];
	}
	public function setDate($date)
	{ # 设置日期
		$this->initTime = [strtotime($date),$date];
		$this->actTime = [];
		$this->endTime = [];
	}
	public function getDate()
	{ # 获取日期
		return $this->initTime[1];
	}
	public function getActDate()
	{
		return $this->actTime[1];
	}
	public function getActTime()
	{
		return $this->actTime[0];
	}
	public function getActDays()
	{
		return $this->diff($this->actTime[0],$this->endTime[0]);
	}
	public function getInitDays()
	{
		return $this->diff($this->initTime[0],$this->endTime[0]);
	}
	public function getEndDate()
	{
		return $this->endTime[1];
	}
	public function getEndTime()
	{
		return $this->endTime[0];
	}
	public function modify($mod)
	{
		if(preg_match("/(\+|\-)(\d{1,})(\w+)/",$mod,$match))
		{
			$type = ['D' => 'days', 'M' => 'month'];
			$mod = "{$match[1]}{$match[2]} {$type[$match[3]]}";
			$this->actTime = empty($this->actTime) ? $this->initTime : $this->endTime;
			$stamp = strtotime($this->initTime[1].$mod);
			if($match[3] == 'D') GOTO END;
			list($y,$m) = [date("Y",$this->initTime[0]),date("m",$this->initTime[0])];
			$ma = $m + $match[2];
			$n = $ma>12?$ma-12+(++$y)-$y:$ma;
			$_m = date("m",$stamp);
			if($n != $_m)
				$stamp = strtotime("{$y}{$_m}01-1 days");
			END:
			$this->endTime = [$stamp,date('Ymd',$stamp)];
			return $this;
		}
		return false;
		
	}
	public function diff( $time1, $time2)
	{
	  $diff = round(($time2 - $time1) / ( 60  * 60 * 24));
	  return $diff;
	}



}