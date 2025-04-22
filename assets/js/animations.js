/**
 * CodeCraft - 高级动画效果脚本
 */

document.addEventListener('DOMContentLoaded', function() {
    // 初始化粒子效果
    if (typeof particlesJS !== 'undefined') {
        particlesJS('particles-js', {
            "particles": {
                "number": {
                    "value": 80,
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": {
                    "value": "#3498db"
                },
                "shape": {
                    "type": "circle",
                    "stroke": {
                        "width": 0,
                        "color": "#000000"
                    },
                    "polygon": {
                        "nb_sides": 5
                    }
                },
                "opacity": {
                    "value": 0.5,
                    "random": true,
                    "anim": {
                        "enable": true,
                        "speed": 1,
                        "opacity_min": 0.1,
                        "sync": false
                    }
                },
                "size": {
                    "value": 3,
                    "random": true,
                    "anim": {
                        "enable": true,
                        "speed": 2,
                        "size_min": 0.1,
                        "sync": false
                    }
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#3498db",
                    "opacity": 0.4,
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 2,
                    "direction": "none",
                    "random": false,
                    "straight": false,
                    "out_mode": "out",
                    "bounce": false,
                    "attract": {
                        "enable": false,
                        "rotateX": 600,
                        "rotateY": 1200
                    }
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        "mode": "grab"
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push"
                    },
                    "resize": true
                },
                "modes": {
                    "grab": {
                        "distance": 140,
                        "line_linked": {
                            "opacity": 1
                        }
                    },
                    "bubble": {
                        "distance": 400,
                        "size": 40,
                        "duration": 2,
                        "opacity": 8,
                        "speed": 3
                    },
                    "repulse": {
                        "distance": 200,
                        "duration": 0.4
                    },
                    "push": {
                        "particles_nb": 4
                    },
                    "remove": {
                        "particles_nb": 2
                    }
                }
            },
            "retina_detect": true
        });
    }

    // 页面元素进入动画
    const animateElements = document.querySelectorAll('.fade-in-up, .fade-in-left, .fade-in-right, .zoom-in');
    
    // 立即执行页面上可见的动画
    animateElements.forEach(element => {
        if (isElementInViewport(element)) {
            element.style.visibility = 'visible';
        }
    });

    // 滚动监听动画
    const revealElements = document.querySelectorAll('.reveal');
    
    // 滚动事件监听
    window.addEventListener('scroll', function() {
        // 检查需要显示的元素
        revealElements.forEach(element => {
            if (isElementInViewport(element)) {
                element.classList.add('active');
            }
        });
    });

    // 初始检查一次
    revealElements.forEach(element => {
        if (isElementInViewport(element)) {
            element.classList.add('active');
        }
    });

    // 为技术线条创建动画效果
    createTechLines();

    // 深色模式切换
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', function() {
            document.body.classList.toggle('dark-mode', this.checked);
            localStorage.setItem('darkMode', this.checked ? 'enabled' : 'disabled');
        });
        
        // 检查用户之前的主题偏好
        if (localStorage.getItem('darkMode') === 'enabled') {
            darkModeToggle.checked = true;
            document.body.classList.add('dark-mode');
        }
    }

    // 标签页切换
    const tabBtns = document.querySelectorAll('.tab-btn');
    
    if (tabBtns.length > 0) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // 移除所有激活类
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // 添加激活类到当前点击的按钮
                this.classList.add('active');
                
                // 显示对应的内容
                const tabId = this.getAttribute('data-tab');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });
    }

    // 为复制按钮添加功能
    const copyButtons = document.querySelectorAll('.icon-btn[id*="copy-btn"]');
    
    if (copyButtons.length > 0) {
        copyButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const outputId = this.id === 'copy-btn' ? 'output-text' : 'decode-output-text';
                const outputElement = document.getElementById(outputId);
                
                if (outputElement && outputElement.value) {
                    // 创建一个临时textarea元素来实现复制功能
                    const textarea = document.createElement('textarea');
                    textarea.value = outputElement.value;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    
                    // 显示复制成功的视觉反馈
                    this.classList.add('copied');
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-check';
                    }
                    
                    // 3秒后恢复原状
                    setTimeout(() => {
                        this.classList.remove('copied');
                        if (icon) {
                            icon.className = 'fas fa-copy';
                        }
                    }, 3000);
                }
            });
        });
    }
});

/**
 * 编码动画效果
 * @param {string} algorithm - 使用的算法
 */
function playEncodeAnimation(algorithm) {
    const animationContainer = document.getElementById('encoder-animation');
    
    if (!animationContainer) return;
    
    // 清除之前的动画
    animationContainer.innerHTML = '';
    animationContainer.classList.add('active');
    
    // 根据算法创建不同的动画效果
    switch (algorithm) {
        case 'base64':
            createBase64Animation(animationContainer);
            break;
        case 'md5':
        case 'sha1':
        case 'sha256':
            createHashAnimation(animationContainer, algorithm);
            break;
        case 'url':
        case 'html':
            createSimpleAnimation(animationContainer);
            break;
    }
    
    // 添加进度条
    const progressBar = document.createElement('div');
    progressBar.className = 'progress-bar';
    animationContainer.appendChild(progressBar);
    
    // 动画完成后恢复
    setTimeout(() => {
        animationContainer.classList.remove('active');
    }, 2000);
}

/**
 * 解码动画效果
 * @param {string} algorithm - 使用的算法
 */
