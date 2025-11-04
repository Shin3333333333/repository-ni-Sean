# app.py
from flask import Flask, jsonify
from flask_cors import CORS
from schedule import generate_schedule

app = Flask(__name__)
CORS(app)

@app.route("/api/generate-schedule")
def api_generate_schedule():
    try:
        schedule = generate_schedule()
        return jsonify(schedule)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == "__main__":
    app.run(port=5000, debug=True)
