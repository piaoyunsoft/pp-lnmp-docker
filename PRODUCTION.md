# 生产部署 Checklist

新服务器上线前过一遍。

**Author**: PiaoYun
**Email**: <piaoyunsoft@163.com>
**Website**: <https://www.chinapyg.com>

## 0. 系统准备 (Linux)

### 装 Docker
```bash
# Ubuntu / Debian
curl -fsSL https://get.docker.com | sh
systemctl enable --now docker

# CentOS / RHEL
yum install -y yum-utils
yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
yum install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
systemctl enable --now docker
```

### 系统内核参数 (/etc/sysctl.d/99-lnmp.conf)
```conf
# 文件描述符
fs.file-max = 2097152

# TCP
net.core.somaxconn = 65535
net.core.netdev_max_backlog = 32768
net.ipv4.tcp_max_syn_backlog = 65535
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 15
net.ipv4.ip_local_port_range = 10000 65000

# 内存
vm.swappiness = 10
vm.overcommit_memory = 1

# 容器
net.bridge.bridge-nf-call-iptables = 1
```
应用: `sysctl -p /etc/sysctl.d/99-lnmp.conf`

### ulimit (/etc/security/limits.conf)
```
* soft nofile 1048576
* hard nofile 1048576
* soft nproc  unlimited
* hard nproc  unlimited
```

### 防火墙
只开放 22/80/443:
```bash
# ufw
ufw allow 22,80,443/tcp && ufw enable
# firewalld
firewall-cmd --permanent --add-service={ssh,http,https} && firewall-cmd --reload
```

## 1. 部署项目

```bash
git clone <你的仓库> /opt/lnmp_docker
# 或 rsync -avz lnmp_docker/ root@server:/opt/lnmp_docker/

cd /opt/lnmp_docker
chmod +x scripts/lnmp scripts/cron-backup
```

## 2. 配置 .env (强密码!)

```bash
cp .env.example .env
vim .env
```

**必改项**:

| 变量 | 推荐 |
|---|---|
| `MYSQL_ROOT_PASSWORD` | `openssl rand -base64 32` 生成 |
| `REDIS_PASSWORD` | 同上 |
| `ACME_EMAIL` | 你的真实邮箱 (Let's Encrypt 用) |
| `BIND_HOST_ADMIN` | `127.0.0.1` (默认就是, 别改) |
| `BIND_HOST` | 留空 (公网访问 nginx) |
| `MYSQL_INNODB_BUFFER_POOL_SIZE` | 内存 50%-60% (8G 给 4G, 16G 给 10G) |
| `MYSQL_INNODB_BUFFER_POOL_INSTANCES` | buffer_pool_size 的 GB 数 (4G 设 4) |

## 3. 启动 + 加站点

```bash
./scripts/lnmp init
./scripts/lnmp up                          # nginx + mysql
./scripts/lnmp up redis                    # 如需

./scripts/lnmp site:add yourdomain.com 8.3
```

## 4. SSL (生产)

### 推荐 DNS API (通配符 + 不用开 80)

`.env` 配:
```
ACME_DNS_API=dns_cf
CF_Token=xxxx
CF_Account_ID=xxxx
CF_Zone_ID=xxxx
```
然后:
```bash
./scripts/lnmp cert:issue yourdomain.com
```

### 或 webroot (单域)
域名 A 记录指向服务器, 80 端口可达, 直接:
```bash
./scripts/lnmp cert:issue yourdomain.com
```

acme.sh daemon 每天自动续期, 无需手动。

## 5. 定时备份

```bash
crontab -e
# 加一行:
0 3 * * *  /opt/lnmp_docker/scripts/cron-backup >> /var/log/lnmp-backup.log 2>&1
```

备份保留 14 天 (可改 `KEEP_DAYS`)。

## 6. 监控 (可选)

最简单的: 加个 cron 检测健康:

```bash
*/5 * * * * docker ps --filter "name=lnmp_" --filter "health=unhealthy" --format '{{.Names}}' | mail -s 'LNMP unhealthy' you@example.com
```

## 7. 安全 Checklist

- [x] BIND_HOST_ADMIN=127.0.0.1 (DB/Redis/Adminer 不暴露公网)
- [x] 所有密码 32 位以上随机
- [x] 默认 HTTPS server 拒绝未知 SNI (防漏)
- [x] PHP 容器不继承根 .env (隔离密码)
- [x] HSTS + secure cookie + samesite Lax
- [x] PHP `disable_functions` 已禁危险函数
- [x] 防火墙只开 22/80/443
- [ ] SSH 改公钥, 禁密码
- [ ] fail2ban 防爆破
- [ ] root 禁登, 用 sudo

## 8. 性能验证

```bash
./scripts/lnmp ps                          # 全 healthy
./scripts/lnmp logs nginx                  # 无 error
./scripts/lnmp logs mysql                  # 无 error

# Apache Bench 简单压测
ab -n 1000 -c 50 https://yourdomain.com/
```

## 9. 升级流程

```bash
cd /opt/lnmp_docker
git pull                                   # 拉新配置
./scripts/lnmp pull                        # 拉新镜像
./scripts/lnmp restart                     # 默认只重启站点 (公共服务保留, 不影响其它站)
# 或者: ./scripts/lnmp restart --all       # 真全重启 (含 MySQL/Nginx, 短暂中断)
# 或者: ./scripts/lnmp restart nginx       # 只重启 nginx
```

升级前先备份: `./scripts/cron-backup`

**站点级操作** (不影响其它站):

```bash
./scripts/lnmp site:restart bbs.com           # 改了 PHP 配置, 单站重启
./scripts/lnmp site:restart bbs.com --build   # 改了 Dockerfile, 重 build
./scripts/lnmp site:down bbs.com              # 暂停一个站 (保留数据)
./scripts/lnmp site:up   bbs.com              # 恢复
```

## 10. 灾难恢复

```bash
# 1. 新服务器装好 docker + 拉项目
# 2. 复制 .env + sites/ + ssl/ + backups/ 过去
# 3. 启动
./scripts/lnmp init
./scripts/lnmp up

# 4. 恢复数据
./scripts/lnmp db:import backups/all-mysql-xxxx.sql.gz
```

数据目录 `mysql/data` 等会自动重建, 数据从 SQL 备份导入。
