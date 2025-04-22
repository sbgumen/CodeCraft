/**
 * 算法特定选项处理
 */

// 算法需要的额外选项
const algorithmOptions = {
    // AES 加密/解密选项
    aes: {
        encode: [
            {
                id: 'aes-mode',
                type: 'select',
                label: 'AES 模式',
                options: [
                    { value: 'CBC', label: 'CBC (默认)' },
                    { value: 'ECB', label: 'ECB' },
                    { value: 'CTR', label: 'CTR' },
                    { value: 'OFB', label: 'OFB' },
                    { value: 'CFB', label: 'CFB' }
                ]
            },
            {
                id: 'aes-key',
                type: 'text',
                label: '密钥 (可选，留空随机生成)',
                placeholder: '输入32个字符的密钥或留空自动生成'
            },
            {
                id: 'aes-iv',
                type: 'text',
                label: '初始化向量 (可选，留空随机生成)',
                placeholder: '输入16个字符的IV或留空自动生成'
            }
        ],
        decode: [
            {
                id: 'aes-mode',
                type: 'select',
                label: 'AES 模式',
                options: [
                    { value: 'CBC', label: 'CBC (默认)' },
                    { value: 'ECB', label: 'ECB' },
                    { value: 'CTR', label: 'CTR' },
                    { value: 'OFB', label: 'OFB' },
                    { value: 'CFB', label: 'CFB' }
                ]
            },
            {
                id: 'aes-key',
                type: 'text',
                label: '密钥',
                placeholder: '输入密钥',
                required: true
            },
            {
                id: 'aes-iv',
                type: 'text',
                label: '初始化向量 (ECB模式不需要)',
                placeholder: '输入初始化向量'
            }
        ]
    },
    
    // RSA 加密/解密选项
    rsa: {
        encode: [
            {
                id: 'rsa-key-size',
                type: 'select',
                label: '密钥大小',
                options: [
                    { value: '2048', label: '2048位 (推荐)' },
                    { value: '4096', label: '4096位 (更安全，但更慢)' }
                ]
            },
            {
                id: 'rsa-public-key',
                type: 'textarea',
                label: '公钥 (可选，留空自动生成密钥对)',
                placeholder: '-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----'
            }
        ],
        decode: [
            {
                id: 'rsa-private-key',
                type: 'textarea',
                label: '私钥',
                placeholder: '-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----',
                required: true
            }
        ]
    },
    
    // 凯撒密码选项
    caesar: {
        encode: [
            {
                id: 'caesar-shift',
                type: 'number',
                label: '移位量',
                min: 1,
                max: 25,
                value: 3
            }
        ],
        decode: [
            {
                id: 'caesar-shift',
                type: 'number',
                label: '移位量',
                min: 1,
                max: 25,
                value: 3
            }
        ]
    },
    
    // 维吉尼亚密码选项
    vigenere: {
        encode: [
            {
                id: 'vigenere-key',
                type: 'text',
                label: '密钥',
                placeholder: '输入密钥（仅字母）',
                required: true
            }
        ],
        decode: [
            {
                id: 'vigenere-key',
                type: 'text',
                label: '密钥',
                placeholder: '输入密钥（仅字母）',
                required: true
            }
        ]
    },
    
    // JWT选项
    jwt: {
        encode: [
            {
                id: 'jwt-secret',
                type: 'text',
                label: '签名密钥',
                placeholder: '输入签名密钥',
                required: true
            },
            {
                id: 'jwt-algorithm',
                type: 'select',
                label: '签名算法',
                options: [
                    { value: 'HS256', label: 'HS256 (默认)' },
                    { value: 'HS384', label: 'HS384' },
                    { value: 'HS512', label: 'HS512' }
                ]
            }
        ],
        decode: [
            {
                id: 'jwt-secret',
                type: 'text',
                label: '签名密钥',
                placeholder: '输入签名密钥',
                required: true
            }
        ]
    }
};

/**
 * 创建特定算法的选项UI
 * @param {string} algorithm - 算法名称
 * @param {string} mode - 'encode' 或 'decode'
 * @returns {HTMLElement|null} - 选项容器或null
 */
