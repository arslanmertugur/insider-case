# 🏗️ Architecture Documentation

## Overview

This application follows **Domain-Driven Design (DDD)** principles with a clear separation between domain logic, application services, and infrastructure concerns.

## Architecture Layers

```
┌─────────────────────────────────────────┐
│          HTTP Layer (Controllers)        │
│  - Request validation                    │
│  - Response formatting                   │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│         Service Layer                    │
│  - Business logic orchestration          │
│  - Transaction management                │
│  - Cross-cutting concerns                │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│         Domain Layer                     │
│  - Core business rules                   │
│  - Match simulation algorithm            │
│  - Pure domain logic                     │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│      Repository Layer                    │
│  - Data access abstraction               │
│  - Query optimization                    │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│         Database (PostgreSQL)            │
└─────────────────────────────────────────┘
```

## Core Components

### 1. Domain Layer (`app/Domain/`)

**Purpose:** Contains pure business logic with no framework dependencies.

#### MatchEngine
- **Responsibility:** Core match simulation algorithm
- **Key Methods:**
  - `simulateMatch()` - Simulates a single match
  - `generateRealisticGoals()` - Poisson distribution-based goal generation
  - `getFormBonus()` - Calculates team form bonus

**Algorithm Details:**

```php
// Poisson Distribution for Goal Generation
$lambda = min($xG, 6);  // Expected goals
$L = exp(-$lambda);
$p = 1.0;
$k = 0;

do {
    $k++;
    $p *= (mt_rand() / mt_getrandmax());
} while ($p > $L && $k < 10);

return max(0, $k - 1);
```

**Why Poisson?**
- Realistic goal distribution
- O(1) complexity
- Statistically accurate for football matches

### 2. Service Layer (`app/Services/League/`)

**Purpose:** Orchestrates business operations and manages transactions.

#### MatchService
- **Responsibility:** Match orchestration and statistics management
- **Key Methods:**
  - `playNextMatch()` - Plays next unplayed match
  - `playNextWeek()` - Plays all matches in a week
  - `playAllWeeks()` - Simulates entire season
  - `processStatsInMemory()` - Updates team statistics
  - `saveStats()` - Batch saves to database

**Performance Optimization:**
- In-memory statistics processing
- Batch database updates
- Transaction management for data consistency

#### PredictionService
- **Responsibility:** Championship prediction calculations
- **Key Methods:**
  - `calculatePredictions()` - Calculates win probabilities
  - `calculatePredictionsInMemory()` - In-memory calculation
  - `updatePredictionsForTeams()` - Updates team predictions

**Prediction Algorithm:**

```php
// Power Score Calculation
$pointsEffect = pow($team->points, 2);
$strengthMultiplier = $team->strength / 10;
$potential = ($remainingGames * $strengthMultiplier);
$powerScore = ($pointsEffect + $potential) * $strengthMultiplier;
$powerScore += ($team->goal_difference * 2);

// Convert to percentage
$percentage = ($score / $totalPowerScores) * 100;
```

#### LeagueSetupService
- **Responsibility:** League initialization and fixture generation
- **Key Methods:**
  - `drawGroups()` - Random group distribution
  - `generateFixtures()` - Home-and-away schedule
  - `resetLeague()` - Complete league reset

### 3. Repository Layer (`app/Repositories/`)

**Purpose:** Abstracts database access and provides query optimization.

#### FixtureRepository
- `getNextUnplayedWeek()` - Finds next week to play
- `getUnplayedMatchesByWeek()` - Gets matches for a week
- `getFixturesGroupedByWeek()` - Grouped fixture data

#### GroupRepository
- `getGroupsForStandings()` - Standings with relationships
- `getGroupTeamsByTeamIds()` - Batch team data retrieval
- `upsertGroupTeams()` - Efficient batch updates

**Query Optimization:**
- Eager loading to prevent N+1 queries
- Batch operations for bulk updates
- Strategic indexing

### 4. Models (`app/Models/`)

**Eloquent Models:**
- `Team` - Team data and attributes
- `Group` - Group information
- `GroupTeam` - Team statistics in a group (pivot)
- `Fixture` - Match data

**Relationships:**
```php
Team
  - hasMany(GroupTeam)
  - hasManyThrough(Fixture)

Group
  - hasMany(GroupTeam)
  - hasMany(Fixture)

Fixture
  - belongsTo(Team, 'home_team_id')
  - belongsTo(Team, 'away_team_id')
  - belongsTo(Group)
```

## Database Schema

### Teams Table
```sql
CREATE TABLE teams (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    country VARCHAR(255) NOT NULL,
    strength INTEGER NOT NULL,  -- 1-100
    attack INTEGER NOT NULL,
    defense INTEGER NOT NULL,
    goalkeeper INTEGER NOT NULL,
    power INTEGER NOT NULL,
    supporter INTEGER
);
```

