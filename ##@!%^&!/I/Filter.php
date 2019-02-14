<?php
namespace jR\I;
class Filter
{
	private static $_allowtags = 'p|br|b|span|strong|hr|a|img|object|param|form|input|label|dl|dt|dd|div|font',
	               $_allowattrs = 'color|style|id|class|align|valign|src|border|href|target|width|height|title|alt|name|action|method|value|type',
	               $_disallowattrvals = 'expression|javascript:|behaviour:|vbscript:|mocha:|livescript:',
				   $_mode = 'entity';
	
	function __construct($allowtags = null, $allowattrs = null, $disallowattrvals = null, $mode = null)
	{
		if ($allowtags) self::$_allowtags = $allowtags;
		if ($allowattrs) self::$_allowattrs = $allowattrs;
		if ($disallowattrvals) self::$_disallowattrvals = $disallowattrvals;
		if ($mode) self::$_mode = $mode;
	}
	
	static function input($cleanxss = 1)
	{
        if (get_magic_quotes_gpc())
        {
           $_POST = filter_stripslashes_deep($_POST);
           $_GET = filter_stripslashes_deep($_GET);
           $_COOKIE = filter_stripslashes_deep($_COOKIE);
           $_REQUEST = filter_stripslashes_deep($_REQUEST);
        }
        if ($cleanxss)
        {
        	$_POST = self::xss($_POST);
        	$_GET = self::xss($_GET);
        	$_COOKIE = self::xss($_COOKIE);
        	$_REQUEST = self::xss($_REQUEST);
        }
	}	
	static function val($string, $cleanxss = 1)
	{
		if(get_magic_quotes_gpc()){
			$string = filter_stripslashes_deep($string);
		}
		if( $cleanxss){
			$string = self::xss($string);
		}
		return $string;
	}
	static function xss($string)
	{
		if (is_array($string))
		{
			$string = array_map(array('self', 'xss'), $string);
		}
		else 
		{
			if (strlen($string) > 20)
			{
				$string = self::_strip_tags($string);
			}
		}
		return $string;
	}
	
	static function _strip_tags($string)
	{
		return preg_replace_callback(array("|(<)(/?)(\w+)([^>]*)(>)|"), array('self', '_strip_attrs'), $string);
	}
	
	static function _strip_attrs($matches)
	{
		if (preg_match("/^(".self::$_allowtags.")$/", $matches[3]))
		{
			if ($matches[4])
			{
				preg_match_all("/\s(".self::$_allowattrs.")\s*=\s*(['\"]?)(.*?)\\2/i", $matches[4], $m, PREG_SET_ORDER);
				$matches[4] = '';
				foreach ($m as $k=>$v)
				{
					if (!preg_match("/(".self::$_disallowattrvals.")/", $v[3]))
					{
						$matches[4] .= $v[0];
					}
				}
			}
		}
		else 
		{
			if(self::$_mode == 'entity'){
				$matches[1] = '&lt;';
				$matches[5] = '&gt;';
			}elseif(self::$_mode == 'strip'){
				$matches = array();
			}
		}
		unset($matches[0]);
		return implode('', $matches);
	}
}
function filter_stripslashes_deep($string)
{
	return is_array($string) ? array_map('filter_stripslashes_deep', $string) : stripslashes($string);
}
function filter_addslashes_deep($string)
{
	return is_array($string) ? array_map('filter_addslashes_deep', $string) : addslashes($string);
}