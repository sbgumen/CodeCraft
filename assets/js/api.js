/**
 * CodeCraft - API调用处理
 */

// API基础URL，生产环境中应替换为实际域名
// 修改为相对路径，避免路径问题
const API_BASE_URL = '/api';

/**
 * 构建完整API URL
 * @param {string} endpoint - API端点路径
 * @returns {string} 完整的API URL
 */
function buildApiUrl(endpoint) {
    return `${API_BASE_URL}/${endpoint}`;
}

/**
 * 通用API请求函数
 * @param {string} url - 请求URL
 * @param {string} method - 请求方法 (GET, POST, etc.)
 * @param {Object} data - 请求数据
 * @returns {Promise<Object>} 响应数据
 */
async function apiRequest(url, method = 'GET', data = null) {
    try {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            }
        };
        
        // 如果有数据并且是POST请求，添加请求体
        if (data && method === 'POST') {
            const formData = new URLSearchParams();
            
            for (const key in data) {
                if (data.hasOwnProperty(key)) {
                    formData.append(key, data[key]);
                }
            }
            
            options.body = formData;
        }
        
        // 发送请求
        const response = await fetch(url, options);
        
        // 检查HTTP状态码
        if (!response.ok) {
            // 尝试获取错误响应
            const errorData = await response.json().catch(() => null);
            
            if (errorData && errorData.error) {
                throw errorData;
            } else {
                throw new Error(`HTTP错误: ${response.status}`);
            }
        }
        
        // 解析JSON响应
        const responseData = await response.json();
        return responseData;
    } catch (error) {
        console.error('API请求错误:', error);
        
        // 如果已经是格式化的错误对象，直接返回
        if (error.error && error.success === false) {
            return error;
        }
        
        // 否则创建一个标准错误响应
        return {
            success: false,
            error: {
                code: 'API_REQUEST_ERROR',
                message: error.message || '未知错误'
            }
        };
    }
}

/**
 * 编码文本
 * @param {string} text - 要编码的文本
 * @param {string} algorithm - 编码算法
 * @returns {Promise<Object>} 编码结果
 */
async function encodeText(text, algorithm, options = {}) {
    const url = buildApiUrl('encode.php');
    const data = {
        text,
        algorithm,
        ...options
    };
    
    return apiRequest(url, 'POST', data);
}

/**
 * 解码文本
 * @param {string} text - 要解码的文本
 * @param {string} algorithm - 解码算法
 * @returns {Promise<Object>} 解码结果
 */
async function decodeText(text, algorithm, options = {}) {
    const url = buildApiUrl('decode.php');
    const data = {
        text,
        algorithm,
        ...options
    };
    
    return apiRequest(url, 'POST', data);
}
/**
 * 获取支持的算法列表
 * @returns {Promise<Object>} 支持的算法列表
 */
async function getSupportedAlgorithms() {
    const url = buildApiUrl('index.php?action=algorithms');
    return apiRequest(url);
}

/**
 * 检查API状态
 * @returns {Promise<Object>} API状态信息
 */
async function checkApiStatus() {
    const url = buildApiUrl('index.php?action=status');
    return apiRequest(url);
}

// 导出API函数
window.CodeCraftAPI = {
    encodeText,
    decodeText,
    getSupportedAlgorithms,
    checkApiStatus
};