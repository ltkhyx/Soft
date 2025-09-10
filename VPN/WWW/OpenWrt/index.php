<?php
header("Content-Type: text/html; charset=utf-8");

// 启动时间
$uptime = @file_get_contents("/proc/uptime");
if ($uptime !== false) {
    $uptime_sec = (int)explode(' ', $uptime)[0];
    $days = (int)($uptime_sec / 86400);
    $hours = (int)(($uptime_sec % 86400) / 3600);
    $minutes = (int)(($uptime_sec % 3600) / 60);
    $uptime_str = "{$days}天 {$hours}小时 {$minutes}分";
} else { $uptime_str = "N/A"; }

// CPU温度
$cpu_temp_file = "/sys/class/thermal/thermal_zone0/temp";
if (file_exists($cpu_temp_file)) {
    $temp = round(file_get_contents($cpu_temp_file)/1000,1);
} else {
    $temp = "N/A";
}

// 内存信息
$mem_total_kb = $mem_avail_kb = 0;
$meminfo = @file("/proc/meminfo");
foreach($meminfo as $line){
    if (strpos($line,"MemTotal:")===0) $mem_total_kb = (int)preg_replace('/\D/', '', $line);
    if (strpos($line,"MemAvailable:")===0) $mem_avail_kb = (int)preg_replace('/\D/', '', $line);
}
// 兼容旧内核
if($mem_avail_kb==0){
    $free=$buf=$cache=0;
    foreach($meminfo as $line){
        if(strpos($line,"MemFree:")===0) $free=(int)preg_replace('/\D/', '', $line);
        if(strpos($line,"Buffers:")===0) $buf=(int)preg_replace('/\D/', '', $line);
        if(strpos($line,"Cached:")===0) $cache=(int)preg_replace('/\D/', '', $line);
    }
    $mem_avail_kb = $free+$buf+$cache;
}
$mem_used_kb = $mem_total_kb - $mem_avail_kb;
$mem_pct = $mem_total_kb ? round($mem_used_kb*100/$mem_total_kb,1) : 0;
$mem_total_human = $mem_total_kb>=1048576 ? round($mem_total_kb/1024/1024,1)."G" : round($mem_total_kb/1024)."M";
$mem_used_human  = $mem_used_kb>=1048576 ? round($mem_used_kb/1024/1024,1)."G" : round($mem_used_kb/1024)."M";

// br-lan 网络
$rx_bytes = @file_get_contents("/sys/class/net/br-lan/statistics/rx_bytes") ?: 0;
$tx_bytes = @file_get_contents("/sys/class/net/br-lan/statistics/tx_bytes") ?: 0;
function human_bytes($b){
    if($b>=1073741824) return round($b/1073741824,1)."G";
    if($b>=1048576) return round($b/1048576,1)."M";
    if($b>=1024) return round($b/1024,1)."K";
    return $b."B";
}

/// br-lan IP
$ip = trim(shell_exec("ip -4 addr show br-lan | awk '/inet / {print \$2}' | cut -d/ -f1"));
if(!$ip){ // 兼容旧系统
    $ip = trim(shell_exec("ifconfig br-lan | awk '/inet / {print \$2}'"));
}
$ip = $ip ?: "N/A";

// 设备地址
$external = trim(@file_get_contents('/etc/name'));
if(!$external) $external = "N/A";

// 硬盘信息
$disks = [];
$df = shell_exec("df -h --output=source,size,used,pcent,target -x tmpfs -x devtmpfs");
$lines = explode("\n", trim($df));
for ($i=1;$i<count($lines);$i++){
    $parts = preg_split('/\s+/', $lines[$i]);
    if(count($parts)==5 && $parts[4]!="/tmp" && $parts[4]!="/dev") $disks[] = $parts;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>设备信息</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;margin:0;padding:0;color:#333;}
.container{max-width:600px;margin:20px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);}
h1{text-align:center;font-size:24px;color:#007bff;}
table{width:100%;border-collapse:collapse;margin:15px 0;}
th,td{padding:10px;text-align:left;border-bottom:1px solid #ddd;}
th{background:#f9f9f9;}
.progress-bar{height:16px;background:#ddd;border-radius:8px;overflow:hidden;}
.progress-bar-inner{height:100%;text-align:right;padding-right:5px;color:#fff;line-height:16px;border-radius:8px;}
.mem-bar{background:#28a745;}
.temp-bar{background:#dc3545;}
button{display:block;margin:20px auto;padding:10px 20px;font-size:16px;border:none;border-radius:5px;background:#007bff;color:#fff;cursor:pointer;}
button:hover{background:#0056b3;}
@media(max-width:480px){.container{padding:15px;}h1{font-size:20px;}}

.ip-link {
    color: #007bff;
    text-decoration: none;
    transition: color 0.3s;
}
.ip-link:hover {
    color: #0056b3; /* 鼠标悬停变色 */
}
.ip-link:active {
    color: #ff6600; /* 点击时变色 */
}

</style>
</head>
<body>
<div class="container">
<h1>设备信息</h1>
<table>
<tr>
    <th>IP 地址</th>
    <td>
        <a href="http://<?php echo $ip; ?>:5244" target="_blank" class="ip-link">
            <?php echo $ip; ?>
        </a>
    </td>
</tr>

<tr><th>启动时间</th><td><?php echo $uptime_str; ?></td></tr>

<tr><th>CPU 温度</th>
<td>
<div class="progress-bar">
    <div class="progress-bar-inner temp-bar" style="width:<?php echo min($temp,100); ?>%;"><?php echo $temp; ?>°C</div>
</div>
</td></tr>

<tr><th>内存使用</th>
<td>
<div class="progress-bar">
    <div class="progress-bar-inner mem-bar" style="width:<?php echo $mem_pct; ?>%;"><?php echo "$mem_used_human / $mem_total_human ($mem_pct%)"; ?></div>
</div>
</td></tr>

<tr><th>网卡流量</th>
<td>接收: <?php echo human_bytes((int)$rx_bytes); ?>, 发送: <?php echo human_bytes((int)$tx_bytes); ?></td></tr>



<tr>
    <th>设备地址</th>
    <td>
        <a href="http://<?php echo $external; ?>" target="_blank" class="ip-link">
            <?php echo $external; ?>
        </a>
    </td>
</tr>

<?php foreach($disks as $d): ?>
<tr><th>硬盘 <?php echo $d[0]; ?></th><td><?php echo "{$d[2]} / {$d[1]} ({$d[3]})"; ?></td></tr>
<?php endforeach; ?>

</table>
<button onclick="location.reload()">刷新</button>
<a href="/cgi-bin/luci" class="button">登录配置界面</a>
</div>
</body>
</html>
