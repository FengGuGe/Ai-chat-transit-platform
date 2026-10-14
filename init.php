<?php
require_once 'config.php';

// 检查是否已登录且为管理员
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('权限不足，仅管理员可访问此页面');
}

$messages = [];

try {
    $pdo = getDB();
    
    // 创建用户表
    $sql1 = "CREATE TABLE IF NOT EXISTS `user` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` TINYINT(1) NOT NULL DEFAULT 2 COMMENT '1=管理员，2=普通用户',
        `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=启用，0=停用',
        `avatar` VARCHAR(255) DEFAULT NULL,
        `bio` TEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `last_login` DATETIME DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表'";
    $pdo->exec($sql1);
    $messages[] = "✅ 用户表(user)创建成功";

    // 创建AI模型表
    $sql2 = "CREATE TABLE IF NOT EXISTS `ai_model` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `api_url` VARCHAR(500) NOT NULL,
        `api_key` VARCHAR(255) NOT NULL,
        `model_identifier` VARCHAR(100) NOT NULL,
        `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=启用，0=停用',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI模型配置表'";
    $pdo->exec($sql2);
    $messages[] = "✅ AI模型表(ai_model)创建成功";

    // 创建对话记录表
    $sql3 = "CREATE TABLE IF NOT EXISTS `chat_record` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `model_id` INT NOT NULL,
        `message_type` TINYINT(1) NOT NULL COMMENT '1=用户消息，2=AI回复',
        `content` TEXT NOT NULL,
        `send_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user` (`user_id`),
        INDEX `idx_model` (`model_id`),
        INDEX `idx_user_model` (`user_id`, `model_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户对话记录表'";
    $pdo->exec($sql3);
    $messages[] = "✅ 对话记录表(chat_record)创建成功";

    // 插入默认管理员账号
    $checkAdmin = $pdo->query("SELECT id FROM user WHERE username = 'admin'");
    if ($checkAdmin->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO user (username, password, role, status) VALUES (?, ?, 1, 1)");
        $stmt->execute(['admin', md5('admin123')]);
        $messages[] = "✅ 默认管理员账号已创建 (admin / admin123)";
    }

} catch (PDOException $e) {
    $messages[] = "❌ 错误: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库初始化</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-lg p-6 max-w-md w-full">
        <h2 class="text-2xl font-bold mb-4">🗄️ 数据库初始化</h2>
        <?php foreach ($messages as $msg): ?>
            <div class="mb-2 p-3 rounded-lg <?php echo strpos($msg, '❌') !== false ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endforeach; ?>
        
        <?php if (strpos(implode('', $messages), '❌') === false): ?>
            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                <p class="text-sm text-yellow-800 font-medium">⚠️ 安全提醒：</p>
                <p class="text-sm text-yellow-700 mt-1">请立即从服务器删除 init.php 文件，防止数据库结构泄露。</p>
            </div>
        <?php endif; ?>
        
        <button onclick="window.location.href='index.php'" class="mt-4 w-full bg-blue-600 text-white py-3 rounded-xl font-bold">
            返回首页
        </button>
    </div>
</body>
</html>