// let wsurl = "ws://192.168.10.10:9501";
let httpUrl = 'http://livesteam.com';
let wsUrl = 'ws://livesteam.wss';
let layer;
let curRoomId = 0;

$('#login').click(function () {
    let username = $('#username').val();
    let password = $('#password').val();
    $.ajax({
        type: "post",
        url: httpUrl + "/register",
        data: {
            username: username,
            password: password
        },
        success: function (result) {
            if (result.code !== 200) {
                layer.alert(result.message);
            } else {
                $.cookie('uid', result.data.uid);
                $.cookie('token', result.data.token);
                $.cookie('userInfo', JSON.stringify(result.data.userInfo));
                $('#register-form').hide();
                window.location.reload();
            }
        }
    });
});
layui.use('layer', function () {
    layer = layui.layer;
    if (!checkRegister()) {
        $('#register-form').show();
        return
    }

    function checkRegister() {
        let uid = $.cookie('uid');
        return uid;
    }

    function init() {
        let userInfo = JSON.parse(getCookie('userInfo'));
        $('#welcomeMsg').html("欢迎您:" + "<span class='welcome-username'>" + userInfo['username'] + "</span>");
    }

    init();
    loadUserRoom();
    let wsurl = wsUrl + "?uid=" + getCookie('uid') + '&token=' + getCookie('token') + '&roomId=' + curRoomId;

    var webim = {
        data: {
            server: null,
            myInfo: null
        },
        init: function () {
            this.data.server = new WebSocket(wsurl);
            this.open();
            this.message();
            this.error();
            this.close();
        },
        open: function () {
            this.data.server.onopen = function (evt) {
                console.log('连接成功');
                console.log(evt);
                console.log(webim.data.server.readyState);
                layer.msg('连接成功');
            }
        },
        message: function () {
            this.data.server.onmessage = function (evt) {
                var data = JSON.parse(evt.data);
                if (data.type == 1) { //普通聊天消息
                    if (curRoomId == data['data']['roomId']) {
                        appendChatMsg(data['data']['msgSender'], data['message'], true);
                        scrolleToBottom();
                    } else if ($(".room-info[dataroomid='" + data['data']['roomId'] + "']").length > 0) {
                        addUnreadMark(data['data']['roomId']);
                    } else {
                        let roomInfo = getRoomInfo(data['data']['roomId']);
                        let avatars = [];
                        let uids = [];
                        $.each(roomInfo['coverUsers'], function (k, v) {
                            avatars.push(v['avatar']);
                            uids.push(v['id']);
                        });

                        if (!curRoomId) {
                            //如果当前没有房间
                            curRoomId = data['data']['roomId'];
                            appendRoomList(roomInfo['roomInfo']['id'], makeAvatar(avatars, uids, roomInfo['unReadMsgCount']), roomInfo['roomInfo'].name, true);
                            appendChatMsg(data['data']['msgSender'], data['message'], true);
                        } else {
                            appendRoomList(roomInfo['roomInfo']['id'], makeAvatar(avatars, uids, roomInfo['unReadMsgCount']), roomInfo['roomInfo'].name, false);
                        }
                    }
                }
            }
        },
        error: function () {
            this.data.server.onerror = function (evt) {
                layer.alert('出现了错误！');
            }
        },
        close: function () {
            this.data.server.onclose = function (evt) {
                console.log(evt);
                layer.alert('不妙，链接断开了');
            }
        }
    };

    webim.init();

    function getCookie(key) {
        return $.cookie(key)
    }

    function getRoomInfo(roomId) {
        let rst = {};
        $.ajax({
            url: httpUrl + "/roomInfo",
            method: 'post',
            dataType: 'json',
            async: false,
            data: {
                uid: getCookie('uid'),
                token: getCookie('token'),
                roomId: roomId
            },
        }).done(function (res) {
            rst = res.data;
            // console.log("ajax success");
        }).fail(function (err) {
            // console.log("ajax error");
        }).always(function () {
            // console.log("ajax complete");
        });
        return rst;

    }

    function makeAvatar(avatars, uids, unreadMsgCount = 0) {
        let str = '<div class=\'userAvatar\'>';
        let len = avatars.length;
        let curUid = getCookie('uid');
        if (len == 2) {
            $.each(avatars, function (k, v) {
                if (uids[k] == curUid) {
                    avatars.splice(k, 1);
                    uids.splice(k, 1);
                }
            });
        }
        let arr = [];
        console.log("len="+len);
        switch (len) {
            case 1:
            case 2:
                arr = [
                    [[60, 60]]
                ];
                break;
            case 3:
                arr = [
                    [[30, 30]],
                    [[30, 30], [30, 30]]
                ];
                break;
            case 4:
                arr = [
                    [[30, 30], [30, 30]],
                    [[30, 30], [30, 30]]
                ];
                break;
            case 5:
                arr = [
                    [[20, 30], [20, 30]],
                    [[20, 30], [20, 30], [20, 30]],
                ];
                break;
            case 6:
                arr = [
                    [[20, 30], [20, 30], [20, 30]],
                    [[20, 30], [20, 30], [20, 30]]
                ];
                break;
            case 7:
                arr = [
                    [[20, 20]],
                    [[20, 20], [20, 20], [20, 20]],
                    [[20, 20], [20, 20], [20, 20]],
                ];
                break;
            case 8:
                arr = [
                    [[20, 20], [20, 20]],
                    [[20, 20], [20, 20], [20, 20]],
                    [[20, 20], [20, 20], [20, 20]],
                ];
                break;
            case 9:
                arr = [
                    [[20, 20], [20, 20], [20, 20]],
                    [[20, 20], [20, 20], [20, 20]],
                    [[20, 20], [20, 20], [20, 20]],
                ];
                break;
        }
        let num = 0;
        for (let i = 0; i < arr.length; i++) {
            str += "<div style='text-align: center'>";
            for (let j = 0; j < arr[i].length; j++) {
                if (num == len) {
                    break;
                }
                str += "<img src='" + avatars[num] + "' style='max-width: " + arr[i][j][0] + "px;max-height: " + arr[i][j][1] + "px'  class='avatar' dataUid='" + uids[num] + "'>";
                num++
            }
            str += "</div>";
        }

        str += makeUnreadMark(unreadMsgCount);
        str += "</div>";
        return str;
    }

    function makeUnreadMark(unreadMsgCount) {
        let str = '';
        if (unreadMsgCount > 0) {
            str += "<div class='unreadMsgCount unreadMsgCount-more'>" + unreadMsgCount + "</div>";
        }
        return str;
    }

    function addUnreadMark(roomId) {
        let obj = $(".room-info[dataroomid='" + roomId + "']");
        let findMark = obj.find('.unreadMsgCount');
        let unreadCount = 1;
        if (findMark.length > 0) {
            unreadCount = parseInt(findMark.text()) + 1;
        }
        obj.find('.unreadMsgCount').remove();
        obj.find('.userAvatar').append(makeUnreadMark(unreadCount));
    }

    function appendRoomList(roomId, makeAvatarStr, name, selected = false) {
        if (selected) {
            $('.room-info').removeClass('selected-room');
        }
        let className = selected ? 'selected-room' : '';
        let str = "<div  class='room-info " + className + "'   dataRoomId='" + roomId + "'>" +
            makeAvatarStr +
            "<div class='username'>" + name + "</div>" +
            "</div>";
        $('#webim .room-list').append(str);
    }

    function clearChatContainer() {
        $('#webim .chat-list').html('');
    }

    function appendChatMsg(userInfo, content, toBottom = false) {
        console.log('tobottom=' + toBottom);
        let className = userInfo['id'] == getCookie('uid') ? 'my-message' : 'others-message';

        let imgStr = "<div class='avatar-div'  ><img class='avatar' src='" + userInfo['avatar'] + "'></div>";
        let chatMessage = "<div class='chat-message " + className + "'>" + imgStr +
            "<div class='message-container' ><span  class='messageSpan'>" + content + "</span></div></div>";
        if (toBottom) {
            $('#webim .chat-list').append(chatMessage);
        } else {
            $('#webim .chat-list').prepend(chatMessage);
        }
    }

    function loadUserRoom() {
        $.ajax({
            type: "post",
            url: httpUrl + "/userRoomList",
            data: {
                uid: getCookie('uid'),
                token: getCookie('token'),
            },
            success: function (result) {
                if (result.code !== 200) {
                    layer.alert(result.message);
                } else {
                    let curUid = getCookie('uid');
                    $.each(result['data'], function (key, value) {

                        let avatars = [];
                        let uids = [];
                        $.each(value['coverUsers'], function (k, v) {
                            avatars.push(v['avatar']);
                            uids.push(v['id'])
                        });
                        appendRoomList(value['roomInfo']['id'], makeAvatar(avatars, uids, value['unReadMsgCount']), value['roomInfo'].name, key == 0);
                    });
                    $('.room-list').children().eq(0).click();
                    scrolleToBottom();
                }
            }
        });
    }

    function scrolleToBottom() {
        var scrollHeight = $('#webim .chat-list').prop("scrollHeight");
        $('#webim .chat-list').scrollTop(scrollHeight, 200);
    }

    $('#webim .send').click(function () {
        let message = $('#chat-input').val();
        if (!message) return false;
        $('#chat-input').val('');

        try {
            webim.data.server.send(makeSendMsg(1, {'chatMsg': message, 'roomId': curRoomId}));
            appendChatMsg(JSON.parse(getCookie('userInfo')), message, true);
            scrolleToBottom();
        } catch (error) {
            console.log(error);
            layer.alert('出错啦，消息发送失败');
        }
    });

    $('#chat-input').bind('keypress', function (event) {
        if (event.keyCode == "13") {
            $('#webim .send').click();
        }
    });
    let curChatListPage = 1;
    let hasNextPage = true;
    $('.room-list').on('click', '.room-info', function () {
        scrolleToBottom();
        console.log('点击加载连天记录');
        let roomId = $(this).attr('dataroomid');
        $(this).addClass('selected-room').siblings('.selected-room').removeClass('selected-room');
        $(this).children().find('.unreadMsgCount').remove();
        curRoomId = roomId;

        curChatListPage = 1;
        hasNextPage = true;
        getRoomChatMsg(roomId);
    });


    $("#chatListContainer .chat-list").scroll(function () {
        if ($(this).scrollTop() == 0 && hasNextPage) {
            curChatListPage += 1;
            console.log('滑动加载连天记录' + curChatListPage);
            if (false === getRoomChatMsg(curRoomId, curChatListPage, 20, false)) {
                curChatListPage -= 1;
            }
        }
    });
    let loading = false;

    function getRoomChatMsg(roomId, page = 1, size = 20, clearContent = true) {
        if (loading) {
            return false;
        }
        loading = true;
        if (clearContent) {
            clearChatContainer();
        }
        //loading层
        let index = layer.load(1, {
            shade: [0.1, '#fff'] //0.1透明度的白色背景
        });
        curRoomId = roomId;
        // console.log("当前roomId="+roomId+' page='+page+' size='+size+' clearContent='+clearContent)
        $.ajax({
            type: "post",
            url: httpUrl + "/roomChatMsgList",
            data: {
                uid: getCookie('uid'),
                token: getCookie('token'),
                page: page,
                roomId: roomId,
                size: size
            },
            success: function (result) {
                if (result.code !== 200) {
                    layer.alert(result.message);
                    hasNextPage = false;
                } else {
                    $.each(result['data'], function (key, value) {
                        appendChatMsg(value['userInfo'], value['content']);
                    });
                    if (result['data'].length <= 0 || result['data'].length < size) {
                        hasNextPage = false;
                    }
                    if (page == 1) {
                        scrolleToBottom();
                    }
                }
                layer.close(index);
                loading = false;
            }
        });
    }

    function getFriendList() {
        $.ajax({
            type: "post",
            url: httpUrl + "/friendList",
            data: {
                uid: getCookie('uid'),
                token: getCookie('token')
            },
            success: function (result) {
                if (result.code !== 200) {
                    layer.alert(result.message);
                } else {
                    let curUid = getCookie('uid');
                    $.each(result['data'], function (k, v) {
                        if (v.id != curUid) {
                            $('#friendListContainer').append("<div class='user'>" +
                                "<input type='checkbox' class='form-control checkbox' name='checkUsers' value='" + v.id + "'>" +
                                "<img src='" + v.avatar + "' alt='' class='avatar'> "
                                + "<div class='name'>" + v.username + "</div>" +
                                "</div>")
                        }
                    })
                }
            }
        });
    }


    $('#registerNewRoom').click(function () {
        $('#friendListContainer').html('');
        getFriendList();
        $('#friendListContent').show();
    });
    $('#checkFriend').click(function () {
        let objs = $("input[name='checkUsers']:checked");
        if (!objs.length) {
            $('#friendListContent').hide();
            return
        }
        let ids = [getCookie('uid')];
        $.each(objs, function (k, v) {
            ids.push(v.value);
        });
        ids = ids.join(',');
        $.ajax({
            type: "post",
            url: httpUrl + "/createRoom",
            data: {
                uid: getCookie('uid'),
                token: getCookie('token'),
                userIds: ids
            },
            success: function (result) {
                if (result.code !== 200) {
                    layer.alert(result.message);
                } else {
                    if (result['data']['isNewCreateRoom']) {
                        let avatars = [];
                        let uids = [];
                        $.each(result['data']['coverUsers'], function (k, v) {
                            avatars.push(v['avatar']);
                            uids.push(v['id']);
                        });
                        appendRoomList(result['data']['roomInfo']['id'], makeAvatar(avatars, uids, result['data']['unReadMsgCount']), result['data']['roomInfo'].name, true);
                        selectedRoom(result['data']['roomInfo']['id']);
                    } else {
                        let obj = $(".room-info[dataroomid='" + result['data']['roomInfo']['id'] + "']");
                        obj.click();
                    }
                }
                $('#friendListContent').hide();
            }
        });
    });

    function selectedRoom(roomId) {
        $(".room-info[dataroomid='" + roomId + "']").click();
        curRoomId = roomId;
    }

    function makeSendMsg(type, data) {
        return JSON.stringify({
            'type': type,
            'userInfo': getCookie('userInfo'),
            'data': data
        });
    }

    $('.close-btn').click(function () {
        $(this).parents().find('.close-parent').hide();
    })
});

