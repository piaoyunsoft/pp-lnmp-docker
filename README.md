# LNMP Docker

专业级 LNMP 多站点 Docker 架构。Nginx 网关 + 各站点独立 PHP-FPM（5.6 / 7.x / 8.x 多版本）+ MySQL 8.4 (+5.7 老站可选) + Redis + Adminer + acme.sh 自动续期。

**Author**: PiaoYun
**Email**: <piaoyunsoft@163.com>
**Website**: <https://www.chinapyg.com>
**License**: MIT

## 目录

```
pp_lnmp_docker/
├── docker-compose.yml        # 共享网络 pp_lnmp_net
├── .env.example              # 配置模板 (复制为 .env)
├── lnmp.cmd                  # Windows 入口 (调 scripts/lnmp)
│
├── scripts/
│   ├── lnmp                  # 主 CLI (Bash)
│   └── lnmp.cmd              # Windows 包装
│
├── compose/                   # 所有服务 (像 DNMP 的 services/)
│   ├── nginx/                  # 统一入口 + acme.sh sidecar
│   │   ├── conf/
│   │   │   ├── nginx.conf      # 主配 (gzip/限流/open_file_cache/SSL)
│   │   │   ├── snippets/       # ssl.conf / php-fpm.conf 复用
│   │   │   ├── rewrite/        # 19 个常见程序伪静态规则
│   │   │   └── sites/          # vhost (lnmp site:add 自动生成)
│   │   └── logs/
│   ├── mysql/                  # MySQL 8.4 (主)
│   ├── mysql57/                # MySQL 5.7 (老站, 按需启)
│   ├── redis/                  # Redis 7.4 (按需启)
│   └── adminer/                # Adminer (按需启)
│
├── ssl/live/<domain>/        # 证书落地
│
├── php/
│   ├── Dockerfile.alpine     # PHP 7.2 ~ 8.4 (alpine, 小)
│   ├── Dockerfile.legacy     # PHP 5.6 / 7.0 / 7.1 (debian)
│   └── conf/
│       ├── 8x/               # PHP 8.x: opcache + JIT
│       ├── 7x/               # PHP 7.x: opcache 无 JIT
│       └── 56/               # PHP 5.6: 极简, 无 JIT
│
├── sites/
│   ├── _template/            # 站点模板 (含 phpinfo + DB 自检)
│   └── <key>/                # 各站独立 (key = 域名替换 .- 为 _)
│       ├── docker-compose.yml
│       ├── .env              # SITE/PHP/DB 凭据 (PHP getenv() 读)
│       ├── www/              # 你的代码
│       └── logs/             # PHP 日志
│
└── backups/                  # db:dump 输出
```

## 5 分钟上手

```cmd
:: 1. 复制配置, 至少改 MYSQL_ROOT_PASSWORD
copy .env.example .env
notepad .env

:: 2. 初始化目录 + docker 网络
lnmp init

:: 3. 启基础栈 (nginx + mysql + acme)
lnmp up

:: 4. 等 15-30 秒, 看全 healthy
lnmp ps

:: 5. 新建站点 (交互式问是否建库, 回车走默认)
lnmp site:add localhost 8.3

:: 6. 浏览器开 https://localhost  (Chrome 自签警告 → 盲打 thisisunsafe)
```

Linux 服务器路径换成 `./scripts/lnmp ...`。

## 命令速查

### 服务管理

| 用途 | 命令 |
|---|---|
| 看帮助 | `lnmp help` |
| **启动**（基础+用过的可选栈+所有站） | `lnmp up` |
| 指定栈启动 | `lnmp up redis adminer mysql57` |
| **重启所有站点**（公共服务不动，**安全**） | `lnmp restart` |
| 全部重启（公共+站点） | `lnmp restart --all` |
| 指定栈重启 | `lnmp restart nginx` |
| **只停所有站点**（公共服务保留） | `lnmp down` |
| 全停 | `lnmp down --all` |
| 指定栈停 | `lnmp down mysql` |
| 状态 | `lnmp ps` |
| 日志 | `lnmp logs nginx -f` |

> **重要**：`lnmp down` / `restart` **默认只动站点**，不影响共用的 MySQL/Nginx。`--all` 才是真"全部"。

### 站点

| 用途 | 命令 |
|---|---|
| 新增（默认 PHP 8.3） | `lnmp site:add a.com` |
| 指定版本 | `lnmp site:add a.com 7.4` |
| 老站（含 5.6） | `lnmp site:add old.com 5.6` |
| 套用 rewrite（Discuz/WP 等） | `lnmp site:add bbs.com 7.4 --rewrite=discuzx` |
| 给框架写 DB 凭据到 .env | `lnmp site:add api.com 8.3 --env-db` |
| 列出可用 rewrite | `lnmp site:rewrites` |
| 删除（保留 www） | `lnmp site:rm a.com` |
| 彻底删除 | `lnmp site:rm a.com --purge` |
| 列出站点 | `lnmp site:list` |
| **启动单站** | `lnmp site:up a.com` |
| **停止单站** | `lnmp site:down a.com` |
| 重启单站 | `lnmp site:restart a.com` |
| 重启+重 build | `lnmp site:restart a.com --build` |
| 单站日志 | `lnmp site:logs a.com -f` |
| reload nginx（改 vhost） | `lnmp site:reload` |

