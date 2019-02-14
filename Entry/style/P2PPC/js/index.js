/*

*王志涛

*白菜金融网

*/

try{

	if(window.console&&window.console.log)

	{

		console.log("一张网页，要经历怎样的过程，才能抵达用户面前？\n一位新人，要经历怎样的成长，才能站在技术之巅？\n探寻这里的秘密；\n体验这里的挑战；\n成为这里的主人；\n加入白菜金融，你，可以影响金融世界。\n");

		console.log("请将简历发送至 js@jsran.cn（ 邮件标题请以“姓名-应聘XX职位-来自console”命名）")

	}

}catch(e){}

$(document).ready(function() {

	$.fn.countTo = function(a) {

		a = a || {};

		return $(this).each(function() {

			var c = $.extend({},

				$.fn.countTo.defaults, {

					from: $(this).data("from"),

					to: $(this).data("to"),

					speed: $(this).data("speed"),

					refreshInterval: $(this).data("refresh-interval"),

					decimals: $(this).data("decimals")

				},

				a);

			var h = Math.ceil(c.speed / c.refreshInterval),

			i = (c.to - c.from) / h;

			var j = this,

			f = $(this),

			e = 0,

			g = c.from,

			d = f.data("countTo") || {};

			f.data("countTo", d);

			if (d.interval) {

				clearInterval(d.interval)

			}

			d.interval = setInterval(k, c.refreshInterval);

			b(g);

			function k() {

				g += i;

				e++;

				b(g);

				if (typeof(c.onUpdate) == "function") {

					c.onUpdate.call(j, g)

				}

				if (e >= h) {

					f.removeData("countTo");

					clearInterval(d.interval);

					g = c.to;

					if (typeof(c.onComplete) == "function") {

						c.onComplete.call(j, g)

					}

				}

			}

			function b(m) {

				var l = c.formatter.call(j, m, c);

				f.html(l)

			}

		})

	};

	$.fn.countTo.defaults = {

		from: 0,

		to: 0,

		speed: 1000,

		refreshInterval: 100,

		decimals: 0,

		formatter: formatter,

		onUpdate: null,

		onComplete: null

	};

	function formatter(b, a) {

		return b.toFixed(2)

	}

	$("#count-number").data("countToOptions", {

		formatter: function(b, a) {

			return b.toFixed(2).replace(/\B(?=(?:\d{3})+(?!\d))/g, ",")

		}

	});

	$(".timer").each(count);

	function count(a) {

		var b = $(this);

		a = $.extend({},

			a || {},

			b.data("countToOptions") || {});

		b.countTo(a)

	};

	$('#ban-wx').mouseover(function(){

        $('.wx-hide').fadeIn(500,function(){    //微信鼠标经过

            $('.wx-hide').mouseout(function() { // 回调，鼠标离开

            	$(this).fadeOut()

            });

        });

    });

    jQuery("#slideBox").slide({mainCell:".bd ul",effect:"fold",autoPlay:true,delayTime:700,trigger:"click"}); //banner

    jQuery(".txtScroll-left").slide({mainCell:".bd ul",autoPage:true,autoPlay:true,scroll:1,vis:1});//notice

    /*

     投影

     */

     $('#fif').children('li').mouseover(function() {

     	$(this).css('position', 'relative');

     });

     $('#fif').children('li').mouseout(function() {

     	$(this).css('position', 'static');

     });

    /*

    弹窗

    */

    $("#alertHd").val($.cookie("alertHd"));

    var alertHd = 1; 

    $.cookie("alertHd", alertHd);

    if ($("#alertHd").val() == 0) { //0 弹窗 1 不显示 3 一直不显示

    	$('#bg').show();

    	$('#huodong').show();

    	$('#huoclose').click(function(){

    		$('#bg').fadeOut();

    		$('#huodong').fadeOut();

    	})

    }

    /*

    修改首页两个项目图片分别不同显示（给鹏程填坑）

    */

    $.each('.subjet', function(index, val) {

    	$('.subjet').eq(index).attr('in', index);

    	if (index == 0) {

    		$('.subjet').eq(index).children('div.left').children('img').attr('src', themes + 'images/index_haha.gif?v=2.4');

    	}

    	if (index == 1) {

    		$('.subjet').eq(index).children('div.left').children('img').attr('src', themes + 'images/give_acti.jpg?v=2.1');

    	}

    });

    

});