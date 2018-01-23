// ͨ�õ���ʱ����������ʱ����������������������ʾ��ʽ���ص���
function countdown(element, options){
    var self = this;
    options = $.extend({
        start: 60,
        secondOnly: false,
        callback: null
    }, options || {});
    var t = options.start;
    var sec = options.secondOnly;
    var fn = options.callback;
    var d = +new Date();
    var diff = Math.round((d + t * 1000) / 1000);
    this.timer = timeout(element, diff, fn);
    this.stop = function() {
        clearTimeout(self.timer);
    };

    function timeout(element, until, fn) {
        var str = '',
            started = false,
            left = {d: 0, h: 0, m: 0, s: 0, t: 0},
            current = Math.round(+new Date() / 1000),
            data = {d: ':', h: ':', m: ':', s: ''};

        left.s = until - current;

        if (left.s < 0) {
            return;
        }
        else if(left.s == 0) {
            fn && fn();
        }
        if(!sec) {
            if (Math.floor(left.s / 86400) > 0) {
                left.d = Math.floor(left.s / 86400);
                left.s = left.s % 86400;
                str += '<font>'+toTime(left.d)+'</font>' + data.d;
                started = true;
            }
            if (Math.floor(left.s / 3600) > 0) {
                left.h = Math.floor(left.s / 3600);
                left.s = left.s % 3600;
                started = true;
            }
        }
        if (started) {
            str += '<font>' + toTime(left.h)+'</font>' + data.h;
            started = true;
        }
        if(!sec) {
            if (Math.floor(left.s / 60) > 0) {
                left.m = Math.floor(left.s / 60);
                left.s = left.s % 60;
                started = true;
            }
        }
        if (started) {
            str += '<font>' + toTime(left.m)+'</font>' + data.m;
            started = true;
        }
        if (Math.floor(left.s) > 0) {
            started = true;
        }
        if (started) {
            str += '<font>' + toTime(left.s)+'</font>' + data.s;
            started = true;
        }

        $(element).html(str);


        return setTimeout(function() {timeout(element, until,fn);}, 1000);
    }
}

function toTime(n){
    return n<10?"0"+n:n;
}