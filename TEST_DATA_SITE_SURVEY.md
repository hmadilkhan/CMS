# Site Survey Scheduling - Test Data

## Test Scenario: Los Angeles Area

### Technicians (Add these users with "Technician" role)

#### Technician 1: John Martinez
- **Name**: John Martinez
- **Email**: john.martinez@test.com
- **Username**: jmartinez
- **Address**: 1234 Wilshire Blvd, Los Angeles, CA 90017
- **Latitude**: 34.0522
- **Longitude**: -118.2437
- **Home Base**: Downtown Los Angeles

#### Technician 2: Sarah Chen
- **Name**: Sarah Chen
- **Email**: sarah.chen@test.com
- **Username**: schen
- **Address**: 5678 Santa Monica Blvd, West Hollywood, CA 90038
- **Latitude**: 34.0900
- **Longitude**: -118.3617
- **Home Base**: West Hollywood

#### Technician 3: Michael Johnson
- **Name**: Michael Johnson
- **Email**: michael.johnson@test.com
- **Username**: mjohnson
- **Address**: 9012 Ventura Blvd, Sherman Oaks, CA 91403
- **Latitude**: 34.1508
- **Longitude**: -118.4398
- **Home Base**: Sherman Oaks (San Fernando Valley)

---

## Customer Addresses for Testing

### Test Case 1: Downtown LA Customer (Close to John Martinez)
**Customer Address**: 789 S Figueroa St, Los Angeles, CA 90017
- **Expected Result**: John Martinez should have highest suitability score (very close to his home)
- **Distance from John**: ~1-2 km
- **Distance from Sarah**: ~8-10 km
- **Distance from Michael**: ~20-25 km

### Test Case 2: Beverly Hills Customer (Between John and Sarah)
**Customer Address**: 9876 Wilshire Blvd, Beverly Hills, CA 90210
- **Expected Result**: Sarah Chen should have highest score (closest)
- **Distance from Sarah**: ~5-7 km
- **Distance from John**: ~12-15 km
- **Distance from Michael**: ~15-18 km

### Test Case 3: Valley Customer (Close to Michael Johnson)
**Customer Address**: 14500 Ventura Blvd, Sherman Oaks, CA 91403
- **Expected Result**: Michael Johnson should have highest score
- **Distance from Michael**: ~2-3 km
- **Distance from Sarah**: ~15-18 km
- **Distance from John**: ~25-30 km

### Test Case 4: Santa Monica Customer (Moderate distance from all)
**Customer Address**: 1234 Ocean Ave, Santa Monica, CA 90401
- **Expected Result**: Sarah Chen likely best (closest to west side)
- **Distance from Sarah**: ~8-10 km
- **Distance from John**: ~18-20 km
- **Distance from Michael**: ~22-25 km

### Test Case 5: Pasadena Customer (Far from all - edge case)
**Customer Address**: 100 W Colorado Blvd, Pasadena, CA 91105
- **Expected Result**: John Martinez (closest to downtown/east side)
- **Distance from John**: ~15-18 km
- **Distance from Sarah**: ~25-28 km
- **Distance from Michael**: ~30-35 km

---

## Testing Scenarios

### Scenario A: Empty Schedule (All Slots Available)
1. Create 3 technicians with above addresses
2. Don't schedule any existing surveys
3. Search for customer at "789 S Figueroa St, Los Angeles, CA 90017"
4. **Expected**: All 5 time slots available for all technicians, John ranked #1

### Scenario B: Partially Booked Schedule
1. Book John Martinez for 08:00-10:00 and 10:00-12:00
2. Search for same customer address
3. **Expected**: John shows only 3 slots (12:00-14:00, 14:00-16:00, 16:00-18:00)

### Scenario C: Route Optimization Test
1. Book John Martinez for 08:00-10:00 at "789 S Figueroa St" (downtown)
2. Search for customer at "1200 S Hope St, Los Angeles, CA 90015" (also downtown, very close)
3. **Expected**: John's 10:00-12:00 slot should show very low travel time (coming from previous job)

### Scenario D: Upcoming Jobs Proximity Bonus
1. Book John Martinez for tomorrow at "800 S Figueroa St" (downtown)
2. Search for today at "789 S Figueroa St" (same area)
3. **Expected**: John should get "Nearby Jobs" badge and higher suitability score

### Scenario E: No Available Technicians
1. Book all technicians for all slots on a specific date
2. Search for any customer address on that date
3. **Expected**: "No available slots found for this date" message

---

## SQL Insert Statements (Quick Setup)