function createAlgorithmOptions(algorithm, mode) {
    // 如果算法没有特定选项，返回null
    if (!algorithmOptions[algorithm] || !algorithmOptions[algorithm][mode]) {
        return null;
    }
    
    const options = algorithmOptions[algorithm][mode];
    
    // 创建选项容器
    const container = document.createElement('div');
    container.className = 'algorithm-options';
    
    // 添加选项标题
    const title = document.createElement('h4');
    title.textContent = `${algorithm.toUpperCase()} 选项`;
    container.appendChild(title);
    
    // 添加每个选项
    options.forEach(option => {
        const group = document.createElement('div');
        group.className = 'option-group';
        
        const label = document.createElement('label');
        label.setAttribute('for', option.id);
        label.textContent = option.label;
        group.appendChild(label);
        
        let input;
        
        // 根据选项类型创建不同的输入元素
        switch (option.type) {
            case 'select':
                const selectWrapper = document.createElement('div');
                selectWrapper.className = 'select-wrapper';
                
                const select = document.createElement('select');
                select.id = option.id;
                
                option.options.forEach(opt => {
                    const optElement = document.createElement('option');
                    optElement.value = opt.value;
                    optElement.textContent = opt.label;
                    select.appendChild(optElement);
                });
                
                selectWrapper.appendChild(select);
                
                const icon = document.createElement('i');
                icon.className = 'fas fa-chevron-down';
                selectWrapper.appendChild(icon);
                
                group.appendChild(selectWrapper);
                break;
                
            case 'textarea':
                input = document.createElement('textarea');
                input.id = option.id;
                input.placeholder = option.placeholder || '';
                
                if (option.required) {
                    input.setAttribute('required', 'required');
                }
                
                group.appendChild(input);
                break;
                
            default: // 文本、数字等
                input = document.createElement('input');
                input.id = option.id;
                input.type = option.type;
                input.placeholder = option.placeholder || '';
                
                if (option.min !== undefined) {
                    input.min = option.min;
                }
                
                if (option.max !== undefined) {
                    input.max = option.max;
                }
                
                if (option.value !== undefined) {
                    input.value = option.value;
                }
                
                if (option.required) {
                    input.setAttribute('required', 'required');
                }
                
                group.appendChild(input);
        }
        
        container.appendChild(group);
    });
    
    return container;
}

/**
 * 更新算法选项区域
 * @param {string} algorithm - 选择的算法
 * @param {string} mode - 'encode' 或 'decode'
 */
function updateAlgorithmOptions(algorithm, mode) {
    // 获取选项容器
    const containerSelector = mode === 'encode' ? '.encode-options' : '.decode-options';
    const container = document.querySelector(containerSelector);
    
    if (!container) return;
    
    // 清空容器
    container.innerHTML = '';
    
    // 创建新选项
    const optionsElement = createAlgorithmOptions(algorithm, mode);
    
    if (optionsElement) {
        container.appendChild(optionsElement);
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

/**
 * 获取算法选项值
 * @param {string} algorithm - 算法名称
 * @param {string} mode - 'encode' 或 'decode'
 * @returns {Object} - 选项值对象
 */
function getAlgorithmOptions(algorithm, mode) {
    const options = {};
    
    // 如果算法没有特定选项，返回空对象
    if (!algorithmOptions[algorithm] || !algorithmOptions[algorithm][mode]) {
        return options;
    }
    
    // 获取每个选项的值
    algorithmOptions[algorithm][mode].forEach(option => {
        const element = document.getElementById(option.id);
        
        if (element) {
            options[option.id] = element.value;
        }
    });
    
    return options;
}

// 当DOM加载完成时初始化
document.addEventListener('DOMContentLoaded', function() {
    // 获取算法选择下拉列表
    const encodeAlgorithmSelect = document.getElementById('encoding-algorithm');
    const decodeAlgorithmSelect = document.getElementById('decoding-algorithm');
    
    // 创建算法选项容器
    const encodeTab = document.getElementById('encode-tab');
    const decodeTab = document.getElementById('decode-tab');
    
    if (encodeTab) {
        const encodeOptionsContainer = document.createElement('div');
        encodeOptionsContainer.className = 'encode-options';
        encodeOptionsContainer.style.display = 'none';
        encodeTab.insertBefore(encodeOptionsContainer, document.getElementById('encode-btn'));
    }
    
    if (decodeTab) {
        const decodeOptionsContainer = document.createElement('div');
        decodeOptionsContainer.className = 'decode-options';
        decodeOptionsContainer.style.display = 'none';
        decodeTab.insertBefore(decodeOptionsContainer, document.getElementById('decode-btn'));
    }
    
    // 监听算法选择变化
    if (encodeAlgorithmSelect) {
        encodeAlgorithmSelect.addEventListener('change', function() {
            updateAlgorithmOptions(this.value, 'encode');
        });
    }
    
    if (decodeAlgorithmSelect) {
        decodeAlgorithmSelect.addEventListener('change', function() {
            updateAlgorithmOptions(this.value, 'decode');
        });
    }
});