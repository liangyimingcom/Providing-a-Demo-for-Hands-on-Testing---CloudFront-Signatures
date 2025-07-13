# CloudFront Signed URL/Cookie 测试环境部署指南

## 📋 概述

本文档提供了在AWS上部署CloudFront Signed URL和Signed Cookie测试环境的完整步骤，包括EC2实例创建、Web服务器配置、CloudFront设置和PHP测试文件部署。

## 🎯 业务目标

测试 CloudFront Signed-URL/Signed Cookie 的以下功能：
- **标准策略 (Canned Policy)** - 基本的时间限制访问控制
- **定制策略 (Custom Policy)** - 支持IP限制、时间窗口等高级控制

## 🏗️ 架构组件

```
用户浏览器 → EC2 Web服务器 (PHP) → CloudFront分发 → S3存储桶
                ↓
            签名URL/Cookie生成
```

## 📋 前置要求

- AWS CLI 已配置（profile: oversea1）
- 目标区域: eu-central-1
- 现有CloudFront分发已配置Trusted Key Groups
- RSA密钥对（private.pem, public.pem）

## 🚀 部署步骤

### 1. 创建安全组

```bash
# 创建安全组
aws ec2 create-security-group \
    --profile oversea1 \
    --region eu-central-1 \
    --vpc-id vpc-b5bdd6de \
    --group-name cloudfront-test-sg \
    --description "Security group for CloudFront signed URL testing"

# 添加HTTP访问规则
aws ec2 authorize-security-group-ingress \
    --profile oversea1 \
    --region eu-central-1 \
    --group-id sg-0e2b085dbbb71fe29 \
    --protocol tcp \
    --port 80 \
    --cidr 0.0.0.0/0

# 添加SSH访问规则
aws ec2 authorize-security-group-ingress \
    --profile oversea1 \
    --region eu-central-1 \
    --group-id sg-0e2b085dbbb71fe29 \
    --protocol tcp \
    --port 22 \
    --cidr 0.0.0.0/0
```

### 2. 创建密钥对

```bash
aws ec2 create-key-pair \
    --profile oversea1 \
    --region eu-central-1 \
    --key-name cloudfront-test-key \
    --query 'KeyMaterial' \
    --output text > cloudfront-test-key.pem

chmod 400 cloudfront-test-key.pem
```

### 3. 启动EC2实例

```bash
aws ec2 run-instances \
    --profile oversea1 \
    --region eu-central-1 \
    --image-id ami-0e872aee57663ae2d \
    --instance-type t3.micro \
    --key-name cloudfront-test-key \
    --security-group-ids sg-0e2b085dbbb71fe29 \
    --subnet-id subnet-4dcaea26 \
    --count 1 \
    --tag-specifications 'ResourceType=instance,Tags=[{Key=Name,Value=CloudFront-SignedURL-Test}]'
```

### 4. 安装Web服务器和PHP

```bash
# SSH连接到实例
ssh -i ./cloudfront-test-key.pem ubuntu@<PUBLIC_IP>

# 安装Apache和PHP
sudo apt update -y
sudo apt install -y apache2 php php-curl php-json php-mbstring
sudo systemctl enable apache2
sudo systemctl start apache2
```

### 5. 部署测试文件

将以下文件上传到 `/var/www/html/`：

- `signed-url.php` - Signed URL测试页面
- `signed-cookie-canned.php` - Signed Cookie标准策略测试
- `signed-cookie-custom.php` - Signed Cookie定制策略测试
- `config.php` - 配置文件
- `private.pem` - RSA私钥
- `public.pem` - RSA公钥

```bash
# 上传文件
scp -i ./cloudfront-test-key.pem *.php *.pem ubuntu@<PUBLIC_IP>:/tmp/

# 移动到Apache目录并设置权限
ssh -i ./cloudfront-test-key.pem ubuntu@<PUBLIC_IP>
sudo cp /tmp/*.php /var/www/html/
sudo cp /tmp/*.pem /var/www/html/
sudo chown www-data:www-data /var/www/html/*.php /var/www/html/*.pem
sudo chmod 644 /var/www/html/*.php
sudo chmod 600 /var/www/html/private.pem
sudo chmod 644 /var/www/html/public.pem
```

