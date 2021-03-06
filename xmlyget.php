<?php
/**
 * Created by PhpStorm.
 * User: CKylin
 * Date: 2017/9/10
 * Time: 12:23
 */
error_reporting(false);
const VE = 1.2;
system("title XiMaLaYa Audios Tool Version ".VE);
$copyright = "
 -------------------------------------------------
>                XMLY AUDIOS TOOL                 <
>                VERSION BETA ".VE."                 <
>                AUTHOR: CKYLINMC                 <
>                OPENSOURCE:GPLv3                 <
> PROJECT: https://github.com/Cansll/XiMaLaYa-Get <
 -------------------------------------------------
";
$logo = " _____ _  __     _ _       __  __  _____ 
/ ____| |/ /    | (_)     |  \/  |/ ____|
| |    | ' /_   _| |_ _ __ | \  / | |     
| |    |  <| | | | | | '_ \| |\/| | |     
| |____| . \ |_| | | | | | | |  | | |____ 
\_____|_|\_\__, |_|_|_| |_|_|  |_|\_____|
            __/ |                        
           |___/                         ";
output($copyright."\n\n".$logo);
//init config
output("");
$c = readConfig();
if(array_key_exists("cfgVersion",$c)){
    if($c['cfgVersion']!=VE){
        output("[!] 配置文件版本低，建议重新生成配置文件。(删除配置文件后重新运行程序即可。)");
    }
}else{
    output("[!] 配置文件过期，建议重新生成配置文件。(删除配置文件后重新运行程序即可。)");
}
readDict();
//UI MODE
output("\n\n[*] 欢迎使用喜马拉雅FM音频下载工具！");
while (true) {
    output("\n\n>[ 新的下载任务 ]----------------------\n");
    $res = ask("[?] 输入一个音频链接: ");
    if (empty($res)) {
        output("[!] 请输入一个链接！!");
        continue;
    }
    if ($res == "exit") {
        output("\n[!] 退出.");
        break;
    }
    $urlinfos = parse_url($res);
    $track = getTrack($urlinfos);
    if ($track === false) continue;
    output("\n[+] Track ID: $track \n[*] 正在获取信息...");
    $api = "http://www.ximalaya.com/tracks/$track.json";
//    $httpinfo;
//    $res = http_get($api, $httpinfo);
//    if ($httpinfo['response_code'] != "200") {
//        output("HTTP " . $httpinfo['response_code'] . " ERROR. JSON data get failed.");
//        continue;
//    }
    $r = cUrl($api);
//    $r = json_decode($res);
    if (empty($r)) {
        output("[!] 解析数据时出错.");
        continue;
    }
    if (!isset($r['res'])) {
        output("[+] 已经定位音频:\n\n");
        $downurl = $r['play_path'];
        $duration = $r['duration'] / 60;
        $title = t($r['title']);
        $user = t($r['nickname']);
        $realtime = t($r['formatted_created_at']);
        $time = t($r['time_until_now']);
        $album = t($r['album_title']);
        $intro = t($r['intro']);
        raw_output(t("上传用户:")." $user \n".t("音频长度: ")."$duration min \n".t("音频题目: ")."$title \n".t("所在专辑:")." $album \n".t("上传时间: ")."$time / $realtime \n".t("音频描述:")." $intro \n".t("音频链接:")." $downurl");

//        $filename = str_replace(" ","","$user-$title-$time-$ran.m4a");
//        $filename = "$user-$title-$album-$time-$ran.m4a";
//        $filename = getFileName($r);
        $infos = getCFG($r);
        $filename = $infos['file'];
        $down = $infos['api'];
        raw_output(t("\n\n[*] 准备下载...")."($filename)");
        $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . "audios" . DIRECTORY_SEPARATOR;
        $filepath = $path . $filename;
        @mkdir($path);
        raw_output(t("[+] 输出目录: ")."$path");
        $target = fopen($down, "rb");
        $newfile = '';
        if ($target) {
            $newfile = fopen($filepath, "wb");
            if ($newfile) {
                output("[*] 正在下载...");
                while (!feof($target)) {
                    fwrite($newfile, fread($target, 1024 * 8), 1024 * 8);
                }
                output("[*] 文件传输完成，正在进行最后的操作...");
            } else {
                //fclose($newfile);
                raw_output(t("[!] 文件写入时出错，无法打开本地文件，请检查权限.")."($filepath)");
                fclose($target);
                continue;
            }
        } else {
            //fclose($target);
            output("[!] 远程文件查找出错，无法下载，请检查网络.($down)");
            continue;
        }
        if ($target) fclose($target);
        if ($newfile) fclose($newfile);
        raw_output(t("\n\n[+] 文件已经成功下载到 ")."$filepath");
        output("文件大小: ".getSizeT($filepath)."\n");
        continue;
    } else {
        output("[!] 数据查询出错，检查输入的链接. ($res)");
        continue;
    }
}

function output($out)
{
    $out = t($out);
    fwrite(STDOUT, "\n$out");
}

function raw_output($out)
{
    // $out = t($out);
    fwrite(STDOUT, "\n$out");
}

function ask($out)
{
    output($out);
    return trim(fgets(STDIN));
}

function getTrack($info)
{
    if (empty($info)) return false;
    $allowhosts = array("m.ximalaya.com","www.ximalaya.com");
    if (!in_array($info["host"],$allowhosts)) {
        output("[!] 请输入完整链接!");
        return false;
    }
    //$sound = stristr($info["path"], "/sound/");
    //if ($sound === false) {
    //    output("[!] 请确保输入的是音频页面的链接!");
    //    return false;
    //}
    //$sound = str_replace("/", "", str_replace("/sound/", "", $sound));
	$pathparts = explode('/',$info["path"]);
	if(count($pathparts)>5){
		output("[!] 请确保输入的是音频页面的链接!");
		return false;
	}
	if(count($pathparts)===5&&empty($pathparts[4])) array_pop($pathparts);
	//$pathparts = array_shift($pathparts);
	$sound = array_pop($pathparts);
    //$sound = stristr($info["path"], "/sound/");
    if ($sound === false) {
        output("[!] 请确保输入的是音频页面的链接!");
        return false;
    }
    $sound = str_replace("/", "", str_replace("/sound/", "", $sound));
    return $sound;
}

