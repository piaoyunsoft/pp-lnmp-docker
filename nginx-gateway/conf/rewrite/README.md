# Nginx Rewrite 规则库

各种常见 PHP 程序的伪静态规则。`lnmp site:add --rewrite=<name>` 自动套用。

## 可用列表

| name | 程序 | 说明 |
|---|---|---|
| `discuz` | Discuz! 7.x | 老论坛 |
| `discuzx` | Discuz! X3.x / X5.x | 主流论坛 |
| `discuzx2` | Discuz! X2 | 老 X 系列 |
| `wordpress` | WordPress | 主流博客 |
| `wp2` | WordPress (二级目录) | 子路径部署 |
| `typecho` / `typecho2` | Typecho | 轻博客 |
| `zblog` | Z-Blog PHP | 博客 |
| `sablog` | SaBlog-X | 老博客 |
| `thinkphp` | ThinkPHP 3/5 | 框架 |
| `laravel` | Laravel | 主流框架 |
| `yii2` | Yii Framework 2 | 框架 |
| `codeigniter` | CodeIgniter | 老框架 |
| `dedecms` | DedeCMS (织梦) | CMS |
| `phpwind` | PHPWind | 老论坛 |
| `ecshop` | ECShop | 老商城 |
| `shopex` | ShopEx | 老商城 |
| `joomla` | Joomla | CMS |
| `drupal` | Drupal | CMS |
| `dabr` | Dabr | 微博客 |

## 用法

```bash
# 建站时套
lnmp site:add bbs.com 7.4 --rewrite=discuzx
lnmp site:add wp.com  8.3 --rewrite=wordpress
lnmp site:add api.com 8.3 --rewrite=laravel

# 已有站要加
# 1. 改 nginx-gateway/conf/sites/<域>.conf
#    在 location / { } 内加: include /etc/nginx/rewrite/<name>.conf;
# 2. lnmp site:reload
```

## 来源

规则来自 **军哥 LNMP** (lnmp.org) 一键安装包 (License: GPL)，感谢 Licess 团队多年维护。

> 本目录文件版权归原作者所有；本项目仅做集成。
