<?php
namespace jR\I;
use DateTime;
use DateTimeZone;
class InterestFormula
{

	/**
	 * 利息公式生产模型
	 *
	 * @package  InterestFormula
	 * @see Money()         @param Integer  设置金额
	 * @see Rate()          @param Float    设置利率 
	 * @see Period()        @param Integer  设置期数
	 * @see Formula()       @param Integer  设置计息方式
	 * @see Manage()        @param Float    设置管理费率
	 * @see AIRate()        @param Float    设置平台加息
	 * @see EIRate()        @param Float    设置额外加息
	 * @see InterestDate()  @param integer  设置计息时间
	 * @see Run()           @return array   返回计算结果
	 * @version    2.0
	 * @author     JsRan <js@jsran.cn>
	 * @copyright  jsran.com
	 */

	private $runing = null;
	private $cfg    = [];
	private $must   = [ 'Money', 'Rate', 'Period', 'Formula', 'InterestDate' ];
	public  $return = [];

	# + bcadd - bcsub * bcmul / bcdiv % bcmod 冥 bcpow
	
	public function __construct()
	{ # __
		
		# 借给我多少钱，每月要还多少钱，总共还多少时间？ 
		# 
		# useage method
		# 
		# $Formula = new InterestFormula();
		# print_r( 
		#   $Formula->
		#	Money(120000)->
		#	# 利率 8%
		#	Rate(0.08)->
		#	# 期数
		#	Period(12)->
		#	# 回息公式
		#	Formula(1)->
		#	# 利息管理 1.5%
		#	Manage(0.015)->
		#	# 平台加息 1.7%
		#	AIRate(0.020)->
		#	# 额外加息 0.5%
		#	EIRate(0.005)->
		#	# 计息时间
		#	InterestDate(1402948572)->
		#	Run()
		# );
		
		# 导出加息();
	}
	public function __call($func,$args) : InterestFormula
	{ # 随心随性大法
		if(!method_exists($this,$func)) err ("Err: Other ".__CLASS__." of $func is not exists!");
		if(!in_array(ucwords($func), array_merge($this->must, ['Manage','AIRate','EIRate']))) err ("Err: Other ".__CLASS__." of $func is private!");
		return call_user_func_array("self::$func",$args);
	}
	public function Run()
	{ # run this set
		# print_r($this->cfg);
		$empty = false;
		array_walk($this->must,function($v) use(& $empty){
			if(!isset($this->cfg[$v]) && empty($this->cfg[$v]))
				$empty[] = $v;
		});
		if($empty) return sprintf('[ %s ] the param of these functions a empty values!', implode('、', $empty));
		$func = $this->FormulaGet($this->cfg['Formula'],true);
		if(!method_exists($this,$func))
			return 'The Formula does not exist!';
		return self::return($func);
	}
	public function FormulaGet($none = false,$t = 0) : string
	{ # return name
		$none = $none === false && isset($this->cfg['Formula']) ? $this->cfg['Formula'] : $none;
		$arr = array(
			3 => array('到期还款(天)','__03'),
			2 => array('到期还款(月)','__02'),
			4 => array('按月还款(月)','__04'),
			5 => array('固定还款(月)','__05'),
			1 => array('等额本息(月)','__01'),
			7 => array('等额本金(月)','__07'),
			8 => array('等本等息(月)','__08'),
			6 => array('按年还款(年)','__06'),
		);
		return isset($arr[$none]) ? $arr[$none][$t] : false;
	}
	private function Money($money)
	{ # set money
		$this->cfg = array_merge($this->cfg,['Money' => self::__F($money)]);
		return $this;
	}
	private function AIRate($rate)
	{ # set added interest rate
		$this->cfg = array_merge($this->cfg,['AIRate' => self::__F($rate, 8)]);
		return $this;
	}

	private function EIRate($rate)
	{ # set extra interest rate
		$this->cfg = array_merge($this->cfg,['EIRate' => self::__F($rate, 8)]);
		return $this;
	}
	
	private function Rate($rate)
	{ # set rate
		$this->cfg = array_merge($this->cfg,['Rate' => self::__F($rate, 8)]);
		return $this;
	}
	private function Period($period)
	{ # set Period
		$this->cfg = array_merge($this->cfg,['Period' => intval($period)]);
		return $this;
	}
	private function Formula($formula)
	{ # set Interest
		$this->cfg = array_merge($this->cfg,['Formula' => intval($formula)]);
		return $this;
	}
	private function Manage($val)
	{ # set Management rate 
		$this->cfg = array_merge($this->cfg,['Manage' => self::__F($val,8)]);
		return $this;
	}
	private function InterestDate($val)
	{ # set Interest date
		$this->cfg = array_merge($this->cfg,['InterestDate' => intval($val)]);
		return $this;
	}

