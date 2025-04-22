/**
 * CodeCraft - 主前端逻辑
 //main.js
/**
 * CodeCraft - 主前端逻辑
 */

document.addEventListener('DOMContentLoaded', function() {
    // 获取DOM元素
    const inputText = document.getElementById('input-text');
    const decodeInputText = document.getElementById('decode-input-text');
    const outputText = document.getElementById('output-text');
    const decodeOutputText = document.getElementById('decode-output-text');
    const encodingAlgorithm = document.getElementById('encoding-algorithm');
    const decodingAlgorithm = document.getElementById('decoding-algorithm');
    const encodeBtn = document.getElementById('encode-btn');
    const decodeBtn = document.getElementById('decode-btn');
    const copyBtn = document.getElementById('copy-btn');
    const decodeCopyBtn = document.getElementById('decode-copy-btn');
    const loadingOverlay = document.getElementById('loading-overlay');

    // 减少闪烁的功能标志
    // 文件上传全局变量
    //let selectedFile = null;
   // let selectedDecodeFile = null;
    //const maxFileSize = 10 * 1024 * 1024; // 10MB
    let isProcessing = false;

    // 初始化文件上传
    //initFileUpload();

    // 编码按钮点击事件
    if (encodeBtn) {
        encodeBtn.addEventListener('click', function() {
            if (isProcessing) return;
            
            // 获取选择的算法
            const algorithm = encodingAlgorithm.value;
            
            // 如果有选择文件且文件处理支持该算法，使用文件处理
            if (selectedFile && isFileProcessingSupported(algorithm, 'encode')) {
                encodeFile(algorithm);
                return;
            }
            
            const text = inputText.value.trim();
            
            if (!text) {
                showToast('请输入要编码的文本', 'warning');
                return;
            }
            
            // 获取算法特定选项
            const options = getAlgorithmOptions(algorithm, 'encode');
            
            isProcessing = true;
            showLoading();
            
            // 播放编码动画
            playEncodeAnimation(algorithm);
            
            // 调用API
            encodeText(text, algorithm, options)
                .then(response => {
                    if (response.success) {
                        outputText.value = response.data.encoded;
                        
                        // 如果有密钥信息，显示它
                        showKeyInfo(response.data, algorithm, 'encode');
                        
                        // 显示成功消息
                        showToast('编码成功完成!', 'success');
                    } else {
                        outputText.value = '';
                        showToast(`编码错误: ${response.error.message}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('编码出错:', error);
                    showToast('服务器错误，请稍后重试', 'error');
                })
                .finally(() => {
                    hideLoading();
                    isProcessing = false;
                });
        });
    }

    // 解码按钮点击事件
    if (decodeBtn) {
        decodeBtn.addEventListener('click', function() {
            if (isProcessing) return;
            
            // 获取选择的算法
            const algorithm = decodingAlgorithm.value;
            
            // 如果有选择文件且文件处理支持该算法，使用文件处理
            if (selectedFile && isFileProcessingSupported(algorithm, 'decode')) {
                decodeFile(algorithm);
                return;
            }
            
            const text = decodeInputText.value.trim();
            
            if (!text) {
                showToast('请输入要解码的文本', 'warning');
                return;
            }
            
            // 获取算法特定选项
            const options = getAlgorithmOptions(algorithm, 'decode');
            
            isProcessing = true;
            showLoading();
            
            // 播放解码动画
            playDecodeAnimation(algorithm);
            
            // 调用API
            decodeText(text, algorithm, options)
                .then(response => {
                    if (response.success) {
                        decodeOutputText.value = response.data.decoded;
                        
                        // 显示成功消息
                        showToast('解码成功完成!', 'success');
                    } else {
                        decodeOutputText.value = '';
                        showToast(`解码错误: ${response.error.message}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('解码出错:', error);
                    showToast('服务器错误，请稍后重试', 'error');
                })
                .finally(() => {
                    hideLoading();
                    isProcessing = false;
                });
        });
    }

    // 当选择算法改变时更新UI
    if (encodingAlgorithm) {
        encodingAlgorithm.addEventListener('change', function() {
            const selectedAlgorithm = this.value;
            
            // 更新算法特定选项
            if (typeof updateAlgorithmOptions === 'function') {
                updateAlgorithmOptions(selectedAlgorithm, 'encode');
            }
            
            // 例如，如果选择了MD5，可能会显示一些附加信息
            const noteMd5 = document.querySelector('.note-md5');
            if (noteMd5) {
                const hashAlgorithms = ['md5', 'sha1', 'sha256', 'sha512'];
                noteMd5.style.display = hashAlgorithms.includes(selectedAlgorithm) ? 'flex' : 'none';
            }
            
            // 清除之前的密钥信息
            clearKeyInfo();
        });
    }

    if (decodingAlgorithm) {
        decodingAlgorithm.addEventListener('change', function() {
            const selectedAlgorithm = this.value;
            
            // 更新算法特定选项
            if (typeof updateAlgorithmOptions === 'function') {
                updateAlgorithmOptions(selectedAlgorithm, 'decode');
            }
            
            // 清除之前的密钥信息
            clearKeyInfo();
        });
    }

    // 监听键盘事件，支持按Enter键执行编码/解码
    document.addEventListener('keydown', function(event) {
        // 如果正在处理请求，不执行任何操作
        if (isProcessing) return;
        
        // 按Enter键
        if (event.key === 'Enter' && event.ctrlKey) {
            const activeTab = document.querySelector('.tab-btn.active');
            if (activeTab) {
                const tabId = activeTab.getAttribute('data-tab');
                
                if (tabId === 'encode' && document.activeElement === inputText) {
                    encodeBtn.click();
                } else if (tabId === 'decode' && document.activeElement === decodeInputText) {
                    decodeBtn.click();
                }
            }
        }
    });

    // 添加文件编码相关函数
    /**
     * 检查算法是否支持文件处理
     * @param {string} algorithm - 算法名称
     * @param {string} mode - 'encode' 或 'decode'
     * @returns {boolean} - 是否支持
     */
     
     
    function isFileProcessingSupported(algorithm, mode) {
        // 支持的文件处理算法
        const supportedAlgorithms = {
            encode: ['base64', 'hex', 'binary', 'aes', 'rsa', 'caesar', 'vigenere', 'rot13'],
            decode: ['base64', 'hex', 'binary', 'aes', 'rsa', 'caesar', 'vigenere', 'rot13']
        };
        
        return supportedAlgorithms[mode].includes(algorithm);
    }

    /**
     * 编码文件
     * @param {string} algorithm - 编码算法
     */
    function encodeFile(algorithm) {
        if (!selectedFile) {
            showToast('请先选择文件', 'warning');
            return;
        }
        
        isProcessing = true;
        showLoading();
        
        // 获取算法特定选项
        const options = getAlgorithmOptions(algorithm, 'encode');
        
        // 处理文件
        processFileEncoding(algorithm)
            .then(result => {
                if (result) {
                    // 显示成功消息
                    showToast('文件编码成功!', 'success');
                    
                    // 如果有密钥信息，显示它
                    showKeyInfo(result, algorithm, 'encode');
                    
                    // 提供下载链接
                    downloadProcessedFile(result, algorithm);
                }
            })
            .finally(() => {
                hideLoading();
                isProcessing = false;
            });
    }

    /**
     * 解码文件
     * @param {string} algorithm - 解码算法
     */
    function decodeFile(algorithm) {
        if (!selectedFile) {
            showToast('请先选择文件', 'warning');
            return;
        }
        
        isProcessing = true;
        showLoading();
        
        // 获取算法特定选项
        const options = getAlgorithmOptions(algorithm, 'decode');
        
        // 处理文件
        processFileDecoding(algorithm)
            .then(result => {
                if (result) {
                    // 显示成功消息
                    showToast('文件解码成功!', 'success');
                    
                    // 提供下载链接
                    downloadProcessedFile(result, algorithm);
                }
            })
            .finally(() => {
                hideLoading();
                isProcessing = false;
            });
    }

    /**
     * 显示密钥信息
     * @param {Object} data - API响应数据
     * @param {string} algorithm - 算法
     * @param {string} mode - 模式 ('encode' 或 'decode')
     */
    function showKeyInfo(data, algorithm, mode) {
        // 找到密钥信息容器
        const containerSelector = mode === 'encode' ? '.encode-options' : '.decode-options';
        const container = document.querySelector(containerSelector);
        
        if (!container) return;
        
        // 检查是否已存在密钥显示
        let keyDisplay = container.querySelector('.key-display');
        if (keyDisplay) {
            keyDisplay.remove();
        }
        
        // 根据算法创建密钥信息
        if (algorithm === 'aes' && data.key) {
            keyDisplay = document.createElement('div');
            keyDisplay.className = 'key-display';
            
            keyDisplay.innerHTML = `
                <h5>AES 密钥信息</h5>
                <p><strong>密钥:</strong> ${data.key}</p>
                ${data.iv ? `<p><strong>IV:</strong> ${data.iv}</p>` : ''}
                <p><strong>模式:</strong> ${data.mode || 'CBC'}</p>
                <button class="copy-key" data-key="${data.key}">
                    <i class="fas fa-copy"></i> 复制密钥
                </button>
            `;
            
            container.appendChild(keyDisplay);
            
            // 添加复制功能
            const copyBtn = keyDisplay.querySelector('.copy-key');
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const key = this.getAttribute('data-key');
                    navigator.clipboard.writeText(key).then(
                        () => showToast('密钥已复制到剪贴板', 'success'),
                        () => showToast('复制失败', 'error')
                    );
                });
            }
        } else if (algorithm === 'rsa') {
            if (data.publicKey && data.privateKey) {
                keyDisplay = document.createElement('div');
                keyDisplay.className = 'key-display';
                
                keyDisplay.innerHTML = `
                    <h5>RSA 密钥对</h5>
                    <p><strong>公钥:</strong></p>
                    <textarea readonly style="height: 80px; font-size: 12px;">${data.publicKey}</textarea>
                    <p><strong>私钥:</strong></p>
                    <textarea readonly style="height: 120px; font-size: 12px;">${data.privateKey}</textarea>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button class="copy-key" data-key="${data.publicKey}">
                            <i class="fas fa-copy"></i> 复制公钥
                        </button>
                        <button class="copy-key" data-key="${data.privateKey}">
                            <i class="fas fa-copy"></i> 复制私钥
                        </button>
                    </div>
                `;
                
                container.appendChild(keyDisplay);
                
                // 添加复制功能
                const copyBtns = keyDisplay.querySelectorAll('.copy-key');
                copyBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const key = this.getAttribute('data-key');
                        navigator.clipboard.writeText(key).then(
                            () => showToast('密钥已复制到剪贴板', 'success'),
                            () => showToast('复制失败', 'error')
                        );
                    });
                });
            }
        } else if (algorithm === 'vigenere' && data.key) {
            keyDisplay = document.createElement('div');
            keyDisplay.className = 'key-display';
            
            keyDisplay.innerHTML = `
                <h5>维吉尼亚密钥</h5>
                <p><strong>密钥:</strong> ${data.key}</p>
                <button class="copy-key" data-key="${data.key}">
                    <i class="fas fa-copy"></i> 复制密钥
                </button>
            `;
            
            container.appendChild(keyDisplay);
            
            // 添加复制功能
            const copyBtn = keyDisplay.querySelector('.copy-key');
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const key = this.getAttribute('data-key');
                    navigator.clipboard.writeText(key).then(
                        () => showToast('密钥已复制到剪贴板', 'success'),
                        () => showToast('复制失败', 'error')
                    );
                });
            }
        } else if (algorithm === 'caesar' && data.shift) {
            keyDisplay = document.createElement('div');
            keyDisplay.className = 'key-display';
            
            keyDisplay.innerHTML = `
                <h5>凯撒密码移位</h5>
                <p><strong>移位量:</strong> ${data.shift}</p>
            `;
            
            container.appendChild(keyDisplay);
        }
    }

    /**
     * 清除密钥信息
     */
    function clearKeyInfo() {
        const keyDisplays = document.querySelectorAll('.key-display');
        keyDisplays.forEach(display => display.remove());
    }

    // 添加滚动监听，显示/隐藏返回顶部按钮
    window.addEventListener('scroll', handleScroll);
    
    // 初始状态创建元素
    createToastContainer();
    createScrollToTopButton();
    
    // 初始调用一次滚动处理函数
    handleScroll();
    
    // 创建更多的交互体验增强功能
    enhanceUserExperience();
});

