jQuery(function($)  {
var debug = 1,//浏览器控制台开关
	ws = false;
function connect() {
	if( ws ) return false;
	// $('div').eq(3).toggle();

	//连接服务器
	ws = new WebSocket("ws://"+document.domain+":8059");
	ws.onopen = function(e) {
		var data = JSON.stringify({
			'cmd' : 'login',
		});
		ws.send(data);
		user_join(time,username);
		// $('div').toggle();
		if( debug ) console.log("connected  to  "  +  "ws://"+document.domain+":8059");
	};

	//接收服务器消息
	ws.onmessage = function (e) {
		var  data  =  JSON.parse(e.data);
		switch(data.cmd)  {
			case  'logout'://离开
				user_leave(data.id,data.username);
				break;
			case  'login'://加入
				user_join(data.id,data.username);
				break;
			case  'msg'://信息
				var img = user_img(data.id);
				send_msg(data.username,img,data.msg,true);
				new_msg();
				break;
		}
		if( debug ) console.log("message  received:  "  +  e.data);
	};

	//服务器断开连接
	ws.onclose = function(e){
		if( debug ) console.log("connection  closed  ("  +  e.code  +  ")");
	};
}
connect();

var user_template = null;

$('#send').click(function(){
	var  msg  =  $('#msg-box').val();
	if(  msg  !=  '') {
		var data = JSON.stringify({
			'cmd' : 'msg',
			'msg' : msg
		});
		ws.send(data);
		var img = user_img(time);
		send_msg(username,img,msg,true);
	}
});
  
$('.chat-message  input').keypress(function(e){
	if(e.which  ==  13)  {
		$('#send').click();
	}
});

var  i  =  0;
	msg = null;
function  send_msg(name,img,msg,clear)  {
	i  =  i  +  1;
	var    inner  =  $('#chat-messages-inner');
	var  time  =  new  Date();
	var  hours  =  time.getHours();
	var  minutes  =  time.getMinutes();
	if(hours  <  10)  hours  =  '0'  +  hours;
	if(minutes  <  10)  minutes  =  '0'  +  minutes;
	var  id  =  'msg-'+i;
	var  idname  =  name.replace('  ','-').toLowerCase();
	inner.append('<p  id="'+id+'"  class="user-'+idname+'">'
	                                +'<span  class="msg-block"><img  src="'+img+'"  alt=""  /><strong>'+name+'</strong>  <span  class="time">-  '+hours+':'+minutes+'</span>'
	                                +'<span  class="msg">'+msg+'</span></span></p>');
	$('#'+id).hide().fadeIn(800);
	if(clear)  {
		$('.chat-message  input').val('').focus();
	}
	$('#chat-messages').animate({  scrollTop:  inner.height()  },1000);
}
function user_join(userid,name) {
	user_template  =  '<li  id="user_'+userid+'"  class="online"><a  href="#"><img  alt=""  src="'+user_img(userid)+'"  /><span>'+name+'</span></a></li>';
	$('#user_list').append(user_template);
	var	inner  =  $('#chat-messages-inner'),
		id  =  'msg-' + ++i;
	inner.append('<p  class="online"  id="'+id+'"><span>用户  '+name+'  加入聊天室</span></p>');
	$('#'+id).hide().fadeIn(800);
}
function user_leave(userid,name)  {
	$('#user_'+userid).addClass('offline').delay(1000).slideUp(800,function(){
		$(this).remove();
	});
	var	inner  =  $('#chat-messages-inner'),
		id  =  'msg-' + ++i;
	inner.append('<p  class="offline"  id="'+id+'"><span>用户  '+name+'  离开聊天室</span></p>');
	$('#'+id).hide().fadeIn(800);
}
function user_img(userid) {
	userid = userid % 5 + 1;
	return '/static/images/av'+userid+'.jpg';
}
});

function mlog(obj){ 
	var description = ""; 
		for(var i in obj) {   
		var property = obj[i];   
		description += i + " = " + property + "\n";  
	}   
	console.log(description); 
}