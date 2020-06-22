<?php

use App\Utility\Console;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\ServerManager;
use EasySwoole\Http\Request;
use EasySwoole\Utility\Str;

/**
 * 获取当前工作进程id
 * @return int
 */
function getWorkId()
{
    return ServerManager::getInstance()->getSwooleServer()->worker_id;
}

/**
 * 获取客户端ip
 * @param Request $request
 * @return mixed
 */
function getClientIp(Request $request)
{
    $headers = $request->getHeaders();
    if (isset($headers['x-real-ip']) && $headers['x-real-ip'][0]) {
        return $headers['x-real-ip'][0];
    }
    $ipInfo = ServerManager::getInstance()->getSwooleServer()->connection_info($request->getSwooleRequest()->fd);
    return $ipInfo['remote_ip'];
}

/**
 * 获取唯一Id
 * @return bool|string
 */
function getUniqid()
{
    $str = md5(uniqid(mt_rand(), true));
    $uuid = substr($str, 0, 8);
    $uuid .= substr($str, 8, 4);
    $uuid .= substr($str, 12, 4);
    $uuid .= substr($str, 16, 4);
    $uuid .= substr($str, 20, 12);
    return $uuid;
}

/**
 * 获取字符串时间
 * @param string $time
 * @return false|string
 */
function getDateTimeStr($time = "")
{
    if(empty($time)) $time = time();
    return date('Y-m-d H:i:s', $time);
}

/**
 * 获取字符串时间
 * @param string $time
 * @return false|string
 */
function getDateStr($time = "")
{
    if(empty($time)) $time = time();
    return date('Y-m-d', $time);
}


/**
 * 获取字符串时间
 * @param string $time
 * @return false|string
 */
function getTimeStr($time = "")
{
    if(empty($time)) $time = time();
    return date('H:i', $time);
}

/**
 * 获取三位毫秒时间
 * @return bool|string
 */
function getMill()
{
    list($s1, $s2) = explode(' ', microtime());
    return substr($s1, 2, 3);
}

/**
 * 获取毫秒时间,以时间戳整数和三位小数的形式
 * @return float
 */
function getTimeMill()
{
    list($s1, $s2) = explode(' ', microtime());
    return (substr($s1, 0, 5) + $s2) * 1000;
}

/**
 * 把中文字符串格式化成标准时间串
 * @param $str
 * @return mixed
 */
function formatDateTimeStr($str){
    $str = str_replace('年', '-', $str);
    $str = str_replace('月', '-', $str);
    $str = str_replace('日', '', $str);
    return $str;
}

/**
 * 把中文字符串格式化成标准时间戳
 * @param $str
 * @return mixed
 */
function formatDateTime($str){
    $str = str_replace('年', '-', $str);
    $str = str_replace('月', '-', $str);
    $str = str_replace('日', '', $str);
    if(strpos($str, 'T16:00:00.000Z')!== false){
        return strtotime($str) + (3600 * 8);
    }else{
        return strtotime($str);
    }
}

/**
 * 获取配置值，支持.号连接，如:MYSQL.database
 * @param $name
 * @return array|mixed|null
 */
function getConfig($name)
{
    return Config::getInstance()->getConf($name);
}

/**
 * info 日志
 * @param $requestId
 * @param mixed ...$msg
 */
function logInfo($requestId, ...$msg)
{
    Console::writeLog($requestId, 'alllog', 'INFO', $msg);
}

/**
 * debug 日志
 * @param $requestId
 * @param mixed ...$msg
 */
function logDebug($requestId, ...$msg)
{
    if (getConfig('DEBUG')) {
        Console::writeLog($requestId, 'alllog', 'DEBUG', $msg);
    }
}

/**
 * 错误日志
 *
 * @param $requestId
 * @param mixed ...$msg
 */
function logError($requestId, ...$msg)
{
    Console::writeLog($requestId, 'error', 'ERROR', $msg);
}


/**
 * 把数组等转为json字符串
 * @param $data
 * @return false|string
 */
function toJsonStr($data)
{
    $data = longIntToStr($data);
    $result = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $result;
}


