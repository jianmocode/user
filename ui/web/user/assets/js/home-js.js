// 获取今天日期
var today=new Date();
var todayWeek=today.getDay();
switch (todayWeek){
		case 0:
		  weeks="日";
		  break;
		case 1:
		  weeks="一";
		  break;
		case 2:
		  weeks="二";
		  break;
		case 3:
		  weeks="三";
		  break;
		case 4:
		  weeks="四";
		  break;
		case 5:
		  weeks="五";
		  break;
		case 6:
		  weeks="六";
		  break;
	}
$("#today span").html(today.getFullYear()+"年"+(today.getMonth()+1)+"月"+today.getDate()+"号星期"+weeks);
// 获取公告
 // $.ajax({
 //            url:"data/notice",
 //            type:"get",
 //            data:"",
 //            dataType:"json",
 //            success:function(data){
 //                alert(data);
 //            },
 //            error:function(){
 //                alert("失败");
 //            },
 //            async:true
 //        })

// 导航栏鼠标覆盖和点击后的效果
	// $(".header-nav li").mouseover(function(event) {
	// 	$(this).addClass('colors').siblings().removeClass('colors');
	// 	$(".header-nav li").eq(0).addClass('colors');
	// });
	// $(".header-nav li").mouseout(function(event) {
	// 	$(".header-nav li").eq(0).addClass('colors').siblings().removeClass('colors');
	// });
 // 中间的图片轮播
 	// var notice_i=-1;
 	// var notice_m;
 	// function run(){
 	// 	notice_i++;
 	// 	if(notice_i>=$(".newNotice-img-box img").length-1){
 	// 		notice_i=0;
 	// 	}
 	// 	notice_m=notice_i+1;
 	// 	// console.log(notice_i+"========="+notice_m);
 	// 	$(".newNotice-img-box img").eq(notice_i).css("left",0+"px").animate({"left": -236},2000);
 	// 	$(".newNotice-img-box img").eq(notice_m).css("left",236+"px").animate({"left": 0},2000,function(){
 	// 		if(notice_m==$(".newNotice-img-box img").length-1){
 	// 				$(".newNotice-img-box img").eq(notice_m).delay(2000).animate({"left": -236},2000)
 	// 		}
 	// 	});
 	// }
 	// setInterval(run,4000);
// 用户名密码验证

 
// 页面下滑时导航固定到上边
$(window).scroll(function(){

	var offset = 136;
	var pos = $(document).scrollTop();
	if ( pos > offset ) {
		$(".header-nav").addClass('fixed');
	} else {
		$(".header-nav").removeClass('fixed')
	}
})
