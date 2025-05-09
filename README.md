# CodeCraft - 高级编码解码工具

CodeCraft是一个现代化的在线编码解码工具，提供优雅的UI和强大的功能。该工具支持多种编码和哈希算法，并且提供完整的API供集成使用。


[演示地址](http://jm.lzx1.top)
![CodeCraft示例图](https://lzx1.top/res/ghithub-img/CodeCraft/2.png)

## 特性

- 支持多种编码/解码算法:
  - Base64编码/解码
  - MD5哈希
  - SHA-1哈希
  - SHA-256哈希
  - URL编码/解码
  - HTML实体编码/解码
  - ......
- 响应式设计，完美适配手机和桌面设备
- 完整的API文档
- 黑暗模式支持

## 安装

1. 克隆此仓库:
```bash
git clone https://github.com/sbgumen/CodeCraft.git
```

2. 将文件上传到您的Web服务器，确保PHP 7.2+已安装

3. 确保Web服务器有权限读写API目录下的`rate_limits.json`文件和`logs`目录



## API使用说明

CodeCraft提供RESTful API，允许您以编程方式使用编码和解码功能。详细的API文档可在`docs.html`页面找到。

### 基本示例

#### Base64编码
```bash
curl -X POST https://yourdomain.com/api/encode.php \
     -d "text=Hello World" \
     -d "algorithm=base64"
```

#### MD5哈希
```bash
curl -X POST https://yourdomain.com/api/encode.php \
     -d "text=password123" \
     -d "algorithm=md5"
```

#### Base64解码
```bash
curl -X POST https://yourdomain.com/api/decode.php \
     -d "text=SGVsbG8gV29ybGQ=" \
     -d "algorithm=base64"
```

## 自定义和扩展

CodeCraft的设计使其易于扩展和添加新的编码/解码算法：

1. 在`api/functions/`目录下创建新的函数文件
2. 在`api/index.php`的`getSupportedAlgorithms()`函数中添加新算法
3. 在`api/encode.php`和`api/decode.php`中为新算法添加处理逻辑
4. 在前端的算法选择下拉列表中添加新选项

## 安全性考虑

- 所有输入在服务器端都经过验证和清理
- 实施了速率限制以防止滥用
- 输入大小限制为1MB



## 许可证

MIT许可证 - 详情请参阅LICENSE文件
