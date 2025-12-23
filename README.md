# MotongServer

###项目背景

1. 基于 zyprosoft/hyperf-common 建立的脚手架，用于快速生成一个支持 zgw 协议的后台开发项目模板
2. MotongAdmin 脚手架的后台服务项目框架

###ZGW 协议开发

####

内部库

####一、使用 motong/motong-server 项目来创建脚手架

1. composer create-project motong/motong-server
2. 完成后执行 composer install

####二、初始化配置
1、复制对应的配置
cp .env.sample .env

####ZGW 协议接口开发
三段式接口名：大模块名.控制器.方法
使用 AutoController(prefix="大模块名/控制器")进行注解之后，
按照 ZGW 协议请求便可自动调用到对应的方法
如下示范:ZgwController 下使用 AutoController(prefix="/common/zgw")进行注解之后便可
请求到 sayHello 方法

```php
curl -d'{
    "version":"1.0",
    "seqId":"xxxxx",
    "timestamp":1601017327,
    "eventId":1601017327,
    "caller":"test",
    "interface":{
        "name":"common.zgw.sayHello",
        "param":{}
    }
}' http://127.0.0.1:9506
```

####普通协议开发可直接按照想要的路径做 AutoController 注解即可

####根据需求继承需要鉴权和不需要鉴权的 Request
AdminRequest:需要管理员身份的请求
AuthedRequest:需要登陆身份的请求

####zgw 协议请求内容防篡改
签名算法:
在 hyperf-common.php 配置好 appId 和 appSecret
第一步:生成当前时间戳 timestamp 和随机字符串 nonce
第二步:取出协议中的 interface.name 和 param, php eg. `$name = $reqBody['interface']['name']`;
第三步:将第一步取出的参数按照如下加入到 param, php eg. `$param['interfaceName'] = $name`;
第四步:将第二步的 param 参数按照首字母升序
第五步:将第四部参数数组 json 编码后进行 md5 编码得到参数字符串 paramString,注意这里 json 编码不要主动编码为 Unicode,不转义/字符
第六步:按照下面的格式拼接参数:
appId=$appId&appSecret=$appSecret&nonce=$nonce&timestamp=$timestamp&$paramString;
第七步:用 appSecret 和第六步字符串采用 sha256 算法算出签名
第八步:将得到的签名使用参数名 signature 加入到请求协议的外层即可

重点:如果是接口带文件上传，需要将上述得到的 auth 和 interface 字段进行 json 编码,后端会在获取到请求的时候自动解码

参考请求包

```php
curl -d'{
    "auth":{
       "timestamp":1601017327,
       "nonce":"1601017327",
       "appId":"test",
       "signature":"xxasdfsfdsfffg"
    },
    "version":"1.0",
    "seqId":"xxxxx",
    "timestamp":1601017327,
    "eventId":1601017327,
    "caller":"test",
    "interface":{
        "name":"common.zgw.sayHello",
        "param":{}
    }
}' http://127.0.0.1:9506
```

# 如果忘记超级管理员密码，可以使用命令修改

php bin/hyperf.php admin:seed --reset --password admin123

本地开发启动容器：

docker run -d --name motong-admin-server \
 -v /Users/zyvincent/Desktop/iCodeFutureWorkSpace/MotongAdminWorkSpace/MotongAdminServer:/data/project \
 -p 9666:9666 -it \
 --privileged -u root \
 --entrypoint /bin/sh \
 hyperf/hyperf:8.0-alpine-v3.12-swoole
