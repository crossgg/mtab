<?php
ini_set('max_execution_time', 0);
ini_set('memory_limit', '500M');

class Upgrade2
{
    protected  $archiveFile = ""; //升级文件地址
    protected  $extractPath = ""; //解压目录地址
    protected  $root_path = ""; //程序根目录
    public  $update_download_url = ""; //升级zip文件下载地址
    public  $update_sql_url = ""; //升级sql脚本文件地址
    public  $update_script = ""; //升级后执行的脚本地址
    protected  $isLog = false;
    protected  $noMove = [];
    //构造方法初始化一些数据
    function __construct($update_download_url = null, $update_sql_url = null, $update_script = null)
    {
        $this->archiveFile = runtime_path() . 'mtab.zip';
        $this->extractPath = runtime_path();
        $this->root_path = root_path();
        if ($update_download_url !== null) {
            $this->update_download_url = $update_download_url;
        }
        if ($update_sql_url !== null) {
            $this->update_sql_url = $update_sql_url;
        }
        if ($update_script !== null) {
            $this->update_script = $update_script;
        }
    }

    //运行入口
    function run($cli = false): bool
    {
        if ($cli) {
            $this->isLog = true;
        }
        return $this->startUpgrade();
    }

    public function log($msg)
    {
        if ($this->isLog) {
            print_r($msg . "\n");
        }
    }

