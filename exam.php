<?php 
require("simple_html_dom.php");
require("encrypt.php");

$sql = "SELECT * FROM table WHERE ……";
$query = mysql_query($sql);
if($row = mysql_fetch_array($query)){		//存在数据则进行抓取

	$account = $row[''];	//账号
	$password = $row[''];	//密码
	// 解密
	$encrypt = new Encrypt();
	$account = $encrypt->default_decrypt($account);
	$password = $encrypt->default_decrypt($password);
	
	$cookie_file = tempnam('./tmp','cookie'); 	//cookie
	$post_data = "IDToken0=&IDToken1=".$account."&IDToken2=".$password."&goto=aHR0cDovL215LnNjdWVjLmVkdS5jbi9pbmRleC5wb3J0YWw=";	//POST值
	$user_agent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.62 Safari/537.36";	//模拟浏览器

	$curltime = 5000;	//请求时间为5000ms=5s

	// 1. 初始化
	$ch = curl_init();
	// 2. 设置选项
	//首先模拟登陆到信息门户中
	curl_setopt($ch, CURLOPT_URL, "http://ids.scuec.edu.cn/amserver/UI/Login?goto=http://my.scuec.edu.cn/index.portal");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_REFERER, "http://www.scuec.edu.cn/");		//来源界面，说明是从学校主页进入的
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, $curltime); 	// 设置超时限制防止死循环
	// 3. 执行并获取HTML文档内容
	$LoginBoolean = curl_exec($ch);
	// 4. 释放curl句柄
	curl_close($ch);	

	// 1. 初始化
	$cookie_file2 = tempnam('./tmp','cookie2');
	$ch = curl_init();
	// 2. 设置选项
	curl_setopt($ch, CURLOPT_URL, "http://ssfw.scuec.edu.cn/ssfw/j_spring_ids_security_check");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_REFERER, "http://my.scuec.edu.cn/index.portal");
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	/*带着进入信息门户产生的cookie跳转到教务系统
	注意：跳转验证到教务系统的过程中会产生新的cookie，需要把新产生的cookie保存下来。*/
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file2);
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, $curltime); 	// 设置超时限制防止死循环
	// 3. 执行并获取HTML文档内容
	$LoginPermission = curl_exec($ch);
	// 4. 释放curl句柄
	curl_close($ch);

	// 1. 初始化
	$ch = curl_init();
	// 2. 设置选项
	curl_setopt($ch, CURLOPT_URL, "http://ssfw.scuec.edu.cn/ssfw/xsks/kcxx.do");	//考试安排页面
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_REFERER, "http://ssfw.scuec.edu.cn/ssfw/j_spring_ids_security_check");
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	/*使用新的cookie进入到教务系统的具体页面上*/
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file2); 
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, $curltime); 	// 设置超时限制防止死循环
	// 3. 执行并获取HTML文档内容
	$coursepage = curl_exec($ch);
	// 4. 释放curl句柄
	curl_close($ch);

	if (preg_match("/Error/", $coursepage)) {	//判断服务器是否连通
		$contentstr = "学校服务器又抽风了/::'(，你可以过段时间再试。\n你可以回复【公交/地铁】,【资讯最新】,【天气】试试。/::P 回复【帮助】获取功能菜单。";
	}
	elseif (preg_match("/Authentication failed/", $LoginBoolean)) {	//判断账号是否有误
		$contentstr = "你绑定的账号信息貌似有误/:P-(\n请点击http://www.stuzone.com/zixunminda/login.php?type=ssfw&tousername=".$tousername."重新绑定账号。";
	}
	elseif (preg_match("/权限/", $LoginPermission)) {	//判断教务系统是否能访问
		$contentstr = "教务系统暂时不能访问，请稍后再试。/::L";
	}
	else{
		// php html Dom
		$html = new simple_html_dom();
		$html->load($coursepage);
		$table = $html->find('.table_con',0);	//考试信息所在表格
		$examinfo = $table->find('.t_con');		//tr
		$examnum = count($examinfo);	//考试数量
		if ($examnum > 0) {
			$contentstr = '';	//初始化输出字符串
			for ($i=0; $i < $examnum; $i++) { 
				$td = $examinfo[$i]->find('td');	//考试具体信息
				$contentstr = $contentstr.($i+1)."-"."[".$td[4]."]".$td[2];		//老师和课程名
				$examtime = substr(strip_tags($td[7]), 5);		//时间，截去年份
				$contentstr = $contentstr."，时间：".$examtime;
				$contentstr = $contentstr."，地点：".$td[8]."，座次：".$td[6];	//地点和座次
				if (preg_match("/已结束/", $td[11])) {		//判断考试是否结束
					$contentstr .= "（已结束）\n";
				}
				$contentstr = strip_tags($contentstr);	//剥离html标签
				if ($i < ($examnum-1)) {
					$contentstr = $contentstr."\n";		//换行
				}
			}
			$contentstr = $this->tody_info()."\n---已安排考试课程---\n".$contentstr;	//添加时间、周次信息
		}
		else{
			$contentstr = $this->tody_info()."\n/:sun 目前没有已经安排的考试。平常好好学习才能考出好成绩哦。";
		}
	}
}
else{	// 	未绑定账号
	$contentstr = "绑定账号后即可查询考试信息。临阵磨枪，不亮也光。/:,@f\n请点击http://www.stuzone.com/zixunminda/login.php?type=ssfw&tousername=".$tousername."绑定账号。";
}
?>