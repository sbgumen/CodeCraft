<?php
/**
 * CodeCraft - 文件编码API端点
 * 处理文件编码请求
 */

// 错误处理设置 - 捕获PHP错误并转换为JSON响应
function fileErrorHandler($errno, $errstr, $errfile, $errline) {
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
set_error_handler('fileErrorHandler', E_ALL);

// 避免重复引入
if (!defined('API_INCLUDED')) {
    require_once __DIR__ . '/index.php';
}

// 引入高级加密函数
$advancedCryptoFile = __DIR__ . '/functions/advanced_crypto.php';
if (file_exists($advancedCryptoFile)) {
    require_once $advancedCryptoFile;
} else {
    sendErrorResponse(
        'CONFIG_ERROR',
        '高级加密函数文件不存在: ' . $advancedCryptoFile
    );
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
if (!isset($_FILES['file']) || !isset($_POST['algorithm'])) {
    sendErrorResponse(
        'INVALID_PARAMS',
        '缺少必需参数: file, algorithm'
    );
}

$file = $_FILES['file'];
$algorithm = strtolower($_POST['algorithm']);
$processOption = isset($_POST['processOption']) ? $_POST['processOption'] : 'whole';

// 检查上传是否成功
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = '文件上传失败: ';
    
    switch ($file['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errorMessage .= '文件大小超过限制';
            break;
        case UPLOAD_ERR_PARTIAL:
            $errorMessage .= '文件只有部分被上传';
            break;
        case UPLOAD_ERR_NO_FILE:
            $errorMessage .= '没有文件被上传';
            break;
        default:
            $errorMessage .= '未知错误 (代码: ' . $file['error'] . ')';
    }
    
    sendErrorResponse('FILE_UPLOAD_ERROR', $errorMessage);
}

// 检查文件大小 (限制为10MB)
$maxFileSize = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $maxFileSize) {
    sendErrorResponse(
        'FILE_TOO_LARGE',
        '文件过大，最大限制为10MB'
    );
}

// 创建上传目录
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        sendErrorResponse(
            'DIRECTORY_CREATE_ERROR',
            '无法创建上传目录'
        );
    }
}

// 创建输出目录
$outputDir = __DIR__ . '/../outputs/';
if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0755, true)) {
        sendErrorResponse(
            'DIRECTORY_CREATE_ERROR',
            '无法创建输出目录'
        );
    }
}

// 生成唯一文件名
$fileId = uniqid('file_', true);
$uploadedFilePath = $uploadDir . $fileId . '_' . basename($file['name']);
$outputFilePath = $outputDir . $fileId . '_' . $algorithm . '_' . basename($file['name']);

// 移动上传的文件
if (!move_uploaded_file($file['tmp_name'], $uploadedFilePath)) {
    sendErrorResponse(
        'FILE_MOVE_ERROR',
        '无法移动上传的文件'
    );
}

// 获取支持的文件处理算法
$supportedAlgorithms = getSupportedAlgorithms();
if (!in_array($algorithm, $supportedAlgorithms['file_encode'])) {
    // 删除上传的文件
    @unlink($uploadedFilePath);
    
    sendErrorResponse(
        'ALGORITHM_NOT_SUPPORTED',
        '不支持的文件编码算法: ' . $algorithm
    );
}

// 获取额外选项
$options = [];
foreach ($_POST as $key => $value) {
    if ($key !== 'algorithm' && $key !== 'processOption') {
        $options[$key] = $value;
    }
}

