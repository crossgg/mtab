<?php

namespace app\model;

use think\Model;

class AiModelModel extends Model
{
    protected $pk = 'id';
    protected $name = "ai_model";

    public static function getModel($id = null, $user_id = null)
    {
        try {
            $mode = self::where("status", 1)->find($id);
            if ($mode) {
                //如果模型user_id为空或者等于当前用户id，则返回模型；因为null标识为公共模型；
                if (!$mode['user_id'] || $mode['user_id'] == $user_id) {
                    return $mode;
                }
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }
}