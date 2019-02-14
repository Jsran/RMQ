<?php
namespace jR;
use jR\I;
class jR
{
  # js@jsran.cn
  # by 2018-07-20 司丙然
  # PHP 7.0+
  
  private $class = [];
  private $modsi = ['home','demo','index'];
  public function __construct()
  { # 构造函数
    $this->act = microtime(true);
    #echo "<pre>\r\n";
    #echo "Code start\r\n";
  }
  public function __destruct()
  { # 析构函数
    if(!defined('ERR')):
    #dump('Runing Times:' . round(microtime(true)-$this->act,8) . ' Sec / Memory: ' . round(memory_get_usage()/1024,8) . ' Kb');
    #dump('Code end');
    endif;
  }
  private function roule()
  { # 处理路由
    if(!CLI && WEB) GOTO WEB;
    $this->setDefine([ 'MOBILE' => 0, 'HOST' => 'php' ]);
    $param = $_SERVER['argv'];
    array_shift($param);
    $args = [];
    if(!empty($param))
    { # 有参数
      array_walk($param, function($k)use(&$args){
        $v = explode('=',$k);
        $args[$v[0]] = $v[1];
      });
    }
    GOTO ROULE;
    WEB:
    $this->setDefine(['SCHEME' => (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == "https") || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? 'https://' : 'http://']);
    $this->setDefine(['HOST' => SCHEME.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/\\').'/', 'MOBILE' => isMobile()]);
    if(empty($GLOBALS['rewrite'])) GOTO INPUT;
    foreach($GLOBALS['rewrite'] as $rule => $mapper):
      if('/' == $rule)$rule = '/$';
      if(0!==stripos($rule, SCHEME))
        $rule = SCHEME.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/\\') .'/'.$rule;
      $rule = '/'.str_ireplace(['\\\\', SCHEME, '/', '<', '>',  '.'], 
        ['', '', '\/', '(?P<', '>[-\w]+)', '\.'], $rule).'/i';
      if(preg_match($rule, SCHEME.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], $matchs)):
        $route = explode("/", $mapper);
        list($_GET['m'], $_GET['s'], $_GET['i']) = isset($route[2]) ? $route : array_merge([$this->modsi[0]],$route);
        array_walk($matchs, function($v,$k){if(!is_int($k))$_GET[$k] = $v;});
        break;
      endif;
    endforeach;
    INPUT:
    parse_str($_SERVER['REDIRECT_QUERY_STRING'] ?? $_SERVER['QUERY_STRING'] ?? null, $args);
    parse_str(file_get_contents('php://input'), $input);
    $args = array_merge($_POST,$input,$args,$_GET);
    ROULE:
    $this->setDefine([
      'M'=>strtolower($args['m']??$this->modsi[0]),
      'S'=>strtolower($args['s']??$this->modsi[1]),
      'I'=>strtolower($args['i']??$this->modsi[2]),
    ]);
    array_map(function($v)use($args){call_user_func("self::{$v}",$args);},['urllog','webscan','defaulepic','location']);
    self::runing(M,S,I,$args);
  }
  private function runing($module,$controller,$action,$args)
  { # 模块 控制器 动作 检测过滤并执行
    $__ = __NAMESPACE__."\C\\$module\\$controller";
    if(!is_available_classname($module)) _err_router("Err: Module '$module' is not correct!");
    if(!is_dir(PATH.DS.CORE.DS.'C'.DS.$module)) _err_router("Err: Modoule '$module' is not exists!");
    if(!is_available_classname($controller)) _err_router("Err: Controller '$controller' is not correct!");
    if(!class_exists($__,true)) _err_router("Err: Controller '$controller' of '$module'  is not exists!");
    $display_file = $module.'/'.args($GLOBALS['site']['theme'][$module],'Default','s').
      (in_array($module,$GLOBALS['model']['Mobile'])?('/'.(MOBILE?'Mobile':'Computer')):null);
    if(!is_callable([($ob = new $__($display_file)), $action])) _err_router("Err: Method '$action' of '$module' -> '$controller' is not exists!");
    setContentReplace(['__THEMES__' => HOST . ( CLI ?  NULL :"style/".styleget($display_file)), '__HOST__' => HOST ]);
    $ob::$args = I\Filter::val($args);
    $ob->$action();
    if($ob->_auto_display):
      $tpl_name = $controller.$GLOBALS['view']['sep'].$action.$GLOBALS['view']['suffix'];
      $auto_tpl_name = $display_file.DS.$tpl_name;
      if(file_exists($GLOBALS['view']['theme'].DS.$auto_tpl_name))$ob->display($tpl_name);
    endif;
  }
  public function setDefine($arr = [])
  { # 常量定义
    array_walk($arr, function($v,$k){defined($k) or define($k,$v);});
  }
  public function autoload($class)
  { # 自动加载
    if(isset($this->class[$class])) return;
    $class = self::autocall(preg_replace(sprintf("/^%s\\\(C|M|V)$/",PACK),PACK.'\S\\\$1',$class,1));
    $new = preg_replace(sprintf("/^%s/",PACK),CORE,$class,1);
    $new = explode('\\', $new);
    $my = array_pop($new);
    $file = PATH.DS.implode(DS, $new).DS.$my.'.php';
    if(is_file($file) && include $file) return $this->class[$class] = true;
    $PATH = glob(PATH.DS.implode(DS, $new).DS.'*.php');
    
    LOOP:
    if(!(list($k,$v) = each($PATH))) return ;
    if(strtolower($file) === strtolower($v) && include $v) return $this->class[$class] = true;
    GOTO LOOP;
  }
  public function autocall($class)
  { # 自定义路径替换
    return preg_replace('/\\\RoleTrait$/','\\'.($_SESSION[SE_NAME]['Uinfo']['role'] ?? 'default'),$class,1);
  }
  public function Run()
  { # 运行
    # 运行模式
    $this->setDefine(['CLI' => PHP_SAPI === 'cli','WEB' => strpos(PHP_SAPI,'apache') !== false || strpos(PHP_SAPI,'cgi') !== false]);
    # 配置文件
    is_file(PATH. DS . CORE. DS . "config.php") or file_put_contents(PATH. DS . CORE. DS . "config.php","<?php\r\n return ['debug'=>true,'site'=>['theme'=>['home'=>'Default']],'urllog'=>['switch'=>true,'file'=>PATH.DS.'O'.DS.'url_log'],'rewrite'=>['RMQRestful.do'=>'mqsystem/restful/home','RMQS/<s>/<i>.do'=>'mqsystem/<s>/<i>','home.do'=>'demo/index','<m>/<s>/<i>.do'=>'<m>/<s>/<i>','<s>/<i>.do'=>'<s>/<i>',],'model'=>['Mobile'=>['home'],'bindParam'=>['home/demo/index'=>'home/demo/hindex']],'view'=>['image'=>'Entry/not.jpg','cache'=>PATH.DS.'O'.DS.'cache','theme'=>PATH.DS.'V','left'=>'{','right'=>'}','sep'=>'/','suffix'=>'.html','contentReplace'=>[],'style'=>['home/Default/Computer'=>'HOMEPC','home/Default/Mobile'=>'HOMEH5','mqsystem/Default'  => 'RMQS',]],'redis'=>['host'=>'127.0.0.1','port'=>6379,'auth'=>''],'mysql'=>['HOST'=>'localhost','PORT'=>'3306','USER'=>'root','DB'=>'jsran','PASS'=>'root','CHARSET'=>'utf8','SLAVE'=>[['HOST'=>'localhost','PORT'=>'3306','USER'=>'root','DB'=>'jsran','PASS'=>'root','CHARSET'=>'utf8']]]];");
    $GLOBALS += require PATH. DS . CORE . DS . 'config.php';
    # 公共方法
    include PATH. DS . CORE. DS. 'function.php';
    # 自动加载
    spl_autoload_register([$this,'autoload']);
    # 异常捕获
    WEB ? set_error_handler("_err_handle") ||  set_exception_handler('_exc_handler') || register_shutdown_function("_fal_handler"): NULL;
    # 永不超时
    set_time_limit(0);
    ini_set("magic_quotes_runtime",0);
    if($GLOBALS['debug']):
      error_reporting(E_ALL);
      ini_set("display_errors", "On");
    else:
      error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
      ini_set("display_errors", "Off");
      ini_set("log_errors", "On");
      ini_set("error_log", DATA.DS."error_log".DS."phplog_".date('Ymd').".log");
    endif;
    self::SetSE();
    self::roule();
  }
  private function SetSE()
  { # 会话设置
    # DATA.DS.'jRSe' files
    $this->setDefine(['SE_NAME' => 'jRId', 'SE_TIME' => 108000,'SE_HAND' => 'files', 'SE_PATH' => DATA.DS.'jRSe']);
    ini_set('session.gc_maxlifetime',SE_TIME);
    ini_set('session.save_handler',  SE_HAND);
    ini_set('session.save_path',     SE_PATH);
    ini_set('session.name',       'jRPHPSID');
    # ini_set('session.use_cookies',    1);
    # ini_set('session.cookie_path',   '/');
    # ini_set('session.cookie_domain', '.baicaif.com');
    @session_start();
    if(isset($_SESSION[SE_NAME]['expire']) && $_SERVER['REQUEST_TIME'] - $_SESSION[SE_NAME]['expire'] > SE_TIME):
      unset($_SESSION);session_unset();session_destroy();
    else:
      $_SESSION[SE_NAME]['expire'] = $_SERVER['REQUEST_TIME'];
    endif;
  }
  private function urllog($args)
  { # 请求记录
    if(CLI) return;
    if($GLOBALS['urllog']['switch']):!is_dir($GLOBALS['urllog']['file'].date('/Ymd/H')) && mkdir($GLOBALS['urllog']['file'].date('/Ymd/H'),0755,true);file_put_contents($GLOBALS['urllog']['file'].date('/Ymd/H/i').'.log', "From:".get_client_ip()."\r\n".args($_SERVER['HTTP_X_POWERED_TOKEN_BY'])."\r\n".args($_SERVER['HTTP_X_CONTENT_TYPE'])."\r\n".'['.date('H:i:s').'] '.args($_SERVER['REQUEST_URI'],'m='.M.'s='.S.'i='.I,'s').' | '.var_export($args,true).PHP_EOL,FILE_APPEND);endif;
  }
  private function webscan($args)
  { # 安全注入过滤
    $web = [
      'switch'=> true, # 拦截开关
      'white'  => ['system'],
      'scan'=>[
        ['Method'=>'GET','Kvalue'=>$_GET,'switch'=>true,'filter'=>"\\<.+javascript:window\\[.{1}\\\\x|<.*=(&#\\d+?;?)+?>|<.*(data|src)=data:text\\/html.*>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\(|benchmark\s*?\(.*\)|sleep\s*?\(.*\)|load_file\s*?\\()|<[a-z]+?\\b[^>]*?\\bon([a-z]{4,})\s*?=|^\\+\\/v(8|9)|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.*\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)|UPDATE\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE)@{0,2}(\\(.+\\)|\\s+?.+?\\s+?|(`|'|\").*?(`|'|\"))FROM(\\(.+\\)|\\s+?.+?|(`|'|\").*?(`|'|\"))|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)",],['Method'=>'POST','Kvalue'=>$_POST,'switch'=>true,'filter'=>"<.*=(&#\\d+?;?)+?>|<.*data=data:text\\/html.*>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\(|benchmark\s*?\(.*\)|sleep\s*?\(.*\)|load_file\s*?\\()|<[^>]*?\\b(onerror|onmousemove|onload|onclick|onmouseover)\\b|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.*\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)|UPDATE\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE)(\\(.+\\)|\\s+?.+?\\s+?|(`|'|\").*?(`|'|\"))FROM(\\(.+\\)|\\s+?.+?|(`|'|\").*?(`|'|\"))|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)",],['Method'=>'COOKIE','Kvalue'=>$_COOKIE,'switch'=>true,'filter'=>"benchmark\s*?\(.*\)|sleep\s*?\(.*\)|load_file\s*?\\(|\\b(and|or)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.*\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)|UPDATE\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE)@{0,2}(\\(.+\\)|\\s+?.+?\\s+?|(`|'|\").*?(`|'|\"))FROM(\\(.+\\)|\\s+?.+?|(`|'|\").*?(`|'|\"))|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)"
        ],
      ],
    ];
    function arr_foreach($arr){static $str;static $keystr;if(!is_array($arr)){return $arr;}foreach($arr as $key=>$val){$keystr=$keystr.$key;if(is_array($val)){arr_foreach($val);}else{$str[]=$val.$keystr;}}return implode($str);}
    if($web['switch'] && !in_array(M,$web['white'])){foreach($web['scan'] as $k=>$v){if($v['switch']){foreach($v['Kvalue'] as $kk=>$vv){$svv=arr_foreach($vv);if(I\RegExp::One(["/".$v['filter']."/is",$kk])==1 || I\RegExp::One(["/".$v['filter']."/is",$svv])==1){self::runing('home','base','nosql',$args);exit;}}}}}
  }
  private function defaulepic($args)
  { # 找不到资源时的默认图片
    if(empty($args) && I\RegExp::One(["/.*\.(jpg|gif|png|bmp|jpeg)$/i",args($_SERVER['REQUEST_URI'],'m='.M.'s='.S.'i='.I,'s')])):header("Content-type: image/jpeg");exit(file_get_contents(PATH.DS.$GLOBALS['view']['image']));endif;
  }
  private function location($args)
  { # PC MOBILE 双重定向
    $MSI = M.'/'.S.'/'.I;
    if(WEB && in_array(M,$GLOBALS['model']['Mobile'])):
      if(MOBILE && array_key_exists($MSI, $GLOBALS['model']['bindParam'])):
        list($m,$s,$i) = explode('/', $GLOBALS['model']['bindParam'][$MSI]);
      elseif(!MOBILE && array_key_exists($MSI,($flip = array_flip($GLOBALS['model']['bindParam'])))):
        list($m,$s,$i) = explode('/', $flip[$MSI]);
      else: return false;
      endif;
      self::runing($m,$s,$i,$args);exit;
    endif;
  }
}