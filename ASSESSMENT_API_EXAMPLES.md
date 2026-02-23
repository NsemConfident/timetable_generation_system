# Assessment (CA/Exam) API – Postman examples and sample responses

Base URL: `http://localhost/time_table_generation_system/timetable-api`  
All assessment endpoints require auth: `Authorization: Bearer <token>` or `X-Auth-Token: <token>`.

---

## 1. Create assessment session (CA)

**POST** `/api/assessments`

**Body (raw JSON):**
```json
{
  "name": "Term 1 Continuous Assessment",
  "type": "ca",
  "term_id": 1,
  "academic_year_id": 1,
  "start_date": "2024-10-01",
  "end_date": "2024-10-15",
  "default_duration_minutes": 60
}
```

**Body (form-data / x-www-form-urlencoded):**
- name = `Term 1 Continuous Assessment`
- type = `ca`
- term_id = `1`
- academic_year_id = `1`
- start_date = `2024-10-01`
- end_date = `2024-10-15`
- default_duration_minutes = `60`

**Sample response (201):**
```json
{
  "success": true,
  "message": "Assessment session created.",
  "data": {
    "assessment": {
      "id": "1",
      "name": "Term 1 Continuous Assessment",
      "type": "ca",
      "term_id": "1",
      "academic_year_id": "1",
      "start_date": "2024-10-01",
      "end_date": "2024-10-15",
      "default_duration_minutes": "60",
      "term_name": "Term 1",
      "academic_year_name": "2024-2025"
    }
  }
}
```

---

## 2. Create assessment session (Exam)

**POST** `/api/assessments`

**Body (raw JSON):**
```json
{
  "name": "Term 1 Final Exams",
  "type": "exam",
  "term_id": 1,
  "academic_year_id": 1,
  "start_date": "2024-12-01",
  "end_date": "2024-12-15",
  "default_duration_minutes": 90
}
```

---

## 3. List assessment sessions

**GET** `/api/assessments`  
**GET** `/api/assessments?term_id=1`  
**GET** `/api/assessments?type=ca`  
**GET** `/api/assessments?term_id=1&type=exam`

**Sample response (200):**
```json
{
  "success": true,
  "message": "Request successful.",
  "data": {
    "assessments": [
      {
        "id": "1",
        "name": "Term 1 Continuous Assessment",
        "type": "ca",
        "term_id": "1",
        "academic_year_id": "1",
        "start_date": "2024-10-01",
        "end_date": "2024-10-15",
        "default_duration_minutes": "60",
        "term_name": "Term 1",
        "academic_year_name": "2024-2025"
      }
    ]
  }
}
```

---

## 4. Add subjects to a session

**POST** `/api/assessments/1/subjects`

**Body (raw JSON):**
```json
{
  "items": [
    { "class_id": 1, "subject_id": 1, "duration_minutes": 60, "supervisor_teacher_id": 2 },
    { "class_id": 1, "subject_id": 2, "duration_minutes": 45 },
    { "class_id": 2, "subject_id": 1, "duration_minutes": 60, "supervisor_teacher_id": 2 }
  ]
}
```

**Body (form-data):**  
- items = `[{"class_id":1,"subject_id":1,"duration_minutes":60},{"class_id":1,"subject_id":2}]` (JSON string)

**Sample response (200):**
```json
{
  "success": true,
  "message": "Subjects added.",
  "data": {
    "subjects": [
      {
        "id": "1",
        "assessment_session_id": "1",
        "class_id": "1",
        "subject_id": "1",
        "duration_minutes": "60",
        "supervisor_teacher_id": "2",
        "class_name": "Class 10A",
        "subject_name": "Mathematics",
        "supervisor_name": "Jane Doe"
      }
    ],
    "added_count": 3
  }
}
```

---

## 5. List subjects in a session

**GET** `/api/assessments/1/subjects`

**Sample response (200):**
```json
{
  "success": true,
  "message": "Request successful.",
  "data": {
    "subjects": [
      {
        "id": "1",
        "assessment_session_id": "1",
        "class_id": "1",
        "subject_id": "1",
        "duration_minutes": "60",
        "supervisor_teacher_id": "2",
        "class_name": "Class 10A",
        "subject_name": "Mathematics",
        "subject_code": "MATH",
        "supervisor_name": "Jane Doe"
      }
    ]
  }
}
```

---

## 6. Generate assessment timetable

**POST** `/api/assessments/1/generate`

No body required. Session type (`ca` or `exam`) determines which generator runs:
- **CA:** max 2 exams per class per day, teacher/supervisor availability, no class/room clash.
- **Exam:** no same-class exam at same time, room not double-booked, optional supervisor.

**Sample response (201):**
```json
{
  "success": true,
  "message": "Timetable generated.",
  "data": {
    "assessment_session_id": 1,
    "entries_count": 12,
    "entries": [
      {
        "id": "1",
        "assessment_session_id": "1",
        "assessment_subject_id": "1",
        "room_id": "1",
        "school_day_id": "1",
        "time_slot_id": "1",
        "supervisor_teacher_id": "2",
        "class_id": "1",
        "subject_id": "1",
        "class_name": "Class 10A",
        "subject_name": "Mathematics",
        "room_name": "Room 101",
        "day_name": "Monday",
        "day_order": "1",
        "slot_name": "Period 1",
        "start_time": "08:00:00",
        "end_time": "08:45:00",
        "supervisor_name": "Jane Doe"
      }
    ]
  }
}
```

---

## 7. Get generated timetable

**GET** `/api/assessments/1/timetable`

**Sample response (200):**
```json
{
  "success": true,
  "message": "Request successful.",
  "data": {
    "timetable": [
      {
        "id": "1",
        "assessment_session_id": "1",
        "assessment_subject_id": "1",
        "room_id": "1",
        "school_day_id": "1",
        "time_slot_id": "1",
        "supervisor_teacher_id": "2",
        "class_name": "Class 10A",
        "subject_name": "Mathematics",
        "room_name": "Room 101",
        "day_name": "Monday",
        "slot_name": "Period 1",
        "start_time": "08:00:00",
        "end_time": "08:45:00",
        "supervisor_name": "Jane Doe"
      }
    ]
  }
}
```

---

## 8. Swap two timetable entries

**POST** `/api/assessments/timetable/swap`

**Body (raw JSON):**
```json
{
  "entry_id_1": 5,
  "entry_id_2": 10
}
```

Swaps day/slot/room between the two entries (same session). Validates no class/room clash after swap.

---

## 9. Move one timetable entry

**POST** `/api/assessments/timetable/move`

**Body (raw JSON):**
```json
{
  "entry_id": 5,
  "school_day_id": 2,
  "time_slot_id": 3,
  "room_id": 1
}
```

`room_id` is optional. For CA sessions, moving is validated so the class does not exceed 2 exams per day.

---

## Setup checklist for generation

1. **School days** – e.g. Mon–Fri (seeded in migrations).
2. **Time slots** – create via `POST /api/time-slots` (name, start_time, end_time, slot_order).
3. **Rooms** – create via `POST /api/rooms`.
4. **Assessment session** – `POST /api/assessments` (type `ca` or `exam`).
5. **Subjects in session** – `POST /api/assessments/{id}/subjects` with `items` array (class_id, subject_id, optional duration_minutes, optional supervisor_teacher_id).
6. **Teacher availability** – if using supervisors, ensure they have availability (or leave unset for “available everywhere”).
7. **Generate** – `POST /api/assessments/{id}/generate`.
