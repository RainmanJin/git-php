$(function(){
     //获取验证码
     var getCode = document.getElementById('getCode');
        var wait = 60;
        function time(btn){
            if (wait===0) {
                btn.removeAttribute("disabled");
                btn.innerHTML = "获取验证码";
                wait = 60;
            }else{
                btn.setAttribute("disabled",true);
                btn.innerHTML = wait + "s";
                wait--;
                setTimeout(function(){
                    time(btn);
                },1000);
            }
        }
        getCode.onclick = function(){
            time(this);
        };
})