// 客服的id
var kf_id = 0;
var kf_name = '';
// 是否点击显示表情的标志
var flag = 1;
// 是否点击显示评价的标志
var flag2 = 1;
// 发送锁  标识
var sendLock = 0;
// 是否显示默认的聊天记录
var commChat = 1;
// 自动尝试连接
var reconnect = false;
// 时间句柄
var timeid = null;
// socekt 句柄
var socket = null;
// 需要重连
var needRec = 0;
// 点赞数
var star = 0;
// 点赞唯一标识
var uuid = 0;

// 连接服务器
if(config != undefined && config.socket != undefined){

    webSocket();
}

// 连接websocekt
function webSocket(){ 

	// 创建一个Socket实例
    socket = new WebSocket(config.socket);

    // 加锁
    lockInput();
    //showSystem({content: '连接中...'});
    document.getElementById('title').innerText = '连接中...';

    // 打开Socket
    socket.onopen = function(res) {
		// 如果是重连则关闭轮询  
        timeid && window.clearInterval(timeid); 
		if(reconnect){
			console.log('重连成功');
		}else{
			console.log('握手成功');
		}
  
        // 登录
        var login_data = '{"type":"userInit", "uid": "' + config.uid + '", "name" : "' + config.name +
		'", "avatar" : "' + config.avatar + '", "group" : ' + config.group + '}';
		socket.send(login_data);

        // 解锁
        unlockInput();
    };

    // 监听消息
    socket.onmessage = function(res) {
        var data = eval("("+res.data+")");
        switch(data['message_type']){
            // 服务端ping客户端
            case 'ping':
                socket.send('{"type":"ping"}');
                break;
            // 已经被分配了客服
            case 'connect':
                kf_id = data.data.kf_id;
                kf_name = data.data.kf_name;
                showSystem({content: '客服 ' + data.data.kf_name + ' 为您服务'});
                document.getElementById('title').innerHTML = '与 ' + kf_name + ' 交流中';
                if(1 == commChat){
                    getChatLog(1);
                }
                unlockInput();

                $.getJSON('/index/index/bindPraise', {kf_id: kf_id, uid: config.uid}, function (res) {
                    if(1 == res.code) {
                        uuid = res.data;
                    }
                });

                break;
            // 排队等待
            case 'wait':

                if('暂时没有客服上班,请稍后再咨询。' == data.data.content){
					document.getElementById("title").innerHTML = '客服不在请留言';
                    document.getElementById("chat-box").style.display = 'none';
                    document.getElementById("leave-box").style.display = 'block';
                    socket.close();
					needRec = 1;
					
                    break;
                }

                lockInput();
                document.getElementById('title').innerHTML = '请稍后再来';
                showSystem(data.data);
                break;
            // 监测聊天数据
            case 'chatMessage':
                showMsg(data.data);
                break;
            // 问候语
            case 'helloMessage':
                showMsg(data.data, 1);
                break;
            // 转接
            case 'relinkMessage':
                commChat = 2;
                document.getElementById('title').innerHTML = '正在转接中...';
                break;
        }
    };

    // 监听错误
    socket.onerror = function(err){
        showSystem({content: '连接失败'});
        document.getElementById('title').innerText = '连接失败';
    };
	
	// 当断开时进行判断
    socket.onclose = function(e){
        window.clearInterval(timeid);
        // 判断是否为苹果ios系统
        var isiOS = !!navigator.userAgent.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); // ios终端
        if(isiOS && 0 == needRec){
            reconnect = true;
            timeid = window.setInterval(webSocket, 3000);
        }
    }
}

// 图片 文件上传
layui.use(['upload', 'mobile'], function () {
    var upload = layui.upload;
    var mobile = layui.mobile,
        index = null,
        layer = mobile.layer;

    // 执行实例
    var uploadInstImg = upload.render({
        elem: '#up-image' // 绑定元素
        , accept: 'images'
        , exts: 'jpg|jpeg|png|gif'
        , url: '/index/upload/uploadImg' // 上传接口
        , before: function () {
            index = layer.open({
                type: 2
                ,content: '上传中'
            });
        }
        , done: function (res) {
            layer.close(index);

            sendMsg('img[' + res.data.src + ']');
            showBigPic();
        }
        , error: function () {
            // 请求异常回调
        }
    });

    $('.layui-upload-file').hide();
});