/**
 * 显示加载中覆盖层
 */
function showLoading() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.classList.add('show');
    }
}

/**
 * 隐藏加载中覆盖层
 */
function hideLoading() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        setTimeout(() => {
            loadingOverlay.classList.remove('show');
        }, 300); // 小延迟，让动画看起来更流畅
    }
}

/**
 * 创建Toast消息容器
 */
function createToastContainer() {
    if (!document.querySelector('.toast-container')) {
        const toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
}

/**
 * 显示Toast消息
 * @param {string} message - 消息内容
 * @param {string} type - 消息类型 (success, error, warning, info)
 */
function showToast(message, type = 'info') {
    const container = document.querySelector('.toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = '';
    switch (type) {
        case 'success':
            icon = '<i class="fas fa-check-circle"></i>';
            break;
        case 'error':
            icon = '<i class="fas fa-times-circle"></i>';
            break;
        case 'warning':
            icon = '<i class="fas fa-exclamation-triangle"></i>';
            break;
        default:
            icon = '<i class="fas fa-info-circle"></i>';
    }
    
    toast.innerHTML = `
        <div class="toast-content">
            <div class="toast-icon">${icon}</div>
            <div class="toast-message">${message}</div>
        </div>
        <div class="toast-progress"></div>
    `;
    
    container.appendChild(toast);
    
    // 淡入效果
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // 等待一段时间后移除
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300); // 等待淡出动画完成
    }, 3000);
}