	private function __F($folat,$len = 2)
	{ # 四舍六入 银行家舍入
		return self::__Fn(round($folat, $len, PHP_ROUND_HALF_EVEN), $len);
	}
	private function __Fn($foalt,$len = 2)
	{ # 保留固定位小数后n位
		return substr(sprintf('%.'.++$len.'f',$foalt),0,-1);
	}
	private function y2m($rate)
	{ # year rates to month rates
		return bcdiv($rate, 12,8);
	}
	private function m2d($rate)
	{ # month rates to days rates
		return bcdiv($rate, 30,8);
	}
	private function return($func) : array
	{ # 
		return in_array($this->cfg['Formula'], [1,4,7,8]) ? self::TypesOne($func) : self::TypesTwo($func);
	}
	private function TypesOne($func): array
	{
		$jRdt = new jRTime();
		$jRdt->setTime($this->cfg['InterestDate']);
		# start interest date
		$this->return = array_merge( $this->return ,['s_time' => $jRdt->getDate()]);
		$jRdt->modify("+{$this->cfg['Period']}". ($this->cfg['Formula'] === 3 ? 'D' :'M'));
		# end interest date
		$this->return = array_merge( $this->return ,['e_time' => $jRdt->getEndDate()]);
		# year rates 
		$this->return = array_merge( $this->return ,['y_rate' => $this->cfg['Rate']]);
		# month rates
		$this->return = array_merge( $this->return ,['m_rate' => self::y2m($this->return['y_rate'])]);
		# days rates
		$this->return = array_merge( $this->return ,['d_rate' => self::m2d($this->return['m_rate'])]);
		# service fee interest rate
		$this->return = array_merge( $this->return ,['s_rate' => self::__Fn($this->cfg['Manage']??0,8)]);
		# added interest rate
		$this->return = array_merge( $this->return ,['a_rate' => self::__Fn($this->cfg['AIRate']??0,8)]);
		# extra interest rate
		$this->return = array_merge( $this->return ,['e_rate' => self::__Fn($this->cfg['EIRate']??0,8)]);
		# loan period
		$this->return = array_merge( $this->return ,['period' => $this->cfg['Period']]);
		# total interest months 
		$this->return = array_merge( $this->return ,['m_total' => $this->cfg['Period']]);
		# total interest days 
		$this->return = array_merge( $this->return ,['d_total' => $jRdt->getInitDays()]);
		# total interest
		$this->return = array_merge( $this->return ,['i_total' => 0]);
		# total service fee
		$this->return = array_merge( $this->return ,['s_total' => 0]);
		# money amounts
		$this->return = array_merge( $this->return ,['amounts' => $this->cfg['Money']]);
		$jRdt->setTime($this->cfg['InterestDate']);
		array_splice($this->cfg,0);
		$this->runing = function ($i,$dc,$Bj,$Lx,$Bx,$Se,$uBj,$uLx,$PM) use($jRdt)
		{ # anonymous processing formula 
			$jRdt->modify("+{$PM}");
			return [
				# 期数索引
				'PeriodIndex' => $i,
				# 本期应回收的本金
				'recoveryBj' => $Bj,
				# 本期应回收的利息
				'recoveryLx' => $Lx,
				# 本期应回收的本息
				'recoveryBx' => $Bx,
				# 本期利息服务费
				'serviceFee' => $Se,
				# 本期附加费用
				'addedFee'   => self::__Fn($this->return['a_rate'] / $this->return['y_rate'] * $Lx),
				# 本期额外费用
				'extraFee'   => self::__Fn($this->return['e_rate'] / $this->return['y_rate'] * $Lx),
				# 剩余未支付的期数
				'unpaidPeriod' =>  $dc ? bcadd($this->return['period'],bcsub($dc,1)) - $i : 0,
				# 剩余未支付的本金
				'unpaidBj' => bcsub($this->return['amounts'],$uBj,2),
				# 剩余未支付的利息
				'unpaidLx' => bcsub($this->return['i_total'],$uLx,2),
				# 剩余未支付的本息
				'unpaidBx' => bcsub(bcadd($this->return['amounts'],$this->return['i_total'],2),bcadd($uLx,$uBj,2),2),
				# 本期计息时间
				'actDate' => $jRdt->getActDate(),
				# 本期计息时间
				'actTime' => $jRdt->getActTime(),
				# 本期回款时间
				'endDate' => $jRdt->getEndDate(),
				# 本期回款时间
				'endTime' => $jRdt->getEndTime(),
				# 本期天数
				'days' => $jRdt->getactDays(),
			];
		};
		$this->return['details'] =  self::$func($jRdt);
		return $this->return;
	}
	private function TypesTwo($func): array
	{
		$date = new DateTime();
		$date->setTimestamp($this->cfg['InterestDate']);
		$date->setTimezone(new \DateTimeZone('PRC'));	
		# start interest date
		$this->return = array_merge( $this->return ,['s_time' => $date->format('Ymd')]);
		$date->modify("+{$this->cfg['Period']} ". ($this->cfg['Formula'] === 3 ? 'days' :'month'));
		#$date->add(new \DateInterval("P{$this->cfg['Period']}". ($this->cfg['Formula'] === 3 ? 'D' :'M')))->format('Ymd');
		# end interest date
		$this->return = array_merge( $this->return ,['e_time' => $date->format('Ymd')]);
		$difx = new DateTime( $this->return['s_time'], new \DateTimeZone('PRC'));
		$diff = $date->diff($difx);
		# year rates 
		$this->return = array_merge( $this->return ,['y_rate' => $this->cfg['Rate']]);
		# month rates
		$this->return = array_merge( $this->return ,['m_rate' => self::y2m($this->return['y_rate'])]);
		# days rates
		$this->return = array_merge( $this->return ,['d_rate' => self::m2d($this->return['m_rate'])]);
		# service fee interest rate
		$this->return = array_merge( $this->return ,['s_rate' => self::__Fn($this->cfg['Manage']??0,8)]);
		# added interest rate
		$this->return = array_merge( $this->return ,['a_rate' => self::__Fn($this->cfg['AIRate']??0,8)]);
		# extra interest rate
		$this->return = array_merge( $this->return ,['e_rate' => self::__Fn($this->cfg['EIRate']??0,8)]);
		# loan period
		$this->return = array_merge( $this->return ,['period' => $this->cfg['Period']]);
		# total interest months 
		$this->return = array_merge( $this->return ,['m_total' => bcadd(bcmul($diff->y,12),$diff->m)]);
		# total interest days 
		$this->return = array_merge( $this->return ,['d_total' => $diff->format('%a')]);
		# total interest
		$this->return = array_merge( $this->return ,['i_total' => 0]);
		# total service fee
		$this->return = array_merge( $this->return ,['s_total' => 0]);
		# money amounts
		$this->return = array_merge( $this->return ,['amounts' => $this->cfg['Money']]);
		array_splice($this->cfg,0);
		$this->runing = function ($i,$dc,$Bj,$Lx,$Bx,$Se,$uBj,$uLx,$PM) use($difx)
		{ # anonymous processing formula 
			$clonedate = clone $difx;
			return [
				# 期数索引
				'PeriodIndex' => $i,
				# 本期应回收的本金
				'recoveryBj' => $Bj,
				# 本期应回收的利息
				'recoveryLx' => $Lx,
				# 本期应回收的本息
				'recoveryBx' => $Bx,
				# 本期利息服务费
				'serviceFee' => $Se,
				# 本期附加费用
				'addedFee'   => self::__Fn($this->return['a_rate'] / $this->return['y_rate'] * $Lx),
				# 本期额外费用
				'extraFee'   => self::__Fn($this->return['e_rate'] / $this->return['y_rate'] * $Lx),
				# 剩余未支付的期数
				'unpaidPeriod' =>  $dc ? bcadd($this->return['period'],bcsub($dc,1)) - $i : 0,
				# 剩余未支付的本金
				'unpaidBj' => bcsub($this->return['amounts'],$uBj,2),
				# 剩余未支付的利息
				'unpaidLx' => bcsub($this->return['i_total'],$uLx,2),
				# 剩余未支付的本息
				'unpaidBx' => bcsub(bcadd($this->return['amounts'],$this->return['i_total'],2),bcadd($uLx,$uBj,2),2),
				# 本期计息时间
				'actDate' => $difx->format('Ymd'),
				# 本期计息时间
				'actTime' => $difx->getTimestamp(),
				# 本期回款时间
				'endDate' => $difx->add(new \DateInterval("P{$PM}"))->format('Ymd'),
				# 本期回款时间
				'endTime' => $difx->getTimestamp(),
				# 本期天数
				'days' => $clonedate->diff($difx)->format('%a'),
			];
		};
		$this->return['details'] =  self::$func($difx);
		return $this->return;
	}
	private function __01()
	{ # 等额本息(月)
		$_li = pow((1+$this->return['m_rate']),$this->return['period']);
		$Bx = self::__F($this->return['amounts'] * ($this->return['m_rate'] * $_li) / ($_li - 1));
		$this->return['i_total'] = self::__F($Bx * $this->return['period'] - $this->return['amounts']);
		$this->return['s_total'] = bcmul($this->return['i_total'],$this->return['s_rate'],2);
		$uBj = $uLx = $uSe = 0;
		$i = 0b0001;
		Act:
		$Lx = $i > 1 ? ( $i == $this->return['period'] ? self::__F($this->return['i_total'] - $uLx): self::__F(($this->return['amounts'] * $this->return['m_rate'] - $Bx) * pow((1+$this->return['m_rate']),$i-1) + $Bx) ) : self::__F($this->return['amounts'] * $this->return ['m_rate']);
		$Bj = $i == $this->return['period'] ? self::__Fn($this->return['amounts'] - $uBj) :self::__Fn($Bx - $Lx);
		$Se = $i == $this->return['period'] ? self::__Fn($this->return['s_total'] - $uSe) :bcmul($Lx,$this->return['s_rate'],2);
		list($uBj,$uLx,$uSe) = [$uBj+$Bj,$uLx+$Lx,$uSe+$Se];
		$result[$i-1] = call_user_func($this->runing,$i,1,$Bj,$Lx,$Bx,$Se,$uBj,$uLx,"{$i}M");
		if($i < $this->return['period']):$i++; goto Act;endif;
		return $result;
	}
	private function __02()
	{ # 到期还款(月)
		$i = 0b0001;
		$Bj = $this->return['amounts'];
		$Lx = bcmul(bcmul($this->return['amounts'], $this->return['m_rate'],2),$this->return['period'],2);
		$this->return['i_total'] = $Lx;
		$uBj = $uLx = $uSe = 0;
		$Bx = bcadd($Bj, $Lx,2);
		$Se = bcmul($Lx,$this->return['s_rate'],2);
		list($uBj,$uLx,$uSe) = [$uBj+$Bj,$uLx+$Lx,$uSe+$Se];
		$result[$i-1] = call_user_func($this->runing,$i,0,$Bj,$Lx,$Bx,$Se,$uBj,$uLx,"{$this->return['period']}M");
		$this->return['s_total'] = $uSe;
		return $result;
	}
	private function __03()
	{ # 到期还款(天)
		$i = 0b0001;
		$Bj = $this->return['amounts'];
		$Lx = bcmul(bcmul($this->return['amounts'], $this->return['d_rate'],2),$this->return['period'],2);
		$this->return['i_total'] = $Lx;
		$uBj = $uLx = $uSe = 0;
		$Bx = bcadd($Bj, $Lx,2);
		$Se = bcmul($Lx,$this->return['s_rate'],2);
		list($uBj,$uLx,$uSe) = [$uBj+$Bj,$uLx+$Lx,$uSe+$Se];
		$result[$i-1] = call_user_func($this->runing,$i,0,$Bj,$Lx,$Bx,$Se,$uBj,$uLx,"{$this->return['period']}D");
		$this->return['s_total'] = $uSe;
		return $result;
	}
	private function __04()
	{ # 按月还款(月)
		$i = 0b0001;
		$Lx = bcmul($this->return['amounts'], $this->return['m_rate'],2);
		# $Lx = self::__Fn($this->return['y_rate']/12 * $this->return['amounts']);
		$Se = bcmul($Lx,$this->return['s_rate'],2);
		# $this->return['i_total'] = bcmul($Lx,$this->return['period'],2);
		$this->return['i_total'] = self::__Fn($this->return['amounts'] * $this->return['y_rate'] / 12 * $this->return['period']);
		$this->return['s_total'] = bcmul($this->return['i_total'],$this->return['s_rate'],2);
		$uBj = $uLx = $uSe = 0;
		Act:
		list($Bj,$Lx,$Se) = bccomp($i,$this->return['period']) === 0 ? 
		[$this->return['amounts'],self::__Fn($this->return['i_total'] - $uLx),self::__Fn($this->return['s_total'] - $uSe)] : [self::__Fn(0.00),$Lx,$Se];
		$Bx = bcadd($Bj, $Lx,2);
		list($uBj,$uLx,$uSe) = [$uBj+$Bj,$uLx+$Lx,$uSe+$Se];
		$result[$i-1] = call_user_func($this->runing,$i,1,$Bj,$Lx,$Bx,$Se,$uBj,$uLx,"{$i}M");
		unset($clonedate);
		if($i < $this->return['period']):$i++; goto Act;endif;
		return $result;
	}
	private function __05()
	{ # 固定还款(月)
		$i = 0b0001;
		$tempdate = clone $date = func_get_arg(0);
		$this->return['i_total'] = bcmul(bcmul($this->return['amounts'], $this->return['m_rate'],2),$this->return['period'],2);
		$this->return['s_total'] = bcmul($this->return['i_total'],$this->return['s_rate'],2);
		$uBj = $uLx = $uSe = 0;
		$today = $date->format('d');
		$size = date('t', $date->getTimestamp());
		$Md = in_array($today,[15,25]) ? 0 : 1;
		$lastday = function () use ($date,$tempdate,$today){$tempdate->add(new \DateInterval("P1M"));return $date->diff($tempdate)->format('%a') - ($today-25) . "D";};
		Act:
		$PM = $Md && $i == 1 ? ($today < 25  ? ($today < 15 ? 15 - $today . "D" : 25 - $today . "D" ) : $lastday()) : ( bccomp($i,bcadd($this->return['period'],$Md)) === 0 && $Md ? max($size,date('t', $date->getTimestamp())) - $result[0]['days'] . "D" : "1M");
		$Lx = bccomp($i,bcadd($this->return['period'],$Md)) === 0 ? self::__Fn($this->return['i_total'] - $uLx) : ( $Md && $i==1 ? bcmul(bcmul($this->return['amounts'], $this->return['d_rate'],2),intval($PM),2) : bcmul($this->return['amounts'], $this->return['m_rate'],2) );
		list($Bj,$Se) = bccomp($i,bcadd($this->return['period'],$Md)) === 0 ? 
		[$this->return['amounts'],self::__Fn($this->return['s_total'] - $uSe)] : [self::__Fn(0.00),bcmul($Lx,$this->return['s_rate'],2)];
		$Bx = bcadd($Bj, $Lx,2);
		list($uBj,$uLx,$uSe) = [$uBj+$Bj,$uLx+$Lx,$uSe+$Se];
		$result[$i-1] = call_user_func($this->runing,$i,$Md + 1,$Bj,$Lx,$Bx,$Se,$uBj,$uLx,$PM);
		unset($tempdate);
		if($i < bcadd($this->return['period'],$Md)):$i++; goto Act;endif;
		return $result;
	}
	private function __07()
	{ # 等额本金(月)
		$i = 0b0001;
		$Bj = bcdiv($this->return['amounts'], $this->return['period'],2);
		$this->return['i_total'] = bcmul(bcadd($this->return['period'],1) * $this->return['amounts'],bcdiv($this->return['m_rate'],2,8),2);
		$this->return['s_total'] = bcmul($this->return['i_total'],$this->return['s_rate'],2);
		$uBj = $uLx = $uSe = 0;	
		Act:
		$Lx =  $i == $this->return['period'] ?  self::__F($this->return['i_total'] - $uLx) :bcmul(self::__Fn($this->return['amounts'] - $uBj),$this->return['m_rate'],2); 
		list($Bj,$Se) = bccomp($i,$this->return['period']) === 0 ? 
		[self::__Fn($this->return['amounts'] - $uBj),self::__Fn($this->return['s_total'] - $uSe)] : [$Bj,bcmul($Lx,$this->return['s_rate'],2)];
		$Bx = bcadd($Bj, $Lx,2);
		list($uBj,$uLx,$uSe) = [$uBj+$Bj,$uLx+$Lx,$uSe+$Se];
		$result[$i-1] = call_user_func($this->runing,$i,1,$Bj,$Lx,$Bx,$Se,$uBj,$uLx,"{$i}M");
		if($i < $this->return['period']):$i++; goto Act;endif;
		return $result;
	}
	private function __08()
	{ # 等本等息(月)
		$i = 0b0001;
		$Bj = bcdiv($this->return['amounts'], $this->return['period'],2);
		$Lx = bcmul($this->return['amounts'], $this->return['m_rate'],2);
		$Se = bcmul($Lx,$this->return['s_rate'],2);
		$this->return['i_total'] = bcmul($Lx, $this->return['period'],2);
		$this->return['s_total'] = bcmul(bcmul($Lx,$this->return['s_rate'],2), $this->return['period'],2);
		$Bx = bcadd($Bj, $Lx,2);
		$uBj = $uLx = $uSe = 0;	
		Act:
		list($Bj,$Lx,$Se) = bccomp($i,$this->return['period']) === 0 ? 
		[self::__Fn($this->return['amounts'] - $uBj),self::__Fn($this->return['i_total'] - $uLx),self::__Fn($this->return['s_total'] - $uSe)] : [$Bj,$Lx,$Se];
		list($uBj,$uLx,$uSe) = [$uBj+$Bj,$uLx+$Lx,$uSe+$Se];
		$result[$i-1] = call_user_func($this->runing,$i,1,$Bj,$Lx,$Bx,$Se,$uBj,$uLx,"{$i}M");
		if($i < $this->return['period']):$i++; goto Act;endif;
		return $result;
	}
	/**
	 * 债权转让
	 *
	 * 转让人利息计算
	 * 当期待收利息(转让时当期待收利息) * ((当期日期-本期开始计息日期)/当期总天数)
	 *
	 * 承接人收益率
	 * 
	 */

