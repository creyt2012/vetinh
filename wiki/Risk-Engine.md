# Risk Engine: The Mathematical Foundation of Alerts

![Risk Analytics Dashboard](../public/assets/docs/images/dashboard_mockup.png)

The Risk Engine is a deterministic scoring system that converts raw sensor and satellite telemetry into a human-readable **Risk Score ($R$)**.

---

## ⚖️ The Scoring Formula

The risk score is a composite value from 0-100, calculated using a weighted linear combination:

$$R = \sum_{i=1}^{n} (w_i \cdot s_i)$$

Where:
- $w_i$ = Weight of the specific metric.
- $s_i$ = Normalized segment score (0-100).

### Weighted Components
| Metric ($i$) | Weight ($w_i$) | Normalization Logic |
|---|---|---|
| **Cloud Coverage** | 0.25 | Percentage of pixels in the grey-spectrum above threshold. |
| **Spectral Density** | 0.15 | Infrared brightness temperature deviation. |
| **Precipitation** | 0.30 | Derived from radar dBZ or Himawari water-vapor bands. |
| **Gradient Delta** | 0.20 | Rate of change in metric values over the last 60 minutes. |
| **Pressure Delta** | 0.10 | Deviation from standard atmospheric pressure (1013.25 hPa). |

---

## 📶 Confidence Scoring (Data Quality)

To prevent false positives, every Risk Score is accompanied by a **Confidence Metric ($C$)**:

$$C = F_{score} \cdot P_{score}$$

1. **Freshness ($F$)**: Decays exponentially based on time since last update ($T$):
   $F = e^{- \lambda \cdot T}$ (where $\lambda$ is the decay constant for the specific data source).
2. **Provenance ($P$)**: Increases based on the number of independent data sources confirming the trend (Himawari + Ground Radar + IoT).

---

## 🚨 Alert Escalation logic

- **Level 1 (Low)**: $R < 40$. Standard periodic updates.
- **Level 2 (Medium)**: $40 \le R < 60$. Increase polling frequency to 5 minutes.
- **Level 3 (High)**: $60 \le R < 80$. Trigger WebSocket broadcast to affected zone users.
- **Level 4 (Critical)**: $R \ge 80$. Immediate push notification/SMS dispatch and mission control override.
