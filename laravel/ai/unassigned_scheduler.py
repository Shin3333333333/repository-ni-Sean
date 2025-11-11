# fixed_scheduler_load_balanced_debug_with_unassigned_and_force.py
from ortools.sat.python import cp_model
import re
import json
# ... existing code ...
import json
import sys
import argparse
from collections import defaultdict
import heapq
import itertools

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

def is_time_conflict(parsed_unavail_ranges, slot_label, buffer_minutes=60, slot_struct=None):
    if not parsed_unavail_ranges:
        return False
    
    current_slot_struct = slot_struct
    if current_slot_struct is None:
        try:
            current_slot_struct = parse_slot_label(slot_label)
        except Exception:
            return True
    
    for rng in parsed_unavail_ranges:
        buf_start = max(0, rng["start"] - buffer_minutes)
        buf_end = min(24*60, rng["end"] + buffer_minutes)
        if current_slot_struct["day"] != rng["day"]:
            continue
        if intervals_overlap(current_slot_struct["start"], current_slot_struct["end"], buf_start, buf_end):
            return True
    return False

def infer_subject_department(subj, department_prefix_map, department_info):
    subj_code = (subj.get('subject_code') or '').upper()
    subj_title = (subj.get('subject_title') or '').upper()

    # 1. Prefix-based inference (highest priority)
    for prefix, dept_name in department_prefix_map.items():
        if subj_code.startswith(prefix):
            return dept_name, 'prefix'

    # 2. Keyword-based inference
    for lower_dept_name, info in department_info.items():
        for keyword in info['keywords']:
            if re.search(rf"\b{re.escape(keyword)}\b", subj_title):
                return info['name'], 'keyword'
    
    return None, None