/**
 * 遍历把长整型转为字符串
 * @param $data
 * @return mixed
 */
function longIntToStr($data){
    foreach($data as $index => &$value){
        if(is_array($value)){
            $value = longIntToStr($value);
        } else if(is_object($value)){
            $value = longIntToStr($value);
        } else if(is_numeric($value) &&(strlen($value)>12)){
            $value = strval($value);
        }
    }
    return $data;
}


/**
 * 获取单个汉字拼音首字母
 * @param $str
 * @return null|string
 */
function getFirstChar($str)
{
    $firstChar = ord($str{0});

    if ($firstChar >= ord("A") and $firstChar <= ord("z"))
        return strtoupper($str{0});
    $s1 = iconv("UTF-8", "gb2312", $str);
    $s2 = iconv("gb2312", "UTF-8", $s1);
    if ($s2 == $str) {
        $s = $s1;
    } else {
        $s = $str;
    }
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 and $asc <= -20284)
        return "A";
    if ($asc >= -20283 and $asc <= -19776)
        return "B";
    if ($asc >= -19775 and $asc <= -19219)
        return "C";
    if ($asc >= -19218 and $asc <= -18711)
        return "D";
    if ($asc >= -18710 and $asc <= -18527)
        return "E";
    if ($asc >= -18526 and $asc <= -18240)
        return "F";
    if ($asc >= -18239 and $asc <= -17923)
        return "G";
    if ($asc >= -17922 and $asc <= -17418)
        return "H";
    if ($asc >= -17922 and $asc <= -17418)
        return "I";
    if ($asc >= -17417 and $asc <= -16475)
        return "J";
    if ($asc >= -16474 and $asc <= -16213)
        return "K";
    if ($asc >= -16212 and $asc <= -15641)
        return "L";
    if ($asc >= -15640 and $asc <= -15166)
        return "M";
    if ($asc >= -15165 and $asc <= -14923)
        return "N";
    if ($asc >= -14922 and $asc <= -14915)
        return "O";
    if ($asc >= -14914 and $asc <= -14631)
        return "P";
    if ($asc >= -14630 and $asc <= -14150)
        return "Q";
    if ($asc >= -14149 and $asc <= -14091)
        return "R";
    if ($asc >= -14090 and $asc <= -13319)
        return "S";
    if ($asc >= -13318 and $asc <= -12839)
        return "T";
    if ($asc >= -12838 and $asc <= -12557)
        return "W";
    if ($asc >= -12556 and $asc <= -11848)
        return "X";
    if ($asc >= -11847 and $asc <= -11056)
        return "Y";
    if ($asc >= -11055 and $asc <= -10247)
        return "Z";

    return NULL;
}

/*
 * 下划线转驼峰
 */
function underlineToCamel($str)
{
    return Str::camel($str);
}

/*
 * 驼峰转下划线
 */
function camelToUnderline($str){
    return Str::snake($str);
}

/**
 * 防xss过滤
 * creator: liming
 * @param $string
 * @param bool|False $low
 * @return mixed|string
 */
function cleanXss(&$string, $low = False)
{
    if (!is_array($string)) {
        $string = trim($string);
        $string = strip_tags($string);
        $string = htmlspecialchars($string);
        if ($low) {
            return $string;
        }
        $string = str_replace(array(
            '"',
            "'",
            "..",
            "../",
            "./",
            '/',
            "//",
            "<",
            ">"
        ), '', $string);
        $no = '/%0[0-8bcef]/';
        $string = preg_replace($no, '', $string);
        $no = '/%1[0-9a-f]/';
        $string = preg_replace($no, '', $string);
        $no = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';
        $string = preg_replace($no, '', $string);
        return $string;
    }
    $keys = array_keys($string);
    foreach ($keys as $key) {
        cleanXss($string [$key]);
    }
}

/**
 * 生成随机字符串
 * @param $length
 * @return bool|string
 */
function generateRandomString($length)
{
    $charset = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $repeatTime = $length / strlen($charset);
    $charset = str_repeat($charset, $repeatTime + 1);
    $charset = str_shuffle($charset);
    return substr($charset, 0, $length);
}

