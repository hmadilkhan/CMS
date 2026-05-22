# Site Survey Feature - Test Coordinates

## Feature Overview
Intelligent site survey scheduling system that matches technicians with customer locations based on:
- Geographic proximity (max 100km straight-line, 60min return travel)
- Technician availability (5 time slots: 8-10, 10-12, 12-14, 14-16, 16-18)
- Route optimization (previous job locations)
- 7-day lookahead scheduling
- Suitability scoring algorithm

---

## Test Data Setup

### Technician Test Profiles

#### Technician 1: "Central Tech"
- **Name**: John Smith
- **Home Address**: Downtown area
- **Coordinates**: 40.7128, -74.0060 (New York City Center)
- **Existing Schedule**: Empty (all slots available)

#### Technician 2: "Busy Tech"
- **Name**: Sarah Johnson
- **Home Address**: Suburban area
- **Coordinates**: 40.7589, -73.9851 (Upper Manhattan)
- **Existing Schedule**: 
  - Tomorrow: 08:00-10:00, 12:00-14:00 (occupied)
  - Day +2: 10:00-12:00 (occupied)

#### Technician 3: "Far Tech"
- **Name**: Mike Wilson
- **Home Address**: Remote location
- **Coordinates**: 41.8781, -87.6298 (Chicago - >100km away)
- **Existing Schedule**: Empty

#### Technician 4: "No Location Tech"
- **Name**: Emily Davis
- **Home Address**: NULL
- **Coordinates**: NULL, NULL
- **Existing Schedule**: Empty

#### Technician 5: "Route Optimizer"
- **Name**: David Brown
- **Home Address**: Brooklyn
- **Coordinates**: 40.6782, -73.9442
- **Existing Schedule**:
  - Tomorrow: 08:00-10:00 at (40.7000, -73.9500)
  - Tomorrow: 14:00-16:00 at (40.7200, -73.9300)

---

## Customer Test Locations

### Customer A: "Near Central"
- **Address**: Manhattan, NY
- **Coordinates**: 40.7300, -74.0100
- **Expected**: Should match Technician 1, 2, 5

### Customer B: "Near Busy Tech Previous Job"
- **Address**: Near Upper Manhattan
- **Coordinates**: 40.7100, -73.9400
- **Expected**: Should highly rank Technician 5 (proximity bonus)

### Customer C: "Far Customer"
- **Address**: Philadelphia, PA
- **Coordinates**: 39.9526, -75.1652
- **Expected**: No technicians available (>100km)

### Customer D: "Edge Case Distance"
- **Address**: Just within 100km
- **Coordinates**: 40.0000, -74.0000
- **Expected**: Borderline matches, check travel time constraint

---

## Test Scenarios

### 1. CUSTOMER TESTS

#### Test 1.1: View Available Slots (Happy Path)
**Actor**: Customer A
**Steps**:
1. Navigate to project details
2. Click "Schedule Site Survey"
3. System geocodes customer address
4. View available technicians and time slots

**Expected Results**:
- ✓ Shows 2-3 qualified technicians
- ✓ Technician 1 appears (no conflicts)
- ✓ Technician 2 appears with limited slots (08:00-10:00, 12:00-14:00 blocked tomorrow)
- ✓ Technician 3 NOT shown (>100km away)
- ✓ Technician 4 NOT shown (no location)
- ✓ Technicians sorted by suitability score
- ✓ Each slot shows: time, travel time, distance, from_location

#### Test 1.2: Select and Book Slot
**Actor**: Customer A
**Steps**:
1. Select Technician 1
2. Choose tomorrow, 10:00-12:00 slot
3. Confirm booking

**Expected Results**:
- ✓ Survey created with status "scheduled"
- ✓ Project moved to "Site Survey" sub-department
- ✓ Task created for technician
- ✓ Technician receives notification
- ✓ Activity log created

#### Test 1.3: Attempt Double Booking
**Actor**: Customer B
**Steps**:
1. Try to book Technician 2 tomorrow 08:00-10:00 (already occupied)

**Expected Results**:
- ✗ Error: "This time slot is already occupied"
- ✗ HTTP 422 response

