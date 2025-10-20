# ai/generate_json.py
import json
from newtry import generate_schedule
import os

# Path to Vue public folder
public_dir = os.path.join(os.path.dirname(os.path.dirname(__file__)), "public")
if not os.path.exists(public_dir):
    os.makedirs(public_dir)

schedule_file = os.path.join(public_dir, "schedule.json")

# Generate schedule
schedule = generate_schedule()

# Write JSON
with open(schedule_file, "w") as f:
    json.dump(schedule, f, indent=2)

print(f"Schedule saved to {schedule_file}")
