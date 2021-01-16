webim

PHP + lumen + Swoole 开发的多房间聊天室demo,功能包括登录、注册，创建房间，获取房间聊天记录，获取朋友列表等。

 
环境要求

    PHP >= 7.0
    Swoole
    
环境初始化

    1、进入  webim/livesteam/  打开env文件，自己修改redis，和数据库配置
    2、执行  composer install  安装必要的包
    3、执行  php artisan migrate  进行数据迁移
    4、配置自己的访问域名，在 webim/livesteam/storage/nginx 里有我自己的 域名配置，可以参考
    5、将  webim/public/js/webim.js 的前两行变量配置 httpUrl 和 wsUrl修改成自己的访问链接
    
    
       
启动 websocket

    进入  webim/livesteam/  执行命令 php artisan startChatServer


浏览器访问 http://127.0.0.1/webim/public  即可进入聊天
![Image text](https://github.com/baijingqi/webim/blob/master/demo.png)


