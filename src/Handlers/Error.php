<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Abstracts\ContainerAbstracts;
use App\Constant\ErrorConstant;
use App\Exception\ApiException;
use App\Exception\ValidationException;
use App\Middleware\Trace;
use Exception;
use Slim\Http\Body;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * 异常处理
 *
 * 未处理的异常，在生产环境显示错误页面，调试环境交由 whoops 处理
 */
class Error extends ContainerAbstracts
{
    /**
     * @param  Request    $request
     * @param  Response   $response
     * @param  Exception $exception
     * @return Response
     */
    public function __invoke(Request $request, Response $response, Exception $exception): Response
    {
        // 字段验证异常
        if ($exception instanceof ValidationException) {
            $output = [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'errors' => $exception->getErrors(),
            ];

            $isNeedCaptcha = $exception->isNeedCaptcha();
        }

        // API 异常
        elseif ($exception instanceof ApiException) {
            $output = [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ];

            $extraMessage = $exception->getExtraMessage();
            if ($extraMessage) {
                $output['extra_message'] = $extraMessage;
            }

            $isNeedCaptcha = $exception->isNeedCaptcha();
        }

        // 未处理的异常
        else {
            // 调试环境交由 whoops 处理
            if (APP_DEBUG) {
                throw $exception;
            }

            $output = [
                'code' => ErrorConstant::SYSTEM_ERROR[0],
                'message' => ErrorConstant::SYSTEM_ERROR[1],
            ];

            $isNeedCaptcha = false;
        }

        if ($isNeedCaptcha) {
            $captchaInfo = $this->captchaService->build();
            $output['captcha_token'] = $captchaInfo['token'];
            $output['captcha_image'] = $captchaInfo['image'];
        }

        $body = new Body(fopen('php://temp', 'r+b'));
        $body->write(json_encode($output));

        $response = $response
            ->withStatus(200)
            ->withHeader('Content-type', 'application/json')
            ->withBody($body);

        // 因为异常中不会自动调用中间件，所以这里手动调用中间件
        if (APP_DEBUG) {
            $response = (new Trace($this->container))($request, $response, static function () use ($response) {
                return $response;
            });
        }

        return $response;
    }
}
