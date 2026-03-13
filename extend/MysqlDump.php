<?php

class MysqlDump
{
    public $conn = null;
    public $host = null;
    public $user = null;
    public $pass = null;
    public $db = null;
    public $port = null;
    public $charset = 'utf8mb4';

    function __construct($host, $user, $pass, $db, $port)
    {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->db = $db;
        $this->port = $port;
        $this->conn = new mysqli($host, $user, $pass, $db, $port);
        if ($this->conn->connect_error) {
            die("连接失败: " . $this->conn->connect_error);
        }
    }

    function export($saveFilePath = './dump.sql')
    {
        $fp = fopen($saveFilePath, 'w');
        $result = $this->conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $table = $row[0];
            // 获取表结构
            $ddl = "# 创建 {$table} 数据表\n";
            $ddl .= "CREATE TABLE IF NOT EXISTS {$table} (\n";
            $columns = [];
            $primaryKeys = [];
            $uniqueKeys = [];
            $results = $this->conn->query("SHOW FULL COLUMNS FROM `{$table}`");
            while ($column = $results->fetch_assoc()) {
                $columnName = $column['Field'];
                $dataType = $column['Type'];
                $nullable = $column['Null'] === 'YES' ? '' : 'NOT NULL';
                $default = $column['Default'] !== null ? "DEFAULT '{$column['Default']}'" : '';
                $extra = $column['Extra'];
                $comment = $column['Comment'];
                $columns[] = "    {$columnName} {$dataType} {$nullable} {$default} COMMENT '{$comment}' {$extra}";
                if ($column['Key'] === 'PRI') {
                    $primaryKeys[] = $columnName;
                } elseif ($column['Key'] === 'UNI') {
                    $uniqueKeys[$columnName] = $columnName;
                }
            }
            $ddl .= implode(",\n", $columns) . ",\n";
            if (!empty($primaryKeys)) {
                $primaryKey = "PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
                $ddl .= "    {$primaryKey},\n";
            }
            foreach ($uniqueKeys as $key => $value) {
                $ddl .= "    UNIQUE KEY `{$key}_uindex` (`{$value}`),\n";
            }
            // 去掉最后一个逗号
            $ddl = substr($ddl, 0, -2) . "\n";
            $ddl .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;\n\n";
            fwrite($fp, $ddl);
        }

        // 导出数据部分
        $result = $this->conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $table = $row[0];
            fwrite($fp, "# 导出数据表 {$table}\n");
            $resultData = $this->conn->query("SELECT * FROM `{$table}`");
            while ($row = $resultData->fetch_assoc()) {
                $insertValues = array_map(function ($value) {
                    if ($value === null) return 'NULL';
                    return "'" . $this->conn->real_escape_string($value) . "'";
                }, $row);
                $insertQuery = "INSERT INTO {$table} VALUES (" . implode(', ', $insertValues) . ");\n";
                fwrite($fp, $insertQuery);
            }
            fwrite($fp, "\n");
        }
        fclose($fp);
        $this->conn->close();
    }
}