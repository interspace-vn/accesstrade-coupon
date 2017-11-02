(function(){
    tinymce.create('tinymce.plugins.ATCouponPlugin', {
        init: function(ed, url) {
            ed.addButton('at_coupon_button', {
                title: 'AT Coupon',
                text: '[at_coupon]',
                cmd: 'at_coupon_command',
                /*image: url + '/img/at.png'*/
            });
            ed.addCommand('at_coupon_command', function() {
                var win = ed.windowManager.open({
                    title: 'Coupon Properties',
                    body: [
                        {
                            type   : 'listbox',
                            name   : 'nxmerchants',
                            label  : 'Merchant',
                            minWidth: 300,
                            values : nhymxu_at_coupon_get_tinymce_list('merchant'),
                        },
                        {
                            type   : 'listbox',
                            name   : 'nxcats',
                            label  : 'Ngành hàng',
                            minWidth: 300,
                            values : nhymxu_at_coupon_get_tinymce_list('cat'),
                        },
                    ],
                    buttons: [
                        {
                            text: "Ok",
                            subtype: "primary",
                            onclick: function() {
                                win.submit();
                            }
                        },
                        {
                            text: "Cancel",
                            onclick: function() {
                                win.close();
                            }
                        }
                    ],
                    onsubmit: function(e){
                        var returnText = '';
                        if( e.data.nxmerchants.length > 0 ) {
                            returnText = '[atcoupon type="'+ e.data.nxmerchants +'"';
                            if( e.data.nxcats.length > 0 ) {
                                returnText = returnText + ' cat="' + e.data.nxcats + '"';
                            }
                            returnText = returnText + ']'; 
                            ed.execCommand('mceInsertContent', 0, returnText);
                        } else {
                            alert('Không được bỏ trống mục Merchant');
                        }
                    }
                });
            });
        },
        getInfo: function() {
            return {
                longname : 'Nhymxu AT Coupon Generater',
                author : 'Dũng Nguyễn',
                authorurl : 'https://dungnt.net',
                version : "0.1"
            };
        }
    });
    tinymce.PluginManager.add( 'at_coupon_button', tinymce.plugins.ATCouponPlugin );
})();