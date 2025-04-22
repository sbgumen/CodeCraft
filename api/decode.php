<?php
/**
 * CodeCraft - 解码API端点
 * 处理文本解码请求
 */

// 避免重复引入
if (!defined('API_INCLUDED')) {
    require_once __DIR__ . '/index.php';
}

// 引入高级加密函数
$advancedCryptoFile = __DIR__ . '/functions/advanced_crypto.php';
if (file_exists($advancedCryptoFile)) {
    require_once $advancedCryptoFile;
} else {
    error_log("高级加密函数文件不存在: " . $advancedCryptoFile);
}

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse(
        'METHOD_NOT_ALLOWED',
        '仅支持POST请求',
        405
    );
}

// 验证必需参数
if (!isset($_POST['text']) || !isset($_POST['algorithm'])) {
    sendErrorResponse(
        'INVALID_PARAMS',
        '缺少必需参数: text, algorithm'
    );
}

$text = $_POST['text'];
$algorithm = strtolower($_POST['algorithm']);

// 检查输入大小
checkInputSize($text);

// 检查算法支持
checkAlgorithmSupport($algorithm, 'decode');

// 获取额外选项
$options = [];
foreach ($_POST as $key => $value) {
    if ($key !== 'text' && $key !== 'algorithm') {
        $options[$key] = $value;
    }
}

// 根据算法执行相应的解码
try {
    $decodedText = '';
    $responseData = [
        'original' => $text,
        'algorithm' => $algorithm
    ];
    
    switch ($algorithm) {
        case 'base64':
            // 验证输入是有效的Base64字符串
            if (!is_valid_base64($text)) {
                sendErrorResponse(
                    'INVALID_INPUT',
                    '输入不是有效的Base64编码字符串'
                );
            }
            
            $decodedText = base64_decode_text($text);
            break;
            
        case 'base32':
            if (function_exists('base32_decode')) {
                $decodedText = base32_decode($text);
            } else {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    'Base32解码函数未实现'
                );
            }
            break;
            
        case 'url':
            $decodedText = urldecode($text);
            break;
            
        case 'html':
            $decodedText = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
            break;
            
        case 'hex':
            if (function_exists('hex_decode')) {
                $decodedText = hex_decode($text);
            } else {
                // 简单实现
                $decodedText = pack("H*", $text);
            }
            break;
            
        case 'binary':
            if (function_exists('binary_decode')) {
                $decodedText = binary_decode($text);
            } else {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    '二进制解码函数未实现'
                );
            }
            break;
            
        case 'morse':
            if (function_exists('morse_decode')) {
                $decodedText = morse_decode($text);
            } else {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    '摩斯密码解码函数未实现'
                );
            }
            break;
            
        case 'aes':
            if (!function_exists('aes_decrypt')) {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    'AES解密函数未实现'
                );
            }
            
            $key = isset($options['aes-key']) ? $options['aes-key'] : null;
            $iv = isset($options['aes-iv']) ? $options['aes-iv'] : null;
            $mode = isset($options['aes-mode']) ? $options['aes-mode'] : 'CBC';
            
            if (!$key) {
                sendErrorResponse(
                    'MISSING_PARAMETER',
                    'AES解密需要密钥'
                );
            }
            
            $decodedText = aes_decrypt($text, $key, $iv, $mode);
            $responseData['key'] = $key;
            $responseData['iv'] = $iv;
            $responseData['mode'] = $mode;
            break;
            
        case 'rsa':
            if (!function_exists('rsa_decrypt')) {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    'RSA解密函数未实现'
                );
            }
            
            $privateKey = isset($options['rsa-private-key']) ? $options['rsa-private-key'] : null;
            
            if (!$privateKey) {
                sendErrorResponse(
                    'MISSING_PARAMETER',
                    'RSA解密需要私钥'
                );
            }
            
            $decodedText = rsa_decrypt($text, $privateKey);
            break;
            
        case 'caesar':
            if (!function_exists('caesar_decrypt')) {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    '凯撒密码解密函数未实现'
                );
            }
            
            $shift = isset($options['caesar-shift']) ? intval($options['caesar-shift']) : 3;
            $decodedText = caesar_decrypt($text, $shift);
            $responseData['shift'] = $shift;
            break;
            
        case 'vigenere':
            if (!function_exists('vigenere_decrypt')) {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    '维吉尼亚密码解密函数未实现'
                );
            }
            
            $key = isset($options['vigenere-key']) ? $options['vigenere-key'] : null;
            
            if (!$key) {
                sendErrorResponse(
                    'MISSING_PARAMETER',
                    '维吉尼亚解密需要密钥'
                );
            }
            
            $decodedText = vigenere_decrypt($text, $key);
            $responseData['key'] = $key;
            break;
            
        case 'rot13':
            $decodedText = str_rot13($text);
            break;
            
        case 'jwt':
            if (!function_exists('jwt_decode')) {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    'JWT解码函数未实现'
                );
            }
            
            $secret = isset($options['jwt-secret']) ? $options['jwt-secret'] : null;
            
            if (!$secret) {
                sendErrorResponse(
                    'MISSING_PARAMETER',
                    'JWT解码需要签名密钥'
                );
            }
            
            $decodedResult = jwt_decode($text, $secret);
            
            if ($decodedResult === false) {
                sendErrorResponse(
                    'INVALID_JWT',
                    'JWT验证失败或令牌已过期'
                );
            }
            
            $decodedText = json_encode($decodedResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;
            
        case 'xml':
            if (!function_exists('xml_decode')) {
                // 简单实现
                $xml = simplexml_load_string($text);
                $decodedText = json_encode($xml, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                $decodedText = xml_decode($text, true);
                $decodedText = json_encode($decodedText, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'json':
            $decodedResult = json_decode($text, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendErrorResponse(
                    'INVALID_JSON',
                    'JSON解析错误: ' . json_last_error_msg()
                );
            }
            
            $decodedText = print_r($decodedResult, true);
            break;
            
        case 'yaml':
            if (!function_exists('yaml_decode')) {
                if (function_exists('yaml_parse')) {
                    $decodedResult = yaml_parse($text);
                    $decodedText = print_r($decodedResult, true);
                } else {
                    sendErrorResponse(
                        'FUNCTION_NOT_FOUND',
                        'YAML解析函数未实现，需要安装php-yaml扩展'
                    );
                }
            } else {
                $decodedText = yaml_decode($text);
                $decodedText = print_r($decodedText, true);
            }
            break;
            
        default:
            // 这里应该永远不会执行，因为之前的检查会拦截无效的算法
            sendErrorResponse(
                'ALGORITHM_NOT_SUPPORTED',
                '不支持的解码算法: ' . $algorithm
            );
    }
    
    // 发送成功响应
    $responseData['decoded'] = $decodedText;
    sendJsonResponse([
        'success' => true,
        'data' => $responseData
    ]);
    
} catch (Exception $e) {
    // 处理解码过程中的异常
    sendErrorResponse(
        'DECODE_ERROR',
        '解码过程中出错: ' . $e->getMessage()
    );
}