# 📚 API Documentation

## Base URL

```
http://localhost:8000/api
```

## Endpoints

### League Setup

#### Draw Groups
Randomly distributes 16 teams into 4 groups of 4 teams each.

**Endpoint:** `POST /draw-groups`

**Request:**
```http
POST /api/draw-groups
Content-Type: application/json
```

**Response:**
```json
{
  "message": "Groups drawn successfully"
}
```

**Status Codes:**
- `200 OK` - Groups drawn successfully
- `400 Bad Request` - Error during group draw

---

#### Generate Fixtures
Generates home-and-away fixtures for all groups (6 weeks total).

**Endpoint:** `POST /generate-fixtures`

**Request:**
```http
POST /api/generate-fixtures
Content-Type: application/json
```

**Response:**
```json
[
  {
    "week": 1,
    "matches": [
      {
        "id": 1,
        "group": "Group A",
        "day": 1,
        "home_team": "Manchester City",
        "away_team": "Bayern Munich",
        "score": "TBD",
        "played": false
      }
    ]
  }
]
```

**Status Codes:**
- `200 OK` - Fixtures generated successfully
- `400 Bad Request` - Error during fixture generation

---

### Match Simulation

#### Play Next Match
Simulates the next unplayed match in the current week.

**Endpoint:** `POST /play-next-match`

**Request:**
```http
POST /api/play-next-match
Content-Type: application/json
```

**Response:**
```json
{
  "match": {
    "id": 1,
    "home_team_name": "Manchester City",
    "away_team_name": "Bayern Munich",
    "home_goals": 2,
    "away_goals": 1,
    "group": "Group A",
    "week": 1
  },
  "week": 1,
  "remaining_matches": 5,
  "is_last_match": false,
  "status": "success"
}
```

**Status Codes:**
- `200 OK` - Match simulated successfully
- `400 Bad Request` - No matches remaining or error

**Notes:**
- Automatically calculates predictions when week is complete
- Updates team statistics (points, goals, form)
- Returns `is_last_match: true` when week is complete

---

#### Play Next Week
Simulates all matches in the next unplayed week.

**Endpoint:** `POST /play-next-week`

**Request:**
```http
POST /api/play-next-week
Content-Type: application/json
```

**Response:**
```json
{
  "message": "1. hafta başarıyla oynandı.",
  "status": "success",
  "data": [
    {
      "id": 1,
      "group": "Group A",
      "day": 1,
      "home_team": "Manchester City",
      "away_team": "Bayern Munich",
      "score": "2 - 1",
      "played": true
    }
  ]
}
```

**Status Codes:**
- `200 OK` - Week simulated successfully
- `400 Bad Request` - No weeks remaining or error

---

#### Simulate All Weeks
Simulates all remaining weeks until the season is complete.

**Endpoint:** `POST /play-all`

**Request:**
```http
POST /api/play-all
Content-Type: application/json
```

**Response:**
```json
{
  "message": "Simulated all weeks"
}
```

**Status Codes:**
- `200 OK` - All weeks simulated successfully
- `400 Bad Request` - Error during simulation

**Notes:**
- May take up to 2 minutes for full season
- Calculates final predictions after completion

---

### Data Retrieval

#### Get Standings
Retrieves current standings for all groups.

**Endpoint:** `GET /standings`

**Request:**
```http
GET /api/standings
```

**Response:**
```json
{
  "Group A": [
    {
      "team_name": "Manchester City",
      "points": 9,
      "played": 3,
      "won": 3,
      "drawn": 0,
      "lost": 0,
      "goal_difference": 5,
      "guess": 45
    },
    {
      "team_name": "Bayern Munich",
      "points": 6,
      "played": 3,
      "won": 2,
      "drawn": 0,
      "lost": 1,
      "goal_difference": 2,
      "guess": 30
    }
  ],
  "Group B": [...]
}
```

**Status Codes:**
- `200 OK` - Standings retrieved successfully

**Fields:**
- `team_name` - Team name
- `points` - Total points (3 for win, 1 for draw)
- `played` - Matches played
- `won` - Matches won
- `drawn` - Matches drawn
- `lost` - Matches lost
- `goal_difference` - Goals for minus goals against
- `guess` - Championship prediction percentage (0-100)

---

#### Get All Fixtures
Retrieves all fixtures grouped by group and week.

**Endpoint:** `GET /fixtures`

**Request:**
```http
GET /api/fixtures
```

**Response:**
```json
{
  "Group A": {
    "1": [
      {
        "id": 1,
        "home_team_name": "Manchester City",
        "away_team_name": "Bayern Munich",
        "home_goals": 2,
        "away_goals": 1,
        "played": true,
        "week": 1
      }
    ],
    "2": [...]
  },
  "Group B": {...}
}
```

**Status Codes:**
- `200 OK` - Fixtures retrieved successfully

---

#### Get Predictions
Retrieves championship predictions for all groups.

**Endpoint:** `GET /predictions`

**Request:**
```http
GET /api/predictions
```

**Response:**
```json
{
  "Group A": [
    {
      "team_name": "Manchester City",
      "probability": 45
    },
    {
      "team_name": "Bayern Munich",
      "probability": 30
    }
  ]
}
```

**Status Codes:**
- `200 OK` - Predictions retrieved successfully

**Notes:**
- Predictions are calculated from Week 4 onwards
- Probabilities sum to 100% per group

---

### League Management

#### Reset League
Resets the entire league (clears all matches, groups, and statistics).

**Endpoint:** `POST /reset`

**Request:**
```http
POST /api/reset
Content-Type: application/json
```

**Response:**
```json
{
  "message": "League reset successfully"
}
```

**Status Codes:**
- `200 OK` - League reset successfully
- `500 Internal Server Error` - Error during reset

**Warning:** This action is irreversible!

---

#### Update Match Result
Manually update a match result (for testing/correction).

**Endpoint:** `PUT /fixtures/{id}`

**Request:**
```http
PUT /api/fixtures/1
Content-Type: application/json

{
  "home_goals": 3,
  "away_goals": 2
}
```

**Response:**
```json
{
  "status": "success"
}
```

**Status Codes:**
- `200 OK` - Match updated successfully
- `400 Bad Request` - Invalid match ID or goals

**Notes:**
- Recalculates all statistics for the affected group
- Updates predictions if necessary

---

## Error Responses

All endpoints may return error responses in the following format:

```json
{
  "status": "error",
  "message": "Error description here"
}
```

### Common Error Codes

- `400 Bad Request` - Invalid request parameters
- `404 Not Found` - Resource not found
- `500 Internal Server Error` - Server error

---

## Rate Limiting

Currently, there are no rate limits applied. In production, consider implementing:

- 60 requests per minute per IP
- Exponential backoff for repeated errors

---

## Authentication

This API currently does not require authentication. For production deployment, consider implementing:

- JWT tokens
- API keys
- OAuth 2.0

---

## CORS

CORS is enabled for all origins in development. Configure appropriately for production.

---

## Webhooks (Future)

Planned webhook support for:
- Match completion
- Week completion
- Season completion

---

## Changelog

### v1.0.0 (2026-02-02)
- Initial API release
- All core endpoints implemented
- Performance optimizations applied

---

**Last Updated:** 2026-02-02
