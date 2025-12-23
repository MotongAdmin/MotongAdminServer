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

use App\Model\User;
use App\Model\SysRole;
use App\Constants\Constants;
use App\Constants\ErrorCode;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Hyperf\Utils\Str;
use Symfony\Component\Console\Input\InputOption;

/**
 * 初始化超级管理员账号命令
 * 
 * @Command
 */
class AdminUserSeederCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('admin:seed');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('初始化超级管理员账号');
        $this->addOption('reset', null, InputOption::VALUE_NONE, '重置超级管理员账号');
        $this->addOption('username', null, InputOption::VALUE_OPTIONAL, '指定用户名 (默认: admin)');
        $this->addOption('password', null, InputOption::VALUE_OPTIONAL, '指定密码 (默认: 随机生成)');
        $this->addOption('email', null, InputOption::VALUE_OPTIONAL, '指定邮箱 (默认: admin@example.com)');
        $this->addOption('mobile', null, InputOption::VALUE_OPTIONAL, '指定手机号 (默认: 13800138000)');
    }

    public function handle()
    {
        $randomPassword = Str::random(10);
        $reset = $this->input->getOption('reset');
        $username = $this->input->getOption('username') ?: 'admin';
        $password = $this->input->getOption('password') ?: $randomPassword;
        $email = $this->input->getOption('email') ?: 'admin@example.com';
        $mobile = $this->input->getOption('mobile') ?: '13800138000';
        
        $this->line('开始初始化超级管理员账号...', 'info');
        
        try {
            Db::beginTransaction();
            
            // 1. 创建或更新超级管理员角色
            $adminRole = $this->createOrUpdateAdminRole($reset);
            $this->line('超级管理员角色处理完成 (ID: ' . $adminRole->role_id . ')', 'comment');
            
            // 2. 创建或更新超级管理员用户
            $adminUser = $this->createOrUpdateAdminUser($username, $password, $email, $mobile, $adminRole->role_id, $reset);
            $this->line('超级管理员用户处理完成 (ID: ' . $adminUser->user_id . ')', 'comment');
            
            Db::commit();
            
            $this->line('', 'info');
            $this->line('====== 超级管理员账号信息 ======', 'info');
            $this->line('用户名: ' . $adminUser->username, 'comment');
            $this->line('密码: ' . $password, 'comment');
            $this->line('邮箱: ' . $adminUser->email, 'comment');
            $this->line('手机号: ' . $adminUser->mobile, 'comment');
            $this->line('角色: ' . $adminRole->role_name . ' (ID: ' . $adminRole->role_id . ')', 'comment');
            $this->line('==============================', 'info');
            $this->line('', 'info');
            $this->line('请及时修改超级管理员密码，并妥善保管！', 'comment');
            $this->line('超级管理员账号初始化成功！', 'info');
            
        } catch (\Exception $e) {
            Db::rollBack();
            $this->line('初始化失败：' . $e->getMessage(), 'error');
            $this->line('错误详情：' . $e->getTraceAsString(), 'error');
            return 1;
        }
        
        return 0;
    }

    /**
     * 创建或更新超级管理员角色
     */
    private function createOrUpdateAdminRole(bool $reset = false): SysRole
    {
        // 查找现有的超级管理员角色
        $adminRole = SysRole::where('role_id', Constants::SUPER_ADMIN_ROLE_ID)->first();
        
        if ($adminRole && !$reset) {
            $this->line('超级管理员角色已存在，跳过创建', 'comment');
            return $adminRole;
        }
        
        if ($adminRole && $reset) {
            $this->line('重置模式：更新现有超级管理员角色', 'comment');
            $adminRole->update([
                'role_name' => '超级管理员',
                'role_key' => 'super_admin',
                'role_sort' => 1,
                'status' => Constants::ROLE_STATUS_NORMAL,
                'del_flag' => Constants::DELETE_FLAG_EXIST,
                'remark' => '系统超级管理员，拥有所有权限',
            ]);
            return $adminRole;
        }
        
        // 创建新的超级管理员角色
        $this->line('创建超级管理员角色...', 'comment');
        $adminRole = SysRole::create([
            'role_id' => Constants::SUPER_ADMIN_ROLE_ID,
            'role_name' => '超级管理员',
            'role_key' => 'super_admin',
            'role_sort' => 1,
            'status' => Constants::ROLE_STATUS_NORMAL,
            'del_flag' => Constants::DELETE_FLAG_EXIST,
            'remark' => '系统超级管理员，拥有所有权限',
        ]);
        
        return $adminRole;
    }

    /**
     * 创建或更新超级管理员用户
     */
    private function createOrUpdateAdminUser(string $username, string $password, string $email, string $mobile, int $roleId, bool $reset = false): User
    {
        // 查找现有的超级管理员用户
        $adminUser = User::where('username', $username)->first();
        
        if ($adminUser && !$reset) {
            $this->line('用户名 "' . $username . '" 已存在，跳过创建', 'comment');
            // 更新角色ID
            $adminUser->role_id = $roleId;
            $adminUser->status = Constants::USER_STATUS_NORMAL;
            $adminUser->save();
            return $adminUser;
        }
        
        if ($adminUser && $reset) {
            $this->line('重置模式：更新现有管理员用户', 'comment');
            $adminUser->update([
                'nickname' => '超级管理员',
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $email,
                'mobile' => $mobile,
                'role_id' => $roleId,
                'status' => Constants::USER_STATUS_NORMAL,
            ]);
            return $adminUser;
        }
        
        // 创建新的超级管理员用户
        $this->line('创建超级管理员用户...', 'comment');
        $adminUser = User::create([
            'username' => $username,
            'nickname' => '超级管理员',
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email' => $email,
            'mobile' => $mobile,
            'role_id' => $roleId,
            'status' => Constants::USER_STATUS_NORMAL,
        ]);
        
        return $adminUser;
    }
} 