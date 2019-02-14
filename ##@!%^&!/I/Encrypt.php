<?php
namespace jR\I;
class Encrypt
{
	#################################
	#								#
	#	密码加密类 v 1.0 By:JsRan	#
	#								#
	#		E-mail:js@jsran.cn		#
	#################################
	# $a = new Encrypt();
	# $c = $a->set(['aaaa',($r = rand(1,3))])->run(); # 加密
	# echo $c."<br/> \r\n";
	# echo $a->set(['aaaa',$r,$c])->run(); # 验证
	# 1 2 可以通用 3 为MD5加密
	private $opt,$cost = 10; # 取决于机器性能 最小10 默认10
	public function set(array $arr)
	{ # 设置加密密文
		$this->opt = [
			args( $arr [0]),		# 加密文本
			args( $arr [1], 1),		# 加密程序
			args( $arr [2], false),	# 验证时填入密文
		];
		return $this;
	}
	public function run()
	{ # 运行程序
		$func = ( $this->opt [2] ? "e" : 'd' ) . $this->opt [1];
		if(!method_exists($this,$func)) err ("Err: Other ".__CLASS__." of $func is not exists!");
		return self::$func();
	}
	private function d1() : string
	{ # 加密方式1
		$opt = [ 'cost' => $this->cost ];
		return password_hash ( $this->opt [0], PASSWORD_BCRYPT, $opt );
	}
	private function e1() : bool
	{ # 验证加密1
		return password_verify ( $this->opt [0], $this->opt [2] );
	}
	private function d2() : string
	{ # 加密方式2
		if ( CRYPT_BLOWFISH != 1 ) err ("Err: Other ".__CLASS__." of ".__FUNCTION__." is CRYPT_BLOWFISH not open！");
		$slen = $len = ( $cost = $this->cost ) + 13;
		$len ^= $cost ^= $len ^= $cost;
		if (  function_exists ( "mcrypt_create_iv" ) )
		{ # mycrypt 扩展是否开启 mcrypt_list_algorithms — 获取支持的加密算法 mcrypt_list_modes — 获取所支持的模式
			$salt = substr ( str_replace ( '+', '.', base64_encode ( mcrypt_create_iv ( mcrypt_get_iv_size('rijndael-192', MCRYPT_MODE_ECB), MCRYPT_DEV_URANDOM ) ) ), 0, $cost );
		} else {
			$salt = null;
			while ( $slen >= 1) {
				$slen--;
				$salt .= rand(1,10) % 2 == 0 ? chr(rand(65,90)) : (rand(1,10) % 2 == 0 ? chr(rand(97,122)) : chr(rand(46,57)));
			}
		}
		return crypt ( $this->opt [0], "\$2a\${$len}\${$salt}" );
	}
	private function e2() : bool
	{ # 验证加密2
		if ( substr_count( $this->opt [2], '$' ) != 3 ) return false;
		$slen = ( $len = ( $obj = explode ( '$', $this->opt [2] ) ) [2] ) + 13;
		$salt = substr ( $obj [3], 0, $slen );
		return $this->opt [2] == crypt ( $this->opt [0], "\${$obj[1]}\${$len}\${$salt}" ) ? true : false;
	}
	private function d3() : string
	{ # 加密方式3
		$slen = $len = ( $cost = $this->cost ) + 13;
		$len ^= $cost ^= $len ^= $cost;
		if (  function_exists ( "mcrypt_create_iv" ) )
		{ # mycrypt 扩展是否开启 mcrypt_list_algorithms — 获取支持的加密算法 mcrypt_list_modes — 获取所支持的模式
			$salt = substr ( str_replace ( '+', '.', base64_encode ( mcrypt_create_iv ( mcrypt_get_iv_size('rijndael-192', MCRYPT_MODE_ECB), MCRYPT_DEV_URANDOM ) ) ), 0, $cost );
		} else {
			$salt = null;
			while ( $slen >= 1) {
				$slen--;
				$salt .= rand(1,10) % 2 == 0 ? chr(rand(65,90)) : (rand(1,10) % 2 == 0 ? chr(rand(97,122)) : chr(rand(46,57)));
			}
		}
		$keys = substr(str_replace('+', '.',base64_encode(md5(substr($salt, 0, 3) . $this->opt [0] . substr($salt, -3)))), 0, 40 - $this->cost);
		return "\$2b\${$len}\${$salt}{$keys}";
	}
	private function e3() : bool
	{ # 验证加密3
		if ( substr_count( $this->opt [2], '$' ) != 3 ) return false;
		$slen = ( $len = ( $obj = explode ( '$', $this->opt [2] ) ) [2] ) + 13;
		$salt = substr ( $obj [3], 0, $slen );
		$keys = substr(str_replace('+', '.',base64_encode(md5(substr($salt, 0, 3) . $this->opt [0] . substr($salt, -3)))), 0, 40 - $this->cost);
		return $this->opt [2] == "\${$obj[1]}\${$len}\${$salt}{$keys}" ? true : false;
	}
 }