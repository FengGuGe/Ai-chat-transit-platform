<?php
/**
 * 数据库配置文件
 * 请根据您的虚拟主机数据库信息修改以下参数
 */
define('DB_HOST', 'localhost');        // 数据库地址
define('DB_NAME', 'f691284w');       // 数据库名
define('DB_USER', 'f691284w');      // 数据库用户名
define('DB_PASS', 'f567829w');      // 数据库密码
define('DB_CHARSET', 'utf8mb4');       // 字符集

// 安全密钥（用于token生成）
define('SECRET_KEY', 'your_secret_key_2024');

// 会话启动
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 获取PDO数据库连接
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>