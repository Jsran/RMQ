<?php
namespace jR\M;
use jR\M;
class User extends M
{
	public $table = "s_user";

	public function login($user,$pass)
	{ # 登录验证
		# 应用数据库不存用户密码
		# API 请求登录验证用户密码
		# SESSION使用Redis存储 - 保存用户信息至SESSION 实现多端共享用户信息
		return ;
	}

	public function one()
	{ # 个人信息
		return ;
	}
}