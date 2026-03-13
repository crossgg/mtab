<?php

namespace app\controller\apps\ai;

use app\BaseController;
use app\model\AiModel;
use app\model\AiModelModel;
use app\model\DialogueModel;
use Exception;
use think\response\Json;

class Ai extends BaseController
{
    // 创建对话
    function createDialogues(): Json
    {
        $user = $this->getUser(true);
        $model = $this->request->post("model", '');
        $add = DialogueModel::create(["user_id" => $user->user_id, "mode_id" => $model]);
        return $this->success("ok", ['id' => $add['id'], "create_time" => $add['create_time'], 'title' => ""]);
    }

    // 删除对话
    function deleteDialogues(): Json
    {
        $user = $this->getUser(true);
        $dialogue_id = $this->request->post("dialogue_id", '');
        $find = DialogueModel::where('user_id', $user['user_id'])->where('id', $dialogue_id)->find();
        if (!$find) {
            return $this->error("对话不存在");
        }
        DialogueModel::destroy($dialogue_id);
        AiModel::where('dialogue_id', $dialogue_id)->delete();
        return $this->success("ok");
    }

    // 获取对话消息
    function messageList(): Json
    {
        $user = $this->getUser(true);
        $dialogue_id = $this->request->post("dialogue_id", '');
        $messages = AiModel::where('user_id', $user['user_id'])->where('dialogue_id', $dialogue_id)->order("create_time", "asc")->select();
        return $this->success("ok", $messages);
    }

    // 对话列表
    function dialogues(): Json
    {
        $user = $this->getUser(true);
        $messages = DialogueModel::where('user_id', $user['user_id']);
        $messages = $messages->order("create_time", "desc")->limit($this->request->post('offset', 0), 50)->select()->toArray();
        return $this->success("ok", ['data' => $messages]);
    }

    // 修改对话标题
    function reDialogueTitle(): Json
    {
        $user = $this->getUser(true);
        $dialogue_id = $this->request->post("dialogue_id", '');
        $title = $this->request->post("title", '');
        DialogueModel::where('user_id', $user['user_id'])->where('id', $dialogue_id)->update(['title' => $title]);
        return $this->success("ok");
    }

    //删除所有对话
    function clearDialog(): Json
    {
        $user = $this->getUser(true);
        DialogueModel::where('user_id', $user['user_id'])->delete();
        AiModel::where('user_id', $user['user_id'])->delete();
        return $this->success("ok");
    }

    // 对话
    function Index()
    {
        set_time_limit(0);
        ignore_user_abort(true);
        ini_set('zlib.output_compression', 'Off');
        header('X-Accel-Buffering: no');
        $input = $this->request->post("input", '');
        $dialogue_id = $this->request->post("dialogue_id", '');
        $model_id = $this->request->post("model", '');
        $user = $this->getUser(true);
        ob_end_clean();
        header('Content-Type: text/event-stream');
        flush();
        $mode = AiModelModel::getModel($model_id, $user['user_id']);
        if (!$mode) {
            return $this->error("模型不存在");
        }
        // 设置请求的URL
        $url = $mode->api_host;
        $apiKey = $mode->sk;
        $model = $mode->model;
        $systemContent = $mode->system_content;
        // 设置请求头
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        $dialogue = DialogueModel::where('user_id', $user->user_id)->where('id', $dialogue_id)->find();
        if (!$dialogue) {
            return $this->error('对话不存在');
        }
        $historyMessages = AiModel::history($user->user_id, $dialogue_id);
        AiModel::addMessage($user->user_id, 'user', $input, $dialogue_id);
        if (!$dialogue->title || $dialogue->model_id !== $model_id) {
            //取出前30个当前消息的内容，并取前30个字符，作为对话标题
            if (!$dialogue->title) {
                $dialogue->title = mb_substr($input, 0, 30);
            }
            if ($dialogue->model_id !== $model_id) {
                $dialogue->mode_id = $model_id;
            }
            $dialogue->save();
        }
        $historyMessages[] = [
            "role" => "user",
            "content" => $input
        ];
        if ($systemContent) {
            // 添加系统消息
            array_unshift($historyMessages, [
                "role" => "system",
                "content" => $systemContent
            ]);
        }
        $data = [
            "model" => $model,
            "messages" => $historyMessages,
            "stream" => true,
        ];
        $str = "";
        $reasoning_content = "";
        // 初始化cURL会话
        $ch = curl_init();
        // 设置cURL选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // 直接输出响应
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        // 设置一个自定义的写回调函数
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use ($user, &$str, &$reasoning_content) {
            //按行读取$data;
            $lines = explode("\n", $data);
            foreach ($lines as $line) {
                if (mb_trim($line) && strpos($line, 'data:') === 0) {
                    $json_data = substr($line, 5); // 去掉 "data:" 前缀
                    $json_data = mb_trim($json_data); // 去掉前后空白字符
                    echo $json_data . "\n";
                    $js = json_decode($json_data);
                    if ($js) {
                        //内容思考内容
                        if (isset($js->choices[0]->delta->content)) {
                            $content = $js->choices[0]->delta->content;
                            $str .= $content;
                        }
                        //深度思考内容
                        if (isset($js->choices[0]->delta->reasoning_content)) {
                            $reasoning = $js->choices[0]->delta->reasoning_content;
                            $reasoning_content .= $reasoning;
                        }
                    }
                } else {
                    try {
                        $js = json_decode($line);
                        if ($js && isset($js->error)) {
                            echo json_encode(['code' => 0, "msg" => $js->error->message]);
                        }
                    } catch (Exception $e) {
                        //什么也不干
                    }
                }
                flush();
            }
            // 刷新输出缓冲区，确保即时输出
            return strlen($data);
        });
        // 执行cURL会话
        curl_exec($ch);
        // 检查是否有错误发生
        if (curl_errno($ch)) {
            echo json_encode(['code' => 0, "msg" => "服务开小差了~~~ 请稍后再尝试！"]);
        } else {
            AiModel::addMessage($user->user_id, 'assistant', $str, $dialogue_id, $reasoning_content);
        }
        // 关闭cURL资源
        curl_close($ch);
        flush();
        exit();
    }
}