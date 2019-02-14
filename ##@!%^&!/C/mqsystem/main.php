<?php
namespace jR\C\mqsystem;
use jR\M;
use jR\I;
class main extends Base
{
  public function home()
  {
    $ob = new M();
    if(!$ob->OneSql("show tables like 'rmq_topic'"))
    { # does the jr_topic table exist
      $ob->runSql(
        "create table `rmq_admin` (
           `id` int(11) not null auto_increment,
           `user` varchar(16) not null,
           `pass` varchar(32) not null,
           primary key (`id`)
          ) engine=innodb auto_increment=10000 default charset=utf8;
        create table `rmq_changelog` (
         `msgid` varchar(32) not null,
         `state` tinyint(1) not null,
         `create_time` timestamp not null default current_timestamp
        ) engine=innodb default charset=utf8;
        create table `rmq_redis` (
         `id` smallint(5) unsigned not null auto_increment,
         `name` varchar(36) not null default '' comment '名称',
         `info` varchar(1024) not null default '',
         `push` int(11) unsigned default '0' comment '压入数量 唯一',
         `repush` int(11) unsigned default '0' comment '压入数量 重复',
         `pop` int(11) unsigned default '0' comment '弹出数量 唯一',
         `repop` int(11) unsigned default '0' comment '弹出数量 重复',
         `state` tinyint(1) not null default '1' comment '1 可用 0 不可用',
         primary key (`id`)
        ) engine=innodb auto_increment=1201 default charset=utf8;
        create table `rmq_runlog` (
         `msgid` varchar(32) not null comment '消息id',
         `tid` smallint(5) unsigned not null comment 'topic id',
         `orderid` varchar(32) not null default '' comment '商户流水号 唯一',
         `rid` smallint(5) unsigned not null comment 'redis id',
         `timerid` tinyint(3) not null default '0' comment 'timer进程分配',
         `put` int(11) not null default '1' comment '写入次数',
         `notify` tinyint(2) not null default '0' comment '消费次数',
         `body` text not null comment '业务内容',
         `status` tinyint(1) not null default '0' comment '0 待消费 1 消费成功 2 消费异常 8 已删除',
         `create_time` timestamp not null default current_timestamp,
         `update_time` timestamp not null default current_timestamp on update current_timestamp,
         `notify_time` timestamp not null comment '通知时间',
         unique key `keys` (`tid`,`orderid`) using btree,
         key `index` (`tid`),
         key `11` (`msgid`)
        ) engine=innodb default charset=utf8;
        create table `rmq_state` (
         `tid` int(11) not null,
         `rid` int(11) not null,
         `state` tinyint(1) not null default '0',
         `state_p` tinyint(1) not null default '0',
         `sums` int(11) unsigned not null,
         unique key `21` (`tid`,`rid`,`state`) using btree,
         key `tid` (`tid`),
         key `rid` (`rid`)
        ) engine=innodb default charset=utf8;
        create table `rmq_topic` (
         `id` smallint(5) unsigned not null auto_increment,
         `name` varchar(24) not null default '' comment '业务名称',
         `topic` varchar(48) not null default '' comment '业务主题',
         `delay` mediumint(8) unsigned not null default '300' comment '延迟时间',
         `callback` varchar(96) not null default '' comment '回调地址',
         `method` varchar(8) not null default 'notify' comment '请求方式',
         `timeout` smallint(5) not null default '5000' comment '响应超时时间',
         `backtype` varchar(6) default 'string' comment '回调类型string|json',
         `succflag` varchar(256) not null default 'success' comment '成功标识',
         `priority` tinyint(1) not null default '2' comment '消费优先级',
         `status` tinyint(1) not null default '1' comment '启用状态',
         `createor` varchar(8) not null default '' comment '创建人',
         `email` varchar(36) not null,
         primary key (`id`)
        ) engine=innodb auto_increment=12001 default charset=utf8;
        create trigger `index_redis_push` AFTER INSERT ON `rmq_runlog`
         FOR EACH ROW begin
        update rmq_redis set push=push+1 where id = new.rid;
        insert into rmq_state(rid,tid,state,sums) values (new.rid,new.tid,0,1) on duplicate key update sums=sums+1;
        end;
        create trigger `update_redis_push_pop` AFTER UPDATE ON `rmq_runlog`
         FOR EACH ROW begin
        if old.status = 0 and new.status != 8 then
            if old.notify = 0 then
            update rmq_redis set pop=pop+1 where id = old.rid;
            else
            update rmq_redis set repush=repush+1,repop=repop+1 where id = old.rid;
            end if;
        end if;
        if new.status >= 1 then
          insert into rmq_state(rid,tid,state,state_p,sums) values  (new.rid,new.tid,new.status,old.status,1) on duplicate key update sums=sums+1;
        end if;
        insert into rmq_changelog(MsgId,state) values  (old.MsgId,new.status);
        end;
        "
      );
    }
  }

  public function welcome()
  {
    parent::opcache();
    $obj = new M\MQRedis;
    parent::Assigns([
      'user' => $_SESSION[SE_NAME]['Uinfo']['user'],
       # 操作系统
      'os'  => PHP_OS,
      # 端地址
      'ip'  => $_SERVER["SERVER_ADDR"],
      # 端标识
      'web' => $_SERVER["SERVER_SOFTWARE"],
      # PHP版本
      'pver'  => PHP_VERSION,
      # CURL
      'curl'  => function_exists("curl_init"),
      'pdo'  => class_exists('pdo'),
      'redis'  => class_exists('Redis'),
      'rc' => $obj->getRedisAlldataCache10(),
      'tdata' => $obj->query('select b.topic,sum(if(state = 0,sums,0) - if(state>0 and state_p =0,sums,0)) n_0,sum(if(state = 1,sums,0)) n_1,sum(if(state = 2,sums,0)) n_2,sum(if(state = 8,sums,0)) n_8,sum(if(state = 0 and state_p=2,sums,0)) n_2_0,sum(if(state = 0 and state_p=8,sums,0)) n_8_0 from rmq_state a left join rmq_topic b on a.tid = b.id group by a.tid')->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_NUM ),
      ]);
  }
  public function reload()
  {
    if($_SERVER['REQUEST_METHOD'] == 'PUT'){
      $obj = new M\MQRedis;
      $obj->getRedisAlldataClear();
      parent::json('reload sussess');
    }
  }

}