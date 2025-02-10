<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DeepSeek R1 模型测试</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .fade-enter-active, .fade-leave-active {
            transition: opacity 0.5s;
        }
        .fade-enter-from, .fade-leave-to {
            opacity: 0;
        }
        .loading-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 1rem;
            border-radius: 0.5rem;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            z-index: 50;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div id="app" class="max-w-2xl mx-auto p-6">
        <!-- 标题部分 -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">DeepSeek R1 模型测试</h1>
            <p class="text-gray-600">输入文本以获取模型输出</p>
        </div>

        <!-- 输入框和按钮 -->
        <div class="mb-4">
            <textarea v-model="inputText" placeholder="请输入要测试的文本..." class="w-full p-2 border rounded"></textarea>
            <button @click="processInput" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                <i class="fas fa-search"></i> 提交
            </button>
            <button @click="clearContext" class="mt-2 ml-2 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                <i class="fas fa-trash-alt"></i> 清空上下文
            </button>
        </div>

        <!-- 对话历史显示 -->
        <div class="mt-4 space-y-2">
            <template v-for="(message, index) in conversation" :key="index">
                <div :class="{'bg-gray-100': message.role === 'user', 'bg-blue-50': message.role === 'assistant'}" class="p-4 rounded">
                    <p><strong>@{{ message.role }}:</strong> @{{ message.content }}</p>
                </div>
            </template>
        </div>

        <!-- 错误提示 -->
        <div v-if="error" class="mt-4 bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                <p class="text-red-700">@{{ error }}</p>
            </div>
        </div>

        <!-- Toast 通知 -->
        <div v-if="toast.show" class="toast fixed bottom-20 right-20 flex items-center p-2 bg-gray-800 text-white rounded shadow-lg">
            <i :class="toast.icon" class="mr-2"></i>
            <p>@{{ toast.message }}</p>
        </div>
    </div>

    <script>
        const { createApp } = Vue

        createApp({
            data() {
                return {
                    inputText: '',
                    conversation: [], // 存储对话历史
                    loading: false,
                    error: null,
                    toast: {
                        show: false,
                        message: '',
                        icon: '',
                        timer: null
                    }
                }
            },
            methods: {
                showToast(message, type = 'info') {
                    if (this.toast.timer) {
                        clearTimeout(this.toast.timer);
                    }

                    const icons = {
                        info: 'fas fa-info-circle',
                        success: 'fas fa-check-circle',
                        error: 'fas fa-exclamation-circle'
                    };

                    this.toast.message = message;
                    this.toast.icon = icons[type];
                    this.toast.show = true;

                    this.toast.timer = setTimeout(() => {
                        this.toast.show = false;
                    }, 3000);
                },
                async processInput() {
                    this.loading = true;
                    this.error = null;

                    try {
                        const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');

                        if (!csrfTokenElement || !csrfTokenElement.content) {
                            throw new Error('CSRF 令牌未找到');
                        }

                        const response = await fetch('/api/deepseek/process', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfTokenElement.content
                            },
                            body: JSON.stringify({ text: this.inputText })
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.error);
                        }

                        // 添加用户消息到对话历史
                        this.conversation.push({
                            role: 'user',
                            content: this.inputText
                        });

                        // 添加助手回复到对话历史
                        this.conversation.push({
                            role: 'assistant',
                            content: data.result
                        });

                        this.inputText = ''; // 清空输入框
                    } catch (e) {
                        this.error = e.message;
                        this.showToast('处理失败', 'error');
                    } finally {
                        this.loading = false;
                    }
                },
                async clearContext() {
                    try {
                        const response = await fetch('/api/deepseek/clear-context', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        if (response.ok) {
                            this.showToast('上下文已清空', 'success');
                            this.conversation = []; // 清空对话历史
                        } else {
                            this.showToast('清空上下文失败', 'error');
                        }
                    } catch (e) {
                        this.showToast('清空上下文失败', 'error');
                    }
                }
            }
        }).mount('#app')
    </script>
</body>
</html> 