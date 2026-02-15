# Tài liệu API (V1)

Hệ thống StarWeather cung cấp API RESTful phục vụ cho việc tích hợp vào các hệ thống bên thứ ba. Tất cả các yêu cầu đều yêu cầu xác thực thông qua Header.

## Xác thực (Authentication)

Mỗi yêu cầu cần bao gồm API Key trong header:

```text
X-API-KEY: your_api_key_here
```

## Các đầu cuối chính (Endpoints)

### 1. Trạng thái Trực tiếp (Live State)
Lấy dữ liệu thời tiết và vệ tinh mới nhất đã được tổng hợp.

- URL: `/api/v1/live/state`
- Phương thức: GET
- Trả về: Đối tượng Unified Weather State JSON.

### 2. Dữ liệu Vệ tinh (Satellite Data)
Thông tin quỹ đạo và vị trí hiện tại của các vệ tinh đang theo dõi.

- URL: `/api/v1/satellite/live`
- Phương thức: GET

### 3. Đánh giá Rủi ro (Risk Assessment)
Lấy chỉ số rủi ro cho một tọa độ cụ thể.

- URL: `/api/v1/weather/risk?lat={latitude}&lon={longitude}`
- Phương thức: GET

## Giới hạn Băng thông (Rate Limiting)

Hệ thống áp dụng giới hạn dựa trên gói dịch vụ của Tenant:

- FREE: 10 yêu cầu/phút.
- PRO: 1.000 yêu cầu/phút.
- ENTERPRISE: Không giới hạn (tùy thuộc vào SLA).