### Groups Table
```sql
CREATE TABLE groups (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);
```

### Group_Teams Table (Pivot)
```sql
CREATE TABLE group_teams (
    id BIGSERIAL PRIMARY KEY,
    group_id BIGINT REFERENCES groups(id),
    team_id BIGINT REFERENCES teams(id),
    played INTEGER DEFAULT 0,
    won INTEGER DEFAULT 0,
    drawn INTEGER DEFAULT 0,
    lost INTEGER DEFAULT 0,
    points INTEGER DEFAULT 0,
    goals_for INTEGER DEFAULT 0,
    goals_against INTEGER DEFAULT 0,
    goal_difference INTEGER DEFAULT 0,
    form VARCHAR(5),  -- e.g., "WWDLW"
    guess INTEGER DEFAULT 0  -- Prediction percentage
);
```

### Fixtures Table
```sql
CREATE TABLE fixtures (
    id BIGSERIAL PRIMARY KEY,
    group_id BIGINT REFERENCES groups(id),
    home_team_id BIGINT REFERENCES teams(id),
    away_team_id BIGINT REFERENCES teams(id),
    week INTEGER NOT NULL,
    match_day INTEGER NOT NULL,
    home_goals INTEGER,
    away_goals INTEGER,
    played BOOLEAN DEFAULT FALSE
);
```

## Design Patterns

### 1. Repository Pattern
**Purpose:** Decouple data access from business logic

**Benefits:**
- Testability - Easy to mock repositories
- Flexibility - Can swap data sources
- Maintainability - Centralized query logic

### 2. Service Layer Pattern
**Purpose:** Encapsulate business logic

**Benefits:**
- Single Responsibility Principle
- Transaction management
- Reusability across controllers

### 3. Dependency Injection
**Purpose:** Loose coupling between components

**Example:**
```php
public function __construct(
    FixtureRepository $fixtureRepository,
    GroupRepository $groupRepository,
    PredictionService $predictionService,
    MatchEngine $matchEngine
) {
    $this->fixtureRepository = $fixtureRepository;
    $this->groupRepository = $groupRepository;
    $this->predictionService = $predictionService;
    $this->matchEngine = $matchEngine;
}
```

## Performance Optimizations

### 1. Algorithm Optimization
- **Goal Generation:** O(n) → O(1) using Poisson distribution
- **Prediction Calculation:** Reduced unnecessary calculations

### 2. Database Optimization
- **Batch Operations:** `upsert()` for bulk updates
- **Eager Loading:** Prevent N+1 queries
- **In-Memory Processing:** Minimize database round-trips

### 3. Transaction Management
- All match operations in transactions
- Rollback on errors
- Data consistency guaranteed

## Frontend Architecture

### Vue 3 Composition API

```
resources/js/
├── components/
│   └── league/
│       ├── LeagueSimulation.vue    # Main component
│       ├── SimulationModal.vue     # Match animation
│       ├── GroupTabs.vue           # Group navigation
│       ├── StandingsTable.vue      # Standings display
│       ├── FixturesList.vue        # Fixtures display
│       └── ControlPanel.vue        # Control buttons
├── composables/
│   ├── useLeagueData.js           # Data management
│   ├── useSimulation.js           # Simulation logic
│   └── useNotifications.js        # Toast/modal logic
├── services/
│   └── leagueApi.js               # API client
└── utils/
    └── constants.js               # Configuration
```

### State Management
- **Composables:** Reactive state management
- **Provide/Inject:** Component communication
- **Axios:** HTTP client with interceptors

## Testing Strategy

### Unit Tests
- **Domain Layer:** Pure logic testing
- **Service Layer:** Business logic validation
- **Repository Layer:** Query correctness

### Feature Tests
- **API Endpoints:** Request/response validation
- **Integration:** End-to-end workflows

### Test Database
- SQLite in-memory for fast tests
- Isolated test environment
- Database transactions for cleanup

## Deployment Considerations

### Production Checklist
- [ ] Environment variables secured
- [ ] Database migrations run
- [ ] Frontend assets built
- [ ] Cache configured
- [ ] Queue workers running
- [ ] Logs monitored

### Scaling Strategies
- **Database:** Read replicas for heavy read operations
- **Cache:** Redis for session/cache storage
- **Queue:** Background jobs for long-running tasks
- **CDN:** Static asset delivery

## Future Enhancements

1. **Knockout Stage:** Implement playoffs after group stage
2. **Real-time Updates:** WebSocket for live match updates
3. **Advanced Statistics:** Player-level statistics
4. **Machine Learning:** Improve prediction accuracy
5. **Multi-language:** i18n support

---

**Last Updated:** 2026-02-02
