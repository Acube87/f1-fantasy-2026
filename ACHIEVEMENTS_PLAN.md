# 🏆 F1 Fantasy Achievements System - Implementation Plan

## Achievement Tiers & Colors

- 🟢 **COMMON** (Green) - Easy to unlock, early game achievements
- 🔵 **RARE** (Blue) - Medium difficulty, consistent play required
- 🟣 **EPIC** (Purple) - Hard to achieve, skill-based
- 🔴 **LEGENDARY** (Red) - Extremely rare, top-tier performance
- 🟡 **SPECIAL** (Yellow/Gold) - Limited time or unique conditions

---

## Complete Achievement List

### 🟢 COMMON ACHIEVEMENTS (Green Badge)

| ID | Name | Description | Unlock Criteria | Difficulty |
|----|------|-------------|-----------------|------------|
| `first_prediction` | **Rookie Driver** | Make your first prediction | Submit 1 prediction | ⭐ |
| `welcome_aboard` | **Welcome to the Paddock** | Complete your profile setup | Fill profile details | ⭐ |
| `participation_1` | **Racing Regular** | Participate in 5 races | Complete 5 race predictions | ⭐⭐ |
| `first_points` | **On the Board** | Score your first points | Earn any points | ⭐ |
| `streak_3` | **Consistency Counts** | Predict 3 races in a row | 3-race streak | ⭐⭐ |

### 🔵 RARE ACHIEVEMENTS (Blue Badge)

| ID | Name | Description | Unlock Criteria | Difficulty |
|----|------|-------------|-----------------|------------|
| `participation_10` | **Season Veteran** | Participate in 10 races | Complete 10 race predictions | ⭐⭐⭐ |
| `streak_5` | **Dedicated Strategist** | Predict 5 races in a row | 5-race streak | ⭐⭐⭐ |
| `podium_sweep_1` | **Podium Prophet** | Get a podium sweep bonus | All 3 podium correct once | ⭐⭐⭐ |
| `top_10_finish` | **Points Scorer** | Finish in top 10 overall | Rank #1-10 at season end | ⭐⭐⭐⭐ |
| `constructor_correct_5` | **Team Tactician** | Predict winning constructor 5 times | 5 correct constructor predictions | ⭐⭐⭐ |
| `total_500` | **Point Collector** | Score 500 total points | Accumulate 500 points | ⭐⭐⭐ |

### 🟣 EPIC ACHIEVEMENTS (Purple Badge)

| ID | Name | Description | Unlock Criteria | Difficulty |
|----|------|-------------|-----------------|------------|
| `perfect_race` | **Flawless Forecast** | Score 100+ points in a single race | 100+ points in one race | ⭐⭐⭐⭐⭐ |
| `podium_sweep_3` | **Crystal Ball** | Get 3 podium sweeps in a season | 3 podium sweeps | ⭐⭐⭐⭐⭐ |
| `streak_10` | **Unbreakable Focus** | Predict 10 races in a row | 10-race streak | ⭐⭐⭐⭐ |
| `top_5_finish` | **Elite Strategist** | Finish in top 5 overall | Rank #1-5 at key point | ⭐⭐⭐⭐⭐ |
| `double_points_master` | **Double or Nothing** | Score 150+ in a 2x points race | 150+ in China/UK/Singapore | ⭐⭐⭐⭐⭐ |
| `accuracy_king` | **Precision Engineer** | Achieve 30% prediction accuracy | 30% exact matches | ⭐⭐⭐⭐⭐ |
| `total_1000` | **Points Millionaire** | Score 1000 total points | Accumulate 1000 points | ⭐⭐⭐⭐ |

### 🔴 LEGENDARY ACHIEVEMENTS (Red Badge)

| ID | Name | Description | Unlock Criteria | Difficulty |
|----|------|-------------|-----------------|------------|
| `champion` | **World Champion** | Win the season championship | Rank #1 overall at season end | ⭐⭐⭐⭐⭐⭐ |
| `podium_sweep_5` | **Oracle of the Grid** | Get 5 podium sweeps in a season | 5 podium sweeps | ⭐⭐⭐⭐⭐⭐ |
| `perfect_streak` | **Perfect Season** | Predict ALL races in a season | Never miss a race | ⭐⭐⭐⭐⭐ |
| `top_100_all_time` | **Hall of Fame** | Reach top 100 all-time leaderboard | Top 100 globally (future) | ⭐⭐⭐⭐⭐⭐ |
| `accuracy_legend` | **The Nostradamus** | Achieve 40% prediction accuracy | 40% exact matches | ⭐⭐⭐⭐⭐⭐ |
| `total_2500` | **Point Legend** | Score 2500 total points | Accumulate 2500 points | ⭐⭐⭐⭐⭐⭐ |

### 🟡 SPECIAL ACHIEVEMENTS (Yellow/Gold Badge)

| ID | Name | Description | Unlock Criteria | Difficulty |
|----|------|-------------|-----------------|------------|
| `first_race_winner` | **Early Bird** | Win the first race of season | Rank #1 in Race 1 | ⭐⭐⭐⭐ |
| `comeback_king` | **Phoenix Rising** | Climb 10+ positions in standings in one race | Jump 10 ranks | ⭐⭐⭐⭐ |
| `underdog_victory` | **Giant Slayer** | Win a race while ranked outside top 10 | Win from low rank | ⭐⭐⭐⭐⭐ |
| `constructor_sweep` | **Team Whisperer** | Predict correct constructor 10 times | 10 correct constructor picks | ⭐⭐⭐⭐ |
| `birthday_bonus` | **Birthday Champion** | Score points on your birthday | Points on birthday | ⭐⭐ |

