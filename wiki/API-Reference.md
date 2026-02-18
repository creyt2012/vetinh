# API Reference (V1 Full Catalog)

Hệ thống StarWeather cung cấp các giao diện lập trình ứng dụng (API) chuẩn RESTful. Dưới đây là danh mục chi tiết toàn bộ các đầu cuối (endpoints) hiện có.

## [AUTH] Xác Thực (Authentication)

Tất cả các yêu cầu yêu cầu Header `X-API-KEY`. Bạn có thể quản lý khóa API trong phần Portals của mình.

```http
X-API-KEY: your_api_key_here
```

---

## [LIVE] Trạng thái Hệ thống (Live State)

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `GET` | `/api/v1/live/state` | Trạng thái tổng quát của mạng lưới cảm biến và vệ tinh. |
| `GET` | `/api/v1/health` | Kiểm tra trạng thái sẵn sàng (Liveness check). |
| `GET` | `/api/v1/health/system`| Chỉ số chi tiết về hạ tầng (DB, Redis, RAM). |

---

## [SAT] Vệ Tinh & Quỹ Đạo (Satellites)

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `GET` | `/api/v1/satellites/live` | Danh sách toàn bộ vệ tinh và vị trí hiện tại. |
| `GET` | `/api/v1/satellites/conjunctions` | Cảnh báo các điểm giao cắt quỹ đạo nguy hiểm. |
| `GET` | `/api/v1/satellites/{id}/telemetry` | Dữ liệu viễn thám thời gian thực của 1 vệ tinh. |
| `GET` | `/api/v1/satellites/imagery-history` | Lịch sử ảnh chụp từ vệ tinh (Time-machine). |
| `GET` | `/api/v1/satellites/{id}/tle` | Dữ liệu TLE (Two-Line Element) thô của vệ tinh. |

---

## [MET] Khí Tượng & Dự Báo (Weather)

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `GET` | `/api/v1/weather/latest` | Chỉ số khí tượng mới nhất từ sensor gần nhất. |
| `GET` | `/api/v1/weather/metrics` | Truy vấn dữ liệu lịch sử theo thời gian. |
| `GET` | `/api/v1/weather/ground-stations`| Danh sách và trạng thái các trạm mặt đất. |
| `GET` | `/api/v1/weather/history` | Lịch sử khí tượng chi tiết tại một tọa độ. |
| `GET` | `/api/v1/weather/heatmap` | Dữ liệu mật độ phân bổ cho bản đồ nhiệt. |
| `GET` | `/api/v1/weather/forecast` | Dự báo AI cho 48 giờ tới (Hourly). |
| `GET` | `/api/v1/weather/point-info` | Phân tích sâu tại một điểm (SST, AQI, UV). |
| `GET` | `/api/v1/weather/trends` | Xu hướng biến đổi khí hậu trong 30 ngày qua. |

---

## [STORM] Theo Dõi Thiên Tai (Storms & Risk)

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `GET` | `/api/v1/weather/storms` | Danh sách các áp thấp và bão đang hoạt động. |
| `GET` | `/api/v1/weather/storms/{id}` | Thông số chi tiết về sức gió, lộ trình bão. |
| `GET` | `/api/v1/weather/storms/{id}/vortex`| Phân tích cấu trúc lõi và mắt bão. |
| `GET` | `/api/v1/weather/risk-areas` | Các khu vực nằm trong vùng cảnh báo đỏ. |

---

## [ALRT] Logic Cảnh Báo (Alerts)

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `GET` | `/api/v1/alerts/rules` | Danh sách các quy tắc cảnh báo (Condition Engine). |
| `POST`| `/api/v1/alerts/rules` | Tạo mới một quy tắc logic cảnh báo. |
| `GET` | `/api/v1/alerts/history` | Nhật ký các thông báo đã gửi cho người dùng. |

---

