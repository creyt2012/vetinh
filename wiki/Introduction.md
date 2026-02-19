# Tài liệu StarWeather - Tổng quan Hệ thống

![Global View](images/global_view.png)

Chào mừng bạn đến với tài liệu kỹ thuật chính thức của StarWeather. Đây là nền tảng phân tích dữ liệu vệ tinh và dự báo thời tiết chuyên sâu, được thiết kế để phục vụ các yêu cầu khắt khe của doanh nghiệp và tổ chức khí tượng.

## Tầm nhìn dự án

StarWeather được xây dựng với mục tiêu cung cấp một cái nhìn toàn diện và chính xác về trạng thái của trái đất thông qua việc kết hợp dữ liệu từ nhiều nguồn vệ tinh và quan trắc mặt đất. Hệ thống không chỉ dừng lại ở việc hiển thị dữ liệu mà còn thực hiện các phép tính toán phức tạp để đưa ra các chỉ số rủi ro và dự báo ngắn hạn.

## Phân khúc khách hàng

Hệ thống được thiết kế theo mô hình SaaS (Software as a Service), hỗ trợ đa người dùng (multi-tenancy) với các gói dịch vụ linh hoạt:

1. Gói Miễn phí (FREE): Dành cho cá nhân nghiên cứu với các chỉ số cơ bản.
2. Gói Chuyên nghiệp (PRO): Cung cấp dữ liệu cập nhật nhanh hơn và quyền truy cập API mở rộng.

![Intelligence Dashboard](images/intelligence_dashboard.png)

3. Gói Doanh nghiệp (ENTERPRISE): Toàn bộ tính năng, bao gồm dữ liệu vệ tinh Sentinel-1/2 và hỗ trợ kỹ thuật ưu tiên.
4. Gói Chính phủ (GOVERNMENT): Tích hợp sâu các radar thời tiết và hệ thống cảnh báo cấp quốc gia.

## Các thành phần chính của hệ thống

Hệ thống được chia thành ba lớp kiến trúc chính:

1. Lớp Thu thập Dữ liệu (Ingestion Layer): Chịu trách nhiệm kết nối và lấy dữ liệu thô từ các cơ quan hàng không vũ trụ và khí tượng.
2. Lớp Xử lý và Phân tích (Analytics & Intelligence): Sử dụng các thuật toán SGP4 và các mô hình vật lý khí tượng để chuyển đổi dữ liệu thô thành thông tin có ích.
3. Lớp Phân phối (Delivery Layer): Cung cấp thông qua giao diện Web 3D trực quan và các đầu cuối API chuẩn RESTful.
