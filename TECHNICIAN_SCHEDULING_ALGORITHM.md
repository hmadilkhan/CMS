# Technician Scheduling Algorithm - Documentation

## Overview
This system intelligently assigns technicians to site surveys based on proximity, availability, and route optimization.

## Fixed Time Slots
The system uses predefined 2-hour time slots:
- **08:00 - 10:00**
- **10:00 - 12:00**
- **12:00 - 14:00** (12 PM - 2 PM)
- **14:00 - 16:00** (2 PM - 4 PM)
- **16:00 - 18:00** (4 PM - 6 PM)

These can be easily modified in `SiteSurveyController::TIME_SLOTS` constant.

## Key Features

### 1. **Double-Booking Prevention**
- Before scheduling, the system checks if the selected time slot is already occupied
- Returns error if technician is already booked for that slot
- Validates against all 'scheduled' and 'in_progress' surveys

### 2. **Smart Technician Selection**
The algorithm ranks technicians by suitability score based on:

#### a) **Travel Time** (Primary Factor)
- Calculates distance from technician's current/home location to customer
- Only shows slots if travel time ≤ 60 minutes
- Shorter travel time = higher score

#### b) **Route Optimization**
- If technician has a job earlier in the day, calculates travel from that location
- Gives bonus points for route continuity (20 points)
- Reduces overall travel time and fuel costs

#### c) **Home Location**
- Each technician has a home base (home_address, home_lat, home_lng)
- First job of the day calculates travel from home
- System falls back to home if no previous jobs exist

#### d) **Upcoming Schedule Proximity** (2-3 Days Lookahead)
- Checks if technician has jobs scheduled in next 2-3 days near customer location
- If upcoming job is within 10km, gives +50 bonus points
- Helps cluster jobs in same geographic area

### 3. **Suitability Scoring System**
```
Base Score: 100 points per slot

Deductions:
- Travel time penalty: -30 points (proportional to travel time)

Bonuses:
- Route continuity: +20 points (coming from previous job)
- Nearby upcoming job: +50 points (within 10km in next 2-3 days)

Final Score: Sum of all slot scores
```

### 4. **Real-Time Traffic Consideration**
- Uses Google Maps Distance Matrix API
- Considers current traffic conditions
- Provides accurate travel time estimates

## Algorithm Flow

```
1. User selects date and enters customer address
   ↓
2. System geocodes customer address to lat/lng
   ↓
3. For each technician with "Technician" role:
   ↓
4. Check each fixed time slot (8-10, 10-12, etc.)
   ↓
5. Is slot already occupied? → Skip
   ↓
6. Determine starting location:
   - Has previous job same day? → Use that location
   - Otherwise → Use home location
   ↓
7. Calculate travel time to customer
   ↓
8. Travel time > 60 min? → Skip slot
   ↓
9. Calculate suitability score
   ↓
10. Check upcoming schedule (next 2-3 days)
    ↓
11. Has nearby job? → Add bonus points
    ↓
12. Sort all technicians by total suitability score
    ↓
13. Return ranked list (best match first)
```

## Database Schema

### Users Table (Technicians)
```sql
- address (string, nullable) - Technician's home/base address
- latitude (decimal, nullable) - Home location latitude  
- longitude (decimal, nullable) - Home location longitude
```

**Note**: The system uses existing address fields that are already managed through the register page.

### Site Surveys Table
```sql
- project_id
- technician_id
- survey_date
- start_time
- end_time
- customer_address
- customer_lat
- customer_lng
- estimated_travel_time
- estimated_distance
- status (scheduled, in_progress, completed, cancelled)
- actual_start_time
- actual_end_time
- actual_lat
- actual_lng
- notes
```

## API Response Format

```json
[
  {
    "technician": {
      "id": 5,
      "name": "John Doe",
      "address": "123 Main St"
    },
    "slots": [
      {
        "start_time": "08:00",
        "end_time": "10:00",
        "travel_time": 25,
        "distance": 18.5,
        "from_location": "home"
      },
      {
        "start_time": "10:00",
        "end_time": "12:00",
        "travel_time": 15,
        "distance": 12.3,
        "from_location": "previous_job"
      }
    ],
    "suitability_score": 165.5,
    "upcoming_nearby": true
  }
]
```

## Configuration

### Adjustable Parameters in Controller:
```php
TIME_SLOTS = [...];           // Modify slot times/duration
MAX_TRAVEL_TIME = 60;         // Maximum acceptable travel (minutes)
LOOKAHEAD_DAYS = 3;           // Days to check for nearby jobs
```

### Scoring Weights (in calculateSlotScore method):
```php
$score -= ($travelData['duration'] / MAX_TRAVEL_TIME) * 30;  // Travel penalty
$score += 20;  // Route continuity bonus
```

### Proximity Threshold (in checkUpcomingProximity method):
```php
if ($distance <= 10) {  // 10km radius
    return 50;  // Bonus points
}
```

## Usage Example

### Frontend Request:
```javascript
$.ajax({
    url: '/api/site-surveys/available-slots',
    method: 'GET',
    data: {
        date: '2025-11-15',
        customer_address: '456 Oak Ave, City',
        customer_lat: 40.7128,
        customer_lng: -74.0060
    },
    success: function(response) {
        // response contains ranked technicians with available slots
        // First technician in array is best match
    }
});
```

### Scheduling:
```javascript
$.ajax({
    url: '/api/site-surveys/schedule',
    method: 'POST',
    data: {
        project_id: 123,
        technician_id: 5,
        survey_date: '2025-11-15',
        start_time: '10:00',
        end_time: '12:00',
        customer_address: '456 Oak Ave',
        customer_lat: 40.7128,
        customer_lng: -74.0060,
        estimated_travel_time: 25,
        estimated_distance: 18.5
    }
});
```

## Benefits

1. **No Double-Booking**: Strict validation prevents overlapping appointments
2. **Optimized Routes**: Minimizes travel time and costs
3. **Geographic Clustering**: Groups nearby jobs together
4. **Fair Distribution**: All available technicians are considered
5. **Transparent Scoring**: Clear ranking system shows best matches first
6. **Flexible Configuration**: Easy to adjust time slots and scoring weights
7. **Real-Time Data**: Uses live traffic information for accuracy

## Future Enhancements

- Add technician skill levels/specializations
- Consider technician workload balance
- Add break time requirements
- Support for emergency/priority jobs
- Multi-day route optimization
- Technician preferences (preferred areas)
- Weather condition consideration
