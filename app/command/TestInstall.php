<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class TestInstall extends Command
{
    protected function configure()
    {
        $this->setName('test:install')->setDescription('Test SQLite install script');
    }

    protected function execute(Input $input, Output $output)
    {
        $sql = file_get_contents(root_path() . 'install.sql');
        $statements = explode(';', $sql);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (!empty($stmt)) {
                try {
                    Db::execute($stmt);
                } catch (\Exception $e) {
                    $output->writeln("Error executing: " . substr($stmt, 0, 50) . "... -> " . $e->getMessage());
                }
            }
        }
        $output->writeln("Install complete!");

        $sql2 = file_get_contents(root_path() . 'defaultData.sql');
        $statements2 = explode(';', $sql2);
        foreach ($statements2 as $stmt) {
            $stmt = trim($stmt);
            if (!empty($stmt)) {
                try {
                    Db::execute($stmt);
                } catch (\Exception $e) {
                    $output->writeln("Error executing default data: " . substr($stmt, 0, 50) . "... -> " . $e->getMessage());
                }
            }
        }
        $output->writeln("Default data install complete!");
    }
}
