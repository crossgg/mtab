<?php
/*
 * @description:
 * @Date: 2022-09-26 20:27:01
 * @LastEditTime: 2022-09-26 20:27:53
 */

namespace app\model;

use think\Model;

class TabbarModel extends Model
{
    protected $name = "tabbar";
    protected $pk = "user_id";
    protected $jsonAssoc = true;
    protected $json = ['tabs'];

    static function getTabbar($user)
    {
        if ($user) {
            $data = self::where("user_id", $user['user_id'])->find();
            if ($data) {
                if (!is_array($data['tabs'])) {
                    return [];
                }
                $arr = [];
                foreach ($data['tabs'] as $item) {
                    if (is_array($item) && isset($item['id'])) {
                        $arr[] = $item;
                    }
                }
                return $arr;
            }
        }
        $config = SettingModel::systemSetting('defaultTab', '/static/defaultTab.json', true);
        if ($config) {
            $fp = joinPath(public_path(), $config);
            if (!file_exists($fp)) {
                $fp = public_path() . 'static/defaultTab.json';
            }
            if (file_exists($fp)) {
                $file = file_get_contents($fp);
                $json = json_decode($file, true);
                return $json['tabbar'] ?? [];
            }
        }
        return [];
    }
}