#### Test 1.4: View Existing Survey Warning
**Actor**: Customer with pending survey
**Steps**:
1. Navigate to schedule form for project with existing survey

**Expected Results**:
- ✓ Warning message displayed
- ✓ Shows existing survey details
- ✓ Prevents duplicate scheduling

#### Test 1.5: No Available Technicians
**Actor**: Customer C (Far location)
**Steps**:
1. Request available slots

**Expected Results**:
- ✓ Empty recommendations array
- ✓ Message: "No technicians available for your location"

---

### 2. TECHNICIAN TESTS

#### Test 2.1: View Daily Schedule
**Actor**: Technician 2
**Steps**:
1. Login as technician
2. Navigate to "My Surveys" for tomorrow
3. View scheduled surveys

**Expected Results**:
- ✓ Shows 2 surveys (08:00-10:00, 12:00-14:00)
- ✓ Ordered by start_time
- ✓ Shows customer details, address, travel info
- ✓ Status: "scheduled"

#### Test 2.2: Start Survey (On-Site)
**Actor**: Technician 1
**Steps**:
1. Navigate to scheduled survey
2. Click "Start Survey"
3. System captures GPS location

**Expected Results**:
- ✓ Status changes to "in_progress"
- ✓ actual_start_time recorded
- ✓ actual_lat/actual_lng captured
- ✓ Cannot start another survey simultaneously

#### Test 2.3: Update Location During Survey
**Actor**: Technician 1
**Steps**:
1. During active survey
2. Move to different location
3. System updates GPS

**Expected Results**:
- ✓ actual_lat/actual_lng updated
- ✓ Real-time tracking enabled

#### Test 2.4: Complete Survey
**Actor**: Technician 1
**Steps**:
1. Click "Complete Survey"
2. Add notes: "Site accessible, power available, 50m cable needed"
3. Submit

**Expected Results**:
- ✓ Status changes to "completed"
- ✓ actual_end_time recorded
- ✓ Notes saved
- ✓ Activity log created
- ✓ Slot becomes available for future bookings

#### Test 2.5: View Multi-Day Schedule
**Actor**: Technician 5
**Steps**:
1. View schedule for next 7 days

**Expected Results**:
- ✓ Shows all scheduled surveys
- ✓ Grouped by date
- ✓ Route optimization visible (jobs clustered geographically)

---

### 3. ALGORITHM TESTS

#### Test 3.1: Distance Pre-Check (Haversine)
**Test Data**:
- Customer: 40.7128, -74.0060
- Technician Home: 41.8781, -87.6298 (Chicago)

**Expected**:
- ✓ Straight-line distance: ~1,145 km
- ✓ Technician skipped (>100km threshold)
- ✓ Log: "Too far (Straight Line: 1145 km)"

#### Test 3.2: Return Travel Time Constraint
**Test Data**:
- Customer D: 40.0000, -74.0000
- Technician 1: 40.7128, -74.0060

**Expected**:
- ✓ Calculate return travel time
- ✓ If >60 minutes: Technician skipped
- ✓ Log: "Return travel time too long (X mins > 60 mins)"

#### Test 3.3: Slot Occupation Check
**Test Data**:
- Technician 2 has survey 08:00-10:00
- New request for 09:00-11:00

**Expected**:
- ✓ Overlap detected (09:00-10:00)
- ✓ Slot marked as occupied
- ✓ Not shown in available slots

#### Test 3.4: Previous Job Route Optimization
**Test Data**:
- Technician 5 has job ending 10:00 at (40.7000, -73.9500)
- New customer at (40.7100, -73.9400) for 10:00-12:00 slot

**Expected**:
- ✓ from_location: "previous_job"
- ✓ Travel time calculated from previous job location
- ✓ Suitability score +20 bonus
- ✓ Higher ranking than starting from home

#### Test 3.5: Proximity Bonus Scoring
**Test Data**:
- Technician 5 has upcoming job at (40.7200, -73.9300)
- New customer at (40.7250, -73.9350) - 5km away

**Expected**:
- ✓ Distance: ~5km (<10km threshold)
- ✓ Proximity bonus: +200 points
- ✓ upcoming_nearby: true
- ✓ Technician ranked highest

