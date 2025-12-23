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

use App\Model\SysConfig;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class InitSystemConfigCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var SysConfig
     */
    protected $configModel;

    public function __construct(ContainerInterface $container, SysConfig $configModel)
    {
        $this->container = $container;
        $this->configModel = $configModel;
        parent::__construct('init:system-config');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('初始化系统配置数据（基于数据库现有数据）');
    }

    public function handle()
    {
        $this->output->title('开始初始化系统配置数据');

        // 从数据库中读取的实际配置数据
        $configs = [
            [
                'config_key' => 'cloud_storage',
                'config_value' => 'aliyun',
                'config_name' => '对象存储',
                'remark' => '当前系统使用的对象存储配置',
                'is_system' => 1,
                'config_type' => 'select',
                'dict_type' => 'sys_cloud_platform'
            ],
            [
                'config_key' => 'sms_platform',
                'config_value' => 'qiniu',
                'config_name' => '短信平台',
                'remark' => '当前系统使用的短信平台配置',
                'is_system' => 1,
                'config_type' => 'select',
                'dict_type' => 'sys_cloud_platform'
            ]
        ];

        foreach ($configs as $config) {
            $this->createOrUpdateConfig($config);
        }

        $this->output->success('系统配置数据初始化完成');
    }

    /**
     * 创建或更新配置
     * 
     * @param array $config
     * @return void
     */
    protected function createOrUpdateConfig(array $config)
    {
        $existConfig = $this->configModel->where('config_key', $config['config_key'])->first();

        if ($existConfig) {
            $this->output->note(sprintf('配置 [%s] 已存在，进行更新', $config['config_key']));
            
            // 如果已有配置是系统配置，则保持其系统配置属性
            if ($existConfig->is_system == 1) {
                $config['is_system'] = 1;
            }
            
            // 更新配置，但保留系统配置状态
            $existConfig->update($config);
        } else {
            $this->output->note(sprintf('创建配置 [%s]', $config['config_key']));
            $this->configModel->create($config);
        }
    }
}