# laravel-passport-miniprogram

扩展Laravel Passport,支持小程序登录；

## 环境需求

- PHP >= 7.3

## Installation

```bash
composer require larva/laravel-passport-miniprogram -vv
```

## 使用

在你的User模型类实现 `findAndValidateForPassportMiniProgram` 方法接收小程序提交的登录信息。

## 原理说明

验证用户登录，使用的是小程序内部的 auth.code2Session 获取到的 session_key 作为验证，服务器验证 session_key 和 openid ,则认为提交的 user_info 参数是可信的。
将会执行后续过程。你需要自行实现 findAndValidateForPassportMiniProgram 方法自己实现验证 session_key 的代码。 

## Usage

### Step 1 - Setting up the User model

On your `User` model and then add method `findAndValidateForPassportMiniProgram`.
`findAndValidateForPassportMiniProgram` 


```php
namespace App;

class User extends Authenticatable {
    
    use HasApiTokens, Notifiable;

    /**
    * Find user using social provider's user
    * 
    * @param \Illuminate\Http\Request $request
    *
    * @return User|null|void
    */
    public static function findAndValidateForPassportMiniProgram($request) {
        
    }
}
```

| id | provider | social_id | user_id | created_at        | updated_at        |
|----|----------|------------------|---------|-------------------|-------------------|
| 1  | facebook | XXXXXXXXXXXXXX   | 1       | XX-XX-XX XX:XX:XX | XX-XX-XX XX:XX:XX |
| 2  | github   | XXXXXXXXXXXXXX   | 2       | XX-XX-XX XX:XX:XX | XX-XX-XX XX:XX:XX |
| 3  | google   | XXXXXXXXXXXXXX   | 3       | XX-XX-XX XX:XX:XX | XX-XX-XX XX:XX:XX |

**That's all folks**