    //新的进程启动升级
    private function startUpgrade(): bool
    {
        //如果有程序代码的更新资源则更新程序代码
        if (strlen($this->update_download_url) > 1) {
            //如果有遗留的解压资源则删除
            $this->log("正在检查是否有旧版本的安装包，并删除。");
            $this->deleteDirectory("{$this->extractPath}mtab");
            //如果存在旧的升级包则删除
            $this->delZip();
            //下载远程更新包
            $this->log("正在下载升级包...");
            if (!$this->fileDownload()) {
                $this->log('资源下载失败');
                abort(0, '资源下载失败');
            }
            //解压升级包
            $this->log("正在解压升级包...");
            if (!$this->unzip($this->archiveFile, $this->extractPath)) {
                $this->delZip();
                abort(0, '升级资源包解压失败');
            }
            $this->log("正在更新程序...");
            $this->deleteDirectory(public_path() . 'dist/'); //删除旧的网站文件
            $exclude = root_path() . 'exclude.txt';
            if (file_exists($exclude)) {
                try {
                    $noMove = array_map('trim', file($exclude, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                    //逐行读取，并且去除前后多余空格
                    foreach ($noMove as $item) {
                        $this->noMove[] = joinPath(root_path(), $item);
                    }
                } catch (\Throwable $th) {
                    $this->log($th->getMessage());
                }
            }
            //拷贝覆盖
            $this->log("正在覆盖源文件");
            $this->copy();
            //删除下载的更新包
            $this->log("正在删除升级包...");
            $this->delZip();
            //更新完后的一些操作
        }
        //如果有数据库的更新资源则更新程序代码
        if (strlen($this->update_sql_url) > 1) {
            $this->log("正在更新数据库...");
            $this->updateSql();
        }
        if (file_exists("{$this->root_path}install.sql")) {
            $this->log("正在更新数据库...");
            $this->updateSql("{$this->root_path}install.sql");
        }
        if (!file_exists(root_path() . "exclude.txt")) {
            file_put_contents(root_path() . "exclude.txt", '#从程序根目录开始填写您的更新忽略的文件地址，每行一个，例如/exclude.txt');
        }
        return true;
    }

    private function fileDownload(): bool
    {
        $length = 0;
        try {
            $f = fopen($this->update_download_url, 'r');
            $w = fopen($this->archiveFile, 'wb+');
            $fileSize = $this->getFileSize($this->update_download_url);
            do {
                $a = fread($f, 1024 * 64);
                $length += strlen($a);
                fwrite($w, $a);
                // 计算下载进度
                $progress = ($fileSize > 0) ? round($length / $fileSize * 100, 2) : 0;
                // 打印进度条，在一行内更新
                if ($this->isLog) {
                    if ($progress <= 100) {
                        $this->printProgress((int)$progress);
                    }
                }
            } while ($a);
            fclose($w);
            fclose($f);
            $this->log("\n下载完成");
        } catch (ErrorException $e) {
            return false;
        }
        return true;
    }

    private function printProgress(float $progress)
    {
        try {
            $barLength = 50; // 进度条的总长度
            $completed = round($progress / 100 * $barLength); // 完成的部分
            $bar = str_repeat('=', $completed) . str_repeat(' ', max($barLength - $completed, 0)); // 拼接进度条
            echo "\r[" . $bar . "] " . $progress . "%";
        } catch (Exception $e) {
        }
    }

    private function getFileSize(string $url): int
    {
        // 使用 HEAD 请求获取文件大小
        $headers = get_headers($url, 1);
        return isset($headers['Content-Length']) ? (int)$headers['Content-Length'] : 0;
    }

    //删除升级包
    function delZip()
    {
        if (file_exists($this->archiveFile)) {
            unlink($this->archiveFile);
        }
    }

    //解压
    private function unzip($archiveFile, $extractPath): bool
    {
        //        如果没有ZipArchive类则尝试使用shell_exec，调用自带的解压程序解压
        if (class_exists("ZipArchive")) {
            $zip = new ZipArchive();
            if ($zip->open($archiveFile) === TRUE) {
                $zip->extractTo($extractPath, null);
                $zip->close();
            } else {
                return false;
            }
            return true;
        }
        $cmd = root_path('extend') . "unzip -f {$archiveFile} -d {$extractPath}";
        if (function_exists("shell_exec")) {
            $status = shell_exec($cmd);
            if ($status === '解压成功!') {
                return true;
            }
        } else {
            $this->log("shell_exec函数被禁用，无法执行解压命令，即将尝试exec");
        }
        if (function_exists('exec')) {
            $status = exec($cmd);
            if ($status === '解压成功!') {
                return true;
            }
        } else {
            $this->log('exec函数被禁用，无法执行解压命令，请手动解压覆盖更新');
        }
        return false;
    }

    //升级的数据库
    function updateSql($path = null)
    {
        if ($path) {
            $f = fopen($path, 'r');
        } else {
            $f = fopen($this->update_sql_url, 'r');
        }
        $sql = "";
        do {
            $sqlTmp = fread($f, 1024);
            $sql = $sql . $sqlTmp;
        } while ($sqlTmp);
        fclose($f);
        // 解析SQL文件内容并执行
        $sql_statements = explode(';', trim($sql));
        foreach ($sql_statements as $sql_statement) {
            if (!empty($sql_statement)) {
                try {
                    \think\facade\Db::execute($sql_statement);
                } catch (Exception $e) {
                }
            }
        }
    }

    //递归删除目录
    function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir("$dir/$file")) {
                    $this->deleteDirectory("$dir/$file");
                } else {
                    unlink("$dir/$file");
                }
            }
        }
        rmdir($dir);
    }

    // 递归复制目录及其内容
    function copyDir($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $src = joinPath($source, '/' . $file);
                $dst =  joinPath($dest, '/' . $file);
                // 检查目标路径是否在 noMove 中，并且是文件
                if (is_dir($src)) {
                    $this->copyDir($src, $dst);
                } else {
                    if (in_array($dst, $this->noMove)) {
                        $this->log('跳过=>' . $dst);
                        continue; // 跳过复制
                    }
                    copy($src, $dst);
                }
            }
        }
    }

    //覆盖原来的程序
    private function copy()
    {
        //移动覆盖
        $this->copyDir("{$this->extractPath}mtab/", "{$this->root_path}");
        //删除解压目录
        $this->deleteDirectory("{$this->extractPath}mtab");
    }
}
