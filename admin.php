<?php 
require_once 'config.php'; 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-lg mx-auto bg-white min-h-screen shadow-lg">
        <header class="border-b px-4 py-3 flex items-center">
            <button onclick="window.location.href='index.php'" class="touch-btn text-2xl mr-3">←</button>
            <h1 class="font-bold text-lg">后台管理</h1>
        </header>
        
        <nav class="flex border-b">
            <button onclick="switchTab('models')" id="tab-models" class="flex-1 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600">模型管理</button>
            <button onclick="switchTab('users')" id="tab-users" class="flex-1 py-3 text-sm font-medium text-gray-500">用户管理</button>
            <button onclick="switchTab('chats')" id="tab-chats" class="flex-1 py-3 text-sm font-medium text-gray-500">对话记录</button>
        </nav>
        
        <main id="adminContent" class="p-4">
            <p class="text-center py-8">加载中...</p>
        </main>
    </div>

    <script>
        let currentTab = 'models';
        
        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('nav button').forEach(b => {
                b.classList.remove('border-blue-600', 'text-blue-600');
                b.classList.add('text-gray-500');
            });
            document.getElementById(`tab-${tab}`).classList.add('border-blue-600', 'text-blue-600');
            document.getElementById(`tab-${tab}`).classList.remove('text-gray-500');
            loadTabContent();
        }
        
        async function loadTabContent() {
            const content = document.getElementById('adminContent');
            content.innerHTML = '<p class="text-center py-8">加载中...</p>';
            
            if (currentTab === 'models') {
                const formData = new FormData();
                formData.append('action', 'admin_models');
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    content.innerHTML = `
                        <button onclick="addModel()" class="w-full bg-blue-600 text-white py-3 rounded-xl mb-4">+ 新增模型</button>
                        ${data.models.map(m => `
                            <div class="border rounded-xl p-4 mb-3">
                                <h3 class="font-bold">${escapeHtml(m.name)}</h3>
                                <p class="text-sm text-gray-500">${escapeHtml(m.api_url)}</p>
                                <div class="flex gap-2 mt-3">
                                    <button onclick="editModel(${m.id})" class="text-blue-600 text-sm">编辑</button>
                                    <button onclick="deleteModel(${m.id})" class="text-red-500 text-sm">删除</button>
                                </div>
                            </div>
                        `).join('')}
                    `;
                }
            } else if (currentTab === 'users') {
                const formData = new FormData();
                formData.append('action', 'admin_users');
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    content.innerHTML = data.users.map(u => `
                        <div class="border rounded-xl p-4 mb-3">
                            <h3 class="font-bold">${escapeHtml(u.username)}</h3>
                            <p class="text-sm text-gray-500">类型: ${u.role == 1 ? '管理员' : '普通用户'}</p>
                            <button onclick="deleteUser(${u.id})" class="text-red-500 text-sm mt-2">删除用户</button>
                        </div>
                    `).join('');
                }
            } else if (currentTab === 'chats') {
                const formData = new FormData();
                formData.append('action', 'admin_chats');
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    content.innerHTML = data.records.map(r => `
                        <div class="border rounded-xl p-4 mb-3">
                            <p class="text-xs text-gray-400">用户: ${escapeHtml(r.username)} | 模型: ${escapeHtml(r.model_name)} | ${r.send_time}</p>
                            <p class="mt-1">${escapeHtml(r.content.substring(0, 100))}...</p>
                        </div>
                    `).join('');
                }
            }
        }
        
        async function addModel() {
            const name = prompt('模型名称:');
            if (!name) return;
            const api_url = prompt('API地址:');
            const api_key = prompt('API密钥:');
            
            const formData = new FormData();
            formData.append('action', 'admin_add_model');
            formData.append('name', name);
            formData.append('api_url', api_url);
            formData.append('api_key', api_key);
            formData.append('model_identifier', name);
            
            const res = await fetch('api.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) loadTabContent();
        }
        
        async function deleteModel(id) {
            if (!confirm('确定删除？')) return;
            const formData = new FormData();
            formData.append('action', 'admin_delete_model');
            formData.append('id', id);
            await fetch('api.php', { method: 'POST', body: formData });
            loadTabContent();
        }
        
        async function deleteUser(id) {
            if (!confirm('确定删除用户及其对话？')) return;
            const formData = new FormData();
            formData.append('action', 'admin_delete_user');
            formData.append('id', id);
            await fetch('api.php', { method: 'POST', body: formData });
            loadTabContent();
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
        
        // 初始化
        loadTabContent();
    </script>
</body>
</html>