## ⚙️ 关键配置信息

### CloudFront配置
```php
// config.php 关键参数
$private_key_filename = './private.pem';
$key_pair_id = 'K3UHZUBESECTVE';  // CloudFront Key Pair ID
$video_path = 'https://d1pizsixgdf7r1.cloudfront.net/index.html';
$expires = time() + 300; // 5分钟过期
$cookie_domain = 'd1pizsixgdf7r1.cloudfront.net';
```

### CloudFront分发信息
- **分发域名**: d1pizsixgdf7r1.cloudfront.net
- **分发ID**: E1X033RHT2CFY
- **Key Group ID**: 1388ae04-efac-441f-8731-b7691c0c3970
- **Key Pair ID**: K3UHZUBESECTVE
- **源站**: S3存储桶 (poc-minimal-153705321444-eu-central-1)

## 🧪 测试验证

### 访问地址
- **主页**: http://\<PUBLIC_IP\>/
- **Signed URL测试**: http://\<PUBLIC_IP\>/signed-url.php
- **Signed Cookie (标准)**: http://\<PUBLIC_IP\>/signed-cookie-canned.php
- **Signed Cookie (定制)**: http://\<PUBLIC_IP\>/signed-cookie-custom.php

### 验证步骤

#### 1. Signed URL测试
1. 访问signed-url.php页面
2. 查看生成的签名URL格式
3. 验证URL包含以下参数：
   - `Expires` - 过期时间戳
   - `Signature` - Base64编码的签名
   - `Key-Pair-Id` - CloudFront密钥对ID

#### 2. Signed Cookie测试
1. 访问cookie测试页面
2. 打开浏览器开发工具 (F12)
3. 查看Application → Cookies
4. 验证以下Cookie被设置：
   - `CloudFront-Policy` (定制策略)
   - `CloudFront-Signature`
   - `CloudFront-Key-Pair-Id`

## 🔐 安全最佳实践

### 文件权限
```bash
# 私钥文件权限设置
chmod 600 /var/www/html/private.pem
chown www-data:www-data /var/www/html/private.pem
```

### 访问控制
- 签名URL/Cookie有效期设置为5分钟
- 定制策略可添加IP地址限制
- 私钥文件仅Web服务器可读

## 🛠️ 故障排除

### 常见问题

1. **签名验证失败**
   - 检查Key Pair ID是否正确
   - 验证私钥文件路径和权限
   - 确认CloudFront分发配置了正确的Trusted Key Groups

2. **Cookie未设置**
   - 检查cookie域名配置
   - 验证浏览器是否支持第三方Cookie
   - 确认PHP session配置

3. **403 Forbidden错误**
   - 验证签名是否正确生成
   - 检查时间戳是否在有效期内
   - 确认IP限制设置（如果使用定制策略）

### 调试命令
```bash
# 检查Apache状态
sudo systemctl status apache2

# 查看Apache错误日志
sudo tail -f /var/log/apache2/error.log

# 测试PHP配置
php -m | grep -E "(curl|json|mbstring)"

# 验证文件权限
ls -la /var/www/html/
```

## 📚 参考资料

- [AWS CloudFront Signed URLs](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/private-content-signed-urls.html)
- [AWS CloudFront Signed Cookies](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/private-content-signed-cookies.html)
- [CloudFront Key Groups](https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/private-content-trusted-signers.html)

## 📝 部署清单

- [ ] 创建安全组并配置规则
- [ ] 创建EC2密钥对
- [ ] 启动EC2实例
- [ ] 安装Apache和PHP
- [ ] 上传测试文件
- [ ] 配置文件权限
- [ ] 更新config.php配置
- [ ] 验证CloudFront分发设置
- [ ] 测试所有功能页面
- [ ] 验证签名URL生成
- [ ] 验证Cookie设置
- [ ] 检查安全配置

---

**创建日期**: 2025-07-11  
**最后更新**: 2025-07-11  
**版本**: 1.0
