<?php
namespace jR\I;
class RSA
{
	const PRI_KEY_FILE = PATH.DS.'O'.DS.'RSACERT'.DS.'private.pem';
	const PUB_KEY_FILE = PATH.DS.'O'.DS.'RSACERT'.DS.'public.pem';

	private static function _pubkey()
	{ # 
		return openssl_get_publickey(file_get_contents(self::PUB_KEY_FILE));
	}
	private static function _prikey()
	{ # 
		return openssl_get_privatekey(file_get_contents(self::PRI_KEY_FILE));
	}
	public static function generate()
	{ # 生产证书
		$config = [
			"config" => "C:\Users\js\Documents\work\UPUPW_AP7.0\Apache2\conf\openssl.cnf",
            "digest_alg" => "sha512",
            "private_key_bits" => 1024,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        $res = openssl_pkey_new($config);
        if(openssl_pkey_export($res, $prikey,null,$config)){
        	file_put_contents(self::PRI_KEY_FILE, prikey, LOCK_EX);
        	file_put_contents(self::PUB_KEY_FILE, openssl_pkey_get_details($res)["key"], LOCK_EX);
        	return true;
        }
        return false;
	}
	public static function encrypt($data)
	{ # 加密
		$pubkey = self::_pubkey();
		$ret = openssl_public_encrypt($data, $res, $pubkey) ? base64_encode($res) : false;
		openssl_free_key($pubkey);
		return $ret;
	}

	public static function decrypt($data)
	{ # 解密
		base64_decode($data);
		$prikey = self::_prikey();
		$data = base64_decode($data);
		$ret = $data ? ( openssl_private_decrypt($data, $res, $prikey) ? ('' . $res) : false ): flase;
		openssl_free_key($prikey);
		return $ret;
	}

	public static function RsaSV($arr,$sign = null)
	{ # Sign or Verify
		ksort($arr);
		$str = join('|',
			array_values(
				array_filter($arr,
				function ($k){
					return gettype($k) == 'string' ?  $k !== '' : gettype($k) == 'integer' ? true : false;
				})
			)
		);
		return is_null($sign)?self::sign($str):self::verify($str,$sign);
	}

	private static function sign($data)
	{ # 签名
		$prikey = self::_prikey();
		$res = openssl_sign($data, $sign, $prikey) ? base64_encode($sign) : false;
		openssl_free_key($prikey);
		return $res;
	}
	private static function verify($data, $sign)
	{ # 验签
		$pubkey = self::_pubkey();
		$sign = base64_decode($sign);
		$res = $sign ? (bool) openssl_verify($data, $sign, $pubkey) : false;
		openssl_free_key($pubkey);
		return $res;
	}
}