<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\VideoDownload;

class TwitterVideoService
{
    public function extractVideoInfo(string $tweetUrl)
    {
        // 检查缓存
        $cacheKey = 'tweet_' . md5($tweetUrl);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // 使用 Twitter API 获取视频信息
        $response = Http::withToken(config('services.twitter.bearer_token'))
            ->get("https://api.twitter.com/2/tweets", [
                'ids' => $this->extractTweetId($tweetUrl),
                'expansions' => 'attachments.media_keys',
                'media.fields' => 'variants'
            ]);

        if ($response->failed()) {
            throw new \Exception('无法获取推文信息');
        }

        $videoData = $this->parseVideoData($response->json());
        
        // 缓存结果
        Cache::put($cacheKey, $videoData, now()->addHours(24));

        return $videoData;
    }

    private function extractTweetId(string $url): string
    {
        preg_match('/(?:twitter\.com|x\.com)\/\w+\/status\/(\d+)/', $url, $matches);
        return $matches[1] ?? throw new \Exception('无效的推特链接');
    }

    private function parseVideoData(array $data): array
    {
        $variants = collect($data['includes']['media'][0]['variants'] ?? [])
            ->filter(fn ($variant) => isset($variant['bit_rate']))
            ->sortByDesc('bit_rate')
            ->values();

        // 只保留最高和最低清晰度
        $qualities = collect([
            $variants->first(),  // 最高清晰度
            $variants->last()    // 最低清晰度
        ])->filter()  // 移除空值
        ->map(fn ($variant) => [
            'url' => $variant['url'],
            'resolution' => $this->formatBitrate($variant['bit_rate']),
            'bitrate' => $variant['bit_rate']
        ])->values()->toArray();

        return [
            'qualities' => $qualities
        ];
    }

    private function formatBitrate(int $bitrate): string
    {
        // 根据比特率范围返回对应的清晰度标签
        if ($bitrate >= 8000000) {
            return '4K';
        } elseif ($bitrate >= 4000000) {
            return '1440p';
        } elseif ($bitrate >= 2000000) {
            return '1080p';
        } elseif ($bitrate >= 1000000) {
            return '720p';
        } elseif ($bitrate >= 500000) {
            return '480p';
        } else {
            return '360p';
        }
    }
} 