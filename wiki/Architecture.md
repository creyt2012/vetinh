# Kiến trúc Hệ thống StarWeather

StarWeather được xây dựng dựa trên nền tảng Laravel 11, tối ưu hóa cho hiệu suất cao và khả năng mở rộng quy mô lớn (Enterprise Scale).

## Công nghệ nền tảng

Cấu trúc công nghệ được lựa chọn để đảm bảo tính ổn định tối đa mà không phụ thuộc vào ảo hóa (Non-Dockerized approach):

- Ngôn ngữ: PHP 8.3 với sự hỗ trợ của bộ nhớ đệm và xử lý luồng (FPM).
- Cơ sở dữ liệu: MySQL 8.0 cho dữ liệu quan hệ và Redis cho dữ liệu luồng tốc độ cao.
- Xử lý hàng đợi: Laravel Horizon quản lý hàng ngàn công việc xử lý dữ liệu mỗi giây.
- Truyền tải thời gian thực: Laravel Reverb sử dụng giao thức WebSocket cho các cập nhật quỹ đạo vệ tinh.

## Quy trình xử lý dữ liệu (Data Pipeline)

Luồng dữ liệu trong StarWeather tuân thủ quy tắc "Data Fusion" - kết hợp nhiều nguồn tin để đạt độ chính xác cao nhất:

1. Thu thập: Các Jobs trong Laravel Horizon thực hiện lấy dữ liệu từ Himawari (JMA), Sentinel (Copernicus) và NORAD.
2. Chuẩn hóa: Dữ liệu được đưa về định dạng Unified Weather State để dễ dàng truy vấn.
3. Tính toán: Engine Analytics thực hiện tính toán độ phủ mây, lượng mưa và điểm rủi ro.
4. Phân phối: Dữ liệu được lưu vào MySQL, đẩy lên Redis Cache và phát sóng qua WebSocket tới người dùng cuối.

## Chiến lược mở rộng (Scaling Strategy)

Để hỗ trợ hàng triệu yêu cầu API mỗi ngày và hàng trăm nghìn người dùng đồng thời:

- Phân cấp Caching: Sử dụng Redis L1 để lưu trữ dữ liệu trạng thái mới nhất và CDN L2 cho các tệp tin hình ảnh vệ tinh nặng.
- Tách biệt luồng xử lý: Các tác vụ tính toán nặng được thực hiện ở các hàng đợi ưu tiên (Priority Queues) tách biệt với luồng xử lý yêu cầu người dùng.
- Phân vùng dữ liệu: Bảng ghi số liệu khí tượng (weather_metrics) được thiết kế để hỗ trợ phân vùng (partitioning) theo thời gian.
