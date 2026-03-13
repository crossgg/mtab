<?php

namespace app\controller;

use app\BaseController;
use app\model\ConfigModel;
use app\model\HistoryModel;
use app\model\LinkModel;
use app\model\TabbarModel;
use app\model\UserSearchEngineModel;
use think\facade\Cache;

class Link extends BaseController
{
    public function update(): \think\response\Json
    {
        is_demo_mode(true);
        $user = $this->getUser(true);
        $error = "";
        try {
            if ($user) {
                $link = $this->request->post("link", []);
                if (is_array($link)) {
                    $is = LinkModel::where("user_id", $user['user_id'])->find();
                    if ($is) {
                        HistoryModel::create(['user_id' => $user['user_id'], 'link' => $is['link'], 'create_time' => date("Y-m-d H:i:s")]); //历史记录备份,用于用户误操作恢复用途
                        $ids = HistoryModel::where("user_id", $user['user_id'])->order("id", 'desc')->limit(10)->select()->toArray();
                        $ids = array_column($ids, "id");
                        HistoryModel::where("user_id", $user['user_id'])->whereNotIn("id", $ids)->delete();
                        $is->link = $link;
                        $is->save();
                    } else {
                        LinkModel::create(["user_id" => $user['user_id'], "link" => $link]);
                    }
                    Cache::delete("Link.{$user['user_id']}");
                    return $this->success('ok');
                }
            }
        } catch (\Throwable $th) {
            $error = $th->getMessage();
        }
        return $this->error('保存失败' . $error);
    }

    public function get(): \think\response\Json
    {
        $user = $this->getUser();
        return $this->success("ok", LinkModel::getLink($user));
    }

    function refreshWebAppCache(): \think\response\Json
    {
        $this->getAdmin();
        Cache::tag('linkCache')->clear();
        return $this->success('刷新完毕');
    }

    public function history(): \think\response\Json
    {
        $user = $this->getUser(true);
        $history = HistoryModel::where("user_id", $user['user_id'])->whereNotNull("create_time")->field('id,user_id,create_time')->limit(100)->order("id", "desc")->select();
        return $this->success('ok', $history);
    }

    public function delBack(): \think\response\Json
    {
        $user = $this->getUser(true);
        $id = $this->request->post('id');
        if ($id) {
            $res = HistoryModel::where('id', $id)->where('user_id', $user['user_id'])->delete();
            if ($res) {
                return $this->success('ok');
            }
        }
        return $this->error('备份节点不存在');
    }

    public function rollBack(): \think\response\Json
    {
        $user = $this->getUser(true);
        $id = $this->request->post("id");
        if ($id) {
            $res = HistoryModel::where('id', $id)->where("user_id", $user['user_id'])->find();
            if ($res) {
                $link = $res['link'];
                Cache::delete("Link.{$user['user_id']}");
                LinkModel::update(["user_id" => $user['user_id'], "link" => $link]);
                return $this->success('ok');
            }
        }
        return $this->error("备份节点不存在");
    }

    public function reset(): \think\response\Json
    {
        $user = $this->getUser();
        if ($user) {
            $data = LinkModel::find($user['user_id']);
            if ($data) {
                Cache::delete("Link.{$user['user_id']}");
                $data->delete();
            }
            $data = TabbarModel::find($user['user_id']);
            if ($data) {
                $data->delete();
            }
            $data = ConfigModel::find($user['user_id']);
            if ($data) {
                $data->delete();
            }
            $data = UserSearchEngineModel::find($user['user_id']);
            if ($data) {
                $data->delete();
            }
        }
        return $this->success('ok');
    }
}
