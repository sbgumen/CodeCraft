/**
 * 文件上传与处理功能 - 修复版
 */

// 文件上传全局变量
let selectedFile = null;
let selectedDecodeFile = null; // 添加解码文件变量
const maxFileSize = 10 * 1024 * 1024; // 10MB


// 格式化文件大小为可读格式
function formatFileSize(bytes) {
    if (bytes === 0) return '0 字节';
    
    const units = ['字节', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    
    // 保留两位小数并去除末尾的0
    return (bytes / Math.pow(1024, i)).toFixed(2).replace(/\.0+$|(\.[0-9]*[1-9])0+$/, '$1') + ' ' + units[i];
}

// 初始化文件上传组件
function initFileUpload() {
    // 编码标签页文件上传
    initEncodingFileUpload();
    
    // 解码标签页文件上传
    initDecodingFileUpload();
    
    // 初始化模式切换按钮
    initModeToggle();
}

// 初始化编码标签页的文件上传
function initEncodingFileUpload() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('file-input');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeFileBtn = document.getElementById('removeFile');
    
    if (!dropZone || !fileInput) return;
    
    // 文件选择处理
    fileInput.addEventListener('change', function(e) {
        e.stopPropagation(); // 阻止事件冒泡，防止重复触发
        handleFileSelect(e.target.files);
    });
    
    // 拖放事件
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('drag-over');
    });
    
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
        
        if (e.dataTransfer.files.length) {
            handleFileSelect(e.dataTransfer.files);
        }
    });
    
    // 点击区域选择文件
    dropZone.addEventListener('click', function(e) {
        e.stopPropagation(); // 阻止事件冒泡，防止重复触发
        fileInput.click();
    });
    
    // 移除文件
    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            resetFileUpload();
        });
    }
}

// 初始化解码标签页的文件上传
function initDecodingFileUpload() {
    const decodeDropZone = document.getElementById('decode-dropZone');
    const decodeFileInput = document.getElementById('decode-file-input');
    const decodeFileInfo = document.getElementById('decode-fileInfo');
    const decodeFileName = document.getElementById('decode-fileName');
    const decodeFileSize = document.getElementById('decode-fileSize');
    const decodeRemoveFileBtn = document.getElementById('decode-removeFile');
    
    if (!decodeDropZone || !decodeFileInput) return;
    
    // 文件选择处理
    decodeFileInput.addEventListener('change', function(e) {
        e.stopPropagation(); // 阻止事件冒泡，防止重复触发
        handleDecodeFileSelect(e.target.files);
    });
    
    // 拖放事件
    decodeDropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('drag-over');
    });
    
    decodeDropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
    });
    
    decodeDropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
        
        if (e.dataTransfer.files.length) {
            handleDecodeFileSelect(e.target.files);
        }
    });
    
    // 点击区域选择文件
    decodeDropZone.addEventListener('click', function(e) {
        e.stopPropagation(); // 阻止事件冒泡，防止重复触发
        decodeFileInput.click();
    });
    
    // 移除文件
    if (decodeRemoveFileBtn) {
        decodeRemoveFileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            resetDecodeFileUpload();
        });
    }
}

// 初始化模式切换
function initModeToggle() {
    // 编码模式切换
    const encodeModeBtns = document.querySelectorAll('.mode-btn:not([data-target="decode"])');
    if (encodeModeBtns.length) {
        encodeModeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const mode = this.getAttribute('data-mode');
                updateEncodeButtonText(mode);
            });
        });
    }
    
    // 解码模式切换
    const decodeModeBtns = document.querySelectorAll('.mode-btn[data-target="decode"]');
    if (decodeModeBtns.length) {
        decodeModeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const mode = this.getAttribute('data-mode');
                updateDecodeButtonText(mode);
            });
        });
    }
}

// 更新编码按钮文字
function updateEncodeButtonText(mode) {
    const encodeBtn = document.getElementById('encode-btn');

    if (encodeBtn) {
        const btnText = encodeBtn.querySelector('.btn-text');
        if (btnText) {
            btnText.textContent = mode === 'file' ? '处理文件' : '编码';
        }
    }
}

// 更新解码按钮文字
function updateDecodeButtonText(mode) {
    const decodeBtn = document.getElementById('decode-btn');

    if (decodeBtn) {
        const btnText = decodeBtn.querySelector('.btn-text');
        if (btnText) {
            btnText.textContent = mode === 'file' ? '处理文件' : '解码';
        }
    }
}

