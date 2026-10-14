<?php 
require_once 'config.php'; 
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人中心</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-lg mx-auto bg-white min-h-screen shadow-lg">
        <header class="border-b px-4 py-3 flex items-center">
            <button onclick="window.location.href='index.php'" class="touch-btn text-2xl mr-3">←</button>
            <h1 class="font-bold text-lg">个人中心</h1>
        </header>
        
        <main class="p-4 space-y-6" id="profileContent">
            <p class="text-center py-8">加载中...</p>
        </main>
    </div>

    <script>
        async function loadProfile() {
            const formData = new FormData();
            formData.append('action', 'session');
            const res = await fetch('api.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                const user = data.user;
                document.getElementById('profileContent').innerHTML = `
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h3 class="font-bold mb-2">基本信息</h3>
                        <p class="text-sm text-gray-600">用户名: ${escapeHtml(user.username)}</p>
                        <p class="text-sm text-gray-600">账号类型: ${user.role === 'admin' ? '管理员' : '普通用户'}</p>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="block text-sm font-medium">个人简介</label>
                        <textarea id="bioInput" class="w-full border rounded-xl p-3" rows="3">加载中...</textarea>
                        <button onclick="updateBio()" class="touch-btn bg-blue-600 text-white px-4 py-2 rounded-xl text-sm">保存简介</button>
                    </div>
                    
                    <div class="space-y-3">
                        <h3 class="font-bold">修改密码</h3>
                        <input type="password" id="oldPwd" placeholder="原密码" class="w-full border p-3 rounded-xl">
                        <input type="password" id="newPwd" placeholder="新密码" class="w-full border p-3 rounded-xl">
                        <button onclick="changePwd()" class="touch-btn bg-blue-600 text-white px-4 py-2 rounded-xl text-sm">修改密码</button>
                    </div>
                    
                    <button onclick="logout()" class="w-full bg-red-500 text-white py-3 rounded-xl font-bold mt-6">退出登录</button>
                `;
            }
        }
        
        async function updateBio() {
            const bio = document.getElementById('bioInput').value.trim();
            const formData = new FormData();
            formData.append('action', 'update_bio');
            formData.append('bio', bio);
            const res = await fetch('api.php', { method: 'POST', body: formData });
            const data = await res.json();
            alert(data.success ? '已保存' : '失败');
        }
        
        async function changePwd() {
            const oldPwd = document.getElementById('oldPwd').value;
            const newPwd = document.getElementById('newPwd').value;
            if (!oldPwd || !newPwd) { alert('请填写完整'); return; }
            
            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('old_password', oldPwd);
            formData.append('new_password', newPwd);
            const res = await fetch('api.php', { method: 'POST', body: formData });
            const data = await res.json();
            alert(data.message || (data.success ? '修改成功' : '失败'));
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        async function logout() {
            const formData = new FormData();
            formData.append('action', 'logout');
            await fetch('api.php', { method: 'POST', body: formData });
            window.location.href = 'index.php';
        }
        
        loadProfile();
    </script>
</body>
</html>