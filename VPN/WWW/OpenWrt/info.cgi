#!/bin/sh

echo "Content-type: text/html"
echo ""

echo "<!DOCTYPE html>"
echo "<html lang='zh-CN'>"
echo "<head>"
echo "<meta charset='UTF-8'>"
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>"
echo "<title>本机信息</title>"
echo "<style>"
echo "body { font-family: Arial, sans-serif; background:#f5f5f5; text-align:center; padding:20px; }"
echo ".card { background:white; padding:20px; margin:15px auto; border-radius:12px; max-width:400px; box-shadow:0 4px 8px rgba(0,0,0,0.1); }"
echo "p { margin:8px 0; font-size:16px; }"
echo "button { margin-top:15px; padding:8px 16px; font-size:16px; border:none; border-radius:6px; background:#4285f4; color:white; cursor:pointer; }"
echo "button:hover { background:#2a63c4; }"
echo "a { text-decoration: none; color: inherit; }"
echo "a:hover { color: blue; text-decoration: underline; }"
echo "</style>"
echo "</head>"
echo "<body>"
echo "<div class='card'>"
echo "<h1>设备信息</h1>"

# 系统版本
SYSTEM_VERSION=$(grep DISTRIB_DESCRIPTION /etc/openwrt_release 2>/dev/null | cut -d\' -f2)
[ -z "$SYSTEM_VERSION" ] && SYSTEM_VERSION="N/A"

# 内核版本
KERNEL_VERSION=$(uname -r)

# 内网 IP
IP=$(ip -4 addr show scope global | grep inet | awk '{print $2}' | cut -d/ -f1 | head -n1)
[ -z "$IP" ] && IP="N/A"

# 外网地址
EXTERNAL=$(cat /etc/name 2>/dev/null)
[ -z "$EXTERNAL" ] && EXTERNAL="N/A"

# CPU 温度
if [ -f /sys/class/thermal/thermal_zone0/temp ]; then
    TEMP=$(awk '{printf "%.1f°C\n",$1/1000}' /sys/class/thermal/thermal_zone0/temp)
else
    TEMP="N/A"
fi

# 启动时间
UPTIME=$(awk '{print int($1/86400)"天 "int($1%86400/3600)"小时 "int($1%3600/60)"分"}' /proc/uptime)

# 网卡流量（字节）
if [ -f /sys/class/net/eth0/statistics/rx_bytes ]; then
    RX_BYTES=$(cat /sys/class/net/eth0/statistics/rx_bytes)
    TX_BYTES=$(cat /sys/class/net/eth0/statistics/tx_bytes)
    
    RX_MB=$(awk "BEGIN {printf \"%.2f\", $RX_BYTES/1024/1024}")
    TX_MB=$(awk "BEGIN {printf \"%.2f\", $TX_BYTES/1024/1024}")
    
    # 判断是否超过 1024 MB，转换为 GB
    if [ "$(awk "BEGIN{print ($RX_MB>=1024)?1:0}")" -eq 1 ]; then
        RX_DISPLAY=$(awk "BEGIN{printf \"%.2f GB\", $RX_MB/1024}")
    else
        RX_DISPLAY="$RX_MB MB"
    fi

    if [ "$(awk "BEGIN{print ($TX_MB>=1024)?1:0}")" -eq 1 ]; then
        TX_DISPLAY=$(awk "BEGIN{printf \"%.2f GB\", $TX_MB/1024}")
    else
        TX_DISPLAY="$TX_MB MB"
    fi
else
    RX_DISPLAY="N/A"
    TX_DISPLAY="N/A"
fi
# 系统存储
STORAGE=$(df -h / | awk 'NR==2{print $5 " of " $2}')

# 系统内存
# ===== 内存（用 MemAvailable 计算真实占用）=====
MEM_TOTAL_KB=$(awk '/MemTotal:/ {print $2}' /proc/meminfo)
MEM_AVAIL_KB=$(awk '/MemAvailable:/ {print $2}' /proc/meminfo)

# 兼容旧内核：没有 MemAvailable 时，用 Free+Buffers+Cached 近似
if [ -z "$MEM_AVAIL_KB" ]; then
  MEM_AVAIL_KB=$(awk '
    /MemFree:/  {free=$2}
    /Buffers:/  {buf=$2}
    /^Cached:/  {cache=$2}
    END {print free+buf+cache}
  ' /proc/meminfo)
fi

MEM_USED_KB=$((MEM_TOTAL_KB - MEM_AVAIL_KB))
MEM_PCT=$(awk -v u="$MEM_USED_KB" -v t="$MEM_TOTAL_KB" 'BEGIN{printf("%.1f", (u*100)/t)}')

# 总内存转人类可读：>=1GiB 显示 GiB，否则 MiB
if [ "$MEM_TOTAL_KB" -ge 1048576 ]; then
  MEM_TOTAL_HUMAN=$(awk -v k="$MEM_TOTAL_KB" 'BEGIN{printf("%.1fG", k/1048576)}')
else
  MEM_TOTAL_HUMAN=$(awk -v k="$MEM_TOTAL_KB" 'BEGIN{printf("%dM", k/1024)}')
fi


# 输出信息
#echo "<p style='color:red;'><b>本机 IP:</b> $IP</p>";
#echo "<p style='color:red;'><b>本地视频:</b> <a href='http://$IP:5244' target='_blank'>点击这里打开</a></p>";
echo "<p style='color:red;'><b>本机 IP:</b> <a href='http://$IP:5244' target='_blank'>$IP</a></p>";
#echo "<p><b>系统版本:</b> $SYSTEM_VERSION</p>"
echo "<p><b>内核版本:</b> $KERNEL_VERSION</p>"
echo "<p><b>CPU 温度:</b> $TEMP</p>"
echo "<p style='color:green;'><b>系统存储:</b> $STORAGE</p>"
echo "<p style='color:purple;'><b>系统内存:</b> ${MEM_PCT}% of ${MEM_TOTAL_HUMAN}</p>"
echo "<p><b>本次接收数据:</b> $RX_DISPLAY</p>"
echo "<p><b>本次发送数据:</b> $TX_DISPLAY</p>"
#echo "<p><b>设备网址:</b> $EXTERNAL</p>"
echo "<p><b>运行时间:</b> $UPTIME</p>"
echo "<p style='color:red;'><b>设备网址:</b> <a href='http://$EXTERNAL' target='_blank'>$EXTERNAL</a></p>";

echo "<button onclick='location.reload()'>刷新信息</button>"
echo "</div>"
echo "</body>"
echo "</html>"
