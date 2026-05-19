# Training Plan API — Documentation

**Base URL:** `https://training.ginamarie-lukas.de`

All `/api/*` endpoints (except `/api/auth/token`) require a Bearer token in the
`Authorization` header. Web session routes (`/health/save`, `/log/*`) use cookie
authentication instead.

---

## Authentication

### POST /api/auth/token

Obtain a long-lived API token. Rate-limited to 5 attempts per IP per 15 minutes.

**Auth required:** No

**Request body:**
```json
{
  "username": "your_username",
  "password": "your_password"
}
```

**Response 200:**
```json
{
  "token": "abc123…",
  "username": "your_username"
}
```

**Errors:**
| Code | Reason              |
|------|---------------------|
| 401  | Invalid credentials |
| 429  | Too many attempts   |

**Usage in subsequent requests:**
```
Authorization: Bearer abc123…
```

---

## Health Metrics

### POST /api/health/sync

Upsert health metrics for a given date. Supports any Android Health Connect
data type — no API changes needed for new metric types.

**Auth required:** Bearer token

**Request body — recommended format:**
```json
{
  "date": "2026-05-09",
  "metrics": {
    "steps": 8432,
    "weight_kg": 78.5,
    "sleep_minutes": 427,
    "active_minutes": 45,
    "calories_kcal": 2150,
    "heart_rate_avg": 72,
    "heart_rate_resting": 58,
    "distance_meters": 6240,
    "vo2_max": 48.3
  }
}
```

**Legacy flat format (still accepted for backwards compatibility):**
```json
{
  "date": "2026-05-09",
  "steps": 8432,
  "sleepMinutes": 427,
  "activeMinutes": 45,
  "weightKg": 78.5,
  "caloriesKcal": 2150
}
```

**Key rules:**
- Keys are auto-normalised: lowercased, non-alphanumeric chars replaced with `_`, max 100 chars
- Values must be numeric
- `date` defaults to today if omitted
- Existing records for `(user, date, metric_key)` are upserted — not duplicated

**Response 200:**
```json
{
  "success": true,
  "saved": ["steps", "weight_kg", "calories_kcal"]
}
```

**Errors:**
| Code | Reason                                    |
|------|-------------------------------------------|
| 400  | Invalid date or no valid metrics provided |

---

### GET /api/health/data?days=30

Fetch health history grouped by date, ordered oldest → newest.

**Auth required:** Bearer token

**Query params:**
| Param | Default | Max | Description         |
|-------|---------|-----|---------------------|
| days  | 7       | 90  | Number of past days |

**Response 200:**
```json
[
  {
    "date": "2026-05-07",
    "steps": 9120,
    "weight_kg": 78.3,
    "calories_kcal": 2080
  },
  {
    "date": "2026-05-08",
    "steps": 7640,
    "sleep_minutes": 430,
    "weight_kg": 78.1
  }
]
```

Each object contains only the metric keys that exist for that date.
Missing metrics are simply absent — no null fields.

---

### GET /api/health/metrics

List all distinct metric keys the user has ever synced, sorted alphabetically.

**Auth required:** Bearer token

**Response 200:**
```json
["active_minutes", "calories_kcal", "heart_rate_avg", "sleep_minutes", "steps", "weight_kg"]
```

---

### GET /api/health/workouts?limit=5

Recent workout sessions — designed for the Android app dashboard widget.

**Auth required:** Bearer token

**Query params:**
| Param | Default | Max | Description                  |
|-------|---------|-----|------------------------------|
| limit | 5       | 20  | Number of sessions to return |

**Response 200:**
```json
[
  {
    "id": 42,
    "date": "2026-05-09",
    "type": "push",
    "label": "Push — Brust, Schulter, Trizeps",
    "color": "#C9184A",
    "duration": 65
  }
]
```

---

## Workouts & Training Log

### GET /api/history?limit=10

Detailed recent sessions including all exercise sets.

**Auth required:** Session cookie

**Query params:**
| Param | Default | Max |
|-------|---------|-----|
| limit | 10      | 50  |

