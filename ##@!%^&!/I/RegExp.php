<?php
namespace jR\I;
class RegExp
{
	/**
	 * 常用正则归档
	 * PHP Version 7.0+
	 * js@jsran.cn
	 * 201609013
	 * 正则表达式简单教程 http://www.w3cschool.cn/zhengzebiaodashi
	 * 常用正则表达式汇总 http://www.w3cschool.cn/regexp/jhbv1pr1.html
	 * 模式修饰符 http://www.php.net/manual/zh/reference.pcre.pattern.modifiers.php
	 **/
	public function __construct()
	{ # 创建对象时,会效验PHP版本!
		if (version_compare("7.0", PHP_VERSION, "ge"))
			exit("the PHP version must be 7.0+, your version is ".PHP_VERSION);
	}
	public static function One(array $match, & $i = null) : bool
	{ # int preg_match ( string $pattern , string $subject [, array &$matches [, int $flags = 0 [, int $offset = 0 ]]] )
		return preg_match(args($match[0]), args($match[1]), $i, args($match[2],0,'d'));
	}
	public static function All(array $match, & $i = null) : bool
	{ # int preg_match_all ( string $pattern , string $subject [, array &$matches [, int $flags = PREG_PATTERN_ORDER [, int $offset = 0 ]]] )
		return preg_match_all(args($match[0]), args($match[1]), $i, args($match[2],PREG_PATTERN_ORDER,'d'));
	}
	public static function Check(string $i,string $u) : bool
	{ # 
		if(!method_exists(__CLASS__, $i))
			return false;
		return self::$i($u);
	}
	private static function isUser(string $u) : bool
	{ # 效验用户格式
		return self::One([[
			# 6-16位字符，字母开头，数字结尾，只包含字母数字下划线
			'/^[a-zA-Z][\w_]{4,14}\d$/',
			# 6-16位字符，数字开头，字母结尾，只包含字母数字下划线
			'/^\d[\w_]{4,14}[a-zA-Z]$/',
			# 
			'/^[a-zA-Zxa0-xff_][0-9a-zA-Zxa0-xff_]{3,15}$/i'
			][2],$u]);
	}
	private static function isPass(string $u) : bool
	{ # 效验密码强度
		return self::One([[
			# 8-16位字符，必须包含数字和大小写字母，不能使用特殊字符，
			'/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,16}$/'
			][0],$u]);
	}
	private static function isPhone(string $u) : bool
	{ # 效验手机号码
		# 移动号段：
		# 134 135 136 137 138 139 147 150 151 152 157 158 159 178 182 183 184 187 188
		# 联通号段：
		# 130 131 132 145 155 156 171 175 176 185 186
		# 电信号段：
		# 133 149 153 173 177 180 181 189
		# 虚拟运营商:
		# 170
		return self::One([[
			'/^(13[0-9]|14[5|7|9]|15[0-35-9]|17[0-35-8]|18[0-9])\d{8}$/',
			][0],$u]);
	}
	private static function AllUrl(string $u)
	{ # 获取全部地址
		# (                                       # Capture 1: entire matched URL
		#   (?:
		#     https?://                           # http or https protocol
		#     |                                   #   or
		#     www\d{0,3}[.]                       # "www.", "www1.", "www2." … "www999."
		#     |                                   #   or
		#     [a-z0-9.\-]+[.][a-z]{2,4}/          # looks like domain name followed by a slash
		#   )
		#   (?:                                   # One or more:
		#     [^\s()<>]+                          # Run of non-space, non-()<>
		#     |                                   #   or
		#     \(([^\s()<>]+|(\([^\s()<>]+\)))*\)  # balanced parens, up to 2 levels
		#   )+
		#   (?:                                   # End with:
		#     \(([^\s()<>]+|(\([^\s()<>]+\)))*\)  # balanced parens, up to 2 levels
		#     |                                   #   or
		#     [^\s`!()\[\]{};:'".,<>?«»“”‘’]      # not a space or one of these punct chars
		#   )
		# )
		# 
		# (                                       # Capture 1: entire matched URL
		#   (?:
		#     [a-z][\w-]+:                        # URL protocol and colon
		#     (?:
		#       /{1,3}                            # 1-3 slashes
		#       |                                 #   or
		#       [a-z0-9%]                         # Single letter or digit or '%'
		#                                         # (Trying not to match e.g. "URI::Escape")
		#     )
		#     |                                   #   or
		#     www\d{0,3}[.]                       # "www.", "www1.", "www2." … "www999."
		#     |                                   #   or
		#     [a-z0-9.\-]+[.][a-z]{2,4}/          # looks like domain name followed by a slash
		#   )
		#   (?:                                   # One or more:
		#     [^\s()<>]+                          # Run of non-space, non-()<>
		#     |                                   #   or
		#     \(([^\s()<>]+|(\([^\s()<>]+\)))*\)  # balanced parens, up to 2 levels
		#   )+
		#   (?:                                   # End with:
		#     \(([^\s()<>]+|(\([^\s()<>]+\)))*\)  # balanced parens, up to 2 levels
		#     |                                   #   or
		#     [^\s`!()\[\]{};:'".,<>?«»“”‘’]      # not a space or one of these punct chars
		#   )
		# )
		return self::All([['/((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'\".,<>?«»“”‘’]))/',
			'/((?:[a-z][\w-]+:(?:/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'\".,<>?«»“”‘’]))/'][0],$u]);
	}
	private static function isCard(string $u) : bool
	{ # 效验身份证
		# 新的18位身份证号码各位的含义: 
		# 1-6位市旗区的行政区划代码，按GB/T2260的规定执行。 
		# 7-14位出生的年月日，按GB/T7408的规定执行。 
		# 15-17位为顺序号，其中17位男为单数，女为双数。 
		# 18位为校验码，0-9和X，由公式随机产生。 
 
		# 15位身份证号码各位的含义: 
		# 1-6位市旗区的行政区划代码，按GB/T2260的规定执行。
		# 7-12位出生年月日,比如670401代表1967年4月1日。
		# 13-15位为顺序号，其中15位男为单数，女为双数。
		if(!self::One([[
			'/(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}(?:\d|x|X)$)/',
			][0],$u]))
			return false;
		if(strlen($u) == 15)
			return true;
		# 效验码验证
		$U = [str_split($u),
		[7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2],
		[1,0,'x',9,8,7,6,5,4,3,2]];
		$I = 0;
		for ($i=0; $i < count($U[0])-1; $i++)
			$I += intval($U[0][$i]) * intval($U[1][$i]);
		if($U[0][count($U[0])-1] != $U[2][$I % 11])
			return false;
		return true;
	}
	private static function isIp(string $u) : bool
	{ # IP段效验
		return self::One([[
			'/^(?:(?:2[0-4][0-9]\.)|(?:25[0-5]\.)|(?:1[0-9][0-9]\.)|(?:[1-9][0-9]\.)|(?:[0-9]\.)){3}(?:(?:2[0-5][0-5])|(?:25[0-5])|(?:1[0-9][0-9])|(?:[1-9][0-9])|(?:[0-9]))$/'][0],
			$u]);
	}
	private static function isPort(string $u) : bool
	{ # 端口号效验
		return self::One([[
			'/^[1-9]$|(^[1-9][0-9]$)|(^[1-9][0-9][0-9]$)|(^[1-9][0-9][0-9][0-9]$)|(^[1-6][0-5][0-5][0-3][0-5]$)/'][0],
			$u]);
	}
	private static function utf8(string $u) : bool
	{ # 中文范围
		return self::One([[
			'/^[\x{4e00}-\x{9fa5}]{0,}$/u',
			][0],$u]);
		return preg_match("/+/u", $u);
	}
}