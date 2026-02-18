# API Reference (V1)

Hệ thống StarWeather cung cấp API RESTful toàn diện để truy cập dữ liệu khí tượng, viễn thám và quản lý nhiệm vụ.

## 🔐 Xác thực (Authentication)

Tất cả các yêu cầu yêu cầu Header `X-API-KEY`. Bạn có thể quản lý khóa API trong phần Portals của mình.

```http
X-API-KEY: your_api_key_here
```

---

## 🛰️ Vệ tinh & Quỹ đạo (Satellites)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/satellites/live` | GET | Vị trí thời gian thực của toàn bộ đội ngũ vệ tinh. |
| `/api/v1/satellites/conjunctions` | GET | Danh sách các tiếp cận gần (Close approach) và nguy cơ va chạm. |
| `/api/v1/satellites/{id}/telemetry` | GET | Dữ liệu viễn thám chi tiết (Vận tốc, độ cao, góc nghiêng). |
| `/api/v1/satellites/{id}/tle` | GET | Bộ phần tử quỹ đạo Two-Line Element sets mới nhất. |

---

## ⛈️ Khí tượng & Theo dõi Bão (Weather & Storms)

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

## 🔔 Cảnh báo (Alerting)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/alerts/rules` | GET/POST | Quản lý danh sách quy tắc (Intelligence Condition Engine). |
| `/api/v1/alerts/rules/{id}` | GET/PUT/DELETE| Chi tiết và cập nhật logic cảnh báo. |
| `/api/v1/alerts/history` | GET | Nhật ký toàn bộ các thông báo đã gửi đi. |

---

## 🚢 Hàng hải (Marine Intelligence)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/marine/vessels` | GET | Theo dõi tàu thuyền tích hợp dữ liệu AIS-mesh. |

---

## 🚀 Quản lý Nhiệm vụ & Báo cáo (Mission Control)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/mission-control/files` | GET | Danh sách tệp tin liên quan đến các nhiệm vụ không gian. |
| `/api/v1/mission-control/upload` | POST | Tải lên dữ liệu lên trung tâm kiểm soát nhiệm vụ. |
| `/api/v1/reports` | GET | Thư viện báo cáo khoa học và phân tích khí tượng định kỳ. |

---

## ⚙️ Sức khỏe Hệ thống (System Health)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/health` | GET | Trạng thái sẵn sàng cơ bản (Health Check). |
| `/api/v1/health/system` | GET | Chỉ số hiệu năng (Latency, Uptime) của Database, Redis, Gateways. |

---

## 💳 Thanh toán (Billing)

| URL | Phương thức | Mô tả |
| :--- | :--- | :--- |
| `/api/v1/plans` | GET | Danh sách các gói dịch vụ và giới hạn băng thông. |
| `/api/v1/payments/checkout` | POST | Khởi tạo quy trình thanh toán nâng cấp tài khoản. |

---

## Giới hạn Tần suất (Rate Limits)

- **FREE**: 10 yêu cầu/phút.
- **PRO**: 1.000 yêu cầu/phút.
- **ENTERPRISE**: Tùy chỉnh theo SLA.
