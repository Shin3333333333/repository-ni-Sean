# fixed_scheduler_load_balanced.py
import mysql.connector
from ortools.sat.python import cp_model
import re
import json

def generate_schedule():
    # -----------------------------
    # DATABASE CONNECTION
    # -----------------------------
    db = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",  # your DB password
        database="school_scheduler"
    )
    cursor = db.cursor(dictionary=True)

    # -----------------------------
    # FETCH DATA
    # -----------------------------
    cursor.execute("SELECT id, name, capacity, status FROM rooms")
    rooms = [r for r in cursor.fetchall() if r["status"].lower() == "available"]

    cursor.execute("SELECT id, name, max_load, time_unavailable, status FROM professors")
    faculty = [f for f in cursor.fetchall() if f["status"].lower() == "active"]

    cursor.execute("SELECT id, name, students FROM courses")
    courses = cursor.fetchall()

    cursor.execute("SELECT id, subject_title, course_id FROM subjects")
    subjects = cursor.fetchall()

    for c in courses:
        c["subjects"] = [s for s in subjects if s["course_id"] == c["id"]]

    days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
    start_hours = range(6, 19)
    slot_duration = 3
    time_slots = []

    for day in days:
        for start in start_hours:
            end = start + slot_duration
            if end <= 21:
                start_str = f"{start:02d}:00"
                end_str = f"{end:02d}:00"
                time_slots.append(f"{day} {start_str}-{end_str}")


    # -----------------------------
    # HELPER FUNCTIONS
    # -----------------------------
    def parse_slot_label(slot_label):
        s = slot_label.replace("–", "-").strip()
        m = re.match(r'([A-Za-z]+)\s+(\d{1,2})(?::(\d{2}))?\s*[-–]\s*(\d{1,2})(?::(\d{2}))?', s)
        if not m:
            raise ValueError(f"Can't parse slot: {slot_label!r}")
        day = m.group(1)
        sh = int(m.group(2)); sm = int(m.group(3) or 0)
        eh = int(m.group(4)); em = int(m.group(5) or 0)
        return {"label": slot_label, "day": day, "start": sh*60 + sm, "end": eh*60 + em}

    def parse_unavailable_range(unavail_label):
        if not isinstance(unavail_label, str):
            return None
        s = unavail_label.replace("–", "-").strip()
        m = re.match(r'([A-Za-z]+)\s+(\d{1,2})(?::(\d{2}))?\s*[-–]\s*(\d{1,2})(?::(\d{2}))?', s)
        if not m:
            return None
        day = m.group(1)
        sh = int(m.group(2)); sm = int(m.group(3) or 0)
        eh = int(m.group(4)); em = int(m.group(5) or 0)
        return {"day": day, "start": sh*60+sm, "end": eh*60+em}

    def intervals_overlap(a_start, a_end, b_start, b_end):
        return not (a_end <= b_start or b_end <= a_start)

    def is_time_conflict(faculty_unavailable_parsed, slot_label, buffer_minutes=60):
        if not faculty_unavailable_parsed:
            return False
        try:
            slot_struct = parse_slot_label(slot_label)
        except Exception:
            return True
        for rng in faculty_unavailable_parsed:
            buf_start = max(0, rng["start"] - buffer_minutes)
            buf_end = min(24*60, rng["end"] + buffer_minutes)
            if slot_struct["day"] != rng["day"]:
                continue
            if intervals_overlap(slot_struct["start"], slot_struct["end"], buf_start, buf_end):
                return True
        return False

    slot_structs = [parse_slot_label(t) for t in time_slots]
    slot_index_by_label = {t: i for i, t in enumerate(time_slots)}
    overlap_map = {i: set() for i in range(len(slot_structs))}
    for i, s1 in enumerate(slot_structs):
        for j, s2 in enumerate(slot_structs):
            if s1["day"] != s2["day"]:
                continue
            if intervals_overlap(s1["start"], s1["end"], s2["start"], s2["end"]):
                overlap_map[i].add(j)

    for f in faculty:
        raw = f.get("time_unavailable") or ""
        if isinstance(raw, list):
            parts = raw
        elif isinstance(raw, str) and raw.strip():
            try:
                parsed_eval = eval(raw)
                parts = list(parsed_eval) if isinstance(parsed_eval, (list, tuple)) else [p.strip() for p in raw.split(",") if p.strip()]
            except Exception:
                parts = [p.strip() for p in raw.split(",") if p.strip()]
        else:
            parts = []
        ranges = []
        for part in parts:
            pr = parse_unavailable_range(part)
            if pr:
                ranges.append(pr)
            else:
                try:
                    pr2 = parse_slot_label(part)
                    ranges.append(pr2)
                except:
                    pass
        f["unavailable_parsed"] = ranges

    course_subjects = []
    for c in courses:
        for s in c["subjects"]:
            s_copy = s.copy()
            s_copy["course_id"] = c["id"]
            course_subjects.append(s_copy)

    model = cp_model.CpModel()
    x = {}

    for subj in course_subjects:
        sid = subj["id"]
        students = next((c["students"] for c in courses if c["id"]==subj["course_id"]),0)
        for f in faculty:
            fid = f["id"]
            for r in rooms:
                rid = r["id"]
                if r["capacity"] < students:
                    continue
                for t in time_slots:
                    if is_time_conflict(f["unavailable_parsed"], t):
                        continue
                    x[(sid,fid,rid,t)] = model.NewBoolVar(f"x_s{sid}_f{fid}_r{rid}_t{slot_index_by_label[t]}")

    faculty_load_vars = {}
    for f in faculty:
        fid = f["id"]
        assigned_vars = [v for (s, ff, rr, t), v in x.items() if ff==fid]
        if assigned_vars:
            faculty_load_vars[fid] = model.NewIntVar(0, len(course_subjects), f"load_f{fid}")
            model.Add(faculty_load_vars[fid] == sum(assigned_vars))

    max_load_var = model.NewIntVar(0, len(course_subjects), "max_faculty_load")
    for lv in faculty_load_vars.values():
        model.Add(lv <= max_load_var)
    model.Minimize(max_load_var)

    for subj in course_subjects:
        sid = subj["id"]
        vars_for_subj = [v for (s,f,r,t),v in x.items() if s==sid]
        if vars_for_subj:
            model.Add(sum(vars_for_subj)==1)
        else:
            model.Add(0==1)

    num_slots = len(slot_structs)
    for f in faculty:
        fid = f["id"]
        for i in range(num_slots):
            vars_overlapping = []
            for (s, ff, rr, t), v in x.items():
                if ff!=fid:
                    continue
                ti = slot_index_by_label[t]
                if ti==i or ti in overlap_map[i]:
                    vars_overlapping.append(v)
            if vars_overlapping:
                model.Add(sum(vars_overlapping)<=1)

    for r in rooms:
        rid = r["id"]
        for i in range(num_slots):
            vars_overlapping = []
            for (s, ff, rr, t), v in x.items():
                if rr!=rid:
                    continue
                ti = slot_index_by_label[t]
                if ti==i or ti in overlap_map[i]:
                    vars_overlapping.append(v)
            if vars_overlapping:
                model.Add(sum(vars_overlapping)<=1)

    for f in faculty:
        fid = f["id"]
        if f.get("max_load") is None:
            continue
        max_subs = int(f["max_load"])
        vars_f = [v for (s, ff, rr, t), v in x.items() if ff==fid]
        if vars_f:
            model.Add(sum(vars_f) <= max_subs)

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 30
    solver.parameters.num_search_workers = 8
    status = solver.Solve(model)

    schedule = []
    if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        for (sid,fid,rid,t), v in x.items():
            if solver.BooleanValue(v):
                schedule.append({
                    "course": next(c["name"] for c in courses if c["id"]==next(s["course_id"] for s in subjects if s["id"]==sid)),
                    "subject": next(s["subject_title"] for s in subjects if s["id"]==sid),
                    "faculty": next(f["name"] for f in faculty if f["id"]==fid),
                    "room": next(r["name"] for r in rooms if r["id"]==rid),
                    "time": t
                })

    cursor.close()
    db.close()

    return schedule


if __name__ == "__main__":
    schedule = generate_schedule()
    output = {
        "success": True,
        "message": f"Schedule generated successfully with {len(schedule)} assignments",
        "schedule": schedule
    }
    print(json.dumps(output, ensure_ascii=False))
