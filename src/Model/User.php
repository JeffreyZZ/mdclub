<?php

declare(strict_types=1);

namespace MDClub\Model;

use MDClub\Facade\Library\Auth;
use MDClub\Facade\Library\Request;
use MDClub\Helper\Ip;

/**
 * 用户模型
 */
class User extends Abstracts
{
    public $table = 'user';
    public $primaryKey = 'id';
    protected $timestamps = true;

    // 被禁用的用户也是真实用户，不作为软删除字段处理

    public $columns = [
        'id',
        'username',
        'email',
        'avatar',
        'cover',
        'password',
        'create_ip',
        'create_location',
        'last_login',
        'last_login_ip',
        'last_login_location',
        'follower_count',
        'followee_count',
        'following_article_count',
        'following_question_count',
        'following_topic_count',
        'article_count',
        'question_count',
        'answer_count',
        'notification_unread',
        'inbox_unread',
        'headline',
        'bio',
        'blog',
        'company',
        'location',
        'create_time',
        'update_time',
        'disable_time',
    ];

    public $allowOrderFields = [
        'follower_count',
        'create_time',
    ];

    public $allowFilterFields = [
        'id',
        'username',
    ];

    public function __construct()
    {
        if (Auth::isManager()) {
            $this->allowFilterFields[] = 'email';
        }

        parent::__construct();
    }

    /**
     * 密码加密方式
     *
     * @param  string      $password
     * @return bool|string
     */
    private function passwordHash($password)
    {
        # return password_hash($password, PASSWORD_DEFAULT);
        return $this->django_password_hash($password);
    }

    /**
     * Django PBKDF2 密码加密方式
     * @ref https://stackoverflow.com/questions/39310898
     * @param  string      $password
     * @return bool|string
     */
    private function django_password_hash($password)
    {
        $algo = "sha256";
        $iterations = 26000;
        // Generate a random IV using openssl_random_pseudo_bytes()
        // random_bytes() or another suitable source of randomness
        $salt = base64_encode(openssl_random_pseudo_bytes(16));
        $hash = hash_pbkdf2(
            $algo, 
            $password, 
            $salt, 
            (int) $iterations,
            32,
            true
        );

        return "pbkdf2_".$algo."$".$iterations."$".$salt."$".base64_encode($hash);
    }

    /**
     * @inheritDoc
     */
    protected function beforeInsert(array $data): array
    {
        $data = collect($data)->union([
            'avatar' => '',
            'cover' => '',
            'create_ip' => Ip::getIp(),
            'create_location' => Ip::getLocation(),
            'last_login' => Request::getDatetimeStr(),
            'last_login_ip' => Ip::getIp(),
            'last_login_location' => Ip::getLocation(),
            'follower_count' => 0,
            'followee_count' => 0,
            'following_article_count' => 0,
            'following_question_count' => 0,
            'following_topic_count' => 0,
            'article_count' => 0,
            'question_count' => 0,
            'answer_count' => 0,
            'notification_unread' => 0,
            'inbox_unread' => 0,
            'headline' => '',
            'bio' => '',
            'blog' => '',
            'company' => '',
            'location' => '',
            'disable_time' => 0,
        ])->all();

        $data['password'] = $this->passwordHash($data['password']);

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function beforeUpdate(array $data): array
    {
        if (isset($data['password'])) {
            $data['password'] = $this->passwordHash($data['password']);
        }

        return $data;
    }

    /**
     * 根据 url 参数获取未禁用的用户列表
     *
     * @return array
     */
    public function getList(): array
    {
        return $this
            ->where($this->getWhereFromRequest(['disable_time' => 0]))
            ->order($this->getOrderFromRequest(['create_time' => 'ASC']))
            ->paginate();
    }

    /**
     * 根据 url 参数获取已禁用的用户列表
     *
     * @return array
     */
    public function getDisabled(): array
    {
        return $this
            ->where($this->getWhereFromRequest(['disable_time[>]' => 0]))
            ->order($this->getOrderFromRequest(['disable_time' => 'DESC']))
            ->paginate();
    }

    /**
     * 增加指定用户的回答数量
     *
     * @param int $userId
     * @param int $count
     */
    public function incAnswerCount(int $userId, int $count = 1): void
    {
        $this
            ->where('id', $userId)
            ->inc('answer_count', $count)
            ->update();
    }

    /**
     * 减少指定用户的回答数量
     *
     * @param int $userId
     * @param int $count
     */
    public function decAnswerCount(int $userId, int $count = 1): void
    {
        $this
            ->where('id', $userId)
            ->dec('answer_count', $count)
            ->update();
    }

    /**
     * 增加指定用户的文章数量
     *
     * @param int $userId
     * @param int $count
     */
    public function incArticleCount(int $userId, int $count = 1): void
    {
        $this
            ->where('id', $userId)
            ->inc('article_count', $count)
            ->update();
    }

    /**
     * 减少指定用户的文章数量
     *
     * @param int $userId
     * @param int $count
     */
    public function decArticleCount(int $userId, int $count = 1): void
    {
        $this
            ->where('id', $userId)
            ->dec('article_count', $count)
            ->update();
    }

    /**
     * 增加指定用户的提问数量
     *
     * @param int $userId
     * @param int $count
     */
    public function incQuestionCount(int $userId, int $count = 1): void
    {
        $this
            ->where('id', $userId)
            ->inc('question_count', $count)
            ->update();
    }

    /**
     * 减少指定用户的提问数量
     *
     * @param int $userId
     * @param int $count
     */
    public function decQuestionCount(int $userId, int $count = 1): void
    {
        $this
            ->where('id', $userId)
            ->dec('question_count', $count)
            ->update();
    }

    /**
     * 减少指定用户关注的话题数量
     *
     * @param int $userId
     * @param int $count
     */
    public function decFollowingTopicCount(int $userId, int $count = 1): void
    {
        $this
            ->where('id', $userId)
            ->dec('following_topic_count', $count)
            ->update();
    }
}
