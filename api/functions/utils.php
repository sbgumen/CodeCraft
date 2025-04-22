<?php
/**
 * CodeCraft - 通用工具函数
 * 提供各种实用函数和帮助方法
 */

/**
 * 生成唯一标识符
 * @return string 唯一标识符
 */
function generate_unique_id() {
    return md5(uniqid(mt_rand(), true));
}

/**
 * 安全的获取请求数据
 * @param array $source 数据源 (如 $_GET, $_POST)
 * @param string $key 键名
 * @param mixed $default 默认值
 * @param bool $sanitize 是否净化输入
 * @return mixed 请求数据或默认值
 */
function safe_get_var($source, $key, $default = null, $sanitize = true) {
    $value = isset($source[$key]) ? $source[$key] : $default;
    
    if ($sanitize && is_string($value)) {
        $value = filter_var($value, FILTER_SANITIZE_STRING);
    }
    
    return $value;
}

/**
 * 记录错误日志
 * @param string $message 错误消息
 * @param string $level 错误级别
 */
function log_error($message, $level = 'ERROR') {
    $logDir = __DIR__ . '/../../logs';
    $logFile = $logDir . '/error.log';
    
    // 确保日志目录存在
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // 格式化日志条目
    $logEntry = sprintf(
        "[%s] [%s] %s\n",
        date('Y-m-d H:i:s'),
        $level,
        $message
    );
    
    // 写入日志文件
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * 检查字符串是否包含多字节字符
 * @param string $string 要检查的字符串
 * @return bool 是否包含多字节字符
 */
function has_multibyte_chars($string) {
    return mb_strlen($string, 'UTF-8') != strlen($string);
}

/**
 * 转换字符编码
 * @param string $string 要转换的字符串
 * @param string $to 目标编码
 * @param string $from 源编码
 * @return string 转换后的字符串
 */
function convert_encoding($string, $to = 'UTF-8', $from = 'auto') {
    if ($from === 'auto') {
        $from = mb_detect_encoding($string, 'UTF-8, ISO-8859-1, GBK, GB2312, BIG5', true);
    }
    
    if ($from === $to) {
        return $string;
    }
    
    return mb_convert_encoding($string, $to, $from);
}

/**
 * 获取当前服务器负载
 * @return float|null 服务器负载平均值或null
 */
function get_server_load() {
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        return $load[0];
    }
    
    return null;
}

/**
 * 转义用于JSON的字符串
 * @param string $string 要转义的字符串
 * @return string 转义后的字符串
 */
function escape_json_string($string) {
    $replacements = array(
        '\\' => '\\\\',
        '"' => '\\"',
        '/' => '\\/',
        "\b" => '\\b',
        "\f" => '\\f',
        "\n" => '\\n',
        "\r" => '\\r',
        "\t" => '\\t'
    );
    
    return str_replace(
        array_keys($replacements),
        array_values($replacements),
        $string
    );
}

/**
 * 执行带超时的函数调用
 * @param callable $callback 要执行的回调函数
 * @param int $timeout 超时时间（秒）
 * @param mixed $default 超时时返回的默认值
 * @return mixed 函数返回值或默认值
 */
function call_with_timeout($callback, $timeout = 5, $default = null) {
    // 设置超时处理器
    $handler = function() use ($timeout) {
        throw new Exception("执行超时（{$timeout}秒）");
    };
    
    // 设置超时警报
    $previous = set_error_handler(function() {}, E_WARNING);
    pcntl_signal(SIGALRM, $handler);
    pcntl_alarm($timeout);
    
    try {
        // 执行回调
        $result = call_user_func($callback);
        pcntl_alarm(0); // 取消警报
        return $result;
    } catch (Exception $e) {
        // 超时异常
        log_error("函数执行超时: " . $e->getMessage());
        return $default;
    } finally {
        // 恢复之前的错误处理器
        set_error_handler($previous);
    }
}

/**
 * 检测客户端IP地址
 * @return string 客户端IP地址
 */
function get_client_ip() {
    $ipAddress = '';
    
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipAddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
    }
    
    // 可能包含多个IP（代理链），只取第一个
    if (strpos($ipAddress, ',') !== false) {
        $ipAddresses = explode(',', $ipAddress);
        $ipAddress = trim($ipAddresses[0]);
    }
    
    return $ipAddress;
}

/**
 * 检查请求是否为AJAX请求
 * @return bool 是否为AJAX请求
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * 格式化文件大小
 * @param int $bytes 字节数
 * @param int $precision 精度
 * @return string 格式化后的文件大小
 */
function format_file_size($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}