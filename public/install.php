<?php
//设置永不超时
set_time_limit(0);
//设置内存可以使用512MB
ini_set('memory_limit', '512M');
function isAjaxRequest(): bool
{
    if (isset($_SERVER['HTTP_REFERER'])) {
        return true;
    } else {
        return false;
    }
}

if (file_exists("./installed.lock")) {
    if (isAjaxRequest()) {
        echo json_encode(['code' => 1000, 'msg' => '系统已安装，请勿重复安装！'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header("Location: /");
    exit;
}

//允许后台运行
ini_set('max_execution_time', 0);
//取消超时限制
set_time_limit(0);
//内存允许更大的
ini_set('memory_limit', '512M');

class Install
{
    protected $defError = [
        1045 => [
            'reason' => '用户名或密码错误，或用户没有访问权限。'
        ],
        2002 => [
            'reason' => '数据库服务器未运行或无法连接到指定的主机和端口。'
        ],
        1049 => [
            'reason' => '指定的数据库不存在。'
        ],
        1044 => [
            'reason' => '用户没有访问指定数据库的权限。'
        ],
        2003 => [
            'reason' => '无法连接到 MySQL 服务器，可能是防火墙阻止了连接。'
        ]
    ];

    function __construct()
    {
    }

    //内容，是否换行
    function output($content, $is_enter = false)
    {
        $content = trim($content);
        echo $content . "\n";
        flush();
    }

    function json($arr)
    {
        return json_encode($arr, JSON_UNESCAPED_UNICODE);
    }

    function index(): string
    {
        $var = [
            'title' => 'mTab新标签页安装程序',
            'customHead|raw' => '',
            'keywords' => '',
            'description' => '',
            'favicon' => '/static/mtab.png',
            'version' => time()
        ];
        //正则替换模板变量{$title},{$customHead|raw}
        $template = file_get_contents(__DIR__ . '/dist/index.html');
        return preg_replace_callback('/{\$([\w|]+)}/', function ($matches) use ($var) {
            $key = $matches[1];
            return $var[$key] ?? '';
        }, $template);
    }

    function connect($form, $tableName = null)
    {
        // 对于 SQLite，直接检查是否可以创建或写入 data/mtab.db
        $dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0777, true);
        }
        if (!is_writable($dataDir)) {
            throw new Exception("目录无写入权限，如果是Docker映射，请宿主机执行 chmod 777 data", 500);
        }
        $dbPath = $dataDir . DIRECTORY_SEPARATOR . 'mtab.db';
        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (\PDOException $e) {
            throw new Exception("SQLite 连接失败: " . $e->getMessage(), 500);
        }
    }

    function ext()
    {
        $phpVersion = phpversion();
        $php_version = false;
        if (version_compare($phpVersion, '7.4', '>')) {
            $php_version = true;
        }
        $fileinfo_ext = false;
        if (extension_loaded('fileinfo')) {
            $fileinfo_ext = true;
        }
        $zip_ext = false;
        if (extension_loaded('zip')) {
            $zip_ext = true;
        }
        $mysqli_ext = false;
        if (extension_loaded('mysqli')) {
            $mysqli_ext = true;
        }
        $curl_ext = false;
        if (extension_loaded('curl')) {
            $curl_ext = true;
        }
        return $this->json([
            'code' => 200,
            'data' => [
                'php_version' => $php_version,
                'fileinfo_ext' => $fileinfo_ext,
                'zip_ext' => $zip_ext,
                'mysqli_ext' => $mysqli_ext,
                'curl_ext' => $curl_ext
            ]
        ]);
    }

    function testDb()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $conn = $this->connect($data);
            $conn = null;
            return $this->json(['code' => 200, 'msg' => '连接成功']);
        } catch (Exception $e) {
            return $this->json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    function install()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        $form = json_decode(file_get_contents('php://input'), true);
        $database_type = $form['database_type'];
        $table_name = $form['table_name'];
        $db_host = $form['db_host'];
        $db_username = $form['db_username'];
        $db_password = $form['db_password'];
        $db_port = $form['db_port'];
        $admin_password = $form['admin_password'];
        $admin_email = $form['admin_email'];
        try {
            $conn = $this->connect($form);
        } catch (\Exception $e) {
            return $this->json(['code' => 500, 'msg' => $e->getMessage()]);
        }
        if ($database_type == 1) { //全新安装
            $dbPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'mtab.db';
            if (file_exists($dbPath)) {
                unlink($dbPath);
            }
            $conn = $this->connect($form, $table_name);
            $this->output("连接数据库成功...", true);
            //数据库的格式内容数据
            $sql_file_content = file_get_contents('../install.sql');
            $sql_file_content = preg_replace('/^\s*(?:#|--).*$/m', '', $sql_file_content);
            // 解析SQL文件内容并执行
            $sql_statements = explode(';', trim($sql_file_content));
            $this->output("执行数据库建表SQL语句", true);
            foreach ($sql_statements as $sql_statement) {
                if (!empty(trim($sql_statement))) {
                    try {
                        $conn->exec($sql_statement);
                        $this->output("执行完成: " . mb_substr($sql_statement, 0, 30), true);
                    } catch (Exception $exception) {
                        return $this->json(['code' => 500, 'msg' => '建表失败: ' . $sql_statement . ' -> ' . $exception->getMessage()]);
                    }
                }
            }
            //默认的一些基础数据
            $sql_file_content = file_get_contents('../defaultData.sql');
            $sql_file_content = preg_replace('/^\s*(?:#|--).*$/m', '', $sql_file_content);
            $this->output("导入默认的数据...", true);
            // 解析SQL文件内容并执行
            $sql_statements = explode(';', trim($sql_file_content));
            $this->output("执行SQL语句", true);
            foreach ($sql_statements as $sql_statement) {
                if (!empty(trim($sql_statement))) {
                    try {
                        $conn->exec($sql_statement);
                        $this->output("执行完成: " . mb_substr($sql_statement, 0, 30), true);
                    } catch (Exception $exception) {
                        return $this->json(['code' => 500, 'msg' => '初始数据失败: ' . $sql_statement . ' -> ' . $exception->getMessage()]);
                    }
                }
            }
            $admin_password = md5($admin_password);
            //添加默认管理员
            $AdminSql = ("
                    INSERT INTO user (mail, password, manager)
                    VALUES ('$admin_email', '$admin_password', 1);
                 ");
            try {
                $conn->exec($AdminSql);
            } catch (Exception $exception) {
                return $this->json(['code' => 500, 'msg' => '管理员添加失败: ' . $exception->getMessage()]);
            }
            $this->output("添加管理员成功...", true);
            $conn = null;
        }
        $dbPathFromEnv = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'mtab.db';
        $env = <<<EOF
                APP_DEBUG = false
                
                [APP]
                
                [DATABASE]
                DRIVER = sqlite
                TYPE = sqlite
                DATABASE = {$dbPathFromEnv}
                CHARSET = utf8
                DEBUG = false
                
                [CACHE]
                DRIVER = file
                
                EOF;
        file_put_contents('../.env', $env);
        $this->output("配置文件写入成功=><b style='color:red'>.env</b>", true);
        file_put_contents('./installed.lock', 'installed');
        $this->output("安装完成...", true);
        return "\nmTabDONE";
    }
}

$handle = new Install();
$path = "/";
if (isset($_GET['s'])) {
    $path = $_GET['s'];
}
switch ($path):
    case '/testDb':
        echo $handle->testDb();
        break;
    case '/install':
        echo $handle->install();
        break;
    case '/ext':
        echo $handle->ext();
        break;
    default:
        echo $handle->index();
        break;
endswitch;
exit;

