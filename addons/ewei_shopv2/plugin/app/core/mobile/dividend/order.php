<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(__DIR__ . "/base.php");
class Order_EweiShopV2Page extends Base_EweiShopV2Page 
{
	
	
	public function main() 
	{
		global $_GPC;
		global $_W;
		$member = $this->model->getInfo($_W["openid"], array( "total", "ordercount0" ));
		$pindex = max(1, intval($_GPC["page"]));
		$psize = 20;
		$status = trim($_GPC["status"]);
		$condition = " and status>=0";
		if( $status != "" ) 
		{
			$condition = " and status=" . intval($status);
		}
		$ordercount = $member["ordercount0"];
				//亿佰start
				// 查询所有下线的订单
				
				// 这里直接根据我现有一级队长ID。去查询 查询的是我所有下线
				$list = pdo_fetchall("select id,ordersn,openid,createtime,price,dividend,status from " . tablename("ewei_shop_order") . " where headsid = " . $member["id"] . $condition . " and uniacid = :uniacid order by id desc limit " . ($pindex - 1) . "," . $psize, array(":uniacid" => $_W["uniacid"]));
				
				// 根据我的id member 表中查询出我的所有下线的ID。和队长状态
				
				
				if ($member['agentheads'] == 1) {
				
					$resultss = pdo_fetchAll("select id,agentheads from " . tablename("ewei_shop_member") . " where headsid = :headsid and uniacid = :uniacid", array(":headsid" => $member['id'], ":uniacid" => $_W["uniacid"]));
					
					foreach ($resultss as $res) {
				
						if ($res['agentheads'] == 2) {
				
							// 根据订单获取到的openid 读取其中下线ID是是否为二级队长，然后遍历
							$list1 = pdo_fetchall("select id,ordersn,openid,createtime,price,dividend,status from " . tablename("ewei_shop_order") . " where headsid = " . $res["id"] . $condition . " and uniacid = :uniacid order by id desc limit " . ($pindex - 1) . "," . $psize, array(":uniacid" => $_W["uniacid"]));
						}
					}
			
					if (!empty($list1)) {
						$list = array_merge($list, $list1);
					}
					
				}
					
				
				//亿佰end
				if (!empty($list)) {
					foreach ($list as &$row) {
				
						$row["createtime"] = date("Y-m-d H:i", $row["createtime"]);
						if ($row["status"] == 0) {
							$row["statusstr"] = "待付款";
						} else {
							if ($row["status"] == 1) {
								$row["statusstr"] = "已付款";
							} else {
								if ($row["status"] == 2) {
									$row["statusstr"] = "待收货";
								} else {
									if ($row["status"] == 3) {
										$row["statusstr"] = "已完成";
									}
								}
							}
						}
				
				
						$dividend = iunserializer($row["dividend"]);
						$row["dividend_price"] = $dividend["dividend_price"];
						
				$row["buyer"] = m("member")->getMember($row["openid"]);
				//print_r($row["buyer"]);die;
				$goods = pdo_fetchall("select og.id,og.goodsid,g.thumb,og.price,og.optionname,g.title from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on g.id = og.goodsid" . " where og.orderid = :orderid and og.uniacid = :uniacid order by og.createtime desc", array( ":orderid" => $row["id"], ":uniacid" => $_W["uniacid"] ));
				$row["order_goods"] = set_medias($goods, "thumb");
			}
			unset($row);
		}
		
		$result = array( "member" => $member, "list" => $list, "pagesize" => $psize, "ordercount" => $ordercount, "total" => count($list), "textyuan" => $this->set["texts"]["yuan"], "textdividend" => $this->set["texts"]["dividend"] );
		
		
		app_json($result);
	}
}
?>