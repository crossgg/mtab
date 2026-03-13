<?php
declare (strict_types=1);

namespace app\command;

use DirectoryIterator;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use ZipArchive;


class backup extends Command
{
    //忽略名单
    public $ignore = [];
    public $sqlName = "";
    public $zipName = "";
    public $date = "";

    protected function configure()
    {
        // 指令配置
        $this->setName('backup')->setDescription('数据库导出')
            ->addArgument('name', Argument::OPTIONAL, '导出数据库');
    }

    protected function getIgnore()
    {
        set_time_limit(0);
        //取消内存使用限制，否则无法写入备份的数据，内存不够
        ini_set('memory_limit', '-1');
        //备份忽略名单
        $arr = ["/web", '.git', '.idea', '/runtime', '/backup'];
        foreach ($arr as $item) {
            $this->ignore[] = joinPath(root_path(), $item);
        }
    }

    protected function execute(Input $input, Output $output)
    {
        $this->getIgnore();
        $this->date = date('YmdHis');
        $this->sqlName = "mTabBackupMysql-{$this->date}.sql";
        $this->zipName = "mTabBackupApp-{$this->date}.zip";
        if (!is_dir(root_path() . 'backup')) {
            mkdir(root_path() . 'backup', 0777, true);
        }
        $name = $input->getArgument('name');
        if ($name === 'database') {
            //只导出数据库数据.
            $this->mysqlDump();
            exit();
        }
        if ($name === 'app') {
            //只导出程序文件
            $this->fileZip();
            exit();
        }

        //默认全部打包
        $this->mysqlDump();
        $this->fileZip();
    }

    public function scan($path): array
    {
        $files = [];
        if (is_dir($path)) {
            $dir = new DirectoryIterator($path);
            foreach ($dir as $item) {
                if ($item->isDot()) continue;
                $currentPath = $item->getPathname();
                // 检查是否忽略路径
                $shouldIgnore = false;
                foreach ($this->ignore as $ignorePath) {
                    if (strpos($currentPath, $ignorePath) === 0) {
                        $shouldIgnore = true;
                        break;
                    }
                }
                if ($shouldIgnore) {
                    // 如果是目录，直接跳过整个目录
                    continue;
                }
                if ($item->isDir()) {
                    // 递归遍历子目录
                    $files = array_merge($files, $this->scan($currentPath));
                } else {
                    // 添加文件
                    $files[] = $currentPath;
                }
            }
        }
        return $files;
    }

    function fileZip()
    {
        print_r("开始文件备份\n");
        $zip = new ZipArchive();
        $zip->open(root_path() . "backup/{$this->zipName}", ZipArchive::CREATE | ZipArchive::OVERWRITE);
        // 添加文件夹及其内容到压缩包
        print_r("添加文件夹及其内容到压缩包\n");
        $this->addFolderToZip($zip, root_path(), root_path());
        $zip->close();
        print_r("文件备份完毕\n");
    }

    //匹配删除路径，返回相对路径
    function getRelativePath($basePath)
    {
        $basePath = realpath($basePath);
        $absolutePath = root_path();
        return preg_replace('/^' . preg_quote($absolutePath, '/') . '/', '', $basePath);
    }

    //添加文件到压缩包;
    function addFolderToZip($zip, $path, $baseDir)
    {
        $filteredIterator = $this->scan($path);
        foreach ($filteredIterator as $file) {
            $filePath = $file;
            $relativePath = $this->getRelativePath($file);
            echo "\033[1;42m" . $filePath . "\033[0m";
            echo "\r\033[K";
            $zip->addFile($filePath, $relativePath);
        }
    }

    function mysqlDump()
    {
        $host = env("DATABASE.HOSTNAME");
        $user = env("DATABASE.USERNAME");
        $password = env("DATABASE.PASSWORD");
        $database = env("DATABASE.DATABASE");
        $port = env("DATABASE.HOSTPORT");
        $export = new \MysqlDump($host, $user, $password, $database, $port);
        print_r("开始导出数据库...\n");
        $export->export(root_path() . "/backup/{$this->sqlName}");
        print_r("数据库导出完成...\n");
    }
}
