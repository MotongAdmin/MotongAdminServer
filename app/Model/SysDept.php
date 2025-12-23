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
 * 系统部门模型
 * @property int $dept_id 部门ID
 * @property int $parent_id 上级部门ID
 * @property string $dept_path 部门路径
 * @property string $dept_name 部门名称
 * @property int $sort 显示顺序
 * @property string $leader 负责人
 * @property string $phone 联系电话
 * @property string $email 邮箱
 * @property int $status 部门状态（1正常 0停用）
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class SysDept extends Model
{
    use SoftDeletes;

    protected $table = 'sys_dept';
    protected $primaryKey = 'dept_id';

    protected $fillable = [
        'parent_id',
        'dept_path',
        'dept_name',
        'sort',
        'leader',
        'phone',
        'email',
        'status',
    ];

    protected $casts = [
        'dept_id' => 'integer',
        'parent_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 父部门
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'dept_id');
    }

    /**
     * 子部门
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'dept_id');
    }

    /**
     * 部门用户
     */
    public function users()
    {
        return $this->hasMany(User::class, 'dept_id', 'dept_id');
    }

    /**
     * 状态范围查询
     */
    public function scopeStatus($query, $status = 1)
    {
        return $query->where('status', $status);
    }

    /**
     * 正常状态的部门
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
        return $query->orderBy('sort')->orderBy('dept_id');
    }

    /**
     * 获取部门层级路径
     */
    public function getFullPath()
    {
        if (empty($this->dept_path)) {
            return $this->dept_name;
        }
        
        $pathIds = explode(',', trim($this->dept_path, ','));
        $pathIds[] = $this->dept_id;
        
        $depts = self::whereIn('dept_id', $pathIds)->orderByRaw('FIELD(dept_id, ' . implode(',', $pathIds) . ')')->get();
        
        return $depts->pluck('dept_name')->implode(' > ');
    }

    /**
     * 更新部门路径
     */
    public function updateDeptPath()
    {
        if ($this->parent_id == 0) {
            $this->dept_path = '';
        } else {
            $parent = self::find($this->parent_id);
            if ($parent) {
                $this->dept_path = $parent->dept_path ? $parent->dept_path . ',' . $parent->dept_id : $parent->dept_id;
            }
        }
        $this->save();
        
        // 递归更新子部门路径
        $this->updateChildrenPath();
    }

    /**
     * 递归更新子部门路径
     */
    protected function updateChildrenPath()
    {
        $children = $this->children;
        foreach ($children as $child) {
            $child->dept_path = $this->dept_path ? $this->dept_path . ',' . $this->dept_id : $this->dept_id;
            $child->save();
            $child->updateChildrenPath();
        }
    }
}
