# Chào mừng bạn đến với StarWeather

**StarWeather** không chỉ là một công cụ theo dõi vệ tinh; đây là một hệ thống trí tuệ thời tiết hiện đại, được thiết kế để mang cả bầu trời và dữ liệu khí tượng toàn cầu vào trong tầm tay bạn. Với sự kết hợp giữa công nghệ theo dõi quỹ đạo chính xác và xử lý hình ảnh vệ tinh thời gian thực, StarWeather giúp các cá nhân và doanh nghiệp đưa ra những quyết định an toàn hơn trước những biến động của thiên nhiên.

---

##  Hệ thống này dành cho ai?

Cho dù bạn là một người đam mê thiên văn muốn theo dõi **ISS** hay **Starlink**, một chuyên gia nông nghiệp cần đánh giá lượng mưa, hay một nhà phát triển phần mềm muốn tích hợp dữ liệu thời tiết tin cậy vào ứng dụng của mình — StarWeather đều có những công cụ dành riêng cho bạn.

---

##  Những điểm nổi bật

### Khám Phá Trái Đất Qua Quả Địa Cầu 3D
Trải nghiệm giao diện 3D sống động, nơi bạn có thể thấy các vệ tinh đang bay lơ lửng trên đầu mình theo đúng quỹ đạo thực tế. Lớp phủ mây từ vệ tinh **Himawari-9** được cập nhật liên tục, cho bạn cái nhìn trực quan nhất về các cơn bão hoặc vùng mây đang hình thành.

### Hệ Thống Đánh Giá Rủi Ro Thông Minh
Mình không chỉ đưa ra những con số khô khan. StarWeather phân tích hàng loạt dữ liệu từ mật độ mây đến cường độ mưa để trả về một **Điểm Rủi Ro (Risk Score)** dễ hiểu. Bạn sẽ biết ngay tình trạng thời tiết tại khu vực của mình đang ở mức An toàn, Trung bình hay Nguy cấp.

### API Dành Cho Nhà Phát Triển
Nếu bạn muốn xây dựng ứng dụng riêng? Hệ thống API của Mình cực kỳ linh hoạt, được thiết kế theo chuẩn Enterprise, giúp bạn truy xuất dữ liệu vệ tinh và thời tiết chỉ với vài dòng code.

---

##  Hướng dẫn sử dụng nhanh

### Bắt đầu với Dashboard
Ngay khi truy cập hệ thống, bạn sẽ thấy Dashboard trung tâm:
1.  **Quả địa cầu**: Dùng chuột để xoay và phóng to các vị trí bạn quan tâm.
2.  **Tracking List**: Danh sách các vệ tinh đang hoạt động thời gian thực sẽ hiển thị ở góc màn hình.
3.  **Metrics Bar**: Theo dõi các chỉ số mây và mưa tại khu vực Việt Nam được cập nhật mỗi 10 phút.

### Cài đặt và Chạy thử (Local)
Nếu bạn là kỹ thuật viên và muốn chạy StarWeather trên máy của mình:

```bash
# 1. Cài đặt các thư viện cần thiết
composer install
npm install

# 2. Khởi tạo cơ sở dữ liệu
php artisan migrate --seed

# 3. Chạy hệ thống (Mở 2 Terminal)
php artisan serve  # Web Server
npm run dev        # Giao diện Vue 3
```

---

##  Lộ trình sắp tới
Mình đang làm việc không ngừng nghỉ để đưa thêm dữ liệu từ các **Radar thời tiết** và triển khai hệ thống **Cảnh báo sớm qua Email/Sms** trong các phiên bản tới.

---
**Một sản phẩm đầy tâm huyết của Mình**  
*Cảm ơn bạn đã đồng hành cùng StarWeather trên con đường chinh phục dữ liệu Trái đất!*