## [OPS] Quan ly Nhiem vu (Mission Control)

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `GET` | `/api/v1/mission-control/files` | Quản lý tệp tin truyền từ vệ tinh về trạm. |
| `POST`| `/api/v1/mission-control/upload`| Tải tệp tin lên trung tâm dữ liệu. |
| `GET` | `/api/v1/reports` | Kho báo cáo khoa học định kỳ (PDF/JSON). |
| `GET` | `/api/v1/reports/{file}/download`| Tải xuống báo cáo chi tiết. |

---

## [FIN] Thanh toan & Hang hai (Billing & Marine)

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `GET` | `/api/v1/marine/vessels` | Theo dõi tàu thuyền tích hợp dữ liệu AIS. |
| `GET` | `/api/v1/plans` | Thông tin các gói PRO/Enterprise. |
| `POST`| `/api/v1/payments/checkout` | Khởi tạo giao dịch nâng cấp tài khoản. |

---

## [ADM] Quan ly Ve tinh & Tram (Admin Assets)

Các đầu cuối dành cho khu vực quản trị, yêu cầu quyền `admin`.

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `GET` | `/admin/satellites` | Liệt kê danh sách vệ tinh trong hệ thống quản lý. |
| `POST`| `/admin/satellites` | Đăng ký vệ tinh mới vào mạng lưới. |
| `PUT` | `/admin/satellites/{satellite}` | Cập nhật thông số TLE hoặc trạng thái vệ tinh. |
| `GET` | `/admin/ground-stations` | Quản lý hạ tầng trạm mặt đất toàn cầu. |
| `POST`| `/admin/ground-stations` | Thiết lập trạm thu phát mới. |

---

## [ADM] Quan tri He thong & Nguoi dung (System Admin)

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `GET` | `/admin/users` | Danh sách người dùng và phân quyền truy cập. |
| `POST`| `/admin/users` | Tạo tài khoản người dùng/doanh nghiệp mới. |
| `GET` | `/admin/api-keys` | Quản lý và thu hồi các khóa API của khách hàng. |
| `GET` | `/admin/system/audit-logs` | Nhật ký hoạt động và truy vết thay đổi hệ thống. |
| `GET` | `/admin/system/health` | Giám sát chi tiết SLA và tình trạng phần cứng. |

---

## [ADM] Tai chinh & Canh bao (Billing & Alert Settings)

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `GET` | `/admin/billing` | Quản lý hóa đơn và doanh thu từ các gói SaaS. |
| `GET` | `/admin/alerts/settings` | Cấu hình tham số ngưỡng cho Engine rủi ro. |
| `GET` | `/admin/alerts/rules` | Quản lý các quy tắc logic mặc định toàn hệ thống. |

---

## [INT] API Ban do Chien thuat (Internal Map)

| Endpoint | Mô tả | Tham số |
| :--- | :--- | :--- |
| `/api/internal-map/satellites` | Stream dữ liệu vệ tinh tốc độ cao. | `token` |
| `/api/internal-map/ground-stations`| Render trạm mặt đất. | `token` |
| `/api/internal-map/storms` | Overlay bão thời gian thực. | `token` |
| `/api/internal-map/point-info` | Thông tin điểm click trên Globe. | `lat`, `lng`, `token` |
| `/api/internal-map/forecast` | Forecast cho Meteogram dashboard. | `lat`, `lng`, `token` |

---

## [AI] Microservice AI Core (:8001)

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `POST` | `/analyze` | Phân tích spectral hình ảnh vệ tinh. |
| `GET` | `/` | Liveness & Heartbeat của AI Core. |

---

## [SYS] Ha tang & Van hanh (Infrastructure)

| Phương thức | Endpoint | Mô tả |
| :--- | :--- | :--- |
| `GET` | `/horizon` | Dashboard quản lý hàng đợi và worker. |
| `GET` | `/up` | Laravel Health Check (V8.3+). |
| `GET` | `/sanctum/csrf-cookie` | Khởi tạo cookie xác thực cho SPA/Frontend. |

---
[🏠 Home](Home) | [🏗️ Architecture](Architecture) | [🚀 Algorithms](Algorithms)
