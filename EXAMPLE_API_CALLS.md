# Timetable API – Example API Calls

Base URL (XAMPP): `http://localhost/time_table_generation_system/timetable-api`

All **responses** are JSON. **Request body** can be either JSON or form (so you can test in Postman with both).

Protected endpoints require a token. Use either:
- **Authorization:** `Bearer <your-token>`  
- Or **X-Auth-Token:** `<your-token>` (use this in Postman if the server strips the Authorization header)

---

## Testing with Postman

The API accepts **both**:

1. **JSON body**  
   - In Postman: **Body** → **raw** → select **JSON**.  
   - Set header `Content-Type: application/json` (Postman sets this when you choose JSON).  
   - Send payload like: `{"email":"admin@school.local","password":"Admin@123"}`

2. **Form data**  
   - In Postman: **Body** → **x-www-form-urlencoded** or **form-data**.  
   - Add key/value pairs (e.g. `email` = `admin@school.local`, `password` = `Admin@123`).  
   - No need to set Content-Type; Postman sets it for form.

**For protected routes:** add header **Authorization** = `Bearer <your-token>` (e.g. after login). If you still get "Missing or invalid authorization token", use header **X-Auth-Token** = `<your-token>` instead (raw token, no "Bearer").

**Arrays in form:**  
- For a list like `subject_ids`, use either multiple form keys `subject_ids[]` = `1`, `subject_ids[]` = `2`, or a single key `subject_ids` = `1,2,3` (comma-separated).  
- For nested data (e.g. `slots` in teacher availability), send that field as a **JSON string** in form, e.g. `slots` = `[{"school_day_id":1,"time_slot_id":1,"is_available":1}]`.

---

## 1. Authentication

### Register
```http
POST /api/auth/register
Content-Type: application/json

{
  "email": "teacher@school.local",
  "password": "secret123",
  "name": "John Teacher",
  "role": "teacher"
}
```
Roles: `admin`, `head_teacher`, `teacher`

### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@school.local",
  "password": "Admin@123"
}
```
Response includes `token`; use it in `Authorization: Bearer <token>` for protected routes.

### Logout
```http
POST /api/auth/logout
Authorization: Bearer <your-token>
```

### Me (current user)
```http
GET /api/auth/me
Authorization: Bearer <your-token>
```

---

## 2. Academic structure

### Academic years
```http
GET    /api/academic-years
POST   /api/academic-years   { "name": "2024-2025", "start_date": "2024-09-01", "end_date": "2025-07-31", "is_active": 1 }
GET    /api/academic-years/1
PUT    /api/academic-years/1  { "name": "...", "start_date": "...", "end_date": "...", "is_active": 0 }
DELETE /api/academic-years/1
```

### Terms
```http
GET    /api/terms?academic_year_id=1
POST   /api/terms   { "academic_year_id": 1, "name": "Term 1", "start_date": "2024-09-01", "end_date": "2024-12-20" }
GET    /api/terms/1
PUT    /api/terms/1  { "academic_year_id": 1, "name": "Term 1", "start_date": "...", "end_date": "..." }
DELETE /api/terms/1
```

### Classes
```http
GET    /api/classes?term_id=1&academic_year_id=1
POST   /api/classes   { "academic_year_id": 1, "term_id": 1, "name": "Class 10A" }
GET    /api/classes/1
PUT    /api/classes/1   { "academic_year_id": 1, "term_id": 1, "name": "Class 10A" }
DELETE /api/classes/1
```

---

## 3. Teachers

```http
GET    /api/teachers
POST   /api/teachers   { "name": "Jane Doe", "email": "jane@school.local" }
GET    /api/teachers/1
PUT    /api/teachers/1   { "name": "Jane Doe", "email": "jane@school.local" }
DELETE /api/teachers/1
GET    /api/teachers/1/subjects
POST   /api/teachers/1/subjects   { "subject_ids": [1, 2, 3] }
GET    /api/teachers/1/availability
PUT    /api/teachers/1/availability   { "slots": [ { "school_day_id": 1, "time_slot_id": 1, "is_available": 1 }, ... ] }
```

---

## 4. Subjects

```http
GET    /api/subjects
POST   /api/subjects   { "name": "Mathematics", "code": "MATH" }
GET    /api/subjects/1
PUT    /api/subjects/1   { "name": "Mathematics", "code": "MATH" }
DELETE /api/subjects/1
```

---

## 5. Rooms

```http
GET    /api/rooms
POST   /api/rooms   { "name": "Room 101", "capacity": 30, "type": "classroom" }
GET    /api/rooms/1
PUT    /api/rooms/1   { "name": "Room 101", "capacity": 30, "type": "lab" }
DELETE /api/rooms/1
GET    /api/rooms/1/availability?term_id=1
```

---

## 6. Time configuration

### School days
```http
GET  /api/school-days
POST /api/school-days   { "name": "Monday", "day_order": 1 }
PUT  /api/school-days/1   { "name": "Monday", "day_order": 1 }
```

### Time slots
```http
GET    /api/time-slots
POST   /api/time-slots   { "name": "Period 1", "start_time": "08:00:00", "end_time": "08:45:00", "slot_order": 1 }
PUT    /api/time-slots/1   { "name": "Period 1", "start_time": "08:00:00", "end_time": "08:45:00", "slot_order": 1 }
DELETE /api/time-slots/1
```

### Break periods
```http
GET   /api/break-periods
POST  /api/break-periods   { "time_slot_id": 4, "school_day_id": null, "name": "Break" }
DELETE /api/break-periods/1
```

---

## 7. Allocations (class–subject–teacher, periods per week)

```http
GET    /api/allocations?term_id=1
POST   /api/allocations   { "class_id": 1, "subject_id": 1, "teacher_id": 1, "periods_per_week": 5, "academic_year_id": 1, "term_id": 1 }
GET    /api/allocations/class/1?term_id=1
PUT    /api/allocations/1   { "class_id": 1, "subject_id": 1, "teacher_id": 1, "periods_per_week": 5, "academic_year_id": 1, "term_id": 1 }
DELETE /api/allocations/1
```

---

## 8. Timetable

### Generate
```http
POST /api/timetable/generate
Content-Type: application/json

{ "term_id": 1 }
```
Requires: academic years, terms, classes, teachers, subjects, rooms, school days, time slots, and **allocations** for that term. Teacher availability is respected.

### List / by class / by teacher
```http
GET /api/timetable?term_id=1
GET /api/timetable/class/1?term_id=1
GET /api/timetable/teacher/1?term_id=1
```

### Swap two entries
```http
POST /api/timetable/swap
Content-Type: application/json

{ "entry_id_1": 10, "entry_id_2": 20 }
```
Response includes updated entries and current `conflicts`.

### Move one entry
```http
POST /api/timetable/move
Content-Type: application/json

{ "entry_id": 10, "school_day_id": 2, "time_slot_id": 3, "room_id": 1 }
```
`room_id` is optional. Response includes updated entry and `conflicts`.

### Conflicts
```http
GET /api/timetable/conflicts?term_id=1
```
Returns `teacher_conflicts`, `room_conflicts`, `class_conflicts` (arrays of overlapping entries).

---

## Response format

Success:
```json
{
  "success": true,
  "message": "Request successful",
  "data": { ... }
}
```

Error (e.g. 422 validation):
```json
{
  "success": false,
  "message": "Validation failed.",
  "data": { "errors": { "email": ["Field email is required."] } }
}
```

---

## Setup

1. Create DB and tables: run `database/migrations.sql` in MySQL.
2. Copy `config/.env.example.php` to `config/.env.php` and set DB credentials.
3. Default admin: `admin@school.local` / `Admin@123` (change in production).
