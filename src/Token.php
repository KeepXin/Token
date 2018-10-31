<?php
/**
 * Created by PhpStorm.
 * User: yanan.shao
 * Date: 2018/10/30
 * Time: 11:35
 */

namespace Clz\Token;

class Token
{
    protected static $drive;
    protected static $table;
    protected static $access_token_expire;
    protected static $refresh_token_expire;

    /**
     * 读取参数
     * @return array
     */
    protected static function init()
    {
        $token = config('config.token');
        if (!$token) {
            return [
                'code' => 1,
                'message' => '缺少config.token配置参数'
            ];
        }
        if (!isset($token['driver'])) {
            return [
                'code' => 2,
                'message' => '缺少config.token.driver配置参数'
            ];
        }

        if ($token['driver'] != 'db') {
            return [
                'code' => 3,
                'message' => '不支持的config.token.driver参数，目前仅支持db'
            ];
        }

        self::$drive = $token['driver'];
        if (!isset($token['table'])) {
            return [
                'code' => 3,
                'message' => '缺少config.token.table配置参数'
            ];
        }
        self::$table = $token['table'];

        if (!isset($token['access_token_expire'])) {
            return [
                'code' => 4,
                'message' => '缺少config.token.access_token_expire配置参数'
            ];
        }
        self::$access_token_expire = $token['access_token_expire'];

        if (!isset($token['refresh_token_expire'])) {
            return [
                'code' => 5,
                'message' => '缺少config.token.refresh_token_expire配置参数'
            ];
        }
        self::$refresh_token_expire = $token['refresh_token_expire'];
    }

    /**
     * 生成token
     * @param $app_id
     * @param $user_id
     * @param $platform
     * @return array
     */
    public static function getToken($app_id, $user_id, $platform)
    {
        $result = self::init();
        if ($result['code']) {
            return $result;
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

        if (self::$drive == 'db') {
            $token = \DB::table(self::$table)->where(['app_id' => $app_id, 'user_id' => $user_id, 'platform' => $platform])->first();
            if ($token) {
                $param['updated_at'] = $time;
                $param = array_merge($token_param, $param);

                $update = \DB::table(self::$table)->where(['id' => $token->id])->update($param);
                if (!$update) {
                    return [
                        'code' => 2,
                        'message' => 'token修改失败'
                    ];
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
                    return [
                        'code' => 3,
                        'message' => 'token插入失败'
                    ];
                }

            }
        }
        return ['code'=>0,'token'=>$token_param];
    }

    /**
     * 刷新token
     * @param $refresh_token
     * @return array
     */
    public static function updateToken($refresh_token)
    {
        $result = self::init();
        if ($result['code']) {
            return $result;
        }

        if (self::$drive == 'db') {
            $token = \DB::table(self::$table)->where(['refresh_token' => $refresh_token])->first();

            if (!$token) {
                return ['code' => 1, 'message' => 'token不存在'];
            }

            if ($token->refresh_token_expires < time()) {
                return ['code' => 2, 'message' => 'token已经过期'];
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
                return [
                    'code' => 4,
                    'message' => 'token更新失败'
                ];
            }

            return ['code' => 0, 'token' => $token_param];
        }

    }

    /**
     * 删除token
     * @param $access_token
     * @return array
     */
    public static function deleteToken($access_token)
    {
        $result = self::init();
        if ($result['code']) {
            return $result;
        }

        if (self::$drive == 'db') {
            $token = \DB::table(self::$table)->where(['access_token' => $access_token])->delete();
            if(!$token){
                return ['code'=>1,'message'=>'删除失败'];
            }

            return ['code' => 0];
        }

    }

    /**
     * 产生token
     * @param int $length
     * @return string
     */
    static function  generateToken($length = 16) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return md5($randomString);
    }

}
