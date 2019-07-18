var config = {
    url: 'ws://192.168.2.159:9501'
};
var layer;
layui.use('layer', function() {
    layer = layui.layer;

    var webim = {
        data:{
            server: null,
            myInfo: null
        },
        init:function () {
            this.data.server =  new WebSocket(config.url);
            this.open();
            this.message();
            this.error();
            this.close();
        },
        open:function () {
            this.data.server.onopen = function(evt) {
                console.log('连接成功');
                console.log(evt);
                layer.msg('连接成功');
            }
        },
        message:function () {
            this.data.server.onmessage = function(evt) {
                var data = JSON.parse(evt.data);
                console.log(data);

                //渲染欢迎信息
                if(data.type == 'welcome-message'){

                    $('#webim .chat-list').append("<li class='welcome-message'>"+data.message+"</li>");
                    var userInfo  = data.userinfo;
                    if(userInfo.fd != webim.data.myInfo.fd){
                        var str = "<div userfd='"+userInfo['fd']+"' class='user-info'>" +
                            "<span class='useravatar'><img src='" +userInfo['avatar']+"' class='avatar'></span>" +
                            "<span class='username'>" + userInfo['name']+"</span>" +
                            "</div>";
                        $('#webim .user-list').append(str);
                    }

                }

                //渲染离开聊天室信息
                if(data.type == 'close-message'){
                    $('#webim .chat-list').append("<li class='close-message'>"+data.message+"</li>");
                    var leaveUserFd = data.userinfo.fd;
                    var userObj = $('.user-list .user-info[userfd="'+leaveUserFd+'"]');
                    userObj.remove();
                }

                //渲染用户列表
                if(data.type == "user-list"){
                    var str = '';
                    $.each(data['user-list'], function (k, v) {
                        var isme = '';
                        if(typeof v['isme'] != 'undefined' && v['isme'] == 1){
                            webim.data.myInfo = v;
                            isme = 'isme';
                        }
                        str += "<div userfd='"+v['fd']+"' class='user-info "+isme+"'>" +
                            "<span class='useravatar'><img src='" +v['avatar']+"' class='avatar'></span>" +
                            "<span class='username'>" + v['name']+"</span>" +
                            "</div>";
                    });
                    $('#webim .user-list').append(str);
                }

                //渲染聊天信息
                if(data.type == 'chat-message'){
                    var userInfo = data.userinfo;
                    var chatMessage =  "<div class='chat-message others-message'><div class='avatar' style='float: left'><img class='avatar' src='"+userInfo['avatar']+"'></div>"+
                        "<div class='message-container' style='margin-left: 5px;float: left'><span  class='messageSpan'>" +data.message+ "</span></div></div>" ;

                    $('#webim .chat-list').append(chatMessage);
                }

                scrolleToBottom();

            }
        },
        error:function () {
            this.data.server.onerror = function(evt) {
                layer.alert('出现了错误！');
            }
        },
        close:function () {
            this.data.server.onclose = function(evt) {
                layer.alert('不妙，链接断开了');
            }
        }
    };
    webim.init();

    function scrolleToBottom() {
        var scrollHeight = $('#webim .chat-list').prop("scrollHeight");
        $('#webim .chat-list').scrollTop(scrollHeight,200);
    }

    $('#webim .send').click(function () {
        var message = $('#chat-input').val();
        if(!message) return false;
        $('#chat-input').val('');
        var myInfo = webim.data.myInfo;
        try{
            webim.data.server.send(message);
            var chatMessage =  "<div class='chat-message mymessage'><div class='avatar' style='float: right'><img class='avatar' src='"+myInfo['avatar']+"'></div>"+
                "<div class='message-container' style='margin-right: 5px;float: right;text-align: right;'><span class='messageSpan' >" +message+ "</span></div></div>" ;

            $('#webim .chat-list').append(chatMessage);

            scrolleToBottom();
        }catch(error){
            layer.alert('出错啦，消息发送失败');
        }
    });

    $('#chat-input').bind('keypress', function (event) {
        if(event.keyCode == "13"){
            $('#webim .send').click();
        }
    });

});