// 处理文件编码
try {
    $fileContent = file_get_contents($uploadedFilePath);
    if ($fileContent === false) {
        throw new Exception('无法读取上传的文件: ' . $uploadedFilePath);
    }
    
    $encryptionResult = null;
    $resultData = [
        'originalFileName' => $file['name'],
        'algorithm' => $algorithm
    ];
    
    // 根据算法和处理选项执行相应的编码
    switch ($algorithm) {
        case 'base64':
            $encoded = base64_encode($fileContent);
            if (file_put_contents($outputFilePath, $encoded) === false) {
                throw new Exception('无法写入输出文件: ' . $outputFilePath);
            }
            break;
            
        case 'hex':
            $encoded = bin2hex($fileContent);
            if (file_put_contents($outputFilePath, $encoded) === false) {
                throw new Exception('无法写入输出文件: ' . $outputFilePath);
            }
            break;
            
        case 'binary':
            $encoded = '';
            for ($i = 0; $i < strlen($fileContent); $i++) {
                $bin = decbin(ord($fileContent[$i]));
                $encoded .= str_pad($bin, 8, '0', STR_PAD_LEFT) . ' ';
            }
            if (file_put_contents($outputFilePath, $encoded) === false) {
                throw new Exception('无法写入输出文件: ' . $outputFilePath);
            }
            break;
            
        case 'aes':
            // 为AES加密生成密钥和IV
            $key = isset($options['aes-key']) ? $options['aes-key'] : bin2hex(random_bytes(16)); // 256位密钥
            $iv = isset($options['aes-iv']) ? $options['aes-iv'] : bin2hex(random_bytes(8)); // 128位IV
            $mode = isset($options['aes-mode']) ? $options['aes-mode'] : 'CBC';
            
            // 使用AES加密
            $encryptionResult = aes_encrypt($fileContent, $key, $iv, $mode);
            if (file_put_contents($outputFilePath, $encryptionResult['encrypted']) === false) {
                throw new Exception('无法写入输出文件: ' . $outputFilePath);
            }
            
            // 添加加密结果到响应数据
            $resultData['key'] = $encryptionResult['key'];
            $resultData['iv'] = $encryptionResult['iv'];
            $resultData['mode'] = $encryptionResult['mode'];
            break;
            
        case 'rsa':
            // 生成RSA密钥对或使用提供的公钥
            $publicKey = isset($options['rsa-public-key']) ? $options['rsa-public-key'] : null;
            $keySize = isset($options['rsa-key-size']) ? intval($options['rsa-key-size']) : 2048;
            
            if (!$publicKey) {
                $keyPair = rsa_generate_keys($keySize);
                $publicKey = $keyPair['publicKey'];
                $resultData['publicKey'] = $publicKey;
                $resultData['privateKey'] = $keyPair['privateKey'];
            }
            
            // 使用RSA加密
            $encoded = rsa_encrypt($fileContent, $publicKey);
            if (file_put_contents($outputFilePath, $encoded) === false) {
                throw new Exception('无法写入输出文件: ' . $outputFilePath);
            }
            break;
            
        case 'caesar':
            // 使用凯撒密码加密
            $shift = isset($options['caesar-shift']) ? intval($options['caesar-shift']) : 3;
            $encoded = caesar_encrypt($fileContent, $shift);
            if (file_put_contents($outputFilePath, $encoded) === false) {
                throw new Exception('无法写入输出文件: ' . $outputFilePath);
            }
            
            // 添加移位量到响应数据
            $resultData['shift'] = $shift;
            break;
            
        case 'vigenere':
            // 使用维吉尼亚密码加密
            $key = isset($options['vigenere-key']) ? $options['vigenere-key'] : null;
            
            if (!$key) {
                // 生成一个随机密钥
                $key = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8);
            }
            
            $encoded = vigenere_encrypt($fileContent, $key);
            if (file_put_contents($outputFilePath, $encoded) === false) {
                throw new Exception('无法写入输出文件: ' . $outputFilePath);
            }
            
            // 添加密钥到响应数据
            $resultData['key'] = $key;
            break;
            
        case 'rot13':
            $encoded = str_rot13($fileContent);
            if (file_put_contents($outputFilePath, $encoded) === false) {
                throw new Exception('无法写入输出文件: ' . $outputFilePath);
            }
            break;
            
        default:
            // 不应该到达这里，因为前面已经检查了算法支持
            throw new Exception('不支持的算法: ' . $algorithm);
    }
    
    // 获取输出文件大小
    $outputFileSize = filesize($outputFilePath);
    if ($outputFileSize === false) {
        throw new Exception('无法获取输出文件大小: ' . $outputFilePath);
    }
    
    // 返回文件URL
    $outputFileUrl = '/outputs/' . basename($outputFilePath);
    
    // 组合响应数据
    $responseData = [
        'success' => true,
        'data' => array_merge($resultData, [
            'fileUrl' => $outputFileUrl,
            'fileName' => basename($outputFilePath),
            'fileSize' => $outputFileSize,
            'fileSizeFormatted' => format_file_size($outputFileSize)
        ])
    ];
    
    // 发送成功响应
    sendJsonResponse($responseData);
    
} catch (Exception $e) {
    // 删除上传的文件和可能存在的输出文件
    @unlink($uploadedFilePath);
    @unlink($outputFilePath);
    
    // 处理编码过程中的异常
    sendErrorResponse(
        'FILE_ENCODE_ERROR',
        '文件编码过程中出错: ' . $e->getMessage()
    );
}