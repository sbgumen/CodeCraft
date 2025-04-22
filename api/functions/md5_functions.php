<?php
/**
 * CodeCraft - MD5相关函数
 * 提供MD5哈希功能和自定义MD5实现
 */

/**
 * 计算MD5哈希
 * @param string $text 要哈希的文本
 * @return string MD5哈希值
 */
function md5_hash($text) {
    if (empty($text)) {
        return '';
    }
    
    // 使用PHP内置的md5函数
    return md5($text);
}

/**
 * 自定义MD5实现 - 用于教育目的
 * 注意：在实际应用中，应使用PHP内置的md5函数
 * 实现基于MD5算法规范 (RFC 1321)
 * 
 * @param string $text 要哈希的文本
 * @return string MD5哈希值
 */
function custom_md5($text) {
    // 步骤1：填充消息
    $text = str_pad_message($text);
    
    // 步骤2：初始化MD缓冲区
    $a0 = 0x67452301;
    $b0 = 0xEFCDAB89;
    $c0 = 0x98BADCFE;
    $d0 = 0x10325476;
    
    // 步骤3：处理消息块
    $chunks = str_split($text, 64); // 512位 = 64字节
    
    foreach ($chunks as $chunk) {
        // 将块分割为16个32位字
        $M = [];
        for ($i = 0; $i < 16; $i++) {
            $M[$i] = unpack('V', substr($chunk, $i * 4, 4))[1];
        }
        
        // 初始化哈希值
        $A = $a0;
        $B = $b0;
        $C = $c0;
        $D = $d0;
        
        // 定义辅助函数
        $F = function($X, $Y, $Z) {
            return ($X & $Y) | (~$X & $Z);
        };
        
        $G = function($X, $Y, $Z) {
            return ($X & $Z) | ($Y & ~$Z);
        };
        
        $H = function($X, $Y, $Z) {
            return $X ^ $Y ^ $Z;
        };
        
        $I = function($X, $Y, $Z) {
            return $Y ^ ($X | ~$Z);
        };
        
        // 定义左循环移位函数
        $leftRotate = function($x, $c) {
            return (($x << $c) | (($x & 0xFFFFFFFF) >> (32 - $c))) & 0xFFFFFFFF;
        };
        
        // MD5预定义常数
        $T = [];
        for ($i = 1; $i <= 64; $i++) {
            $T[$i - 1] = floor(abs(sin($i)) * pow(2, 32));
        }
        
        // 第一轮
        for ($i = 0; $i < 16; $i++) {
            $j = $i;
            $temp = $A + $F($B, $C, $D) + $M[$j] + $T[$i];
            $temp = $leftRotate($temp, [7, 12, 17, 22][$i % 4]);
            $A = $D;
            $D = $C;
            $C = $B;
            $B = ($B + $temp) & 0xFFFFFFFF;
        }
        
        // 第二轮
        for ($i = 16; $i < 32; $i++) {
            $j = (5 * $i + 1) % 16;
            $temp = $A + $G($B, $C, $D) + $M[$j] + $T[$i];
            $temp = $leftRotate($temp, [5, 9, 14, 20][($i % 4)]);
            $A = $D;
            $D = $C;
            $C = $B;
            $B = ($B + $temp) & 0xFFFFFFFF;
        }
        
        // 第三轮
        for ($i = 32; $i < 48; $i++) {
            $j = (3 * $i + 5) % 16;
            $temp = $A + $H($B, $C, $D) + $M[$j] + $T[$i];
            $temp = $leftRotate($temp, [4, 11, 16, 23][($i % 4)]);
            $A = $D;
            $D = $C;
            $C = $B;
            $B = ($B + $temp) & 0xFFFFFFFF;
        }
        
        // 第四轮
        for ($i = 48; $i < 64; $i++) {
            $j = (7 * $i) % 16;
            $temp = $A + $I($B, $C, $D) + $M[$j] + $T[$i];
            $temp = $leftRotate($temp, [6, 10, 15, 21][($i % 4)]);
            $A = $D;
            $D = $C;
            $C = $B;
            $B = ($B + $temp) & 0xFFFFFFFF;
        }
        
        // 添加结果到缓冲区
        $a0 = ($a0 + $A) & 0xFFFFFFFF;
        $b0 = ($b0 + $B) & 0xFFFFFFFF;
        $c0 = ($c0 + $C) & 0xFFFFFFFF;
        $d0 = ($d0 + $D) & 0xFFFFFFFF;
    }
    
    // 步骤4：输出 - 将缓冲区值转换为十六进制字符串
    $result = '';
    foreach ([$a0, $b0, $c0, $d0] as $val) {
        $result .= str_pad(dechex($val & 0xFF), 2, '0', STR_PAD_LEFT) .
                  str_pad(dechex(($val >> 8) & 0xFF), 2, '0', STR_PAD_LEFT) .
                  str_pad(dechex(($val >> 16) & 0xFF), 2, '0', STR_PAD_LEFT) .
                  str_pad(dechex(($val >> 24) & 0xFF), 2, '0', STR_PAD_LEFT);
    }
    
    return $result;
}

/**
 * 填充消息以符合MD5要求
 * @param string $text 原始消息
 * @return string 填充后的消息
 */
function str_pad_message($text) {
    // 将消息转换为二进制
    $binary = '';
    for ($i = 0; $i < strlen($text); $i++) {
        $binary .= $text[$i];
    }
    
    // 计算原始消息的比特长度
    $originalBitLength = strlen($binary) * 8;
    
    // 添加单个'1'位
    $binary .= chr(0x80);
    
    // 填充'0'位直到长度对512取模等于448
    while ((strlen($binary) * 8) % 512 != 448) {
        $binary .= chr(0);
    }
    
    // 添加64位的原始消息长度
    $lowBits = $originalBitLength & 0xFFFFFFFF;
    $highBits = ($originalBitLength >> 32) & 0xFFFFFFFF;
    
    $binary .= pack('V', $lowBits);
    $binary .= pack('V', $highBits);
    
    return $binary;
}

/**
 * 验证字符串是否是有效的MD5哈希
 * @param string $hash 要验证的哈希字符串
 * @return bool 是否是有效的MD5哈希
 */
function is_valid_md5($hash) {
    return preg_match('/^[a-f0-9]{32}$/i', $hash) === 1;
}