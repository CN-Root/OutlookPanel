<p align="center">
  <img src="https://raw.githubusercontent.com/CN-Root/OutlookPanel/466b00d90e8c2673e781ee9949b8d52ff63fab9a/static/image/favicon/OutlookPanel_Favicon.svg" width="120" alt="OutlookPanel Logo">
</p>

<h1 align="center">OutlookPanel</h1>

<p align="center">
一个基于 PHP + SQLite 的轻量级 Outlook 邮箱管理系统
</p>

---

支持：

* Outlook OAuth2 DeviceCode 授权
* Outlook / Hotmail IMAP 邮件读取
* 多账号管理
* Web 邮件查看
* Telegram 自动备份
* SQLite 单文件数据库
* 无需 MySQL
* 无需 Docker
* 开箱即用

# 功能介绍

## Outlook OAuth2 授权

支持：

* Outlook
* Hotmail
* Live

采用：

```txt
OAuth2 DeviceCode
```

无需：

* Azure 回调地址
* HTTPS 回调
* 企业应用配置

---

## 多账号管理

支持：

* 添加账号
* 编辑账号
* 删除账号
* 备注管理
* 导入导出

---

## 邮件查看

支持：

* IMAP XOAUTH2 登录
* HTML 邮件渲染
* 邮件主题解析
* 发件人解析
* UTF-8 / GBK / QuotedPrintable 解码

---

## Telegram 自动备份

支持：

* 一键备份账号
* 自动发送 JSON 文件到 TG Bot

---

# 系统要求

推荐环境：

| 项目     | 推荐版本           |
| ------ | -------------- |
| PHP    | 8.1+           |
| SQLite | PHP内置          |
| Nginx  | 1.20+          |
| 宝塔面板   | 9.x            |
| 系统     | Debian 11 / 12 |

---

# 部署教程（宝塔面板）

---

# 一、创建网站

进入宝塔：

```txt
网站 -> 添加站点
```

推荐：

| 配置    | 建议      |
| ----- | ------- |
| PHP版本 | PHP 8.1 |
| 数据库   | 不创建     |
| SSL   | 可选      |

因为项目使用：

```txt
SQLite
```

无需 MySQL。

---

# 二、上传项目

上传项目文件到网站目录：

```txt
/www/wwwroot/你的域名/
```

例如：

```txt
/www/wwwroot/mail.example.com/
```

---

# 三、安装 PHP 扩展

进入：

```txt
宝塔 -> PHP8.1 -> 安装扩展
```

确保安装：

| 扩展         | 必须 |
| ---------- | -- |
| curl       | √  |
| mbstring   | √  |
| openssl    | √  |
| sqlite3    | √  |
| pdo_sqlite | √  |

安装后：

```txt
重启 PHP
```

---

# 四、设置目录权限

进入终端：

```bash
chmod -R 755 /www/wwwroot/你的域名
chmod -R 777 /www/wwwroot/你的域名/data
```

或者：

```bash
mkdir data
chmod 777 data
```

---

# 五、访问后台

打开：

```txt
https://你的域名
```

默认账号：

```txt
admin
```

默认密码：

```txt
admin123
```

首次登录后请立即修改密码。

---

# 六、获取 Outlook Token

打开：

```txt
https://你的域名/get_token.php
```

按照页面提示：

1. 获取验证码
2. 打开微软授权页面
3. 输入验证码
4. 登录 Outlook 账号
5. 获取 RefreshToken

最终会生成：

```txt
邮箱----密码----ClientID----RefreshToken
```

复制后即可添加到后台。

---

# 七、添加 Outlook 账号

进入后台：

```txt
添加账号
```

粘贴：

```txt
邮箱----密码----ClientID----RefreshToken
```

即可开始读取邮件。

---

# Telegram 备份配置

进入：

```txt
设置
```

填写：

| 配置           | 说明                 |
| ------------ | ------------------ |
| TG Bot Token | Telegram Bot Token |
| TG Chat ID   | Telegram ChatID    |

然后即可：

```txt
一键备份到 Telegram
```

---

# 数据库存储位置

SQLite 数据库：

```txt
/data/outlook.db
```

建议定期备份。

---

# 默认目录结构

```txt
OutlookPanel/
│
├── api.php
├── config.php
├── dashboard.php
├── get_token.php
├── index.php
│
├── data/
│   └── outlook.db
│
├── README.md
├── LICENSE
```

---

# 默认管理员信息

| 用户名   | 密码       |
| ----- | -------- |
| admin | admin123 |

首次登录请修改。

---

# 安全建议

建议：

* 配置 HTTPS
* 修改默认密码
* 设置宝塔防火墙
* 定期备份 SQLite 数据库
* 不要公开演示站

---

Nginx伪静态：

```bash
location ~ /data/.*\.db$ {
    deny all;
}
```

# 开源协议

本项目采用：

```txt
MIT License
```

---

# 免责声明

本项目仅供学习与研究用途。

请勿用于：

* 非法收集邮件
* 未授权账号访问
* 批量恶意行为

使用者需自行承担风险。

---


# Star History

如果这个项目对你有帮助，欢迎 Star ⭐
