<?php if (!defined("IN_IA")) {
    exit("Access Denied");
}
require(EWEI_SHOPV2_PLUGIN . "dividend/core/dividend_page_web.php");

class Agent_EweiShopV2Page extends DividendWebPage
{

    public function main()
    {


        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC["page"]));
        $psize = 20;
        $params = array();
        $condition = "";
        $searchfield = "member";
        $keyword = trim($_GPC["keyword"]);
        if (!empty($searchfield) && !empty($keyword) && $searchfield == "member") {
            $condition .= " and ( dm.realname like :keyword or dm.nickname like :keyword or dm.mobile like :keyword)";
            $params[":keyword"] = "%" . $keyword . "%";
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime("-1 month");
            $endtime = time();
        }
        if (!empty($_GPC["time"]["start"]) && !empty($_GPC["time"]["end"])) {
            $starttime = strtotime($_GPC["time"]["start"]);
            $endtime = strtotime($_GPC["time"]["end"]);
            $condition .= " AND dm.headstime >= :starttime AND dm.headstime <= :endtime ";
            $params[":starttime"] = $starttime;
            $params[":endtime"] = $endtime;
        }
        if ($_GPC["headsstatus"] != "") {
            $condition .= " and dm.headsstatus=" . intval($_GPC["status"]);
        }
        //修改前$sql = "select dm.*,dm.nickname,dm.avatar,p.id as pid,p.nickname as parentname,p.avatar as parentavatar  from " . tablename("ewei_shop_member") . " dm " . " left join " . tablename("ewei_shop_member") . " p on p.id = dm.headsid " . " where dm.uniacid = " . $_W["uniacid"] . " and dm.isagent =1 and dm.isheads = 1 " . $condition . " ORDER BY dm.agenttime desc";
        // 亿佰start
        // 修改后

        $sql = "select dm.*,dm.nickname,dm.avatar,p.id as pid,p.nickname as parentname,p.avatar as parentavatar  from " . tablename("ewei_shop_member") . " dm " . " left join " . tablename("ewei_shop_member") . " p on p.id = dm.headsid " . " where dm.uniacid = " . $_W["uniacid"] . " and dm.isagent =1" . $condition . " ORDER BY dm.agenttime desc";
        // 亿佰end
        if (empty($_GPC["export"])) {
            $sql .= " limit " . ($pindex - 1) * $psize . "," . $psize;
        }
        $list = pdo_fetchall($sql, $params);  //查询所有分销商会员

        //print_r($list);die;
        //修改前$total = pdo_fetchcolumn("select count(dm.id) from" . tablename("ewei_shop_member") . " dm  " . " left join " . tablename("ewei_shop_member") . " p on p.id = dm.headsid " . " where dm.uniacid =" . $_W["uniacid"] . " and dm.isagent =1 and dm.isheads = 1 " . $condition, $params);
        // 亿佰start
        // 修改后
        $total = pdo_fetchcolumn("select count(dm.id) from" . tablename("ewei_shop_member") . " dm  " . " left join " . tablename("ewei_shop_member") . " p on p.id = dm.headsid " . " where dm.uniacid =" . $_W["uniacid"] . " and dm.isagent =1" . $condition, $params);

        // 亿佰end
        foreach ($list as &$row) {
            $info = $this->model->getInfo($row["openid"], array("total", "pay"));
            //当前会员的下线数量
            $row["groupscount"] = $info["groupscount"];
            $row["credit1"] = m("member")->getCredit($row["openid"], "credit1");
            $row["credit2"] = m("member")->getCredit($row["openid"], "credit2");
            $row["dividend_total"] = $info["dividend_total"];
            $row["dividend_pay"] = $info["dividend_pay"];
            if (p("diyform") && !empty($row["diymemberfields"]) && !empty($row["diymemberdata"])) {
                $diyformdata_array = p("diyform")->getDatas(iunserializer($row["diymemberfields"]), iunserializer($row["diymemberdata"]));
                $diyformdata = "";
                foreach ($diyformdata_array as $da) {
                    $diyformdata .= $da["name"] . ": " . $da["value"] . "\r\n";
                }
                $row["member_diyformdata"] = $diyformdata;
            }
            if (p("diyform") && !empty($row["diycommissionfields"]) && !empty($row["diycommissiondata"])) {
                $diyformdata_array = p("diyform")->getDatas(iunserializer($row["diycommissionfields"]), iunserializer($row["diycommissiondata"]));
                $diyformdata = "";
                foreach ($diyformdata_array as $da) {
                    $diyformdata .= $da["name"] . ": " . $da["value"] . "\r\n";
                }
                $row["agent_diyformdata"] = $diyformdata;
            }

        }


        unset($row);
        if ($_GPC["export"] == "1") {
            ca("commission.agent.export");
            plog("commission.agent.export", "导出分销商数据");
            foreach ($list as &$row) {
                $row["createtime"] = date("Y-m-d H:i", $row["createtime"]);
                $row["agentime"] = (empty($row["agenttime"]) ? "" : date("Y-m-d H:i", $row["agentime"]));
                $row["groupname"] = (empty($row["groupname"]) ? "无分组" : $row["groupname"]);
                $row["levelname"] = (empty($row["levelname"]) ? "普通等级" : $row["levelname"]);
                $row["parentname"] = (empty($row["pid"]) ? "总店" : "[" . $row["agentid"] . "]" . $row["parentname"]);
                $row["statusstr"] = (empty($row["status"]) ? "未通过" : "通过");
                $row["followstr"] = (empty($row["followed"]) ? "" : "已关注");
                $row["realname"] = str_replace("=", "", $row["realname"]);
                $row["nickname"] = str_replace("=", "", $row["nickname"]);
            }
            unset($row);
            $columns = array(array("title" => "ID", "field" => "id", "width" => 12), array("title" => "昵称", "field" => "nickname", "width" => 12), array("title" => "姓名", "field" => "realname", "width" => 12), array("title" => "手机号", "field" => "mobile", "width" => 12), array("title" => "微信号", "field" => "weixin", "width" => 12), array("title" => "openid", "field" => "openid", "width" => 24), array("title" => "下级团员总数", "field" => "levelcount", "width" => 12), array("title" => "累计分红", "field" => "commission_total", "width" => 12), array("title" => "打款分红", "field" => "commission_pay", "width" => 12), array("title" => "注册时间", "field" => "createtime", "width" => 12), array("title" => "成为队长时间", "field" => "createtime", "width" => 12), array("title" => "审核状态", "field" => "statusstr", "width" => 12));
            if (p("diyform")) {
                $columns[] = array("title" => "团队会员自定义信息", "field" => "member_diyformdata", "width" => 36);
                $columns[] = array("title" => "团队申请自定义信息", "field" => "agent_diyformdata", "width" => 36);
            }
            m("excel")->export($list, array("title" => "团队数据-" . date("Y-m-d-H-i", time()), "columns" => $columns));
        }
        $pager = pagination2($total, $pindex, $psize);
        load()->func("tpl");

        include($this->template());
    }

    public function delete()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        if (empty($id)) {
            $id = (is_array($_GPC["ids"]) ? implode(",", $_GPC["ids"]) : 0);
        }
        $members = pdo_fetchall("SELECT * FROM " . tablename("ewei_shop_member") . " WHERE id in( " . $id . " ) AND uniacid=" . $_W["uniacid"]);
        foreach ($members as $member) {
            pdo_update("ewei_shop_member", array("isheads" => 0, "headsstatus" => 0), array("id" => $member["id"]));
            plog("dividend.agent.delete", "取消队长资格 <br/>队长信息:  ID: " . $member["id"] . " /  " . $member["openid"] . "/" . $member["nickname"] . "/" . $member["realname"] . "/" . $member["mobile"]);
            $initData = pdo_fetch("select * from " . tablename("ewei_shop_dividend_init") . " where headsid = :headsid and uniacid = :uniacid", array(":headsid" => $member["id"], ":uniacid" => $_W["uniacid"]));
            if (!empty($initData)) {
                pdo_update("ewei_shop_dividend_init", array("status" => 0), array("headsid" => $member["id"]));
                $dividend = pdo_fetch("select id,isheads,headsid,headsstatus from " . tablename("ewei_shop_member") . " where id = :id", array(":id" => $member["agentid"]));
                $dividend_init = pdo_fetch("select * from " . tablename("ewei_shop_dividend_init") . " where headsid = :headsid", array(":headsid" => $member["agentid"]));
                if (!empty($dividend["isheads"]) && !empty($dividend["headsstatus"]) && !empty($dividend_init["status"])) {
                    pdo_update("ewei_shop_member", array("headsid" => $member["agentid"]), array("id" => $member["id"], "uniacid" => $_W["uniacid"]));
                    $data = pdo_fetchall("select id from " . tablename("ewei_shop_commission_relation") . " where pid = :pid", array(":pid" => $member["id"]));
                    if (!empty($data)) {
                        $ids = array();
                        foreach ($data as $k => $v) {
                            $ids[] = $v["id"];
                        }
                        pdo_update("ewei_shop_member", array("headsid" => $member["agentid"]), array("id" => $ids));
                    }
                } else {
                    if (empty($dividend["isheads"]) && !empty($dividend["headsid"])) {
                        pdo_update("ewei_shop_member", array("headsid" => $dividend["headsid"]), array("id" => $member["id"], "uniacid" => $_W["uniacid"]));
                        $data = pdo_fetchall("select id from " . tablename("ewei_shop_commission_relation") . " where pid = :pid", array(":pid" => $member["id"]));
                        if (!empty($data)) {
                            $ids = array();
                            foreach ($data as $k => $v) {
                                $ids[] = $v["id"];
                            }
                            pdo_update("ewei_shop_member", array("headsid" => $dividend["headsid"]), array("id" => $ids));
                        }
                    } else {
                        pdo_update("ewei_shop_member", array("headsid" => 0), array("id" => $member["id"], "uniacid" => $_W["uniacid"]));
                        $data = pdo_fetchall("select id from " . tablename("ewei_shop_commission_relation") . " where pid = :pid", array(":pid" => $member["id"]));
                        if (!empty($data)) {
                            $ids = array();
                            foreach ($data as $k => $v) {
                                $ids[] = $v["id"];
                            }
                            pdo_update("ewei_shop_member", array("headsid" => 0), array("id" => $ids));
                        }
                    }
                }
            }
        }
        show_json(1, array("url" => referer()));
    }

    //获取分销商信息 以及 下级信息
    public function user()
    {
        global $_W;
        global $_GPC;
        $headsid = intval($_GPC["id"]);
        $member = $this->model->getInfo($headsid);  //获取当前会员的信息
        $condition = " ";
        $params = array();
        $hasheads = true;
        if (!empty($_GPC["mid"])) {
            $condition .= " and dm.id=:mid";
            $params[":mid"] = intval($_GPC["mid"]);
        }
        $searchfield = "member";
        $keyword = trim($_GPC["keyword"]);
        if (!empty($keyword) && $searchfield == "member") {
            $condition .= " and ( dm.realname like :keyword or dm.nickname like :keyword or dm.mobile like :keyword)";
            $params[":keyword"] = "%" . $keyword . "%";
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime("-1 month");
            $endtime = time();
        }
        if ($_GPC["followed"] != "") {
            if ($_GPC["followed"] == 2) {
                $condition .= " and f.follow=0 and dm.uid<>0";
            } else {
                $condition .= " and f.follow=" . intval($_GPC["followed"]);
            }
        }
        $pindex = max(1, intval($_GPC["page"]));
        $psize = 20;
        $list = array();
        if ($hasheads) {
            // 亿佰start

            //下线总数量
            $total = pdo_fetchcolumn("select count(dm.id) from" . tablename("ewei_shop_member") . " dm " . " left join " . tablename("mc_mapping_fans") . "f on f.openid=dm.openid" . " where dm.uniacid =" . $_W["uniacid"] . " and dm.headsid = " . $headsid . " " . $condition, $params);
            // 查询当前会员的直接下线的 id 以及 等级
            // var_dump($member);
            $groupsMembers = pdo_fetchAll("select id,agentheads from " . tablename("ewei_shop_member") . " where headsid = :headsid and uniacid = :uniacid", array(":headsid" => $member["id"], ":uniacid" => $_W["uniacid"]));
            // 再获取下线所有用户的ID。
            //var_dump($groupsMembers);

            foreach ($groupsMembers as $groupsMember) {
                // var_dump($groupsMember);
                // 会员的下线如果是二级会员
                if ($groupsMember['agentheads'] == 2) {

                    $groupscounts2 = pdo_fetch("select count(id) as counts from " . tablename("ewei_shop_member") . " where headsid = :headsid and uniacid = :uniacid", array(":headsid" => $groupsMember["id"], ":uniacid" => $_W["uniacid"]));

                    $groupscounts3 = intval($groupscounts2['counts']);
                    //加上下线为二级队长的下线
                    $total += $groupscounts3;
                }
            }


            // 若果为一级队长查询下线人员列表一级其状态.ID
            if ($member['agentheads'] == 1) {
                // 一级其状态.ID

                //查询 所有一级队长下的二级
                $groupscounts1 = pdo_fetchAll("select id,agentheads,headsid from " . tablename("ewei_shop_member") . " where headsid = :headsid and uniacid = :uniacid", array(":headsid" => $member["id"], ":uniacid" => $_W["uniacid"]));
                foreach ($groupscounts1 as $values) {
                    if ($values['agentheads'] == 2) {
                        //二级队长的下级
                        $groupscounts3 = pdo_fetchAll("select id,agentheads,headsid from " . tablename("ewei_shop_member") . " where headsid = :headsid and uniacid = :uniacid", array(":headsid" => $values["id"], ":uniacid" => $_W["uniacid"]));
                        foreach ($groupscounts3 as $values3) {
                            $sql = "select dm.*  from " . tablename("ewei_shop_member") . " dm " . " left join " . tablename("mc_mapping_fans") . "f on f.openid=dm.openid  and f.uniacid=" . $_W["uniacid"] . " where dm.uniacid = " . $_W["uniacid"] . " and dm.headsid = " . $values3['headsid'] . "  " . $condition . "   ORDER BY dm.createtime desc";
                        }
				

                        if (empty($_GPC["export"])) {
                            $sql .= " limit " . ($pindex - 1) * $psize . "," . $psize;
                        }
                        $list1 = pdo_fetchall($sql, $params);

                    }
                }
            }
            if ($member['agentheads'] == 2) {
                /*		$sql = "select dm.*  from " . tablename("ewei_shop_member") . " dm " . " left join " . tablename("mc_mapping_fans") . "f on f.openid=dm.openid  and f.uniacid=" . $_W["uniacid"] . " where dm.uniacid = " . $_W["uniacid"] . " and dm.headsid = " . $headsid . "  " . $condition . "   ORDER BY dm.createtime desc";

                            if( empty($_GPC["export"]) )
                            {
                                $sql .= " limit " . ($pindex - 1) * $psize . "," . $psize;
                            }
                            $list1 = pdo_fetchall($sql, $params);*/
                $list1 = array();


            }
            $sql = "select dm.*  from " . tablename("ewei_shop_member") . " dm " . " left join " . tablename("mc_mapping_fans") . "f on f.openid=dm.openid  and f.uniacid=" . $_W["uniacid"] . " where dm.uniacid = " . $_W["uniacid"] . " and dm.headsid = " . $headsid . "  " . $condition . "   ORDER BY dm.createtime desc";

            if (empty($_GPC["export"])) {
                $sql .= " limit " . ($pindex - 1) * $psize . "," . $psize;
            }
            $list = pdo_fetchall($sql, $params);
            if (!empty($list1)) {


                $list = array_merge($list, $list1);


            }

            // 亿佰end


            $pager = pagination($total, $pindex, $psize);
            foreach ($list as &$row) {
                $info = $this->model->getInfo($row["openid"], array("total", "pay"));
                $row["credit1"] = m("member")->getCredit($row["openid"], "credit1");
                $row["credit2"] = m("member")->getCredit($row["openid"], "credit2");
                $row["commission_total"] = $info["commission_total"];
                $row["commission_pay"] = $info["commission_pay"];
                $row["followed"] = m("user")->followed($row["openid"]);
            }
        }

        unset($row);
        if ($_GPC["export"] == 1) {
            foreach ($list as &$row) {
                $row["realname"] = str_replace("=", "", $row["realname"]);
                $row["nickname"] = str_replace("=", "", $row["nickname"]);
                $row["createtime"] = date("Y-m-d H:i", $row["createtime"]);
                $row["followstr"] = (empty($row["followed"]) ? "" : "已关注");
                if (p("diyform") && !empty($row["diymemberfields"]) && !empty($row["diymemberdata"])) {
                    $diyformdata_array = p("diyform")->getDatas(iunserializer($row["diymemberfields"]), iunserializer($row["diymemberdata"]));
                    $diyformdata = "";
                    foreach ($diyformdata_array as $da) {
                        $diyformdata .= $da["name"] . ": " . $da["value"] . "\r\n";
                    }
                    $row["member_diyformdata"] = $diyformdata;
                }
                if (p("diyform") && !empty($row["diycommissionfields"]) && !empty($row["diycommissiondata"])) {
                    $diyformdata_array = p("diyform")->getDatas(iunserializer($row["diycommissionfields"]), iunserializer($row["diycommissiondata"]));
                    $diyformdata = "";
                    foreach ($diyformdata_array as $da) {
                        $diyformdata .= $da["name"] . ": " . $da["value"] . "\r\n";
                    }
                    $row["agent_diyformdata"] = $diyformdata;
                }
            }
            unset($row);
            $columns = array(array("title" => "ID", "field" => "id", "width" => 12), array("title" => "昵称", "field" => "nickname", "width" => 12), array("title" => "姓名", "field" => "realname", "width" => 12), array("title" => "手机号", "field" => "mobile", "width" => 12), array("title" => "微信号", "field" => "weixin", "width" => 12), array("title" => "openid", "field" => "openid", "width" => 24), array("title" => "注册时间", "field" => "createtime", "width" => 12), array("title" => "是否关注", "field" => "followstr", "width" => 12));
            if (p("diyform")) {
                $columns[] = array("title" => "团队分红会员自定义信息", "field" => "member_diyformdata", "width" => 36);
                $columns[] = array("title" => "团队分红申请自定义信息", "field" => "agent_diyformdata", "width" => 36);
            }
            m("excel")->export($list, array("title" => "团员信息-" . date("Y-m-d-H-i", time()), "columns" => $columns));
        }
        load()->func("tpl");
        include($this->template("dividend/agent_user"));
    }


    public function query()
    {
        global $_W;
        global $_GPC;
        $kwd = trim($_GPC["keyword"]);
        $wechatid = intval($_GPC["wechatid"]);
        if (empty($wechatid)) {
            $wechatid = $_W["uniacid"];
        }
        $params = array();
        $params[":uniacid"] = $wechatid;
        $condition = " and uniacid=:uniacid and isagent=1 and status=1";
        if (!empty($kwd)) {
            $condition .= " AND ( `nickname` LIKE :keyword or `realname` LIKE :keyword or `mobile` LIKE :keyword )";
            $params[":keyword"] = "%" . $kwd . "%";
        }
        if (!empty($_GPC["selfid"])) {
            $condition .= " and id<>" . intval($_GPC["selfid"]);
        }
        $ds = pdo_fetchall("SELECT id,avatar,nickname,openid,realname,mobile FROM " . tablename("ewei_shop_member") . " WHERE 1 " . $condition . " order by createtime desc", $params);
        foreach ($ds as $key => $val) {
            $ds[$key]["nickname"] = str_replace("'", "", $ds[$key]["nickname"]);
        }
        include($this->template("commission/query"));
    }

    // 亿佰start
    public function check()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        if (empty($id)) {
            $id = (is_array($_GPC["ids"]) ? implode(",", $_GPC["ids"]) : 0);
        }
        $status = intval($_GPC["headsstatus"]);
        $members = pdo_fetchall("SELECT id,openid,agentid,nickname,realname,mobile,status,headsstatus,isheads FROM " . tablename("ewei_shop_member") . " WHERE id in( " . $id . " ) AND uniacid=" . $_W["uniacid"]);
		
		
				$time = time();
				foreach ($members as $member) {
					if ($member["headsstatus"] === $status) {
						continue;
					}
			if($member['isheads'] == 0){
				
				
				echo 2;die;
				/*echo "<script>alert('不满足审核条件');console.log(1);document.write('1');
				window.location.href= '/web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=dividend.agent';
				</script>";
				die;*/
				
				}

            if ($member['isheads'] == 1 && $status == 1) {
                $this->model->sendMessage($member["openid"], array("nickname" => $member["nickname"], "headstime" => $time), TM_DIVIDEND_DOWNLINE_BECOME);
                pdo_update("ewei_shop_member", array("headsstatus" => 1, "headstime" => $time, "agentheads" => 2), array("id" => $member["id"], "uniacid" => $_W["uniacid"]));
                $heads_data["uniacid"] = $_W["uniacid"];
                $heads_data["headsid"] = $member["id"];
                $heads_data["status"] = 0;
                $initData = pdo_fetch("select * from " . tablename("ewei_shop_dividend_init") . " where headsid = :headsid and uniacid = :uniacid", array(":headsid" => $member["id"], ":uniacid" => $_W["uniacid"]));
                if (empty($initData)) {
                    pdo_insert("ewei_shop_dividend_init", $heads_data);
                }
                plog("dividend.agent.check", "审核队长 <br/>队长信息:  ID: " . $member["id"] . " /  " . $member["openid"] . "/" . $member["nickname"] . "/" . $member["realname"] . "/" . $member["mobile"]);
                $this->model->sendMessage($member["openid"], array("nickname" => $member["nickname"], "headstime" => $time), TM_DIVIDEND_BECOME);
                $this->model->sendMessage($member["openid"], array("nickname" => $member["nickname"], "headstime" => $time), TM_DIVIDEND_BECOME_SALER);

            } else {
                pdo_update("ewei_shop_member", array("headsstatus" => 0, "headstime" => 0,"agentheads" => 0), array("id" => $member["id"], "uniacid" => $_W["uniacid"]));
                plog("commission.agent.check", "取消审核 <br/>队长信息:  ID: " . $member["id"] . " /  " . $member["openid"] . "/" . $member["nickname"] . "/" . $member["realname"] . "/" . $member["mobile"]);
            }


        }
        show_json(1, array("url" => referer()));
    }
    // 亿佰end
    //亿佰start
    public function agentheads()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        //$agentheads = intval($_GPC["agentheads"]);
        $time = time();
        //$status = intval($_GPC["headsstatus"]); 这个用不到了。
        // 获取队长状态JS提交。
        //$id = 2189;
        $members = pdo_fetchall("SELECT id,openid,agentid,nickname,realname,mobile,status,headsstatus,agentheads,headsid FROM " . tablename("ewei_shop_member") . " WHERE id in( " . $id . " ) AND uniacid=" . $_W["uniacid"]);
        // Array ( [0] => Array ( [id] => 2190 [openid] => oCU8WuJlWuBx-J90EvqRPu7UJnio [agentid] => 2189 [nickname] => 王守卫 [realname] => 张兴 [mobile] => 15963063660 [status] => 1 [headsstatus] => 0 [agentheads] => 0 ) )
        foreach ($members as $member) {
            //print_r($member);die;
            /*	if( $member["headsid"] != 0)
                {
                    //echo 2;
                    continue;
                }*/
            // 取消一级队长是时，还需要判断是不是二级队长指定而来的。如果是
            if ($member['agentheads'] == 1) {

                pdo_update("ewei_shop_member", array("headstime" => 0, "agentheads" => 0,"headsstatus" => 0,"isheads" => 0), array("id" => $member["id"], "uniacid" => $_W["uniacid"]));


                $html = "<span class='label label-default'>指定为一级队长</span>";
                if ($member['headsstatus'] == 1) {
                    $html2 = "<span class='label label-primary'>二级队长</span>";
                } else {
                    $html2 = "<span class='label label-primary'>团员</span>";
                }


            } else {
                //echo 1;
                if ($member['headsid'] != 0) {
                    $this->error('不满足升级为一级队长条件');
                } else {
                    $this->model->sendMessage($member["openid"], array("nickname" => $member["nickname"], "headstime" => $time), TM_DIVIDEND_DOWNLINE_BECOME);
                    pdo_update("ewei_shop_member", array("headstime" => $time, "agentheads" => 1,"isheads" => 1,"headsstatus" => 1), array("id" => $member["id"], "uniacid" => $_W["uniacid"]));
                    $html = "<span class='label label-primary'>已成为一级队长</span>";
                    $html2 = "<span class='label label-primary'>一级队长</span>";
                }

            }
        }

        show_json(1, array("html" => $html, "html2" => $html2));
    }

    //亿佰end
    public function agentblack()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC["id"]);
        if (empty($id)) {
            $id = (is_array($_GPC["ids"]) ? implode(",", $_GPC["ids"]) : 0);
        }
        $agentblack = intval($_GPC["agentblack"]);
        $members = pdo_fetchall("SELECT id,openid,nickname,realname,mobile,agentblack FROM " . tablename("ewei_shop_member") . " WHERE id in( " . $id . " ) AND uniacid=" . $_W["uniacid"]);
        foreach ($members as $member) {
            if ($member["agentblack"] === $agentblack) {
                continue;
            }
            if ($agentblack == 1) {
                pdo_update("ewei_shop_member", array("agentblack" => 1), array("id" => $member["id"]));
                plog("commission.agent.agentblack", "设置黑名单 <br/>分销商信息:  ID: " . $member["id"] . " /  " . $member["openid"] . "/" . $member["nickname"] . "/" . $member["realname"] . "/" . $member["mobile"]);
            } else {
                pdo_update("ewei_shop_member", array("agentblack" => 0), array("id" => $member["id"]));
                plog("commission.agent.agentblack", "取消黑名单 <br/>分销商信息:  ID: " . $member["id"] . " /  " . $member["openid"] . "/" . $member["nickname"] . "/" . $member["realname"] . "/" . $member["mobile"]);
            }
        }
        show_json(1, array("url" => referer()));
    }

    public function init()
    {
        global $_W;
        global $_GPC;
        $id = (is_array($_GPC["ids"]) ? implode(",", $_GPC["ids"]) : 0);
        $members = pdo_fetchall("select * from " . tablename("ewei_shop_member") . " WHERE id in( " . $id . " ) AND uniacid=" . $_W["uniacid"]);
        foreach ($members as $member) {
            if (empty($member["isheads"]) || empty($member["headsstatus"])) {
                continue;
            }
            $data = pdo_fetchall("select  r.id,r.pid,m.isheads from " . tablename("ewei_shop_commission_relation") . " r " . " left join " . tablename("ewei_shop_member") . " m on m.id = r.id where  r.pid=:pid and m.uniacid=:uniacid", array(":pid" => $member["id"], ":uniacid" => $_W["uniacid"]));
            if (!empty($data)) {
                $heads = array();
                $later = array();
                $ids = array();
                foreach ($data as $k => $v) {
                    if (!empty($v["isheads"])) {
                        $heads[] = $v["id"];
                        continue;
                    }
                    $ids[] = $v["id"];
                }
                if (!empty($heads)) {
                    $later = pdo_fetchall("select id from " . tablename("ewei_shop_commission_relation") . " where pid in (" . implode(",", $heads) . ")");
                }
                if (!empty($ids)) {
                    if (!empty($later)) {
                        $later = array_column($later, "id");
                        $ids = array_diff($ids, $later);
                    }
                    pdo_update("ewei_shop_member", array("headsid" => $member["id"]), array("id" => $ids));
                }
            }
            pdo_update("ewei_shop_dividend_init", array("status" => 1), array("headsid" => $member["id"]));
        }
        show_json(1);
    }
}

?>