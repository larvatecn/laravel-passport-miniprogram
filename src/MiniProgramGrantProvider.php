<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

declare (strict_types=1);

namespace Larva\Passport\MiniProgram;

use Exception;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Bridge\UserRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;
use League\OAuth2\Server\AuthorizationServer;

/**
 * 小程序登录
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class MiniProgramGrantProvider extends PassportServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     * @throws Exception
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->afterResolving(AuthorizationServer::class, function (AuthorizationServer $oauthServer) {
            $oauthServer->enableGrantType($this->makeMiniProgramGrant(), Passport::tokensExpireIn());
        });
    }

    /**
     * Create and configure a Password grant instance.
     *
     * @return MiniProgramGrant
     * @throws Exception
     */
    protected function makeMiniProgramGrant(): MiniProgramGrant
    {
        $grant = new MiniProgramGrant(
            $this->app->make(UserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );
        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());
        return $grant;
    }
}