### 证书 (acme.sh)
| 用途 | 命令 |
|---|---|
| 签发 | `lnmp cert:issue a.com` |
| 全部续期 | `lnmp cert:renew` |
| 列表 | `lnmp cert:list` |


### 数据库 (默认 MySQL 8.4, 加 `-m 57` 切 5.7)
| 用途 | 命令 |
|---|---|
| 进 mysql | `lnmp db:cli` / `lnmp db:cli -m 57` |
| 建库+用户+授权 | `lnmp db:create mydb myuser mypass` |
| 改密码 (root 同步 .env) | `lnmp db:passwd root 新密` |
| 备份单库 | `lnmp db:dump mydb` |
| 备份全部 | `lnmp db:dumpall` |
| 导入 (支持 .gz) | `lnmp db:import backups/x.sql.gz` |
| 列用户 | `lnmp db:users` |

### Redis / PHP
| 用途 | 命令 |
|---|---|
| Redis CLI | `lnmp redis:cli` |
| Redis 改密 | `lnmp redis:passwd 新密` |
| 进站点 shell | `lnmp php:cli a.com` |
| Composer | `lnmp php:composer a.com install` |

## PHP 版本支持

| 版本 | Dockerfile | conf 目录 | 镜像基础 | 特性 |
|---|---|---|---|---|
| 5.6 | legacy | 56 | debian:stretch | 老 OpenSSL, 老 Discuz/ECShop 用 |
| 7.0 / 7.1 | legacy | 7x | debian:stretch | 无 JIT |
| 7.2 ~ 7.4 | alpine | 7x | alpine | 无 JIT, 兼容 WP/Laravel |
| 8.0 ~ 8.4 | alpine | 8x | alpine | **JIT tracing 64M**, 小内存优化 |

每站独立容器：`pp_lnmp_site_<key>_php`，互不影响。

## MySQL 双版本

- **MySQL 8.4** (`lnmp up mysql`)：默认，容器内主机名 `mysql`，端口 3306
- **MySQL 5.7** (`lnmp up mysql57`)：老站兼容，主机名 `mysql57` 或 `mysql5`，端口 3307
- 站点 PHP 通过 `DB_HOST=mysql` 或 `DB_HOST=mysql57` 区分（建站时自动写）

## 自动依赖管理

`lnmp up` 不需要手动指定起 mysql57 / redis — CLI 会**扫描所有站点 `.env` 的 `LNMP_USE_MYSQL`** 自动判断：

```
sites/old.com/.env:
  LNMP_USE_MYSQL=mysql57   ← CLI 看到这个, 自动起 mysql57

sites/new.com/.env:
  LNMP_USE_MYSQL=mysql     ← 默认就起的, 无所谓
```

删站点（`site:rm --purge`）→ 下次 `lnmp up` 重新评估 → 没有站点用的 mysql57 自动不起。

兜底：之前手动起过 mysql57 / redis（容器存在）也会自动续命，不强求站点标记。


## Rewrite 规则库（19 个常见程序）

内置 Discuz / WordPress / Laravel / ThinkPHP / Typecho / ECShop / Joomla / Drupal 等 19 个程序的伪静态规则（来自军哥 LNMP，GPL）。

```bash
lnmp site:add bbs.com 7.4 --rewrite=discuzx
lnmp site:add wp.com  8.3 --rewrite=wordpress
lnmp site:rewrites              # 查看完整列表
```

详见 [`nginx-gateway/conf/rewrite/README.md`](nginx-gateway/conf/rewrite/README.md)

## SSL 自动续期

- acme.sh 容器以 daemon 模式跑，每天自检
- **DNS 模式**（推荐，支持通配符 + 不开 80）：`.env` 配 `ACME_DNS_API=dns_cf` + CF Token
- **webroot 模式**（默认）：域名 A 记录指向本机，80 端口可达
- `site:add` 时自动生成自签占位证书 → nginx 配置指向占位证书 → 避免 nginx 起不来
- `lnmp cert:issue <domain>` 签发正式证书 → 自动更新 nginx 配置 → reload 生效

## 密码管理

| 位置 | 内容 | 怎么改 |
|---|---|---|
| 根 `.env` | `MYSQL_ROOT_PASSWORD` / `MYSQL57_ROOT_PASSWORD` / `REDIS_PASSWORD` | `lnmp db:passwd root xxx` / `lnmp redis:passwd xxx` (自动同步) |
| 站点 `sites/<key>/.env` | 仅 SITE/PHP 元数据，**默认不存 DB 密码** | - |
| 站点 `sites/<key>/.env` (`--env-db` 时) | `DB_HOST/DB_NAME/DB_USER/DB_PASSWORD` | 改完 `db:passwd` 后手动改这里 |
| 程序自己的配置 (Discuz `config_global.php` / WP `wp-config.php`) | 各自存 | 程序后台或文件改 |

