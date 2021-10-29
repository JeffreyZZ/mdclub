<?php

declare(strict_types=1);

namespace MDClub\Service;

use MDClub\Facade\Library\Auth;
use MDClub\Facade\Library\Request;
use MDClub\Facade\Model\TokenModel;
use MDClub\Facade\Model\UserModel;
use MDClub\Facade\Validator\TokenValidator;
use MDClub\Helper\Ip;
use MDClub\Helper\Str;

/**
 * Token 服务
 */
class Token extends Abstracts
{
    /**
     * @inheritDoc
     */
    protected function getModel(): string
    {
        return \MDClub\Model\Token::class;
    }

    /**
     * 创建 Token
     *
     * @param array $data [name, password, device]
     *
     * @return string
     */
    public function create(array $data): string
    {
        if (!isset($data['device']) || !$data['device']) {
            $data['device'] = Request::getServerParams()['HTTP_USER_AGENT'] ?? '';
        }

        $userId = TokenValidator::create($data);
        // user_id column of accounts_token table is defined like this 
        //     UNIQUE KEY 'user_id'('user_id')
        // user_id cannot be duplicate, so we  need to reuse the existing valid token or delete  
        // the expired token before create a new token in the table.  
        $tokenInfo = TokenModel::where('user_id', $userId)->get();
        if ($tokenInfo)
        {
            // token 已过期，删除该 token
            $requestTime = Request::time();
            if ($tokenInfo['expire_time'] < $requestTime) {
                TokenModel::delete($tokenInfo['key']);
                $tokenInfo = false;
            }
        }

        // token 没有过期，继续使用
        if($tokenInfo)
        {
            return $tokenInfo['key'];
        }

        $token = Str::guid();
        TokenModel
            ::set('key', $token)
            ->set('user_id', $userId)
            ->set('device', $data['device'])
            ->set('expire_time', Request::time() + Auth::getLifeTime())
            ->insert();

        UserModel
            ::where('id', $userId)
            ->set('last_login', Request::getDatetimeStr())
            ->set('last_login_ip', Ip::getIp())
            ->set('last_login_location', Ip::getLocation())
            ->update();

        return $token;
    }
}
