<?php
/**
 * This file is part of Motong-Admin.
 *
 * @link     https://github.com/MotongAdmin
 * @document https://github.com/MotongAdmin
 * @contact  motong0306@hotmail.com
 * @author   zyvincent 
 * @Company  Motong Admin @ 2025
 * @license  GPL
 */
declare(strict_types=1);

namespace App\Model;

use Hyperf\Database\Model\SoftDeletes;

/**
 * 系统职位模型
 * @property int $post_id 职位ID
 * @property string $post_name 职位名称
 * @property string $post_code 职位编码
 * @property int $sort 显示顺序
 * @property int $status 状态（1正常 0停用）
 * @property string $remark 备注
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class SysPost extends Model
{
    use SoftDeletes;

    protected $table = 'sys_post';
    protected $primaryKey = 'post_id';

    protected $fillable = [
        'post_name',
        'post_code',
        'sort',
        'status',
        'remark',
    ];

    protected $casts = [
        'post_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 职位用户
     */
    public function users()
    {
        return $this->hasMany(User::class, 'post_id', 'post_id');
    }

    /**
     * 状态范围查询
     */
    public function scopeStatus($query, $status = 1)
    {
        return $query->where('status', $status);
    }

    /**
     * 正常状态的职位
     */
    public function scopeNormal($query)
    {
        return $query->where('status', 1);
    }

    /**
     * 按排序查询
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('post_id');
    }

    /**
     * 按职位名称搜索
     */
    public function scopeSearchByName($query, $keyword)
    {
        if (!empty($keyword)) {
            return $query->where('post_name', 'like', "%{$keyword}%");
        }
        return $query;
    }

    /**
     * 按职位编码搜索
     */
    public function scopeSearchByCode($query, $keyword)
    {
        if (!empty($keyword)) {
            return $query->where('post_code', 'like', "%{$keyword}%");
        }
        return $query;
    }
}
