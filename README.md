# 🌌 Hệ Thống StarWeather (Dự án Vệ Tinh)
## Nền Tảng Phân Tích Cơ Học Quỹ Đạo & Trí Tuệ Khí Tượng Tổng Hợp Cấp Liên Bang

![Bảng điều khiển Trung tâm Nhiệm vụ StarWeather](public/assets/docs/images/mission_control_terminal.png)

[![Tiêu chuẩn Hàng không Vũ trụ](https://img.shields.io/badge/Chuẩn-SGP4/WGS84-blue?style=for-the-badge)](https://en.wikipedia.org/wiki/Simplified_perturbations_models)
[![Nền tảng Laravel](https://img.shields.io/badge/Framework-Laravel_11_Enterprise-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Đồ họa Real-time](https://img.shields.io/badge/Graphics-Three.js_WebGL-black?style=for-the-badge&logo=three.js)](https://threejs.org)

**StarWeather** là một hệ sinh thái phân tích dữ liệu quy mô lớn, được thiết kế để giải quyết các bài toán phức tạp về sự giao thoa giữa cơ học thiên thể và động lực học khí quyển. Hệ thống cung cấp một giải pháp hợp nhất (Unified Solution) cho việc theo dõi vật thể quỹ đạo thấp (LEO) và phân tích các hiện tượng thời tiết cực đoan dựa trên dữ liệu đa phổ thời gian thực.

---

## 🏛️ Kiến Trúc Hệ Thống & Phân Lớp Kỹ Thuật

Dự án được xây dựng trên triết lý **"Data-Centric Architecture"**, ưu tiên xử lý dữ liệu hiệu năng cao mà không phụ thuộc vào ảo hóa (Non-Dockerized) để tận dụng tối đa tài nguyên phần cứng I/O.

### 1. Phân Lớp Thu Thập & Chuyển Đổi (Ingestion & ETL)
Hệ thống triển khai các Pipeline dữ liệu tự động cho phép hội tụ dữ liệu từ các nguồn phi tập trung:
- **Orbital Ingestion**: Tự động đồng bộ hóa các bộ phần tử hai dòng (TLE) từ NORAD qua giao thức API an toàn.
- **Meteorological Stream**: Phân tích luồng ảnh từ vệ tinh địa tĩnh Himawari-9 (JMA) với tần suất 10 phút/lần.
- **Radar Mosaic integration**: Hợp nhất các mảnh radar (tiles) từ RainViewer để tạo ra một bản đồ lượng mưa toàn cầu không vết cắt.

### 2. Engine Tính Toán Động Lực Học (Computational Dynamics Engine)
Linh hồn của StarWeather nằm ở các mô hình toán học thuần túy được tối ưu hóa bằng PHP 8.3 JIT:

#### 🛰️ Cơ Học Quỹ Đạo Vệ Tinh (Aerospace Mechanics)
- **Mô Hình Lan Truyền SGP4**: Giải các phương trình nhiễu loạn để dự báo vị trí vệ tinh. Thuật toán xử lý các tham số Keplerian (Độ nghiêng, Độ lệch tâm, RAAN) để xác định vector trạng thái $(r, v)$ trong hệ quy chiếu ECI.
- **Phương Trình Vis-Viva**: Tính toán vận tốc quỹ đạo tức thời:
  $$v = \sqrt{\mu \left( \frac{2}{r} - \frac{1}{a} \right)}$$
- **Hệ Quy Chiếu WGS84**: Chuyển đổi tọa độ từ không gian ECI sang địa lý Lat/Lng/Alt bằng cách sử dụng các hằng số định hình Trái đất (Bán trục lớn $a = 6378.137\text{ km}$, Độ dẹt $f = 1/298.257$).

![Phác họa mạng lưới vệ tinh bảo phủ Trái đất](public/assets/docs/images/constellation_view.png)

#### 🌡️ Vật Lý Khí Tượng & Phân Tích Đa Phổ
- **Phân Tích Băng Thông Himawari**: Hệ thống xử lý các dải phổ Hồng Ngoại (IR) để xác định nhiệt độ đỉnh mây. Thuật toán **Spectral Normalization** giúp phân lập các vùng có nguy cơ tạo xoáy.
- **Nhận Dạng Vortex**: Sử dụng các phương pháp phân tích gradient áp suất và trường vận tốc gió để xác định tâm bão.
- **Dự Báo Quỹ Đạo Bão**: Áp dụng mô hình nội suy vectơ (Vector Interpolation) trên chuỗi thời gian để phác thảo lộ trình di chuyển tiềm năng.

![Phân tích đa phổ và đo đạc nhiệt độ khí quyển](public/assets/docs/images/spectral_analysis.png)

### 3. Engine Đánh Giá Rủi Ro Định Lượng (Deterministic Risk Engine)
Hệ thống không dựa trên cảm tính, mà sử dụng các công thức toán học để định lượng rủi ro:
- **Hàm Tổng Trọng Số (Weighted Sum Function)**:
  $$RiskScore = \sum (Weight_i \times NormalizedValue_i)$$
- **Chỉ Số Tin Cậy (Confidence Index)**: Mỗi điểm rủi ro được gán một mức độ tin cậy dựa trên độ mới của dữ liệu (Data Freshness Score) và sự hội tụ giữa các nguồn cảm biến khác nhau.

---

## 💻 Công Nghệ Nền Tảng (Core Stack)

| Lớp (Layer) | Công Nghệ & Tiêu Chuẩn |
|---|---|
| **Back-end Core** | Laravel 11 (Skeleton tối ưu cho Enterprise), PHP 8.3 JIT |
| **Real-time Pipeline** | Laravel Reverb (Giao thức WebSocket tốc độ cao cho dữ liệu quỹ đạo) |
| **Data Persistence** | MySQL 8.0 (Partitioned Tables), Redis (L1 State Cache) |
| **Front-end / GIS** | Vue 3, Inertia.js, Three.js (WebGL Engine) |
| **GIS Visuals** | Globe.gl (UV Spherical Mapping cho dữ liệu WGS84) |

---

## 🛠️ Hướng Dẫn Triển Khai Hệ Thống (Deployment)

### Yêu Cầu Hạ Tầng
- **PHP**: Phiên bản 8.2 trở lên với các extension: `bcmath`, `gmp`, `redis`.
- **Database**: MySQL 8.0 với hỗ trợ JSON/Spatial.
- **Memory Store**: Redis server để quản lý hàng đợi và cache trạng thái.

### Quy Trình Cài Đặt
```bash
# Bước 1: Khởi tạo mã nguồn và thư viện
git clone https://github.com/creyt2012/vetinh.git
composer install && npm install

# Bước 2: Thiết lập tham số môi trường
cp .env.example .env
php artisan key:generate

# Bước 3: Di cư cơ sở dữ liệu và nạp dữ liệu nền tảng
php artisan migrate --seed

# Bước 4: Khởi chạy hệ thống tích hợp (Concurrently)
# Chạy đồng thời Web Server, Queue Worker và Vite Compiler
npm run dev
```

---

## 📊 Lộ Trình Phát Triển (Scientific Roadmap)
- [ ] Tích hợp hệ thống máy học (LSTM) để dự báo quỹ đạo bão phi tuyến tính.
- [ ] Triển khai đo đạc mật độ Plasma tầng điện ly để phân tích ảnh hưởng đến tín hiệu liên lạc vệ tinh.
- [ ] Dashboard dành riêng cho các cơ quan ứng phó thiên tai chuyên sâu.

---
**Một sản phẩm nghiên cứu và phát triển bởi Đội ngũ Kỹ thuật StarWeather Core.**  
*Tận dụng sức mạnh trí tuệ không gian để bảo vệ sự sống trên bề mặt hành tinh.*
