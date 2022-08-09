<div>
    <div class="">
        <p class="">两步验证</p>
        <p>请下载 Google 的两步验证器。</p>
        <p>
            <i class="fa fa-android"></i>
            <a id="android" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">&nbsp;Android</a>
        </p>
        <p>
            <i class="fa fa-apple"></i>
            <a id="ios" href="https://itunes.apple.com/cn/app/google-authenticator/id388497605?mt=8">&nbsp;iOS</a>
        </p>

        <p>如果遇到问题，请参考：<a href="https://phpartisan.cn/specials/5" target="_blank">Google Authenticator帮助文档</a></p>

        <p>本地时间：<span class="text-red" id="txt"></span></p>
        <p>服务器时间：<span class="text-red" >{!! date('Y-m-d H:i:s') !!}</span></p>
        @if($google_auth)
            <div class="form-group form-group-label">
                <div class="text-center">
                    <div>
                        {!! QrCode::encoding('UTF-8')->size(200)->margin(1)->errorCorrection('H')->generate($createSecret["codeurl"]); !!}
                    </div>
                    <h4 class="">
                        密钥：<span class="text-red" style="font-size: 12px">{{ $createSecret["secret"] }}</span>
                    </h4>
                </div>
            </div>
        @else
            <h4 class="text-red">未绑定谷歌验证</h4>
        @endif

    </div>
</div>


