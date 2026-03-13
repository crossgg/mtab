<?php

namespace app\model;

use think\Model;

class FileModel extends Model
{
    protected $name = "file";
    protected $pk = "id";

    function getPathAttr($value)
    {
        return joinPath("/", $value);
    }

    public static function addFile($file, $user_id = null)
    {
        $originPath = joinPath(public_path(), $file);
        $hash = hash_file('md5', $originPath);
        $find = self::where('hash', $hash)->find();
        if ($find) {
            //如果文件不存在，就删除记录
            if (!file_exists(joinPath(public_path(), $find['path']))) {
                $find->delete();
            } else {
                //存在就返回数据库的路径
                if ($find['path'] !== $file) {
                    unlink($originPath);
                }
                return joinPath('/', $find['path']);
            }
        }
        if (file_exists($originPath)) {
            clearstatcache(true, $originPath);
            $info = [];
            $info["path"] = $file;
            $info["user_id"] = $user_id;
            $info['create_time'] = date("Y-m-d H:i:s");
            $info['size'] = filesize($originPath);
            $info['hash'] = $hash;
            $info["mime_type"] = mime_content_type($originPath);
            self::insert($info);
            return joinPath('/', $info['path']);
        }
        return false;
    }

    static function delFile($path): bool
    {
        $path = joinPath(public_path(), $path);
        if (file_exists($path)) {
            $hash = hash_file("md5", $path);
            unlink($path);
            $find = self::where("hash", $hash)->find();
            if ($find) {
                $find->delete();
            }
        }
        return true;
    }

    static function moveFile($oldPath, $newPath): bool
    {
        $path = joinPath(public_path(), $oldPath);
        $Path2 = joinPath(public_path(), $newPath);
        if (file_exists($path)) {
            $find = self::where("hash", hash_file('md5', $path))->find();
            if ($find) {
                rename($path, $Path2);
                $find->path = joinPath('/', $newPath);
                $find->save();
            }
        }
        return true;
    }

    function user(): \think\model\relation\HasOne
    {
        return $this->hasOne(UserModel::class, "id", "user_id")->field("id,nickname,mail");
    }
}