```sql
-- Insert Technicians (adjust user_type_id and other fields as per your schema)
INSERT INTO users (name, email, username, password, user_type_id, address, latitude, longitude, created_at, updated_at) VALUES
('John Martinez', 'john.martinez@test.com', 'jmartinez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '1234 Wilshire Blvd, Los Angeles, CA 90017', 34.0522, -118.2437, NOW(), NOW()),
('Sarah Chen', 'sarah.chen@test.com', 'schen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '5678 Santa Monica Blvd, West Hollywood, CA 90038', 34.0900, -118.3617, NOW(), NOW()),
('Michael Johnson', 'michael.johnson@test.com', 'mjohnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '9012 Ventura Blvd, Sherman Oaks, CA 91403', 34.1508, -118.4398, NOW(), NOW());

-- Assign Technician Role (get role_id for 'Technician' role first)
-- Replace {user_id} with actual IDs from above insert
INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES
((SELECT id FROM roles WHERE name = 'Technician'), 'App\\Models\\User', {john_id}),
((SELECT id FROM roles WHERE name = 'Technician'), 'App\\Models\\User', {sarah_id}),
((SELECT id FROM roles WHERE name = 'Technician'), 'App\\Models\\User', {michael_id});
```

---

## Alternative Test Data: New York City Area

### NYC Technicians

#### Technician 1: David Rodriguez
- **Address**: 350 5th Ave, New York, NY 10118 (Empire State Building area)
- **Latitude**: 40.7484
- **Longitude**: -73.9857

#### Technician 2: Emily Wang
- **Address**: 123 Brooklyn Bridge, Brooklyn, NY 11201
- **Latitude**: 40.7061
- **Longitude**: -73.9969

#### Technician 3: James O'Connor
- **Address**: 456 Broadway, Queens, NY 11373
- **Latitude**: 40.7282
- **Longitude**: -73.8781

### NYC Customer Test Addresses
1. **Times Square**: 1560 Broadway, New York, NY 10036
2. **Brooklyn Heights**: 100 Montague St, Brooklyn, NY 11201
3. **Queens Center**: 90-15 Queens Blvd, Queens, NY 11373
4. **Upper East Side**: 1000 5th Ave, New York, NY 10028
5. **Lower Manhattan**: 200 Broadway, New York, NY 10038

---

## Expected Algorithm Behavior

### Distance-Based Ranking
- Technician closest to customer gets highest score
- Travel time under 60 minutes required (configurable)
- Each slot scored independently

### Route Optimization
- If technician has earlier job same day, travel calculated from that location
- Bonus points for route continuity (+20 points)

### Upcoming Jobs Bonus
- If technician has job within 10km in next 2-3 days: +50 points
- Shows "Nearby Jobs" badge

### Time Slot Display
- 5 fixed slots: 8-10, 10-12, 12-2, 2-4, 4-6
- Only shows available (not booked) slots
- Shows travel time and distance for each slot

---

## Testing Checklist

- [ ] Create 3 technicians with addresses
- [ ] Assign "Technician" role to all
- [ ] Verify latitude/longitude are saved
- [ ] Create test project with customer address
- [ ] Test Case 1: Empty schedule - all slots show
- [ ] Test Case 2: Book some slots - remaining slots show
- [ ] Test Case 3: Verify ranking (closest technician first)
- [ ] Test Case 4: Book all slots - no slots message
- [ ] Test Case 5: Route optimization - check travel time from previous job
- [ ] Test Case 6: Upcoming jobs - verify "Nearby Jobs" badge
- [ ] Test Case 7: Schedule a slot - verify no double-booking
- [ ] Test Case 8: Try to book same slot twice - should fail

---

## Google Maps API Key Required

Make sure you have set `GOOGLE_MAPS_API_KEY` in your `.env` file:
```
GOOGLE_MAPS_API_KEY=your_actual_api_key_here
```

The API is used for:
1. Geocoding customer addresses to lat/lng
2. Calculating travel time with real-time traffic
3. Calculating distances between locations

---

## Troubleshooting

### Issue: No slots showing
- Check if technicians have "Technician" role assigned
- Verify latitude/longitude are set for technicians
- Check Google Maps API key is valid
- Check browser console for errors

### Issue: Wrong ranking
- Verify technician addresses are correct
- Check if MAX_TRAVEL_TIME is too restrictive (default 700 min)
- Verify customer address is valid and geocodable

### Issue: "Unable to locate address"
- Customer address might be invalid
- Google Maps API key might be missing/invalid
- Check network tab for API errors
