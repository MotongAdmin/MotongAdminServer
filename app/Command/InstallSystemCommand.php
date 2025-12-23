<?php
/**
 * This file is part of Motong-Admin.
 *
 * @link     https://github.com/MotongAdmin
 * @document https://github.com/MotongAdmin
 * @contact  1003081775@qq.com
 * @author   zyvincent 
 * @Company  Icodefuture Information Technology Co., Ltd.
 * @license  GPL
 */

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @Command
 */
class InstallSystemCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('install:system');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('一键安装系统：执行所有数据初始化命令');
    }

    public function handle()
    {
        $this->line('开始执行系统数据初始化...', 'info');
        $this->output->newLine();

        // 定义初始化命令的执行顺序
        $commands = [
            [
                'command' => 'migrate',
                'description' => '数据库初始化'
            ],
            [
                'command' => 'init:system-config',
                'description' => '系统配置数据初始化'
            ],
            [
                'command' => 'init:system-dict',
                'description' => '系统字典数据初始化'
            ],
            [
                'command' => 'init:system-menu',
                'description' => '系统菜单数据初始化'
            ],
            [
                'command' => 'init:system-permission',
                'description' => '系统权限数据初始化'
            ],
            [
                'command' => 'admin:seed',
                'description' => '初始化超级管理员账号'
            ]
        ];

        $totalCommands = count($commands);
        $successCount = 0;
        $failedCommands = [];

        foreach ($commands as $index => $commandInfo) {
            $commandName = $commandInfo['command'];
            $description = $commandInfo['description'];

            $this->line("[" . ($index + 1) . "/$totalCommands] 正在执行: $description ($commandName)", 'comment');

            try {
                // 执行命令
                $returnCode = $this->call($commandName);

                if ($returnCode === 0) {
                    $this->line("✅ $description 执行成功", 'info');
                    $successCount++;
                } else {
                    $this->line("❌ $description 执行失败 (返回码: $returnCode)", 'error');
                    $failedCommands[] = $commandName;
                }
            } catch (\Throwable $e) {
                $this->line("❌ $description 执行异常: " . $e->getMessage(), 'error');
                $failedCommands[] = $commandName;
            }

            $this->output->newLine();
        }

        // 输出执行结果汇总
        $this->line('=================== 执行结果汇总 ===================', 'comment');
        $this->line("总命令数: $totalCommands", 'info');
        $this->line("成功数量: $successCount", 'info');
        $this->line("失败数量: " . count($failedCommands), count($failedCommands) > 0 ? 'error' : 'info');

        if (!empty($failedCommands)) {
            $this->line("失败的命令:", 'error');
            foreach ($failedCommands as $failedCommand) {
                $this->line("  - $failedCommand", 'error');
            }
        }

        $this->output->newLine();

        if ($successCount === $totalCommands) {
            $this->line('🎉 系统数据初始化完成！所有命令执行成功！', 'info');
            $this->line('系统已准备就绪，可以正常使用。', 'comment');
            return 0;
        } else {
            $this->line('⚠️  系统数据初始化部分失败，请检查失败的命令并重新执行。', 'error');
            return 1;
        }
    }
}