def generate_schedule(academic_year=None, semester_id=None, json_payload=None, max_solve_seconds=120, search_workers=4):
    import sys
    import argparse

    # Only parse CLI args if this is run as main
    
    # -----------------------------
    # LOAD DATA FROM JSON PAYLOAD
    # -----------------------------
    if json_payload is None:
        print("[ERROR] No JSON payload provided.", file=sys.stderr)
        return {"success": False, "message": "No JSON data provided."}

    # -----------------------------
    # FETCH ANCILLARY DATA
    # -----------------------------
    rooms_all = json_payload.get("rooms", [])
    for r in rooms_all:
        r['capacity'] = r.get('capacity') or r.get('max_load') or r.get('size') or None
        r['status'] = (r.get('status') or "").lower()
    rooms = [r for r in rooms_all if (r.get("status") or "") in ("", "available", "active", None)]
    room_by_id = {r['id']: r for r in rooms}
    
    
    faculty_all = json_payload.get("faculty", [])
    for f in faculty_all:
        f['status'] = (f.get('status') or "").lower()
        f['time_unavailable'] = f.get('time_unavailable') or f.get('unavailable') or None
        f['is_full_time'] = (f.get('type') or "").lower() == "full-time"

    faculty = [f for f in faculty_all if (f.get("status") in ("active", "available"))]

    for f in faculty:
        unavails = f.get('time_unavailable')
        if isinstance(unavails, list):
            f['parsed_unavailability'] = [parse_unavailable_range(u) for u in unavails if u]
        elif isinstance(unavails, str):
            f['parsed_unavailability'] = [parse_unavailable_range(u) for u in unavails.split(',') if u]
        else:
            f['parsed_unavailability'] = []

    # Create a lookup for faculty by ID for faster access
    faculty_by_id = {f['id']: f for f in faculty}


    courses = json_payload.get("courses", [])
    course_by_id = {c.get('id'): c for c in courses}
    print(f"[DEBUG] Loaded {len(course_by_id)} courses for section lookup.", file=sys.stderr)

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
                semester_map = {"1st Semester": 1, "2nd Semester": 2, "Summer": 3}
                semester_id = semester_map.get(semester_id, None)
        elif isinstance(semester_id, int):
            pass  # already fine
        else:
            semester_id = None

    # Only allow semester_id 1 and 2, reject others
    if semester_id not in [1, 2, 3]:
        print(f"[ERROR] Invalid semester_id: {semester_id}. Only semester_id 1, 2 and 3 are allowed.", file=sys.stderr)
        return {
            "success": False,
            "message": f"Invalid semester_id: {semester_id}. Only semester_id 1, 2 and 3 are allowed.",
            "schedule": [],
            "unassigned": [],
            "summary": {
                "total_curriculum_subjects": 0,
                "total_assigned": 0,
                "total_unassigned": 0,
                "assigned_ids": [],
                "unassigned_ids": []
            },
            "conflicts": []
        }

    print(f"[DEBUG] semester_id before query: {semester_id}", file=sys.stderr)

    # -----------------------------
    # FETCH SUBJECTS (filtered by semester)
    # -----------------------------
    print(f"[DEBUG] semester_id before query: {semester_id}", file=sys.stderr)

    # Quick check: return empty if semester has no subjects
    if semester_id is not None:
        subjects_rows = json_payload.get("subjects", [])
        if not subjects_rows:
            print(f"[DEBUG] No subjects for semester_id {semester_id}, returning empty schedule.", file=sys.stderr)
            return {
                "success": True,
                "message": "No subjects for this semester",
                "schedule": [],
                "unassigned": [],
                "summary": {
                    "total_curriculum_subjects": 0,
                    "total_assigned": 0,
                    "total_unassigned": 0,
                    "assigned_ids": [],
                    "unassigned_ids": []
                },
                "conflicts": [],
                "force_assign_fn": lambda *a, **k: {"error": "force_assign_not_available"}
            }

    # FETCH SUBJECTS (filtered by semester + course year)
    # -----------------------------
    subjects_rows = json_payload.get("subjects", [])
    pre_assigned_subjects = json_payload.get("pre_assigned_subjects", [])

    # Get the IDs of the pre-assigned subjects
    pre_assigned_subject_ids = {s['id'] for s in pre_assigned_subjects}

    # Filter out the pre-assigned subjects from the subjects_rows list
    subjects_rows = [s for s in subjects_rows if s.get('id') not in pre_assigned_subject_ids]

    # DEBUG: Only process the first subject
    if subjects_rows:
        subjects_rows = [subjects_rows[0]]
        print(f"[DEBUG] TESTING WITH A SINGLE SUBJECT: {subjects_rows[0]}", file=sys.stderr)

    # Create constraints from pre-assigned subjects
    occupied_faculty_slots = defaultdict(list)
    occupied_room_slots = defaultdict(list)
    occupied_section_slots = defaultdict(list)

    for pa in pre_assigned_subjects:
        fid = pa.get('faculty_id')
        rid = pa.get('room_id')
        t_label = pa.get('time_slot') or pa.get('time')
        section = pa.get('course_section')

        if not t_label:
            continue

        try:
            parsed_slot = parse_slot_label(t_label)
            if fid:
                occupied_faculty_slots[fid].append(parsed_slot)
            if rid:
                occupied_room_slots[rid].append(parsed_slot)
            if section:
                occupied_section_slots[section].append(parsed_slot)
        except (ValueError, AttributeError):
            print(f"[WARNING] Could not parse time_slot for pre-assigned subject {pa.get('id')}: {t_label}", file=sys.stderr)


    print(f"[DEBUG] Raw query returned {len(subjects_rows)} subjects with valid course_id and semester_id={semester_id}", file=sys.stderr)

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
        norm['course_section'] = norm.get('course_section') or ''
        course_obj = course_by_id.get(norm.get('course_id'))
        norm['course_name'] = course_obj.get('name') if course_obj else None
        subjects.append(norm)

    print(f"[DEBUG] Normalized {len(subjects)} subjects after processing", file=sys.stderr)
    
    if len(subjects) < len(subjects_rows):
        print(f"[WARNING] Some subjects were filtered during normalization. Expected {len(subjects_rows)}, got {len(subjects)}", file=sys.stderr)


    # All subjects from the query already have valid course_id due to INNER JOIN
    curriculum_subjects = subjects
    subject_by_id = {s['id']: s for s in curriculum_subjects}


    print(f"[DEBUG] Loaded {len(curriculum_subjects)} valid subjects for semester {semester_id} and matching course year.", file=sys.stderr)
    print(f"[DEBUG] semester_id resolved: {semester_id}", file=sys.stderr)
    print(f"[DEBUG] Loaded {len(curriculum_subjects)} subjects for this semester.", file=sys.stderr)

    # -----------------------------
    # TIME SLOTS
    # -----------------------------
    days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
    
    # 3-hour slots for subjects with >= 3 units
    time_slots_3_hours = []
    for day in days:
        for start in range(6, 19): # 6am to 6pm start
            end = start + 3
            if end <= 21:
                time_slots_3_hours.append(f"{day} {start:02d}:00-{end:02d}:00")

    # 2-hour slots for subjects with < 3 units
    time_slots_2_hours = []
    for day in days:
        for start in range(6, 20): # 6am to 7pm start
            end = start + 2
            if end <= 21:
                time_slots_2_hours.append(f"{day} {start:02d}:00-{end:02d}:00")

    # Combined list for overlap calculations and indexing
    time_slots = sorted(list(set(time_slots_3_hours + time_slots_2_hours)))

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
            # Avoid eval for safety; support JSON array or comma-separated
            try:
                parsed_json = json.loads(raw)
                if isinstance(parsed_json, (list, tuple)):
                    parts = [str(p).strip() for p in parsed_json if str(p).strip()]
                else:
                    parts = [p.strip() for p in raw.split(",") if p.strip()]
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
    # -----------------------------
    # Hybrid subject department inference
    # -----------------------------
    department_info = json_payload.get("departments", {})
    department_prefix_map = {}
    # The `departments.json` file has department names as keys.
    # We iterate through them to build a prefix map for quick lookups.
    for lower_dept_name, info in department_info.items():
        # Ensure prefixes is a list before iterating
        prefixes = info.get('prefixes', [])
        if prefixes:
            for prefix in prefixes:
                department_prefix_map[prefix] = info.get('name')

    if not department_info:
        print("[WARNING] No department data found in the JSON payload. Department-based scoring will be limited.", file=sys.stderr)
    else:
        print(f"[DEBUG] Loaded {len(department_info)} departments from the JSON payload.", file=sys.stderr)


    # Step 1: infer departments for all subjects
    for subj in curriculum_subjects:
        inferred_dept, reason = infer_subject_department(subj, department_prefix_map, department_info)
        if inferred_dept:
            subj['dept'] = inferred_dept
            # print(f"[DEBUG] Inferred department for '{subj.get('subject_code')}': {inferred_dept} (reason: {reason})", file=sys.stderr)
        else:
            subj['dept'] = 'Unassigned'
            # print(f"[WARNING] Could not infer department for subject: {subj.get('subject_code')}", file=sys.stderr)
        subj['assigned'] = False  # track assignment
    
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
   # -----------------------------
    # BUILD ALLOWED ASSIGNMENT COMBOS (DEPARTMENT-FIRST + LAB HANDLING)
    # -----------------------------
    allowed_combos = {}
    subject_feasible_reasons = defaultdict(list)
    combos_by_subject = defaultdict(list)

    # 1️⃣ Group subjects by department
    subjects_by_dept = defaultdict(list)
    for s in curriculum_subjects:
        subjects_by_dept[s['dept']].append(s)

    # 2️⃣ Track total subjects per department per faculty
    faculty_total_dept_counts = defaultdict(lambda: defaultdict(int))
    faculty_assigned_dept_counts = defaultdict(lambda: defaultdict(int))
    for dept, subj_list in subjects_by_dept.items():
        for f in faculty_by_dept_and_type.get(dept, {}).get('full_time', []) + \
                faculty_by_dept_and_type.get(dept, {}).get('part_time', []):
            faculty_total_dept_counts[f['id']][dept] = len(subj_list)

    # 3️⃣ Build allowed combos with STRICT PRIORITY PHASES
    # Phase A: only perfect matches (dept + prefix on course code and/or subject-name keyword)
    perfect_match_pairs = defaultdict(list)  # sid -> list of faculty ids
    for subj in curriculum_subjects:
        sid = subj.get('id')
        subj_dept = (subj.get('dept') or '').lower()
        subj_code = (subj.get('subject_code') or '').upper()
        subj_title = (subj.get('subject_title') or '').upper()
        for fobj in faculty:
            fid = fobj.get('id')
            faculty_dept = (fobj.get('department') or fobj.get('dept') or '').lower()
            if not faculty_dept or faculty_dept != subj_dept:
                continue
            prefix_match = any(subj_code.startswith(p) for p in department_prefix_map.keys())
            keyword_match = any(re.search(rf"\\b{re.escape(kw)}\\b", subj_title) for kw in department_info.get((fobj.get('department') or '').lower(), {}).get('keywords', []))
            if prefix_match or keyword_match:
                perfect_match_pairs[sid].append(fid)

    # Build combos ONLY for perfect matches first
    for subj in curriculum_subjects:
        sid = subj.get('id')
        subj_dept = subj.get('dept')
        subj_code = (subj.get('subject_code') or '').upper()
        subj_title = (subj.get('subject_title') or '').upper()
        course_obj = course_by_id.get(subj.get('course_id'))
        students = 1
        lec_units_val = float(subj.get('lec_units', 0) or 0)
        lab_units_val = float(subj.get('lab_units', 0) or 0)
        subj_units = int(subj.get('units', 3))

        candidate_fids = perfect_match_pairs.get(sid, [])
        for fobj in faculty:
            fid = fobj.get('id')
            if fid not in candidate_fids:
                continue
            for r in rooms:
                rid = r.get('id')
                cap = r.get('capacity') or r.get('max_load') or None
                if cap is not None and int(cap) < students:
                    continue
                room_name = (r.get('name') or '').lower()
                if lab_units_val > 0 and ('lab' not in room_name and 'laboratory' not in room_name):
                    continue
                if lab_units_val == 0 and ('lab' in room_name or 'laboratory' in room_name):
                    continue
                
                current_time_slots = time_slots_2_hours if subj_units < 3 else time_slots_3_hours
                for t in current_time_slots:
                    slot_struct = slot_structs[slot_index_by_label[t]]
                    section = subj.get('course_section')

                    # Check for conflicts
                    if is_time_conflict(fobj.get('unavailable_parsed', []), t, buffer_minutes=60, slot_struct=slot_struct) or \
                       is_time_conflict(occupied_faculty_slots.get(fid, []), t, slot_struct=slot_struct) or \
                       is_time_conflict(occupied_room_slots.get(rid, []), t, slot_struct=slot_struct) or \
                       is_time_conflict(occupied_section_slots.get(section, []), t, slot_struct=slot_struct):
                        continue

                    allowed_combos[(sid, fid, rid, t)] = True
                    combos_by_subject[sid].append((fid, rid, t))

    # Phase B: fallback only if no perfect combos exist for the subject OR faculty still need load later
    for subj in curriculum_subjects:
        sid = subj.get('id')
        subj_dept = (subj.get('dept') or '').lower()
        subj_code = (subj.get('subject_code') or '').upper()
        subj_title = (subj.get('subject_title') or '').upper()
        course_obj = course_by_id.get(subj.get('course_id'))
        students = 1
        lec_units_val = float(subj.get('lec_units', 0) or 0)
        lab_units_val = float(subj.get('lab_units', 0) or 0)
        subj_units = int(subj.get('units', 3))

        # If there are already perfect combos, skip adding cross-dept here for this subject
        has_perfect = bool(combos_by_subject.get(sid))
        
        # Fallback priority: dept + subject name match (not necessarily prefix), then dept only
        for fobj in faculty:
            fid = fobj.get('id')
            faculty_dept = (fobj.get('department') or fobj.get('dept') or '').lower()
            if faculty_dept == subj_dept:
                # skip if already in perfect set for this subject
                if fid in perfect_match_pairs.get(sid, []):
                    continue
                # Dept-only fallback allowed, but lower priority; still build combos (solver objective handles priority)
                for r in rooms:
                    rid = r.get('id')
                    cap = r.get('capacity') or r.get('max_load') or None
                    if cap is not None and int(cap) < students:
                        continue
                    room_name = (r.get('name') or '').lower()
                    if lab_units_val > 0 and ('lab' not in room_name and 'laboratory' not in room_name):
                        continue
                    if lab_units_val == 0 and ('lab' in room_name or 'laboratory' in room_name):
                        continue
                    
                    current_time_slots = time_slots_2_hours if subj_units < 3 else time_slots_3_hours
                    for t in current_time_slots:
                        slot_struct = slot_structs[slot_index_by_label[t]]
                        section = subj.get('course_section')

                        if is_time_conflict(fobj.get('unavailable_parsed', []), t, buffer_minutes=60, slot_struct=slot_struct) or \
                           is_time_conflict(occupied_faculty_slots.get(fid, []), t, slot_struct=slot_struct) or \
                           is_time_conflict(occupied_room_slots.get(rid, []), t, slot_struct=slot_struct) or \
                           is_time_conflict(occupied_section_slots.get(section, []), t, slot_struct=slot_struct):
                            continue

                        allowed_combos[(sid, fid, rid, t)] = True
                        combos_by_subject[sid].append((fid, rid, t))

        # Cross-department fallback only if still no combos (to reduce unassigned) — very low priority
        if not combos_by_subject.get(sid):
            for fobj in faculty:
                fid = fobj.get('id')
                faculty_dept = (fobj.get('department') or fobj.get('dept') or '').lower()
                if faculty_dept == subj_dept:
                    continue
                # Allow cross dept only if subject title keywords overlap faculty dept keywords
                keyword_match = any(re.search(rf"\\b{re.escape(kw)}\\b", subj_title) for kw in department_info.get((fobj.get('department') or '').lower(), {}).get('keywords', []))
                if not keyword_match:
                    continue

                subj_units = int(subj.get('units', 3))
                for r in rooms:
                    rid = r.get('id')
                    cap = r.get('capacity') or r.get('max_load') or None
                    if cap is not None and int(cap) < students:
                        continue
                    room_name = (r.get('name') or '').lower()
                    if lab_units_val > 0 and ('lab' not in room_name and 'laboratory' not in room_name):
                        continue
                    if lab_units_val == 0 and ('lab' in room_name or 'laboratory' in room_name):
                        continue
                    
                    current_time_slots = time_slots_2_hours if subj_units < 3 else time_slots_3_hours
                    for t in current_time_slots:
                        slot_struct = slot_structs[slot_index_by_label[t]]
                        section = subj.get('course_section')

                        if is_time_conflict(fobj.get('unavailable_parsed', []), t, buffer_minutes=60, slot_struct=slot_struct) or \
                           is_time_conflict(occupied_faculty_slots.get(fid, []), t, slot_struct=slot_struct) or \
                           is_time_conflict(occupied_room_slots.get(rid, []), t, slot_struct=slot_struct) or \
                           is_time_conflict(occupied_section_slots.get(section, []), t, slot_struct=slot_struct):
                            continue

                        allowed_combos[(sid, fid, rid, t)] = True
                        combos_by_subject[sid].append((fid, rid, t))


        # --- 3d. Feasibility checks ---
        if not combos_by_subject[sid]:
            if not combos_by_subject.get(sid):
                subject_feasible_reasons[sid].append("no_faculty_for_dept")
            else:
                rooms_ok = [r for r in rooms if (r.get('capacity') or r.get('max_load') or 0) >= (students or 1)]
                if not rooms_ok:
                    subject_feasible_reasons[sid].append("no_room_with_capacity")
                else:
                    subject_feasible_reasons[sid].append("faculty_unavailable_for_all_slots")
        # -----------------------------
    # 4️⃣ FILL REMAINING SUBJECTS — CROSS-DEPARTMENT & GE FALLBACK
    # -----------------------------
    # 🔧 Compute total units already assigned per faculty
    faculty_total_units = defaultdict(float)
    for subj in curriculum_subjects:
        sid = subj.get('id')
        lec = float(subj.get('lec_units', 0) or 0)
        lab = float(subj.get('lab_units', 0) or 0)
        total_units = lec + lab

        # find if subject already assigned (optional, if you track assignments)
        for fid, rid, t in combos_by_subject[sid]:
            faculty_total_units[fid] += total_units

    # -----------------------------
    # 4️⃣ FILL REMAINING SUBJECTS — EXPANDED FALLBACK TO HELP REACH MAX LOAD
    # -----------------------------
    unassigned_subjects = [s for s in curriculum_subjects if s['id'] not in combos_by_subject or not combos_by_subject[s['id']]]

    # Track conservative current load estimate (units) based on potential combos we added above
    faculty_total_units = defaultdict(float)

    if unassigned_subjects:
        for subj in unassigned_subjects:
            sid = subj['id']
            subj_dept = subj['dept']
            subj_title = subj.get('subject_title', "").upper()
            subj_students = int(course_by_id.get(subj.get('course_id'), {}).get('students', 1) or 1)
            subj_units = float(subj.get('units') or (subj.get('lec_units', 0) or 0) + (subj.get('lab_units', 0) or 0))

            # Consider all faculty under their max load for broader fallback
            fallback_candidates = []
            for f in faculty:
                fid = f['id']
                max_load = float(f.get('max_load', 20 if f.get('is_full_time') else 6))
                current_load = float(faculty_total_units.get(fid, 0))
                remaining_load = max_load - current_load
                if remaining_load + 1e-6 >= subj_units:  # allow tiny epsilon
                    score = 0
                    faculty_dept = f.get('department') or ''
                    
                    # Perfect match scoring (highest priority)
                    if faculty_dept.lower() == subj_dept.lower():
                        # Check for prefix match (highest priority)
                        prefix_match = False
                        for prefix in department_prefix_map.keys():
                            if subj_code.startswith(prefix):
                                prefix_match = True
                                break
                        
                        if prefix_match:
                            score += 1000  # Highest priority for prefix match
                        else:
                            # Check for keyword match
                            keyword_match = False
                            for kw in department_info.get(faculty_dept.lower(), {}).get('keywords', []):
                                if re.search(rf'\b{re.escape(kw)}\b', subj_title):
                                    keyword_match = True
                                    break
                            
                            if keyword_match:
                                score += 500  # High priority for keyword match
                            else:
                                score += 100  # Department match only
                    else:
                        # Cross-department keyword match
                        for kw in department_info.get(faculty_dept.lower(), {}).get('keywords', []):
                            if re.search(rf'\b{re.escape(kw)}\b', subj_title):
                                score += 10
                    
                    # prefer full-time to fill loads, but still use part-time when needed
                    if f.get('is_full_time'):
                        score += 5
                    # lower score when very close to max (to spread a bit)
                    score -= int(max(0, (max_load - (current_load + subj_units)) < 1)) * 2
                    fallback_candidates.append((score, f))

            fallback_candidates.sort(key=lambda x: -x[0])

            # Try to add feasible combos for these fallback faculty
            created_any = False
            for score, fobj in fallback_candidates:
                fid = fobj['id']
                for r in rooms:
                    rid = r.get('id')
                    cap = r.get('capacity') or r.get('max_load') or None
                    if cap is not None and int(cap) < subj_students:
                        continue

                    room_name = (r.get('name') or '').lower()
                    lec_units_val = float(subj.get('lec_units', 0) or 0)
                    lab_units_val = float(subj.get('lab_units', 0) or 0)
                    if lab_units_val > 0 and ('lab' not in room_name and 'laboratory' not in room_name):
                        continue
                    if lab_units_val == 0 and ('lab' in room_name or 'laboratory' in room_name):
                        continue

                    for t in time_slots:
                        slot_struct = slot_structs[slot_index_by_label[t]]
                        cid = subj.get('course_id')

                        if is_time_conflict(fobj.get('unavailable_parsed', []), t, buffer_minutes=60, slot_struct=slot_struct) or \
                           is_time_conflict(occupied_faculty_slots.get(fid, []), t, slot_struct=slot_struct) or \
                           is_time_conflict(occupied_room_slots.get(rid, []), t, slot_struct=slot_struct) or \
                           is_time_conflict(occupied_section_slots.get(cid, []), t, slot_struct=slot_struct):
                            continue

                        allowed_combos[(sid, fid, rid, t)] = True
                        combos_by_subject[sid].append((fid, rid, t))
                        subject_feasible_reasons[sid].append('fallback_assignment')
                        faculty_total_units[fid] += subj_units
                        created_any = True
                        break
                    if created_any:
                        break
                    if created_any:
                        break

            if not created_any and not combos_by_subject[sid]:
                subject_feasible_reasons[sid].append('no_available_faculty_even_in_fallback')


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

    # Course section (by course_id) time conflicts
    # Ensure a section (course) cannot have two classes that overlap in time
    course_ids = set(s.get('course_id') for s in curriculum_subjects if s.get('course_id') is not None)
    for cid in course_ids:
        for i in range(num_slots):
            vars_overlapping = []
            for (s, ff, rr, t), v in x.items():
                subj = subject_by_id.get(s)
                if not subj or subj.get('course_id') != cid:
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
                subj = subject_by_id.get(sid)
                if subj:
                    units = int(subj.get('units', 3))  # Default to 3 units if not specified
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

    # 2. Perfect matching (highest priority) — enforce: dept + prefix and/or subject name keyword
    for (sid, fid, rid, t), var in x.items():
        subj = subject_by_id.get(sid)
        fobj = faculty_by_id.get(fid)
        if subj and fobj:
            subj_dept = (subj.get('dept') or '').lower()
            faculty_dept = (fobj.get('department') or '').lower()
            
            if subj_dept and faculty_dept and subj_dept == faculty_dept:
                # Check for prefix match
                subj_code = (subj.get('subject_code') or '').upper()
                is_prefix_match = False
                if subj_dept in department_info:
                    for prefix in department_info[subj_dept].get('prefixes', []):
                        if subj_code.startswith(prefix):
                            is_prefix_match = True
                            break
                
                # Check for keyword match
                subj_title = (subj.get('subject_title') or '').upper()
                is_keyword_match = False
                if subj_dept in department_info:
                    for keyword in department_info[subj_dept].get('keywords', []):
                        if re.search(rf"\b{re.escape(keyword)}\b", subj_title):
                            is_keyword_match = True
                            break

                if is_prefix_match:
                    objective_terms.append(var * 30000)
                elif is_keyword_match:
                    objective_terms.append(var * 22000)
                else:
                    objective_terms.append(var * 8000)

    # 3. Full-time faculty preference (medium priority)
    for (sid, fid, rid, t), var in x.items():
        fobj = faculty_by_id.get(fid)
        if fobj and fobj.get('is_full_time', True):
            objective_terms.append(var * 2000)  # Moderate bonus for full-time

    # 4. Load shaping — push faculty loads toward their max (without exceeding)
    for fobj in faculty:
        fid = fobj['id']
        load_var = faculty_load_vars[fid]
        max_load = fobj.get('max_load', 20 if fobj.get('is_full_time') else 6)
        # Reward proximity to max load: larger coefficient to reduce unassigned where possible
        objective_terms.append(load_var * 50)

    # Add explicit penalty for cross-department assignments to reinforce preference
    for (sid, fid, rid, t), var in x.items():
        subj = subject_by_id.get(sid)
        fobj = faculty_by_id.get(fid)
        if subj and fobj:
            subj_dept = subj.get('dept')
            faculty_dept = fobj.get('department')
            if subj_dept and faculty_dept and subj_dept != faculty_dept:
                objective_terms.append(var * -3000)

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
                subj_obj = subject_by_id.get(sid)
                course_obj = course_by_id.get(subj_obj.get('course_id') if subj_obj else None)
                faculty_obj = faculty_by_id.get(fid)
                room_obj = room_by_id.get(rid)
                
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
                })
                assigned_subject_ids.add(sid)

        # Calculate faculty loads and add to assignments
        faculty_loads = defaultdict(int)
        for a in assigned:
            faculty_loads[a['faculty_id']] += a.get('units', 0)

        for a in assigned:
            fid = a['faculty_id']
            fobj = faculty_by_id.get(fid)
            max_load = fobj.get('max_load', 20 if fobj.get('is_full_time') else 6)
            current_load = faculty_loads[fid]
            a['faculty_load_units'] = current_load
            a['max_load_units'] = max_load
            a['is_overloaded'] = current_load > max_load
    
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
        fobj = faculty_by_id.get(fid)
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
    section_slots = defaultdict(list)
    for a in assigned:
        fid = a.get('faculty_id')
        rid = a.get('room_id')
        t = a.get('time_slot')
        cid = a.get('course_id')
        if t not in slot_index_by_label:
            continue
        ti = slot_index_by_label[t]
        fac_slots[fid].append((ti, a))
        room_slots[rid].append((ti, a))
        if cid is not None:
            section_slots[cid].append((ti, a))

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
    for cid, lst in section_slots.items():
        detect_overlaps(lst, "section")

    # Check faculty unavailable time violations
    for a in assigned:
        fid = a.get('faculty_id')
        t = a.get('time_slot')
        fobj = faculty_by_id.get(fid)
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

    top_per_faculty = 100
    heap_counter = itertools.count()

    # Iterate through unassigned subjects
    # Loop through unassigned subjects
    # Fetch all courses once and build a lookup dictionary
    
    # -----------------------------
    # FETCH COURSE SECTIONS FOR LOOKUP
    # -----------------------------
    # course_lookup is redundant, course_by_id is already defined and used.
    # This block is maintained for compatibility but should be reviewed for removal.
    try:
        course_lookup = {c.get("id"): c for c in courses if c.get("id")}
        print(f"[DEBUG] Loaded {len(courses)} courses for section lookup.", file=sys.stderr)
    except Exception as e:
        print(f"[ERROR] Could not fetch course data for lookup: {e}", file=sys.stderr)
        # print(traceback.format_exc(), file=sys.stderr) # Too verbose for this non-critical error
        course_lookup = {}

    # Precompute parsed slot info once to avoid repeated parsing
    parsed_slot_info = {t: parse_slot_label(t) for t in time_slots}

    for u in unassigned:
        sid = u['subject_id']
        subj_obj = subject_by_id.get(sid)
        if not subj_obj:
            continue

        subj_course_id = subj_obj.get('course_id')
        subj_course_code = subj_obj.get('subject_code')
        subj_units = subj_obj.get('units', 3)
        subj_dept = subj_obj.get('dept')
        # Determine subject delivery type for room preference scoring
        lec_units_val = float(subj_obj.get('lec_units', 0) or 0)
        lab_units_val = float(subj_obj.get('lab_units', 0) or 0)
        is_lecture_only = lab_units_val <= 0
        is_lab_subject = lab_units_val > 0

        subj_course_section = course_lookup.get(subj_course_id, {}).get('name', '-') if subj_course_id else '-'
        subj_course_name = ("Year " + str(course_lookup.get(subj_course_id, {}).get('year', '-'))) if subj_course_id else None

        # Build suggestions per faculty to ensure coverage, with per-faculty caps and overload options
        MAX_PER_FACULTY = 25
        MAX_PER_FACULTY_OVERLOAD = 10
        MAX_GLOBAL_SUGGESTIONS = 800
        merged = []
        for fobj in faculty:
            fid = fobj['id']
            faculty_current_load = current_load_by_faculty.get(fid, 0)
            faculty_max = fobj.get('max_load', 20 if fobj.get('is_full_time') else 6)
            dept_match = subj_dept == fobj.get('department')

            # In-limit heap
            in_heap = []
            # Overload heap (marked for override use)
            over_heap = []
            counter = itertools.count()

            for r in rooms:
                rid = r['id']
                room_cap = r.get('capacity') or r.get('max_load') or 0
                if room_cap < 1:
                    continue

                current_time_slots = time_slots_2_hours if subj_units < 3 else time_slots_3_hours
                for t in current_time_slots:
                    if t in occupied_slots_by_faculty[fid] or t in occupied_slots_by_room[rid]:
                        continue
                    if is_time_conflict(fobj.get('unavailable_parsed', []), t, buffer_minutes=60):
                        continue

                    info = parsed_slot_info[t]
                    score = 0
                    if dept_match:
                        score += 10000
                    score += room_cap
                    score -= info.get('start', 0)

                    # Room type preference: prefer lecture rooms for lecture-only subjects,
                    # and lab rooms for subjects with lab units. Do not exclude other rooms.
                    room_name_lower = (r.get('name') or '').lower()
                    is_lab_room = ('lab' in room_name_lower) or ('laboratory' in room_name_lower)
                    if is_lecture_only and is_lab_room:
                        score -= 500  # discourage lab rooms for lecture-only subjects
                    elif is_lab_subject and is_lab_room:
                        score += 800  # prefer lab rooms for lab subjects
                    elif is_lab_subject and not is_lab_room:
                        score -= 300  # slight penalty for non-lab room on lab subjects

                    base_item = {
                        'faculty_id': fid,
                        'faculty_name': fobj.get('name'),
                        'faculty_department': fobj.get('department'),
                        'faculty_type': 'full_time' if fobj.get('is_full_time') else 'part_time',
                        'faculty_current_load': faculty_current_load,
                        'faculty_max_load': faculty_max,
                        'room_id': rid,
                        'room_name': r.get('name'),
                        'room_capacity': room_cap,
                        'time_slot_label': t,
                        'time_day': info.get('day'),
                        'time_start': info.get('start'),
                        'time_end': info.get('end'),
                        'department_match': dept_match,
                        'score': score,
                    }

                    would_overload = (faculty_current_load + subj_units) > faculty_max
                    idx = next(counter)
                    if not would_overload:
                        if len(in_heap) < MAX_PER_FACULTY:
                            heapq.heappush(in_heap, (score, idx, base_item))
                        else:
                            if in_heap[0][0] < score:
                                heapq.heapreplace(in_heap, (score, idx, base_item))
                    else:
                        overload_amount = (faculty_current_load + subj_units) - faculty_max
                        item = dict(base_item)
                        item['would_exceed_max_load'] = True
                        item['overload_units'] = overload_amount
                        if len(over_heap) < MAX_PER_FACULTY_OVERLOAD:
                            heapq.heappush(over_heap, (score, idx, item))
                        else:
                            if over_heap[0][0] < score:
                                heapq.heapreplace(over_heap, (score, idx, item))

            merged.extend([it[2] for it in in_heap])
            merged.extend([it[2] for it in over_heap])

        # Apply global cap after sorting (keep highest score first)
        merged.sort(key=lambda x: x['score'], reverse=True)
        if len(merged) > MAX_GLOBAL_SUGGESTIONS:
            merged = merged[:MAX_GLOBAL_SUGGESTIONS]
        u['possible_assignments'] = merged
        u['subject_display'] = f"{(subj_course_code or '')} - {subj_obj.get('subject_title', 'Untitled')}"
        u['subject_code'] = subj_course_code
        u['course_section'] = subj_course_section
        u['course_name'] = subj_course_name


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
    parser.add_argument("--file", type=str, required=True, help="Path to the JSON payload file.")
    args = parser.parse_args()

    try:
        with open(args.file, 'r') as f:
            json_payload = json.load(f)

        academic_year = json_payload.get("academic_year")
        semester_id = json_payload.get("semester_id")

        out = generate_schedule(academic_year=academic_year, semester_id=semester_id, json_payload=json_payload)
        out_for_json = {k: v for k, v in out.items() if k != "force_assign_fn"}
        print(json.dumps(out_for_json, ensure_ascii=False, separators=(",", ":")))
    except Exception as e:
        output = {"success": False, "message": str(e), "trace": traceback.format_exc()}
        print(json.dumps(output, ensure_ascii=False, indent=2))