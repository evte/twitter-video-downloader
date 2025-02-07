<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>推特视频下载器</title>
    
    <!-- 网站图标 -->
    <link rel="icon" type="image/svg+xml" href="/icons/icon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
    <link rel="manifest" href="/icons/site.webmanifest">
    <meta name="theme-color" content="#1DA1F2">
    
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- 添加 Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <h1 class="text-4xl font-bold text-gray-800 mb-2">推特视频下载器</h1>
            <p class="text-gray-600">轻松下载推特视频，支持多种分辨率</p>
        </div>
        
        <!-- 主要内容卡片 -->
        <div class="bg-white rounded-xl shadow-lg p-6 space-y-6">
            <!-- 输入框组 -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                    输入推特链接
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        v-model="tweetUrl"
                        class="block w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        placeholder="https://x.com/..."
                        :disabled="loading"
                    >
                    <button 
                        @click="extractVideo"
                        :disabled="loading || !tweetUrl"
                        class="absolute right-2 top-2 px-4 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <template v-if="loading">
                            <i class="fas fa-circle-notch loading-spin"></i>
                            处理中
                        </template>
                        <template v-else>
                            <i class="fas fa-search"></i>
                            提取
                        </template>
                    </button>
                </div>
            </div>

            <!-- 错误提示 -->
            <div v-if="error" class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                    <p class="text-red-700">@{{ error }}</p>
                </div>
            </div>

            <!-- 视频信息 -->
            <transition name="fade">
                <div v-if="videoInfo" class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-video mr-2 text-blue-500"></i>
                        可用清晰度
                    </h2>
                    <div class="grid gap-3">
                        <div v-for="quality in videoInfo.qualities" 
                            :key="quality.resolution"
                            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                        >
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-film text-gray-600"></i>
                                <span class="text-gray-700 font-medium">@{{ quality.resolution }}</span>
                            </div>
                            <button 
                                @click="downloadVideo(quality)"
                                class="flex items-center space-x-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="downloading"
                            >
                                <template v-if="downloading">
                                    <i class="fas fa-circle-notch loading-spin mr-2"></i>
                                    处理中...
                                </template>
                                <template v-else>
                                    <i class="fas fa-download mr-2"></i>
                                    下载
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- 下载进度 -->
            <div v-if="downloading" class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-circle-notch loading-spin text-blue-500"></i>
                    <span class="text-blue-700">正在处理下载请求...</span>
                </div>
            </div>

            <!-- Toast 消息 -->
            <transition name="fade">
                <div v-if="toast.show" class="toast">
                    <div class="flex items-center space-x-2">
                        <i :class="toast.icon"></i>
                        <span>@{{ toast.message }}</span>
                    </div>
                </div>
            </transition>
        </div>

        <!-- 页脚 -->
        <div class="mt-8 text-center text-gray-600 text-sm">
            <p>支持 Twitter/X 平台视频下载</p>
        </div>
    </div>

    <script>
        const { createApp } = Vue

        createApp({
            data() {
                return {
                    tweetUrl: '',
                    videoInfo: null,
                    loading: false,
                    downloading: false,
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
                    // 清除之前的定时器
                    if (this.toast.timer) {
                        clearTimeout(this.toast.timer);
                    }
                    
                    // 设置图标
                    const icons = {
                        info: 'fas fa-info-circle',
                        success: 'fas fa-check-circle',
                        error: 'fas fa-exclamation-circle'
                    };
                    
                    // 显示新消息
                    this.toast.message = message;
                    this.toast.icon = icons[type];
                    this.toast.show = true;
                    
                    // 3秒后自动隐藏
                    this.toast.timer = setTimeout(() => {
                        this.toast.show = false;
                    }, 3000);
                },
                async extractVideo() {
                    this.loading = true;
                    this.error = null;
                    this.videoInfo = null;
                    
                    try {
                        const response = await fetch('/api/twitter/extract', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ url: this.tweetUrl })
                        });

                        const data = await response.json();
                        
                        if (!response.ok) {
                            throw new Error(data.error);
                        }

                        this.videoInfo = data;
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                },
                async downloadVideo(quality) {
                    this.downloading = true;
                    this.error = null;
                    this.showToast('正在准备下载...', 'info');
                    
                    try {
                        const response = await fetch('/api/twitter/download', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                video_url: quality.url,
                                resolution: quality.resolution,
                                tweet_url: this.tweetUrl
                            })
                        });

                        const data = await response.json();
                        
                        if (!response.ok) {
                            throw new Error(data.error);
                        }

                        this.showToast('开始处理下载请求...', 'info');
                        this.pollDownloadStatus(data.download_id);
                    } catch (e) {
                        this.error = e.message;
                        this.showToast('下载请求失败', 'error');
                        this.downloading = false;
                    }
                },
                async pollDownloadStatus(downloadId) {
                    const checkStatus = async () => {
                        try {
                            const response = await fetch(`/api/twitter/status/${downloadId}`);
                            const data = await response.json();

                            if (data.status === 'completed') {
                                const downloadLink = document.createElement('a');
                                downloadLink.href = `/storage/downloads/${downloadId}.mp4`;
                                downloadLink.download = `twitter_video_${downloadId}.mp4`;
                                document.body.appendChild(downloadLink);
                                downloadLink.click();
                                document.body.removeChild(downloadLink);
                                this.downloading = false;
                                this.showToast('下载开始', 'success');
                            } else if (data.status === 'failed') {
                                this.error = '下载失败，请重试';
                                this.showToast('下载失败', 'error');
                                this.downloading = false;
                            } else {
                                setTimeout(checkStatus, 1000);
                            }
                        } catch (e) {
                            this.error = '检查下载状态时出错';
                            this.showToast('下载状态检查失败', 'error');
                            this.downloading = false;
                        }
                    };

                    checkStatus();
                }
            }
        }).mount('#app')
    </script>
</body>
</html> 