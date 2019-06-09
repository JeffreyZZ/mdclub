<?php

declare(strict_types=1);

namespace App\Model;

use App\Abstracts\ModelAbstracts;

/**
 * 提问模型
 */
class Question extends ModelAbstracts
{
    public $table = 'question';
    public $primaryKey = 'question_id';
    protected $timestamps = true;
    protected $softDelete = true;

    public $columns = [
        'question_id',
        'user_id',
        'title',
        'content_markdown',
        'content_rendered',
        'comment_count',
        'answer_count',
        'view_count',
        'follower_count',
        'vote_count',
        'create_time',
        'update_time',
        'delete_time',
    ];

    protected function beforeInsert(array $data): array
    {
        return collect($data)->union([
            'comment_count'    => 0,
            'answer_count'     => 0,
            'view_count'       => 0,
            'follower_count'   => 0,
            'vote_count'       => 0,
            'last_answer_time' => 0,
        ])->all();
    }
}