function playDecodeAnimation(algorithm) {
    const animationContainer = document.getElementById('decoder-animation');
    
    if (!animationContainer) return;
    
    // 清除之前的动画
    animationContainer.innerHTML = '';
    animationContainer.classList.add('active');
    
    // 根据算法创建不同的动画效果
    switch (algorithm) {
        case 'base64':
            createDecodeBase64Animation(animationContainer);
            break;
        case 'url':
        case 'html':
            createDecodeSimpleAnimation(animationContainer);
            break;
    }
    
    // 添加进度条
    const progressBar = document.createElement('div');
    progressBar.className = 'progress-bar';
    animationContainer.appendChild(progressBar);
    
    // 动画完成后恢复
    setTimeout(() => {
        animationContainer.classList.remove('active');
    }, 2000);
}

/**
 * 创建Base64编码动画
 * @param {HTMLElement} container - 动画容器
 */
function createBase64Animation(container) {
    // 数据流动画
    for (let i = 0; i < 5; i++) {
        const stream = document.createElement('div');
        stream.className = 'data-stream';
        stream.style.top = `${10 + i * 20}%`;
        stream.style.animationDelay = `${i * 0.2}s`;
        container.appendChild(stream);
    }
    
    // 矩阵数字雨效果
    const matrixRain = document.createElement('div');
    matrixRain.className = 'matrix-rain';
    container.appendChild(matrixRain);
    
    // 创建60个矩阵滴落效果
    for (let i = 0; i < 60; i++) {
        const drop = document.createElement('div');
        drop.className = 'matrix-drop';
        drop.style.left = `${Math.random() * 100}%`;
        drop.style.width = `${Math.random() * 3 + 1}px`;
        drop.style.opacity = Math.random() * 0.5 + 0.3;
        drop.style.animationDuration = `${Math.random() * 2 + 1}s`;
        drop.style.animationDelay = `${Math.random() * 2}s`;
        matrixRain.appendChild(drop);
    }
}

/**
 * 创建哈希算法动画
 * @param {HTMLElement} container - 动画容器
 * @param {string} algorithm - 哈希算法名称
 */
function createHashAnimation(container, algorithm) {
    const hashAnimation = document.createElement('div');
    hashAnimation.className = 'md5-animation';
    container.appendChild(hashAnimation);
    
    // 创建100个粒子
    for (let i = 0; i < 100; i++) {
        const particle = document.createElement('div');
        particle.className = 'hash-particle';
        
        // 随机位置和方向
        const x = Math.random() * 2 - 1;
        const y = Math.random() * 2 - 1;
        
        particle.style.setProperty('--x', x);
        particle.style.setProperty('--y', y);
        
        // 根据算法设置不同的颜色
        if (algorithm === 'md5') {
            particle.style.background = `hsl(210, ${Math.random() * 30 + 70}%, ${Math.random() * 30 + 40}%)`;
        } else if (algorithm === 'sha1') {
            particle.style.background = `hsl(280, ${Math.random() * 30 + 70}%, ${Math.random() * 30 + 40}%)`;
        } else {
            particle.style.background = `hsl(140, ${Math.random() * 30 + 70}%, ${Math.random() * 30 + 40}%)`;
        }
        
        particle.style.animationDelay = `${Math.random() * 0.5}s`;
        hashAnimation.appendChild(particle);
    }
}

/**
 * 创建简单编码动画
 * @param {HTMLElement} container - 动画容器
 */
function createSimpleAnimation(container) {
    // 数据流动画
    for (let i = 0; i < 3; i++) {
        const stream = document.createElement('div');
        stream.className = 'data-stream';
        stream.style.top = `${20 + i * 30}%`;
        stream.style.animationDelay = `${i * 0.3}s`;
        container.appendChild(stream);
    }
}

/**
 * 创建Base64解码动画
 * @param {HTMLElement} container - 动画容器
 */
function createDecodeBase64Animation(container) {
    // 创建解码像素效果
    for (let i = 0; i < 50; i++) {
        const pixel = document.createElement('div');
        pixel.className = 'decode-pixel';
        pixel.style.width = `${Math.random() * 10 + 5}px`;
        pixel.style.height = `${Math.random() * 10 + 5}px`;
        pixel.style.top = `${Math.random() * 100}%`;
        pixel.style.left = `${Math.random() * 100}%`;
        pixel.style.animationDelay = `${Math.random() * 0.5}s`;
        container.appendChild(pixel);
    }
}

/**
 * 创建简单解码动画
 * @param {HTMLElement} container - 动画容器
 */
function createDecodeSimpleAnimation(container) {
    // 与简单编码动画类似，但方向相反
    for (let i = 0; i < 3; i++) {
        const stream = document.createElement('div');
        stream.className = 'data-stream';
        stream.style.top = `${20 + i * 30}%`;
        stream.style.animationDelay = `${i * 0.3}s`;
        stream.style.transform = 'scaleY(-1)'; // 翻转方向
        container.appendChild(stream);
    }
}

/**
 * 创建技术线条效果
 */
function createTechLines() {
    const container = document.createElement('div');
    container.className = 'tech-lines';
    document.body.appendChild(container);
    
    // 创建15条技术线
    for (let i = 0; i < 15; i++) {
        const line = document.createElement('div');
        line.className = 'tech-line';
        line.style.top = `${Math.random() * 100}%`;
        line.style.animationDelay = `${Math.random() * 8}s`;
        line.style.opacity = Math.random() * 0.5 + 0.1;
        container.appendChild(line);
    }
}

/**
 * 检查元素是否在视口中
 * @param {HTMLElement} element - 要检查的元素
 * @returns {boolean} 元素是否在视口中
 */
function isElementInViewport(element) {
    const rect = element.getBoundingClientRect();
    
    return (
        rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.left <= (window.innerWidth || document.documentElement.clientWidth) &&
        rect.bottom >= 0 &&
        rect.right >= 0
    );
}