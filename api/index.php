<?php
/**index.php
 * CodeCraft - API入口点
 * 处理所有API请求的路由和通用功能
 */

// 错误处理设置 - 捕获PHP错误并转换为JSON响应
function errorHandler($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'PHP_ERROR',
            'message' => $errstr,
            'file' => basename($errfile),
            'line' => $errline
        ]
    ]);
    exit;
}
set_error_handler('errorHandler', E_ALL);

// 设置头信息
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// 定义一个标志，防止循环引用
if (!defined('API_INCLUDED')) {
    define('API_INCLUDED', true);
}

// 定义必要的函数文件路径
$baseDir = __DIR__;
$baseDir = rtrim($baseDir, '/\\'); // 确保路径末尾没有斜杠
$functionsDir = $baseDir . '/functions';
$base64FunctionsFile = $functionsDir . '/base64_functions.php';
$md5FunctionsFile = $functionsDir . '/md5_functions.php';
$utilsFile = $functionsDir . '/utils.php';

// 检查目录是否存在
if (!is_dir($functionsDir)) {
    sendErrorResponse('CONFIG_ERROR', 'Functions目录不存在: ' . $functionsDir);
}

// 引入必要的函数文件 - 检查文件是否存在
if (file_exists($base64FunctionsFile)) {
    require_once $base64FunctionsFile;
} else {
    sendErrorResponse('CONFIG_ERROR', 'Base64函数文件不存在: ' . $base64FunctionsFile);
}

if (file_exists($md5FunctionsFile)) {
    require_once $md5FunctionsFile;
} else {
    sendErrorResponse('CONFIG_ERROR', 'MD5函数文件不存在: ' . $md5FunctionsFile);
}

if (file_exists($utilsFile)) {
    require_once $utilsFile;
} else {
    sendErrorResponse('CONFIG_ERROR', 'Utils函数文件不存在: ' . $utilsFile);
}

/**
 * 发送JSON响应
 * @param array $data 响应数据
 * @param int $statusCode HTTP状态码
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 发送错误响应
 * @param string $code 错误代码
 * @param string $message 错误消息
 * @param int $statusCode HTTP状态码
 */
function sendErrorResponse($code, $message, $statusCode = 400) {
    sendJsonResponse([
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message
        ]
    ], $statusCode);
}

/**
 * 检查和限制请求频率
 * 简单的速率限制实现，生产环境中应该使用更健壮的解决方案（如Redis）
 */
function checkRateLimit() {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $timestamp = time();
        $rateLimitFile = __DIR__ . '/rate_limits.json';
        
        // 创建或加载速率限制数据
        if (!file_exists($rateLimitFile)) {
            file_put_contents($rateLimitFile, json_encode([]));
        }
        
        if (!is_writable($rateLimitFile)) {
            // 如果文件不可写，记录警告但允许请求继续
            error_log("警告: rate_limits.json 文件不可写");
            return;
        }
        
        $rateLimitsContent = file_get_contents($rateLimitFile);
        if (empty($rateLimitsContent)) {
            $rateLimits = [];
        } else {
            $rateLimits = json_decode($rateLimitsContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // JSON解析错误，重置文件
                $rateLimits = [];
                file_put_contents($rateLimitFile, json_encode([]));
            }
        }
        
        // 清理过期条目
        foreach ($rateLimits as $ip => $data) {
            if ($timestamp - $data['timestamp'] > 60) { // 60秒窗口
                unset($rateLimits[$ip]);
            }
        }
        
        // 检查当前IP的请求计数
        if (isset($rateLimits[$ipAddress])) {
            $currentCount = $rateLimits[$ipAddress]['count'];
            $lastTimestamp = $rateLimits[$ipAddress]['timestamp'];
            
            // 如果在同一分钟内
            if ($timestamp - $lastTimestamp < 60) {
                if ($currentCount >= 60) { // 每分钟60个请求的限制
                    sendErrorResponse(
                        'RATE_LIMIT_EXCEEDED',
                        '超过API请求限制，请稍后再试',
                        429
                    );
                }
                
                // 更新计数
                $rateLimits[$ipAddress]['count']++;
            } else {
                // 重置计数
                $rateLimits[$ipAddress] = [
                    'count' => 1,
                    'timestamp' => $timestamp
                ];
            }
        } else {
            // 新的IP地址
            $rateLimits[$ipAddress] = [
                'count' => 1,
                'timestamp' => $timestamp
            ];
        }
        
        // 保存更新后的速率限制数据
        file_put_contents($rateLimitFile, json_encode($rateLimits));
    } catch (Exception $e) {
        // 速率限制失败不应阻止API功能
        error_log("速率限制检查失败: " . $e->getMessage());
    }
}

