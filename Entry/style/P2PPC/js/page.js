	/* 导航效果 */
	$('.nav li a').mouseover(function(){		//鼠标经过
		$(this).find('span').css("width",'68px');
	});
	$('.nav li a').mouseout(function(){			//鼠标离开
		if ($(this).parents().attr('class') != 'active') {  //判断是不是 选中页面；
			$(this).find('span').css("width",'0px');   
		}
	})	
	jQuery(".slideTxtBox").slide({trigger:"click",pnLoop:false});