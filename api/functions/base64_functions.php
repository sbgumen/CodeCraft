<?php
/**
 * CodeCraft - Base64相关函数
 * 提供Base64编码和解码功能
 */

/**
 * 检查字符串是否是有效的Base64编码
 * @param string $string 要检查的字符串
 * @return bool 是否是有效的Base64编码
 */
function is_valid_base64($string) {
    // 移除Base64填充字符
    $string = str_replace(['-', '_'], ['+', '/'], $string);
    $string = rtrim($string, '=');
    
    // 检查长度是否为4的倍数
    if (strlen($string) % 4 !== 0) {
        return false;
    }
    
    // 使用正则表达式检查是否只包含有效的Base64字符
    return preg_match('/^[A-Za-z0-9\/+]*$/', $string) === 1;
}

/**
 * 使用Base64编码文本
 * @param string $text 要编码的文本
 * @return string Base64编码后的文本
 */
function base64_encode_text($text) {
    if (empty($text)) {
        return '';
    }
    
    // 使用PHP内置的base64_encode函数
    return base64_encode($text);
}

/**
 * 解码Base64文本
 * @param string $text Base64编码的文本
 * @return string 解码后的文本
 */
function base64_decode_text($text) {
    if (empty($text)) {
        return '';
    }
    
    // 尝试清理和修复可能的Base64字符串
    $text = str_replace(['-', '_'], ['+', '/'], $text);
    
    // 添加可能缺少的填充字符
    $padLength = strlen($text) % 4;
    if ($padLength) {
        $text .= str_repeat('=', 4 - $padLength);
    }
    
    // 使用严格模式解码Base64
    $decoded = base64_decode($text, true);
    
    if ($decoded === false) {
        throw new Exception('无效的Base64编码');
    }
    
    return $decoded;
}

/**
 * 自定义Base64实现 - 用于教育目的
 * 注意：在实际应用中，应使用PHP内置的base64_encode/base64_decode函数
 */

/**
 * 自定义Base64编码实现
 * @param string $text 要编码的文本
 * @return string Base64编码后的文本
 */
function custom_base64_encode($text) {
    // Base64字符表
    $base64Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    
    $result = '';
    $padding = strlen($text) % 3;
    
    // 将3个字节转换为4个Base64字符
    for ($i = 0; $i < strlen($text); $i += 3) {
        $chunk = substr($text, $i, 3);
        
        // 将字符串转换为ASCII值
        $bytes = [];
        for ($j = 0; $j < strlen($chunk); $j++) {
            $bytes[] = ord($chunk[$j]);
        }
        
        // 将3个8位字节转换为4个6位索引
        $indices = [];
        
        if (isset($bytes[0])) {
            $indices[0] = ($bytes[0] >> 2) & 0x3F;
        }
        
        if (isset($bytes[1])) {
            $indices[1] = (($bytes[0] & 0x03) << 4) | (($bytes[1] >> 4) & 0x0F);
            $indices[2] = (($bytes[1] & 0x0F) << 2) | (($bytes[2] >> 6) & 0x03);
        } else {
            $indices[1] = ($bytes[0] & 0x03) << 4;
            $indices[2] = 64; // 填充
        }
        
        if (isset($bytes[2])) {
            $indices[3] = $bytes[2] & 0x3F;
        } else {
            $indices[3] = 64; // 填充
        }
        
        // 使用索引查找Base64字符
        for ($j = 0; $j < 4; $j++) {
            if ($indices[$j] === 64) {
                $result .= '=';
            } else {
                $result .= $base64Chars[$indices[$j]];
            }
        }
    }
    
    return $result;
}

/**
 * 自定义Base64解码实现
 * @param string $text Base64编码的文本
 * @return string 解码后的文本
 */
function custom_base64_decode($text) {
    // Base64字符表
    $base64Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    
    // 创建字符到索引的映射
    $base64Map = [];
    for ($i = 0; $i < strlen($base64Chars); $i++) {
        $base64Map[$base64Chars[$i]] = $i;
    }
    
    // 移除填充字符
    $text = rtrim($text, '=');
    
    $result = '';
    
    // 将4个Base64字符转换为3个字节
    for ($i = 0; $i < strlen($text); $i += 4) {
        $chunk = substr($text, $i, 4);
        
        // 将Base64字符转换为索引
        $indices = [];
        for ($j = 0; $j < strlen($chunk); $j++) {
            if (isset($base64Map[$chunk[$j]])) {
                $indices[] = $base64Map[$chunk[$j]];
            } else {
                $indices[] = 0;
            }
        }
        
        // 将4个6位索引转换为3个8位字节
        $bytes = [];
        
        if (isset($indices[0]) && isset($indices[1])) {
            $bytes[0] = ($indices[0] << 2) | (($indices[1] >> 4) & 0x03);
        }
        
        if (isset($indices[1]) && isset($indices[2])) {
            $bytes[1] = (($indices[1] & 0x0F) << 4) | (($indices[2] >> 2) & 0x0F);
        }
        
        if (isset($indices[2]) && isset($indices[3])) {
            $bytes[2] = (($indices[2] & 0x03) << 6) | $indices[3];
        }
        
        // 将字节转换为字符
        for ($j = 0; $j < count($bytes); $j++) {
            $result .= chr($bytes[$j]);
        }
    }
    
    return $result;
}