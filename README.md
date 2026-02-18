# 🌌 StarWeather (Vetinh)
### Enterprise-Grade Satellite Tracking & Meteorological Intelligence Platform

![StarWeather Globe Visualization](/Users/creytdeveloper/.gemini/antigravity/brain/49cd3ed0-ce2b-49cc-a7bc-a25ea8ff049f/starweather_globe_visualization_1771426474667.png)

[![Laravel 11](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel)](https://laravel.com)
[![Vue 3](https://img.shields.io/badge/Vue-3.x-4FC08D?logo=vue.js)](https://vuejs.org)
[![Three.js](https://img.shields.io/badge/Engine-Three.js-black?logo=three.js)](https://threejs.org)
[![SGP4](https://img.shields.io/badge/Algorithm-SGP4-blue)](https://en.wikipedia.org/wiki/Simplified_perturbations_models)

**StarWeather** is a sophisticated weather intelligence system designed to bridge the gap between orbital precision and terrestrial safety. By fusing real-time satellite telemetry with multi-spectral meteorological data, StarWeather provides actionable insights into atmospheric risks and satellite logistics.

---

## 🚀 Key Capabilities

### 📡 High-Precision Satellite Tracking
- **SGP4 Propagation Engine**: Implements the Standard General Perturbations (SGP4) model to predict satellite orbits (ISS, Starlink, Himawari) with high accuracy using TLE (Two-Line Element) sets.
- **Coordinate Transformation**: Real-time ECI (Earth-Centered Inertial) to Geodetic (Lat/Lng/Alt) conversion, accounting for Earth's rotation and flattening (WGS84).
- **Dynamic 3D Visualization**: Powered by `globe.gl` and `Three.js` for an immersive orbital perspective.

### ⛈️ Advanced Meteorological Intelligence
- **Himawari-9 Multi-Spectral Fusion**: Real-time ingestion and processing of infrared and visible spectrum images from the Japan Meteorological Agency (via NICT).
- **Automated Vortex Identification**: Real-time scanning of pressure and wind speed metrics to detect tropical depressions and storm systems.
- **Predictive Path Tracking**: Linear and non-linear extrapolation of storm trajectories based on historical pressure gradients.

### ⚠️ Intelligent Risk Engine
- **Weighted Scoring Model**: Calculates area-specific risk scores (0-100) based on cloud density, precipitation intensity, and atmospheric pressure volatility.
- **Data Provenance Consensus**: Each risk assessment is accompanied by a confidence score derived from data freshness and sensor agreement.

---

## 🛠️ Technology Stack

| Layer | Technologies |
|---|---|
| **Core** | PHP 8.2+, Laravel 11 (Enterprise Skeleton) |
| **Frontend** | Vue 3, Inertia.js, Tailwind CSS |
| **Graphics** | Three.js, Globe.gl, Chart.js |
| **Data Real-time** | Laravel Reverb (WebSockets), Redis |
| **Background Ops** | Laravel Horizon, Redis-backed Queues |

---

## 📦 Installation & Setup

### Prerequisites
- PHP 8.2+ & Composer
- Node.js 18+ & NPM
- MySQL 8+ & Redis

### Quick Start
```bash
# 1. Clone and Install
git clone https://github.com/creyt2012/vetinh.git
cd vetinh
composer install
npm install

# 2. Configure Environment
cp .env.example .env
php artisan key:generate

# 3. Database & Seeding
touch database/database.sqlite # If using sqlite
php artisan migrate --seed

# 4. Start Development Environment
# Uses concurrently to start server, worker, and vite
npm run dev
```

### Environment Variables Highlights
| Key | Description |
|---|---|
| `REVERB_APP_ID` | Required for real-time satellite updates. |
| `HIMAWARI_SYNC_INTERVAL` | Frequency of weather image updates (default: 10m). |

---

## 📖 Technical Documentation

Detailed deep-dives are available in our internal wiki:
- [System Architecture](https://github.com/creyt2012/vetinh/wiki/Architecture)
- [Mathematical Algorithms (SGP4 & Storm Tracking)](https://github.com/creyt2012/vetinh/wiki/Algorithms)
- [Risk Scoring Methodology](https://github.com/creyt2012/vetinh/wiki/Risk-Engine)
- [API Reference for Enterprise Integration](https://github.com/creyt2012/vetinh/wiki/API-Reference)

---

## 🗺️ Roadmap
- [ ] Integration of terrestrial Radar data (NEXRAD/Local).
- [ ] Machine Learning driven storm path prediction using LSTM models.
- [ ] SMS/Push notification gateway for critical weather events.

---

**Developed with Passion for Earth Science**  
*Empowering data-driven decisions via orbital and atmospheric intelligence.*
