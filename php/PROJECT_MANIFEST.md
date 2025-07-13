# CloudFront双分发点项目文件清单

## 📁 项目结构

### 根目录文件
- `README.md` - 项目主要说明文档
- `QUICK_START.md` - 快速开始指南
- `PROJECT_MANIFEST.md` - 本文件清单

### 📂 configs/ - 配置文件目录
- `config-r53-dual-distribution.php` - PHP应用程序配置文件
- `cloudfront-app-r53-distribution.json` - 应用程序CloudFront分发点配置
- `cloudfront-cdn-r53-distribution.json` - 私有内容CloudFront分发点配置
- `route53-dns-records.json` - Route 53 DNS记录配置
- `s3-bucket-policy.json` - S3存储桶策略配置

### 📂 templates/ - 应用程序模板目录
- `app-r53-main.php` - 主应用程序页面（完整功能）
- `cookie-generator.php` - Cookie生成器页面（简化版）
- `cookie-test.php` - Cookie测试和验证页面
- `simple-test.php` - 简单测试页面

### 📂 scripts/ - 部署脚本目录
- `deploy-dual-distribution.sh` - 自动化部署脚本
- `verify-deployment.sh` - 部署验证脚本

### 📂 tests/ - 测试文件目录
- `test-private-content.html` - 私有HTML测试页面
- `test-private-document.txt` - 私有文档测试文件

### 📂 keys/ - 密钥文件目录
- `private.pem` - CloudFront签名私钥文件
- `cloudfront-test-key.pem` - EC2访问密钥文件

### 📂 docs/ - 文档目录
- `CloudFront-SignedURL-Cookie-部署指南.md` - 原始部署指南
- `CloudFront双分发点部署指南-R53.md` - R53域名部署指南
- `final-test-report.md` - 最终测试报告
- `DEPLOYMENT_SUCCESS.md` - 部署成功报告
- `deployment-summary.txt` - 部署总结

## 🔧 文件用途说明

### 核心配置文件

#### config-r53-dual-distribution.php
- **用途**: PHP应用程序的核心配置
- **包含**: 域名设置、Cookie配置、签名函数
- **需要修改**: 域名、Key Pair ID

#### cloudfront-app-r53-distribution.json
- **用途**: 应用程序CloudFront分发点配置
- **特点**: 不开启签名验证
- **需要修改**: ELB域名、SSL证书ARN

#### cloudfront-cdn-r53-distribution.json
- **用途**: 私有内容CloudFront分发点配置
- **特点**: 开启签名验证
- **需要修改**: S3存储桶域名、SSL证书ARN

### 应用程序模板

#### app-r53-main.php
- **用途**: 完整功能的主应用程序页面
- **功能**: 自动生成Cookie、显示配置信息、提供测试链接
- **推荐**: 生产环境使用

#### cookie-generator.php
- **用途**: 简化的Cookie生成器
- **功能**: 专注于Cookie生成和基本测试
- **推荐**: 开发测试使用

#### cookie-test.php
- **用途**: Cookie状态检查和调试
- **功能**: 显示Cookie详细信息、跨域测试
- **推荐**: 故障排除使用

### 部署脚本

#### deploy-dual-distribution.sh
- **用途**: 自动化部署整个架构
- **功能**: 创建CloudFront分发点、配置DNS记录
- **使用**: 一键部署

#### verify-deployment.sh
- **用途**: 验证部署状态
- **功能**: 检查DNS、CloudFront状态、应用程序功能
- **使用**: 部署后验证

## 🚀 使用流程

### 1. 准备阶段
1. 检查 `keys/` 目录中的密钥文件
2. 修改 `configs/` 目录中的配置文件
3. 确保AWS CLI已配置

### 2. 部署阶段
1. 执行 `scripts/deploy-dual-distribution.sh`
2. 等待CloudFront部署完成
3. 运行 `scripts/verify-deployment.sh` 验证

### 3. 测试阶段
1. 将 `templates/` 中的PHP文件部署到EC2
2. 将 `tests/` 中的文件上传到S3
3. 进行端到端测试

### 4. 维护阶段
1. 参考 `docs/` 中的文档进行故障排除
2. 使用测试页面进行日常检查

## 📋 部署检查清单

### 部署前检查
- [ ] AWS CLI已配置并有足够权限
- [ ] Route 53 Hosted Zone已创建
- [ ] SSL证书已申请并验证
- [ ] CloudFront Key Pair已创建
- [ ] 私钥文件已放置在 `keys/private.pem`
- [ ] 配置文件中的域名已修改
- [ ] 配置文件中的资源ARN已更新

### 部署后检查
- [ ] CloudFront分发点状态为"Deployed"
- [ ] DNS记录已正确创建
- [ ] 应用程序页面可以访问
- [ ] Cookie能正确生成
- [ ] 私有内容访问正常
- [ ] 无Cookie时访问被拒绝

## 🔄 版本信息

- **版本**: 1.0.0
- **创建日期**: 2025-07-11
- **最后更新**: 2025-07-11
- **状态**: 生产就绪
- **测试状态**: 全部通过

## 📞 支持信息

如需帮助，请参考：
1. `README.md` - 项目概述和基本使用
2. `QUICK_START.md` - 快速开始指南
3. `docs/` 目录中的详细文档
4. AWS官方文档和CloudTrail日志