**Response 200:**
```json
[
  {
    "id": 42,
    "date": "09.05.2026",
    "type": "push",
    "label": "Push — Brust, Schulter, Trizeps",
    "color": "#C9184A",
    "duration": 65,
    "notes": "Gutes Training",
    "exercises": {
      "Bankdrücken": [
        { "set": 1, "reps": 8, "weight": 100.0, "rpe": 8 },
        { "set": 2, "reps": 6, "weight": 102.5, "rpe": 9 }
      ]
    }
  }
]
```

---

### GET /api/progression/{exerciseName}

Weight and rep progression over time for a single exercise.

**Auth required:** Session cookie

**Path param:** `exerciseName` — URL-encoded (e.g. `Bankdr%C3%BCcken`)

**Response 200:**
```json
[
  { "date": "05.04.2026", "maxWeight": 97.5,  "maxReps": 8 },
  { "date": "12.04.2026", "maxWeight": 100.0, "maxReps": 8 },
  { "date": "09.05.2026", "maxWeight": 102.5, "maxReps": 6 }
]
```

---

### GET /api/last-weights/{type}

Last recorded weight per exercise for a given workout type (past 60 days).
Used to pre-fill the training log form.

**Auth required:** Session cookie

**Path param:** `type` — one of `push` | `pull` | `legs` | `upper`

**Response 200:**
```json
{
  "Bankdrücken":     { "weight": 102.5, "reps": 6, "date": "09.05.2026" },
  "Schulterdrücken": { "weight": 65.0,  "reps": 8, "date": "01.05.2026" }
}
```

---

## Settings

### PATCH /api/settings

Update one or more user settings. Only fields present in the body are modified.

**Auth required:** Session cookie

**Request body (all fields optional):**
```json
{
  "weekSchedule": {
    "1": "push",
    "2": "pull",
    "3": "legs",
    "4": null,
    "5": "push",
    "6": "upper",
    "7": null
  },
  "reminderTime": "07:30",
  "healthDashboard": {
    "cards": [
      { "metric": "steps" },
      { "metric": "weight_kg" },
      { "metric": "calories_kcal" }
    ],
    "chartMetrics": ["steps", "weight_kg"],
    "chartDays": 30
  }
}
```

**Field details:**
| Field                         | Values                                         | Notes                             |
|-------------------------------|------------------------------------------------|-----------------------------------|
| weekSchedule                  | Keys `"1"`–`"7"`, values push/pull/legs/upper/null | Full object required          |
| reminderTime                  | `"HH:MM"` or `null`                            | 24-hour format                    |
| healthDashboard.cards         | Array of `{ "metric": string }`                | Max 20, keys: `^[a-z0-9_]{1,100}$` |
| healthDashboard.chartMetrics  | Array of metric key strings                    | Same key validation               |
| healthDashboard.chartDays     | Integer                                        | Clamped to 7–90                   |

**Response 200:**
```json
{ "success": true }
```

---

## Supplements

All supplement endpoints require a Bearer token. The supplement object returned by every endpoint has the following shape:

```json
{
  "id": 1,
  "name": "Creatine",
  "dosage": "5 g",
  "servingsPerDay": 1,
  "servingsRemaining": 27.0,
  "totalServings": 60.0,
  "unit": "Portionen",
  "warningDays": 7,
  "notes": "Take with water",
  "sortOrder": 0,
  "daysRemaining": 27.0,
  "stockPercent": 45.0,
  "stockStatus": "ok",
  "isLow": false,
  "isEmpty": false
}
```

**`stockStatus`** is one of:
- `"ok"` — days remaining > warningDays
- `"low"` — days remaining ≤ warningDays (but > 0)
- `"empty"` — servingsRemaining = 0

---

### GET /api/supplements

List all supplements sorted by sortOrder, then creation date.

**Auth required:** Bearer token

**Response 200:** Array of supplement objects.

---

### POST /api/supplements

Create a supplement.

**Auth required:** Bearer token

