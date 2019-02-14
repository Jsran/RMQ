<?php
namespace jR;
use jR\I;
use PDO;
use Exception;
class M
{
  # js@jsran.cn
  # by 2018-07-20 司丙然
  # PHP 7.0+

  public $table;
  public $redis;
  protected $link = [], $conn, $sql,$exec = ['duplicate','join','where','group','having','order','limit'];

  public function __construct($table = null)
  { # 构造函数
    if($table)$this->table = $table;
    self::dbInstance();
  }
  public function __call($name,$args)
  { # 数据缓存
    if(!I\RegExp::One(['/(\w+)(Cache|Clear|Flush)(\d+)/',$name],$u))
      if(!I\RegExp::One(['/(\w+)(Cache|Clear|Flush)/',$name],$u)) return;
    array_shift($u);
    list($method,$action,$expire) = array_pad($u,3,1440);
    $key = strtolower(sprintf('%s:%s:', str_replace('\\', '_', static::class), $method));
    if(is_null($this->redis)){
      $this->redis = new \Redis;
      $this->redis->connect($GLOBALS['redis']['host'],$GLOBALS['redis']['port']);
      if(!empty($GLOBALS['redis']['auth'])) $this->redis->auth($GLOBALS['redis']['auth']);
    }
    switch ($action) {
      case 'Cache':
        $key = $key . md5(json_encode($args));
        $data = $this->redis->get($key);
        if ($data !== false) return json_decode($data, JSON_UNESCAPED_UNICODE) ?? $data;
        if (method_exists($this, $method) === false) err ("Err: Method ".static::class." of $method is not exists!");
        #$data = static::$method($args);
        $data = call_user_func_array("static::$method",$args);
        $this->redis->setex($key,$expire * 60, json_encode($data));
        return $data;
      break;
      case 'Clear':
        return $this->redis->del($key . md5(json_encode($args)));
      break;
      case 'Flush':
        return $this->redis->del($this->redis->keys($key . '*'));
      break;
    }
  }
  public function action(callable $func)
  { # 事务执行
    if (!is_callable($func)) return false;
    $this->begin();
    try {
      ($result = $func($this)) ? $this->commit() : $this->rollBack();
    }catch (Exception $e) {
      $this->link['master']->rollBack();
      throw new Exception($e->getMessage());
    }
    return $result;
  }
  public function oneSql($sql,$param = [],$type = PDO::FETCH_ASSOC)
  { # 单条查询
    # 执行SQL
    return self::execute($sql,$param,true)->fetch($type);
  }
  public function allSql($sql,$param = [],$type = PDO::FETCH_ASSOC)
  { # 多条查询
    # 执行SQL
    return self::execute($sql,$param,true)->fetchAll($type);
  }
  public function runSql($sql,$param = [])
  { # 执行操作
    # 执行SQL
    return self::execute($sql,$param)->rowCount();
  }
  public function query($sql,$param = [])
  { # 
    return self::execute($sql,$param,true);
  }
  public function begin()
  { # 开始事务
    $this->link['master']->beginTransaction();
  }
  public function commit()
  { # 提交事务
    $this->link['master']->commit();
  }
  public function rollBack()
  { # 回滚事务
    $this->link['master']->rollBack();
  }
  public function table($table) : self
  { # 选择表
    $this->table = $table;
    return $this;
  }
  public function update(array $data) : self
  { # 更新数据
    self::__init(__function__);
    $this->sql = "update {$this->table}" . self::wo($data,'set',', ');
    return $this;
  }
  public function insert(array $data) : self
  { # 新增数据
    self::__init(__function__);
    $this->sql = "{$this->run['first']} into {$this->table} ";
    array_walk($data, function($v,$k) use(&$mark){$mark['k'][] = "`{$k}`";$mark['v'][":i_{$k}"] = $v; $mark['i'][] = ":i_{$k}";});
    $this->run['bind'] += $mark['v'];
    $this->sql .= "(". implode(', ', $mark['k'] ) .") values (" . implode(', ', $mark['i'] ) .")";
    return $this;
  }
  public function delete() : self
  { # 删除数据
    self::__init(__function__);
    $this->sql = "{$this->run['first']} from {$this->table}";
    return $this;
  }
  public function select($field = '*',$one = false) : self
  { # 查询数据
    self::__init(__function__);
    $this->run['sone'] = $one;$this->run['field'] = $field;
    $this->sql = "{$this->run['first']} #field# from {$this->table}";
    return $this;
  }
  public function duplicate($data) : self
  { # 重复更新
    $this->run['duplicate'] = ' on duplicate key update' . self::wo($data,'',', ');
    return $this;
  }
  private function join($table,$type = 'inner',$on = []) : self
  { # 数据联合
    $this->run['fieldJoin'][] = "{$table}";
    $this->run['join'] =  (isset($this->run['join']) ? "{$this->run['join']} {$type} join {$table}" : " {$type} join {$table}") . 
      self::wo($on,'on');
    return $this;
  }
  public function leftjoin($table,$on = []) : self
  {
    return self::join($table,'left',$on);
  }
  public function innerjoin($table,$on = []) : self
  {
    return self::join($table,'inner',$on);
  }
  public function rightjoin($table,$on = []) : self
  {
    return self::join($table,'right',$on);
  }
  public function where(array $where) : self
  { # 条件设置
    if(!empty($where)) $this->run['where'] = self::wo($where);
    return $this;
  }
  public function forup(string $_ = null)
  { # 表锁锁 必须是在事务中使用
    $this->run['forup'] = " for update {$_}";
  }
  public function group(string $group) : self
  { # 字段分组
    $this->run['group'] = " group by {$group}";
    return $this;
  }
  public function having(array $having) : self
  { # 聚合过滤
    $this->run['having'] = self::wo($having,'having');
    return $this;
  }
  public function order(string $order = 'id desc') : self
  { # 字段排序
    $this->run['order'] = " order by {$order}";
    return $this;
  }
  public function limit(string $limit) : self
  { # 固定条数
    $this->run['limit'] = " limit {$limit}";
    return $this;
  }
  public function lastInsertId()
  {
    return $this->link['master']->lastInsertId();
  }
  public function run(bool $show = false)
  { # 执行操作
    array_walk($this->exec, function($v,$k){$this->sql .= $this->run[$v] ?? null;});
    if($this->run['first'] == 'select' && !empty($this->run['field'])) $this->sql = str_replace('#field#', self::getField($this->run['field']), $this->sql); 
    if($show) return $this->sql;
    $sth = self::execute($this->sql,$this->run['bind'],$this->run['first'] == 'select'?true:false);
    $run = ['insert' => isset($this->run['duplicate']) && $this->run['duplicate'] ? 'rowCount' : 'lastInsertId','select' => isset($this->run['sone']) && $this->run['sone'] ? 'fetch' : 'fetchAll'][$this->run['first']] ?? 'rowCount';
    return strpos($run,'tch') ? $sth->$run(PDO::FETCH_NAMED) : ( strpos($run,'ser') ? $this->link['master']->$run() : $sth->$run());
  }
  public function union($array_sql_object)
  {
  	

  }
  public function pager($page, $total, $pageSize = 10, $scope = 5)
  { # 获取分页
    $pager = null;
    if($total >= 1){
      $total_page = ceil($total / $pageSize);
      $page = min(intval(max($page, 1)), $total_page);
      
      $scope = (int)$scope;
      $min = max($page + 2,5);
      $max = min($min,$total_page);
      $act = max($max - 4,1);
      $pager = [
        'total_count' => $total, 
        'page_size'   => $pageSize,
        'total_page'  => $total_page,
        'first_page'  => 1,
        'prev_page'   => ( ( 1 == $page ) ? 1 : ($page - 1) ),
        'next_page'   => ( ( $page == $total_page ) ? $total_page : ($page + 1)),
        'last_page'   => $total_page,
        'current_page'=> $page,
        'all_pages'   => range($act, $max),
        'limit'       => ($page - 1) * $pageSize . ',' .$pageSize,
      ];
    }
    return $pager;
  }
  private function __init($first = null)
  { # 净化变量
    $this->run = null;
    $this->run['bind'] = [];
    $this->run['first'] = is_null($first) ?: $first; 
  }
  private function wo($where,$wo = 'where',$ao = ' and '): string
  { # 设计条件
    if(!is_array($where) || empty($where)) return null;
    $mark = ['join' => [],'where' => []];
    array_walk($where, function($v,$k) use(&$mark,$wo){
      if(gettype($k) == 'integer' ):
        $mark['join'][] = $v;
      elseif(!I\RegExp::One(['/^:.*+/',$k])):
        $kk =  str_replace('.','' ,$k);
        $mark['join'][] = "{$k} = :{$wo}_{$kk}";
        $mark['where'][":{$wo}_{$kk}"] = $v;
      else:
        $mark['where'][$k] = $v;
      endif;
    });
    $this->run['bind'] += $mark['where'];
    return " {$wo} ".join($ao,$mark['join']);
  }
  private function getField($field = '*')
  { # 获取字段
    if(in_array($field,['*','!*'])) return $field;
    if(!I\RegExp::One(['/^!.*+/',$field])) return $field;
    $fields = explode(',',substr(trim($field),1));
    if(strpos(trim($this->table),' ')) $this->run['fieldJoin'][] = $this->table;
    if(isset($this->run['fieldJoin']) && is_array($this->run['fieldJoin'])):
      $sql = "select concat(case table_name ";
      array_walk($this->run['fieldJoin'], function($v,$k) use(&$sql,&$tables){
        $join = explode(' ',$v);$tables[] = "'{$join[0]}'";$joincount = count($join);
        $sql .=  "when '{$join[0]}' then '" .  ($joincount>= 2 ? $join[$joincount-1] : $join[0] ) . ".' ";
      });
      $table = join(',', $tables);
      $sql .= "end,column_name) from information_schema.columns where table_schema='{$GLOBALS['mysql']['DB']}' and table_name in ({$table})";
      # echo $sql;
    else:
      $sql = "desc {$this->table}";
    endif;
    $fielda = self::execute($sql,[],true)->fetchAll(PDO::FETCH_COLUMN);
    $res = $fields ? array_diff($fielda,$fields) : '*';
    array_walk($res, function(&$v,$k){if(strpos($v, '.')) $v = explode('.', $v)[1];});
    if($res != '*' ) $res = array_diff($res,$fields);
    return implode(', ',  $res );
  }
  private function pdoTe(&$v)
  { # 获取类型
    $v = in_array( $t = gettype($v), ['array', 'object']) ? serialize($v) : $v;
    return 
    [ 'integer' => PDO::PARAM_INT, 'boolean' => PDO::PARAM_BOOL, 'NULL' => PDO::PARAM_NULL, 'resource' => PDO::PARAM_LOB ]
    [$v] ?? PDO::PARAM_STR;
  }
  private function execute($sql,$param = [],$flag = false)
  { # 执行
    ACT:
    # 执行SQL
    $sth = self::dbInstance($flag)->prepare($sql);
    # 绑定参数
    if(args($param,[],'a'))
      array_walk($param, function(&$v,$k) use($sth){$sth->bindParam($k,$v,self::pdoTe($v));});
    # 执行语句
    if($sth->execute()) return $sth;
    $err = $sth->errorInfo();
    if($err[2] == 'MySQL server has gone away')
    { # wait_timeout 
      $this->link = []; GOTO ACT;
    }
    throw new Exception('Database SQL: "' . $sql. '", ErrorInfo: '. json_encode($err));
  }
  private function dbInstance( $sm = false )
  { # 链接主从
    list($conf,$k) = $sm && !empty($GLOBALS['mysql']['SLAVE'])?
      [ $GLOBALS['mysql']['SLAVE'][($k = array_rand($GLOBALS['mysql']['SLAVE']))],'slave_'.$k]:
      [ $GLOBALS['mysql'], 'master'];
    if(empty($this->link[$k])){
      try {
        $this->link[$k] = new PDO(
          'mysql:dbname='.$conf['DB'].';host='.$conf['HOST'].';port='.$conf['PORT'],
          $conf['USER'],
          $conf['PASS'],
          [PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES \''.$conf['CHARSET'].'\'']);
      }catch(PDOException $e){throw new Exception('Database Err: '.$e->getMessage());}
    }
    return $this->link[$k];
  }
}