/**
 * 生成随机字符串
 * @param $length
 * @return bool|string
 */
function generateRandomNumber($length)
{
    $charset = '0123456789';
    $repeatTime = $length / strlen($charset);
    $charset = str_repeat($charset, $repeatTime + 1);
    $charset = str_shuffle($charset);
    return substr($charset, 0, $length);
}


/**
 * 是否以某字符串结尾
 * @param $longStr
 * @param $endStr
 * @return bool
 */
function endWith($longStr, $endStr){
    return strrchr($longStr, $endStr) == $endStr;
}

/**
 * 是否以某字符串开头
 * @param $longStr
 * @param $endStr
 * @return bool
 */
function startWith($longStr, $endStr){
    return strchr($longStr, $endStr) == $endStr;
}

/**
 * 把二维数组里的数据key转为camel
 * @param $rows
 * @return array
 */
function rowsToCamel($rows)
{
    if(!is_array($rows)) return $rows;
    foreach ($rows as $index => &$row) {
        if (!is_array($row)) return []; //如若不是二维数组，直接返回空数据
        foreach ($row as $key => $value) {
            $newKey = Str::camel($key);
            if ($key != $newKey) {
                $row[$newKey] = $value;
                unset($row[$key]);
            }
        }
    }
    return $rows;
}

/**
 * 把一维数组里的数据key转为camel
 * @param $row
 * @return array
 */
function rowToCamel($row)
{
    if (!is_array($row)) return []; //如若不是一维数组，直接返回空数据
    foreach ($row as $key => $value) {
        $newKey = Str::camel($key);
        if ($key != $newKey) {
            $row[$newKey] = $value;
            unset($row[$key]);
        }
    }
    return $row;
}

/**
 * 把一维数组里的数据key转为snake,用于数据更新等方法中
 * @param $row
 * @return array
 */
function rowToSnake($row)
{
    if (!is_array($row)) return []; //如若不是一维数组，直接返回空数据
    foreach ($row as $key => $value) {
        $newKey = Str::Snake($key);
        if ($key != $newKey) {
            $row[$newKey] = $value;
            unset($row[$key]);
        }
    }
    return $row;
}

/********************************************
 *
 * 函数名：get_utf8_to_gb($value)
 * 作  用：utf8编码字符串转换成gb2312编码
 * 作  者：刘先忠
 * 日  期：2011-11-09
 *
 ********************************************/
function   getUtf8ToGb($value){
    $value_1= $value;
    $value_2   =   @iconv( "utf-8", "gb2312//IGNORE",$value_1);//使用@抵制错误，如果转换字符串中，某一个字符在目标字符集里没有对应字符，那么，这个字符之后的部分就被忽略掉了；即结果字符串内容不完整，此时要使用//IGNORE
    $value_3   =   @iconv( "gb2312", "utf-8//IGNORE",$value_2);

    if   (strlen($value_1)   ==   strlen($value_3))
    {
        return   $value_2;
    }else
    {
        return   $value_1;
    }
}
/********************************************
 *
 * 函数名：get_gb_to_utf8($value)
 * 作  用：gb2312编码字符串转换成utf8编码
 * 作  者：刘先忠
 * 日  期：2011-11-09
 *
 ********************************************/
function   getGbToUtf8($value){
    $value_1= $value;
    $value_2   =   @iconv( "gb2312", "utf-8//IGNORE",$value_1);
    $value_3   =   @iconv( "utf-8", "gb2312//IGNORE",$value_2);
    if   (strlen($value_1)   ==   strlen($value_3))
    {
        return   $value_2;
    }else
    {
        return   $value_1;
    }
}

/**
 * 从时间戳获取中文周几
 * @param $timestamp
 * @return string
 */
function getWeekChineseNumber($timestamp){
    return mb_substr( "日一二三四五六", date("w", $timestamp), 1, "utf-8");
}

function getImageUrl($column){
    return startWith($column, 'http')? $column: getConfig('QINIU_UPLOAD.IMAGE_URL'). '.'. $column;
}