// 显示大图
function showBigPic() {

    $(".layui-whisper-photos").on('click', function () {
        var src = this.src;

        layer.open({
            type: 1
            ,title: ''
            ,content: '<img src="' + src + '" width="100%" height="100%">'
            ,anim: 'up'
            ,style: 'position:fixed; left:0; top:30%; width:100%; height:10%; border: none; -webkit-animation-duration: .5s; animation-duration: .5s;'
        });
    });
}

$(function(){
	
	// 修复IOS下输入法遮挡问题
	$('#msg').on('focus', function () {
		var target = this;

        setTimeout(function(){
            // 设置body的高度为可视高度+302
            // 302为原生键盘的高度
            document.getElementsByTagName('body')[0].style.height = (window.innerHeight + 500) + 'px';
            document.body.scrollTop = 500;
        }, 300);
	});

    $('#msg').on('blur', function () {
        document.getElementsByTagName('body')[0].style.height = window.innerHeight + 'px';
    });

    // 监听输入改变发送按钮
    $("#msg").bind('input porpertychange', function(){

        if($("#msg").val().length > 0){
            $(".layim-send").removeClass('layui-disabled');
        }else{
            $(".layim-send").addClass('layui-disabled');
        }
    });

    // 点击发送
    var sendObj = document.getElementById("send");
    sendObj.addEventListener('touchend', function(e) {
    	sendMsg();
    });

    // 点击表情
    $('#up-face').click(function(e){
        e.stopPropagation();

        if(2 == flag2){
            $("#appraise").hide();
            flag2 = 1;
        }

        if(1 == flag){
            showFaces();
            $('#face-box').show();
            flag = 2;
        }else{
            $('#face-box').hide();
            flag = 1;
        }
    });
	
    // 监听点击旁边关闭表情
    document.addEventListener("click",function(){
        if(2 == flag){
            document.getElementById('face-box').style.display = 'none';
            flag = 1;
        }
    });

    // 点赞
    $("#praise").click(function (e) {
        e.stopPropagation();

        if(2 == flag){
            $('#face-box').hide();
            flag = 1;
        }

        if(1 == flag2){
            var height = $("#chat-box").height() -  150;
            $("#appraise").css('top', height).show();
            flag2 = 2;
        }else {
            $("#appraise").hide();
            flag2 = 1;
        }
    });

    // 确认评价
    $('#do-praise').click(function(e) {
        e.stopPropagation();

        $.getJSON('/index/index/dopraise', {uuid: uuid, star: star}, function (res) {
            $("#appraise").hide();
            flag2 = 1;
            showSystem({content: '您给 ' + kf_name + ' 打出 ' + star + '星评价'});
        });
    })
});

// 发送消息
function sendMsg(sendMsg){

    if(1 == sendLock){
        return false;
    }

    var msg = (typeof(sendMsg) == 'undefined') ? $('#msg').val() : sendMsg;
	var cpMsg = msg;
    if('' == msg || 0 == cpMsg.trim().length){
        return false;
    }

    var _html = $("#chat-list").html();
    var time = getTime();
    var content = replaceContent(msg);

    _html += '<li class="layim-chat-system"><span>' + time + '</span></li>'
    _html += '<li class="layim-chat-li layim-chat-mine">';
    _html += '<div class="layim-chat-user">';
    _html += '<img src="' + config.avatar + '"><cite>我</cite></div>';
    _html += '<div class="layim-chat-text">' + content + ' </div></li>';

    $('#chat-list').html(_html);

    // 发送消息
    socket.send(JSON.stringify({
        type: 'chatMessage',
        data: {to_id: kf_id, to_name: kf_name, content: msg, from_name: config.name,
            from_id: config.uid, from_avatar: config.avatar}
    }));

    $('#msg').val('');
    $(".layim-send").addClass('layui-disabled');

    // 滚动条自动定位到最底端
    wordBottom();
}

// 消息声音提醒
function voice() {
    var audio = document.createElement("audio");
    audio.src = '/static/service/js/layui/css/modules/layim/voice/default.mp3';
    audio.play();
}

// 展示发送来的消息
function showMsg(info, flag){

    var _html = $("#chat-list").html();
    var content = replaceContent(info.content);

    _html += '<li class="layim-chat-system"><span>' + info.time + '</span></li>';
    _html += '<li class="layim-chat-li">';
    _html += '<div class="layim-chat-user">';
    _html += '<img src="' + info.avatar + '"><cite>' + info.name + '</cite></div>';
    _html += '<div class="layim-chat-text">' + content + '</div></li>';

    $('#chat-list').html(_html);

    voice();
    showBigPic();
    // 滚动条自动定位到最底端
    wordBottom();
}

