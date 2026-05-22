<?php
/**
 * LNMP Docker - 站点占位首页 (由 lnmp site:add 生成, 可随时删除)
 *
 * @author  PiaoYun <piaoyunsoft@163.com>
 * @link    https://www.chinapyg.com
 * @license MIT
 */

$db_host = getenv('DB_HOST') ?: '';
$db_name = getenv('DB_NAME') ?: '';
$db_user = getenv('DB_USER') ?: '';
$db_pass = getenv('DB_PASSWORD') ?: '';

echo "<h1>" . htmlspecialchars(getenv('SITE_DOMAIN') ?: 'site') . " is up</h1>";
echo "<p>PHP " . PHP_VERSION . "</p>";

if ($db_host && $db_name) {
    echo "<h2>DB check</h2>";
    try {
        $pdo = new PDO(
            "mysql:host={$db_host};port=" . (getenv('DB_PORT') ?: 3306) . ";dbname={$db_name};charset=utf8mb4",
            $db_user, $db_pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $v = $pdo->query("SELECT VERSION()")->fetchColumn();
        echo "<p style='color:green'>OK - MySQL $v / db=$db_name / user=$db_user</p>";
    } catch (Throwable $e) {
        echo "<p style='color:red'>FAIL: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<hr><details><summary>phpinfo()</summary>";
phpinfo();
echo "</details>";

echo '<hr><footer style="color:#888;font-size:12px;margin-top:20px;">'
   . 'Powered by <a href="https://www.chinapyg.com" style="color:#888;">LNMP Docker</a> · '
   . 'by PiaoYun &lt;piaoyunsoft@163.com&gt;'
   . '</footer>';
