import re
import mysql.connector
from ortools.sat.python import cp_model

# -----------------------------
# DATABASE CONNECTION
# -----------------------------
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",  # change if needed
    database="school_scheduler"
)
cursor = db.cursor(dictionary=True)

# -----------------------------
# FETCH DATA FROM DATABASE
# -----------------------------
cursor.execute("SELECT id, name, capacity FROM rooms")
rooms = cursor.fetchall()

cursor.execute("SELECT id, name, max_load, time_unavailable FROM professors")
faculty = cursor.fetchall()

cursor.execute("SELECT id, name, students FROM courses")
courses = cursor.fetchall()

cursor.execute("SELECT id, subject_title, course_id FROM subjects")
subjects = cursor.fetchall()

# -----------------------------
# BUILD COURSE-SUBJECT MAPPING
# -----------------------------
for c in courses:
    c["subjects"] = [s for s in subjects if s["course_id"] == c["id"]]

# -----------------------------
# TIME SLOTS (You can fetch from DB later)
# -----------------------------
time_slots = [
    "Mon 8:00–10:00", "Mon 10:00–12:00", "Tue 8:00–10:00",
    "Tue 10:00–12:00", "Wed 13:00–15:00", "Fri 8:00–11:00",
    "Sat 7:00–10:00", "Sat 10:00–13:00"
]

# -----------------------------
# PARSING HELPERS
# -----------------------------
def parse_range(range_label):
    if not isinstance(range_label, str):
        return None
    m = re.match(r'(\w+)\s+(\d{1,2}):(\d{2})\s*[–-]\s*(\d{1,2}):(\d{2})', range_label.strip())
    if not m:
        return None
    day = m.group(1)
    start = int(m.group(2)) * 60 + int(m.group(3))
    end = int(m.group(4)) * 60 + int(m.group(5))
    return {"day": day, "start": start, "end": end}

def parse_slot(slot_label):
    m = re.match(r'(\w+)\s+(\d{1,2})(?::(\d{2}))?\s*[–-]\s*(\d{1,2})(?::(\d{2}))?', slot_label)
    if not m:
        raise ValueError(f"Can't parse slot label: {slot_label!r}")
    day = m.group(1)
    sh = int(m.group(2)); sm = int(m.group(3) or 0)
    eh = int(m.group(4)); em = int(m.group(5) or 0)
    return {"label": slot_label, "day": day, "start": sh * 60 + sm, "end": eh * 60 + em, "duration_minutes": (eh * 60 + em) - (sh * 60 + sm)}

def overlaps(a, b):
    if a["day"] != b["day"]:
        return False
    return not (a["end"] <= b["start"] or b["end"] <= a["start"])

# -----------------------------
# PREPARE DATA STRUCTURES
# -----------------------------
time_slots_struct = [parse_slot(t) for t in time_slots]

for f in faculty:
    raw = f.get("time_unavailable") or ""
    parts = [p.strip() for p in raw.split(",") if p.strip()]
    parsed = []
    for p in parts:
        pr = parse_range(p)
        if pr:
            parsed.append(pr)
    f["unavailable_parsed"] = parsed

# map helpers
subjects_map = {s["id"]: s for s in subjects}
course_students_map = {c["id"]: c["students"] for c in courses}
course_subjects = []
for c in courses:
    for s in c["subjects"]:
        s_copy = s.copy()
        s_copy["course_id"] = c["id"]
        course_subjects.append(s_copy)

# -----------------------------
# BUILD CP-SAT MODEL
# -----------------------------
model = cp_model.CpModel()
x = {}
slot_count = len(time_slots_struct)

# Precompute available slots for each faculty
faculty_avail_indices = {}
for f in faculty:
    fid = f["id"]
    avail = set()
    for i, slot in enumerate(time_slots_struct):
        if not any(overlaps(slot, rng) for rng in f["unavailable_parsed"]):
            avail.add(i)
    faculty_avail_indices[fid] = avail

