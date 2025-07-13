# CloudFront双分发点 - Signed Cookie解决方案

## 📋 项目概述

本项目实现了CloudFront Signed Cookie的标准使用场景，包含两个CloudFront分发点：
- **应用程序分发点**: 不开启签名，用于生成Cookie
- **私有内容分发点**: 开启签名，通过Cookie验证访问权限

## 🏗️ 架构说明

```
用户浏览器
    ↓
app.domain.com (应用程序分发点)
    ↓ 生成Cookie
用户浏览器 (携带Cookie)
    ↓
cdn.domain.com (私有内容分发点)
    ↓ 验证Cookie
私有内容 (S3)
```

### 关键特性
- ✅ URL中不包含签名参数
- ✅ 跨子域名Cookie共享
- ✅ 自动身份验证
- ✅ 标准使用流程

## 📁 项目结构

```
cloudfront-dual-distribution/
├── README.md                    # 项目说明文档
├── QUICK_START.md              # 快速开始指南
├── configs/                    # 配置文件
│   ├── config-r53-dual-distribution.php
│   ├── cloudfront-app-r53-distribution.json
│   ├── cloudfront-cdn-r53-distribution.json
│   ├── route53-dns-records.json
│   └── s3-bucket-policy.json
├── templates/                  # 应用程序模板
│   ├── app-r53-main.php       # 主应用程序页面
│   ├── cookie-generator.php   # Cookie生成器
│   ├── cookie-test.php        # Cookie测试页面
│   └── simple-test.php        # 简单测试页面
├── scripts/                   # 部署脚本
│   ├── deploy-dual-distribution.sh
│   └── verify-deployment.sh
├── tests/                     # 测试文件
│   ├── test-private-content.html
│   └── test-private-document.txt
├── keys/                      # 密钥文件
│   ├── private.pem
│   └── cloudfront-test-key.pem
└── docs/                      # 文档
    ├── CloudFront-SignedURL-Cookie-部署指南.md
    ├── CloudFront双分发点部署指南-R53.md
    ├── final-test-report.md
    ├── DEPLOYMENT_SUCCESS.md
    └── deployment-summary.txt
```

## 🚀 快速开始

### 前置条件

1. AWS CLI已配置
2. 拥有Route 53 Hosted Zone
3. 有效的SSL证书 (ACM)
4. CloudFront Key Pair和私钥文件

### 一键部署

```bash
# 1. 克隆或下载项目文件
# 2. 修改配置文件中的域名和资源信息
# 3. 执行部署脚本
./scripts/deploy-dual-distribution.sh
```

### 验证部署

```bash
# 验证部署状态
./scripts/verify-deployment.sh
```

## ⚙️ 配置说明

### 主要配置文件

1. **configs/config-r53-dual-distribution.php**
   - PHP应用程序配置
   - 域名设置
   - Cookie配置

2. **configs/cloudfront-app-r53-distribution.json**
   - 应用程序分发点配置
   - 不开启签名验证

3. **configs/cloudfront-cdn-r53-distribution.json**
   - 私有内容分发点配置
   - 开启签名验证

### 需要修改的配置项

```php
// configs/config-r53-dual-distribution.php
$main_domain = 'your-domain.com';           // 修改为你的域名
$key_pair_id = 'YOUR_KEY_PAIR_ID';          // 修改为你的Key Pair ID
```

```json
// configs/cloudfront-*-distribution.json
"DomainName": "your-elb-domain.com",        // 修改为你的ELB域名
"Items": ["app.your-domain.com"]            // 修改为你的域名
```

## 🧪 测试流程

### 1. 基础功能测试

```bash
# 测试应用程序分发点
curl https://app.your-domain.com/cookie-generator.php

# 测试私有内容分发点 (应该返回MissingKey错误)
curl https://cdn.your-domain.com/index.html
```

### 2. 端到端测试

```bash
# 生成Cookie并测试私有内容访问
curl -c cookies.txt https://app.your-domain.com/cookie-generator.php
curl -b cookies.txt https://cdn.your-domain.com/index.html
```

## 📖 详细文档

- [部署指南](docs/CloudFront双分发点部署指南-R53.md)
- [测试报告](docs/final-test-report.md)
- [成功案例](docs/DEPLOYMENT_SUCCESS.md)

## 🔧 故障排除

### 常见问题

1. **403 Forbidden错误**
   - 检查Cookie是否正确设置
   - 验证Key Pair ID和私钥匹配

2. **DNS解析问题**
   - 检查Route 53记录
   - 等待DNS传播完成

3. **CloudFront连接问题**
   - 检查源站健康状态
   - 验证安全组配置

## 📞 支持

如遇到问题，请检查：
1. [故障排除文档](docs/CloudFront双分发点部署指南-R53.md#故障排除)
2. AWS CloudTrail日志
3. CloudFront访问日志
4. 浏览器开发工具Network标签

## 📄 许可证

本项目仅供学习和测试使用。

---

**最后更新:** 2025-07-11  
**版本:** 1.0.0  
**状态:** 生产就绪 ✅
