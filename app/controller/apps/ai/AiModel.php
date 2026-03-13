<?php

namespace app\controller\apps\ai;

use app\BaseController;
use app\model\AiModelModel;

class AiModel extends BaseController
{
    public function ModelList(): \think\response\Json
    {
        $user = $this->getUser(true);
        $list = AiModelModel::where([
            "status" => 1,
            "user_id" => null
        ])->whereOr('user_id', $user->user_id)
            ->field("user_id,id,name,tips")
            ->where("status", 1)
            ->order("user_id")
            ->select();
        return $this->success("ok", $list);
    }

    public function ModelListManager(): \think\response\Json
    {
        $user = $this->getAdmin();
        $list = AiModelModel::where('user_id', $user->user_id)->removeWhereField("user_id")
            ->where("user_id", null)
            ->select();
        return $this->success("ok", $list);
    }

    function delMode(): \think\response\Json
    {
        $user = $this->getAdmin();
        $id = $this->request->post("id", false);
        if ($id) {
            AiModelModel::destroy($id);
            return $this->success("ok");
        } else {
            return $this->error("参数错误");
        }
    }

    function ModelSave(): \think\response\Json
    {
        $data = $this->request->post();
        $this->getAdmin();
        if (isset($data['id']) && $data['id'] > 0) {
            AiModelModel::update($data);
        } else {
            AiModelModel::create($data);
        }
        return $this->success("ok");
    }
}