# Engine Đánh Giá Rủi Ro: Cơ Sở Định Lượng Cảnh Báo

Engine Đánh giá Rủi ro (Risk Engine) là thành phần trung tâm của hệ thống StarWeather, chịu trách nhiệm chuyển đổi các dữ liệu đo xa và cảm biến thô thành các chỉ số rủi ro có thể hiểu được bằng phương thức định lượng.

---

## [MODEL] 1. Phướng Pháp Luận Tính Điểm Rủi Ro

Điểm rủi ro không phải là một giá trị định tính mà là kết quả của một hàm tổng trọng số được chuẩn hóa trong khoảng $[0, 100]$.

### 1.1. Công Thức Tổng Quát
$$R = \sum_{i=1}^{n} (w_i \cdot s_i)$$

Trong đó:
- $w_i$: Trọng số của thành phần thứ $i$, phản ánh tầm quan trọng của chỉ số đó đối với rủi ro tổng thể.
- $s_i$: Giá trị đã chuẩn hóa của chỉ số thứ $i$ (thường là từ ảnh vệ tinh hoặc radar).

### 1.2. Phân Bổ Trọng Số Hệ Thống
| Chỉ số ($i$) | Trọng số ($w_i$) | Logic Phân Tích |
|---|---|---|
| **Độ Phủ Mây (Cloud Cover)** | 25% | Tỷ lệ diện tích bề mặt bị che phủ bởi mây dày. |
| **Độ Dày Quang Học (Optical Depth)** | 15% | Độ xuyên thấu của phổ hồng ngoại qua lớp mây. |
| **Cường Độ Lượng Mưa (Rain Rate)** | 30% | Dữ liệu tích hợp từ vệ tinh và mạng lưới radar XYZ. |
| **Biến Thiên Áp Suất (Pressure Delta)** | 10% | Độ lệch so với áp suất chuẩn ($1013.25\text{ hPa}$). |
| **Tốc Độ Thay Đổi (Gradient)** | 20% | Vận tốc phát triển của các khối mây trong 60 phút qua. |

---

## [DATA] 2. Chỉ Số Tin Cậy (Confidence Metric)

Để đảm bảo tính xác thực của cảnh báo, mỗi kết quả tính toán đều đi kèm với một giá trị tin cậy:
$$C = F(t) \cdot P(n)$$

1. **Hàm Suy Giảm Thời Gian (Freshness - $F$)**: Dữ liệu càng cũ, độ tin cậy càng giảm theo hàm mũ $e^{-\lambda t}$.
2. **Sự Đồng Thuận Nguồn (Provenance - $P$)**: Độ tin cậy tăng lên khi có sự xác nhận chéo từ nhiều nguồn (ví dụ: Himawari đồng thuận với Radar mặt đất).

---

## [SEV] 3. Phân Cấp Cảnh Báo & Hành Động (Severity Levels)

- **Mức 1 (An Toàn)**: $R < 40$. Điều kiện môi trường ổn định.
- **Mức 2 (Theo Dõi)**: $40 \le R < 60$. Hệ thống tăng tần suất quét và cập nhật trạng thái mỗi 5 phút.
- **Mức 3 (Nguy Cơ Cao)**: $60 \le R < 80$. Tự động phát sóng WebSocket cho các vùng bị ảnh hưởng.
- **Mức 4 (Nguy Cấp)**: $R \ge 80$. Kích hoạt quy trình cảnh báo khẩn cấp qua SMS/Email và ghi đè các ưu tiên hệ thống.

![Bảng điều khiển Phân tích Rủi ro StarWeather](images/intelligence_dashboard.png)
