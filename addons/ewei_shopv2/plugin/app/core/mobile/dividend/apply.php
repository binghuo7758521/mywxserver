<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(__DIR__ . "/base.php");
class Apply_EweiShopV2Page extends Base_EweiShopV2Page 
{
	public function main() 
	{


		global $_W;
		global $_GPC;
		$openid = $_W["openid"];
		$member = $this->model->getInfo($openid, array( ));

		$time = time();
		$day_times = intval($this->set["settledays"]) * 3600 * 24;
		$dividend_ok = 0;
		// 2018.12.25注释
	/*	if($member['agentheads']==2){
			$orders = pdo_fetchall("select id,dividend from " . tablename("ewei_shop_order") . " where headsid=:headsid and status>=3  and dividend2_status=0 and (" . $time . " - finishtime > " . $day_times . ") and (finishtime >" . $member["headstime"] . ") and uniacid=:uniacid  group by id", array( ":uniacid" => $_W["uniacid"], ":headsid" => $member["id"] ));
			}else{
				$orders = pdo_fetchall("select id,dividend from " . tablename("ewei_shop_order") . " where headsid=:headsid and status>=3  and dividend_status=0 and (" . $time . " - finishtime > " . $day_times . ") and (finishtime >" . $member["headstime"] . ") and uniacid=:uniacid  group by id", array( ":uniacid" => $_W["uniacid"], ":headsid" => $member["id"] ));
				}
				
		/*foreach( $orders as $o )
		{
			$dividend = iunserializer($o["dividend"]);
			if( !empty($dividend) ) 
			{
				$dividend_ok += (isset($dividend["dividend_price"]) ? floatval($dividend["dividend_price"]) : 0);
			}
		}*/
		//print_r($dividend_ok);die;
		// 2018.12.25注释end
		// 二级分红80
		// 遍历订单。这个地方有问题。遍历出订单。然后求出分红总金额。
		/*	if ($member['agentheads'] == 1) {
							// 遍历二级队长
								//获取所有成员的信息 /
								$groupscounts2 = pdo_fetchAll("select id,agentheads,headstime from " . tablename("ewei_shop_member") . " where headsid = :headsid and uniacid = :uniacid", array(":headsid" => $member["id"], ":uniacid" => $_W["uniacid"]));
									


								foreach($groupscounts2 as $result){

								if ($result['agentheads'] == 2) {
									$orders1 = pdo_fetchall("select id,dividend from " . tablename("ewei_shop_order") . " where headsid=:headsid and status>=3 and dividend_status=0 and (" . $time . " - finishtime > " . $day_times . ") and (finishtime >" . $result["headstime"] . ")  and uniacid=:uniacid  ", array( ":uniacid" => $_W["uniacid"], ":headsid" => $result["id"] ));
								}
								}*/
									// 2018.12.25注释
                                  /*<!--  $dividend_total1 = 0;
									if (!empty($orders1)) {
										foreach ($orders1 as $k => $v) {
											$divedend = iunserializer($v["dividend"]);
											// 可提现分红数
											$dividend_total1 += $divedend["dividend_price"];
										}
									}-->*/

									/*$dividend_ok+=$dividend_total1;*/
									
								// 2018.12.25注释end
						/*		}
							}*/
							// 判断二级队长是否的订单

							// 遍历查询
/*<!--						}

if(!empty($orders1)){
    $orders = array_merge($orders,$orders1);

}-->*/

			date_default_timezone_set("Asia/Shanghai");
            $beginThismonth=mktime(0,0,0,date('m'),1,date('Y'));
            $orders = pdo_fetchAll("select * from " . tablename("ewei_shop_dividendok"). " where member_id = :member_id and dividend_status = 0 and dividend_month<$beginThismonth",array(":member_id" => $member["id"]));
			$dividend_ok = pdo_fetchColumn("select sum(dividend_money) from " . tablename("ewei_shop_dividendok"). " where member_id = :member_id and dividend_status = 0 and dividend_month<$beginThismonth",array(":member_id" => $member["id"]));
			
				
				
			
		$withdraw = floatval($this->set["withdraw"]);
		if( $withdraw <= 0 ) 
		{
			$withdraw = 1;
		}
		$cansettle = $withdraw <= $dividend_ok;
		$member["dividend_ok"] = number_format($dividend_ok, 2);
		$set_array = array( );
		$set_array["charge"] = $this->set["withdrawcharge"];
		$set_array["begin"] = floatval($this->set["withdrawbegin"]);
		$set_array["end"] = floatval($this->set["withdrawend"]);
		$realmoney = $dividend_ok;
		$deductionmoney = 0;
		if( !empty($set_array["charge"]) ) 
		{
			$money_array = m("member")->getCalculateMoney($dividend_ok, $set_array);
			if( $money_array["flag"] ) 
			{
				$realmoney = $money_array["realmoney"];
				$deductionmoney = $money_array["deductionmoney"];
			}
		}
		$last_data = $this->model->getLastApply($member["id"]);
		$canusewechat = !strexists($openid, "wap_user_") && !strexists($openid, "sns_qq_") && !strexists($openid, "sns_wx_");
		$type_array = array( );
		if( $this->set["cashcredit"] == 1 ) 
		{
			$type_array[0]["title"] = $this->set["texts"]["withdraw"] . "到" . $_W["shopset"]["trade"]["moneytext"];
		}
		if( $this->set["cashweixin"] == 1 && $canusewechat ) 
		{
			$type_array[1]["title"] = $this->set["texts"]["withdraw"] . "到微信钱包";
		}
		if( $this->set["cashother"] == 1 ) 
		{
			if( $this->set["cashalipay"] == 1 ) 
			{
				$type_array[2]["title"] = $this->set["texts"]["withdraw"] . "到支付宝";
				if( !empty($last_data) && $last_data["type"] != 2 ) 
				{
					$type_last = $this->model->getLastApply($member["id"], 2);
					if( !empty($type_last) ) 
					{
						$last_data["realname"] = $type_last["realname"];
						$last_data["alipay"] = $type_last["alipay"];
					}
				}
			}
			if( $this->set["cashcard"] == 1 ) 
			{
				$type_array[3]["title"] = $this->set["texts"]["withdraw"] . "到银行卡";
				if( !empty($last_data) && $last_data["type"] != 3 ) 
				{
					$type_last = $this->model->getLastApply($member["id"], 3);
					if( !empty($type_last) ) 
					{
						$last_data["realname"] = $type_last["realname"];
						$last_data["bankname"] = $type_last["bankname"];
						$last_data["bankcard"] = $type_last["bankcard"];
					}
				}
				$condition = " and uniacid=:uniacid and status=1";
				$params = array( ":uniacid" => $_W["uniacid"] );
				$banklist = pdo_fetchall("SELECT * FROM " . tablename("ewei_shop_dividend_bank") . " WHERE 1 " . $condition . "  ORDER BY displayorder DESC", $params);
			}
		}
		if( !empty($last_data) && array_key_exists($last_data["type"], $type_array) ) 
		{
			$type_array[$last_data["type"]]["checked"] = 1;
		}
		if( $_W["ispost"] ) 
		{


			unset($_SESSION["dividend_apply_token"]);
			if( $dividend_ok <= 0 || empty($orders) ) 
			{
				app_error(1, "参数错误,请刷新页面后重新提交!!");
			}
			$type = intval($_GPC["type"]);
			if( !array_key_exists($type, $type_array) ) 
			{
				app_error(1, "未选择提现方式，请您选择提现方式后重试!");
			}
			$apply = array( );
			if( $type == 2 ) 
			{
				$realname = trim($_GPC["realname"]);
				$alipay = trim($_GPC["alipay"]);
				$alipay1 = trim($_GPC["alipay1"]);
				if( empty($realname) ) 
				{
					app_error(1, "请填写姓名!");
				}
				if( empty($alipay) ) 
				{
					app_error(1, "请填写支付宝帐号!");
				}
				if( empty($alipay1) ) 
				{
					app_error(1, "请填写确认帐号!");
				}
				if( $alipay != $alipay1 ) 
				{
					app_error(1, "支付宝帐号与确认帐号不一致!");
				}
				$apply["realname"] = $realname;
				$apply["alipay"] = $alipay;
			}
			else 
			{
				if( $type == 3 ) 
				{

					$realname = trim($_GPC["realname"]);
					$bankname = trim($_GPC["bankname"]);
					$bankcard = trim($_GPC["bankcard"]);
					$bankcard1 = trim($_GPC["bankcard1"]);
					if( empty($realname) ) 
					{
						app_error(1, "请填写姓名!");
					}
					if( empty($bankname) ) 
					{
						app_error(1, "请选择银行!");
					}
					if( empty($bankcard) ) 
					{
						app_error(1, "请填写银行卡号!");
					}
					if( empty($bankcard1) ) 
					{
						app_error(1, "请填写确认卡号!");
					}
					if( $bankcard != $bankcard1 ) 
					{
						app_error(1, "银行卡号与确认卡号不一致!");
					}
					$apply["realname"] = $realname;
					$apply["bankname"] = $bankname;
					$apply["bankcard"] = $bankcard;
				}
			}
		
			$orderids = array( );
			
			
			foreach( $orders as $o ) 
			{
			
				$orderids[] = $o["order_ids"];
				if($member['agentheads']==1){
					
					//pdo_update("ewei_shop_order", array( "dividend_status" => 1, "dividend_applytime" => $time ), array( "id" => $o["order_ids"], "uniacid" => $_W["uniacid"] ));
					
					pdo_query('UPDATE '.tablename('ewei_shop_order')." SET dividend_status = 1,dividend_applytime = $time WHERE id in ({$o['order_ids']})");
					pdo_query('UPDATE '.tablename('ewei_shop_dividendok')." SET dividend_status = 1 WHERE member_id = {$member["id"]} and dividend_month < {$beginThismonth}");
					
					}else{
						pdo_query('UPDATE '.tablename('ewei_shop_order')." SET dividend2_status = 1,dividend2_applytime = $time WHERE id in ({$o['order_ids']})");
				//pdo_update("ewei_shop_order", array( "dividend2_status" => 1, "dividend2_applytime" => $time ), array( "id" => $o["order_ids"], "uniacid" => $_W["uniacid"] ));
			pdo_query('UPDATE '.tablename('ewei_shop_dividendok')." SET dividend_status = 1 WHERE member_id = {$member["id"]} and dividend_month < {$beginThismonth}");
					}
			}
			
			$applyno = m("common")->createNO("dividend_apply", "applyno", "DA");
			$apply["uniacid"] = $_W["uniacid"];
			$apply["applyno"] = $applyno;
			$apply["orderids"] = implode(",", $orderids);
			$apply["mid"] = $member["id"];
			$apply["dividend"] = $dividend_ok;
			$apply["type"] = $type;
			$apply["status"] = 1;
			$apply["applytime"] = $time;
			$apply["realmoney"] = $realmoney;
			$apply["deductionmoney"] = $deductionmoney;
			$apply["charge"] = $set_array["charge"];
			$apply["beginmoney"] = $set_array["begin"];
			$apply["endmoney"] = $set_array["end"];
			pdo_insert("ewei_shop_dividend_apply", $apply);
			$apply_type = array( "余额", "微信钱包", "支付宝", "银行卡" );
			$mdividend = $dividend_ok;
			if( !empty($deductionmoney) ) 
			{
				$mdividend .= ",实际到账金额:" . $realmoney . ",提现手续费金额:" . $deductionmoney;
			}
			app_json();
		}
		$token = md5(microtime());
		$_SESSION["dividend_apply_token"] = $token;
		$result = array( "set" => $this->set, "deductionmoney" => $deductionmoney, "realmoney" => $realmoney, "dividend_ok" => $dividend_ok, "withdraw" => $withdraw, "cansettle" => $cansettle, "banklist" => $banklist, "last_data" => $last_data, "type_array" => $type_array, "set_array" => $set_array );
		app_json($result);
	}
}
?>