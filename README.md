# StarWeather - Nền tảng Trí tuệ Vệ tinh & Trái đất Thời gian thực

StarWeather là một nền tảng SaaS thương mại cấp Enterprise, cung cấp khả năng theo dõi vệ tinh thời gian thực và phân tích dữ liệu thời tiết thông minh. Hệ thống kết hợp dữ liệu từ các vệ tinh khí tượng (Himawari), dữ liệu Sentinel (Copernicus), và dữ liệu quỹ đạo NORAD để cung cấp các dự báo, cảnh bảo rủi ro và API cho nhà phát triển.

## 🛰️ Tính năng cốt lõi

### 1. Công cụ Vệ tinh Thời gian thực
- Theo dõi vị trí vệ tinh (Starlink, ISS, Sentinel, Himawari,...) mỗi 3 giây.
- Sử dụng thuật toán SGP4 để tính toán quỹ đạo chính xác.
- Hiển thị độ cao, vận tốc và bán kính hiển thị trên bản đồ 3D.

### 2. Trí tuệ Thời tiết & Ảnh Vệ tinh
- Tự động lấy ảnh từ vệ tinh Himawari-9 mỗi 10 phút.
- Xử lý ảnh để trích xuất độ phủ mây, mật độ mây và ước tính lượng mưa.
- Phân đoạn mây và xác định các vùng đối lưu mạnh tại khu vực Việt Nam.

### 3. Hệ thống Đánh giá Rủi ro (Risk Engine)
- Tính toán điểm rủi ro thiên tai (0-100) dựa trên fusion dữ liệu đa nguồn.
- Phân loại mức độ rủi ro: **LOW**, **MEDIUM**, **HIGH**, **CRITICAL**.
- Phát hiện các bất thường về áp suất và sự phát triển bất thường của mây bão.

### 4. Giao diện 3D Globe Cao cấp
- Quả địa cầu 3D tương tác xây dựng bằng Three.js.
- Lớp phủ mây thời gian thực và quỹ đạo vệ tinh sống động.
- Bảng điều khiển (Dashboard) mang phong cách Glassmorphism, hiện đại và tối ưu trải nghiệm người dùng.

### 5. API cho Nhà phát triển
- Hệ thống API v1 hoàn chỉnh với xác thực API Key.
- Giới hạn băng thông (Rate limiting) theo gói đăng ký (SaaS model).
- Tài liệu tích hợp sẵn cho việc truy xuất dữ liệu thời tiết và vệ tinh.

## 🛠️ Công nghệ sử dụng

- **Backend**: Laravel 11 (PHP 8.3)
- **Frontend**: Vue 3, Vite, TailwindCSS
- **3D Engine**: Three.js
- **Real-time**: Laravel Reverb (WebSocket)
- **Database**: MySQL 8.0 & Redis
- **Queue/Worker**: Laravel Horizon
- **Monitoring**: Laravel Pulse

## 🚀 Hướng dẫn cài đặt địa phương (Local Setup)

Dự án này được tối ưu hóa để chạy trực tiếp trên môi trường MacOS/Linux mà không cần Docker (theo yêu cầu của kiến trúc sư).

### 1. Yêu cầu hệ thống
- PHP 8.3+
- Composer
- Node.js & NPM
- MySQL 8.0+
- Redis

### 2. Các bước cài đặt

```bash
# Clone repository
git clone https://github.com/creyt2012/vetinh.git
cd vetinh

# Cài đặt PHP dependencies
composer install

# Cài đặt JS dependencies
npm install

# Cấu hình môi trường
cp .env.example .env
php artisan key:generate

# Cấu hình Database trong .env sau đó chạy migration và seeder
php artisan migrate --seed
```

### 3. Chạy ứng dụng

Mở 3 cửa sổ terminal riêng biệt:

- **Terminal 1 (Web Server)**: `php artisan serve`
- **Terminal 2 (Vite Dev)**: `npm run dev`
- **Terminal 3 (Worker & Scheduler)**:
  ```bash
  php artisan horizon
  # và một terminal khác để chạy scheduler
  php artisan schedule:work
  ```

## 🔄 Tự động hóa GitHub (Auto-push)

Dự án tích hợp sẵn script `git-autopush.sh`. Script này sử dụng `fswatch` để theo dõi sự thay đổi của file và tự động commit/push lên GitHub ngay lập tức.

Để khởi động:
```bash
nohup ./git-autopush.sh > git-autopush.log 2>&1 &
```

##  Lộ trình phát triển (Roadmap)
- **Tháng 1-2**: Hoàn thiện nền tảng SaaS và Ingestion cơ bản (Đã hoàn thành).
- **Tháng 3-4**: Tích hợp Radar thời tiết và dữ liệu trạm mặt đất.
- **Tháng 5-6**: Triển khai mô hình Nowcasting (dự báo ngắn hạn 0-2h) và hệ thống Billing thương mại.

---
**Phát triển bởi creyt2012**  
*Kiến trúc sư hệ thống vệ tinh & Kỹ sư Laravel Enterprise*