/**
 * 获取指定时间的本月起止时间
 * @param $time int
 * @return mixed 11
 */
function currentMonth($time)
{
    $info['start'] = strtotime(date( 'Y-m-1 00:00:00', $time ));
    $mdays         = date( 't', $time );
    $info['end']   = strtotime(date( 'Y-m-' . $mdays . ' 23:59:59', $time ));
    return $info;
}

/**
 * 获取指定时间的上个月起止时间
 * @param $time int
 * @return mixed 11
 */
function lastMonth($time)
{
    $datetime      = date('Ymd',$time);
    $info['start'] = strtotime(date("Y-m-1 00:00:00",strtotime("last months",strtotime($datetime))));
    $mdays         = date( 't', $time );
    $info['end']   = strtotime(date( 'Y-m-' . $mdays . ' 23:59:59', $info['start']));
    return $info;
}

/**
 * 获取指定时间下个月的起止时间（Y-m-d）
 * @param $time /int
 * @return array
 */
function nextMonth($time){
    $arr=getdate($time);
    if($arr['mon'] == 12){
        $year=$arr['year'] +1;
        $month=$arr['mon'] -11;
        $firstday=$year.'-0'.$month.'-01';
        $lastday=date('Y-m-d',strtotime("$firstday +1 month -1 day"));
    }else{
        $firstday=date('Y-m-01',strtotime(date('Y',$time).'-'.(date('m',$time)+1).'-01'));
        $lastday=date('Y-m-d',strtotime("$firstday +1 month -1 day"));
    }
    $info['start'] = $firstday;
    $info['end']   = $lastday;
    return $info;
}

/**
 * 获取指定时间上一年对应月份的起止时间
 * @param $time
 * @return mixed
 */
function lastYearMonth($time)
{
    $info['start'] = strtotime(date( 'Y-m-01 00:00:00', strtotime("-1 years",$time) ));
    $mdays         = date( 't', $time );
    $info['end']   = strtotime(date( 'Y-m-' . $mdays . ' 23:59:59',  strtotime("-1 years",$time) ));
    return $info;
}

/**
 * 获取指定时间的当年起止时间
 * @param $time int
 * @return mixed 11
 */
function currentYear($time)
{
    $info['start'] = strtotime(date( 'Y-01-01 00:00:00', $time ));
    $info['end']   = strtotime(date( 'Y-12-31 23:59:59', $time ));
    return $info;
}

/**
 * 获取指定时间的上一年起止时间
 * @param $time int
 * @return mixed 11
 */
function lastYear($time)
{
    $info['start'] = strtotime(date( 'Y-01-01 00:00:00', strtotime("-1 years",$time) ));
    $info['end']   = strtotime(date( 'Y-12-31 23:59:59', strtotime("-1 years",$time) ));
    return $info;
}

/**
 * 输入起止时间查询所有月份（"Y-m"格式）
 * @param $params
 * @param int $k
 * @return array
 */
function allMonth($params,$k=0)
{
    //先算月份，再算开始时间的月份开始/结束时间，再算结束时间的月份开始结束时间
    $mouth  = array();
    $start  = (date( 'Y-m', $params['startTime'] ));
    $end    = (date( 'Y-m', $params['endTime']));
    if($start <= $end){
        $mouth[$k] = $end;
        $params['endTime'] = strtotime(date( 'Y-m', strtotime("last months",$params['endTime'])));
        $mouth = array_merge($mouth,allMonth($params,$k+1));
    }
    return $mouth;
}


/**
 * 求百分比
 * @param $molecule int
 * @param $denominator int
 * @return int|string
 */
function percentage($molecule,$denominator)
{
    if(empty($molecule)){
        $info = 0;
    }elseif (empty($denominator)){
        $info = 0;
    }else{
//        $info = round($molecule/$denominator*100,2)."%";
        $info = round($molecule/$denominator,2);
    }
    return $info;
}

/**
 * 计算二维数组中某个字段不同的数量
 * @param $array
 * @param $field
 * @return int
 */
function count_field($array,$field)
{
    return count(array_unique(array_column($array,$field)));
}

