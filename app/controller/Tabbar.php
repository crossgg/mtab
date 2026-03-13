<?php


namespace app\controller;


use app\BaseController;
use app\model\TabbarModel;
use think\facade\Cache;

class Tabbar extends BaseController
{
    public function update(): \think\response\Json
    {
        $user = $this->getUser(true);
        if ($user) {
            $tabbar = $this->request->post("tabbar", []);
            if (is_array($tabbar)) {
                $is = TabbarModel::where("user_id", $user['user_id'])->find();
                if ($is) {
                    $is->tabs = $tabbar;
                    $is->save();
                } else {
                    TabbarModel::create(["user_id" => $user['user_id'], "tabs" => $tabbar]);
                }
                return $this->success('ok');
            }
        }
        return $this->error('保存失败');
    }

    public function get(): \think\response\Json
    {
        $user = $this->getUser();
        return $this->success('ok', TabbarModel::getTabbar($user));
    }
}
