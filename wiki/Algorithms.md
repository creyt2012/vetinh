# Core Algorithms & Mathematical Models

StarWeather relies on established aerospace and meteorological models to provide high-fidelity data.

---

## 🛰️ Satellite Orbit Propagation & Dynamics

### 1. SGP4 Propagation Engine
The system uses the **Simplified General Perturbations (SGP4)** model to propagate the position and velocity of satellites. The `SatelliteEngine` parses TLE sets to extract:
- **Mean Motion ($n$)**: Converged from revs/day to rad/min ($n = \text{meanMotion} \cdot 2\pi / 1440$).
- **Semi-major axis ($a$)**: Derived via Kepler's Third Law $a = (\mu / n^2)^{1/3}$.

### 2. Instantaneous Velocity Calculation
We compute the orbital speed based on the **Vis-Viva equation**, allowing real-time telemetry updates for users:
$$v = \sqrt{\mu \left(2/r - 1/a \right)}$$
where $r$ is the magnitude of the position vector.

### 3. Earth Rotation & Geodetic Transformation (GMST)
To map satellites correctly over ground stations, we calculate the **Greenwich Mean Sidereal Time (GMST)**:
$$GMST = 280.46061837 + 360.98564736629 \cdot (JD - 2451545.0)$$
This ensures the longitude $\lambda$ accounts for Earth's rotation relative to the orbital RAAN.

---

## 🌩️ Multi-Spectral Weather Processing

### 1. Himawari IR/VIS Fusion
The `HimawariService` synchronizes spectral bands from the NICT dynamic API.
- **Dynamic Normalization**: Raw pixel data is processed to differentiate between high-altitude ice clouds (colder IR signature) and low-level water vapor.
- **UV Spherical Mapping**: Images are mapped onto a WGS84 ellipsoid in Three.js using standard UV coordinates, ensuring zero distortion at the equator.

### ⛈️ Storm Detection & Path Prediction
The `StormTrackingService` identifies atmospheric vortices by analyzing weather metrics in real-time.
- **Vortex Identification**: Scans for wind speed $> 60$ km/h and pressure $< 1000$ hPa.
- **Path Extrapolation**: Uses a linear vector based on the last 2 observed points to predict coordinates at 6-hour intervals:
  $$\vec{P}_{next} = \vec{P}_{last} + (\vec{P}_{last} - \vec{P}_{prev}) \cdot \Delta t$$