/**
 * 检查输入大小限制
 * @param string $input 输入数据
 * @param int $maxSize 最大大小（字节）
 */
function checkInputSize($input, $maxSize = 1048576) { // 默认1MB
    if (strlen($input) > $maxSize) {
        sendErrorResponse(
            'INPUT_TOO_LARGE',
            '输入数据过大，请减少数据量',
            413
        );
    }
}

/**
 * 支持的算法列表
 */
function getSupportedAlgorithms() {
    return [
        'encode' => [
            'base64', 'base32', 'md5', 'sha1', 'sha256', 'sha512', 
            'url', 'html', 'hex', 'binary', 'morse', 'aes', 'rsa', 
            'caesar', 'vigenere', 'rot13', 'jwt', 'xml', 'json', 'yaml'
        ],
        'decode' => [
            'base64', 'base32', 'url', 'html', 'hex', 'binary', 'morse', 
            'aes', 'rsa', 'caesar', 'vigenere', 'rot13', 'jwt', 'xml', 'json', 'yaml'
        ],
        'file_encode' => [
            'base64', 'hex', 'binary', 'aes', 'rsa', 'caesar', 'vigenere', 'rot13'
        ],
        'file_decode' => [
            'base64', 'hex', 'binary', 'aes', 'rsa', 'caesar', 'vigenere', 'rot13'
        ]
    ];
}

/**
 * 检查算法是否支持
 * @param string $algorithm 算法名称
 * @param string $mode 模式 ('encode' 或 'decode')
 */
function checkAlgorithmSupport($algorithm, $mode = 'encode') {
    $supportedAlgorithms = getSupportedAlgorithms();
    
    if (!in_array($algorithm, $supportedAlgorithms[$mode])) {
        sendErrorResponse(
            'ALGORITHM_NOT_SUPPORTED',
            sprintf('不支持的%s算法: %s', $mode === 'encode' ? '编码' : '解码', $algorithm)
        );
    }
}

try {
    // 应用速率限制
    checkRateLimit();
    
    // 只有在直接访问此文件时才执行以下操作
    if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
        // 处理不同的操作
        $action = isset($_GET['action']) ? $_GET['action'] : '';
    
        switch ($action) {
            case 'algorithms':
                // 返回支持的算法列表
                sendJsonResponse([
                    'success' => true,
                    'data' => getSupportedAlgorithms()
                ]);
                break;
                
            case 'status':
                // 返回API状态信息
                sendJsonResponse([
                    'success' => true,
                    'data' => [
                        'status' => 'online',
                        'version' => '1.0.0',
                        'timestamp' => time()
                    ]
                ]);
                break;
                
            default:
                // 默认情况下返回API信息，而不是重定向
                sendJsonResponse([
                    'success' => true,
                    'data' => [
                        'name' => 'CodeCraft API',
                        'version' => '1.0.0',
                        'endpoints' => [
                            'encode' => '/api/encode.php',
                            'decode' => '/api/decode.php'
                        ]
                    ]
                ]);
        }
    }
} catch (Exception $e) {
    // 捕获所有异常并返回JSON格式的错误信息
    sendErrorResponse('SERVER_ERROR', $e->getMessage(), 500);
}