#### Test 3.6: Suitability Score Calculation
**Test Data**:
- Slot A: 10 min travel, from home
- Slot B: 45 min travel, from previous job
- Slot C: 5 min travel, near upcoming job

**Expected Scores**:
- Slot A: 100 - (10/700)*30 = ~99.6
- Slot B: 100 - (45/700)*30 + 20 = ~98.1
- Slot C: 100 - (5/700)*30 + 200 = ~299.8 (highest)

#### Test 3.7: 7-Day Lookahead
**Test Data**:
- Current date: 2024-01-15
- Technician fully booked Days 1-3
- Available Days 4-7

**Expected**:
- ✓ Checks dates: 2024-01-16 to 2024-01-21
- ✓ Shows only Days 4-7 slots
- ✓ Total 20 slots available (5 slots × 4 days)

---

### 4. EDGE CASES & ERROR HANDLING

#### Test 4.1: Missing Google Maps API Key
**Setup**: Remove GOOGLE_MAPS_API_KEY from .env
**Expected**:
- ✓ Geocoding returns null
- ✓ Travel time returns null
- ✓ Technician skipped gracefully
- ✓ Log: "Could not calculate travel time"

#### Test 4.2: Invalid Coordinates
**Test Data**: Customer lat: 999, lng: 999
**Expected**:
- ✓ Validation error
- ✓ No API calls made

#### Test 4.3: API Rate Limiting
**Setup**: Exceed Google Maps API quota
**Expected**:
- ✓ Cache prevents repeated calls
- ✓ Graceful degradation
- ✓ Error logged

#### Test 4.4: Concurrent Booking Attempts
**Setup**: Two customers book same slot simultaneously
**Expected**:
- ✓ First request succeeds
- ✓ Second request fails (422)
- ✓ Database transaction prevents double-booking

#### Test 4.5: Past Date Booking
**Test Data**: survey_date: yesterday
**Expected**:
- ✓ Validation error
- ✓ "Date must be in the future"

#### Test 4.6: Technician Without Role
**Setup**: User exists but not assigned "Technician" role
**Expected**:
- ✓ Not included in technician query
- ✓ Not shown in recommendations

---

### 5. INTEGRATION TESTS

#### Test 5.1: End-to-End Workflow
**Actors**: Customer A, Technician 1, Admin
**Steps**:
1. Customer requests slots → sees recommendations
2. Customer books slot → survey created
3. Technician views schedule → sees new survey
4. Technician starts survey → status updated
5. Technician completes survey → project progresses
6. Admin views activity log → all actions logged

**Expected**: Complete workflow without errors

#### Test 5.2: Cache Performance
**Test**:
1. First request: Geocode address (API call)
2. Second request: Same address (cache hit)

**Expected**:
- ✓ First: ~500ms (API call)
- ✓ Second: <10ms (cache)
- ✓ Cache TTL: 24 hours for geocoding, 1 hour for travel

#### Test 5.3: Notification System
**Test**: Book survey for Technician 1
**Expected**:
- ✓ Email notification sent
- ✓ In-app notification created
- ✓ Contains: date, time, customer address, travel info

---

## Test Execution Checklist

### Pre-Test Setup
- [ ] Seed database with test technicians
- [ ] Configure Google Maps API key
- [ ] Clear cache
- [ ] Set system date/time
- [ ] Create test projects and customers

### Customer Testing
- [ ] Test 1.1: View available slots
- [ ] Test 1.2: Book slot successfully
- [ ] Test 1.3: Prevent double booking
- [ ] Test 1.4: Existing survey warning
- [ ] Test 1.5: No available technicians

### Technician Testing
- [ ] Test 2.1: View daily schedule
- [ ] Test 2.2: Start survey
- [ ] Test 2.3: Update location
- [ ] Test 2.4: Complete survey
- [ ] Test 2.5: Multi-day schedule

### Algorithm Testing
- [ ] Test 3.1: Distance pre-check
- [ ] Test 3.2: Travel time constraint
- [ ] Test 3.3: Slot occupation
- [ ] Test 3.4: Route optimization
- [ ] Test 3.5: Proximity bonus
- [ ] Test 3.6: Score calculation
- [ ] Test 3.7: 7-day lookahead

