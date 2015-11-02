var flag = false,
	title = document.title,
	blur = false;
$(window).blur(function() {
	blur = true;
});
$(window).focus(function() {
	blur = false;
});
function new_msg() {
	//当窗口效果为最小化，或者没焦点状态下才闪动
	if( blur ) {
		var ftitle = function () {
			if(flag){
				flag = false;
				document.title = '【新消息】' + title;
			}else{
				flag = true;
				document.title = '【　　　】' + title;
			}
			setTimeout(new_msg,1000);
		}
		ftitle();
	} else {
		document.title = title;//窗口没有消息的时候默认的title内容
		clearTimeout();
	}
}