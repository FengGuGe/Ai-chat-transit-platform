<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AI模型对话</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563EB'
                    }
                }
            }
        }
    </script>
    <style>
        .touch-btn { min-height: 44px; min-width: 44px; display: inline-flex; align-items: center; justify-content: center; }
        .modal-overlay { background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-lg mx-auto bg-white min-h-screen shadow-lg">
        <!-- 头部 -->
        <header class="sticky top-0 bg-white border-b px-4 py-3 flex items-center justify-between z-10">
            <h1 class="font-bold text-lg text-primary">🤖 AI模型对话</h1>
            <div id="headerBtns">
                <button onclick="window.location.href='login.php'" class="touch-btn bg-primary text-white px-4 py-2 rounded-full text-sm" id="loginBtn">登录</button>
                <div id="userActions" class="hidden items-center gap-2">
                    <button onclick="window.location.href='profile.php'" class="touch-btn text-sm bg-gray-100 px-3 py-2 rounded-full">个人中心</button>
                    <button onclick="logout()" class="touch-btn text-sm text-red-500">退出</button>
                </div>
                <button onclick="window.location.href='admin.php'" class="touch-btn bg-gray-800 text-white px-3 py-2 rounded-full text-sm hidden" id="adminBtn">后台</button>
            </div>
        </header>

        <!-- 模型列表 -->
        <main class="p-4 pb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">探索AI模型</h2>
                <button onclick="loadModels()" class="text-primary text-sm touch-btn">🔄 刷新</button>
            </div>
            <div id="modelList" class="space-y-3">
                <p class="text-center text-gray-400 py-12">加载中...</p>
            </div>
        </main>
    </div>

    <!-- 登录提示弹窗 -->
    <div id="loginPrompt" class="fixed inset-0 z-50 modal-overlay flex items-center justify-center hidden">
        <div class="bg-white rounded-2xl p-6 w-80">
            <p class="text-center mb-4">请登录账号后，才可与AI模型对话</p>
            <button onclick="window.location.href='login.php'" class="w-full bg-primary text-white py-3 rounded-xl font-medium">去登录</button>
            <button onclick="closePrompt()" class="w-full text-gray-500 py-2 mt-2">取消</button>
        </div>
    </div>

    <script>
        let currentUser = null;
        
        // 检查登录状态
        async function checkLogin() {
            try {
                const formData = new FormData();
                formData.append('action', 'session');
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    currentUser = data.user;
                    updateHeader();
                } else {
                    currentUser = null;
                }
            } catch(e) {
                currentUser = null;
            }
            updateHeader();
        }
        
        function updateHeader() {
            const loginBtn = document.getElementById('loginBtn');
            const userActions = document.getElementById('userActions');
            const adminBtn = document.getElementById('adminBtn');
            
            if (currentUser) {
                loginBtn.classList.add('hidden');
                userActions.classList.remove('hidden');
                userActions.classList.add('flex');
                
                if (currentUser.role === 'admin') {
                    adminBtn.classList.remove('hidden');
                }
            } else {
                loginBtn.classList.remove('hidden');
                userActions.classList.add('hidden');
                adminBtn.classList.add('hidden');
            }
        }
        
        // 加载模型列表
        async function loadModels() {
            const listDiv = document.getElementById('modelList');
            listDiv.innerHTML = '<p class="text-center text-gray-400 py-12">加载中...</p>';
            
            try {
                const formData = new FormData();
                formData.append('action', 'models');
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success && data.models.length > 0) {
                    listDiv.innerHTML = data.models.map(m => `
                        <div class="bg-white border rounded-2xl p-4 shadow-sm flex items-center ${m.status == 0 ? 'opacity-50' : ''}">
                            <div class="flex-1">
                                <h3 class="font-bold">${escapeHtml(m.name)}</h3>
                                <p class="text-sm text-gray-500 mt-1">${escapeHtml(m.description || '暂无简介')}</p>
                                <span class="inline-block mt-2 px-2 py-0.5 rounded text-xs ${m.status == 1 ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500'}">
                                    ${m.status == 1 ? '可用' : '停用'}
                                </span>
                            </div>
                            <button onclick="startChat(${m.id})" 
                                class="touch-btn ml-3 px-4 py-2 rounded-xl text-sm text-white ${m.status == 1 ? 'bg-primary' : 'bg-gray-300 cursor-not-allowed'}"
                                ${m.status == 0 ? 'disabled' : ''}>
                                开始对话
                            </button>
                        </div>
                    `).join('');
                } else {
                    listDiv.innerHTML = '<p class="text-center text-gray-400 py-12">暂无可用模型</p>';
                }
            } catch(e) {
                listDiv.innerHTML = '<p class="text-center text-red-400 py-12">加载失败</p>';
            }
        }
        
        function startChat(modelId) {
            if (!currentUser) {
                document.getElementById('loginPrompt').classList.remove('hidden');
                return;
            }
            window.location.href = `chat.php?model=${modelId}`;
        }
        
        function closePrompt() {
            document.getElementById('loginPrompt').classList.add('hidden');
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
            window.location.reload();
        }
        
        // 初始化
        checkLogin();
        loadModels();
    </script>
</body>
</html>