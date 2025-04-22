<?php
/**
 * CodeCraft - 高级加密和解密函数
 * 提供多种加密和解密算法
 */

/**
 * AES加密
 * @param string $text 要加密的文本
 * @param string $key 加密密钥 (可选)
 * @param string $iv 初始化向量 (可选)
 * @param string $mode 加密模式 (CBC, ECB, CTR, OFB, CFB)
 * @return string 加密后的文本 (Base64编码)
 */
function aes_encrypt($text, $key = null, $iv = null, $mode = 'CBC') {
    // 如果没有提供密钥，生成一个随机密钥
    if ($key === null) {
        $key = bin2hex(random_bytes(16)); // 256位密钥
    }
    
    // 如果密钥长度不是32个字符(16字节)，进行调整
    if (strlen($key) < 32) {
        $key = str_pad($key, 32, '0');
    } elseif (strlen($key) > 32) {
        $key = substr($key, 0, 32);
    }
    
    // 如果没有提供IV，生成一个随机IV
    if ($iv === null) {
        $iv = openssl_random_pseudo_bytes(16); // 128位IV
    } else {
        // 确保IV的长度是16个字节
        if (strlen($iv) < 16) {
            $iv = str_pad($iv, 16, '0');
        } elseif (strlen($iv) > 16) {
            $iv = substr($iv, 0, 16);
        }
    }
    
    // 确定加密模式
    $cipher = 'aes-256-';
    switch (strtoupper($mode)) {
        case 'ECB':
            $cipher .= 'ecb';
            $iv = null; // ECB模式不使用IV
            break;
        case 'CTR':
            $cipher .= 'ctr';
            break;
        case 'OFB':
            $cipher .= 'ofb';
            break;
        case 'CFB':
            $cipher .= 'cfb';
            break;
        default:
            $cipher .= 'cbc'; // 默认CBC模式
    }
    
    // 执行加密
    $encrypted = openssl_encrypt(
        $text,
        $cipher,
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    if ($encrypted === false) {
        throw new Exception('加密失败: ' . openssl_error_string());
    }
    
    // 组合IV和加密数据，并使用Base64编码
    $result = base64_encode($iv . $encrypted);
    
    return [
        'encrypted' => $result,
        'key' => bin2hex($key),
        'iv' => bin2hex($iv),
        'mode' => $mode
    ];
}

/**
 * AES解密
 * @param string $encryptedText Base64编码的加密文本
 * @param string $key 解密密钥
 * @param string $iv 初始化向量 (对于ECB模式可为null)
 * @param string $mode 解密模式 (CBC, ECB, CTR, OFB, CFB)
 * @return string 解密后的文本
 */
function aes_decrypt($encryptedText, $key, $iv = null, $mode = 'CBC') {
    // 从Base64解码
    $data = base64_decode($encryptedText);
    
    // 将十六进制密钥转换为二进制
    if (ctype_xdigit($key)) {
        $key = hex2bin($key);
    }
    
    // 确定解密模式
    $cipher = 'aes-256-';
    switch (strtoupper($mode)) {
        case 'ECB':
            $cipher .= 'ecb';
            // ECB模式不使用IV
            $encrypted = $data;
            break;
        case 'CTR':
            $cipher .= 'ctr';
            if ($iv === null) {
                // 从加密数据中提取IV (前16字节)
                $iv = substr($data, 0, 16);
                $encrypted = substr($data, 16);
            } else {
                // 如果提供了IV，直接使用
                if (ctype_xdigit($iv)) {
                    $iv = hex2bin($iv);
                }
                $encrypted = $data;
            }
            break;
        case 'OFB':
            $cipher .= 'ofb';
            if ($iv === null) {
                $iv = substr($data, 0, 16);
                $encrypted = substr($data, 16);
            } else {
                if (ctype_xdigit($iv)) {
                    $iv = hex2bin($iv);
                }
                $encrypted = $data;
            }
            break;
        case 'CFB':
            $cipher .= 'cfb';
            if ($iv === null) {
                $iv = substr($data, 0, 16);
                $encrypted = substr($data, 16);
            } else {
                if (ctype_xdigit($iv)) {
                    $iv = hex2bin($iv);
                }
                $encrypted = $data;
            }
            break;
        default:
            $cipher .= 'cbc'; // 默认CBC模式
            if ($iv === null) {
                $iv = substr($data, 0, 16);
                $encrypted = substr($data, 16);
            } else {
                if (ctype_xdigit($iv)) {
                    $iv = hex2bin($iv);
                }
                $encrypted = $data;
            }
    }
    
    // 执行解密
    $decrypted = openssl_decrypt(
        $encrypted,
        $cipher,
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    if ($decrypted === false) {
        throw new Exception('解密失败: ' . openssl_error_string());
    }
    
    return $decrypted;
}

/**
 * RSA密钥对生成
 * @param int $bits 密钥位数 (通常是2048或4096)
 * @return array ['privateKey' => 私钥, 'publicKey' => 公钥]
 */
function rsa_generate_keys($bits = 2048) {
    // 设置配置参数
    $config = [
        'digest_alg' => 'sha256',
        'private_key_bits' => $bits,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];
    
    // 生成密钥对
    $res = openssl_pkey_new($config);
    
    if ($res === false) {
        throw new Exception('RSA密钥生成失败: ' . openssl_error_string());
    }
    
    // 提取私钥
    openssl_pkey_export($res, $privateKey);
    
    // 提取公钥
    $publicKeyDetails = openssl_pkey_get_details($res);
    $publicKey = $publicKeyDetails['key'];
    
    return [
        'privateKey' => $privateKey,
        'publicKey' => $publicKey
    ];
}

/**
 * RSA加密
 * @param string $text 要加密的文本
 * @param string $publicKey 公钥
 * @return string Base64编码的加密文本
 */
function rsa_encrypt($text, $publicKey) {
    // 加载公钥
    $pubKeyRes = openssl_pkey_get_public($publicKey);
    
    if ($pubKeyRes === false) {
        throw new Exception('无效的公钥: ' . openssl_error_string());
    }
    
    // 获取密钥详情
    $details = openssl_pkey_get_details($pubKeyRes);
    $keySize = $details['bits'] / 8 - 11; // 可加密的最大长度
    
    // 如果文本太长，分块加密
    if (strlen($text) > $keySize) {
        $encrypted = '';
        $chunks = str_split($text, $keySize);
        
        foreach ($chunks as $chunk) {
            $partialEncrypted = '';
            $encryptSuccess = openssl_public_encrypt($chunk, $partialEncrypted, $pubKeyRes);
            
            if ($encryptSuccess === false) {
                throw new Exception('RSA加密失败: ' . openssl_error_string());
            }
            
            $encrypted .= $partialEncrypted;
        }
    } else {
        // 文本足够短，直接加密
        $encryptSuccess = openssl_public_encrypt($text, $encrypted, $pubKeyRes);
        
        if ($encryptSuccess === false) {
            throw new Exception('RSA加密失败: ' . openssl_error_string());
        }
    }
    
    // 释放资源
    openssl_free_key($pubKeyRes);
    
    return base64_encode($encrypted);
}

/**
 * RSA解密
 * @param string $encryptedText Base64编码的加密文本
 * @param string $privateKey 私钥
 * @return string 解密后的文本
 */
function rsa_decrypt($encryptedText, $privateKey) {
    // 加载私钥
    $privKeyRes = openssl_pkey_get_private($privateKey);
    
    if ($privKeyRes === false) {
        throw new Exception('无效的私钥: ' . openssl_error_string());
    }
    
    // 解码Base64
    $encryptedData = base64_decode($encryptedText);
    
    // 获取密钥详情
    $details = openssl_pkey_get_details($privKeyRes);
    $keySize = $details['bits'] / 8;
    
    // 如果加密数据很长，分块解密
    if (strlen($encryptedData) > $keySize) {
        $decrypted = '';
        $chunks = str_split($encryptedData, $keySize);
        
        foreach ($chunks as $chunk) {
            $partialDecrypted = '';
            $decryptSuccess = openssl_private_decrypt($chunk, $partialDecrypted, $privKeyRes);
            
            if ($decryptSuccess === false) {
                throw new Exception('RSA解密失败: ' . openssl_error_string());
            }
            
            $decrypted .= $partialDecrypted;
        }
    } else {
        // 直接解密
        $decryptSuccess = openssl_private_decrypt($encryptedData, $decrypted, $privKeyRes);
        
        if ($decryptSuccess === false) {
            throw new Exception('RSA解密失败: ' . openssl_error_string());
        }
    }
    
    // 释放资源
    openssl_free_key($privKeyRes);
    
    return $decrypted;
}

/**
 * JWT令牌生成
 * @param array $payload 数据负载
 * @param string $key 签名密钥
 * @param string $algorithm 签名算法 (HS256, HS384, HS512)
 * @return string JWT令牌
 */
function jwt_encode($payload, $key, $algorithm = 'HS256') {
    // 头部
    $header = [
        'typ' => 'JWT',
        'alg' => $algorithm
    ];
    
    // Base64Url编码头部和负载
    $encodedHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $encodedPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    
    // 创建签名
    $signature = '';
    $data = $encodedHeader . '.' . $encodedPayload;
    
    switch ($algorithm) {
        case 'HS384':
            $signature = hash_hmac('sha384', $data, $key, true);
            break;
        case 'HS512':
            $signature = hash_hmac('sha512', $data, $key, true);
            break;
        default:
            $signature = hash_hmac('sha256', $data, $key, true);
    }
    
    $encodedSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    // 生成JWT
    return $encodedHeader . '.' . $encodedPayload . '.' . $encodedSignature;
}

/**
 * JWT令牌验证和解码
 * @param string $jwt JWT令牌
 * @param string $key 签名密钥
 * @return array|false 成功时返回负载数据，失败时返回false
 */
function jwt_decode($jwt, $key) {
    // 分割JWT
    $parts = explode('.', $jwt);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($encodedHeader, $encodedPayload, $encodedSignature) = $parts;
    
    // 解码头部
    $header = json_decode(base64_decode(strtr($encodedHeader, '-_', '+/')), true);
    
    if (!$header) {
        return false;
    }
    
    // 获取算法
    $algorithm = isset($header['alg']) ? $header['alg'] : 'HS256';
    
    // 验证签名
    $data = $encodedHeader . '.' . $encodedPayload;
    $signature = base64_decode(strtr($encodedSignature, '-_', '+/'));
    $isSignatureValid = false;
    
    switch ($algorithm) {
        case 'HS384':
            $expectedSignature = hash_hmac('sha384', $data, $key, true);
            $isSignatureValid = hash_equals($expectedSignature, $signature);
            break;
        case 'HS512':
            $expectedSignature = hash_hmac('sha512', $data, $key, true);
            $isSignatureValid = hash_equals($expectedSignature, $signature);
            break;
        default:
            $expectedSignature = hash_hmac('sha256', $data, $key, true);
            $isSignatureValid = hash_equals($expectedSignature, $signature);
    }
    
    if (!$isSignatureValid) {
        return false;
    }
    
    // 解码负载
    $payload = json_decode(base64_decode(strtr($encodedPayload, '-_', '+/')), true);
    
    // 检查令牌是否过期
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

/**
 * 摩斯密码编码
 * @param string $text 要编码的文本
 * @return string 摩斯密码
 */
function morse_encode($text) {
    $morseCodeMap = [
        'A' => '.-',     'B' => '-...',   'C' => '-.-.',   'D' => '-..',
        'E' => '.',      'F' => '..-.',   'G' => '--.',    'H' => '....',
        'I' => '..',     'J' => '.---',   'K' => '-.-',    'L' => '.-..',
        'M' => '--',     'N' => '-.',     'O' => '---',    'P' => '.--.',
        'Q' => '--.-',   'R' => '.-.',    'S' => '...',    'T' => '-',
        'U' => '..-',    'V' => '...-',   'W' => '.--',    'X' => '-..-',
        'Y' => '-.--',   'Z' => '--..',   '0' => '-----',  '1' => '.----',
        '2' => '..---',  '3' => '...--',  '4' => '....-',  '5' => '.....',
        '6' => '-....',  '7' => '--...',  '8' => '---..',  '9' => '----.',
        '.' => '.-.-.-', ',' => '--..--', '?' => '..--..', "'" => '.----.',
        '!' => '-.-.--', '/' => '-..-.',  '(' => '-.--.',  ')' => '-.--.-',
        '&' => '.-...',  ':' => '---...', ';' => '-.-.-.', '=' => '-...-',
        '+' => '.-.-.',  '-' => '-....-', '_' => '..--.-', '"' => '.-..-.',
        '$' => '...-..-','@' => '.--.-.',
        ' ' => '/'       // 空格转换为斜杠
    ];
    
    $text = strtoupper($text);
    $morse = '';
    
    for ($i = 0; $i < strlen($text); $i++) {
        $char = $text[$i];
        
        if (isset($morseCodeMap[$char])) {
            $morse .= $morseCodeMap[$char] . ' ';
        } else {
            // 对于未知字符，保持原样
            $morse .= $char . ' ';
        }
    }
    
    return trim($morse);
}

/**
 * 摩斯密码解码
 * @param string $morse 摩斯密码
 * @return string 解码后的文本
 */
function morse_decode($morse) {
    $morseCodeMap = [
        '.-'     => 'A', '-...'   => 'B', '-.-.'   => 'C', '-..'    => 'D',
        '.'      => 'E', '..-.'   => 'F', '--.'    => 'G', '....'   => 'H',
        '..'     => 'I', '.---'   => 'J', '-.-'    => 'K', '.-..'   => 'L',
        '--'     => 'M', '-.'     => 'N', '---'    => 'O', '.--.'   => 'P',
        '--.-'   => 'Q', '.-.'    => 'R', '...'    => 'S', '-'      => 'T',
        '..-'    => 'U', '...-'   => 'V', '.--'    => 'W', '-..-'   => 'X',
        '-.--'   => 'Y', '--..'   => 'Z', '-----'  => '0', '.----'  => '1',
        '..---'  => '2', '...--'  => '3', '....-'  => '4', '.....'  => '5',
        '-....'  => '6', '--...'  => '7', '---..'  => '8', '----.'  => '9',
        '.-.-.-' => '.', '--..--' => ',', '..--..' => '?', '.----.' => "'",
        '-.-.--' => '!', '-..-.'  => '/', '-.--.'  => '(', '-.--.-' => ')',
        '.-...'  => '&', '---...' => ':', '-.-.-.' => ';', '-...-'  => '=',
        '.-.-.'  => '+', '-....-' => '-', '..--.-' => '_', '.-..-.' => '"',
        '...-..-'=> '$', '.--.-.' => '@',
        '/'      => ' '  // 斜杠转换为空格
    ];
    
    $words = explode(' / ', $morse);
    $result = '';
    
    foreach ($words as $word) {
        $chars = explode(' ', $word);
        
        foreach ($chars as $char) {
            if (empty($char)) continue;
            
            if (isset($morseCodeMap[$char])) {
                $result .= $morseCodeMap[$char];
            } else {
                // 对于未知摩斯码，保持原样
                $result .= $char;
            }
        }
        
        $result .= ' ';
    }
    
    return trim($result);
}

/**
 * 凯撒密码加密 (Caesar Cipher)
 * @param string $text 要加密的文本
 * @param int $shift 移位量 (1-25)
 * @return string 加密后的文本
 */
function caesar_encrypt($text, $shift = 3) {
    $shift = $shift % 26;
    $result = '';
    
    for ($i = 0; $i < strlen($text); $i++) {
        $char = $text[$i];
        
        // 只处理字母
        if (ctype_alpha($char)) {
            $ascii = ord($char);
            $isUppercase = ctype_upper($char);
            
            // 计算新的ASCII值
            $baseAscii = $isUppercase ? 65 : 97;
            $newAscii = ($ascii - $baseAscii + $shift) % 26 + $baseAscii;
            
            $result .= chr($newAscii);
        } else {
            // 非字母字符保持不变
            $result .= $char;
        }
    }
    
    return $result;
}

/**
 * 凯撒密码解密
 * @param string $text 加密的文本
 * @param int $shift 移位量 (1-25)
 * @return string 解密后的文本
 */
function caesar_decrypt($text, $shift = 3) {
    // 解密就是向反方向移动，所以用26减去移位量
    return caesar_encrypt($text, 26 - ($shift % 26));
}

/**
 * 维吉尼亚密码加密 (Vigenère Cipher)
 * @param string $text 要加密的文本
 * @param string $key 密钥
 * @return string 加密后的文本
 */
function vigenere_encrypt($text, $key) {
    $key = strtoupper($key);
    $keyLength = strlen($key);
    $result = '';
    $keyIndex = 0;
    
    for ($i = 0; $i < strlen($text); $i++) {
        $char = $text[$i];
        
        // 只处理字母
        if (ctype_alpha($char)) {
            // 确定移位量（基于密钥字符的相应ASCII值）
            $keyChar = $key[$keyIndex % $keyLength];
            $shift = ord($keyChar) - 65; // 'A'的ASCII值是65
            
            $ascii = ord($char);
            $isUppercase = ctype_upper($char);
            
            // 计算新的ASCII值
            $baseAscii = $isUppercase ? 65 : 97;
            $newAscii = ($ascii - $baseAscii + $shift) % 26 + $baseAscii;
            
            $result .= chr($newAscii);
            $keyIndex++;
        } else {
            // 非字母字符保持不变
            $result .= $char;
        }
    }
    
    return $result;
}

/**
 * 维吉尼亚密码解密
 * @param string $text 加密的文本
 * @param string $key 密钥
 * @return string 解密后的文本
 */
function vigenere_decrypt($text, $key) {
    $key = strtoupper($key);
    $keyLength = strlen($key);
    $result = '';
    $keyIndex = 0;
    
    for ($i = 0; $i < strlen($text); $i++) {
        $char = $text[$i];
        
        // 只处理字母
        if (ctype_alpha($char)) {
            // 确定移位量（基于密钥字符的相应ASCII值）
            $keyChar = $key[$keyIndex % $keyLength];
            $shift = ord($keyChar) - 65; // 'A'的ASCII值是65
            
            $ascii = ord($char);
            $isUppercase = ctype_upper($char);
            
            // 计算新的ASCII值（反方向移动）
            $baseAscii = $isUppercase ? 65 : 97;
            $newAscii = ($ascii - $baseAscii - $shift + 26) % 26 + $baseAscii;
            
            $result .= chr($newAscii);
            $keyIndex++;
        } else {
            // 非字母字符保持不变
            $result .= $char;
        }
    }
    
    return $result;
}

/**
 * 十六进制编码
 * @param string $text 要编码的文本
 * @return string 十六进制编码
 */
function hex_encode($text) {
    $hex = '';
    
    for ($i = 0; $i < strlen($text); $i++) {
        $hex .= dechex(ord($text[$i]));
    }
    
    return $hex;
}

/**
 * 十六进制解码
 * @param string $hex 十六进制编码
 * @return string 解码后的文本
 */
function hex_decode($hex) {
    // 移除所有空格
    $hex = str_replace(' ', '', $hex);
    
    $text = '';
    
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $text .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    
    return $text;
}

/**
 * 二进制编码
 * @param string $text 要编码的文本
 * @param bool $addSpaces 是否在字节之间添加空格
 * @return string 二进制编码
 */
function binary_encode($text, $addSpaces = true) {
    $binary = '';
    
    for ($i = 0; $i < strlen($text); $i++) {
        $bin = decbin(ord($text[$i]));
        $bin = str_pad($bin, 8, '0', STR_PAD_LEFT);
        
        $binary .= $bin;
        
        if ($addSpaces && $i < strlen($text) - 1) {
            $binary .= ' ';
        }
    }
    
    return $binary;
}

/**
 * 二进制解码
 * @param string $binary 二进制编码
 * @return string 解码后的文本
 */
function binary_decode($binary) {
    // 移除所有空格
    $binary = str_replace(' ', '', $binary);
    
    $text = '';
    
    // 每8位表示一个字符
    for ($i = 0; $i < strlen($binary); $i += 8) {
        $byte = substr($binary, $i, 8);
        
        if (strlen($byte) === 8) {
            $text .= chr(bindec($byte));
        }
    }
    
    return $text;
}

/**
 * ROT13编码/解码
 * ROT13是凯撒密码的一种特殊情况，移位量固定为13
 * @param string $text 要编码/解码的文本
 * @return string 编码/解码后的文本
 */
function rot13($text) {
    return str_rot13($text);
}

/**
 * BASE32编码
 * @param string $text 要编码的文本
 * @return string BASE32编码
 */
function base32_encode($text) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $inputLength = strlen($text);
    
    for ($i = 0; $i < $inputLength; $i += 5) {
        $chunk = substr($text, $i, 5);
        $chunkLength = strlen($chunk);
        
        // 将字符转换为字节
        $bytes = [];
        for ($j = 0; $j < $chunkLength; $j++) {
            $bytes[$j] = ord($chunk[$j]);
        }
        
        // 根据字节长度编码
        switch ($chunkLength) {
            case 5:
                $output .= $alphabet[($bytes[0] >> 3) & 0x1F];
                $output .= $alphabet[(($bytes[0] << 2) & 0x1C) | (($bytes[1] >> 6) & 0x03)];
                $output .= $alphabet[($bytes[1] >> 1) & 0x1F];
                $output .= $alphabet[(($bytes[1] << 4) & 0x10) | (($bytes[2] >> 4) & 0x0F)];
                $output .= $alphabet[(($bytes[2] << 1) & 0x1E) | (($bytes[3] >> 7) & 0x01)];
                $output .= $alphabet[($bytes[3] >> 2) & 0x1F];
                $output .= $alphabet[(($bytes[3] << 3) & 0x18) | (($bytes[4] >> 5) & 0x07)];
                $output .= $alphabet[$bytes[4] & 0x1F];
                break;
            case 4:
                $output .= $alphabet[($bytes[0] >> 3) & 0x1F];
                $output .= $alphabet[(($bytes[0] << 2) & 0x1C) | (($bytes[1] >> 6) & 0x03)];
                $output .= $alphabet[($bytes[1] >> 1) & 0x1F];
                $output .= $alphabet[(($bytes[1] << 4) & 0x10) | (($bytes[2] >> 4) & 0x0F)];
                $output .= $alphabet[(($bytes[2] << 1) & 0x1E) | (($bytes[3] >> 7) & 0x01)];
                $output .= $alphabet[($bytes[3] >> 2) & 0x1F];
                $output .= $alphabet[($bytes[3] << 3) & 0x18];
                $output .= '=';
                break;
            case 3:
                $output .= $alphabet[($bytes[0] >> 3) & 0x1F];
                $output .= $alphabet[(($bytes[0] << 2) & 0x1C) | (($bytes[1] >> 6) & 0x03)];
                $output .= $alphabet[($bytes[1] >> 1) & 0x1F];
                $output .= $alphabet[(($bytes[1] << 4) & 0x10) | (($bytes[2] >> 4) & 0x0F)];
                $output .= $alphabet[($bytes[2] << 1) & 0x1E];
                $output .= '===';
                break;
            case 2:
                $output .= $alphabet[($bytes[0] >> 3) & 0x1F];
                $output .= $alphabet[(($bytes[0] << 2) & 0x1C) | (($bytes[1] >> 6) & 0x03)];
                $output .= $alphabet[($bytes[1] >> 1) & 0x1F];
                $output .= $alphabet[($bytes[1] << 4) & 0x10];
                $output .= '====';
                break;
            case 1:
                $output .= $alphabet[($bytes[0] >> 3) & 0x1F];
                $output .= $alphabet[($bytes[0] << 2) & 0x1C];
                $output .= '======';
                break;
        }
    }
    
    return $output;
}

/**
 * BASE32解码
 * @param string $base32 BASE32编码
 * @return string 解码后的文本
 */
function base32_decode($base32) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32 = strtoupper($base32);
    $output = '';
    
    // 移除所有填充字符
    $base32 = rtrim($base32, '=');
    $base32Length = strlen($base32);
    
    // 创建查找表
    $lookup = [];
    for ($i = 0; $i < 32; $i++) {
        $lookup[$alphabet[$i]] = $i;
    }
    
    // 处理每8个字符
    for ($i = 0; $i < $base32Length; $i += 8) {
        $chunk = substr($base32, $i, 8);
        $chunkLength = strlen($chunk);
        
        // 解码每个字符
        $bytes = [];
        for ($j = 0; $j < $chunkLength; $j++) {
            $bytes[$j] = isset($lookup[$chunk[$j]]) ? $lookup[$chunk[$j]] : 0;
        }
        
        // 根据块长度生成字节
        switch ($chunkLength) {
            case 8:
                $output .= chr(($bytes[0] << 3) | ($bytes[1] >> 2));
                $output .= chr(($bytes[1] << 6) | ($bytes[2] << 1) | ($bytes[3] >> 4));
                $output .= chr(($bytes[3] << 4) | ($bytes[4] >> 1));
                $output .= chr(($bytes[4] << 7) | ($bytes[5] << 2) | ($bytes[6] >> 3));
                $output .= chr(($bytes[6] << 5) | $bytes[7]);
                break;
            case 7:
                $output .= chr(($bytes[0] << 3) | ($bytes[1] >> 2));
                $output .= chr(($bytes[1] << 6) | ($bytes[2] << 1) | ($bytes[3] >> 4));
                $output .= chr(($bytes[3] << 4) | ($bytes[4] >> 1));
                $output .= chr(($bytes[4] << 7) | ($bytes[5] << 2) | ($bytes[6] >> 3));
                break;
            case 5:
                $output .= chr(($bytes[0] << 3) | ($bytes[1] >> 2));
                $output .= chr(($bytes[1] << 6) | ($bytes[2] << 1) | ($bytes[3] >> 4));
                $output .= chr(($bytes[3] << 4) | ($bytes[4] >> 1));
                break;
            case 4:
                $output .= chr(($bytes[0] << 3) | ($bytes[1] >> 2));
                $output .= chr(($bytes[1] << 6) | ($bytes[2] << 1) | ($bytes[3] >> 4));
                break;
            case 2:
                $output .= chr(($bytes[0] << 3) | ($bytes[1] >> 2));
                break;
        }
    }
    
    return $output;
}

/**
 * XML编码
 * @param mixed $data 要编码的数据 (通常是数组)
 * @param string $rootElement 根元素的名称
 * @param boolean $formatOutput 是否格式化输出
 * @return string XML字符串
 */
function xml_encode($data, $rootElement = 'root', $formatOutput = true) {
    // 创建DOMDocument对象
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = $formatOutput;
    
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
    
    // 创建根元素
    $root = $xml->createElement($rootElement);
    $xml->appendChild($root);
    
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
    
    return $xml->saveXML();
}

/**
 * XML解码
 * @param string $xml XML字符串
 * @param boolean $assoc 是否返回关联数组
 * @return array|object 解码后的数据
 */
function xml_decode($xml, $assoc = true) {
    // 检查XML是否有效
    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    
    $result = $dom->loadXML($xml);
    libxml_use_internal_errors($prev);
    
    if (!$result) {
        throw new Exception('无效的XML');
    }
    
    // 将XML转换为SimpleXMLElement
    $sxml = simplexml_load_string($xml);
    
    // 将SimpleXMLElement转换为JSON
    $json = json_encode($sxml);
    
    // 将JSON转换为数组或对象
    return json_decode($json, $assoc);
}

/**
 * YAML编码
 * 注意：需要安装yaml扩展
 * @param mixed $data 要编码的数据
 * @return string YAML字符串
 */
function yaml_encode($data) {
    if (!function_exists('yaml_emit')) {
        throw new Exception('需要YAML扩展');
    }
    
    return yaml_emit($data, YAML_UTF8_ENCODING);
}

/**
 * YAML解码
 * 注意：需要安装yaml扩展
 * @param string $yaml YAML字符串
 * @return mixed 解码后的数据
 */
function yaml_decode($yaml) {
    if (!function_exists('yaml_parse')) {
        throw new Exception('需要YAML扩展');
    }
    
    return yaml_parse($yaml);
}