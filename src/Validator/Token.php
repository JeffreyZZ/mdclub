<?php

declare(strict_types=1);

namespace MDClub\Validator;

use MDClub\Exception\ValidationException;
use MDClub\Facade\Library\Captcha;
use MDClub\Facade\Model\UserModel;
use MDClub\Helper\Ip;
use MDClub\Initializer\App;

/**
 * Token 验证
 */
class Token extends Abstracts
{
    protected $attributes = [
        'name' => '账号',
        'password' => '密码',
        'device' => '设备信息',
    ];

    /**
     * 创建时验证，验证成功返回 user_id
     *
     * @param array $data [name, password, device]
     *
     * @return int
     */
    public function create(array $data): int
    {
        $isNeedCaptcha = Captcha::isNextTimeNeed(
            Ip::getIpSign(),
            'create_token',
            App::$config['APP_DEBUG'] ? 30 : 3, // 调试模式放宽限制
            3600 * 24
        );

        $data = $this->data($data)
            ->field('name')->exist()->string()->notEmpty()
            ->field('password')->exist()->string()->notEmpty()
            ->field('device')->exist()->string()->length(null, 600)
            ->validate($isNeedCaptcha);

        $user = UserModel
            ::where($this->isEmail($data['name']) ? 'email' : 'username', $data['name'])
            ->field(['id', 'password', 'disable_time'])
            ->get();

        $errors = [];

        if (!$user) {
            $errors['password'] = '账号或密码错误';
        } elseif ($user['disable_time']) {
            $errors['name'] = '该账号已被禁用';
        } elseif (!$this->django_password_verify($data['password'], $user['password'])) {
            $errors['password'] = '账号或密码错误';
        }

        if ($errors) {
            throw new ValidationException($errors, $isNeedCaptcha);
        }

        return $user['id'];
    }

    /**
     * Verify a Django password (PBKDF2-SHA256)
     *
     * @ref http://stackoverflow.com/a/39311299/2224584
     * @param string $password   The password provided by the user
     * @param string $djangoHash The hash stored in the Django app
     * @return bool
     * @throws Exception
     */
    function django_password_verify(string $password, string $djangoHash): bool
    {
        $pieces = explode('$', $djangoHash);
        list($header, $iter, $salt, $hash) = $pieces;
        
        // Get the hash algorithm used:
        if (preg_match('#^pbkdf2_([a-z0-9A-Z]+)$#', $header, $m)) {
            $algo = $m[1];
        }

        $calc = hash_pbkdf2(
            $algo,
            $password,
            $salt,
            (int) $iter,
            32,
            true
        );

        return hash_equals($calc, base64_decode($hash));
    }

}