	/*
	# 交易时标号前缀 + "db_" 字符串

	create table if not exists db_invest_list (
	Id int(11) unsigned not null auto_increment comment '自增编号',
	Uid int(11) not null default '0' comment '用户编号',
	UType tinyint(1) default '0' comment '用户类型 0 个人 1 企业',
	IType tinyint(1) default '1' comment '标的类型 0 信用 1 车贷',
	Title varchar(255) not null comment '借款标题',
	Purpose varchar(50) not null comment '借款用途',
	Content text comment '详情描述',
	Photos text comment '图片们',
	Money decimal(13,2) not null default '0.00' comment '借款总额',
	Balance decimal(13,2) not null default '0.00' comment '筹款余额',
	ServiceFee decimal(13,2) default '0.00' comment '服务费用',
	Period tinyint(2) not null default '0' comment '借款期数',
	EndPeriod tinyint(2) not null default '0' comment '完结期数',
	Rate decimal(9,8) not null default '0.00000000' comment '年化利率',
	Manage decimal(9,8) not null default '0.00000000' comment '利息管理费率',
	AIRate decimal(9,8) not null default '0.00000000' comment '平台加息费率',
	Formula tinyint(1) not null default '1' comment '计算公式',
	MulMoney decimal(13,2) default '10.00' comment '买入倍数',
	MinMoney decimal(13,2) default '100.00' comment '最小买入',
	MaxMoney decimal(13,2) default '0.00' comment '最大买入 0 无限',
	CreateDate int(10) not null  comment '创建时间',
	Creator int(5) not null comment '创建人员',
	AuditsDate int(10) default '0' comment '审核时间',
	Auditor int(5) default '0' comment '审核人员',
	StartDate int(10) not null comment '开始时间',
	TermDate int(7) default '604800' comment '筹集时间',
	SuccessDate int(10) default '0' comment '满标时间',
	InterestDate int(10) default '0' comment '计息时间',
	EndDate int(10) default '0' comment '结束时间',
	Details text comment '更新操作详情记录',
	State tinyint(1) default '0' comment '0 发布待审 1 筹款中 2 满标待放款 3 还款中 4 已还完 5 初审未通过 6 已流标',
	IsHide tinyint(1) default '0' comment '是否隐藏 0 否 1 是',
	IsDele tinyint(1) default '0' comment '是否删除 0 否 1 是',
	primary key (Id),
	key uid (Uid)
	) engine=InnoDb auto_increment=120000 default charset=utf8  comment '等本标的列表';

	# 客户端买入方法
	# insert into db_invest_buy(Iid,Uid,Money,Details,LastTime) values() 
	# on duplicate key update Money=Money+values(Money),Count=Count+1,Details=concat(Details,"|",1000,",",unix_timestamp()),LastTime=unix_timestamp()
	
	create table if not exists db_invest_buy(
	Id int(11) unsigned not null auto_increment comment '自增编号',
	Iid int(11) unsigned not null comment '标的编号',
	Uid int(11) not null default '0' comment '用户编号',
	Iuid int(11) default '0' comment '邀约用户',
	Aid int(11) unsigned default '0' comment '授权编号',
	Aindex tinyint(2) default '1' comment '复投索引',
	Money decimal(13,2) not null default '0.00' comment '买入金额',
	Auto tinyint(1) default '0' comment '是否自动投标 0 否 1 是',
	Bfrom tinyint(1) default '0' comment '0 PC 1 ANDRIOD 2 IOS 3 其他',
	Total tinyint(2) default '1' comment '买入总次数',
	Details text comment '买入详情 Money|Time,Money|Time',
	State tinyint(1) default '0' comment '0 进行中 1 放款中 2 还款中 3 已还款 4 已流标',
	LastTime int(10) not null comment '最后操作时间',
	primary key (Id),
	key uid (Uid),
	key iid (Iid),
	unique key buykey (Uid,Iid)
	) engine=InnoDb default charset=utf8 comment '标的买入明细';

	# 买入前，update s_user_depository set 
	# 客户端买入方法
	# insert into db_buy_auto(Iid,Uid,LastTime) values() 
	# on duplicate key update Auto_term=Auto_term|values(Auto_term),LastTime=unix_timestamp()

	create table if not exists db_buy_auto (
	Id int(11) unsigned not null auto_increment comment '自增编号',
	IId int(11) unsigned not null comment '标的编号',
	Uid int(11) not null default '0' comment '用户编号',
	Total tinyint(3) default '0' comment '已复投次数',
	Details varchar(512) not null comment '标号 "," 分割',
	Auto_term decimal(13,2) not null default '0.0' comment '预计授权额度',
	Used_term decimal(13,2) not null default '0.0' comment '已用额度额度',
	State tinyint(1) comment '是否完结 0 正常复投 1 正常完结 ',
	LastTime int(10) default '0' comment '最后操作时间',
	EndTime int(10) default '0' comment '终止时间',
	primary key (Id),
	unique key buykey (Uid,Iid)
	) engine=InnoDb default charset=utf8 comment '标的买入产品自动续投关系表';

	create table if not exists db_invest_buy_repay(
	Id int(11) unsigned not null auto_increment comment '自增编号',
	Uid int(11) not null default '0' comment '用户编号',
	Iid int(11) unsigned not null default '0' comment '标的编号',
	Bid int(11) unsigned not null default '0' comment '买入编号',
	PeriodIndex tinyint(2) not null comment '期数索引',
	RecoveryBj decimal(13,2) not null comment '本期本金',
	RecoveryLx decimal(13,2) not null comment '本期利息',
	RecoveryBx decimal(13,2) not null comment '本期本息',
	ServiceFee decimal(13,2) default '0.00' comment '利息服务费',
	AddedFee decimal(13,2) default '0.00' comment '附加费用',
	ExtraFee decimal(13,2) default '0.00' comment '额外费用',
	CountDays tinyint(3) not null comment '本期天数',
	ActDate date not null default '20180701' comment '计息日期',
	ActTime int(10) not null comment '计息时间',
	EndDate date not null default '20180701' comment '回款日期',
	EndTime int(10) not null comment '回款时间',
	SelfTime int(10) default '0' commetn '实收时间',
	is_Bx tinyint(1) default '0' comment '本期本息 0 未支付 1 已支付',
	is_AF tinyint(1) default '0' comment '附加费用 0 未支付 1 已支付',
	is_EF tinyint(1) default '0' comment '额外费用 0 未支付 1 已支付',
	is_SF tinyint(1) default '0' comment '利息服务 0 未收取 1 已收取',
	State tinyint(1) default '0' comment '0 未还 1 自还 2 代还',
	primary key(Id),
	key uid (Uid),
	key iid (Iid),
	key periodindex (PeriodIndex)
	) engine=InnoDb default charset=utf8 comment '出借人回息明细';

	create table if not exists db_invest_repay(
	Id int(11) unsigned not null auto_increment comment '自增编号',
	Uid int(11) not null default '0' comment '用户编号',

	) engine=InnoDb default charset=utf8 comment '借款人还息明细';

	 */
	
}