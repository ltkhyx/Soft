<?php
// 核心数据获取逻辑

// 获取芯片型号
$chipname = trim(shell_exec("cat /etc/regname 2>&1"));

// 获取本机内网IP地址
$ipaddress = trim(shell_exec("hostname -I | awk '{print $1}'"));

// 获取本机外网地址，直接从 /etc/name 文件读取
$external_ip = trim(file_get_contents('/etc/name'));

// 执行Shell命令获取温度信息
$temp_raw = shell_exec("cat /sys/class/thermal/thermal_zone0/temp");
$temp = floatval($temp_raw) / 1000;
$temp = round($temp, 1); // 保留一位小数

// 获取启动时间并进行中文转换
$uptime_raw = trim(shell_exec("uptime -p"));
$uptime = '';
if (!empty($uptime_raw)) {
    // 移除 "up "
    $uptime_string = str_replace('up ', '', $uptime_raw);
    
    // 将英文单位替换成中文
    $uptime = str_replace([' day', ' days'], '天', $uptime_string);
    $uptime = str_replace([' hour', ' hours'], '小时', $uptime);
    $uptime = str_replace([' minute', ' minutes'], '分钟', $uptime);
}

// 获取CPU信息
$cpu_info = trim(shell_exec("lscpu | grep 'Model name' | cut -d ':' -f2 | xargs"));

// 获取内核版本
$kernel_version = trim(shell_exec("uname -r"));

// 获取系统版本
$os_version = trim(shell_exec("lsb_release -d | cut -f2"));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统信息</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            max-width: 600px;
            width: 100%;
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #1a73e8;
            text-align: center;
            margin-bottom: 25px;
            font-size: 24px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            flex: 1;
        }
        .info-value {
            font-family: monospace;
            font-size: 16px;
            color: #1a73e8;
            text-align: right;
            flex: 2;
            word-wrap: break-word;
            white-space: normal;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
                border-radius: 8px;
            }
            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .info-label {
                margin-bottom: 5px;
            }
            .info-value {
                text-align: left;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>设备信息</h1>
        <div class="info-item">
            <span class="info-label">芯片型号</span>
            <span class="info-value"><?php echo htmlspecialchars($chipname); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">CPU 信息</span>
            <span class="info-value"><?php echo htmlspecialchars($cpu_info); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">内核版本</span>
            <span class="info-value"><?php echo htmlspecialchars($kernel_version); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">系统版本</span>
            <span class="info-value"><?php echo htmlspecialchars($os_version); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">本机内网IP</span>
            <span class="info-value"><?php echo htmlspecialchars($ipaddress); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">本机外网地址</span>
            <span class="info-value"><?php echo htmlspecialchars($external_ip); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">当前设备温度</span>
            <span class="info-value"><?php echo htmlspecialchars($temp . ' °C'); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">启动时间</span>
            <span class="info-value"><?php echo htmlspecialchars($uptime); ?></span>
        </div>
    </div>
</body>
</html>