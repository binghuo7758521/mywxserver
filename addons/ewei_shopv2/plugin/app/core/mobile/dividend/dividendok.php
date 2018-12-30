<?php

error_reporting(E_ALL);

//define("IN_MOBILE", true);

require(dirname(__FILE__) . "/../../../../../../../framework/bootstrap.inc.php");



require(IA_ROOT . "/addons/ewei_shopv2/defines.php");

require(IA_ROOT . "/addons/ewei_shopv2/core/inc/functions.php");

require(IA_ROOT . "/addons/ewei_shopv2/core/inc/plugin_model.php");

require(IA_ROOT . "/addons/ewei_shopv2/core/inc/com_model.php");



global $_W;

date_default_timezone_set("Asia/Shanghai");

date_default_timezone_set('PRC');



//上个月的起始时间

$lastMonthBeginTime = strtotime(date('Y-m-01 00:00:00', strtotime('-1 month')));

//上个月的结束时间

$lastMonthEndTime = strtotime(date("Y-m-d 23:59:59", strtotime(-date('d') . 'day')));



//上个月   订单状态已完成

$sql = "SELECT * FROM " . tablename("ewei_shop_order")

    . " WHERE  status>=3 AND is_divendended=0 AND headsid>0 AND (finishtime>=$lastMonthBeginTime AND finishtime<=$lastMonthEndTime)";

$orders = pdo_fetchAll($sql);

//根据订单的headsid对订单进行分组

$result = array();

foreach ($orders as $order) {

    $result[$order['headsid']][] = $order;

}

foreach ($result as $headsId => $orders) {

    if ($headsId > 0) {

        $headsInfo = pdo_fetch('SELECT agentheads,isheads,headsstatus,headsid FROM ' . tablename('ewei_shop_member') . ' WHERE id=' . $headsId);

        if ($headsInfo['agentheads'] >= 1 && $headsInfo['isheads'] == 1 && $headsInfo['headsstatus'] == 1) {

            $orderTotal = 0;

            $orderIds = array();

            foreach ($orders as $order) {

                $orderIds[] = $order['id'];

                $orderTotal += $order['price'];

            }

            $orderIds = implode(',',$orderIds);

            //echo $headsId . ':' . $orderIds . '<br>';

            $res=pdo_query('UPDATE '.tablename('ewei_shop_order')." SET is_divendended=1 WHERE id IN ({$orderIds})");

            $_W['uniacid'] = $orders[0]['uniacid'];

            $set = m('common')->getPluginSet('dividend');



            //统计该headsid下所有订单的总额



            //如果为二级队长

            if ($headsInfo['agentheads'] == 2) {

                if ($set["startmoney1"] <= $orderTotal && $orderTotal <= $set["endmoney1"]) {

                    $dividendTadio = $set["ratio1"];

                } elseif ($set["startmoney2"] < $orderTotal && $orderTotal <= $set["endmoney2"]) {

                    $dividendTadio = $set["ratio2"];

                } elseif ($set["startmoney3"] < $orderTotal && $orderTotal <= $set["endmoney3"]) {

                    $dividendTadio = $set["ratio3"];

                } elseif ($set["startmoney4"] < $orderTotal) {

                    $dividendTadio = $set["ratio4"];

                }



                $dividendArray['member_id'] = $headsId;

                $dividendArray['dividend_month'] = $lastMonthBeginTime;  //上个月的开始时间戳

                $dividendArray['dividend_ratio'] = $dividendTadio;

                $dividendArray['dividend_money'] = round($orderTotal * $dividendTadio / 100, 2);

                $dividendArray['dividend_status'] = 0;   //未分红状态

                $dividendArray['level'] = $headsInfo['agentheads'];   //队长等级

                $dividendArray['order_ids'] = $orderIds;
				


                pdo_insert('ewei_shop_dividendok', $dividendArray);



                //二级队长的上级队长(一级队长)

                $topHeadsInfo = pdo_fetch('SELECT id,agentheads,isheads,headsstatus FROM ' . tablename('ewei_shop_member') . ' WHERE id=' . $headsInfo['headsid']);

                if ($topHeadsInfo['isheads'] == 1 && $topHeadsInfo['headsstatus'] == 1) {

                    $dividendArray['member_id'] = $topHeadsInfo['id'];

                    $dividendArray['dividend_month'] = $lastMonthBeginTime;  //上个月的开始时间戳

                    $dividendArray['dividend_ratio'] = $set['ratio5'];

                    $dividendArray['dividend_money'] = round($orderTotal * $set['ratio5'] / 100, 2);

                    $dividendArray['dividend_status'] = 0;   //未分红状态

                    $dividendArray['order_ids'] = $orderIds;   //分红关联的订单号

                    $dividendArray['level']=1;



                    //统计一级队长是否存在

                    $res = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_dividendok') . 'WHERE member_id=' . $topHeadsInfo['id'] . ' AND dividend_month=' . $lastMonthBeginTime);

                    //如果可提现记录表存在该一级队长的记录,则执行增加

                    if (!empty($res)) {

                        $money = $res['dividend_money'] + $dividendArray['dividend_money'];

                        $orderIds = $orderIds . ',' . $res['order_ids'];

                        pdo_update('ewei_shop_dividendok', array('dividend_money' => $money, 'order_ids' => $orderIds), array('member_id' => $topHeadsInfo['id'], 'dividend_month' => $lastMonthBeginTime));

                    } else {

                        pdo_insert('ewei_shop_dividendok', $dividendArray);

                    }

                }



            } elseif ($headsInfo['agentheads'] == 1) { //如果为一级队长



                $dividendArray['member_id'] = $headsId;

                $dividendArray['dividend_month'] = $lastMonthBeginTime;  //上个月的开始时间戳

                $dividendArray['dividend_ratio'] = $set['ratio5'];

                $dividendArray['dividend_money'] = round($orderTotal * $set['ratio5'] / 100, 2);

                $dividendArray['dividend_status'] = 0;   //未分红状态

                $dividendArray['level'] = $headsInfo['agentheads'];   //队长等级

                $dividendArray['order_ids']=$orderIds;



                //统计一级队长是否存在

                $res = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_dividendok') . 'WHERE member_id=' . $headsId . ' AND dividend_month=' . $lastMonthBeginTime);

                //如果可提现记录表存在该一级队长的记录,则执行增加

                if (!empty($res)) {

                    $orderIds = $orderIds . ',' . $res['order_ids'];

                    $money = $res['dividend_money'] + $dividendArray['dividend_money'];

                    pdo_update('ewei_shop_dividendok', array('dividend_money' => $money, 'order_ids' => $orderIds), array('member_id' => $headsId, 'dividend_month' => $lastMonthBeginTime));

                } else {

                    pdo_insert('ewei_shop_dividendok', $dividendArray);

                }

            }

        }

    }

}


echo "<script>alert('分红处理完成');
window.location.href='https://www.gouwanmei.wang/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=dividend.set';
</script>";
















