# 🚀 CloudFront双分发点 - 快速开始指南

## 📋 5分钟快速部署

### 步骤1: 准备工作

1. **检查AWS CLI配置**
   ```bash
   aws sts get-caller-identity
   ```

2. **准备域名和证书**
   - Route 53 Hosted Zone
   - ACM SSL证书 (通配符证书推荐)

3. **准备CloudFront Key Pair**
   - 在CloudFront控制台创建Key Pair
   - 下载私钥文件

### 步骤2: 配置修改

1. **修改主配置文件**
   ```bash
   # 编辑 configs/config-r53-dual-distribution.php
   $main_domain = 'your-domain.com';           # 改为你的域名
   $key_pair_id = 'YOUR_KEY_PAIR_ID';          # 改为你的Key Pair ID
   ```

2. **修改CloudFront配置**
   ```bash
   # 编辑 configs/cloudfront-app-r53-distribution.json
   "DomainName": "your-elb-domain.com"         # 改为你的ELB域名
   "Items": ["app.your-domain.com"]            # 改为你的应用域名
   
   # 编辑 configs/cloudfront-cdn-r53-distribution.json
   "DomainName": "your-s3-bucket.s3.region.amazonaws.com"  # 改为你的S3域名
   "Items": ["cdn.your-domain.com"]            # 改为你的CDN域名
   ```

3. **更新SSL证书ARN**
   ```bash
   # 在两个CloudFront配置文件中更新
   "ACMCertificateArn": "arn:aws:acm:us-east-1:account:certificate/cert-id"
   ```

4. **替换私钥文件**
   ```bash
   # 替换 keys/private.pem 为你的私钥文件
   cp your-private-key.pem keys/private.pem
   chmod 600 keys/private.pem
   ```

### 步骤3: 一键部署

```bash
# 执行自动化部署
./scripts/deploy-dual-distribution.sh
```

### 步骤4: 验证部署

```bash
# 验证部署状态
./scripts/verify-deployment.sh
```

### 步骤5: 测试功能

1. **访问应用程序页面**
   ```
   https://app.your-domain.com/cookie-generator.php
   ```

2. **测试私有内容访问**
   ```
   https://cdn.your-domain.com/index.html
   ```

## 🔧 常见配置示例

### 示例1: 使用现有ELB

```json
// configs/cloudfront-app-r53-distribution.json
{
  "Origins": {
    "Items": [{
      "DomainName": "my-app-alb-123456789.us-east-1.elb.amazonaws.com"
    }]
  }
}
```

### 示例2: 使用现有S3存储桶

```json
// configs/cloudfront-cdn-r53-distribution.json
{
  "Origins": {
    "Items": [{
      "DomainName": "my-private-bucket.s3.us-east-1.amazonaws.com"
    }]
  }
}
```

### 示例3: 自定义域名

```php
// configs/config-r53-dual-distribution.php
$main_domain = 'example.com';
$app_subdomain = 'app.' . $main_domain;    // app.example.com
$cdn_subdomain = 'cdn.' . $main_domain;    // cdn.example.com
```

## 🧪 快速测试

### 命令行测试

```bash
# 1. 生成Cookie
curl -c cookies.txt https://app.your-domain.com/cookie-generator.php

# 2. 测试私有内容访问
curl -b cookies.txt https://cdn.your-domain.com/index.html

# 3. 验证无Cookie访问被拒绝
curl https://cdn.your-domain.com/index.html
# 应该返回: MissingKey错误
```

### 浏览器测试

1. 打开 `https://app.your-domain.com/cookie-generator.php`
2. 检查浏览器开发工具中的Cookie
3. 点击私有内容链接
4. 验证能正常访问且URL无签名参数

## ⚠️ 注意事项

1. **DNS传播时间**: 新DNS记录可能需要5-10分钟生效
2. **CloudFront部署时间**: 分发点部署通常需要10-15分钟
3. **SSL证书**: 必须是us-east-1区域的证书
4. **Key Pair**: 确保私钥文件与Key Pair ID匹配

## 🔍 故障排除

### 部署失败

```bash
# 检查AWS权限
aws iam get-user

# 检查CloudFront状态
aws cloudfront list-distributions

# 检查Route 53记录
aws route53 list-hosted-zones
```

### 访问失败

```bash
# 检查DNS解析
nslookup app.your-domain.com
nslookup cdn.your-domain.com

# 检查SSL证书
openssl s_client -connect app.your-domain.com:443 -servername app.your-domain.com
```

## 📞 获取帮助

如果遇到问题：
1. 查看 [详细部署指南](docs/CloudFront双分发点部署指南-R53.md)
2. 检查 [测试报告](docs/final-test-report.md) 中的解决方案
3. 查看AWS CloudTrail日志获取详细错误信息

---

**预计部署时间:** 20-30分钟  
**难度等级:** 中等  
**成功率:** 95%+ (按照指南操作)	
