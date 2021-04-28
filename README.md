# laravel-passport-miniprogram

扩展Laravel Passport,支持小程序登录；

## 环境需求

- PHP >= 7.1.3

## Installation

```bash
composer require larva/laravel-passport-miniprogram -vv
```

## 使用

在你的User模型类实现 `findForPassportMiniProgramRequest` 方法接收小程序提交的登录信息。

## 原理说明

验证用户登录，使用的是小程序内部的 auth.code2Session 获取到的 session_key 作为验证，服务器验证 session_key 和 openid ,则认为提交的 user_info 参数是可信的。
将会执行后续过程。你需要自行实现 findForPassportMiniProgramRequest 方法自己实现验证 session_key 的代码。 

## Usage

### Step 1 - Setting up the User model

On your `User` model and then add method `findForPassportMiniProgramRequest`.
`findForPassportMiniProgramRequest` should accept two arguments i.e. `$provider` and `$user`

**$provider - string - will be the social provider i.e. facebook, google, github etc.**

**$id - string - is the user id as per social provider for example facebook's user id 1234567890**

**And the function should find the user which is related to that information and return user object or return null if not found**

Below is how your `User` model should look like after above implementations.


```php
namespace App;

class User extends Authenticatable {
    
    use HasApiTokens, Notifiable;

    /**
    * Find user using social provider's user
    * 
    * @param string $sessionKey
    * @param string $provider Provider name as requested from oauth e.g. facebook
    * @param \Larva\Passport\MiniProgram\MiniProgramUser $socialUser
    *
    * @return User|null|void
    */
    public static function findForPassportMiniProgramRequest($sessionKey, $provider, $user) {
        
    }
}
```

| id | provider | social_id | user_id | created_at        | updated_at        |
|----|----------|------------------|---------|-------------------|-------------------|
| 1  | facebook | XXXXXXXXXXXXXX   | 1       | XX-XX-XX XX:XX:XX | XX-XX-XX XX:XX:XX |
| 2  | github   | XXXXXXXXXXXXXX   | 2       | XX-XX-XX XX:XX:XX | XX-XX-XX XX:XX:XX |
| 3  | google   | XXXXXXXXXXXXXX   | 3       | XX-XX-XX XX:XX:XX | XX-XX-XX XX:XX:XX |

**That's all folks**