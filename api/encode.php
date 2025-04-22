<?php
/**
 * CodeCraft - 编码API端点
 * 处理文本编码请求
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
checkAlgorithmSupport($algorithm, 'encode');

// 获取额外选项
$options = [];
foreach ($_POST as $key => $value) {
    if ($key !== 'text' && $key !== 'algorithm') {
        $options[$key] = $value;
    }
}

// 根据算法执行相应的编码
try {
    $encodedText = '';
    $responseData = [
        'original' => $text,
        'algorithm' => $algorithm
    ];
    
    switch ($algorithm) {
        case 'base64':
            $encodedText = base64_encode_text($text);
            break;
            
        case 'base32':
            if (function_exists('base32_encode')) {
                $encodedText = base32_encode($text);
            } else {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    'Base32编码函数未实现'
                );
            }
            break;
            
        case 'md5':
            $encodedText = md5_hash($text);
            break;
            
        case 'sha1':
            $encodedText = sha1($text);
            break;
            
        case 'sha256':
            $encodedText = hash('sha256', $text);
            break;
            
        case 'sha512':
            $encodedText = hash('sha512', $text);
            break;
            
        case 'url':
            $encodedText = urlencode($text);
            break;
            
        case 'html':
            $encodedText = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5);
            break;
            
        case 'hex':
            if (function_exists('hex_encode')) {
                $encodedText = hex_encode($text);
            } else {
                // 简单实现
                $encodedText = bin2hex($text);
            }
            break;
            
        case 'binary':
            if (function_exists('binary_encode')) {
                $encodedText = binary_encode($text);
            } else {
                // 简单实现
                $encodedText = '';
                for ($i = 0; $i < strlen($text); $i++) {
                    $bin = decbin(ord($text[$i]));
                    $encodedText .= str_pad($bin, 8, '0', STR_PAD_LEFT) . ' ';
                }
                $encodedText = trim($encodedText);
            }
            break;
            
        case 'morse':
            if (function_exists('morse_encode')) {
                $encodedText = morse_encode($text);
            } else {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    '摩斯密码编码函数未实现'
                );
            }
            break;
            
        case 'aes':
            if (!function_exists('aes_encrypt')) {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    'AES加密函数未实现'
                );
            }
            
            $key = isset($options['aes-key']) ? $options['aes-key'] : null;
            $iv = isset($options['aes-iv']) ? $options['aes-iv'] : null;
            $mode = isset($options['aes-mode']) ? $options['aes-mode'] : 'CBC';
            
            $result = aes_encrypt($text, $key, $iv, $mode);
            $encodedText = $result['encrypted'];
            
            // 添加密钥信息到响应
            $responseData['key'] = $result['key'];
            $responseData['iv'] = $result['iv'];
            $responseData['mode'] = $result['mode'];
            break;
            
        case 'rsa':
            if (!function_exists('rsa_encrypt') || !function_exists('rsa_generate_keys')) {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    'RSA加密函数未实现'
                );
            }
            
            $publicKey = isset($options['rsa-public-key']) ? $options['rsa-public-key'] : null;
            $keySize = isset($options['rsa-key-size']) ? intval($options['rsa-key-size']) : 2048;
            
            // 如果没有提供公钥，生成一个新的密钥对
            if (!$publicKey) {
                $keyPair = rsa_generate_keys($keySize);
                $publicKey = $keyPair['publicKey'];
                $responseData['publicKey'] = $publicKey;
                $responseData['privateKey'] = $keyPair['privateKey'];
            }
            
            $encodedText = rsa_encrypt($text, $publicKey);
            break;
            
        case 'caesar':
            if (!function_exists('caesar_encrypt')) {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    '凯撒密码加密函数未实现'
                );
            }
            
            $shift = isset($options['caesar-shift']) ? intval($options['caesar-shift']) : 3;
            $encodedText = caesar_encrypt($text, $shift);
            $responseData['shift'] = $shift;
            break;
            
        case 'vigenere':
            if (!function_exists('vigenere_encrypt')) {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    '维吉尼亚密码加密函数未实现'
                );
            }
            
            $key = isset($options['vigenere-key']) ? $options['vigenere-key'] : null;
            
            if (!$key) {
                // 生成随机密钥
                $key = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
            }
            
            $encodedText = vigenere_encrypt($text, $key);
            $responseData['key'] = $key;
            break;
            
        case 'rot13':
            $encodedText = str_rot13($text);
            break;
            
        case 'jwt':
            if (!function_exists('jwt_encode')) {
                sendErrorResponse(
                    'FUNCTION_NOT_FOUND',
                    'JWT编码函数未实现'
                );
            }
            
            $secret = isset($options['jwt-secret']) ? $options['jwt-secret'] : null;
            $algorithm = isset($options['jwt-algorithm']) ? $options['jwt-algorithm'] : 'HS256';
            
            if (!$secret) {
                sendErrorResponse(
                    'MISSING_PARAMETER',
                    'JWT编码需要签名密钥'
                );
            }
            
            // 尝试解析有效载荷为JSON
            $payload = json_decode($text, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // 如果不是有效的JSON，则将文本作为字符串处理
                $payload = ['data' => $text, 'iat' => time()];
            }
            
            $encodedText = jwt_encode($payload, $secret, $algorithm);
            break;
            
        case 'xml':
            if (!function_exists('xml_encode')) {
                // 尝试将文本解析为JSON
                $data = json_decode($text, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // 如果不是有效的JSON，则将文本作为字符串处理
                    $data = $text;
                }
                
                // 使用PHP内置的DOMDocument类
                $xml = new DOMDocument('1.0', 'UTF-8');
                $xml->formatOutput = true;
                
                // 创建根元素
                $root = $xml->createElement('root');
                $xml->appendChild($root);
                
                // 递归创建XML元素的函数
                $createXmlElement = function ($parent, $name, $value) use (&$createXmlElement, $xml) {
                    if (is_array($value)) {
                        $element = $xml->createElement($name);
                        $parent->appendChild($element);
                        
                        foreach ($value as $childName => $childValue) {
                            // 如果键是数字，使用item作为元素名
                            if (is_numeric($childName)) {
                                $childName = 'item';
                            }
                            
                            $createXmlElement($element, $childName, $childValue);
                        }
                    } else {
                        $element = $xml->createElement($name);
                        $parent->appendChild($element);
                        
                        if (is_bool($value)) {
                            $value = $value ? 'true' : 'false';
                        } elseif (is_null($value)) {
                            $value = '';
                        }
                        
                        $textNode = $xml->createTextNode((string) $value);
                        $element->appendChild($textNode);
                    }
                };
                
                // 处理数据
                if (is_array($data)) {
                    foreach ($data as $name => $value) {
                        // 如果键是数字，使用item作为元素名
                        if (is_numeric($name)) {
                            $name = 'item';
                        }
                        
                        $createXmlElement($root, $name, $value);
                    }
                } else {
                    $textNode = $xml->createTextNode((string) $data);
                    $root->appendChild($textNode);
                }
                
                $encodedText = $xml->saveXML();
            } else {
                $encodedText = xml_encode($text);
            }
            break;
            
        case 'json':
            // 尝试将文本解析为PHP数组/对象
            $data = null;
            
            // 尝试作为XML解析
            if (substr(trim($text), 0, 1) === '<' && substr(trim($text), -1) === '>') {
                libxml_use_internal_errors(true);
                $xml = simplexml_load_string($text);
                if ($xml !== false) {
                    $data = json_decode(json_encode($xml), true);
                }
                libxml_clear_errors();
            }
            
            // 如果XML解析失败，尝试其他格式
            if ($data === null) {
                // 尝试作为查询字符串解析
                if (strpos($text, '=') !== false) {
                    parse_str($text, $data);
                } else {
                    // 假设它是一个简单的文本
                    $data = $text;
                }
            }
            
            $encodedText = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if ($encodedText === false) {
                sendErrorResponse(
                    'JSON_ENCODE_ERROR',
                    'JSON编码错误: ' . json_last_error_msg()
                );
            }
            break;
            
        case 'yaml':
            if (!function_exists('yaml_encode')) {
                if (function_exists('yaml_emit')) {
                    // 尝试将文本解析为PHP数组/对象
                    $data = null;
                    
                    // 尝试作为JSON解析
                    $data = json_decode($text, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // 如果不是有效的JSON，则将文本作为字符串处理
                        $data = $text;
                    }
                    
                    $encodedText = yaml_emit($data, YAML_UTF8_ENCODING);
                } else {
                    sendErrorResponse(
                        'FUNCTION_NOT_FOUND',
                        'YAML编码函数未实现，需要安装php-yaml扩展'
                    );
                }
            } else {
                $encodedText = yaml_encode($text);
            }
            break;
            
        default:
            // 这里应该永远不会执行，因为之前的检查会拦截无效的算法
            sendErrorResponse(
                'ALGORITHM_NOT_SUPPORTED',
                '不支持的编码算法: ' . $algorithm
            );
    }
    
    // 发送成功响应
    $responseData['encoded'] = $encodedText;
    sendJsonResponse([
        'success' => true,
        'data' => $responseData
    ]);
    
} catch (Exception $e) {
    // 处理编码过程中的异常
    sendErrorResponse(
        'ENCODE_ERROR',
        '编码过程中出错: ' . $e->getMessage()
    );
}