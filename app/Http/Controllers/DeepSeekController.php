<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class DeepSeekController extends Controller
{
    public function index()
    {
        return view('deepseek');
    }

    public function process(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => '无效的输入，请输入有效的文本'
            ], 422);
        }

        try {
            // 获取 API 密钥
            $apiKey = config('services.dashscope.api_key');

            // 获取当前用户的会话上下文
            $context = Session::get('deepseek_context', []);

            // 构建 API 请求数据
            $requestData = [
                "model" => "deepseek-r1",
                "messages" => array_merge(
                    $context,
                    [
                        [
                            "role" => "user",
                            "content" => $request->text
                        ]
                    ]
                ),
            ];

            // 调用 DeepSeek R1 模型 API
            $response = Http::withHeaders([
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json'
            ])->post('https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions', $requestData);

            Log::info('DeepSeek API 请求参数:', ['data' => $requestData]);
            Log::info('DeepSeek API 响应:', ['response' => $response->body()]);

            if ($response->failed()) {
                throw new \Exception('无法获取模型输出');
            }

            $data = $response->json();

            // 检查 API 响应是否包含预期的结果字段
            if (!isset($data['choices']) || empty($data['choices'])) {
                throw new \Exception('API 响应中未找到结果字段');
            }

            // 提取第一个选择的内容作为结果
            $result = $data['choices'][0]['message']['content'];

            // 将新消息添加到上下文中
            $newContext = array_merge(
                $context,
                [
                    [
                        "role" => "user",
                        "content" => $request->text
                    ],
                    [
                        "role" => "assistant",
                        "content" => $result
                    ]
                ]
            );

            // 更新会话中的上下文
            Session::put('deepseek_context', $newContext);

            return response()->json(['result' => $result]);
        } catch (\Exception $e) {
            Log::error('DeepSeek API 请求失败:', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => '处理失败: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearContext()
    {
        // 清除会话中的上下文
        Session::forget('deepseek_context');
        return response()->json(['status' => 'success']);
    }
} 