**两种用法**：

- **装现成 CMS**（Discuz / WP / DedeCMS）：`lnmp site:add a.com 8.1` → 屏幕显示库凭据 → 在程序安装向导手动填 → 程序自己存配置
- **开发框架**（Laravel / Symfony / TP）：`lnmp site:add a.com 8.3 --env-db` → 凭据写入站点 `.env` → 代码 `getenv('DB_PASSWORD')` 读，源码不存密码

## 数据持久化

- **数据**：bind 挂载到项目目录（dnmp/laradock 风格，能直接看文件）
  - MySQL 8.4: `mysql/data/`
  - MySQL 5.7: `mysql57/data/`
  - Redis: `redis/data/`
- **配置/代码/日志**：bind 挂载，编辑器直改
- **不进 git**：`.gitignore` 已排除数据/日志/证书/`.env`
- **备份**：`lnmp db:dump <db>` → `backups/`（**强烈推荐**走 SQL，跨版本兼容）
- **整机迁移**：停服务 → `tar czf` 整个项目 → 新机解压 → `lnmp up`

## 已启用优化

- **Nginx**: HTTP/2、gzip、keepalive、limit_req/conn、open_file_cache、tmpfs 临时文件、安全头
- **PHP-FPM**: opcache + JIT tracing (8.x)、dynamic pm(10 子进程)、process_idle_timeout、慢日志(3s)、status/ping、tmpfs `/tmp`、300s 硬超时(安装够用, 生产防死进程)、grep 去重 zend_extension(8.3+ 已内置)
- **MySQL**: InnoDB buffer 可调、O_DIRECT、slow query、utf8mb4 + utf8mb4_unicode_ci (与 Discuz 一致)、8.4 不加 authentication-plugin/skip-client-handshake(已移除, 8.4 默认更优)
- **Redis**: AOF everysec、allkeys-lru、慢日志
- **PHP 安全**: disable_functions、expose_php=Off、request_order=GP、zend.assertions=-1、session strict 全开, 全面 ≥ DNMP 基准

## 多平台说明

| 平台 | 说明 |
|---|---|
| Windows + Docker Desktop | 能跑，**慢**（WSL2 文件 IO），开发够用 |
| WSL2 Ubuntu | 快很多，项目放 `~/lnmp_docker` |
| Linux 服务器（正式） | 原生速度，零修改部署 |
| macOS | 类似 Windows，VirtioFS 较快 |

正式上线建议：

1. `.env` 全部用强密码
2. `MYSQL_INNODB_BUFFER_POOL_SIZE` 调到内存 50%
3. 默认配置瞄准 1C1G~1C2G 小云服务器。机器更大时调 `php/conf/8x/www.conf` 的 `pm.max_children`（每子进程预算 ~64M）
4. `opcache.validate_timestamps=0` 不变（生产模式，改代码后需重启）
5. Adminer 端口绑 `127.0.0.1`
6. acme 用 DNS API 签通配符
7. cron 加备份：`0 3 * * * cd /opt/lnmp_docker && ./scripts/lnmp db:dumpall`

## 常见问题

| 问题 | 原因 | 解决 |
|---|---|---|
| `connect to mysql via localhost: No such file` | PHP `localhost` 走 socket | 改 host 为 `mysql` (容器名) |
| 自签证书警告 | 浏览器默认行为 | 签发正式证书: `lnmp cert:issue <domain>` |
| 证书已签发但浏览器不绿 | Git Bash 路径转换, 证书装错位置 | 升级到最新脚本 (已修复 `MSYS2_ARG_CONV_EXCL`) |
| 站点删了但 nginx 配置还在 | `site:rm` 检查目录存在时才删配置 | 手动 `rm nginx-gateway/conf/sites/<域名>.conf`, 然后 `lnmp site:reload` |
| 502 Bad Gateway | PHP 容器没起 | `docker logs pp_lnmp_site_<key>_php` |
| MySQL "World-writable config ignored" | Windows 卷权限 | 已用 command 参数绕过, 无影响 |
| `Container is restarting` | MySQL 配置错 / 数据不完整 | `docker logs pp_lnmp_mysql` 看真实错 |
| 大数据导入超时 | 默认还行, 超大可调 `max_execution_time` | `request_terminate_timeout=30s`, 超时可临时设为 0 |
| nginx orphan containers warning | 多 compose 同 project | 安全, 可忽略 |

## License

MIT License. See [LICENSE](LICENSE) for details.

## Author

**PiaoYun** ([@piaoyunsoft](https://github.com/piaoyunsoft))

- Email: <piaoyunsoft@163.com>
- Website: <https://www.chinapyg.com>
- 中国飘云阁论坛: 安全、逆向、编程爱好者社区

Contributions, issues, and feature requests are welcome.

