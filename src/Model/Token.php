<?php

declare(strict_types=1);

namespace MDClub\Model;

/**
 * Token 模型
 */
class Token extends Abstracts
{
    public $table = 'accounts_token';
    public $primaryKey = 'key';
    protected $timestamps = true;

    public $columns = [
        'key',
        'user_id',
        'device',
        'create_time',
        'update_time',
        'expire_time',
    ];
}
