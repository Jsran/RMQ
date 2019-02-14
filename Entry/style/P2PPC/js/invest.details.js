$(document).ready(function() {

	//出借金额*年化率/100/12*借券期限 = 收益金额

	$('#touzi').bind('input propertychange', function () {  //显示收益

		var touzi = parseInt($('#touzi').val());

		var lilv = parseInt($('#lilv').html());

		var qixian = parseInt($('#qixian').html());

		var ketou = parseInt($('#ketou').attr('dmoney'));

		var leibie = $('#leibie').html();

		var lilv2 = $('#lilv').html();

		var num;

		var lilv3 = 0;

		var shouyi = 0;

		var shouyimax = 0;

		num=lilv2.match(/\d+(\.\d+)?/g);

		for (var i = 0; i < num.length; i++) {

			lilv3 = lilv3 + parseInt(num[i]);

		}

		if (leibie == "天" ) {

			shouyi = touzi*lilv3/100/365*qixian;

			shouyimax = ketou*lilv3/100/365*qixian;

		} else {

			shouyi = touzi*lilv/100/12*qixian;

			shouyimax = ketou*lilv/100/12*qixian;

		}

		if (touzi <= ketou ) {

			$('#shouyi').html(shouyi.toFixed(2));

		}else if(touzi > ketou){

			$('#touzi').val(ketou);

			$('#shouyi').html(shouyimax.toFixed(2));

		}else if( $('#touzi').val() ==''|| $('#touzi').val() == 0){

			shouyi = 0

			$('#shouyi').html(shouyi);

		}else{

			return false;

		}

	}); 

	// 新增
	// 未填写问卷点击输入框提示
	 $("#touzi").click(function(){ //
	 	if(isHasAnswer == 0){
	 		$(".invest-bg,#danger").fadeIn();
	 		$("#danger .invest-tip-cont").html('您还未填写调查问卷，评测后可进行出借操作')
	 	}
	 })
	 $(".invest-close").click(function(){ //
	 	$(".invest-bg,#danger").fadeOut();
	 })
	// 超额提示
	function invest_tip(e){ //
		$(".invest-bg,#danger_1").fadeIn();
		$("#danger_1 .invest-tip-cont").text(e);
	}
	// 超笔数限制
	function invest_tip2(e){ //
		$(".invest-bg,#danger_2").fadeIn();
		$("#danger_2 .invest-tip-cont").text(e);
	}

	$("#invest-close1,#iknow").click(function(){ //
		$(".invest-bg,#danger_1").fadeOut();
		if(touci>100){ //
			// 判断是否24小时内弹出窗1
			if($.cookie("isClose3") != 'yes'){ 
				invest_tip2('您好，您当日出借频率过高。为防止他人借用您的名义从事洗钱等非法活动，请不要出租、出借、转让您的身份证件和银行账户。');
				$("#invest-close1,#iknow,.reset").click(function(){ //
					$.cookie("isClose3",'yes',{ expires:1});
				})
			}
		};
	})
	$('#iknow-know,#invest-close2,#iknow-ele').click(function(){ //
		$(".invest-bg,#danger_2,#danger_3").fadeOut();
	})
	//电子签章书
	$("#show_elec").click(function(){ //
		$(".cont-bg,.cont-elec").fadeIn();
	})
    $(".cont-elec span").click(function(){ //
		$(".cont-bg,.cont-elec").fadeOut();
	})
    	
  // end

		var off = true;

		$('#submit').click(function(){

			var touzi = $('#touzi').val();

			var lilv = parseInt($('#lilv').html());

			var qixian = parseInt($('#qixian').html());

			var ketou = parseInt($('#ketou').attr('dmoney'));

			var keyong = parseInt($('#keyong').html());

			var leibie =  $('.flag').html();

			if (leibie == "项目新手标") {		// 判断是否为新手标的

				if (parseInt(touzi) > ketou) {

				alert('您输入的金额大于可投金额！');

				return false;

				}else if(parseInt(touzi) > 5000){

					alert("最多5000！");

					return false;

				}else if(touzi == '' || touzi == '0'){

					alert("请输入出借金额！");

					return false;

				}else if(parseInt(touzi)%100 != 0){

					alert("请输入100的倍数哦！");

					return false;

				}
			} else {

				if (parseInt(touzi) > ketou) {

					alert('您输入的金额大于可投金额！');

					return false;

				}else if(touzi == '' || touzi == '0'){

					alert("请输入出借金额！");

					return false;

				}else if(parseInt(touzi)%100 != 0){   // 此处取余 1000 的话， 为最低1000起投，100倍数。如果可投小于1000 为100起投。 为100的话为100起投。

						alert("请输入100的倍数哦！");

						return false;

				// 新增的啊
				}else if(times==0){ //第一次投资提示电子签章
					if($.cookie("isClose") != 'yes'){ 
						
						$(".invest-bg,#danger_3").fadeIn();
						$("#iknow-ele").click(function(e){ //
							$.cookie("isClose",'yes');
						})
						return false;
					}
					
				}else{
					//================  24小时内只提示一次限额限笔 
				    
					if(score>=45 && score<60 && touall>1000000){ //
						if($.cookie("isClose2") != 'yes'){ 
							invest_tip('您是保守型出借者，建议您的出借金额控制在100万以内。');
							$("#invest-close1,#iknow,.reset").click(function(){ //
								$.cookie("isClose2",'yes',{ expires:1});
							})
							return false;
						}
						
					}else if(score>=60 && score<90 && touall>2000000){ //
						
						if($.cookie("isClose2") != 'yes'){ 
							invest_tip('您是稳健型出借者，建议您的出借金额控制在500万以内。');
							$("#invest-close1,#iknow,.reset").click(function(){ //
								$.cookie("isClose2",'yes',{ expires:1});
							})
							return false;
						}
						
					}else if(score>=90 && score<120 && touall>10000000){ //
						if($.cookie("isClose2") != 'yes'){ 
							invest_tip('您是平衡型出借者，建议您的出借金额控制在1000万以内。');
							$("#invest-close1,#iknow,.reset").click(function(){ //
								$.cookie("isClose2",'yes',{ expires:1});
							})
							return false;
						}
						
					}else if(score>=120 && touall>20000000){ //
						if($.cookie("isClose2") != 'yes'){ 
							invest_tip('您是进取型出借者，建议您的出借金额控制在2000万以内。');
							$("#invest-close1,#iknow,.reset").click(function(){ //
								$.cookie("isClose2",'yes',{ expires:1});
							})
							return false;
						}
						
					}else if(touci>100){ //
						if($.cookie("isClose2") != 'yes'){ 
							invest_tip2('您好，您当日出借频率过高。为防止他人借用您的名义从事洗钱等非法活动，请不要出租、出借、转让您的身份证件和银行账户。');
							$("#invest-close1,#iknow,.reset").click(function(){ //
								$.cookie("isClose2",'yes',{ expires:1});
							})
							return false;
						}
						
					}		
				}
				return true;

			}

			if (!off) {

				return false;

			};

			off = false;

		});	

	$('.showon').mouseover(function(){					//小图标显示

			$(this).parents().children('.mode1-tip').show();

		});

		$('.showon').mouseout(function(){				//小图标yincang 

			$(this).parents().children('.mode1-tip').hide();

	});

	
	// 借款合同
	$('#show-cont').click(function(){ 		//合同显示

		$('.cont-bg').fadeIn();

		$('.ht1').fadeIn();

	});

	$('.cont-hide,.cont-bg').click(function(){  //合同隐藏

		$('.cont-bg').fadeOut();

		$('.ht1').fadeOut();

		$('.hb').fadeOut();

	});
	// 履约险合同
	$('#show-bd').click(function(){ 		//合同显示
		$('.cont-bg').fadeIn();
		$('.ht2').fadeIn();
		
	});
	$('.cont-hide,.cont-bg').click(function(){  //合同隐藏
		$('.cont-bg').fadeOut();
		$('.ht2').fadeOut();
		$('.hb').fadeOut();
	});
	$("#use-hg").click(function() {

		$('.cont-bg').fadeIn();

		$('.hb').fadeIn();

	});

	//console.log($('#hb-ok').children('li.hb-yes').children('h3'))

	if ($("#redcount").val()>0) {

		$('.hb').show();

		$('.cont-bg').show();

	}

	$('#hb-ok').children('li.hb-yes').children('h3').click(function(){

		$('.hb').fadeOut();

		$('.cont-bg').fadeOut();

		$('#use-hg').css('color','#888');

		$('#use-hg').html('<img src="'+ themes +'images/yhq-my.png">已使用红包');

		var chuan = $(this).nextAll('input').val();

		$('#quan_jin').val(chuan);

		var chuan2 = parseInt($(this).prev().val());

		console.log(chuan2);

		console.log(chuan);

		$("#touzi").val(chuan2);

		var touzi = parseInt($('#touzi').val());

		var lilv = parseInt($('#lilv').html());

		var qixian = parseInt($('#qixian').html());

		var ketou = parseInt($('#ketou').attr('dmoney'));

		var leibie = $('#leibie').html();

		var lilv2 = $('#lilv').html();

		var num;

		var lilv3 = 0;

		var shouyi = 0;

		var shouyimax = 0;

		num=lilv2.match(/\d+(\.\d+)?/g);

		for (var i = 0; i < num.length; i++) {

			lilv3 = lilv3 + parseInt(num[i]);

		}

		if (leibie == "天" ) {

			shouyi = touzi*lilv3/100/365*qixian;

			shouyimax = ketou*lilv3/100/365*qixian;

		} else {

			shouyi = touzi*lilv/100/12*qixian;

			shouyimax = ketou*lilv/100/12*qixian;

		}

		if (touzi <= ketou ) {

			$('#shouyi').html(shouyi.toFixed(2));

		}else if(touzi > ketou){

			$('#touzi').val(ketou);

			$('#shouyi').html(shouyimax.toFixed(2));

		}else if( $('#touzi').val() ==''|| $('#touzi').val() == 0){

			shouyi = 0

			$('#shouyi').html(shouyi);

		}else{

			return false;

		}

	})

	$('.no-use').click(function(){

		$('.hb').fadeOut();

		$('.cont-bg').fadeOut();

		$('#use-hg').css('color','#fe6d00');

		$('#use-hg').html('<img src="'+ themes +'images/yhq-hb.png">使用红包');

		$('#quan_jin').val('');

	});

	function color(a,b){ 					//表格隔行换色

		var TbRow = document.getElementById(a);

		if (TbRow != null) {

		for (var i = 0; i < TbRow.rows.length; i++) {

			if (TbRow.rows[i].rowIndex % 2 == 1) {

				TbRow.rows[i].style.backgroundColor = ""

			} else {

				TbRow.rows[i].style.backgroundColor = b

			}

		}

	}

	};

    color("golaer","#FAFAFA");

	color("golaer2","#FAFAFA");

});