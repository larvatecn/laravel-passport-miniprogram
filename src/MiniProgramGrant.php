<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */
declare (strict_types=1);

namespace Larva\Passport\MiniProgram;

use DateInterval;
use RuntimeException;
use Illuminate\Http\Request;
use Laravel\Passport\Bridge\User;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 小程序登录
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class MiniProgramGrant extends AbstractGrant
{
    /**
     * @param UserRepositoryInterface $userRepository
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     * @throws \Exception
     */
    public function __construct(UserRepositoryInterface $userRepository, RefreshTokenRepositoryInterface $refreshTokenRepository)
    {
        $this->setUserRepository($userRepository);
        $this->setRefreshTokenRepository($refreshTokenRepository);
        $this->refreshTokenTTL = new DateInterval('P1M');
    }

    /**
     * @throws OAuthServerException
     */
    public function respondToAccessTokenRequest(ServerRequestInterface $request, ResponseTypeInterface $responseType, DateInterval $accessTokenTTL): ResponseTypeInterface
    {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request));
        $user = $this->validateUser($request);
        // Finalize the requested scopes
        $scopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getIdentifier());
        // Issue and persist new tokens
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getIdentifier(), $scopes);
        $refreshToken = $this->issueRefreshToken($accessToken);
        // Inject tokens into response
        $responseType->setAccessToken($accessToken);
        $responseType->setRefreshToken($refreshToken);
        return $responseType;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return 'mini-program';
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return UserEntityInterface
     * @throws OAuthServerException
     */
    protected function validateUser(ServerRequestInterface $request): UserEntityInterface
    {
        $laravelRequest = new Request($request->getParsedBody());
        if (!$laravelRequest->has('provider')) {
            throw OAuthServerException::invalidRequest('provider');
        }
        $user = $this->getUserEntityByRequest($laravelRequest);
        if ($user instanceof UserEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));
            throw OAuthServerException::invalidCredentials();
        }
        return $user;
    }

    /**
     * Retrieve user by request.
     * @param $request
     * @return User|void
     */
    protected function getUserEntityByRequest($request)
    {
        $provider = config('auth.guards.api.provider');
        if (is_null($model = config('auth.providers.' . $provider . '.model'))) {
            throw new RuntimeException('Unable to determine authentication model from configuration.');
        }
        if (method_exists($model, 'findAndValidateForPassportMiniProgramRequest')) {
            $user = (new $model)->findAndValidateForPassportMiniProgramRequest($request);
            if (! $user) {
                return null;
            }
            return new User($user->getAuthIdentifier());
        } else {
            throw new RuntimeException('Unable to find findAndValidateForPassportMiniProgramRequest method on user model.');
        }
    }
}