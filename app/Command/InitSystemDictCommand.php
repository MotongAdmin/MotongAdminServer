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

use App\Constants\Constants;
use Psr\Container\ContainerInterface;
use App\Model\SysDictData;
use App\Model\SysDictType;
use App\Model\SysFieldDict;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;

/**
 * @Command
 */
class InitSystemDictCommand extends HyperfCommand
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct('init:system-dict');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Initialize system dictionary data');
    }
    
    public function handle()
    {
        $this->output->title('Start initializing system dictionaries from database data...');
        
        try {
            Db::beginTransaction();
            
            // 1. 用户状态字典
            $this->createDictType('sys_user_status', '用户状态', '系统用户状态字典');
            $this->createDictData('sys_user_status', [
                ['label' => '正常', 'value' => '1', 'list_class' => 'success'],
                ['label' => '禁用', 'value' => '0', 'list_class' => 'danger'],
            ]);
            
            // 2. 角色状态字典
            $this->createDictType('sys_role_status', '角色状态', '系统角色状态字典');
            $this->createDictData('sys_role_status', [
                ['label' => '正常', 'value' => '1', 'list_class' => 'success'],
                ['label' => '禁用', 'value' => '0', 'list_class' => 'danger'],
            ]);
            
            // 3. 菜单类型字典
            $this->createDictType('sys_menu_type', '菜单类型', '系统菜单类型字典');
            $this->createDictData('sys_menu_type', [
                ['label' => '目录', 'value' => 'M'],
                ['label' => '菜单', 'value' => 'C'],
                ['label' => '按钮', 'value' => 'F'],
            ]);
            
            // 4. 菜单状态字典
            $this->createDictType('sys_menu_status', '菜单状态', '系统菜单状态字典');
            $this->createDictData('sys_menu_status', [
                ['label' => '正常', 'value' => '1', 'list_class' => 'success'],
                ['label' => '停用', 'value' => '0', 'list_class' => 'danger'],
            ]);
            
            // 5. 菜单可见性字典
            $this->createDictType('sys_menu_visible', '菜单可见性', '系统菜单可见性字典');
            $this->createDictData('sys_menu_visible', [
                ['label' => '显示', 'value' => '1'],
                ['label' => '隐藏', 'value' => '0'],
            ]);
            
            // 6. API状态字典
            $this->createDictType('sys_api_status', 'API状态', '系统API状态字典');
            $this->createDictData('sys_api_status', [
                ['label' => '正常', 'value' => '1', 'list_class' => 'success'],
                ['label' => '停用', 'value' => '0', 'list_class' => 'danger'],
            ]);
            
            // 7. HTTP请求方法字典
            $this->createDictType('sys_http_method', 'HTTP请求方法', '系统HTTP请求方法字典');
            $this->createDictData('sys_http_method', [
                ['label' => 'GET', 'value' => 'GET'],
                ['label' => 'POST', 'value' => 'POST'],
                ['label' => 'PUT', 'value' => 'PUT'],
                ['label' => 'DELETE', 'value' => 'DELETE'],
                ['label' => 'PATCH', 'value' => 'PATCH'],
                ['label' => 'HEAD', 'value' => 'HEAD'],
                ['label' => 'OPTIONS', 'value' => 'OPTIONS'],
            ]);
            
            // 8. API分组字典
            $this->createDictType('sys_api_group', 'API分组', '系统API分组字典');
            $this->createDictData('sys_api_group', [
                ['label' => '用户管理', 'value' => 'user'],
                ['label' => '角色管理', 'value' => 'role'],
                ['label' => '菜单管理', 'value' => 'menu'],
                ['label' => 'API管理', 'value' => 'api'],
                ['label' => '认证授权', 'value' => 'auth'],
                ['label' => '系统管理', 'value' => 'system'],
            ]);
            
            // 9. 操作日志等级字典
            $this->createDictType('sys_log_level', '操作日志等级', '系统操作日志等级字典');
            $this->createDictData('sys_log_level', [
                ['label' => '普通', 'value' => '1', 'list_class' => 'info'],
                ['label' => '重要', 'value' => '2', 'list_class' => 'warning'],
                ['label' => '关键', 'value' => '3', 'list_class' => 'danger'],
            ]);
            
            // 10. 操作状态字典
            $this->createDictType('sys_operation_status', '操作状态', '系统操作状态字典');
            $this->createDictData('sys_operation_status', [
                ['label' => '成功', 'value' => '1', 'list_class' => 'success'],
                ['label' => '失败', 'value' => '0', 'list_class' => 'danger'],
            ]);
            
            // 11. 操作类型字典
            $this->createDictType('sys_operation_type', '操作类型', '系统操作类型字典');
            $this->createDictData('sys_operation_type', [
                ['label' => '登录', 'value' => 'LOGIN'],
                ['label' => '登出', 'value' => 'LOGOUT'],
                ['label' => '新增', 'value' => 'INSERT'],
                ['label' => '删除', 'value' => 'DELETE'],
                ['label' => '修改', 'value' => 'UPDATE'],
                ['label' => '查询', 'value' => 'QUERY'],
                ['label' => '授权', 'value' => 'GRANT'],
                ['label' => '导出', 'value' => 'EXPORT'],
                ['label' => '导入', 'value' => 'IMPORT'],
                ['label' => '强制登出', 'value' => 'FORCE_LOGOUT'],
                ['label' => '其他', 'value' => 'OTHER'],
            ]);
            
            // 12. 系统模块字典
            $this->createDictType('sys_module', '系统模块', '系统模块字典');
            $this->createDictData('sys_module', [
                ['label' => '用户', 'value' => 'user'],
                ['label' => '角色', 'value' => 'role'],
                ['label' => '菜单', 'value' => 'menu'],
                ['label' => '接口', 'value' => 'api'],
                ['label' => '系统', 'value' => 'system'],
                ['label' => '配置', 'value' => 'config'],
                ['label' => '字典数据', 'value' => 'dictData'],
                ['label' => '字典类型', 'value' => 'dictType'],
                ['label' => '其他', 'value' => 'other'],
            ]);
            
            // 13. 云平台字典
            $this->createDictType('sys_cloud_platform', '云平台', '各大云平台');
            $this->createDictData('sys_cloud_platform', [
                ['label' => '本地存储', 'value' => 'local'],
                ['label' => '七牛云', 'value' => 'qiniu', 'list_class' => 'warning'],
                ['label' => '阿里云', 'value' => 'aliyun'],
                ['label' => '腾讯云', 'value' => 'tencent'],
            ]);
            
            // 14. 开放平台字典
            $this->createDictType('sys_open_platform', '开放平台', '各大开放平台');
            $this->createDictData('sys_open_platform', [
                ['label' => '微信开放平台', 'value' => 'wexin'],
                ['label' => '抖音开放平台', 'value' => 'douyin'],
                ['label' => '支付宝开放平台', 'value' => 'alipay'],
            ]);
            
            // 15. 支付渠道字典
            $this->createDictType('sys_pay_channel', '支付渠道', '各大支付渠道');
            $this->createDictData('sys_pay_channel', [
                ['label' => '微信支付', 'value' => 'wechat'],
                ['label' => '支付宝', 'value' => 'alipay'],
            ]);
            
            // 16. 访问属性字典
            $this->createDictType('sys_access_type', '访问属性', '');
            $this->createDictData('sys_access_type', [
                ['label' => '公开', 'value' => 'public'],
                ['label' => '私有', 'value' => 'private', 'list_class' => 'danger'],
            ]);
            
            // 17. 组件类型字典
            $this->createDictType('sys_field_type', '组件类型', '用于确定字段使用哪种组件展示');
            $this->createDictData('sys_field_type', [
                ['label' => '文本', 'value' => 'text'],
                ['label' => '图像', 'value' => 'image'],
                ['label' => '下拉框', 'value' => 'select'],
                ['label' => '文件', 'value' => 'file'],
            ]);
            
            // 18. 存储目录字典
            $this->createDictType('sys_storage_dir', '存储目录', '各种文件的存储目录');
            $this->createDictData('sys_storage_dir', [
                ['label' => '图片', 'value' => 'image'],
                ['label' => '通用', 'value' => 'common'],
                ['label' => '视频', 'value' => 'video'],
                ['label' => '音频', 'value' => 'audio'],
            ]);

            // 19. 数据范围字典
            $this->createDictType('sys_data_scope', '数据范围', '数据范围字典');
            $this->createDictData('sys_data_scope', [
                ['label' => '全部', 'value' => '1'],
                ['label' => '本部门', 'value' => '2'],
                ['label' => '本部门及子部门', 'value' => '3'],
                ['label' => '自定义', 'value' => '4'],
            ]);

            // 初始化字段与字典类型的关联关系
            $this->initFieldDictMapping();
            
            Db::commit();
            $this->output->success('System dictionaries initialized successfully!');
            
        } catch (\Throwable $e) {
            Db::rollBack();
            $this->output->error('Failed to initialize system dictionaries: ' . $e->getMessage());
        }
    }
    
    /**
     * 初始化字段与字典类型的关联关系
     */
    protected function initFieldDictMapping(): void
    {
        $mappings = [
            // 用户表字段映射
            [
                'table_name' => 'user',
                'field_name' => 'status',
                'dict_type' => 'sys_user_status',
                'description' => '用户状态',
            ],
            
            // 角色表字段映射
            [
                'table_name' => 'sys_role',
                'field_name' => 'status',
                'dict_type' => 'sys_role_status',
                'description' => '角色状态',
            ],
            
            // 菜单表字段映射
            [
                'table_name' => 'sys_menu',
                'field_name' => 'menu_type',
                'dict_type' => 'sys_menu_type',
                'description' => '菜单类型',
            ],
            [
                'table_name' => 'sys_menu',
                'field_name' => 'status',
                'dict_type' => 'sys_menu_status',
                'description' => '菜单状态',
            ],
            [
                'table_name' => 'sys_menu',
                'field_name' => 'visible',
                'dict_type' => 'sys_menu_visible',
                'description' => '菜单可见性',
            ],
            
            // API表字段映射
            [
                'table_name' => 'sys_api',
                'field_name' => 'status',
                'dict_type' => 'sys_api_status',
                'description' => 'API状态',
            ],
            [
                'table_name' => 'sys_api',
                'field_name' => 'method',
                'dict_type' => 'sys_http_method',
                'description' => 'HTTP请求方法',
            ],
            [
                'table_name' => 'sys_api',
                'field_name' => 'group',
                'dict_type' => 'sys_api_group',
                'description' => 'API分组',
            ],
            
            // 操作日志表字段映射
            [
                'table_name' => 'sys_operation_log',
                'field_name' => 'log_level',
                'dict_type' => 'sys_log_level',
                'description' => '日志等级',
            ],
            [
                'table_name' => 'sys_operation_log',
                'field_name' => 'status',
                'dict_type' => 'sys_operation_status',
                'description' => '操作状态',
            ],
            [
                'table_name' => 'sys_operation_log',
                'field_name' => 'operation_type',
                'dict_type' => 'sys_operation_type',
                'description' => '操作类型',
            ],
            [
                'table_name' => 'sys_operation_log',
                'field_name' => 'operation',
                'dict_type' => 'sys_operation_type',
                'description' => '操作类型绑定',
            ],
            [
                'table_name' => 'sys_operation_log',
                'field_name' => 'module',
                'dict_type' => 'sys_module',
                'description' => '系统模块',
            ],
            
            // 配置相关字段映射
            [
                'table_name' => 'sys_config',
                'field_name' => 'config_type',
                'dict_type' => 'sys_field_type',
                'description' => '',
            ],
            
            // 短信配置字段映射
            [
                'table_name' => 'sys_sms_config',
                'field_name' => 'provider',
                'dict_type' => 'sys_cloud_platform',
                'description' => '绑定服务平台',
            ],
            
            // 支付配置字段映射
            [
                'table_name' => 'sys_payment_config',
                'field_name' => 'platform',
                'dict_type' => 'sys_pay_channel',
                'description' => '',
            ],
            
            // 小程序配置字段映射
            [
                'table_name' => 'sys_miniapp_config',
                'field_name' => 'platform',
                'dict_type' => 'sys_open_platform',
                'description' => '',
            ],
            
            // 存储配置字段映射
            [
                'table_name' => 'sys_storage_config',
                'field_name' => 'provider',
                'dict_type' => 'sys_cloud_platform',
                'description' => '',
            ],
            [
                'table_name' => 'sys_storage_config',
                'field_name' => 'access_type',
                'dict_type' => 'sys_access_type',
                'description' => '',
            ],
            
            // 端点配置字段映射
            [
                'table_name' => 'sys_endpoint_config',
                'field_name' => 'request_method',
                'dict_type' => 'sys_http_method',
                'description' => '',
            ],

            // 角色表字段映射
            [
                'table_name' => 'sys_role',
                'field_name' => 'data_scope',
                'dict_type' => 'sys_data_scope',
                'description' => '',
            ],
        ];
        
        foreach ($mappings as $mapping) {
            $exists = SysFieldDict::query()
                ->where('table_name', $mapping['table_name'])
                ->where('field_name', $mapping['field_name'])
                ->exists();
                
            if (!$exists) {
                SysFieldDict::create($mapping);
                $this->output->writeln(sprintf(
                    'Created field dict mapping: %s.%s -> %s',
                    $mapping['table_name'],
                    $mapping['field_name'],
                    $mapping['dict_type']
                ));
            }
        }
    }
    
    protected function createDictType(string $dictType, string $dictName, string $remark = '')
    {
        $exists = SysDictType::query()->where('dict_type', $dictType)->exists();
        if (!$exists) {
            SysDictType::query()->create([
                'dict_type' => $dictType,
                'dict_name' => $dictName,
                'status' => Constants::USER_STATUS_NORMAL,
                'is_system' => 1, // 标记为系统内置
                'remark' => $remark,
            ]);
            $this->output->writeln(sprintf('Created dict type: %s', $dictType));
        }
    }
    
    protected function createDictData(string $dictType, array $items)
    {
        foreach ($items as $index => $item) {
            $exists = SysDictData::query()
                ->where('dict_type', $dictType)
                ->where('dict_value', $item['value'])
                ->exists();
                
            if (!$exists) {
                SysDictData::query()->create([
                    'dict_type' => $dictType,
                    'dict_sort' => $index,
                    'dict_label' => $item['label'],
                    'dict_value' => $item['value'],
                    'status' => Constants::USER_STATUS_NORMAL,
                    'list_class' => $item['list_class'] ?? null,
                ]);
                $this->output->writeln(sprintf('Created dict data: %s - %s', $dictType, $item['label']));
            }
        }
    }
}