<script>


    $(document).ready(function () {


        $('#android').popover({
            trigger: 'hover',
            html: true,
            content: "<img height='100' width='100' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAAEAAQAAAAB0CZXLAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAd2KE6QAAAAHdElNRQfjCxUOBxONcq8jAAACnElEQVRo3tWZPY7rMAyEKbhI6SPkKL6ZldzMR/ERXKowzMeZkTa722z5TEEIAuhrCP4NafM/jt0BaBantOfb99Xid7N5O82meMJZkgDPsCVeawP2wtMW95w9GD/SAC9YtxdipS0wzZbTlumwKRfQrPoevqlhIF7NwsyEQGm2PvbqHq+w0ZMBCDmkD7AVPloQab9i8u7ASP9+Aej+rg93BlRw5SnCJxIHvz9q9c2BcNaIOiMTDjrnzaMmm3Vv3h9A4XpeFtcK7Y0ibEycTACts4dfvO8OIPH9K3EyACue0NkdJivY0N9PdPkkANo6Ej/aukIuylcPOfzxJIBHsK2It11e8yMizdjf2VBSAE11WD1xXx9wkBh2xiwAPMWs2U1njoQJxSiXeRIgxJXLzPBX1RMbChJnzgJ0rVgoFwuscwqtTdI3B8CnghnKqN43+qunf+84CQCca2itq1dgMKaQywGgcIVEgVCJCtaY+72zm5z1/4FvOlDi3Ob+2iV6CoD9mrGEG2bKEfE6GkoKAJ6KSvuCyzDxyUwWKCmQHIDDzF1qil7rOsq9K5AMAFp2pacuzd3Ia3ZtLkNyANinwV/c50T7W+SpiVJKY1QCgM2isoK9WAJ8TNzTYXkAf9NS5g4VCAE/PoN5AiDkB5tdTEmyNLKGKbNxPbjkABzWFU3faIvGJS0b93BWCkCrA2MdLsNZ5/BXDoDNXdsDpybkAkTp/5Eodwf6Vk3jHjQtPGVdn0xHEqBvOLE6CBtrSBTOF58ilgN4OnsihQq+X2BJi3EPZp6WBujfLxrrGJe02OEwlTSxpgG898Q6Jlbj3G3JgL6t9abOblqA/Phkdm+AIacNJ0ZXfoLZhsuyAEp/rc3DTKlEU9Zo1ZMA8D9OCuAfOvBOGWO+UU0AAAAldEVYdGRhdGU6Y3JlYXRlADIwMTktMTEtMjFUMjE6MDc6MTktMDc6MDDQWz//AAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE5LTExLTIxVDIxOjA3OjE5LTA3OjAwoQaHQwAAAABJRU5ErkJggg=='>",

        })
        $('#ios').popover({
            trigger: 'hover',
            html: true,
            content: "<img height='100' width='100' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAAEAAQAAAAB0CZXLAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAd2KE6QAAAAHdElNRQfjCxUOCQp3miptAAACe0lEQVRo3t2ZMZKDMAxFzVC45AgchZsFcjMfhSNQUjDW/v8F2U22SIvlmZAEXgpZsr6kJPuyUhPAkbgGs35P09O2NBsvx7DqwRwFyDB2H9aZXx+JH/I24d7KS2kGWFNHAGam1Busw08WfJhgfURgtIp9ALRpQxoEbIF1ZcazZAzOIxygoEUqYdD227jwzvNfVN8c8AQib5YT2GTme4ZpHDizPgDzE6edqcycb3rROHAlUsTqiE/YB3wrM7Znqni1AvCK+8b0aAfOIEMYz8YiXYgDuHZjH2A+AawK35YPcb81kJEQ80ZPrhQ1mEp7mTCVOcMAZyL1oAWFh5Mxw3xk2lsDPGMZCcT25KvH0UtImKVm86o4AEDzO6leXhW9Cz2qWgVOphzEALA6hiiVgPkTr0FyUOpL9ZoA4MhRekZK5bHqyW4PBrA3gwC46vW7uxRBSzloAjhwP3nNb6VjFWI6eqYIVYaJAFDsOjc/saVmrTJQ9dZUJQdtALCOMUktKyqPr4Q5SdKDAPJm9tamKNV4styu9NkIQHvgSI4DdN/t9XLRK7EAADtPiTTjdeabJleHAA/aEADOpv1qNzfEEyn7bmXaFgAGLQstjT7qOWZUeXz+LgigUQ+1mz0O5eDQXA6H1MWwEYCOtFUyJl1gXHImPF2dWgxAFRUSC/trNqRH3tJrNQJ4oaUWZrSF9xSKII31iAUBfv+eQE55qrf2DcH2/B0/tg6cfbfPRpL87j2ONQX4xFtBywmPsa1+0FhvYWIBC9Was2L2bRQ8XPa3yX8TAOsN7zdNE286+c3MxgEPWvOiRBPvE/iYy90a8ATiFchyeVN/T9RAgH1ZMYAfXZaYCVpMtGYAAAAldEVYdGRhdGU6Y3JlYXRlADIwMTktMTEtMjFUMjE6MDk6MTAtMDc6MDBbCkqfAAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE5LTExLTIxVDIxOjA5OjEwLTA3OjAwKlfyIwAAAABJRU5ErkJggg=='>",

        })

    });

    startTime();

    function startTime() {
        //获取当前系统日期
        var myDate = new Date();
        var y = myDate.getFullYear(); //获取当前年份(2位)
        var m = myDate.getMonth() + 1; //获取当前月份(0-11,0代表1月)
        var d = myDate.getDate(); //获取当前日(1-31)
        var h = myDate.getHours(); //获取当前小时数(0-23)
        var mi = myDate.getMinutes(); //获取当前分钟数(0-59)
        var s = myDate.getSeconds(); //获取当前秒数(0-59)
        var hmiao = myDate.getMilliseconds(); //获取当前毫秒数(0-999)
        //s设置层txt的内容
        document.getElementById('txt').innerHTML = y + "-" + m + "-" + d + " " + h + ":" + mi + ":" + s;
        //过500毫秒再调用一次
        // t = setTimeout('startTime()', 500)

        //小于10，加0
        // function checkTime(i) {
        //     if (i < 10) {
        //         i = "0" + i
        //     }
        //     return i
        // }
    }


</script>