// 输入模式切换
const modeBtns = document.querySelectorAll('.mode-btn');
const inputModes = document.querySelectorAll('.input-mode');

modeBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        // 移除所有激活状态
        modeBtns.forEach(b => b.classList.remove('active'));
        inputModes.forEach(m => m.classList.remove('active'));
        
        // 激活当前选中的模式
        this.classList.add('active');
        const mode = this.getAttribute('data-mode');
        document.getElementById(`${mode}-input-mode`).classList.add('active');
        
        // 根据模式更新编码按钮文本
        const encodeBtn = document.getElementById('encode-btn');
        if (encodeBtn) {
            const btnText = encodeBtn.querySelector('.btn-text');
            if (btnText) {
                btnText.textContent = mode === 'file' ? '处理文件' : '编码';
            }
        }
    });
});


 //解码部分的输入模式切换
const decodeModeBtns = document.querySelectorAll('.mode-btn[data-target="decode"]');
const decodeInputModes = document.querySelectorAll('.input-mode[id*="decode"]');

decodeModeBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        // 移除所有激活状态
        decodeModeBtns.forEach(b => b.classList.remove('active'));
        decodeInputModes.forEach(m => m.classList.remove('active'));
        
        // 激活当前选中的模式
        this.classList.add('active');
        const mode = this.getAttribute('data-mode');
        document.getElementById(`${mode}-input-mode-decode`).classList.add('active');
        
        // 根据模式更新解码按钮文本
        const decodeBtn = document.getElementById('decode-btn');
        if (decodeBtn) {
            const btnText = decodeBtn.querySelector('.btn-text');
            if (btnText) {
                btnText.textContent = mode === 'file' ? '处理文件' : '解码';
            }
        }
    });
});



