import json
import mysql.connector
import argparse
from collections import defaultdict

def detect_conflicts(records):
    errors = []

    # === GROUP BY FACULTY TO DETECT OVERLAPS ===
    faculty_schedules = defaultdict(list)
    room_schedules = defaultdict(list)
    section_schedules = defaultdict(list)
    course_schedules = defaultdict(list)

    for r in records:
        faculty_schedules[r["faculty"]].append(r)
        room_schedules[r["classroom"]].append(r)
        section_schedules[r["course_section"]].append(r)
        course_schedules[r["subject"]].append(r)

    def parse_time(time_str):
        """Convert time range like '8:00-9:00' to tuple of minutes"""
        if not time_str or '-' not in time_str:
            return None
        try:
            start, end = time_str.split('-')
            h1, m1 = map(int, start.split(':'))
            h2, m2 = map(int, end.split(':'))
            return h1 * 60 + m1, h2 * 60 + m2
        except (ValueError, TypeError):
            return None

    # === FACULTY CONFLICTS ===
    for faculty, scheds in faculty_schedules.items():
        for i in range(len(scheds)):
            for j in range(i + 1, len(scheds)):
                a, b = scheds[i], scheds[j]
                if a["day"] == b["day"]:
                    ta, tb = parse_time(a["time"]), parse_time(b["time"])
                    if ta and tb and ta[0] < tb[1] and tb[0] < ta[1]:
                        errors.append({
                            "type": "Faculty Conflict",
                            "description": f"{faculty} has overlapping classes ({a['subject']} and {b['subject']}) on {a['day']} [{a['time']}]",
                            "severity": "high"
                        })

    # === ROOM CONFLICTS ===
    for room, scheds in room_schedules.items():
        for i in range(len(scheds)):
            for j in range(i + 1, len(scheds)):
                a, b = scheds[i], scheds[j]
                if a["day"] == b["day"]:
                    ta, tb = parse_time(a["time"]), parse_time(b["time"])
                    if ta and tb and ta[0] < tb[1] and tb[0] < ta[1]:
                        errors.append({
                            "type": "Room Conflict",
                            "description": f"Room {room} double-booked ({a['subject']} and {b['subject']}) on {a['day']} [{a['time']}]",
                            "severity": "high"
                        })

    # === SECTION CONFLICTS ===
    for section, scheds in section_schedules.items():
        for i in range(len(scheds)):
            for j in range(i + 1, len(scheds)):
                a, b = scheds[i], scheds[j]
                if a["day"] == b["day"]:
                    ta, tb = parse_time(a["time"]), parse_time(b["time"])
                    if ta and tb and ta[0] < tb[1] and tb[0] < ta[1]:
                        errors.append({
                            "type": "Section Conflict",
                            "description": f"Section {section} has overlapping classes ({a['subject']} and {b['subject']}) on {a['day']} [{a['time']}]",
                            "severity": "medium"
                        })

    # === COURSE OFFERING CONFLICTS ===
    for course, scheds in course_schedules.items():
        for i in range(len(scheds)):
            for j in range(i + 1, len(scheds)):
                a, b = scheds[i], scheds[j]
                if a["day"] == b["day"] and a["course_section"] != b["course_section"]:
                    ta, tb = parse_time(a["time"]), parse_time(b["time"])
                    if ta and tb and ta[0] < tb[1] and tb[0] < ta[1]:
                        errors.append({
                            "type": "Course Offering Conflict",
                            "description": f"Multiple sections of {course} ({a['course_section']} and {b['course_section']}) are scheduled at the same time on {a['day']} [{a['time']}]",
                            "severity": "low"
                        })

    return errors

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--academic_year", required=True)
    parser.add_argument("--semester", required=True)
    args = parser.parse_args()

    semester = args.semester
    if semester == '1st':
        semester = '1st Semester'
    elif semester == '2nd':
        semester = '2nd Semester'

    db = None
    try:
        db = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",  # update if needed
            database="school_scheduler"
        )
        cursor = db.cursor(dictionary=True)

        query = """
            SELECT faculty, subject, classroom, course_section, SUBSTRING_INDEX(time, ' ', -1) as time, SUBSTRING_INDEX(time, ' ', 1) as day
            FROM finalized_schedules
            WHERE academicYear = %s AND semester = %s
        """
        cursor.execute(query, (args.academic_year, semester))
        records = cursor.fetchall()

        if not records:
            print(json.dumps({
                "success": True,
                "errors": [],
                "message": "No schedules found for the given parameters."
            }))
            return

        errors = detect_conflicts(records)

        if not errors:
            print(json.dumps({
                "success": True,
                "errors": [],
                "message": "No conflicts found."
            }))
            return

        print(json.dumps({
            "success": True,
            "errors": errors,
            "message": "Conflict check complete."
        }))

    except Exception as e:
        print(json.dumps({
            "success": False,
            "errors": [],
            "message": f"Database or logic error: {str(e)}"
        }))
    finally:
        if db and db.is_connected():
            db.close()

if __name__ == "__main__":
    main()