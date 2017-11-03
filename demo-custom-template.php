<style>
/*
* Coupon area
*/
.coupondiv {
    border: 1px solid #d3d3d3;
    min-width: 250px;
    margin-bottom: 6px;
    background-color: #fff
}
.coupondiv .promotiontype {
    padding: 15px;
    overflow: hidden
}
.promotag {
    float: left
}
.promotagcont {
    background: #fff;
    color: #fe6f17;
    overflow: hidden;
    width: 70px;
    border-radius: 2px;
    -webkit-box-shadow: 1px 1px 4px rgba(34, 34, 34, .2);
    box-shadow: 1px 1px 4px rgba(34, 34, 34, .2);
    text-align: center
}
.promotagcont .saleorcoupon {
    background: #fe6f17;
    padding: 7px 6px;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    line-height: 2em
}
.tagsale.promotagcont {
    background: #fff;
    color: #1fb207
}
.tagsale .saleorcoupon {
    background: #1fb207
}
.saveamount {
    min-height: 58px;
    font-size: 20px;
    margin: 0 auto;
    padding: 4px 3px 0;
    font-weight: 700;
    line-height: 2.5
}
.coupondiv .cpbutton {
    float: right;
    position: relative;
    z-index: 1;
    text-align: right;
    width: 140px;
    margin-top: 35px;
    margin-right: 15px
}
.copyma {
    width: 110px;
    min-width: 110px;
    display: inline-block;
    position: relative;
    margin-right: 30px;
    padding: 15px 5px;
    border: 0;
    background: #fe6f17;
    color: #fff;
    font-family: 'Roboto', sans-serif;
    font-size: 15px;
    font-weight: 500;
    line-height: 1;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    border-style: solid;
    border-color: #fe6f17;
    border-radius: 0
}
.copyma:after {
    border-left-color: #fe6f17;
    content: "";
    display: block;
    width: 0;
    height: 0;
    border-top: 45px solid transparent;
    border-left: 45px solid #fe6f17;
    position: absolute;
    right: -45px;
    top: 0
}
.copyma:hover {
    background-color: #cb5912
}
.copyma:hover:after {
    opacity: 0;
    -webkit-transition-duration: .5s;
    transition-duration: .5s
}
.coupon-code {
    position: absolute;
    top: 0;
    right: -45px;
    z-index: -1;
    min-width: 50px;
    height: 45px;
    padding: 0 5;
    font-weight: 500;
    line-height: 45px;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    border-radius: 0;
    font-size: 16px;
    color: #222;
    font-family: 'Open Sans', sans-serif;
    border: 1px solid #ddd
}
.xemngayz {
    width: 88px;
    min-width: 88px;
    display: inline-block;
    position: relative;
    margin-right: 30px;
    padding: 15px 15px;
    border: 0;
    background: #1fb207;
    color: #fff;
    font-family: 'Roboto', sans-serif;
    font-size: 16px;
    font-weight: 500;
    line-height: 1;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    border-style: solid;
    border-color: #1fb207;
    border-radius: 0
}
.xemngayz:hover {
    background-color: #167f05
}
.promotiondetails {
    padding-left: 20px;
    width: calc(100% - 270px);
    word-wrap: break-word;
    float: left;
    font-size: 16px
}
.coupontitle {
    display: block;
    font-family: 'Roboto', sans-serif;
    margin-bottom: 5px;
    color: #222;
    font-weight: 500;
    line-height: 1.2;
    text-decoration: none;
    font-size: 16px
}
.cpinfo {
    display: block;
    margin-bottom: 5px;
    color: #222;
    line-height: 1.6;
    text-decoration: none;
    font-size: 14px
}
.news-box .news-thumb,
.news-box .news-info {
    display: inline-block;
    float: left
}
.news-box .news-info {
    width: 500px;
    margin-left: 10px
}
@media screen and (max-width: 767px) {
    .coupontitle {
        font-size: 18px
    }
    .promotagcont {
        width: 60px
    }
    .promotagcont .saleorcoupon {
        font-size: 11px
    }
    .saveamount {
        min-height: 50px;
        font-size: 16px
    }
    .promotiondetails {
        margin-right: 0;
        font-size: 14px;
        width: auto;
        float: none;
        margin-left: 70px;
        padding-left: 0
    }
    .coupondiv .cpbutton {
        clear: both;
        margin-top: 0;
        width: 116px
    }
    .copyma {
        width: 100px;
        min-width: 100px;
        padding: 10px 8px
    }
    .copyma:after {
        border-top: 35px solid transparent;
        border-left: 35px solid #fe6f17;
        position: absolute;
        right: -34px;
        top: 0
    }
    .coupon-code {
        position: absolute;
        top: 0;
        right: -35px;
        z-index: -1;
        height: 35px;
        line-height: 35px
    }
    .xemngayz {
        width: 135px;
        min-width: 135px;
        padding: 10px 8px
    }
    .xemngayz:hover {
        background-color: #167f05
    }
}	
</style>
<script type="text/javascript">
function nhymxu_at_coupon_copy2clipboard( coupon_value ) {
    var aux = document.createElement("input");
    aux.setAttribute("value", coupon_value);
    document.body.appendChild(aux);
    aux.select();
    document.execCommand("copy");
    document.body.removeChild(aux);
}
</script>
<?php foreach( $at_coupons as $row ): ?>
    <div class="coupondiv">
        <div class="promotiontype">
            <div class="promotag">
                <div class="promotagcont tagsale">
                    <div class="saveamount"><?=($row['save'] != '') ? $row['save'] : 'KM';?></div>
                    <div class="saleorcoupon"><?=($row['code']) ? ' SALE' : ' COUPON';?></div>
                </div>
            </div>
            <div class="promotiondetails">
                <div class="coupontitle"><?=$row['title'];?></div>
                <div class="cpinfo">
                    <strong>Hạn dùng: </strong><?=$row['exp'];?>
                    <?php if( !empty($row['categories']) ): ?>
                    <br><strong>Ngành hàng:</strong> <?=implode(',', $row['categories']);?>
                    <?php endif; ?>
                    <?=( $row['note'] != '' ) ? '<br>' . $row['note'] : '';?>
                </div>
            </div>
            <div class="cpbutton">
            <?php if( $row['code'] != '' ): ?>
                <div class="copyma" onclick="nhymxu_at_coupon_copy2clipboard('<?=$row['code'];?>');window.open('<?=$row['deeplink'];?>','_blank')">
                    <div class="coupon-code"><?=$row['code'];?></div>
                    <div>COPY MÃ</div>
                </div>
            <?php else: ?>
                <div class="xemngayz" onclick="window.open('<?=$row['deeplink'];?>','_blank')">XEM NGAY</div>
            <?php endif; ?>
            </div>
        </div>
    </div>	
<?php
endforeach;