# fixed_scheduler_load_balanced_debug_with_unassigned_and_force.py
import mysql.connector
from ortools.sat.python import cp_model
import re
import json
import sys
from collections import defaultdict

def generate_schedule(academic_year=None, semester_id=None, max_solve_seconds=30, search_workers=8):
    """
    Generates schedule using OR-Tools CP-SAT.
    Filters subjects by semester_id if provided.
    Returns dict with keys: success, message, schedule, unassigned, summary, conflicts.
    """

    # -----------------------------
    # DATABASE CONNECTION
    # -----------------------------
    db = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",  # update if needed
        database="school_scheduler"
    )
    cursor = db.cursor(dictionary=True)

    # -----------------------------
    # FETCH ANCILLARY DATA
    # -----------------------------
    cursor.execute("SELECT * FROM rooms")
    rooms_all = cursor.fetchall() or []
    # normalize capacity field: try 'capacity', 'max_load' or fallback None
    for r in rooms_all:
        r['capacity'] = r.get('capacity') or r.get('max_load') or r.get('size') or None
        # status may be in different column names; standardize
        r['status'] = (r.get('status') or "").lower()

    # only available rooms
    rooms = [r for r in rooms_all if (r.get("status") or "") in ("", "available", "active", None)]

    cursor.execute("SELECT * FROM professors")
    faculty_all = cursor.fetchall() or []
    # standardize keys used in code
    for f in faculty_all:
        f['status'] = (f.get('status') or "").lower()
        # keep time_unavailable if exists (otherwise None)
        f['time_unavailable'] = f.get('time_unavailable') or f.get('unavailable') or None
    faculty = [f for f in faculty_all if (f.get("status") in ("", "active", "available", None))]

    cursor.execute("SELECT * FROM courses")
    courses = cursor.fetchall() or []

    # build course_by_id for quick lookup
    course_by_id = {c.get('id'): c for c in courses}

    # -----------------------------
    # FETCH SUBJECTS (filtered by semester)
    # -----------------------------
    subject_query = """
        SELECT
            s.id AS subject_id,
            s.year_level,
            s.semester_id,
            s.subject_code,
            s.subject_title,
            s.units,
            s.hours,
            s.pre_requisite,
            s.type,
            s.created_at,
            s.updated_at,
            s.curriculum_id,
            s.course_id
        FROM subjects s
        WHERE s.course_id IS NOT NULL
    """

    params = []

    # ✅ Handle semester filter properly
    if semester_id is not None:
        # If semester_id is provided as string, first try to parse as int
        if isinstance(semester_id, str):
            try:
                # allow "1" or "2" strings
                semester_int = int(semester_id)
                semester_id = semester_int
            except Exception:
                # Lookup semester name in 'semester' table (you confirmed table name is 'semester')
                cursor.execute("SELECT id FROM semesters WHERE name = %s LIMIT 1", (semester_id,))
                row = cursor.fetchone()
                if row:
                    semester_id = row.get("id")
                else:
                    # if not found, set impossible value so query returns nothing
                    semester_id = -1
        subject_query += " AND s.semester_id = %s"
        params.append(semester_id)

    # Run query with params (if any)
    if params:
        cursor.execute(subject_query, params)
    else:
        cursor.execute(subject_query)

    subjects_rows = cursor.fetchall() or []

    # Normalize subject dicts to expected keys (code elsewhere expects 'id' and 'subject_code')
    subjects = []
    for r in subjects_rows:
        norm = dict(r)
        # map subject_id -> id for compatibility
        if 'subject_id' in norm:
            norm['id'] = norm['subject_id']
            norm.pop('subject_id', None)
        # ensure subject_code exists
        norm['subject_code'] = norm.get('subject_code') or ''
        # assign course name / section from course lookup if present
        course_obj = course_by_id.get(norm.get('course_id'))
        norm['course_name'] = course_obj.get('name') if course_obj else None
        subjects.append(norm)

    # only curriculum subjects (they already come with course_id != NULL by query)
    curriculum_subjects = [s for s in subjects if s.get('course_id')]

    # -----------------------------
    # TIME SLOTS (config)
    # -----------------------------
    days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
    start_hours = range(6, 19)   # 06:00 .. 18:00 starts
    slot_duration = 3            # hours
    time_slots = []
    for day in days:
        for start in start_hours:
            end = start + slot_duration
            if end <= 21:
                time_slots.append(f"{day} {start:02d}:00-{end:02d}:00")

    # -----------------------------
    # PARSERS & HELPERS
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

    # Check if slot overlaps any unavailable range with buffer (minutes)
    def is_time_conflict(parsed_unavail_ranges, slot_label, buffer_minutes=60):
        if not parsed_unavail_ranges:
            return False
        try:
            slot_struct = parse_slot_label(slot_label)
        except Exception:
            return True
        for rng in parsed_unavail_ranges:
            buf_start = max(0, rng["start"] - buffer_minutes)
            buf_end = min(24*60, rng["end"] + buffer_minutes)
            if slot_struct["day"] != rng["day"]:
                continue
            if intervals_overlap(slot_struct["start"], slot_struct["end"], buf_start, buf_end):
                return True
        return False

    # -----------------------------
    # PREPARE SLOT STRUCTS & OVERLAPS
    # -----------------------------
    slot_structs = [parse_slot_label(t) for t in time_slots]
    slot_index_by_label = {t: i for i, t in enumerate(time_slots)}
    overlap_map = {i: set() for i in range(len(slot_structs))}
    for i, s1 in enumerate(slot_structs):
        for j, s2 in enumerate(slot_structs):
            if s1["day"] != s2["day"]:
                continue
            if intervals_overlap(s1["start"], s1["end"], s2["start"], s2["end"]):
                overlap_map[i].add(j)

    # -----------------------------
    # Parse faculty unavailable strings into ranges
    # -----------------------------
    for f in faculty:
        raw = f.get("time_unavailable") or f.get("unavailable") or ""
        parts = []
        if isinstance(raw, list):
            parts = raw
        elif isinstance(raw, str) and raw.strip():
            # try to parse list-like strings, otherwise comma split
            try:
                parsed_eval = eval(raw)
                parts = list(parsed_eval) if isinstance(parsed_eval, (list, tuple)) else [p.strip() for p in raw.split(",") if p.strip()]
            except Exception:
                parts = [p.strip() for p in raw.split(",") if p.strip()]
        ranges = []
        for part in parts:
            pr = parse_unavailable_range(part)
            if pr:
                ranges.append(pr)
            else:
                try:
                    pr2 = parse_slot_label(part)
                    ranges.append(pr2)
                except Exception:
                    pass
        f["unavailable_parsed"] = ranges
        # debug log
        print(f"Faculty {f.get('name')} unavailable parsed: {f.get('unavailable_parsed')}", file=sys.stderr)

    # -----------------------------
    # SUBJECT DEPT + FACULTY BY DEPT
    # -----------------------------
    def get_subject_department(subject_code):
        m = re.match(r'([A-Z]+)', subject_code or "")
        return m.group(1) if m else None

    for s in subjects:
        s['dept'] = get_subject_department(s.get('subject_code') or "")

    faculty_by_dept = {}
    for f in faculty:
        dept = f.get("department") or f.get("dept") or f.get("year") or None
        if dept:
            faculty_by_dept.setdefault(dept, []).append(f)

    # -----------------------------
    # Build allowed assignment combos (only feasible ones)
    # Track reasons for subjects that have zero combos
    # -----------------------------
    allowed_combos = {}
    subject_feasible_reasons = defaultdict(list)
    combos_by_subject = defaultdict(list)

    # precompute students for course subject if available in courses table
    for subj in curriculum_subjects:
        pass  # placeholder - variable exists later

    course_by_id = {c.get('id'): c for c in courses}
    course_subjects = [s for s in subjects if s.get('course_id')]

    for subj in course_subjects:
        course_obj = course_by_id.get(subj.get('course_id'))
        # use students field from course if present, else fallback 1
        students = (course_obj.get('students') if course_obj and course_obj.get('students') is not None else None) or 1
        sid = subj.get('id')
        subj_dept = subj.get('dept')
        allowed_faculty = faculty_by_dept.get(subj_dept, []) or faculty[:]  # fallback to all active faculty

        for fobj in allowed_faculty:
            fid = fobj.get('id')
            for r in rooms:
                rid = r.get('id')
                # capacity fallback: try capacity or max_load
                cap = r.get('capacity') or r.get('max_load') or None
                if cap is not None:
                    try:
                        if int(cap) < int(students):
                            continue
                    except Exception:
                        # if can't parse capacity, allow (safer)
                        pass
                for t in time_slots:
                    # respect faculty unavailability (1-hour buffer)
                    if is_time_conflict(fobj.get('unavailable_parsed', []), t, buffer_minutes=60):
                        continue
                    # passed filters - add combo
                    allowed_combos[(sid, fid, rid, t)] = True
                    combos_by_subject[sid].append((fid, rid, t))

        if not combos_by_subject[sid]:
            # determine reason
            if not allowed_faculty:
                subject_feasible_reasons[sid].append("no_faculty_for_dept")
            else:
                rooms_ok = [r for r in rooms if (r.get('capacity') or r.get('max_load') or 0) >= (students or 1)]
                if not rooms_ok:
                    subject_feasible_reasons[sid].append("no_room_with_capacity")
                else:
                    subject_feasible_reasons[sid].append("faculty_unavailable_for_all_slots")

    print(f"Allowed combos built: {len(allowed_combos)}", file=sys.stderr)

    # -----------------------------
    # BUILD CP-SAT MODEL
    # -----------------------------
    model = cp_model.CpModel()
    x = {}

    # create boolean var for each allowed combo
    for (sid, fid, rid, t) in allowed_combos.keys():
        idx = slot_index_by_label.get(t, 0)
        var = model.NewBoolVar(f"x_s{sid}_f{fid}_r{rid}_t{idx}")
        x[(sid, fid, rid, t)] = var

    # each subject at most once
    for subj in course_subjects:
        sid = subj.get('id')
        vars_for_subj = [v for (s,f,r,t), v in x.items() if s == sid]
        if vars_for_subj:
            model.Add(sum(vars_for_subj) <= 1)

    # faculty/time conflicts
    num_slots = len(slot_structs)
    for fobj in faculty:
        fid = fobj.get('id')
        for i in range(num_slots):
            vars_overlapping = []
            for (s, ff, rr, t), v in x.items():
                if ff != fid:
                    continue
                ti = slot_index_by_label.get(t)
                if ti == i or ti in overlap_map.get(i, set()):
                    vars_overlapping.append(v)
            if vars_overlapping:
                model.Add(sum(vars_overlapping) <= 1)

    # room/time conflicts
    for robj in rooms:
        rid = robj.get('id')
        for i in range(num_slots):
            vars_overlapping = []
            for (s, ff, rr, t), v in x.items():
                if rr != rid:
                    continue
                ti = slot_index_by_label.get(t)
                if ti == i or ti in overlap_map.get(i, set()):
                    vars_overlapping.append(v)
            if vars_overlapping:
                model.Add(sum(vars_overlapping) <= 1)

    # faculty load variables (for balancing)
    faculty_load_vars = {}
    for fobj in faculty:
        fid = fobj.get('id')
        assigned_vars = [v for (s, ff, rr, t), v in x.items() if ff == fid]
        if assigned_vars:
            faculty_load_vars[fid] = model.NewIntVar(0, len(course_subjects), f"load_f{fid}")
            model.Add(faculty_load_vars[fid] == sum(assigned_vars))

    max_load_var = model.NewIntVar(0, len(course_subjects), "max_faculty_load")
    for lv in faculty_load_vars.values():
        model.Add(lv <= max_load_var)

    # objective: maximize number assigned (primary) and minimize max load (secondary)
    total_assigned = model.NewIntVar(0, len(course_subjects), "total_assigned")
    if x:
        model.Add(total_assigned == sum(x.values()))
    else:
        model.Add(total_assigned == 0)
    model.Maximize(total_assigned * 1000 - max_load_var)

    # -----------------------------
    # SOLVE
    # -----------------------------
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = max_solve_seconds
    solver.parameters.num_search_workers = search_workers
    status = solver.Solve(model)

    assigned = []
    unassigned = []
    assigned_subject_ids = set()

    if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        for (sid, fid, rid, t), v in x.items():
            if solver.BooleanValue(v):
                subj_obj = next((s for s in subjects if s.get('id') == sid), None)
                course_obj = course_by_id.get(subj_obj.get('course_id') if subj_obj else None)
                faculty_obj = next((f for f in faculty if f.get('id') == fid), None)
                room_obj = next((r for r in rooms if r.get('id') == rid), None)
                assigned.append({
                    "subject_id": sid,
                    "subject_title": subj_obj.get('subject_title') if subj_obj else None,
                    "course_id": subj_obj.get('course_id') if subj_obj else None,
                    # course_section displayed from course name
                    "course_section": subj_obj.get('course_name') or (course_obj.get('name') if course_obj else None),
                    "course_code": subj_obj.get('subject_code') if subj_obj else None,
                    "faculty_id": fid,
                    "faculty_name": faculty_obj.get('name') if faculty_obj else None,
                    "room_id": rid,
                    "room_name": room_obj.get('name') if room_obj else None,
                    "time_slot": t,
                    "units": subj_obj.get('units') if subj_obj else 0,
                })
                assigned_subject_ids.add(sid)

    # build unassigned list and reasons
    for subj in course_subjects:
        sid = subj.get('id')
        if sid not in assigned_subject_ids:
            reasons = list(subject_feasible_reasons.get(sid, []))
            if combos_by_subject.get(sid):
                reasons.append("unselected_by_solver_or_conflict")
            unassigned.append({
                "subject_id": sid,
                "subject_title": subj.get('subject_title'),
                "course_id": subj.get('course_id'),
                "units": subj.get('units'),
                "possible_combos_count": len(combos_by_subject.get(sid, [])),
                "reasons": reasons
            })

    # -----------------------------
    # Post-check conflict detection for UI
    # -----------------------------
    conflicts = []

    # unassigned due to no combos -> human messages
    for u in unassigned:
        if not combos_by_subject.get(u['subject_id']):
            for r in u['reasons']:
                if r == "no_faculty_for_dept":
                    msg = f"Subject '{u.get('subject_title')}' has no available faculty in its department."
                elif r == "no_room_with_capacity":
                    msg = f"Subject '{u.get('subject_title')}' requires a room with larger capacity."
                elif r == "faculty_unavailable_for_all_slots":
                    msg = f"Subject '{u.get('subject_title')}' — candidate faculty unavailable for all slots (1h buffer)."
                else:
                    msg = f"Subject '{u.get('subject_title')}' unassigned: {r}."
                conflicts.append({
                    "type": "unassigned_reason",
                    "subject_id": u.get('subject_id'),
                    "description": msg,
                    "reasons": u.get('reasons')
                })

    # check assigned for overlaps (defensive)
    fac_slots = defaultdict(list)
    room_slots = defaultdict(list)
    for a in assigned:
        fid = a.get('faculty_id')
        rid = a.get('room_id')
        t = a.get('time_slot')
        if t not in slot_index_by_label:
            continue
        ti = slot_index_by_label[t]
        fac_slots[fid].append((ti, a))
        room_slots[rid].append((ti, a))

    def detect_overlaps(list_of_ti_and_a, owner_type):
        list_of_ti_and_a.sort(key=lambda x: x[0])
        for i in range(len(list_of_ti_and_a)):
            ti, a = list_of_ti_and_a[i]
            for j in range(i+1, len(list_of_ti_and_a)):
                tj, b = list_of_ti_and_a[j]
                if tj == ti or tj in overlap_map.get(ti, set()):
                    conflicts.append({
                        "type": "overlap",
                        "owner_type": owner_type,
                        "owner_id": a.get('faculty_id') if owner_type == 'faculty' else a.get('room_id'),
                        "subject_a_id": a.get('subject_id'),
                        "subject_b_id": b.get('subject_id'),
                        "description": f"Overlap detected for {owner_type} between '{a.get('subject_title')}' and '{b.get('subject_title')}' at {a.get('time_slot')} / {b.get('time_slot')}"
                    })

    for fid, lst in fac_slots.items():
        detect_overlaps(lst, "faculty")
    for rid, lst in room_slots.items():
        detect_overlaps(lst, "room")

    # defensive check: assigned to faculty during their unavailable slots
    for a in assigned:
        fid = a.get('faculty_id')
        t = a.get('time_slot')
        fobj = next((f for f in faculty if f.get('id') == fid), None)
        if fobj and is_time_conflict(fobj.get('unavailable_parsed', []), t, buffer_minutes=60):
            conflicts.append({
                "type": "faculty_unavailable_violation",
                "faculty_id": fid,
                "subject_id": a.get('subject_id'),
                "description": f"Assigned '{a.get('subject_title')}' to '{fobj.get('name')}' during their unavailable time {t} (1h buffer)."
            })

    # -----------------------------
    # Helper: force assign (local only)
    # -----------------------------
    def force_assign(subject_id, faculty_id, room_id, time_slot):
        subject_obj = next((s for s in subjects if s.get('id') == subject_id), None)
        faculty_obj = next((f for f in faculty if f.get('id') == faculty_id), None)
        room_obj = next((r for r in rooms if r.get('id') == room_id), None)
        if not subject_obj:
            return {"error": "subject_not_found"}
        if time_slot not in time_slots:
            return {"error": "invalid_time_slot"}
        assignment = {
            "subject_id": subject_id,
            "subject_title": subject_obj.get('subject_title'),
            "course_id": subject_obj.get('course_id'),
            "faculty_id": faculty_id,
            "faculty_name": faculty_obj.get('name') if faculty_obj else None,
            "room_id": room_id,
            "room_name": room_obj.get('name') if room_obj else None,
            "time_slot": time_slot,
            "units": subject_obj.get('units', 0),
            "forced": True
        }
        assigned.append(assignment)
        for i, u in enumerate(unassigned):
            if u.get('subject_id') == subject_id:
                unassigned.pop(i)
                break
        return assignment

    # -----------------------------
    # Summary & cleanup
    # -----------------------------
    total_curriculum = len(curriculum_subjects)
    total_assigned_count = len(assigned)
    total_unassigned_count = len(unassigned)
    summary = {
        "total_curriculum_subjects": total_curriculum,
        "total_assigned": total_assigned_count,
        "total_unassigned": total_unassigned_count,
        "assigned_ids": [a.get('subject_id') for a in assigned],
        "unassigned_ids": [u.get('subject_id') for u in unassigned],
    }

    cursor.close()
    db.close()

    return {
        "success": True,
        "message": "Schedule generation finished",
        "schedule": assigned,
        "unassigned": unassigned,
        "summary": summary,
        "conflicts": conflicts,
        "force_assign_fn": force_assign  # programmatic only (not serializable)
    }


if __name__ == "__main__":
    try:
        academic_year = None
        semester_id = None
        try:
            if not sys.stdin.isatty():
                payload = json.load(sys.stdin)
                academic_year = payload.get("academicYear") or payload.get("academic_year")
                semester_id = payload.get("semester_id") or payload.get("semesterId") or payload.get("semester")
        except Exception:
            pass

        out = generate_schedule(academic_year=academic_year, semester_id=semester_id)
        out_for_json = {k: v for k, v in out.items() if k != "force_assign_fn"}
        print(json.dumps(out_for_json, ensure_ascii=False, indent=2))
    except Exception as e:
        import traceback
        output = {"success": False, "message": str(e), "trace": traceback.format_exc()}
        print(json.dumps(output, ensure_ascii=False, indent=2))