---

## Database Schema

### Table: `achievements`
```sql
CREATE TABLE achievements (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    tier ENUM('common', 'rare', 'epic', 'legendary', 'special') NOT NULL,
    icon VARCHAR(50) DEFAULT 'trophy',
    unlock_criteria TEXT NOT NULL,
    points_reward INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Table: `user_achievements`
```sql
CREATE TABLE user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id VARCHAR(50) NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_displayed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id)
);
```

### Table: `achievement_progress`
```sql
CREATE TABLE achievement_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id VARCHAR(50) NOT NULL,
    current_value INT DEFAULT 0,
    target_value INT NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_progress (user_id, achievement_id)
);
```

---

## Implementation Phases

### ✅ PHASE 1: Frontend & Design (EASY - Start Here)
- [x] Create achievements.php page with all badges
- [x] Design achievement cards with tier colors
- [x] Add "Achievements" link to navigation menu
- [ ] Create badge component/helper function

**Difficulty**: 🟢 Easy
**Time**: 2-3 hours

---

### ✅ PHASE 2: Database Setup (EASY)
- [ ] Create `achievements` table with all achievement definitions
- [ ] Create `user_achievements` table for tracking unlocks
- [ ] Create `achievement_progress` table for progress tracking
- [ ] Seed initial achievements data

**Difficulty**: 🟢 Easy
**Time**: 1 hour

---

### 🔶 PHASE 3: Achievement Checker System (MEDIUM)
- [ ] Create `includes/achievements.php` with helper functions:
  - `checkAchievements($userId)` - Check all achievements for a user
  - `unlockAchievement($userId, $achievementId)` - Award achievement
  - `getAchievementProgress($userId, $achievementId)` - Get progress %
  - `getUserAchievements($userId)` - Get all unlocked achievements
  - `getDisplayedBadge($userId)` - Get user's selected badge

**Difficulty**: 🟡 Medium
**Time**: 4-5 hours

---

### 🔶 PHASE 4: Auto-Unlock Integration (MEDIUM-HARD)
Hook achievement checks into existing workflows:

#### **Easy to Implement:**
- ✅ `first_prediction` - Check after first prediction save
- ✅ `welcome_aboard` - Check after profile update
- ✅ `first_points` - Check after score calculated > 0
- ✅ `participation_1`, `participation_10` - Count predictions
- ✅ `total_500`, `total_1000`, `total_2500` - Check total_points

#### **Medium Difficulty:**
- 🟡 `streak_3`, `streak_5`, `streak_10` - Need to track consecutive races
- 🟡 `podium_sweep_1/3/5` - Count podium sweeps from scores table
- 🟡 `constructor_correct_5/10` - Track constructor predictions
- 🟡 `accuracy_king/legend` - Calculate prediction accuracy %

#### **Harder to Implement:**
- 🔴 `perfect_race` - Check if single race score >= 100
- 🔴 `top_5_finish`, `top_10_finish` - Requires ranking calculation
- 🔴 `champion` - Season-end ranking check
- 🔴 `comeback_king` - Track rank changes between races
- 🔴 `underdog_victory` - Complex rank + win condition
- 🔴 `birthday_bonus` - Requires birthday field in users table

**Difficulty**: 🟡🔴 Medium to Hard
**Time**: 8-10 hours

---

### 🔶 PHASE 5: Badge Display System (EASY-MEDIUM)
- [ ] Add badge display on leaderboard
- [ ] Add badge display on user profile
- [ ] Add "Select Badge" UI on achievements page
- [ ] Update `user_achievements.is_displayed` when user selects badge
- [ ] Show badge next to username in:
  - Leaderboard table
  - Dashboard header
  - Race results

**Difficulty**: 🟡 Medium
**Time**: 3-4 hours

---

### ✅ PHASE 6: Progress Tracking (OPTIONAL - NICE TO HAVE)
- [ ] Show % progress on locked achievements
- [ ] "Almost there!" notifications
- [ ] Progress bars on achievement cards

**Difficulty**: 🟡 Medium
**Time**: 2-3 hours

---

## Total Implementation Time
- **Full System**: ~20-25 hours
- **MVP (Phases 1-4)**: ~15-18 hours

---

## Questions & Challenges

### ❓ Questions for You:
1. **Birthday achievements** - Do we have birthday field in users table?
2. **Season-end achievements** - How do we define "season end"? Specific date?
3. **Point rewards** - Should achievements give bonus points?
4. **Retroactive unlocks** - Check achievements for existing users with history?

### 🚨 Hard to Implement:
1. **Streak tracking** - Need to track consecutive race participation (requires new logic)
2. **Rank changes** - Need to snapshot rankings after each race
3. **Season-based achievements** - Need season management system
4. **All-time leaderboard** - Requires cross-season data (future feature)

### ✅ Easy to Implement:
1. All point-based achievements (total_500, etc.)
2. First-time achievements (first_prediction, welcome_aboard)
3. Count-based (participation_N, podium_sweep_N)
4. Accuracy-based (can calculate from existing data)

---

## Next Steps
1. **Review & approve** achievement list
2. **Create achievements page** (Phase 1)
3. **Set up database** (Phase 2)
4. **Build checker system** (Phase 3)
5. **Integrate auto-unlock** (Phase 4)
6. **Polish UI** (Phase 5)
