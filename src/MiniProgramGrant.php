<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */
declare (strict_types=1);

namespace Larva\Passport\MiniProgram;

use DateInterval;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
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
     * MiniProgramGrant constructor.
     * @param UserRepositoryInterface $userRepository
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     */
    public function __construct(UserRepositoryInterface $userRepository, RefreshTokenRepositoryInterface $refreshTokenRepository)
    {
        $this->setUserRepository($userRepository);
        $this->setRefreshTokenRepository($refreshTokenRepository);
        $this->refreshTokenTTL = new DateInterval('P1M');
    }

    /**
     * 获取标识
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'mini-program';
    }

    /**
     * Respond to an incoming request.
     * @param ServerRequestInterface $request
     * @param ResponseTypeInterface $responseType
     * @param DateInterval $accessTokenTTL
     * @return ResponseTypeInterface
     * @throws OAuthServerException
     * @throws UniqueTokenIdentifierConstraintViolationException
     */
    public function respondToAccessTokenRequest(ServerRequestInterface $request, ResponseTypeInterface $responseType, DateInterval $accessTokenTTL): ResponseTypeInterface
    {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request));
        $user = $this->validateUser($request, $client);

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
     * 验证用户
     * @param ServerRequestInterface $request
     * @param ClientEntityInterface $client
     * @return UserEntityInterface
     * @throws OAuthServerException
     */
    protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $client): UserEntityInterface
    {
        $laravelRequest = new Request($request->getParsedBody());
        if (!$laravelRequest->has('provider')) {
            throw OAuthServerException::invalidRequest('provider');
        }
        $user = $this->getUserEntityByRequest($laravelRequest, $this->getIdentifier(), $client);
        if ($user instanceof UserEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));
            throw OAuthServerException::invalidCredentials();
        }
        return $user;
    }

    /**
     * Retrieve user by request.
     * @param $request
     * @param string $grantType
     * @param ClientEntityInterface $clientEntity
     * @return User|null
     * @throws OAuthServerException
     */
    protected function getUserEntityByRequest($request, string $grantType, ClientEntityInterface $clientEntity): ?User
    {
        $provider = config('auth.guards.api.provider');
        if (is_null($model = config('auth.providers.' . $provider . '.model'))) {
            throw OAuthServerException::serverError('Unable to determine authentication model from configuration.');
        }
        if (method_exists($model, 'findAndValidateForPassportMiniProgram')) {
            $user = (new $model)->findAndValidateForPassportMiniProgram($request);
            if (!$user) {
                return null;
            }
            return new User($user->getAuthIdentifier());
        } else {
            throw OAuthServerException::serverError('Unable to find findAndValidateForPassportMiniProgram method on user model.');
        }
    }
}