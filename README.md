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

# Đồng bộ
Việc đồng bộ sẽ được tự động thực hiện 2 lần 1 ngày.
Để việc đồng bộ chính xác hơn, bạn nên setup real cronjob trên Server thay vì virtual cronjob mặc định của WordPress.

# Cách sử dụng
Format đầy đủ
```
[coupon type="merchant1,merchant2" cat="category-1,category-2"]
```
Trong đó *type* là thuộc tính bắt buộc, *cat* là thuộc tính tùy chọn có thể xóa bỏ.
Có thể chọn nhiều merchant, category. Chúng được ngắn cách bằng dấu *,*

Ví dụ
```
[coupon type="lazada"]
[coupon type="adayroi,lazada"]
[coupon type="adayroi" cat="me-va-be"]
```

# Tác giả

* **Dũng Nguyễn**(aka nhymxu) - *Developer* - [Interspace Việt Nam](https://dungnt.net)

## Bản quyền

Dự án này được giữ bản quyền cho tác giả. Bạn được phép sử dụng miễn phí.
Mọi chỉnh sửa phân phối đều phải được sự đồng ý của tác giả.