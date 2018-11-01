<h1 align="center"> token </h1>

<p align="center"> A Token SDK.</p>


## Installing

```shell
$ composer require keepxin/token -vvv
```

## Usage
```shell
CREATE TABLE `api_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` int(11) NOT NULL COMMENT 'app_id',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `access_token` char(32) COLLATE utf8_unicode_ci NOT NULL COMMENT 'access_token值',
  `access_token_expires` int(11) NOT NULL COMMENT 'access_token有效期',
  `refresh_token` char(32) COLLATE utf8_unicode_ci NOT NULL COMMENT 'refresh_token值',
  `refresh_token_expires` int(11) NOT NULL COMMENT 'refresh_token有效期',
  `created_at` int(11) NOT NULL COMMENT '创建时间',
  `updated_at` int(11) NOT NULL COMMENT '修改时间',
  `platform` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '平台参数',
  `access_secret` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT '用户访问加密秘钥',
  PRIMARY KEY (`id`),
  KEY `api_token_user_id_index` (`user_id`),
  KEY `api_token_access_token_index` (`access_token`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```
```shell
 try{
            $result = Token::getToken(1, 1, 'pc');
        }catch (\Exception $exception){
            $message = $exception->getMessage();
            if ($exception instanceof \KeepXin\Token\Exceptions\InvalidConfigException) {
                $message = '参数配置错误'.$message;
            } else if ($exception instanceof \KeepXin\Token\Exceptions\ErrorDbException) {
                $message = '数据库操作失败'.$message;
            }
            dd($message);
        }
```
## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/keepxin/token/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/keepxin/token/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT
