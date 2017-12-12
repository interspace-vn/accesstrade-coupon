# accesstrade-coupon
Hệ thống đồng bộ coupon tự động

# Cài đặt

### Cách 1
Tải thư mục nhymxu-at-coupon lên wp-content/plugins
Vào admin và active plugin
Vào Settings -> AccessTrade Coupon để cài đặt AccessTrade User ID

### Cách 2
Tải bản đóng gói plugin ở ![đây](https://github.com/nhymxu/accesstrade-coupon/releases)
Cài đặt plugin với file zip ở WordPress admin

# Cập nhật plugin
Plugin có tích hợp chế độ thông báo bản cập nhật khi chúng tôi release phiên bản mới.
Tuy nhiên chưa hỗ trợ automatic update theo WordPress. 
Bạn cần vào wp-admin và click update thủ công khi thấy có thông báo phiên bản mới.

# Đồng bộ
Việc đồng bộ sẽ được tự động thực hiện 2 lần 1 ngày.
Để việc đồng bộ chính xác hơn, bạn nên setup real cronjob trên Server thay vì virtual cronjob mặc định của WordPress.

# Cách sử dụng
Format đầy đủ
```
[atcoupon type="merchant1,merchant2" cat="category-1,category-2"]
```
Trong đó *type* là thuộc tính bắt buộc, *cat* là thuộc tính tùy chọn có thể xóa bỏ.
Có thể chọn nhiều merchant, category. Chúng được ngắn cách bằng dấu *,*

Ví dụ
```
[atcoupon type="lazada"]
[atcoupon type="adayroi,lazada"]
[atcoupon type="adayroi" cat="me-va-be"]
```

# Cách tùy biến giao diện coupon
Với các bạn có nhu cầu tùy biến giao diện hiển thị coupon theo phong cách riêng.
Các bạn có thể làm dễ dàng bằng cách copy file *demo-custom-template.php* vào thư mục giao diện bạn đang sử dụng
và đổi tên file thành *accesstrade_coupon_template.php*.
Hoặc các bạn có thể tải file template này ở [đây](https://github.com/nhymxu/accesstrade-coupon/blob/master/demo-custom-template.php) 

File này được đặt ngang hàng với file *style.css* và *functions.php* của giao diện bạn đang sử dụng.
Sau đó mở file này và chỉnh sửa cấu trúc HTML/CSS theo ý muốn.

Lưu ý: vui lòng giữ cấu trúc vòng lặp
```
<?php foreach( $at_coupons as $row ): ?>
```
Vì đây là biến chứa dữ liệu coupon để hiển thị.

*Trở về giao diện coupon mặc định*
Khi bạn không muốn sử dụng giao diện coupon tùy biến, và muốn sử dụng giao diện coupon mặc định.
Hãy xóa/di chuyển/đổi tên file *accesstrade_coupon_template.php* trong thư mục giao diện hiện tại.
Hệ thống tự động sử dụng giao diện coupon mặc định.

# Tác giả

* **Dũng Nguyễn** - *Developer* - [Interspace Việt Nam](https://dungnt.net)

## Bản quyền

Dự án này được giữ bản quyền cho tác giả. Bạn được phép sử dụng miễn phí.
Mọi chỉnh sửa, phân phối đều phải được sự đồng ý của tác giả.