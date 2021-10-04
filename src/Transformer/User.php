<?php

declare(strict_types=1);

namespace MDClub\Transformer;

use MDClub\Facade\Library\Auth;
use MDClub\Facade\Model\FollowModel;
use MDClub\Facade\Model\UserModel;
use MDClub\Facade\Service\UserAvatarService;
use MDClub\Facade\Service\UserCoverService;
use MDClub\Facade\Transformer\FollowTransformer;

/**
 * 用户转换器
 */
class User extends Abstracts
{
    protected $table = 'user';
    protected $primaryKey = 'id';
    protected $availableIncludes = ['is_followed', 'is_following', 'is_me'];
    protected $userExcept = [
        'password',
        'email',
        'create_ip',
        'create_location',
        'last_login',
        'last_login_ip',
        'last_login_location',
        'notification_unread',
        'inbox_unread',
        'update_time',
        'disable_time',
    ];
    protected $managerExcept = ['password'];

    /**
     * 格式化用户信息
     *
     * @param  array $item
     * @return array
     */
    protected function format(array $item): array
    {
        if (isset($item['id'], $item['avatar_text'])) {
            $item['avatar_text'] = UserAvatarService::getBrandUrls($item['id'], $item['avatar_text']);
        }

        if (isset($item['id'], $item['cover'])) {
            $item['cover'] = UserCoverService::getBrandUrls($item['id'], $item['cover']);
        }

        if (isset($item['headline'])) {
            $item['headline'] = htmlspecialchars($item['headline']);
        }

        if (isset($item['bio'])) {
            $item['bio'] = htmlspecialchars($item['bio']);
        }

        if (isset($item['blog'])) {
            $item['blog'] = htmlspecialchars($item['blog']);
        }

        if (isset($item['company'])) {
            $item['company'] = htmlspecialchars($item['company']);
        }

        if (isset($item['location'])) {
            $item['location'] = htmlspecialchars($item['location']);
        }

        return $item;
    }

    /**
     * 添加 is_followed 状态
     *
     * @param  array $items
     * @param  array $knownRelationship
     * @return array
     */
    protected function isFollowed(array $items, array $knownRelationship): array
    {
        $currentUserId = Auth::userId();
        $userIds = collect($items)->pluck('id')->unique()->diff($currentUserId)->all();
        $followedUserIds = [];

        if ($userIds && $currentUserId) {
            if (isset($knownRelationship['is_followed'])) {
                $followedUserIds = $knownRelationship['is_followed'] ? $userIds : [];
            } else {
                $followedUserIds = FollowModel::where([
                    'user_id' => $userIds,
                    'followable_id' => $currentUserId,
                    'followable_type' => 'user',
                ])->pluck('user_id');
            }
        }

        foreach ($items as &$item) {
            $item['relationships']['is_followed'] =  in_array($item['id'], $followedUserIds);
        }

        return $items;
    }

    /**
     * 添加 is_following 状态
     *
     * @param  array $items
     * @param  array $knownRelationship
     * @return array
     */
    protected function isFollowing(array $items, array $knownRelationship): array
    {
        $userId = Auth::userId();
        $keys = collect($items)->pluck('id')->unique()->diff($userId)->all();
        $followingKeys = [];

        if ($keys && $userId) {
            if (isset($knownRelationship['is_following'])) {
                $followingKeys = $knownRelationship['is_following'] ? $keys : [];
            } else {
                $followingKeys = FollowTransformer::getInRelationship($keys, 'user');
            }
        }

        foreach ($items as &$item) {
            $item['relationships']['is_following'] = in_array($item['id'], $followingKeys);
        }

        return $items;
    }

    /**
     * 添加 is_me 状态
     *
     * @param  array $items
     * @return array
     */
    protected function isMe(array $items): array
    {
        $userId = Auth::userId();

        foreach ($items as &$item) {
            $item['relationships']['is_me'] = $userId === $item['id'];
        }

        return $items;
    }

    /**
     * 获取 user 子资源
     *
     * @param  array $userIds
     * @return array
     */
    public function getInRelationship(array $userIds): array
    {
        if (!$userIds) {
            return [];
        }

        $users = UserModel
            ::field(['id', 'avatar_text', 'username', 'headline'])
            ->select($userIds);

        return collect($users)
            ->keyBy('id')
            ->map(function ($item) {
                $item['avatar_text'] = UserAvatarService::getBrandUrls($item['id'], $item['avatar_text']);
                $item['headline'] = htmlspecialchars($item['headline']);

                return $item;
            })
            ->unionFill($userIds)
            ->all();
    }
}
