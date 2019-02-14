<?php
namespace jR\I\Interfaces;
interface bankInterface
{
	# 个人开户
	function RegPer($in);
	# 法人开户
	function RegEnt($in);
	# 绑卡
	function BindCard($in);
	# 解绑
	function UnBindCard($in);
	# 销户
	function userCancel($in);
	# 更改手机
	function mobileChange($in);
	# 重置密码
	function passwordModify($in);
	# 短信通知配置
	function smsNotifyGrant($in);
	# 充值
	function quickRecharge($in);
	# 提现
	function withdraw($in);
	# 项目报备
	function projectAdd($in);
	# 项目更新
	function projectUpdate($in);
	# 用户授权
	function Grant($in);
	# 验密冻结
	function passwordFreeze($in);
	# 授权冻结
	function freeze($in);
	# 验密解冻
	function passwordUnfreeze($in);
	# 授权解冻
	function unfreeze($in);
	# 转账交易
	function transferPay($in);
	# 项目查询
	function projectQuery($in);
	# 债权查询
	function creditQuery($in);
	# 交易查询
	function txnQuery($in);
	# 用户信息
	function userQuery();
	#################################
	#		以下为旧的保留接口		#
	#################################

	# 余额查询
	function queryUserMoney();
	# 网银充值
	function webCharge($in);
}