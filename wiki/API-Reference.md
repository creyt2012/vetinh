# API Reference (V1)

Hệ thống StarWeather cung cấp API RESTful toàn diện để truy cập dữ liệu khí tượng, viễn thám và quản lý nhiệm vụ.

## [AUTH] Xác thực (Authentication)

Tất cả các yêu cầu yêu cầu Header `X-API-KEY`. Bạn có thể quản lý khóa API trong phần Portals của mình.

```http
X-API-KEY: your_api_key_here
```

---

## [SAT] Vệ tinh & Quỹ đạo (Satellites)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/satellites/live` | GET | Vị trí thời gian thực của toàn bộ đội ngũ vệ tinh. |
| `/api/v1/satellites/conjunctions` | GET | Danh sách các tiếp cận gần (Close approach) và nguy cơ va chạm. |
| `/api/v1/satellites/{id}/telemetry` | GET | Dữ liệu viễn thám chi tiết (Vận tốc, độ cao, góc nghiêng). |
| `/api/v1/satellites/{id}/tle` | GET | Bộ phần tử quỹ đạo Two-Line Element sets mới nhất. |

---

## [MET] Khí tượng & Theo dõi Bão (Weather & Storms)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/weather/latest` | GET | Các chỉ số khí tượng mới nhất từ mạng lưới cảm biến. |
| `/api/v1/weather/metrics` | GET | Truy cập lịch sử dữ liệu (Time-series metrics). |
| `/api/v1/weather/forecast` | GET | Dự báo khí tượng dựa trên mô hình AI (48h). |
| `/api/v1/weather/heatmap` | GET | Dữ liệu mật độ mây và lượng mưa cho bản đồ nhiệt. |
| `/api/v1/weather/storms` | GET | Theo dõi danh sách các cơn bão đang hoạt động. |
| `/api/v1/weather/storms/{id}/vortex` | GET | Phân tích sâu cấu trúc Vortex và tính toàn vẹn vật lý. |
| `/api/v1/weather/risk-areas` | GET | Các vùng nguy hiểm và khu vực sơ tán chiến thuật. |
| `/api/v1/weather/ground-stations` | GET/POST | Quản lý hoặc liệt kê các trạm thu phát mặt đất. |

---

## [ALRT] Cảnh báo (Alerting)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/alerts/rules` | GET/POST | Quản lý danh sách quy tắc (Intelligence Condition Engine). |
| `/api/v1/alerts/rules/{id}` | GET/PUT/DELETE| Chi tiết và cập nhật logic cảnh báo. |
| `/api/v1/alerts/history` | GET | Nhật ký toàn bộ các thông báo đã gửi đi. |

---

## [OPS] Hàng hải (Marine Intelligence)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/marine/vessels` | GET | Theo dõi tàu thuyền tích hợp dữ liệu AIS-mesh. |

---

## [OPS] Quản lý Nhiệm vụ & Báo cáo (Mission Control)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/mission-control/files` | GET | Danh sách tệp tin liên quan đến các nhiệm vụ không gian. |
| `/api/v1/mission-control/upload` | POST | Tải lên dữ liệu lên trung tâm kiểm soát nhiệm vụ. |
| `/api/v1/reports` | GET | Thư viện báo cáo khoa học và phân tích khí tượng định kỳ. |

---

## [INT] API Bản đồ Nội bộ (Internal Map APIs)

Được sử dụng bởi dashboard chính để hiển thị dữ liệu thời gian thực mà không bị giới hạn bởi Rate Limit thông thường. Yêu cầu tham số `token`.

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/internal-map/satellites` | GET | Dữ liệu vị trí và quỹ đạo tối ưu cho rendering. |
| `/api/internal-map/storms` | GET | Danh sách các cơn bão đang hoạt động. |
| `/api/internal-map/ground-stations`| GET | Tọa độ các trạm mặt đất. |
| `/api/internal-map/point-info` | GET | Thông tin khí tượng tại một điểm cụ thể. |
| `/api/internal-map/forecast` | GET | Dữ liệu dự báo thô cho biểu đồ Meteogram. |

---

## [AI] Microservice Phân tích Ảnh (AI Core)

Dịch vụ độc lập xử lý dữ liệu hình ảnh từ vệ tinh. Chạy tại cổng `:8001`.

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `POST /analyze` | POST | Phân tích ảnh vệ tinh (Input: File, Lat, Lng). Trả về temp, pressure, wind. |
| `GET /` | GET | Kiểm tra trạng thái microservice. |

---

## [SYS] Sức khỏe Hệ thống (System Health)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/health` | GET | Trạng thái sẵn sàng cơ bản (Health Check). |
| `/api/v1/health/system` | GET | Chỉ số hiệu năng (Latency, Uptime) của Database, Redis, Gateways. |

---

## [FIN] Thanh toán (Billing)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/plans` | GET | Danh sách các gói dịch vụ và giới hạn băng thông. |
| `/api/v1/payments/checkout` | POST | Khởi tạo quy trình thanh toán nâng cấp tài khoản. |

---

- **ENTERPRISE**: Tùy chỉnh theo SLA.

---
[[Về Trang Chủ|Home]] | [[Kiến Trúc Hệ Thống|Architecture]] | [[Mô Hình Toán Học|Algorithms]]
