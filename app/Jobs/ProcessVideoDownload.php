<?php

namespace App\Jobs;

use App\Models\VideoDownload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProcessVideoDownload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $videoDownload;

    public function __construct(VideoDownload $videoDownload)
    {
        $this->videoDownload = $videoDownload;
    }

    public function handle()
    {
        try {
            // 添加日志
            \Log::info('开始下载视频: ' . $this->videoDownload->video_url);

            // 使用 file_get_contents 替代 Http 客户端
            $videoContent = file_get_contents($this->videoDownload->video_url);
            
            if ($videoContent === false) {
                throw new \Exception('视频内容下载失败');
            }

            // 检查内容大小
            $contentLength = strlen($videoContent);
            \Log::info('下载的视频大小: ' . $contentLength . ' bytes');

            if ($contentLength == 0) {
                throw new \Exception('下载的视频内容为空');
            }

            $fileName = 'downloads/' . $this->videoDownload->id . '.mp4';
            
            // 保存文件
            $saved = Storage::disk('public')->put($fileName, $videoContent);
            
            if (!$saved) {
                throw new \Exception('视频文件保存失败');
            }

            // 验证保存的文件
            $savedSize = Storage::disk('public')->size($fileName);
            \Log::info('保存的文件大小: ' . $savedSize . ' bytes');

            if ($savedSize == 0) {
                throw new \Exception('保存的文件大小为0');
            }

            $this->videoDownload->update([
                'status' => 'completed',
                'download_count' => $this->videoDownload->download_count + 1
            ]);

            \Log::info('视频下载完成: ' . $fileName);
        } catch (\Exception $e) {
            \Log::error('视频下载失败: ' . $e->getMessage());
            $this->videoDownload->update(['status' => 'failed']);
            throw $e;
        }
    }
} 