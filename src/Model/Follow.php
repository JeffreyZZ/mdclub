<?php

declare(strict_types=1);

namespace App\Model;

use App\Abstracts\ModelAbstracts;

/**
 * 关注模型
 */
class Follow extends ModelAbstracts
{
    public $table = 'follow';
    protected $timestamps = true;

    protected const UPDATE_TIME = false; // 不维护 update_time 字段

    public $columns = [
        'user_id',
        'followable_id',
        'followable_type',
        'create_time',
    ];
}