function cUrl($url, $header = null, $data = null)
{
    //初始化curl
    $curl = curl_init();
    //设置cURL传输选项

    if (is_array($header)) {

        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);


    if (!empty($data)) {//post方式
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }

    //获取采集结果
    $output = curl_exec($curl);

    //关闭cURL链接
    curl_close($curl);

    //解析json
    $json = json_decode($output, true);
    //判断json还是xml
    if ($json) {
        return $json;
    } else {
        #验证xml
        libxml_disable_entity_loader(true);
        #解析xml
        $xml = simplexml_load_string($output, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $xml;
    }
}
function t($t){
//    return $t;
    return iconv("UTF-8","GBK",$t);
}

function getSizeT($file){
    $fs = filesize($file);
    if($fs===false) return "UNKNOW";
    $size = round(($fs/1024)/1024,2)."MB";
    return $size;
}

function readDict(){
    $dict = dirname(__FILE__).DIRECTORY_SEPARATOR."dict.txt";
    if(!file_exists($dict)) {
        fclose(fopen($dict,"w"));
        output("[*] 字典文件创建成功!");
    }
    $lines = file($dict);//str_replace(PHP_EOL,"",file($config));
    $dtdata = array();
    foreach($lines as $line){
        if(empty($line)) continue;
        $result = explode("=",str_replace(PHP_EOL,"",$line));
        $dtdata[$result[0]] = $result[1];
    }
    return $dtdata;
}

function readConfig(){
    $config = dirname(__FILE__).DIRECTORY_SEPARATOR."options.txt";
    if(!file_exists($config)) {
        $f = fopen($config,"w");
        fwrite($f,"cfgVersion=".VE.PHP_EOL."nameformat=%name-%title-%album-%time-%ran".PHP_EOL."source=web".PHP_EOL);
        fclose($f);
        output("[*] 配置文件创建成功!");
        output("[*] 文件名可用参数：\n\n\t%name\t\t真实姓名(无对应时输出用户名)\n\t%user\t\t用户名\n\t%duration\t音频长度(秒)\n\t%time\t\t中文相对时间\n\t%album\t\t专辑名\n\t%realtime\t上传日期\n\t%title\t\t音频标题\n\t%ran\t\t随机数\n");
        output("[*] 音频源选项：\n\n\tweb\t\t网页在线试听源(格式m4a 体积小)\n\torgin\t\t主播后台下载源(格式mp3 体积很大)\n");
    }
    $lines = file($config);//str_replace(PHP_EOL,"",file($config));
    $cfg = array();
    $default = array(
        "cfgVersion"=>VE,
        "nameformat"=>"%name-%title-%album-%time-%ran",
        "source"=>"web"
    );
    foreach($lines as $line){
        if(empty($line)) continue;
        $result = explode("=",str_replace(PHP_EOL,"",$line));
        $cfg[$result[0]] = $result[1];
    }
    return array_merge($default,$cfg);
}

function getName($name){
    $dict = readDict();
    if(array_key_exists($name,$dict)){
        $fn = $dict[$name];
        output("[*] 用户字典：'$name'已替换为'$fn'");
        return $fn;
    }
    return $name;
}

function getCFG($r){
    $rules = array(
        "%name"=>getName(t($r['nickname'])),
        "%user"=>t($r['nickname']),
        "%duration"=>t($r['duration']),
        "%time" => t($r['time_until_now']),
        "%album" => t($r['album_title']),
        "%realtime" => t($r['formatted_created_at']),
        "%title" => t($r['title']),
        "%ran" => rand(00001, 99999),
    );
    $cfg = readConfig();
    output("[*] 配置文件版本：".$cfg["cfgVersion"]);
    $base = "%name-%title-%album-%time-%ran";
    if(array_key_exists("nameformat",$cfg)){
        $base = $cfg['nameformat'];
    }
    $down = false;
    if(array_key_exists("source",$cfg)){
        $format = $cfg['source'];
    }
    if(strtolower($format)=="web"){
        $f = '.m4a';
        $down = $r['play_path'];
        output("[*] 音频源选定：网页在线试听源(格式m4a 体积小)");
    }elseif(strtolower($format)=="orgin"){
        $f = '.mp3';
        $down = "http://www.ximalaya.com/center/voice/download?trackId=".$r['id'];
        output("[*] 音频源选定：主播后台下载源(格式mp3 体积很大)\n[!] 请注意，mp3格式很容易下载失败，但是在浏览器打开可以正常下载。\n[*] MP3格式音频下载地址：\n$down\n");
    }else{
        $f = '.m4a';
        $down = $r['play_path'];
        output("[!] 配置文件中缺少'source'设置项，建议删除配置文件以重新生成。本次使用默认设置。");
        output("[*] 音频源选定：网页在线试听源(格式m4a 体积小)");
    }
    $filename = strtr($base,$rules).$f;
    return array('file'=>$filename,'api'=>$down);
}


//$down = $r['play_path'];
//$duration = $r['duration'] / 60;
//$title = t($r['title']);
//$user = t($r['nickname']);
//$realtime = t($r['formatted_created_at']);
//$time = t($r['time_until_now']);
//$album = t($r['album_title']);
//$intro = t($r['intro']);
output("\n\n> 脚本结束运行.\n\n");