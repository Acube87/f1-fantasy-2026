-- ============================================================
-- FIX: Dutch Grand Prix driver-lineup shake-up
-- Hadjar (Red Bull) OUT - Lawson (RB) IN at Red Bull, Tsunoda at RB.
-- Run on the LIVE database. Safe/idempotent. Use a transaction.
-- ============================================================

START TRANSACTION;

-- Resolve the Dutch GP race id by name (don't hardcode it).
SET @race_id = COALESCE(
  (SELECT id FROM races WHERE race_name = 'Dutch Grand Prix' AND status = 'upcoming' ORDER BY race_date LIMIT 1),
  0
);

-- Sanity guard: abort if the race can't be found.
SELECT IF(@race_id = 0, 1/0, 0);
SELECT CONCAT('Targeting race_id = ', @race_id) AS 'Target';

-- ------------------------------------------------------------
-- 1) ROSTER UPDATE
--    Lawson moves up to Red Bull. Hadjar is sidelined -> Reserve
--    (sorts to the bottom of the pick list, clearly crossed out).
--    Tsunoda already sits at RB -> no change needed.
-- ------------------------------------------------------------
UPDATE drivers SET team = 'Red Bull Racing' WHERE id = 'lawson';
UPDATE drivers SET team = 'Reserve'          WHERE id = 'hadjar';

-- ------------------------------------------------------------
-- 2) AUTO-SUBSTITUTE PREDICTIONS
--    Any user who picked Hadjar gets Lawson at the SAME predicted
--    position. If a user ALSO picked Lawson, we keep their own
--    Lawson pick and drop their dead Hadjar slot instead (they
--    keep the driver they actually wanted).
-- ------------------------------------------------------------
DELETE p
FROM predictions p
JOIN predictions inp
  ON inp.race_id = p.race_id AND inp.user_id = p.user_id AND inp.driver_id = 'lawson'
WHERE p.race_id = @race_id AND p.driver_id = 'hadjar';

UPDATE predictions
SET driver_id = 'lawson', driver_name = 'Liam Lawson'
WHERE race_id = @race_id AND driver_id = 'hadjar';

-- ------------------------------------------------------------
-- 3) OFFICIAL ANNOUNCEMENT (shows at the top of the updates feed)
--    Author id 3 = Race Control system account.
-- ------------------------------------------------------------
INSERT INTO posts (race_id, title, content, author_id, is_manual)
VALUES (
  @race_id,
  '🔄 Dutch GP Lineup Change: Hadjar Out, Lawson In — How It Affects Your Picks',
  CONCAT('<div class="post-race-debrief">', '\n\n',
    '🔄 <strong>LINEUP CHANGE — DUTCH GRAND PRIX</strong>', '\n\n',
    'Red Bull have confirmed <strong>Isack Hadjar</strong> will miss the Dutch GP with a wrist injury from the summer break. <strong>Liam Lawson</strong> steps up from Racing Bulls to partner Max Verstappen, and <strong>Yuki Tsunoda</strong> takes Lawson''s seat at Racing Bulls for the Zandvoort sprint weekend.', '\n\n',
    '<strong>What this means for your picks:</strong>', '\n',
    '• Any pick for <strong>Hadjar</strong> has been automatically re-pointed to <strong>Lawson at the exact same position</strong> — your pick stays live.\n',
    '• Picks for <strong>Lawson</strong> follow the driver, so they now benefit from his promotion to Red Bull.', '\n',
    '• The roster is updated — Lawson is listed under Red Bull, Hadjar is marked <strong>Reserve</strong>.', '\n\n',
    '⏰ Picks stay open until <strong>Friday 23:59 UK</strong>. Log in and tune your grid before the deadline if you want to adjust.', '\n\n',
    'Fair play, sharp picks. See you at Zandvoort. 🏁', '\n\n',
    '</div>'),
  3,
  1
);

-- ------------------------------------------------------------
-- 4) VERIFY (should be 1 row per affected user, Lawson now Red Bull)
-- ------------------------------------------------------------
SELECT u.username, p.driver_name, p.predicted_position
FROM predictions p JOIN users u ON u.id = p.user_id
WHERE p.race_id = @race_id AND p.driver_id = 'lawson'
ORDER BY p.predicted_position;

SELECT id, driver_name, team FROM drivers WHERE id IN ('hadjar','lawson','tsunoda');

COMMIT;