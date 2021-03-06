<?php if(!defined('ROOT')) die('Access denied.');

class c_mymessages extends Admin{

	public function __construct($path){
		parent::__construct($path);

	}

	public function index(){
		$myid = $this->admin['aid'];

		$NumPerPage = 20;
		$page = ForceIntFrom('p', 1);
		$search = ForceStringFrom('s');
		$groupid = ForceStringFrom('g');
		$time = ForceStringFrom('t');

		if(IsGet('s')) $search = urldecode($search);

		if($time){
			ini_set('date.timezone', 'GMT'); //先设置为格林威治时区, 时区会影响strtotime函数将日期转为时间戳
			$start_time = intval(strtotime($time)) - 3600 * intval(APP::$_CFG['Timezone']); //再根据welive设置的时区转为UNIX时间戳
			$end_time = $start_time + 86400;
		}

		$start = $NumPerPage * ($page-1);

		//排序
		$order = ForceStringFrom('o');
        switch($order)
        {
            case 'fromid.down':
				$orderby = " fromid DESC ";
				break;

            case 'fromid.up':
				$orderby = " fromid ASC ";
				break;

            case 'toid.down':
				$orderby = " toid DESC ";
				break;

            case 'toid.up':
				$orderby = " toid ASC ";
				break;

            case 'time.down':
				$orderby = " time DESC ";
				break;

            case 'time.up':
				$orderby = " time ASC ";
				break;

            case 'mid.up':
				$orderby = " mid ASC ";
				break;

			default:
				$orderby = " mid DESC ";			
				$order = "mid.down";
				break;
		}


		SubMenu('我的对话记录', array(array('记录列表', 'mymessages', 1), array('检索QQ、手机、微信访客', 'mymessages/filter')));

		TableHeader('搜索对话记录');

		TableRow('<center><form method="post" action="'.BURL('mymessages').'" name="search" style="display:inline-block;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="12"  value="'.$search.'">&nbsp;&nbsp;&nbsp;<label>分类:</label>&nbsp;<select name="g"><option value="0">全部</option><option value="1" ' . Iif($groupid == '1', 'SELECTED') . ' class=red>客人的发言</option><option value="2" ' . Iif($groupid == '2', 'SELECTED') . '>我的发言</option></select>&nbsp;&nbsp;&nbsp;<label>日期:</label>&nbsp;<input type="text" name="t" class="date-input" value="' . $time . '" size="8">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索记录" class="cancel"></form></center>');
		
		TableFooter();

		if($search){
			if(preg_match("/^[1-9][0-9]*$/", $search)){
				$s = ForceInt($search);
				$searchsql = " WHERE (mid = '$s' OR fromid = '$s' OR toid = '$s') "; //按ID搜索
				$title = "搜索ID号为: <span class=note>$s</span> 的记录";
			}else{
				$searchsql = " WHERE (fromname LIKE '%$search%' OR toname LIKE '%$search%' OR msg LIKE '%$search%') ";
				$title = "搜索: <span class=note>$search</span> 的记录列表";
			}

			if($groupid) {
				if($groupid == 1 OR $groupid == 2){
					$searchsql .= " AND (" . Iif($groupid == 1, "type = 0 AND toid = '$myid'", "type = 1 AND fromid = '$myid'") . ") ";
					$title = "在 <span class=note>" .Iif($groupid == 1, '客人的发言', '我的发言'). "</span> 中, " . $title;
				}
			}else{
					$searchsql .= " AND ((type = 1 AND fromid = '$myid') OR (type = 0 AND toid = '$myid')) ";
			}

			if($time) {
				$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			}
		}else if($groupid){
			if($groupid == 1 OR $groupid == 2){
				$searchsql .= " WHERE " . Iif($groupid == 1, "type = 0 AND toid = '$myid' ", "type = 1 AND fromid = '$myid' ");
				$title = "全部 <span class=note>" .Iif($groupid == 1, '客人的发言', '我的发言'). "</span> 列表";
			}

			if($time) {
				$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			}
		}else if($time){
			$searchsql .= " WHERE time >= '$start_time' AND time < '$end_time' ";
			$title = "搜索日期: <span class=note>{$time}</span> 的记录列表";
		}else{
			$searchsql = " WHERE (type = 1 AND fromid = '$myid') OR (type = 0 AND toid = '$myid') ";
			$title = '全部记录列表';
		}

		$getmy = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "msg " . $searchsql . " ORDER BY {$orderby} LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(mid) AS value FROM " . TABLE_PREFIX . "msg " . $searchsql);

