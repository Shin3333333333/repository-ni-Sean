# fixed_scheduler_load_balanced_debug_with_unassigned_and_force.py
import mysql.connector
from ortools.sat.python import cp_model
import re
import json
import sys
import argparse
from collections import defaultdict
import heapq
import itertools

def generate_schedule(academic_year=None, semester_id=None, max_solve_seconds=60, search_workers=8):
    import sys
    import argparse

    # Only parse CLI args if this is run as main
    
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
    for r in rooms_all:
        r['capacity'] = r.get('capacity') or r.get('max_load') or r.get('size') or None
        r['status'] = (r.get('status') or "").lower()
    rooms = [r for r in rooms_all if (r.get("status") or "") in ("", "available", "active", None)]
    
    cursor.execute("SELECT * FROM professors")
    faculty_all = cursor.fetchall() or []
    for f in faculty_all:
        f['status'] = (f.get('status') or "").lower()
        f['time_unavailable'] = f.get('time_unavailable') or f.get('unavailable') or None
        f['is_full_time'] = (f.get('type') or "").lower() == "full-time"

    faculty = [f for f in faculty_all if (f.get("status") in ("", "active", "available", None))]

    cursor.execute("SELECT * FROM courses")
    courses = cursor.fetchall() or []
    course_by_id = {c.get('id'): c for c in courses}

    # -----------------------------
    # SEMESTER ID NORMALIZATION
    # -----------------------------
    # Default semester_id from CLI args
    # Try to read JSON payload (Laravel frontend)
    # -----------------------------
    # SEMESTER ID NORMALIZATION
    # -----------------------------
    # Initialize from CLI args
    semester_id = semester_id
    academic_year = academic_year

    # Override with JSON payload (Laravel frontend)
    try:
        if not sys.stdin.isatty():
            payload = json.load(sys.stdin)
            semester_id = payload.get("semester_id") or payload.get("semesterId") or payload.get("semester") or semester_id
            academic_year = payload.get("academicYear") or payload.get("academic_year") or academic_year
    except Exception:
        pass

    # Normalize semester_id
    if semester_id is not None:
        if isinstance(semester_id, str):
            semester_id = semester_id.strip()
            if semester_id.isdigit():
                semester_id = int(semester_id)
            else:
                semester_map = {"1st Semester": 1, "2nd Semester": 2}
                semester_id = semester_map.get(semester_id, None)
        elif isinstance(semester_id, int):
            pass  # already fine
        else:
            semester_id = None

    print(f"[DEBUG] semester_id before query: {semester_id}", file=sys.stderr)

    # -----------------------------
    # FETCH SUBJECTS (filtered by semester)
    # -----------------------------
    print(f"[DEBUG] semester_id before query: {semester_id}", file=sys.stderr)

    # Quick check: return empty if semester has no subjects
    if semester_id is not None:
        cursor.execute("SELECT COUNT(*) AS cnt FROM subjects WHERE semester_id=%s", (semester_id,))
        subject_count = cursor.fetchone().get("cnt", 0)
        if subject_count == 0:
            print(f"[DEBUG] No subjects for semester_id {semester_id}, returning empty schedule.", file=sys.stderr)
            curriculum_subjects = []
            assigned = []
            unassigned = []
            conflicts = []
            summary = {
                "total_curriculum_subjects": 0,
                "total_assigned": 0,
                "total_unassigned": 0,
                "assigned_ids": [],
                "unassigned_ids": []
            }
            cursor.close()
            db.close()
            return {
                "success": True,
                "message": "No subjects for this semester",
                "schedule": assigned,
                "unassigned": unassigned,
                "summary": summary,
                "conflicts": conflicts,
                "force_assign_fn": lambda *a, **k: {"error": "force_assign_not_available"}
            }

    # FETCH SUBJECTS (filtered by semester + course year)
    # -----------------------------
    subject_query = """
        SELECT 
            s.id AS subject_id,
            s.subject_title,
            s.subject_code,
            s.course_id,
            s.year_level,
            s.semester_id,
            s.lec_units,
            s.lab_units,
            s.total_units,
            c.name AS course_name,
            c.year AS course_year,
            f.name AS faculty_name
        FROM subjects s
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN professors f ON f.department = c.name
        WHERE s.semester_id = %s
        AND s.year_level = c.year
    """
    params = [semester_id]

    cursor.execute(subject_query, params)
    subjects_rows = cursor.fetchall() or []


    # Normalize subjects as before
    subjects = []
    for r in subjects_rows:
        norm = dict(r)
        if 'subject_id' in norm:
            norm['id'] = norm.pop('subject_id')
        norm['lec_units'] = int(norm.get('lec_units') or 0)
        norm['lab_units'] = int(norm.get('lab_units') or 0)
        norm['units'] = int(norm.get('total_units') or (
            norm['lec_units'] + norm['lab_units']
        ))
        norm['subject_code'] = norm.get('subject_code') or ''
        course_obj = course_by_id.get(norm.get('course_id'))
        norm['course_name'] = course_obj.get('name') if course_obj else None
        subjects.append(norm)


    # Only include subjects with valid course_id
    curriculum_subjects = [
        s for s in subjects
        if s.get('course_id') not in (None, '', 0)
    ]

    print(f"[DEBUG] Loaded {len(curriculum_subjects)} valid subjects for semester {semester_id} and matching course year.", file=sys.stderr)

    print(f"[DEBUG] semester_id resolved: {semester_id}", file=sys.stderr)
    print(f"[DEBUG] Loaded {len(curriculum_subjects)} subjects for this semester.", file=sys.stderr)

    # -----------------------------
    # TIME SLOTS
    # -----------------------------
    days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
    start_hours = range(6, 19)   # 06:00 .. 18:00 starts
    slot_duration = 3
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
    # Parse faculty unavailable strings
    # -----------------------------
    for f in faculty:
        raw = f.get("time_unavailable") or f.get("unavailable") or ""
        parts = []
        if isinstance(raw, list):
            parts = raw
        elif isinstance(raw, str) and raw.strip():
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
        print(f"Faculty {f.get('name')} unavailable parsed: {f.get('unavailable_parsed')}", file=sys.stderr)

    # -----------------------------
    # SUBJECT DEPT + FACULTY BY DEPT AND TYPE
    # -----------------------------
    def get_subject_department(subject_code):
        m = re.match(r'([A-Z]+)', subject_code or "")
        return m.group(1) if m else None

    for s in subjects:
        s['dept'] = get_subject_department(s.get('subject_code') or "")

    # Separate faculty by department AND type
    faculty_by_dept_and_type = defaultdict(lambda: defaultdict(list))
    for f in faculty:
        dept = f.get("department") or f.get("dept") or f.get("year") or None
        if dept:
            faculty_type = "full_time" if f.get('is_full_time') else "part_time"
            faculty_by_dept_and_type[dept][faculty_type].append(f)

    # -----------------------------
    # Build allowed assignment combos with STRICT load limits
    # -----------------------------
    allowed_combos = {}
    subject_feasible_reasons = defaultdict(list)
    combos_by_subject = defaultdict(list)

    for subj in curriculum_subjects:
        course_obj = course_by_id.get(subj.get('course_id'))
        students = (course_obj.get('students') if course_obj and course_obj.get('students') is not None else None) or 1
        sid = subj.get('id')
        subj_dept = subj.get('dept')
        
        # Get ALL faculty candidates first (we'll filter by load in the constraints)
        faculty_candidates = []
        
        # Include all faculty from same department first
        if subj_dept and subj_dept in faculty_by_dept_and_type:
            faculty_candidates.extend(faculty_by_dept_and_type[subj_dept]['full_time'])
            faculty_candidates.extend(faculty_by_dept_and_type[subj_dept]['part_time'])
        
        # Then include any other faculty as fallback
        for fobj in faculty:
            if fobj not in faculty_candidates:
                faculty_candidates.append(fobj)

        for fobj in faculty_candidates:
            fid = fobj.get('id')
            for r in rooms:
                rid = r.get('id')
                cap = r.get('capacity') or r.get('max_load') or None
                if cap is not None:
                    try:
                        if int(cap) < int(students):
                            continue
                    except Exception:
                        pass

                # --- 🔍 Enforce Lecture/Laboratory rule ---
                room_name = (r.get('name') or "").lower()
                lec_units = subj.get('lec_units', None)
                lab_units = subj.get('lab_units', None)

                # Normalize numeric values
                try:
                    lab_units_val = float(lab_units or 0)
                    lec_units_val = float(lec_units or 0)
                except Exception:
                    lab_units_val = lec_units_val = 0

                if lab_units_val > 0:
                    # Must be a laboratory room
                    if "lab" not in room_name and "laboratory" not in room_name:
                        continue
                elif lec_units_val > 0 or lab_units_val == 0:
                    # Must be a lecture room
                    if "lab" in room_name or "laboratory" in room_name:
                        continue

                # --- Continue to time slot assignment ---
                for t in time_slots:
                    if is_time_conflict(fobj.get('unavailable_parsed', []), t, buffer_minutes=60):
                        continue
                    allowed_combos[(sid, fid, rid, t)] = True
                    combos_by_subject[sid].append((fid, rid, t))


        if not combos_by_subject[sid]:
            if not faculty_candidates:
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

    for (sid, fid, rid, t) in allowed_combos.keys():
        idx = slot_index_by_label.get(t, 0)
        var = model.NewBoolVar(f"x_s{sid}_f{fid}_r{rid}_t{idx}")
        x[(sid, fid, rid, t)] = var

    # Each subject assigned at most once
    for subj in curriculum_subjects:
        sid = subj.get('id')
        vars_for_subj = [v for (s,f,r,t), v in x.items() if s == sid]
        if vars_for_subj:
            model.Add(sum(vars_for_subj) <= 1)

    # Faculty time conflicts
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

    # Room time conflicts
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

    # -----------------------------
    # STRICT FACULTY LOAD CONSTRAINTS USING UNITS - NO OVERLOAD ALLOWED
    # -----------------------------
    faculty_load_vars = {}

    for fobj in faculty:
        fid = fobj['id']
        
        # Calculate total units this faculty could be assigned (for upper bound)
        max_possible_units = 0
        assigned_vars_with_units = []
        
        for (sid, ff, rr, t), var in x.items():
            if ff == fid:
                subj = next((s for s in curriculum_subjects if s['id'] == sid), None)
                if subj:
                    units = subj.get('units', 3)  # Default to 3 units if not specified
                    max_possible_units += units
                    assigned_vars_with_units.append((var, units))
        
        # NO OVERLOAD ALLOWED FOR ANY FACULTY - using units
        max_load_units = fobj.get('max_load', 20 if fobj.get('is_full_time') else 6)  # Default to 20 for full-time, 6 for part-time
        
        # Create load variable in units
        load_var = model.NewIntVar(0, max_possible_units, f"load_f{fid}")
        
        # Sum of units for assigned subjects
        if assigned_vars_with_units:
            model.Add(load_var == sum(var * units for var, units in assigned_vars_with_units))
        else:
            model.Add(load_var == 0)
            
        # ENFORCE ABSOLUTE MAXIMUM LOAD IN UNITS - NO OVERLOAD
        model.Add(load_var <= max_load_units)
        faculty_load_vars[fid] = load_var

    # -----------------------------
    # OBJECTIVE FUNCTION - STRICT PRIORITIES WITHOUT OVERLOAD
    # -----------------------------
    objective_terms = []

    # 1. HIGHEST priority: Assign as many subjects as possible
    total_assigned = model.NewIntVar(0, len(curriculum_subjects), "total_assigned")
    model.Add(total_assigned == sum(x.values()))
    objective_terms.append(total_assigned * 10000)  # Very high priority to assign subjects

    # 2. Department matching (high priority)
    for (sid, fid, rid, t), var in x.items():
        subj = next((s for s in curriculum_subjects if s['id'] == sid), None)
        fobj = next((f for f in faculty if f['id'] == fid), None)
        if subj and fobj:
            subj_dept = subj.get('dept')
            faculty_dept = fobj.get("department") or fobj.get("dept") or fobj.get("year") or None
            if subj_dept and faculty_dept and subj_dept == faculty_dept:
                objective_terms.append(var * 5000)  # High bonus for department match

    # 3. Full-time faculty preference (medium priority)
    for (sid, fid, rid, t), var in x.items():
        fobj = next((f for f in faculty if f['id'] == fid), None)
        if fobj and fobj.get('is_full_time', True):
            objective_terms.append(var * 2000)  # Moderate bonus for full-time

    # 4. Load balancing - encourage full-time faculty to reach their target load (in units)
    for fobj in faculty:
        if fobj.get('is_full_time', True):
            fid = fobj['id']
            load_var = faculty_load_vars[fid]
            target_load = fobj.get('max_load', 20)  # Target in units
            
            # Reward for reaching target load (but not exceeding)
            objective_terms.append(load_var * 10)  # Small reward per unit

    # 5. Reward part-time faculty utilization
    for fobj in faculty:
        if not fobj.get('is_full_time', True):
            fid = fobj['id']
            load_var = faculty_load_vars[fid]
            # Reward part-time faculty for each unit assigned
            objective_terms.append(load_var * 15)  # Slightly higher reward to encourage usage

    # Maximize the objective
    model.Maximize(sum(objective_terms))

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
                
                # Calculate actual faculty load in units
                faculty_load_units = 0
                for (s2, f2, r2, t2), v2 in x.items():
                    if f2 == fid and solver.BooleanValue(v2):
                        subj2 = next((s for s in curriculum_subjects if s['id'] == s2), None)
                        if subj2:
                            faculty_load_units += subj2.get('units', 3)
                
                max_load_units = faculty_obj.get('max_load', 20 if faculty_obj.get('is_full_time') else 6)
                is_overloaded = faculty_load_units > max_load_units
                
                assigned.append({
                    "subject_id": sid,
                    "subject_title": subj_obj.get('subject_title') if subj_obj else None,
                    "course_id": subj_obj.get('course_id') if subj_obj else None,
                    "course_section": subj_obj.get('course_section') or course_obj.get('name') if course_obj else None,
                    "course_code": subj_obj.get('subject_code') if subj_obj else None,
                    "faculty_id": fid,
                    "faculty_name": faculty_obj.get('name') if faculty_obj else None,
                    "faculty_type": "full_time" if faculty_obj.get('is_full_time') else "part_time",
                    "faculty_department": faculty_obj.get('department') if faculty_obj else None,
                    "room_id": rid,
                    "room_name": room_obj.get('name') if room_obj else None,
                    "time_slot": t,
                    "units": subj_obj.get('units') if subj_obj else 0,
                    "faculty_load_units": faculty_load_units,
                    "max_load_units": max_load_units,
                    "is_overloaded": is_overloaded
                })
                assigned_subject_ids.add(sid)
    # Identify unassigned subjects
    for subj in curriculum_subjects:
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
                "department": subj.get('dept'),
                "possible_combos_count": len(combos_by_subject.get(sid, [])),
                "reasons": reasons
            })
    # Generate conflict reports
    conflicts = []
    for u in unassigned:
        if not combos_by_subject.get(u['subject_id']):
            for r in u['reasons']:
                if r == "no_faculty_for_dept":
                    msg = f"Subject '{u.get('subject_title')}' has no available faculty in its department ({u.get('department')})."
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
    # Check for overloaded faculty
    faculty_assignment_units = defaultdict(int)
    for a in assigned:
        fid = a.get('faculty_id')
       # Safely calculate total units for this subject
        lec = a.get('lec_units') or 0
        lab = a.get('lab_units') or 0
        total = a.get('units') or (lec + lab)

        faculty_assignment_units[fid] += total

        
    for fid, units in faculty_assignment_units.items():
        fobj = next((f for f in faculty if f.get('id') == fid), None)
        if fobj:
            max_load_units = fobj.get('max_load', 20 if fobj.get('is_full_time') else 6)
            if units > max_load_units:
                conflicts.append({
                    "type": "faculty_overload",
                    "faculty_id": fid,
                    "faculty_name": fobj.get('name'),
                    "assigned_units": units,
                    "max_load_units": max_load_units,
                    "description": f"Faculty '{fobj.get('name')}' is overloaded: {units} units vs max {max_load_units}"
                })
    # Check time overlaps
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

    # Check faculty unavailable time violations
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
    # Helper: force assign (DISABLED as requested)
    # -----------------------------
    def force_assign(subject_id, faculty_id, room_id, time_slot):
        return {"error": "force_assign_disabled"}
    for subj in curriculum_subjects:
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
                "department": subj.get('dept'),
                "possible_combos_count": len(combos_by_subject.get(sid, [])),
                "reasons": reasons
            })
    # -----------------------------
    # Populate possible_assignments for unassigned subjects
    # -----------------------------
    # Precompute current assigned load per faculty id (from solver assignments)
    # 1) build current loads from already assigned schedule
    current_load_by_faculty = defaultdict(int)
    for a in assigned:
        fid = int(a.get('faculty_id'))  # Ensure it's an integer

        if fid:
            try:
                current_load_by_faculty[fid] += int(a.get('units', 0))
            except Exception:
                current_load_by_faculty[fid] += (a.get('units', 0) or 0)

    # build occupied slot maps
    occupied_slots_by_faculty = defaultdict(set)
    occupied_slots_by_room = defaultdict(set)
    for a in assigned:
        t = a.get('time_slot')
        fid = a.get('faculty_id')
        rid = a.get('room_id')
        if t:
            if fid:
                occupied_slots_by_faculty[fid].add(t)
            if rid:
                occupied_slots_by_room[rid].add(t)

    top_per_faculty = 50
    heap_counter = itertools.count()

    # Iterate through unassigned subjects
    # Loop through unassigned subjects
    # Fetch all courses once and build a lookup dictionary
    
    # -----------------------------
    # FETCH COURSE SECTIONS FOR LOOKUP
    # -----------------------------
    try:
        # ✅ Use only existing columns
        cursor.execute("SELECT id, name, year, students, curriculum_id FROM courses")
        courses_all = cursor.fetchall() or []
        course_lookup = {c["id"]: c for c in courses_all}
        print(f"[DEBUG] Loaded {len(courses_all)} courses", file=sys.stderr)
        print(f"[DEBUG] Loaded {len(course_lookup)} courses for section lookup.", file=sys.stderr)
    except Exception as e:
        print(f"[ERROR] Could not fetch course data: {e}", file=sys.stderr)
        course_lookup = {}

    for u in unassigned:
        sid = u['subject_id']

        # Find the corresponding subject in the curriculum_subjects list
        subj_obj = next((s for s in curriculum_subjects if s.get('id') == sid), None)

        # --- Extract subject and course details ---
        subj_course_id = subj_obj.get('course_id') if subj_obj else None
        subj_course_code = subj_obj.get('subject_code') if subj_obj else None
        subj_units = subj_obj.get('units', 3) if subj_obj else (u.get('units') or 3)
        subj_dept = subj_obj.get('dept') if subj_obj else None

        # --- Fetch course info dynamically from DB ---
        subj_course_section = "-"
        subj_course_name = None

        if subj_course_id and subj_course_id in course_lookup:
            course_data = course_lookup[subj_course_id]
            subj_course_section = course_lookup.get(subj_course_id, {}).get('name', '-') if subj_course_id else "-"
            subj_course_name = f"Year {course_data.get('year', '-')}"  # optional descriptive name

        candidates_by_faculty = defaultdict(list)

        # Iterate through the possible combinations of faculty and rooms for the subject
        for fid, rid, t in combos_by_subject.get(sid, []):
            fobj = next((f for f in faculty if f.get('id') == fid), None)
            robj = next((r for r in rooms if r.get('id') == rid), None)
            if not fobj or not robj:
                continue

            # Skip if faculty or room already booked
            if t in occupied_slots_by_faculty[fid] or t in occupied_slots_by_room[rid]:
                continue

            try:
                slot_struct = parse_slot_label(t)
            except Exception:
                slot_struct = {"day": None, "start": None, "end": None}

            faculty_dept = fobj.get('department')
            dept_match = (subj_dept and faculty_dept and subj_dept == faculty_dept) or not subj_dept
            room_cap = robj.get('capacity')
            faculty_current = current_load_by_faculty.get(fid, 0)
            faculty_max = fobj.get('max_load', 20 if fobj.get('is_full_time') else 6)

            # Scoring logic
            score = 0
            if dept_match:
                score += 10000
            if faculty_max is not None and (faculty_current + subj_units) > faculty_max:
                score -= 5000
            if room_cap:
                try:
                    score += int(room_cap)
                except Exception:
                    pass
            if slot_struct.get("start") is not None:
                score -= slot_struct.get("start")

            candidate = {
                "faculty_id": fid,
                "faculty_name": fobj.get('name'),
                "faculty_department": faculty_dept,
                "faculty_type": "full_time" if fobj.get('is_full_time') else "part_time",
                "faculty_current_load": faculty_current,
                "faculty_max_load": faculty_max,
                "room_id": rid,
                "room_name": robj.get('name'),
                "room_capacity": room_cap,
                "time_slot_label": t,
                "time_day": slot_struct.get("day"),
                "time_start": slot_struct.get("start"),
                "time_end": slot_struct.get("end"),
                "department_match": dept_match,
                "score": score,
            }

            heapq.heappush(candidates_by_faculty[fid], (-score, next(heap_counter), candidate))

        # Flatten the top N candidates
        combined = []
        for fid, heap in candidates_by_faculty.items():
            top_n = heapq.nsmallest(top_per_faculty, heap)
            for _, _, cand in top_n:
                combined.append(cand)

        possible = sorted(combined, key=lambda x: x.get("score", 0), reverse=True)
        print(f"[DEBUG] Subject {sid} ({subj_course_code or 'NoCode'}) → Possible={len(possible)}",file=sys.stderr
            )
        # ✅ Add subject details including course info
        u["subject_display"] = f"{(subj_course_code or '')} - {(u.get('subject_title') or subj_obj.get('subject_title') or 'Untitled')}"
        u["subject_code"] = subj_course_code
        u["course_section"] = subj_course_section
        u["course_name"] = subj_course_name
        u["possible_assignments"] = possible

    # --- SUMMARY BLOCK ---
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

    # ✅ Return with clean serializable unassigned subjects (includes course_section)
    return {
        "success": True,
        "message": "Schedule generation finished",
        "schedule": assigned,
        "unassigned": [
            {
                "subject_id": u.get("subject_id"),
                "subject_title": u.get("subject_title"),
                "subject_code": u.get("subject_code"),
                "course_section": u.get("course_section", "-"),
                "units": u.get("units", 3),
                "possible_assignments": u.get("possible_assignments", []),
                "subject_display": u.get("subject_display", ""),
            }
            for u in unassigned
        ],
        "summary": summary,
        "conflicts": conflicts
    }


if __name__ == "__main__":
    import json, sys, argparse, traceback

    parser = argparse.ArgumentParser()
    parser.add_argument("--academic_year", type=str, default=None)
    parser.add_argument("--semester", type=str, default=None)  # can be '1', '2', '1st Semester', etc.
    args = parser.parse_args()

    academic_year = args.academic_year
    semester_id = args.semester  # keep as str or int

    try:
        out = generate_schedule(academic_year=academic_year, semester_id=semester_id)
        out_for_json = {k: v for k, v in out.items() if k != "force_assign_fn"}
        print(json.dumps(out_for_json, ensure_ascii=False, separators=(',', ':')))
    except Exception as e:
        output = {"success": False, "message": str(e), "trace": traceback.format_exc()}
        print(json.dumps(output, ensure_ascii=False, indent=2))