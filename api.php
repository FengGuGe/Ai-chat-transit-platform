<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getDB();
    
    switch ($action) {
        // 登录
        case 'login':
            handleLogin($pdo);
            break;
        
        // 登出
        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;
        
        // 获取会话状态
        case 'session':
            checkLogin();
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'role' => $_SESSION['role']
                ]
            ]);
            break;
        
        // 获取模型列表
        case 'models':
            $stmt = $pdo->query("SELECT id, name, description, status, created_at FROM ai_model ORDER BY id DESC");
            echo json_encode(['success' => true, 'models' => $stmt->fetchAll()]);
            break;
        
        // 获取聊天记录
        case 'chats':
            checkLogin();
            $model_id = intval($_POST['model_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM chat_record WHERE user_id = ? AND model_id = ? ORDER BY send_time ASC");
            $stmt->execute([$_SESSION['user_id'], $model_id]);
            echo json_encode(['success' => true, 'records' => $stmt->fetchAll()]);
            break;
        
        // 发送消息
        case 'send_message':
            checkLogin();
            $model_id = intval($_POST['model_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            
            if (empty($message) || mb_strlen($message) > 2000) {
                echo json_encode(['success' => false, 'message' => '消息长度无效']);
                exit;
            }
            
            // 保存用户消息
            $stmt = $pdo->prepare("INSERT INTO chat_record (user_id, model_id, message_type, content, send_time) VALUES (?, ?, 1, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $model_id, $message]);
            
            // 获取模型配置
            $stmt = $pdo->prepare("SELECT * FROM ai_model WHERE id = ? AND status = 1");
            $stmt->execute([$model_id]);
            $model = $stmt->fetch();
            
            if (!$model) {
                echo json_encode(['success' => false, 'message' => '模型不可用']);
                exit;
            }
            
            // 调用AI接口
            $aiResponse = callAIAPI($model['api_url'], $model['api_key'], $message);
            
            // 保存AI回复
            $stmt = $pdo->prepare("INSERT INTO chat_record (user_id, model_id, message_type, content, send_time) VALUES (?, ?, 2, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $model_id, $aiResponse]);
            
            echo json_encode(['success' => true, 'reply' => $aiResponse]);
            break;
        
        // 清空对话
        case 'clear_chat':
            checkLogin();
            $model_id = intval($_POST['model_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM chat_record WHERE user_id = ? AND model_id = ?");
            $stmt->execute([$_SESSION['user_id'], $model_id]);
            echo json_encode(['success' => true, 'message' => '对话已清空']);
            break;
        
        // 更新个人简介
        case 'update_bio':
            checkLogin();
            $bio = trim($_POST['bio'] ?? '');
            $stmt = $pdo->prepare("UPDATE user SET bio = ? WHERE id = ?");
            $stmt->execute([$bio, $_SESSION['user_id']]);
            echo json_encode(['success' => true]);
            break;
        
        // 修改密码
        case 'change_password':
            checkLogin();
            $old_pwd = $_POST['old_password'] ?? '';
            $new_pwd = $_POST['new_password'] ?? '';
            
            $stmt = $pdo->prepare("SELECT password FROM user WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user['password'] !== md5($old_pwd)) {
                echo json_encode(['success' => false, 'message' => '原密码错误']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE user SET password = ? WHERE id = ?");
            $stmt->execute([md5($new_pwd), $_SESSION['user_id']]);
            echo json_encode(['success' => true, 'message' => '密码修改成功']);
            break;
        
        // 管理：获取所有用户
        case 'admin_users':
            checkAdmin();
            $stmt = $pdo->query("SELECT id, username, role, status, bio, created_at, last_login FROM user ORDER BY id DESC");
            echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
            break;
        
        // 管理：获取所有模型
        case 'admin_models':
            checkAdmin();
            $stmt = $pdo->query("SELECT * FROM ai_model ORDER BY id DESC");
            echo json_encode(['success' => true, 'models' => $stmt->fetchAll()]);
            break;
        
        // 管理：获取所有聊天记录
        case 'admin_chats':
            checkAdmin();
            $sql = "SELECT cr.*, u.username, am.name as model_name 
                    FROM chat_record cr 
                    JOIN user u ON cr.user_id = u.id 
                    JOIN ai_model am ON cr.model_id = am.id 
                    ORDER BY cr.send_time DESC LIMIT 500";
            $stmt = $pdo->query($sql);
            echo json_encode(['success' => true, 'records' => $stmt->fetchAll()]);
            break;
        
        // 管理：添加模型
        case 'admin_add_model':
            checkAdmin();
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $api_url = trim($_POST['api_url'] ?? '');
            $api_key = trim($_POST['api_key'] ?? '');
            $model_id = trim($_POST['model_identifier'] ?? '');
            
            $stmt = $pdo->prepare("INSERT INTO ai_model (name, description, api_url, api_key, model_identifier, status) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$name, $desc, $api_url, $api_key, $model_id]);
            echo json_encode(['success' => true]);
            break;
        
        // 管理：更新模型
        case 'admin_update_model':
            checkAdmin();
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $api_url = trim($_POST['api_url'] ?? '');
            $api_key = trim($_POST['api_key'] ?? '');
            $model_id = trim($_POST['model_identifier'] ?? '');
            $status = intval($_POST['status'] ?? 1);
            
            $stmt = $pdo->prepare("UPDATE ai_model SET name=?, description=?, api_url=?, api_key=?, model_identifier=?, status=? WHERE id=?");
            $stmt->execute([$name, $desc, $api_url, $api_key, $model_id, $status, $id]);
            echo json_encode(['success' => true]);
            break;
        
        // 管理：删除模型
        case 'admin_delete_model':
            checkAdmin();
            $id = intval($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM ai_model WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        
        // 管理：添加用户
        case 'admin_add_user':
            checkAdmin();
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $role = intval($_POST['role'] ?? 2);
            
            $stmt = $pdo->prepare("INSERT INTO user (username, password, role, status) VALUES (?, ?, ?, 1)");
            $stmt->execute([$username, md5($password), $role]);
            echo json_encode(['success' => true]);
            break;
        
        // 管理：删除用户
        case 'admin_delete_user':
            checkAdmin();
            $id = intval($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM chat_record WHERE user_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM user WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => '无效请求']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// 辅助函数
function handleLogin($pdo) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ? AND status = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['password'] === md5($password)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'] == 1 ? 'admin' : 'user';
        
        // 更新最后登录时间
        $stmt = $pdo->prepare("UPDATE user SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '用户名或密码错误']);
    }
}

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => '未登录']);
        exit;
    }
}

function checkAdmin() {
    checkLogin();
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => '权限不足']);
        exit;
    }
}

function callAIAPI($api_url, $api_key, $message) {
    // 这里模拟AI回复，实际使用时替换为真实API调用
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'message' => $message,
        'max_tokens' => 500
    ]));
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        return "AI服务暂时不可用，请稍后再试";
    }
    
    curl_close($ch);
    $data = json_decode($response, true);
    
    return $data['reply'] ?? "我已收到您的消息，正在处理中...";
}
?>