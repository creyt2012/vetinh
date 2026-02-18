# Phân Tích Thuật Toán & Mô Hình Toán Học Nâng Cao

Hệ thống StarWeather được vận hành bởi các mô hình hàng không vũ trụ và vật lý khí quyển tiêu chuẩn quốc tế, đảm bảo tính nhất quán và độ chính xác của dữ liệu đầu ra.

---

## 🛰️ 1. Động Lực Học Quỹ Đạo Vệ Tinh (Orbital Dynamics)

Hệ thống sử dụng các phương pháp mô phỏng số để xác định trạng thái của vệ tinh trong không gian ba chiều.

### 1.1. Lan Truyền SGP4 (Simplified General Perturbations)
Thuật toán SGP4 giải quyết các phương trình vi phân chuyển động có tính đến các lực nhiễu loạn chính:
- **Độ dẹt của Trái đất ($J_2$)**: Ảnh hưởng của hình dạng không cầu đối với quỹ đạo.
- **Lực cản khí quyển**: Đặc biệt quan trọng đối với các vệ tinh ở quỹ đạo thấp (LEO).

**Các tham số đầu vào chính từ TLE:**
- **Độ nghiêng ($i$)**: Góc giữa mặt phẳng quỹ đạo và mặt phẳng xích đạo.
- **RAAN ($\Omega$)**: Kinh độ của nút lên, xác định hướng của mặt phẳng quỹ đạo trong không gian.
- **Độ lệch tâm ($e$)**: Độ "méo" của quỹ đạo (0 cho hình tròn hoàn hảo).

### 1.2. Tính Toán Vận Tốc Tức Thời (Vis-Viva Equation)
Tốc độ của vệ tinh tại bất kỳ điểm nào trên quỹ đạo được tính bằng hàm số của khoảng cách đến tâm Trái đất:
$$v = \sqrt{\mu \left(2/r - 1/a \right)}$$
Trong đó:
- $\mu$: Hằng số trọng trường Trái đất ($398600.44\text{ km}^3/\text{s}^2$).
- $r$: Khoảng cách tức thời từ vệ tinh đến tâm địa cầu.
- $a$: Bán trục lớn của quỹ đạo elip.

### 1.3. Hệ Quy Chiếu & Bù Trừ Chuyển Động Quay WGS84
Do Trái đất quay quanh trục của nó, một điểm cố định trong không gian Inertial (ECI) sẽ có tọa độ địa lý thay đổi theo thời gian. Chúng tôi sử dụng **Giờ Sidereal Trung bình tại Greenwich (GMST)** để thực hiện phép xoay tọa độ:
$$lng = \alpha - GMST$$
trong đó $\alpha$ là độ thăng thiên thẳng (Right Ascension) của vệ tinh.

---

## 🌩️ 2. Xử Lý Phổ Khí Tượng & Hợp Nhất Dữ Liệu (Data Fusion)

### 2.1. Phân Tích Băng Thông Đa Phổ Himawari
Dữ liệu từ cảm biến AHI (Advanced Himawari Imager) được xử lý qua hai kênh chính:
- **Kênh Hồng Ngoại (Băng 13 - 10.4µm)**: Dùng để xác định nhiệt độ bức xạ của đỉnh mây. Nhiệt độ càng thấp tương ứng với mây càng cao và dày (nguy cơ bão lớn).
- **Kênh Khả Kiến (Băng 3 - 0.64µm)**: Dùng để phân tích cấu trúc bề mặt mây và độ phản xạ Albedo.

### 2.2. Thuật Toán Mosaic Radar XYZ
Để duy trì hiệu năng hiển thị, dữ liệu radar lượng mưa được phân phối dưới dạng các mảnh (tiles) 256x256 pixel. Hệ thống sử dụng thuật toán nội suy song tuyến tính (Bilinear Interpolation) để đảm bảo các cạnh của các mảnh radar khớp nhau hoàn hảo trên địa cầu 3D.

---

## ⛈️ 3. Định Danh Xoáy Thuận & Dự Báo (Vortex ID)

Hệ thống triển khai một công cụ quét tự động (`StormTrackingService`) để phát hiện các bất thường khí quyển:
- **Phân tích Gradient**: Tính toán tốc độ thay đổi áp suất theo thời gian ($dP/dt$).
- **Mô Hình Nội Suy Vectơ**: Dự báo quỹ đạo dựa trên hướng di chuyển lịch sử và các trường dòng chảy khí quyển tầng cao.