/**
 * 创建返回顶部按钮
 */
function createScrollToTopButton() {
    if (!document.querySelector('.scroll-to-top')) {
        const scrollBtn = document.createElement('button');
        scrollBtn.className = 'scroll-to-top';
        scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        scrollBtn.title = '回到顶部';
        
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        document.body.appendChild(scrollBtn);
    }
}

/**
 * 处理滚动事件
 */
function handleScroll() {
    const scrollBtn = document.querySelector('.scroll-to-top');
    
    if (scrollBtn) {
        if (window.pageYOffset > 200) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
    }
}

/**
 * 增强用户体验的附加功能
 */
function enhanceUserExperience() {
    // 为表单元素添加焦点效果
    const formElements = document.querySelectorAll('input, textarea, select');
    
    formElements.forEach(element => {
        // 添加焦点类
        element.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        // 移除焦点类
        element.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
        
        // 输入时添加动画效果
        if (element.tagName.toLowerCase() === 'textarea') {
            element.addEventListener('input', function() {
                if (!this.classList.contains('typing')) {
                    this.classList.add('typing');
                    setTimeout(() => {
                        this.classList.remove('typing');
                    }, 500);
                }
            });
        }
    });

    // 添加CSS变量来支持动态效果
    document.documentElement.style.setProperty('--input-characters', '0');
    
    // 监听输入字符数
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            document.documentElement.style.setProperty('--input-characters', this.value.length);
        });
    });

    // 添加自动调整高度功能
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });

    // 添加页面离开确认（如果有未保存的数据）
    window.addEventListener('beforeunload', function(e) {
        const inputTextValue = document.getElementById('input-text')?.value || '';
        const decodeInputTextValue = document.getElementById('decode-input-text')?.value || '';
        const outputTextValue = document.getElementById('output-text')?.value || '';
        const decodeOutputTextValue = document.getElementById('decode-output-text')?.value || '';
        
        // 如果有任何输入或输出数据，提示用户确认
        if (inputTextValue || decodeInputTextValue || outputTextValue || decodeOutputTextValue) {
            // 现代浏览器不再显示自定义消息，但仍需要设置
            const confirmationMessage = '您有未保存的数据，确定要离开吗？';
            e.returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });
}

