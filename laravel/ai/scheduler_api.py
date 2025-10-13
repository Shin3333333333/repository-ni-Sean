from flask import Flask, jsonify
from fixed_scheduler_load_balanced import generate_schedule
import json
import os

app = Flask(__name__)

# Optional: allow CORS if your Vue app is on another port
from flask_cors import CORS
CORS(app)

@app.route("/api/generate-schedule", methods=["GET"])
def run_scheduler():
    try:
        schedule = generate_schedule()

        # Save to public folder for Vue
        public_path = os.path.join(os.path.dirname(__file__), "../public/schedule.json")
        os.makedirs(os.path.dirname(public_path), exist_ok=True)
        with open(public_path, "w") as f:
            json.dump(schedule, f, indent=2)

        return jsonify(schedule)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == "__main__":
    app.run(port=5000)
