<?php

namespace app\model;

use think\Model;

/**
 *
 */
class AiModel extends Model
{
    protected $name = "ai";
    protected $pk = "id";

    /**
     * @param int $user_id 用户id
     * @param string $role 角色
     * @param string $message 消息
     * @param int $dialogue_id 对话id
     * @param string $reasoning_content 深度思考内哦让给你
     * @return AiModel|Model
     * @noinspection PhpMissingParamTypeInspection
     */
    static function addMessage($user_id, $role, $message, $dialogue_id, $reasoning_content = "")
    {
        if (!$role) {
            $role = 'user';
        }
        return self::create(['user_id' => $user_id, 'message' => $message, 'role' => $role, 'dialogue_id' => $dialogue_id, 'reasoning_content' => $reasoning_content]);
    }

    static function history($user_id, $dialogue_id): array
    {
        $list = self::where('user_id', $user_id)->where('dialogue_id', $dialogue_id)->field("role,message as content")->select()->toArray();
        $content = [];
        $countList = count($list);
        for ($i = 0; $i < $countList; $i++) {
            $value = $list[$i];
            if ($value['role'] === "user") {
                $content[] = $value;
                // 检查是否需要补充 assistant
                if ($i + 1 < $countList && $list[$i + 1]['role'] !== 'assistant') {
                    $content[] = ["role" => "assistant", "content" => ""];
                }
            } else {
                // 如果第一条不是 user，则跳过
                if ($i === 0) continue;
                $content[] = $value;
            }
        }
        // 确保最后一条是 assistant
        if (!empty($content) && $content[count($content) - 1]['role'] === 'user') {
            $content[] = ["role" => "assistant", "content" => ""];
        }
        return $content;
    }

}