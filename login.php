<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - AI模型对话</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-sm">
        <h2 class="text-2xl font-bold text-center mb-6">登录账号</h2>
        <form id="loginForm" class="space-y-4">
            <input type="text" id="username" placeholder="用户名" class="w-full border p-3 rounded-xl" required>
            <input type="password" id="password" placeholder="密码" class="w-full border p-3 rounded-xl" required>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold touch-btn">登录</button>
        </form>
        <p class="text-xs text-gray-400 mt-4 text-center">管理员: admin / admin123</p>
        <button onclick="window.location.href='index.php'" class="w-full text-gray-500 mt-3 py-2">返回首页</button>
        <div id="errorMsg" class="hidden mt-3 p-3 bg-red-50 text-red-600 rounded-lg text-sm"></div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('username', username);
            formData.append('password', password);
            
            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    window.location.href = 'index.php';
                } else {
                    const errorDiv = document.getElementById('errorMsg');
                    errorDiv.textContent = data.message || '登录失败';
                    errorDiv.classList.remove('hidden');
                }
            } catch(e) {
                alert('网络错误');
            }
        });
    </script>
</body>
</html>