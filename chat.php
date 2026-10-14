<?php require_once 'config.php'; 
// 检查登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$model_id = intval($_GET['model'] ?? 0);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AI对话</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .touch-btn { min-height: 44px; min-width: 44px; display: inline-flex; align-items: center; justify-content: center; }
        .chat-bubble-user { background: #DBEAFE; border-radius: 16px 16px 4px 16px; }
        .chat-bubble-ai { background: #F3F4F6; border-radius: 16px 16px 16px 4px; }
    </style>
</head>
<body class="bg-white h-screen flex flex-col">
    <header class="border-b px-4 py-3 flex items-center bg-white">
        <button onclick="window.location.href='index.php'" class="touch-btn text-2xl mr-3">←</button>
        <span id="modelName" class="font-bold text-lg">加载中...</span>
        <button onclick="clearChat()" class="touch-btn text-red-500 text-sm ml-auto">清空对话</button>
    </header>

    <main id="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
        <p class="text-center text-gray-400 mt-8">开始对话吧...</p>
    </main>

    <footer class="border-t p-3 bg-white">
        <div class="flex gap-2">
            <textarea id="messageInput" rows="2" class="flex-1 border rounded-xl p-3 resize-none" placeholder="输入消息..."></textarea>
            <button id="sendBtn" onclick="sendMessage()" class="touch-btn bg-blue-600 text-white px-5 rounded-xl font-bold">发送</button>
        </div>
    </footer>

    <script>
        const modelId = <?php echo $model_id; ?>;
        let sending = false;
        
        // 加载聊天记录
        async function loadChats() {
            const formData = new FormData();
            formData.append('action', 'chats');
            formData.append('model_id', modelId);
            
            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                const container = document.getElementById('chatContainer');
                if (data.success && data.records.length > 0) {
                    container.innerHTML = data.records.map(r => `
                        <div class="flex ${r.message_type == 1 ? 'justify-end' : 'justify-start'}">
                            <div class="max-w-[80%] p-3 ${r.message_type == 1 ? 'chat-bubble-user' : 'chat-bubble-ai'}">
                                <p class="whitespace-pre-wrap break-words">${escapeHtml(r.content)}</p>
                                <p class="text-xs text-gray-400 mt-1">${r.send_time}</p>
                            </div>
                        </div>
                    `).join('');
                    container.scrollTop = container.scrollHeight;
                } else {
                    container.innerHTML = '<p class="text-center text-gray-400 mt-8">开始对话吧...</p>';
                }
            } catch(e) {
                console.error(e);
            }
        }
        
        // 发送消息
        async function sendMessage() {
            if (sending) return;
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            if (!message) return;
            
            sending = true;
            document.getElementById('sendBtn').disabled = true;
            document.getElementById('sendBtn').textContent = '...';
            
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('model_id', modelId);
            formData.append('message', message);
            
            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    input.value = '';
                    await loadChats();
                } else {
                    alert(data.message || '发送失败');
                }
            } catch(e) {
                alert('网络错误');
            } finally {
                sending = false;
                document.getElementById('sendBtn').disabled = false;
                document.getElementById('sendBtn').textContent = '发送';
            }
        }
        
        // 清空对话
        async function clearChat() {
            if (!confirm('确定要清空当前对话吗？')) return;
            
            const formData = new FormData();
            formData.append('action', 'clear_chat');
            formData.append('model_id', modelId);
            
            await fetch('api.php', { method: 'POST', body: formData });
            await loadChats();
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // 初始化
        loadChats();
        
        // 回车发送
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
</body>
</html>