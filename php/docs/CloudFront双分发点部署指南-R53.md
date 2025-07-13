# CloudFront双分发点部署指南 - 使用R53域名

## 📋 架构概述

基于参考文档的要求，本方案实现了CloudFront Signed Cookie的标准使用场景：

### 🏗️ 架构组件

1. **分发点1 (应用程序)**
   - 域名: `app.liangym.people.aws.dev`
   - 源站: ELB (应用服务器)
   - 签名: **不开启**CloudFront签名
   - 功能: 生成Cookie并提供应用界面

2. **分发点2 (私有内容)**
   - 域名: `cdn.liangym.people.aws.dev`
   - 源站: S3 (私有内容存储)
   - 签名: **开启**CloudFront签名
   - 功能: 通过Cookie验证提供私有内容

3. **Cookie共享机制**
   - Cookie域名: `.liangym.people.aws.dev`
   - 两个子域名共享同一套Cookie
   - 用户从应用程序跳转到私有内容时，URL不包含签名参数

## 🚀 快速部署

### 前置条件

1. AWS CLI已配置
2. 拥有 `liangym.people.aws.dev` 域名的Route 53 Hosted Zone
3. 有效的SSL证书 (ACM)
4. CloudFront Key Pair和私钥文件

### 一键部署

```bash
# 执行自动化部署脚本
./deploy-dual-distribution.sh
```

### 手动部署步骤

#### 1. 创建应用程序分发点

```bash
aws cloudfront create-distribution \
    --distribution-config file://cloudfront-app-r53-distribution.json \
    --region us-east-1
```

#### 2. 创建私有内容分发点

```bash
aws cloudfront create-distribution \
    --distribution-config file://cloudfront-cdn-r53-distribution.json \
    --region us-east-1
```

#### 3. 配置Route 53 DNS记录

```bash
# 获取Hosted Zone ID
HOSTED_ZONE_ID=$(aws route53 list-hosted-zones-by-name \
    --dns-name "liangym.people.aws.dev" \
    --query "HostedZones[0].Id" \
    --output text | sed 's|/hostedzone/||')

# 创建DNS记录
aws route53 change-resource-record-sets \
    --hosted-zone-id "$HOSTED_ZONE_ID" \
    --change-batch file://route53-dns-records.json
```

## 🧪 测试验证

### 1. 基本功能测试

1. **访问应用程序页面**
   ```
   https://app.liangym.people.aws.dev/app-r53-main.php
   ```

2. **检查Cookie设置**
   - 打开浏览器开发工具 (F12)
   - 转到 Application → Cookies
   - 确认看到以下Cookie:
     - `CloudFront-Policy`
     - `CloudFront-Signature`
     - `CloudFront-Key-Pair-Id`

3. **测试私有内容访问**
   ```
   https://cdn.liangym.people.aws.dev/index.html
   ```
   - URL中不包含签名参数
   - 应该返回200状态码

### 2. Cookie测试页面

访问专门的Cookie测试页面：
```
https://app.liangym.people.aws.dev/cookie-test.php
```

### 3. 验证标志

✅ **成功标志:**
- Cookie正确设置到 `.liangym.people.aws.dev` 域名
- 私有内容链接能正常访问 (200状态码)
- 私有内容URL不包含签名参数
- 两个子域名能共享Cookie

❌ **失败标志:**
- 403 Forbidden错误
- Cookie不存在或无法共享
- 签名验证失败

## 📁 文件说明

### 配置文件
- `config-r53-dual-distribution.php` - 双分发点PHP配置
- `cloudfront-app-r53-distribution.json` - 应用程序分发点配置
- `cloudfront-cdn-r53-distribution.json` - 私有内容分发点配置
- `route53-dns-records.json` - DNS记录配置

### 应用文件
- `app-r53-main.php` - 应用程序主页 (生成Cookie)
- `cookie-test.php` - Cookie测试页面

### 部署文件
- `deploy-dual-distribution.sh` - 自动化部署脚本
- `deployment-summary.txt` - 部署总结 (脚本生成)

## 🔧 配置详解

### Cookie配置

```php
// Cookie域名设置为主域名，实现子域名共享
$cookie_domain = '.liangym.people.aws.dev';

// Cookie安全设置
setcookie("CloudFront-Policy", $policy_base64, $expires, "/", $cookie_domain, true, true);
//                                                                              ↑     ↑
//                                                                           Secure HttpOnly
```

### CloudFront分发点差异

| 配置项 | 应用程序分发点 | 私有内容分发点 |
|--------|----------------|----------------|
| 签名验证 | 关闭 | 开启 |
| 源站类型 | ELB | S3 |
| 允许方法 | 全部HTTP方法 | GET, HEAD |
| Cookie转发 | 全部 | 仅签名Cookie |

## 🔍 故障排除

### 常见问题

1. **403 Forbidden错误**
   - 检查Cookie是否正确设置
   - 验证Key Pair ID和私钥匹配
   - 确认Cookie域名配置正确

2. **Cookie不共享**
   - 检查Cookie域名设置 (应为 `.liangym.people.aws.dev`)
   - 验证浏览器安全设置
   - 确认HTTPS配置正确

3. **DNS解析问题**
   - 检查Route 53记录是否正确创建
   - 验证SSL证书域名匹配
   - 等待DNS传播完成

### 调试命令

```bash
# 检查DNS解析
nslookup app.liangym.people.aws.dev
nslookup cdn.liangym.people.aws.dev

# 测试HTTP响应
curl -I https://app.liangym.people.aws.dev/app-r53-main.php
curl -I https://cdn.liangym.people.aws.dev/

# 检查CloudFront分发点状态
aws cloudfront list-distributions --query "DistributionList.Items[?Aliases.Items[0]=='app.liangym.people.aws.dev']"
```

## 📈 性能优化

### 缓存策略
- 应用程序分发点: 动态内容，较短缓存时间
- 私有内容分发点: 静态内容，较长缓存时间

### 安全设置
- 启用HTTPS重定向
- 设置最小TLS版本为1.2
- Cookie设置HttpOnly和Secure标志

## 🔄 维护操作

### 更新Cookie有效期
修改 `config-r53-dual-distribution.php` 中的 `$expires` 变量

### 轮换密钥
1. 生成新的CloudFront Key Pair
2. 更新配置文件中的Key Pair ID
3. 替换私钥文件
4. 更新CloudFront分发点配置

### 监控建议
- 设置CloudWatch告警监控403错误率
- 监控Cookie设置成功率
- 跟踪私有内容访问模式

---

## 📞 支持

如遇到问题，请检查：
1. AWS CloudTrail日志
2. CloudFront访问日志
3. 浏览器开发工具Network标签
4. 本文档的故障排除部分
