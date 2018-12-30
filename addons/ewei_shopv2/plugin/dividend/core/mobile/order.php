<?php  if( !defined("IN_IA") ) 
{
	exit( "Access Denied" );
}
require(EWEI_SHOPV2_PLUGIN . "dividend/core/page_login_mobile.php");
class Order_EweiShopV2Page extends DividendMobileLoginPage 
{
	public function main() 
	{ 
		
		global $_W;
		global $_GPC;
		$page_title = "商城";
		if( !empty($_W["shopset"]["shop"]["name"]) ) 
		{
			$page_title = $_W["shopset"]["shop"]["name"];
		}
		$member = $this->model->getInfo($_W["openid"], array( "total", "ordercount0" ));
		
		
		//print_r($member);die;
		include($this->template());
		
	}
	//亿佰start
	public function get_list() 
	{
		
		
		global $_W;
		global $_GPC;
		$pindex = max(1, intval($_GPC["page"]));
		$psize = 20;
		$openid = $_W["openid"];
		// oCU8WuMbCTNdBNmbF4nM2AbxPTlk 当前会员的openid
		
		$member = $this->model->getInfo($openid, array( "ordercount0" ));
		//print_r($member);die;
		//当前用户所有信息
		$status = trim($_GPC["status"]);
		$condition = " and status>=0";
		if( $status != "" ) 
		{
			$condition = " and status=" . intval($status);
		}
		$ordercount = $member["ordercount0"];
		// 由接口传来数据
		
		$list = pdo_fetchall("select id,ordersn,openid,createtime,price,status,dividend from " . tablename("ewei_shop_order") . " where headsid = " . $member["id"] . $condition . " and uniacid = :uniacid order by id desc limit " . ($pindex - 1) . "," . $psize, array( ":uniacid" => $_W["uniacid"] ));
		//Array ( [0] => Array ( [id] => 173 [ordersn] => SH20181214152759480624 [openid] => oCU8WuJlWuBx-J90EvqRPu7UJnio [createtime] => 1544772479 [price] => 1000.00 [status] => 3 [dividend] => a:2:{s:14:"dividend_price";s:5:"20.00";s:14:"dividend_ratio";d:2;} ) )
		
		
		if( !empty($list) ) 
		{
			foreach( $list as &$row ) 
			{
				$row["createtime"] = date("Y-m-d H:i", $row["createtime"]);
				if( $row["status"] == 0 ) 
				{
					$row["statusstr"] = "待付款";
				}
				else 
				{
					if( $row["status"] == 1 ) 
					{
						$row["statusstr"] = "已付款";
					}
					else 
					{
						if( $row["status"] == 2 ) 
						{
							$row["statusstr"] = "待收货";
						}
						else 
						{
							if( $row["status"] == 3 ) 
							{
								$row["statusstr"] = "已完成";
							}
						}
					}
				}
				
				//$dividend = iunserializer($row["dividend"]);
						//	$row["dividend_price"] = $dividend["dividend_price"];
				$dividend = iunserializer($row["dividend"]);
				$row["dividend_price"] = $dividend["dividend_price"];
				if($member['agentheads'] == 1){
					//查询一级队长下级是否拥有二级队长。如果拥有则获得二级队长所有的下线成员
							$member1 = $this->model->getInfo($row['openid']);
							$list1 = pdo_fetchall("select id,ordersn,openid,createtime,price,status,dividend from " . tablename("ewei_shop_order") . " where headsid = " . $member1["id"] . $condition . " and uniacid = :uniacid order by id desc limit " . ($pindex - 1) . "," . $psize, array( ":uniacid" => $_W["uniacid"] ));
							foreach($list1 as $haha){
								$row["createtime"] = date("Y-m-d H:i", $row["createtime"]);
								if( $row["status"] == 0 ) 
								{
									$row["statusstr"] = "待付款";
								}else 
								{	if( $row["status"] == 1 ) 
									{
										$row["statusstr"] = "已付款";
									}
									else
									{
										if( $row["status"] == 2 ) 
										{
											$row["statusstr"] = "待收货";
										}else{
											
											if( $row["status"] == 3 ) 
											{
												$row["statusstr"] = "已完成";
											}
											}
				
										}
									}
								}
							
							$dividend1 = iunserializer($value["dividend"]);
							$row1["dividend_price"] += $dividend1["dividend_price"];
							}
							$row["dividend_price"]+=$row1["dividend_price"];
					//print_r($member1);die;
				
				// 获取该笔订单队长分红金额
				$row["buyer"] = m("member")->getMember($row["openid"]);
				// 获取分红订单信息
				$goods = pdo_fetchall("select og.id,og.goodsid,g.thumb,og.price,og.optionname,g.title from " . tablename("ewei_shop_order_goods") . " og " . " left join " . tablename("ewei_shop_goods") . " g on g.id = og.goodsid" . " where og.orderid = :orderid and og.uniacid = :uniacid order by og.createtime desc", array( ":orderid" => $row["id"], ":uniacid" => $_W["uniacid"] ));
				// 商品价格
				
				$row["order_goods"] = set_medias($goods, "thumb");
			}
			unset($row);
		}
		
		//print_r($list);die;
		show_json(1, array( "list" => $list, "pagesize" => $psize, "total" => $ordercount ));
	}
}
//亿佰end
function order_sort($a, $b) 
{
	if( $a["createtime"] == $b["createtime"] ) 
	{
		return 0;
	}
	return ($a["createtime"] < $b["createtime"] ? 1 : -1);
}
?>