### Edge Cases
- [ ] Test 4.1: Missing API key
- [ ] Test 4.2: Invalid coordinates
- [ ] Test 4.3: API rate limiting
- [ ] Test 4.4: Concurrent bookings
- [ ] Test 4.5: Past date booking
- [ ] Test 4.6: Invalid technician role

### Integration
- [ ] Test 5.1: End-to-end workflow
- [ ] Test 5.2: Cache performance
- [ ] Test 5.3: Notifications

---

## Success Criteria

### Functional Requirements
✓ Customers can view available technicians and time slots
✓ System prevents double-booking
✓ Technicians can manage their schedules
✓ GPS tracking works during surveys
✓ Route optimization prioritizes nearby jobs
✓ Notifications sent correctly

### Performance Requirements
✓ Slot availability response < 3 seconds
✓ Cache hit rate > 80%
✓ Handles 50+ concurrent users
✓ API calls minimized through caching

### Business Rules Validated
✓ Max 100km straight-line distance
✓ Max 60min return travel time
✓ Max 700min travel time per slot
✓ 5 fixed time slots (2-hour blocks)
✓ 7-day lookahead scheduling
✓ Proximity bonus within 10km

---

## Bug Reporting Template

**Bug ID**: [AUTO]
**Test Case**: [e.g., Test 1.2]
**Severity**: Critical / High / Medium / Low
**Steps to Reproduce**:
1. 
2. 
3. 

**Expected Result**:
**Actual Result**:
**Screenshots/Logs**:
**Environment**: Browser, OS, Laravel version
**Assigned To**:
**Status**: Open / In Progress / Resolved

---

## Notes for Testers

1. **GPS Testing**: Use browser dev tools to simulate locations
2. **Time Testing**: Adjust system time to test different days
3. **API Testing**: Monitor network tab for API calls
4. **Cache Testing**: Clear cache between tests when needed
5. **Log Monitoring**: Check `storage/logs/laravel.log` for algorithm decisions
6. **Database**: Verify data integrity after each test
7. **Rollback**: Keep database snapshots for test resets

---

## Test Data SQL Seeds

```sql
-- Insert test technicians
INSERT INTO users (name, email, role, latitude, longitude, address) VALUES
('John Smith', 'john@test.com', 'Technician', 40.7128, -74.0060, 'Manhattan, NY'),
('Sarah Johnson', 'sarah@test.com', 'Technician', 40.7589, -73.9851, 'Upper Manhattan, NY'),
('Mike Wilson', 'mike@test.com', 'Technician', 41.8781, -87.6298, 'Chicago, IL'),
('Emily Davis', 'emily@test.com', 'Technician', NULL, NULL, NULL),
('David Brown', 'david@test.com', 'Technician', 40.6782, -73.9442, 'Brooklyn, NY');

-- Insert test customers
INSERT INTO customers (name, address, latitude, longitude) VALUES
('Customer A', 'Manhattan, NY', 40.7300, -74.0100),
('Customer B', 'Near Upper Manhattan', 40.7100, -73.9400),
('Customer C', 'Philadelphia, PA', 39.9526, -75.1652),
('Customer D', 'Edge Case Location', 40.0000, -74.0000);

-- Insert existing surveys for Technician 2
INSERT INTO site_surveys (technician_id, survey_date, start_time, end_time, status) VALUES
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00', '10:00', 'scheduled'),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '12:00', '14:00', 'scheduled'),
(2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00', '12:00', 'scheduled');

-- Insert existing surveys for Technician 5 (route optimization)
INSERT INTO site_surveys (technician_id, survey_date, start_time, end_time, customer_lat, customer_lng, status) VALUES
(5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00', '10:00', 40.7000, -73.9500, 'scheduled'),
(5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00', '16:00', 40.7200, -73.9300, 'scheduled');
```

---

**Document Version**: 1.0
**Last Updated**: 2024
**Prepared By**: QA Team
**Approved By**: Project Manager
