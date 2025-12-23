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
declare (strict_types=1);
namespace App\Model;

use ZYProSoft\Model\LoginUserModable;
/**
 * @property int $user_id 用户唯一ID
 * @property string $username 用户名
 * @property string $nickname 用户昵称
 * @property string $avatar 头像
 * @property string $password 密码
 * @property string $mobile 手机号
 * @property string $wechat 微信
 * @property string $work_wechat 企业微信
 * @property string $email 邮箱
 * @property string $token 令牌
 * @property string $token_expire 令牌过期时间
 * @property string $token_refresh_expire 令牌可刷新过期时间
 * @property string $last_login_time 上次登录时间
 * @property int $role_id 普通用户
 * @property int $dept_id 部门ID
 * @property int $post_id 职位ID
 * @property int $status 用户状态 1:启用 0:禁用
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property string $deleted_at 
 */
class User extends Model implements LoginUserModable
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';
    protected $primaryKey = 'user_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'nickname',
        'avatar',
        'password',
        'email',
        'mobile',
        'role_id',
        'dept_id',
        'post_id',
        'status',
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['user_id' => 'integer', 'role_id' => 'integer', 'dept_id' => 'integer', 'post_id' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
    protected $hidden = ['password'];
    public function getId()
    {
        return $this->user_id;
    }

    public static function retrieveById($key) : ?LoginUserModable
    {
        return User::find($key);
    }

    public static function getByToken($token): ?LoginUserModable
    {
        return User::where('token',$token)->first();
    }

    public function isAdmin()
    {
        return $this->role_id > 0;
    }

    /**
     * 关联角色
     */
    public function role()
    {
        return $this->belongsTo(SysRole::class, 'role_id', 'role_id');
    }

    /**
     * 关联部门
     */
    public function dept()
    {
        return $this->belongsTo(SysDept::class, 'dept_id', 'dept_id');
    }

    /**
     * 关联职位
     */
    public function post()
    {
        return $this->belongsTo(SysPost::class, 'post_id', 'post_id');
    }

    /**
     * 状态范围查询
     */
    public function scopeStatus($query, $status = 1)
    {
        return $query->where('status', $status);
    }

    /**
     * 正常状态的用户
     */
    public function scopeNormal($query)
    {
        return $query->where('status', 1);
    }
}