// 添加CSS样式用于Toast和滚动按钮
const style = document.createElement('style');
style.textContent = `
    /* Toast容器 */
    .toast-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    /* Toast样式 */
    .toast {
        min-width: 250px;
        max-width: 350px;
        background: var(--glass-bg);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-radius: var(--border-radius);
        border: 1px solid var(--glass-border);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        padding: 0;
        overflow: hidden;
        transform: translateX(100%);
        opacity: 0;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }
    
    .toast.show {
        transform: translateX(0);
        opacity: 1;
    }
    
    .toast-content {
        display: flex;
        align-items: center;
        padding: 15px;
    }
    
    .toast-icon {
        margin-right: 10px;
        font-size: 20px;
    }
    
    .toast-success .toast-icon {
        color: var(--success-color);
    }
    
    .toast-error .toast-icon {
        color: var(--error-color);
    }
    
    .toast-warning .toast-icon {
        color: var(--warning-color);
    }
    
    .toast-info .toast-icon {
        color: var(--info-color);
    }
    
    .toast-message {
        color: var(--text-color);
        font-size: 14px;
    }
    
    .toast-progress {
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        width: 100%;
        animation: toast-progress 3s linear forwards;
    }
    
    @keyframes toast-progress {
        from {
            width: 100%;
        }
        to {
            width: 0;
        }
    }
    
    /* 滚动到顶部按钮 */
    .scroll-to-top {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px);
        transition: opacity 0.3s ease, transform 0.3s ease, visibility 0.3s ease, background 0.3s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border: none;
        z-index: 99;
    }
    
    .scroll-to-top.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .scroll-to-top:hover {
        background: var(--primary-dark);
        transform: translateY(-3px);
    }
    
    .scroll-to-top i {
        font-size: 18px;
    }
    
    /* 输入框焦点效果 */
    .input-group, .algorithm-selection, .output-group {
        transition: transform 0.3s ease;
    }
    
    .input-group.focused, .algorithm-selection.focused, .output-group.focused {
        transform: translateY(-5px);
    }
    
    /* 输入动画效果 */
    textarea.typing {
        animation: typing-pulse 0.5s ease;
    }
    
    @keyframes typing-pulse {
        0% {
            box-shadow: 0 0 0 rgba(52, 152, 219, 0.4);
        }
        50% {
            box-shadow: 0 0 10px rgba(52, 152, 219, 0.6);
        }
        100% {
            box-shadow: 0 0 0 rgba(52, 152, 219, 0.4);
        }
    }
`;

document.head.appendChild(style);