// 处理文件选择
function handleFileSelect(files) {
    if (files.length === 0) return;
    
    const file = files[0];
    
    // 检查文件大小
    if (file.size > maxFileSize) {
        showToast(`文件过大，最大限制为${formatFileSize(maxFileSize)}`, 'error');
        return;
    }
    
    selectedFile = file;
    
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileInfo = document.getElementById('fileInfo');
    const dropZone = document.getElementById('dropZone');
    
    if (fileName && fileSize) {
        // 更新UI
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
    }
    
    // 设置文件图标
    setFileIcon(file.type);
    
    // 显示文件信息，隐藏拖放区
    if (dropZone && fileInfo) {
        dropZone.style.display = 'none';
        fileInfo.style.display = 'block';
    }
    
    // 自动切换到文件模式
    switchToFileMode();
}

// 处理解码文件选择
function handleDecodeFileSelect(files) {
    if (files.length === 0) return;
    
    const file = files[0];
    
    // 检查文件大小
    if (file.size > maxFileSize) {
        showToast(`文件过大，最大限制为${formatFileSize(maxFileSize)}`, 'error');
        return;
    }
    
    selectedDecodeFile = file;
    
    const fileName = document.getElementById('decode-fileName');
    const fileSize = document.getElementById('decode-fileSize');
    const fileInfo = document.getElementById('decode-fileInfo');
    const dropZone = document.getElementById('decode-dropZone');
    
    if (fileName && fileSize) {
        // 更新UI
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
    }
    
    // 设置文件图标
    setDecodeFileIcon(file.type);
    
    // 显示文件信息，隐藏拖放区
    if (dropZone && fileInfo) {
        dropZone.style.display = 'none';
        fileInfo.style.display = 'block';
    }
    
    // 自动切换到文件模式
    switchToDecodeFileMode();
}

// 切换到文件模式
function switchToFileMode() {
    // 查找文件模式按钮并点击
    const fileTab = document.querySelector('.mode-btn[data-mode="file"]:not([data-target="decode"])');
    if (fileTab) {
        fileTab.click();
    }
    
    // 直接更新按钮文本，以防点击事件未生效
    updateEncodeButtonText('file');
}

// 切换到解码文件模式
function switchToDecodeFileMode() {
    // 查找解码文件模式按钮并点击
    const fileTab = document.querySelector('.mode-btn[data-mode="file"][data-target="decode"]');
    if (fileTab) {
        fileTab.click();
    }
    
    // 直接更新按钮文本，以防点击事件未生效
    updateDecodeButtonText('file');
}

// 重置文件上传
function resetFileUpload() {
    selectedFile = null;
    
    const fileInput = document.getElementById('file-input');
    const dropZone = document.getElementById('dropZone');
    const fileInfo = document.getElementById('fileInfo');
    
    if (fileInput) {
        fileInput.value = '';
    }
    
    // 重设UI
    if (dropZone && fileInfo) {
        dropZone.style.display = 'block';
        fileInfo.style.display = 'none';
    }
}

// 重置解码文件上传
function resetDecodeFileUpload() {
    selectedDecodeFile = null;
    
    const fileInput = document.getElementById('decode-file-input');
    const dropZone = document.getElementById('decode-dropZone');
    const fileInfo = document.getElementById('decode-fileInfo');
    
    if (fileInput) {
        fileInput.value = '';
    }
    
    // 重设UI
    if (dropZone && fileInfo) {
        dropZone.style.display = 'block';
        fileInfo.style.display = 'none';
    }
}

// 根据文件类型设置图标
function setFileIcon(mimeType) {
    const iconElement = document.querySelector('#fileInfo .file-icon');
    if (!iconElement) return;
    
    // 移除所有现有类
    iconElement.className = 'file-icon';
    
    // 添加适当的图标
    if (mimeType.startsWith('image/')) {
        iconElement.classList.add('fas', 'fa-file-image', 'image');
    } else if (mimeType.startsWith('text/')) {
        iconElement.classList.add('fas', 'fa-file-alt', 'document');
    } else if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) {
        iconElement.classList.add('fas', 'fa-file-excel', 'spreadsheet');
    } else if (mimeType.includes('zip') || mimeType.includes('compressed')) {
        iconElement.classList.add('fas', 'fa-file-archive', 'archive');
    } else if (mimeType.includes('pdf')) {
        iconElement.classList.add('fas', 'fa-file-pdf', 'document');
    } else if (mimeType.includes('javascript') || mimeType.includes('json') || mimeType.includes('html') || mimeType.includes('css')) {
        iconElement.classList.add('fas', 'fa-file-code', 'code');
    } else {
        iconElement.classList.add('fas', 'fa-file');
    }
}

