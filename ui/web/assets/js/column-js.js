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

	// 页面下滑时导航固定到上边
$(window).scroll(function(){
	var wh=$(document).scrollTop()-136;
	var th=$(".main-right-top").height();
	var whr=$(document).scrollTop()-th-185;
	console.log(wh);
	if(wh+136>=136){
		console.log("zheli sh");
		$(".header-nav").css("top",wh+"px");	
		if(wh>=229){
			$(".main-right-down").css("top",whr+"px");
		}else{
			$(".main-right-down").css("top","0px");
		}

	}else{
		$(".header-nav").css("top","0px");
	}
	
})