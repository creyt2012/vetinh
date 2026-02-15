# Công cụ Đánh giá Rủi ro (Risk Engine)

Risk Engine là linh hồn của nền tảng StarWeather, chịu trách nhiệm chuyển đổi các dữ liệu số học thô thành các cảnh báo có ý nghĩa thực tiễn.

## Phương pháp tính toán

Điểm rủi ro (Risk Score) là một giá trị từ 0 đến 100, được tổng hợp dựa trên các trọng số sau:

| Chỉ số | Trọng số | Mô tả |
|--------|----------|-------|
| Độ phủ mây | 25% | Tỷ lệ mây bao phủ trên khu vực tracked. |
| Mật độ mây | 15% | Độ dày và đặc tính của lớp mây qua ảnh hồng ngoại. |
| Cường độ mưa | 30% | Lượng mưa ước tính từ ảnh vệ tinh và radar. |
| Tốc độ thay đổi mây | 20% | Tốc độ phát triển của các khối mây trong 30-60 phút. |
| Áp suất khí quyển | 10% | Các biến động về áp suất bất thường. |

## Phân cấp mức độ (Severity Levels)

Hệ thống tự động phân loại rủi ro dựa trên điểm số cuối cùng:

- Thấp (LOW): 0 - 40. Điều kiện thời tiết bình thường.
- Trung bình (MEDIUM): 41 - 60. Có dấu hiệu thay đổi thời tiết, cần theo dõi.
- Cao (HIGH): 61 - 80. Điều kiện thời tiết nguy hiểm, có khả năng xảy ra dông lốc, mưa lớn.
- Nguy cấp (CRITICAL): 81 - 100. Các hiện tượng mây bão hình thành mạnh, cần cảnh báo tức thời.

## Độ tin cậy (Confidence Score)

Mỗi kết quả tính toán đều đi kèm với một điểm tin cậy. Điểm này phụ thuộc vào:
- Độ mới của dữ liệu (Data freshness).
- Số lượng nguồn dữ liệu đồng thuận (Data provenance consensus).
- Chất lượng tín hiệu từ các sensor/trạm mặt đất.