		echo '<script type="text/javascript" src="'.SYSDIR.'public/laydate/laydate.js"></script>';

		TableHeader($title . '(' . $maxrows['value'] . '个)');

		//TableRow(array('ID', '发送人', '接收人', '对话内容', '记录时间'), 'tr0');
		echo '<tr class="tr0"><td class=td><a class="do-sort" for="mid">ID</a></td><td class=td><a class="do-sort" for="fromid">发送人</a></td><td class=td><a class="do-sort" for="toid">接收人</a></td><td class="td" width="50%">对话内容</td><td class="td last"><a class="do-sort" for="time">记录时间</a></td></tr>';

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何记录!</font><BR><BR></center>');
		}else{
			while($msg = APP::$DB->fetch($getmy)){
				TableRow(array($msg['mid'],

				"<a title=\"编辑\" href=\"" . Iif($msg['type'], BURL('myprofile'), BURL('myguests/edit?gid='.$msg['fromid'])) . "\">$msg[fromname]</a>",

				"<a title=\"编辑\" href=\"" . Iif($msg['type'], BURL('myguests/edit?gid='.$msg['toid']), BURL('myprofile')) . "\">$msg[toname]</a>",

				Iif($msg['filetype'] == '1', $this->getImage($msg['msg']), Iif($msg['filetype'] == '2', $this->getFile($msg['msg']), getSmile($msg['msg']))),

				DisplayDate($msg['time'], '', 1)));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('mymessages'), $totalpages, $page, 10, array('s'=>urlencode($search), 'g'=>$groupid, 't'=>$time, 'o'=>$order)));
			}

		}

		TableFooter();

		//JS排序等
		echo '<script type="text/javascript">
			$(function(){
				var url = "' . BURL("mymessages") . FormatUrlParam(array('p'=>$page, 's'=>urlencode($search), 'g'=>$groupid, 't'=>$time)) . '";

				format_sort(url, "' . $order . '");

				//日期选择器
				$(".date-input").each(function(){
					laydate.render({
						elem: this
					});
				});

			});
		</script>';

	}


	public function filter(){
		$myid = $this->admin['aid'];

		$NumPerPage = 20;
		$page = ForceIntFrom('p', 1);
		$time = ForceStringFrom('t');

		if($time){
			ini_set('date.timezone', 'GMT'); //先设置为格林威治时区, 时区会影响strtotime函数将日期转为时间戳
			$start_time = intval(strtotime($time)) - 3600 * intval(APP::$_CFG['Timezone']); //再根据welive设置的时区转为UNIX时间戳
			$end_time = $start_time + 86400;
		}

		$start = $NumPerPage * ($page-1);

		//排序
		$order = ForceStringFrom('o');
        switch($order)
        {
            case 'fromid.down':
				$orderby = " fromid DESC ";
				break;

            case 'fromid.up':
				$orderby = " fromid ASC ";
				break;

            case 'toid.down':
				$orderby = " toid DESC ";
				break;

            case 'toid.up':
				$orderby = " toid ASC ";
				break;

            case 'time.down':
				$orderby = " time DESC ";
				break;

            case 'time.up':
				$orderby = " time ASC ";
				break;

            case 'mid.up':
				$orderby = " mid ASC ";
				break;

			default:
				$orderby = " mid DESC ";			
				$order = "mid.down";
				break;
		}


		SubMenu('我的对话记录', array(array('记录列表', 'mymessages'), array('检索QQ、手机、微信访客', 'mymessages/filter', 1)));

		TableHeader('搜索对话内容中含有QQ、手机、微信的客人');

		TableRow('<center><form method="post" action="'.BURL('mymessages/filter').'" name="search" style="display:inline-block;"><label>日期:</label>&nbsp;<input type="text" name="t" class="date-input" value="' . $time . '" size="8">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索客人" class="cancel"></form></center>');
		
		TableFooter();

		$searchsql = " WHERE type =0 AND filetype <> 1 AND filetype <> 2 AND toid = '$myid' AND (msg REGEXP('[1-9][0-9]{4,}') OR msg REGEXP('[a-zA-Z]([-_a-zA-Z0-9]{5,19})') OR msg REGEXP('[1][2,3,4,5,6,7,8,9][0-9]{9}')) ";
		$title = '搜索到的客人列表';

		if($time){
			$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			$title = "搜索日期: <span class=note>{$time}</span> 的客人列表";
		}

		$getmy = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "msg " . $searchsql . " ORDER BY {$orderby} LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(mid) AS value FROM " . TABLE_PREFIX . "msg " . $searchsql);

		echo '<script type="text/javascript" src="'.SYSDIR.'public/laydate/laydate.js"></script>';

		TableHeader($title . '(' . $maxrows['value'] . '个)');

		echo '<tr class="tr0"><td class=td><a class="do-sort" for="mid">ID</a></td><td class=td><a class="do-sort" for="fromid">客人</a></td><td class=td><a class="do-sort" for="toid">客服</a></td><td class="td" width="50%">可能含有QQ、手机、微信的对话内容</td><td class="td last"><a class="do-sort" for="time">记录时间</a></td></tr>';

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何客人!</font><BR><BR></center>');
		}else{
			while($msg = APP::$DB->fetch($getmy)){
				$info = preg_replace("/[1-9][0-9]{4,}|[a-zA-Z]([-_a-zA-Z0-9]{5,19})|[1][2,3,4,5,6,7,8,9][0-9]{9}/", '<font color=red>${0}</font>', $msg['msg']);

				TableRow(array($msg['mid'],

				"<a title=\"编辑\" href=\"" . BURL('myguests/edit?gid='.$msg['fromid']) . "\">$msg[fromname]</a>",

				"<a title=\"编辑\" href=\"" . BURL('myprofile') . "\">$msg[toname]</a>",

				getSmile($info),

				DisplayDate($msg['time'], '', 1)));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('mymessages/filter'), $totalpages, $page, 10, array('t'=>$time, 'o'=>$order)));
			}

		}

		TableFooter();

		//JS排序等
		echo '<script type="text/javascript">
			$(function(){
				var url = "' . BURL("mymessages/filter") . FormatUrlParam(array('p'=>$page, 't'=>$time)) . '";

				format_sort(url, "' . $order . '");

				//日期选择器
				$(".date-input").each(function(){
					laydate.render({
						elem: this
					});
				});

			});
		</script>';

	}


	//解析图片
	private function getImage($msg){
		if(!$msg) return '';

		$img = explode('|', $msg);

		$img_src = $img[0];
		$img_w = $img[1];
		$img_h = $img[2];

		$show_h = 80;

		if(!$img_h) $img_h = 1;

		if(($img_w / $img_h) > 1){
			if($img_w < 1) $img_w = 1;
			$show_h = ForceInt($img_h * 80 / $img_w);
		}

		return '<img src="' . SYSDIR . 'upload/img/' . $img_src . '"  class="img_upload" style="height:' . $show_h . 'px;"  onclick="show_big_img(this, ' . $img_w . ', ' . $img_h . ');">';
	}

	//解析文件下载
	private function getFile($msg){
		if(!$msg) return '';

		$file = explode('|', $msg);

		return '<a href="' . SYSDIR . 'upload/file/' . $file[0] . '" target="_blank" download="' . $file[1] . '" class="down"><img src="' . SYSDIR . 'public/img/save.png">&nbsp;&nbsp;点击下载: ' . $file[1] . '</a>';
	}

} 

?>