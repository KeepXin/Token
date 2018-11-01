<?php
/**
 * Created by PhpStorm.
 * User: keep-xin
 * Date: 2018/10/30
 * Time: 11:35
 */

namespace KeepXin\Token;

use KeepXin\Token\Exceptions\InvalidConfigException;
use KeepXin\Token\Exceptions\ErrorDbException;

class Token
{
    protected static $drive;
    protected static $table;
    protected static $access_token_expire;
    protected static $refresh_token_expire;

    /**
     * 读取参数
     * @throws InvalidConfigException
     */
    protected static function init()
    {
        $token = config('config.token');
        if (!$token) {
            throw new InvalidConfigException('缺少config.token参数配置');
        }
        if (!isset($token['driver'])) {
            throw new InvalidConfigException('缺少config.token.driver配置参数');
        }

        if ($token['driver'] != 'db') {
            throw new InvalidConfigException('不支持的config.token.driver参数，目前仅支持db');
        }

        self::$drive = $token['driver'];
        if (!isset($token['table'])) {
            throw new InvalidConfigException('缺少config.token.table配置参数');
        }
        self::$table = $token['table'];

        if (!isset($token['access_token_expire'])) {
            throw new InvalidConfigException('缺少config.token.access_token_expire配置参数');
        }
        self::$access_token_expire = $token['access_token_expire'];

        if (!isset($token['refresh_token_expire'])) {
            throw new InvalidConfigException('缺少config.token.refresh_token_expire配置参数');
        }
        self::$refresh_token_expire = $token['refresh_token_expire'];
    }

    /**
     * @param $app_id
     * @param $user_id
     * @param $platform
     * @return array
     * @throws ErrorDbException
     * @throws InvalidConfigException
     */
    public static function getToken($app_id, $user_id, $platform)
    {
        self::init();

        $access_token = self::generateToken();
        $refresh_token = self::generateToken();
        $access_secret = self::generateToken();

        $time = time();
        $access_token_expires = $time + self::$access_token_expire * 3600;
        $refresh_token_expires = $time + self::$refresh_token_expire * 3600;

        $token_param = [
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'access_secret' => $access_secret,
            'access_token_expires' => $access_token_expires,
            'refresh_token_expires' => $refresh_token_expires,
        ];

        if (self::$drive == 'db') {
            $token = \DB::table(self::$table)->where(['app_id' => $app_id, 'user_id' => $user_id, 'platform' => $platform])->first();
            if ($token) {
                $param['updated_at'] = $time;
                $param = array_merge($token_param, $param);

                $update = \DB::table(self::$table)->where(['id' => $token->id])->update($param);
                if (!$update) {
                    throw new ErrorDbException('数据库操作失败');
                }

            } else {

                $param['app_id'] = $app_id;
                $param['user_id'] = $user_id;
                $param['platform'] = $platform;
                $param['created_at'] = $time;
                $param['updated_at'] = $time;
                $param = array_merge($token_param, $param);
                $insert = \DB::table(self::$table)->insert($param);
                if (!$insert) {
                    throw new ErrorDbException('数据库操作失败');
                }

            }
        }
        return ['code' => 0, 'token' => $token_param];
    }

    /**
     * 刷新token
     * @param $refresh_token
     * @return array
     * @throws ErrorDbException
     * @throws InvalidConfigException
     */
    public static function updateToken($refresh_token)
    {
        self::init();

        if (self::$drive == 'db') {
            $token = \DB::table(self::$table)->where(['refresh_token' => $refresh_token])->first();

            if (!$token) {
                return ['code' => 400105, 'message' => 'refresh-token不存在'];
            }

            if ($token->refresh_token_expires < time()) {
                return ['code' => 400104, 'message' => 'refresh-token已经过期'];
            }

            $access_token = self::generateToken();
            $refresh_token = self::generateToken();
            $access_secret = self::generateToken();

            $time = time();
            $access_token_expires = $time + self::$access_token_expire * 3600;
            $refresh_token_expires = $time + self::$refresh_token_expire * 3600;

            $token_param = [
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'access_secret' => $access_secret,
                'access_token_expires' => $access_token_expires,
                'refresh_token_expires' => $refresh_token_expires,
            ];

            $param['updated_at'] = $time;
            $param = array_merge($token_param, $param);

            $update = \DB::table(self::$table)->where(['id' => $token->id])->update($param);
            if (!$update) {
                throw new ErrorDbException('数据库操作失败');
            }

            return ['code' => 0, 'token' => $token_param];
        }

    }

    /**
     * 删除token
     * @param $access_token
     * @return array
     * @throws ErrorDbException
     * @throws InvalidConfigException
     */
    public static function deleteToken($access_token)
    {
        self::init();

        if (self::$drive == 'db') {
            $token = \DB::table(self::$table)->where(['access_token' => $access_token])->delete();
            if (!$token) {
                throw new ErrorDbException('数据库操作失败');
            }

            return ['code' => 0];
        }

    }

    /**
     * 产生token
     * @param int $length
     * @return string
     */
    protected static function generateToken($length = 16)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return md5($randomString);
    }

}
