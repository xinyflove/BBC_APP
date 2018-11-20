var utils = {
    baseUrl: '',
    //baseUrl: '',
    //获取url链接参数
    getQueryString: function (name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); 
        var r = window.location.search.substr(1).match(reg);
        if(r != null) return r[2];
        return '';
    },
    // 转换成2018年9月27号 11:00:00
    formatTime: function (t) {
        var date = new Date(t);
        var y = date.getFullYear(),
            m = date.getMonth() + 1,
            d = date.getDay(),
            h = date.getHours(),
            mi = date.getMinutes(),
            s = date.getSeconds();
        return (y + '年' + m + '月'+ d + '日' + h + ':' + mi + ':' + s)
    },
    // 转换成2017-09-19
    format: function(date, format) {
        var A = Array();
        var currtime = new Date(date);
        A.push(currtime.getFullYear());
        A.push(('0' + (currtime.getMonth() + 1)).slice(-2));
        A.push(('0' + currtime.getDate()).slice(-2));
        return A.join(format || '-');
    },
    
    //添加Cookie
    addCookie: function (name, value, expireHours) {
        var cookieString = name + "=" + escape(value) + "; path=/";
        //判断是否设置过期时间
        if(expireHours > 0) {
            var date = new Date();
            date.setTime(date.getTime() + expireHours * 3600 * 1000);
            cookieString = cookieString + ";expires=" + date.toGMTString();
        }
        document.cookie = cookieString;
    },
    //获取Cookie
    getCookie:function(name){
        var strcookie = document.cookie;
        var arrcookie = strcookie.split("; ");
        for(var i = 0; i < arrcookie.length; i++) {
            var arr = arrcookie[i].split("=");
            if(arr[0] == name) return unescape(arr[1]);
        }
        return null;
    },
    //删除Cookie
    delCookie:function(name){
        this.addCookie(name,1,-1)
    },
    //判断对象是否为空
    isEmpty:function(obj) {
        for(var name in obj) {
            return false;
        }
        return true;
    }
}