# Create decision variables for feasible combinations only
for subj in course_subjects:
    sid = subj["id"]
    students = course_students_map[subj["course_id"]]
    for f in faculty:
        fid = f["id"]
        for r in rooms:
            rid = r["id"]
            if r["capacity"] < students:
                continue
            for ti in faculty_avail_indices[fid]:
                x[(sid, fid, rid, ti)] = model.NewBoolVar(f"x_s{sid}_f{fid}_r{rid}_t{ti}")

# -----------------------------
# CONSTRAINTS
# -----------------------------

# Each subject exactly once
for subj in course_subjects:
    sid = subj["id"]
    vars_for_subj = [v for (s, f, r, t), v in x.items() if s == sid]
    if vars_for_subj:
        model.Add(sum(vars_for_subj) == 1)
    else:
        model.Add(0 == 1)

# Faculty no overlap
overlap_map = {i: set() for i in range(slot_count)}
for i, s1 in enumerate(time_slots_struct):
    for j, s2 in enumerate(time_slots_struct):
        if overlaps(s1, s2):
            overlap_map[i].add(j)

for f in faculty:
    fid = f["id"]
    for i in range(slot_count):
        overlapping = [v for (sid, ff, rr, tt), v in x.items() if ff == fid and (tt == i or tt in overlap_map[i])]
        if overlapping:
            model.Add(sum(overlapping) <= 1)

# Room no overlap
for r in rooms:
    rid = r["id"]
    for i in range(slot_count):
        overlapping = [v for (sid, ff, rr, tt), v in x.items() if rr == rid and (tt == i or tt in overlap_map[i])]
        if overlapping:
            model.Add(sum(overlapping) <= 1)

# -----------------------------
# FACULTY LOAD BALANCING (optional)
# -----------------------------
faculty_load_minutes_vars = {}
for f in faculty:
    fid = f["id"]
    assigned_minutes_terms = [(v, time_slots_struct[t]["duration_minutes"]) for (sid, ff, rr, t), v in x.items() if ff == fid]
    if assigned_minutes_terms:
        load_var = model.NewIntVar(0, 24 * 60 * len(course_subjects), f"loadmin_f{fid}")
        model.Add(load_var == sum(var * dur for (var, dur) in assigned_minutes_terms))
    else:
        load_var = model.NewIntVar(0, 0, f"loadmin_f{fid}")
        model.Add(load_var == 0)
    faculty_load_minutes_vars[fid] = load_var

for f in faculty:
    fid = f["id"]
    if f.get("max_load"):
        model.Add(faculty_load_minutes_vars[fid] <= int(f["max_load"]) * 60)

# Objective: minimize the maximum faculty load
max_minutes_var = model.NewIntVar(0, 24 * 60 * len(course_subjects), "max_minutes")
for fid, load_var in faculty_load_minutes_vars.items():
    model.Add(load_var <= max_minutes_var)
model.Minimize(max_minutes_var)

# -----------------------------
# SOLVE MODEL
# -----------------------------
solver = cp_model.CpSolver()
solver.parameters.max_time_in_seconds = 30
solver.parameters.num_search_workers = 8
status = solver.Solve(model)

schedule = []

if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
    for (sid, fid, rid, tt), var in x.items():
        if solver.BooleanValue(var):
            schedule.append({
                "course": next(c["name"] for c in courses if c["id"] == subjects_map[sid]["course_id"]),
                "subject": subjects_map[sid]["subject_title"],
                "faculty": next(f["name"] for f in faculty if f["id"] == fid),
                "room": next(r["name"] for r in rooms if r["id"] == rid),
                "time": time_slots_struct[tt]["label"]
            })
else:
    print("❌ No feasible solution found. Status:", status)

# -----------------------------
# OUTPUT
# -----------------------------
if schedule:
    print("✅ Generated Schedule:")
    for s in schedule:
        print(f"{s['course']} - {s['subject']} | {s['faculty']} | {s['room']} | {s['time']}")
else:
    print("❌ No schedule generated.")
