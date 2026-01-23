# Scoring System Guide

## How Points Are Awarded

### Driver Predictions

1. **Exact Position Match**: 10 points
   - You predict a driver finishes in position 3, and they actually finish in position 3
   - Example: You predict Verstappen finishes 1st, and he actually finishes 1st = **10 points**

2. **Off by One Position**: 1 point
   - You predict a driver finishes in position 3, but they finish in position 2 or 4
   - Example: You predict Hamilton finishes 2nd, but he finishes 3rd = **1 point**

3. **Top 3 Bonus**: 30 bonus points (TRIPLE POINTS!)
   - If you correctly predict all top 3 finishers (positions 1, 2, 3) in the correct order
   - Example: You predict 1st=Verstappen, 2nd=Hamilton, 3rd=Leclerc, and that's exactly what happens = **30 bonus points** (triple the 10 points for each position, in addition to the 30 points for the 3 exact matches)

### Constructor Predictions

1. **Exact Position Match**: 10 points
   - You predict a constructor finishes in position 2, and they actually finish in position 2
   - Example: You predict Mercedes finishes 1st, and they actually finish 1st = **10 points**

2. **Top 3 Constructor Bonus**: 30 bonus points (TRIPLE POINTS!)
   - If you correctly predict all top 3 constructors (positions 1, 2, 3) in the correct order
   - Example: You predict 1st=Mercedes, 2nd=Red Bull, 3rd=Ferrari, and that's exactly what happens = **30 bonus points** (triple points bonus)

## Maximum Points Per Race

- **Driver Points**: Up to 10 points per driver × 20 drivers = 200 points (if perfect)
- **Top 3 Driver Bonus**: 30 points (triple bonus)
- **Constructor Points**: Up to 10 points per constructor × 10 constructors = 100 points (if perfect)
- **Top 3 Constructor Bonus**: 30 points (triple bonus)

**Theoretical Maximum**: 360 points per race (extremely unlikely!)

**Note**: You must predict the FULL GRID (all 20 drivers) for each race.

## Example Scoring Scenario

### Your Predictions:
- Driver Top 3: 1st=Verstappen, 2nd=Hamilton, 3rd=Leclerc
- Constructor Top 3: 1st=Red Bull, 2nd=Mercedes, 3rd=Ferrari

### Actual Results:
- Driver Top 3: 1st=Verstappen, 2nd=Leclerc, 3rd=Hamilton
- Constructor Top 3: 1st=Red Bull, 2nd=Ferrari, 3rd=Mercedes

### Your Score:
- Verstappen: Exact match (1st) = **10 points**
- Hamilton: Predicted 2nd, actual 3rd (off by 1) = **1 point**
- Leclerc: Predicted 3rd, actual 2nd (off by 1) = **1 point**
- Top 3 Driver Bonus: Not awarded (order incorrect)
- Red Bull: Exact match (1st) = **10 points**
- Ferrari: Predicted 3rd, actual 2nd = **0 points** (no "off by one" for constructors)
- Mercedes: Predicted 2nd, actual 3rd = **0 points**
- Top 3 Constructor Bonus: Not awarded

**Total Score**: 10 + 1 + 1 + 10 = **22 points**

## Tips for Better Scores

1. **Research**: Check recent form, track history, and qualifying results
2. **Focus on Top 3**: The bonus points are significant
3. **Consider Track Characteristics**: Some tracks favor certain teams/drivers
4. **Watch Qualifying**: Qualifying results often predict race outcomes
5. **Stay Updated**: Check for penalties, weather, or last-minute changes

## Scoring Configuration

You can adjust the scoring system by editing `config.php`:

```php
define('POINTS_EXACT_POSITION', 10);      // Points for exact position match
define('POINTS_OFF_BY_ONE', 1);           // Points if off by 1 position
define('POINTS_TOP3_BONUS', 30);         // Triple points bonus for correct top 3
define('POINTS_CONSTRUCTOR_EXACT', 10);   // Points for exact constructor position
define('POINTS_CONSTRUCTOR_TOP3', 30);    // Triple points bonus for top 3 constructor prediction
```

## When Scores Are Calculated

Scores are automatically calculated when:
1. Race results are fetched from the F1 API (via `api/fetch-results.php`)
2. This happens after each race is completed
3. You can manually trigger it by visiting the API endpoint

Scores are calculated for all users who made predictions for that race.