**Request body:**
```json
{
  "name": "Creatine",
  "dosage": "5 g",
  "servingsPerDay": 1,
  "servingsRemaining": 60.0,
  "totalServings": 60.0,
  "unit": "Portionen",
  "warningDays": 7,
  "notes": "Take with water",
  "sortOrder": 0
}
```

Required: `name`. All other fields optional (sensible defaults apply).

**Response 201:** Created supplement object.

**Errors:**
| Code | Reason                          |
|------|---------------------------------|
| 400  | name missing or invalid         |

---

### PATCH /api/supplements/{id}

Update fields on an existing supplement. Only provided fields are changed.

**Auth required:** Bearer token

**Request body:** Any subset of the POST body (name is not required).

**Response 200:** Updated supplement object.

**Errors:**
| Code | Reason        |
|------|---------------|
| 404  | Not found     |
| 400  | Invalid field |

---

### POST /api/supplements/{id}/dose

Log a dose — decrements `servingsRemaining` by the supplement's `servingsPerDay`
(or a custom amount if `amount` is provided).

**Auth required:** Bearer token

**Request body (all optional):**
```json
{ "amount": 1.0 }
```

If `amount` is omitted, `servingsPerDay` is used. Amount is clamped to ≥ 0.

**Response 200:** Updated supplement object.

**Errors:**
| Code | Reason    |
|------|-----------|
| 404  | Not found |

---

### POST /api/supplements/{id}/restock

Replace pack — sets both `totalServings` and `servingsRemaining` to the new value.

**Auth required:** Bearer token

**Request body:**
```json
{ "servings": 60.0 }
```

`servings` must be a positive number.

**Response 200:** Updated supplement object.

**Errors:**
| Code | Reason                          |
|------|---------------------------------|
| 404  | Not found                       |
| 400  | servings missing or not positive |

---

### DELETE /api/supplements/{id}

Delete a supplement permanently.

**Auth required:** Bearer token

**Response 200:**
```json
{ "success": true }
```

**Errors:**
| Code | Reason    |
|------|-----------|
| 404  | Not found |

---

## Web-Only Endpoints (Session Cookie Auth)

### POST /health/save

Manual health entry from the web dashboard.

**Request body:**
```json
{
  "date": "2026-05-09",
  "metrics": {
    "weight_kg": 78.5,
    "calories_kcal": 2200
  }
}
```

**Response 200:**
```json
{
  "success": true,
  "saved": { "weight_kg": 78.5, "calories_kcal": 2200.0 }
}
```

**Errors:**
| Code | Reason                                |
|------|---------------------------------------|
| 400  | Future date, invalid date, no metrics |

---

## Error Format

All error responses share the same shape:
```json
{ "error": "Human-readable message" }
```

---

## App Routes (HTML Pages)

| Method   | Path             | Description               |
|----------|------------------|---------------------------|
| GET      | /login           | Login page                |
| GET      | /logout          | Logout                    |
| GET      | /dashboard       | Main dashboard            |
| GET      | /                | Training plan overview    |
| GET      | /plans/edit      | Plan editor               |
| GET      | /log?type=push   | Active training log       |
| POST     | /log/save        | Save completed workout    |
| DELETE   | /log/{id}/delete | Delete a workout session  |
| POST     | /health/save     | Manual health entry       |
| GET      | /supplements     | Supplement tracker page   |

---

## Known Metric Keys

The API accepts any key — these have rich dashboard support (icon, chart type,
smart formatting):

| Key                | Unit        | Chart type |
|--------------------|-------------|------------|
| steps              | count       | Bar        |
| sleep_minutes      | min → Xh Ym | Bar        |
| active_minutes     | min         | Bar        |
| weight_kg          | kg          | Line       |
| calories_kcal      | kcal        | Bar        |
| heart_rate_avg     | bpm         | Line       |
| heart_rate_resting | bpm         | Line       |
| distance_meters    | m / km      | Bar        |
| vo2_max            | —           | Line       |

Any other key is stored and displayed with a generic 📊 icon and auto-formatted label.
