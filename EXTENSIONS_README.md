基于MotongAdmin扩展模块，可以是公共功能，也可以是业务功能，可以解决系统太复杂，模块都耦合到一起的问题
开发中的第三方库建议放置在extensions目录下，先创建组件的git库，然后克隆到这个库的目录下，然后在外层主工程的.gitignore文件中明确忽略这个目录即可

创建组件库的方法:

1、先创建扩展模块目录，如 oauth

2、进入这个目录执行创建命令：
composer create-project hyperf/component-creator your_component "2.2.*"

3、完成创建后，通过composer.json配置链接到本地目录

{
    "require": {
        "your_component/your_component": "dev-master"
    },
    "repositories": {
        "your_component": {
            "type": "path",
            "url": "/data/project/extensions/oauth/MotongOAuth" //这个路径是容器映射后的目录，具体看第四步
        }
    }
}

4、本地开发启动容器：

注意：需要将工作目录和前端目录进行映射

docker run -d --name motong-server \
   -v /Users/zyvincent/Desktop/iCodeFutureWorkSpace/MotongWorkSpace/Motong-Server:/data/project \
   -v /Users/zyvincent/Desktop/iCodeFutureWorkSpace/MotongWorkSpace/MotongAdminWeb:/data/admin-web \
   -v /Users/zyvincent/Desktop/iCodeFutureWorkSpace/MotongWorkSpace/MotongUniapp:/data/uniapp \
   -p 9666:9666 -it \
   --privileged -u root \
   --entrypoint /bin/sh \
   hyperf/hyperf:8.0-alpine-v3.12-swoole

5、执行命令，composer update -o，建立依赖


### 扩展模块开发规范

1、不要与系统模块耦合，给模块创建独立的菜单目录
2、管理端接口，第一级别前缀必须使用admin，否则无法实现权限校验
3、每个模块，菜单创建完毕后，必须将管理端接口与菜单进行有效关联
4、模块卸载应该原则上将模块产生的数据都清理
5、创建InstallCommand和UninstallCommand用于安装模块和卸载模块
6、对外非管理端接口，第一级别前缀建议使用模块特有命名空间，如MotongOAuth，采用motong为第一级别命名空间，motong.oauth.loginByCode
7、前端文件放置在assets目录下，采用的目录结构为需要复制到前端工程的真实相对目录路径