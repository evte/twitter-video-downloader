<?php

namespace App\Http\Controllers;

use App\Services\TwitterVideoService;
use App\Models\VideoDownload;
use App\Jobs\ProcessVideoDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\IpDownload;
use Carbon\Carbon;

class TwitterVideoController extends Controller
{
    protected $twitterService;

    public function __construct(TwitterVideoService $twitterService)
    {
        $this->twitterService = $twitterService;
    }

    public function extract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => [
                'required',
                'url',
                'regex:/(?:twitter\.com|x\.com)/' // 同时支持 twitter.com 和 x.com
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => '无效的链接，请输入有效的推特/X链接'
            ], 422);
        }

        try {
            $videoInfo = $this->twitterService->extractVideoInfo($request->url);
            return response()->json($videoInfo);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function download(Request $request)
    {
        try {
            // 检查 IP 下载次数
            $ipAddress = $request->ip();
            $today = Carbon::today();
            
            $ipDownload = IpDownload::firstOrCreate(
                ['ip_address' => $ipAddress, 'date' => $today],
                ['download_count' => 0]
            );

            if ($ipDownload->download_count >= 3) {
                return response()->json([
                    'error' => '您今日的下载次数已达上限（3次/天）'
                ], 429);
            }

            // 验证请求参数
            $validator = Validator::make($request->all(), [
                'video_url' => 'required|url',
                'resolution' => 'required|string',
                'tweet_url' => 'required|url'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => '无效的请求参数'
                ], 422);
            }

            $download = VideoDownload::create([
                'tweet_url' => $request->tweet_url,
                'video_url' => $request->video_url,
                'resolution' => $request->resolution,
                'status' => 'pending'
            ]);

            // 增加下载计数
            $ipDownload->increment('download_count');

            ProcessVideoDownload::dispatch($download);

            return response()->json([
                'message' => '下载已开始处理',
                'download_id' => $download->id,
                'remaining_downloads' => 3 - $ipDownload->download_count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => '下载处理失败: ' . $e->getMessage()
            ], 500);
        }
    }

    public function status($id)
    {
        try {
            $download = VideoDownload::findOrFail($id);
            return response()->json([
                'status' => $download->status,
                'file_url' => $download->status === 'completed' 
                    ? Storage::url('downloads/' . $id . '.mp4') 
                    : null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => '无法获取下载状态'
            ], 404);
        }
    }
} 