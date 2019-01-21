<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(__DIR__ . "/base.php");
class Down_EweiShopV2Page extends Base_EweiShopV2Page 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$openid = $_W["openid"];
		$member = $this->model->getInfo($openid);
		$groupscount = $member["groupscount"];
		$pindex = max(1, intval($_GPC["page"]));
		$psize = 20;
		//$list = pdo_fetchall("select * from " . tablename("ewei_shop_member") . " where headsid = :headsid and isheads = 0 and uniacid = :uniacid order by id desc limit " . ($pindex - 1) * $psize . "," . $psize, array( ":headsid" => $member["id"], ":uniacid" => $_W["uniacid"] ));
		$list = pdo_fetchall("select * from " . tablename("ewei_shop_member") . " where headsid = :headsid and uniacid = :uniacid order by id desc limit " . ($pindex - 1) * $psize . "," . $psize, array( ":headsid" => $member["id"], ":uniacid" => $_W["uniacid"] ));
		//遍历下级列表
		
		foreach($list as $values){
			
			if($values['agentheads']==2){
				
				$lists = pdo_fetchall("select * from " . tablename("ewei_shop_member") . " where headsid = :headsid and uniacid = :uniacid order by id desc limit " . ($pindex - 1) * $psize . "," . $psize, array( ":headsid" => $values["id"], ":uniacid" => $_W["uniacid"] ));
			
				
				}
			}
		if(!empty($lists)){
							$list=array_merge($list,$lists);
							}
		
		if( !empty($list) ) 
		{
			foreach( $list as &$row ) 
			{
				$money = 0;
				$order = pdo_fetchall("select id,price,dividend from " . tablename("ewei_shop_order") . " where openid=:openid and uniacid=:uniacid limit 1", array( ":uniacid" => $_W["uniacid"], ":openid" => $row["openid"]));
				
				foreach( $order as $k => $v ) 
				{
					$dividend = iunserializer($v["dividend"]);
					$money += $dividend["dividend_price"];
				}
				$row["ordercount"] = count($order);
				$row["moneycount"] = floatval($money);
				$row["createtime"] = date("Y-m-d H:i", $row["createtime"]);
			}
			unset($row);
		}	
		$result = array( "member" => $member, "list" => $list, "groupscount" => $groupscount, "total" => count($list), "pagesize" => $psize );
		app_json($result);
	}
	// 亿佰start
	public function check() 
	{
				global $_W;
				global $_GPC;
			
				$id = intval($_GPC["member_id"]);

				$ids = pdo_fetch("select id from " . tablename("ewei_shop_member") . " where openid = :openid and uniacid = :uniacid ", array(":openid" => $_W['openid'], ":uniacid" => $_W["uniacid"]));
				$members = pdo_fetch("SELECT id,openid,agentid,nickname,realname,mobile,status,headsstatus,headsid FROM " . tablename("ewei_shop_member") . " WHERE id in( " . $id . " ) AND uniacid=" . $_W["uniacid"]);
				// 指定为二级队长且为已申请，审核后成为二级队长
				$result = pdo_update("ewei_shop_member", array("isheads" => 1,"agentheads"=>2), array("id" => $members["id"], "uniacid" => $_W["uniacid"]));
				$initData = pdo_fetch("select * from " . tablename("ewei_shop_dividend_init") . " where headsid = :headsid and uniacid = :uniacid", array(":headsid" => $members["id"], ":uniacid" => $_W["uniacid"]));
				if (empty($initData)) {
					pdo_insert("ewei_shop_dividend_init", $heads_data);
				}
			
				if (!empty($result)) {
					echo json_encode(array('code' => 1));
				} else {
					echo json_encode(array('code' => 2));
				}

		
	}
	
	// 亿佰end
	
	
	
}
?>