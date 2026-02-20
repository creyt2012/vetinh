import http.server
import socketserver
import json
from datetime import datetime

class AICoreMockHandler(http.server.SimpleHTTPRequestHandler):
    def do_GET(self):
        self.send_response(200)
        self.send_header('Content-type', 'application/json')
        self.end_headers()
        self.wfile.write(json.dumps({"name": "StarWeather AI Core", "version": "1.0.0", "status": "operational"}).encode())

    def do_POST(self):
        if self.path == '/analyze':
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            response = {
                "status": "success",
                "temperature_c": 12.5,
                "pressure_hpa": 995.2,
                "wind_speed_kmh": 34.5,
                "wind_direction_deg": 120,
                "cloud_coverage_pct": 85.0,
                "timestamp": datetime.now().isoformat(),
                "metadata": {
                    "mean_brightness": 180.5,
                    "resolution": "2000x2000",
                    "engine": "AI_CORE_V1_MOCK"
                }
            }
            self.wfile.write(json.dumps(response).encode())
        else:
            self.send_response(404)
            self.end_headers()

if __name__ == "__main__":
    PORT = 8001
    with socketserver.TCPServer(("", PORT), AICoreMockHandler) as httpd:
        print("Mock AI Core running at port", PORT)
        httpd.serve_forever()
