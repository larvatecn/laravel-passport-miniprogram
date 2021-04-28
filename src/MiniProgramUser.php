<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 * @license http://www.larva.com.cn/license/
 */

namespace Larva\Passport\MiniProgram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * 小程序用户
 * @property int $id ID
 * @property int|null $user_id 用户ID
 * @property string $open_id 社交用户ID
 * @property string|null $union_id 联合ID
 * @property string|null $name 用户名
 * @property string|null $nickname 昵称
 * @property string|null $email 邮箱
 * @property string|null $avatar 头像
 * @property string $provider 供应商
 * @property array|null $data 附加数据
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder|MiniProgramUser byOpenid($openid)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniProgramUser byUnionid($unionid)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniProgramUser byProvider($provider)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniProgramUser byOpenidAndProvider($openid, $provider)
 * @method static \Illuminate\Database\Eloquent\Builder|MiniProgramUser byUnionidAndProvider($unionid, $provider)
 * @method static MiniProgramUser|null find($id)
 * @author Tongle Xu <xutongle@gmail.com>
 */
class MiniProgramUser extends Model
{
    const PROVIDER_QQ = 'qq';
    const PROVIDER_ALIPAY = 'alipay';
    const PROVIDER_BAIDU = 'baidu';
    const PROVIDER_WECHAT = 'wechat';
    const PROVIDER_TAOBAO = 'taobao';

    /**
     * 与模型关联的数据表。
     *
     * @var string
     */
    protected $table = 'mini_program_users';

    /**
     * 可以批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'open_id', 'union_id', 'provider', 'name', 'nickname', 'email', 'avatar', 'data',
    ];

    /**
     * 这个属性应该被转换为原生类型.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * 为数组 / JSON 序列化准备日期。
     *
     * @param \DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /**
     * Get the user relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(
            config('auth.providers.' . config('auth.guards.web.provider') . '.model')
        );
    }

    /**
     * 链接用户
     * @param \Illuminate\Foundation\Auth\User $user
     * @return bool
     */
    public function connect(\Illuminate\Foundation\Auth\User $user): bool
    {
        $this->user_id = $user->getAuthIdentifier();
        return $this->saveQuietly();
    }

    /**
     * 解除用户连接
     * @return bool
     */
    public function disconnect(): bool
    {
        $this->user_id = null;
        return $this->saveQuietly();
    }

    /**
     * Finds an account by open_id.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $openid
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOpenid($query, $openid)
    {
        return $query->where('open_id', $openid);
    }

    /**
     * Finds an account by union_id.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $unionid
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUnionid($query, $unionid)
    {
        return $query->where('union_id', $unionid);
    }

    /**
     * Finds an account by user_id.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param integer $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUserid($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Finds an account by provider.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Finds an account by open_id and provider.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $openid
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOpenidAndProvider($query, $openid, $provider)
    {
        return $query->where('open_id', $openid)->where('provider', $provider);
    }

    /**
     * Finds an account by union_id and provider.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $unionid
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUnionidAndProvider($query, $unionid, $provider)
    {
        return $query->where('union_id', $unionid)->where('provider', $provider);
    }

    /**
     * 生成用户名
     * @return string|null
     */
    public function generateUsername()
    {
        if (!empty($this->name)) {
            return $this->name;
        } else {
            return $this->nickname;
        }
    }

    /**
     * 获取用户
     * @param array $user
     * @return MiniProgramUser
     */
    public static function mapUserToObject(array $user): MiniProgramUser
    {
        //存在联合ID
        if (isset($user['union_id']) && !empty($user['union_id'])) {
            /** @var MiniProgramUser $unionUser */
            $unionUser = MiniProgramUser::byUnionIdAndProvider($user['union_id'], $user['provider'])->first();
            if ($unionUser != null && $unionUser->user_id) {
                $user['user_id'] = $unionUser->user_id;
            }
        }
        return MiniProgramUser::updateOrCreate([
            'open_id' => $user['open_id'], 'provider' => $user['provider']
        ], $user);
    }

    /**
     * 微信用户
     * @param array $user
     * @return MiniProgramUser
     */
    public static function mapWechatUserToObject(array $user): MiniProgramUser
    {
        return static::mapUserToObject([
            'provider' => static::PROVIDER_WECHAT,
            'open_id' => Arr::get($user, 'openId'),
            'union_id' => Arr::get($user, 'unionId'),
            'nickname' => Arr::get($user, 'nickname'),
            'name' => null,
            'email' => null,
            'avatar' => Arr::get($user, 'avatarUrl'),
            'data' => $user
        ]);
    }
}