// 根据文件类型设置解码图标
function setDecodeFileIcon(mimeType) {
    const iconElement = document.querySelector('#decode-fileInfo .file-icon');
    if (!iconElement) return;
    
    // 移除所有现有类
    iconElement.className = 'file-icon';
    
    // 添加适当的图标 (与setFileIcon相同逻辑)
    if (mimeType.startsWith('image/')) {
        iconElement.classList.add('fas', 'fa-file-image', 'image');
    } else if (mimeType.startsWith('text/')) {
        iconElement.classList.add('fas', 'fa-file-alt', 'document');
    } else if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) {
        iconElement.classList.add('fas', 'fa-file-excel', 'spreadsheet');
    } else if (mimeType.includes('zip') || mimeType.includes('compressed')) {
        iconElement.classList.add('fas', 'fa-file-archive', 'archive');
    } else if (mimeType.includes('pdf')) {
        iconElement.classList.add('fas', 'fa-file-pdf', 'document');
    } else if (mimeType.includes('javascript') || mimeType.includes('json') || mimeType.includes('html') || mimeType.includes('css')) {
        iconElement.classList.add('fas', 'fa-file-code', 'code');
    } else {
        iconElement.classList.add('fas', 'fa-file');
    }
}

// 处理文件编码 - 修改使用正确的变量
async function processFileEncoding(algorithm) {
    if (!selectedFile) {
        showToast('请先选择文件', 'warning');
        return null;
    }
    
    try {
        showLoading();
        
        // 获取算法特定选项
        const options = getAlgorithmOptions ? getAlgorithmOptions(algorithm, 'encode') : {};
        
        // 文件编码处理选项
        const processOption = document.getElementById('file-process-option')?.value || 'whole';
        
        // 使用API处理文件
        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('algorithm', algorithm);
        formData.append('processOption', processOption);
        
        // 添加算法特定选项
        for (const key in options) {
            formData.append(key, options[key]);
        }
        
        const response = await fetch('api/encode_file.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error?.message || '文件处理失败');
        }
        console.log(result.data);
        return result.data;
        
    } catch (error) {
        console.error('文件处理错误:', error);
        showToast(error.message, 'error');
        return null;
    } finally {
        hideLoading();
    }
}

// 处理文件解码 - 修改使用正确的变量
async function processFileDecoding(algorithm) {
    if (!selectedDecodeFile) {
        showToast('请先选择文件', 'warning');
        return null;
    }
    
    try {
        showLoading();
        
        // 获取算法特定选项
        const options = getAlgorithmOptions ? getAlgorithmOptions(algorithm, 'decode') : {};
        
        // 文件解码处理选项
        const processOption = document.getElementById('decode-file-process-option')?.value || 'whole';
        
        // 使用API处理文件
        const formData = new FormData();
        formData.append('file', selectedDecodeFile);
        formData.append('algorithm', algorithm);
        formData.append('processOption', processOption);
        
        // 添加算法特定选项
        for (const key in options) {
            formData.append(key, options[key]);
        }
        
        const response = await fetch('api/decode_file.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error?.message || '文件处理失败');
        }
        
        return result.data;
    } catch (error) {
        console.error('文件处理错误:', error);
        showToast(error.message, 'error');
        return null;
    } finally {
        hideLoading();
    }
}

function downloadProcessedFile(data, algorithm) {
    if (!data || !data.fileUrl) {
        showToast('没有可下载的文件', 'error');
        return;
    }
    
    // 创建下载链接
    const a = document.createElement('a');
    a.href = data.fileUrl;
    a.download = data.fileName || `processed_file.${algorithm}`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    
    showToast('文件下载已开始', 'success');
}
// 当DOM加载完成时初始化
document.addEventListener('DOMContentLoaded', function() {
    initFileUpload();
});