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

use App\Swagger\Generator\SwaggerGenerator;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class SwaggerGenerateCommand extends HyperfCommand
{
    protected ContainerInterface $container;

    /**
     * 命令名称
     */
    protected $name = 'swagger:generate';

    /**
     * 命令描述
     */
    protected $description = '生成 Swagger API 文档 (OpenAPI 3.0)';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * 配置命令选项
     */
    protected function configure()
    {
        parent::configure();
        $this->addOption('output', 'o', \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, '输出文件路径');
        $this->addOption('format', 'f', \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, '输出格式 (json|yaml)', 'json');
    }

    /**
     * 执行命令
     */
    public function handle()
    {
        $config = $this->container->get(ConfigInterface::class);

        // 检查是否启用
        if (!$config->get('swagger.enable', true)) {
            $this->error('Swagger 文档生成已禁用，请在配置中启用');
            return;
        }

        $this->info('开始生成 Swagger 文档...');
        $this->line('');

        // 获取输出路径
        $outputPath = $this->input->getOption('output');
        if (!$outputPath) {
            $outputDir = $config->get('swagger.output', BASE_PATH . '/public/swagger');
            $outputPath = $outputDir . '/swagger.json';
        }

        try {
            // 生成文档
            $generator = $this->container->get(SwaggerGenerator::class);
            $document = $generator->generate($outputPath);

            // 统计信息
            $pathCount = count($document['paths'] ?? []);
            $tagCount = count($document['tags'] ?? []);

            $this->info('✓ Swagger 文档生成成功!');
            $this->line('');
            $this->table(
                ['项目', '值'],
                [
                    ['输出路径', $outputPath],
                    ['接口数量', $pathCount],
                    ['分组数量', $tagCount],
                    ['OpenAPI 版本', $document['openapi'] ?? '3.0.3'],
                ]
            );

            $this->line('');
            $this->comment('提示: 可以通过 /swagger 路由访问 Swagger UI');

        } catch (\Throwable $e) {
            $this->error('生成失败: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