// 获取时间
function getTime(){
    var myDate = new Date();
    var hour = myDate.getHours();
    var minute = myDate.getMinutes();
    if(hour < 10) hour = '0' + hour;
    if(minute < 10) minute = '0' + minute;

    return hour + ':' + minute;
}

// 缓存聊天数据 [本地存储策略]
function cacheChat(obj){
    if(typeof(Storage) !== "undefined"){
        localStorage.setItem(obj.key, JSON.stringify(obj.data));
    }
}

// 从本地缓存中，拿出数据
function getCache(key){
    return JSON.parse(localStorage.getItem(key));
}

// 对话框定位到最底端
function wordBottom() {
    // 滚动条自动定位到最底端
    var box = $(".layim-chat-main");
    box.scrollTop(box[0].scrollHeight);
}

// 锁住输入框
function lockInput(){
    sendLock = 1;
    document.getElementById('msg').setAttribute('readonly', 'readonly');
}

// 解锁输入框
function unlockInput(){
    sendLock = 0;
    document.getElementById('msg').removeAttribute('readonly');
}

// 展示表情数据
function showFaces(){
    var alt = getFacesIcon();
    var _html = '<ul class="layui-layim-face">';
    var len = alt.length;
    for(var index = 0; index < len; index++){
        _html += '<li title="' + alt[index] + '" onclick="checkFace(this)"><img src="/static/customer/images/face/'+ index + '.gif" /></li>';
    }
    _html += '</ul>';

    document.getElementById('face-box').innerHTML = _html;
}

// 选择表情
function checkFace(obj){
    var msg = document.getElementById('msg').value;
    document.getElementById('msg').value = 	msg + ' face' + obj.title + ' ';
    document.getElementById('face-box').style.display = 'none';
    $(".layim-send").removeClass('layui-disabled');
    flag = 1;
}

// 系统消息
function showSystem(info){

    $("#chat-list").append('<li class="layim-chat-system"><span>' + info.content + '</span></li>');
}

// 获取聊天记录
function getChatLog(page, flag) {

    $.getJSON('/index/index/getChatLog', {uid: config.uid, kf_id: kf_id, page: page}, function(res){
        if(1 == res.code && res.data.length > 0){

            if(res.msg == res.total){
                var _html = '<div class="layui-flow-more">没有更多了</div>';
            }else{
                var _html = '<div class="layui-flow-more"><a href="javascript:;" data-page="' + parseInt(res.msg + 1)
                    + '" onclick="getMore(this)"><cite>更多记录</cite></a></div>';
            }

            var len = res.data.length;

            for(var i = 0; i < len; i++){
                var item = res.data[len - i - 1];

                if('mine' == item.type){

                    _html += '<li class="layim-chat-system"><span>' + item.time_line + '</span></li>'
                    _html += '<li class="layim-chat-li layim-chat-mine">';
                    _html += '<div class="layim-chat-user">';
                    _html += '<img src="' + item.from_avatar + '"><cite>我</cite></div>';
                    _html += '<div class="layim-chat-text">' + replaceContent(item.content) + ' </div></li>';

                }else {

                    _html += '<li class="layim-chat-system"><span>' + item.time_line + '</span></li>';
                    _html += '<li class="layim-chat-li">';
                    _html += '<div class="layim-chat-user">';
                    _html += '<img src="' + item.from_avatar + '"><cite>' + item.from_name + '</cite></div>';
                    _html += '<div class="layim-chat-text">' + replaceContent(item.content) + '</div></li>';
                }
            }

            if(typeof flag == 'undefined'){
                $('#chat-list').html(_html);
                wordBottom();
            }else{
                $('#chat-list').prepend(_html);
            }
            showBigPic();
        }
    });
}

// 获取更多
function getMore(obj){
    $(obj).remove();

    var page = $(obj).attr('data-page');
    getChatLog(page, 1);
}

// 退出
function loginOut(){
    socket.close();
    window.history.go(-1);
}

layui.use(['rate'], function () {
    var rate = layui.rate;

    rate.render({
        elem: '#star'
        ,choose: function(value){
            star = value;
        }
    });
});

// 留言
layui.use(['form', 'jquery'], function(){
    var form = layui.form;
    var $ = layui.jquery;

    $('#appraise').hide();
    flag2 = 1;

    // 监听提交
    form.on('submit(formLeave)', function(data){
        $.post('/index/index/leave', data.field, function(res){

            $("input").val("");
            $("textarea").val("");
			loginOut();
        });

        return false;
    });
});