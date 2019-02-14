<?php
namespace jR;
use jR\V;
class C{
	public $layout;
	public $_auto_display = true;
	public $_display_file;
	public static $args;
	protected $_v;
	private $_data = array();

	public function JsGo(){}
	public function __construct($display_file){if($display_file)$this->_display_file = $display_file;$this->JsGo();}
	public function &__get($name){return $this->_data[$name];}
	public function __set($name, $value){$this->_data[$name] = $value;}

	public function display($tpl_name, $return = false){
		if(!$this->_v) $this->_v = new V($GLOBALS['view']['theme'], $GLOBALS['view']['cache'].DS.$this->_display_file,$GLOBALS['view']['left'],$GLOBALS['view']['right'],$this->_display_file);
		$this->_v->assign(get_object_vars($this));
		$this->_v->assign($this->_data);
		if($this->layout){
			$this->_v->assign('__template_file', $tpl_name);
			$tpl_name = $this->layout;
		}
		$this->_auto_display = false;
		if($return){
			return $this->_v->render($tpl_name);
		}else{
			echo $this->_v->render($tpl_name);
		}
	}

	/**
	 * 是否GET方式请求而来的
	 *
	 * @return boolean
	 */
	public function is_get()
	{
		return $_SERVER['REQUEST_METHOD'] == 'GET';
	}

	/**
	 * 是否POST方式请求而来的
	 *
	 * @return boolean
	 */
	public function is_post()
	{
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	/**
	 * 是否HEAD方式请求而来的
	 *
	 * @return boolean
	 */
	function is_head()
	{
		return $_SERVER['REQUEST_METHOD'] == 'HEAD';
	}

	/**
	 * 是否PUT方式请求而来的
	 *
	 * @return boolean
	 */
	public function is_put()
	{
		return $_SERVER['REQUEST_METHOD'] == 'PUT';
	}

	/**
	 * 是否DELETE方式请求而来的
	 *
	 * @return boolean
	 */
	public function is_delete()
	{
		return $_SERVER['REQUEST_METHOD'] == 'DELETE';
	}

	/**
	 * 是否AJAX方式请求而来的
	 *
	 * @return boolean
	 */
	public function is_ajax()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}

	/**
	 * 用session记录一个令牌token
	 *
	 */
	public function set_token()
	{
		$_SESSION['token'] = md5(microtime(true));
	}

	/**
	 * 验证一个令牌token
	 *
	 * @return boolean
	 */
	public function valid_token()
	{
		$return = $_REQUEST['token'] === $_SESSION['token'] ? true : false;
		self::set_token();
		return $return;
	}

}