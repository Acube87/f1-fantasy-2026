-- ============================================================
-- FIX: ensure both Racing Bulls seats are in the roster.
-- Safe on ANY environment (idempotent, no duplicates):
--   * adds Tsunoda    if missing
--   * adds Lindblad   if missing
--   * normalises both to team 'Racing Bulls'
-- After this, the pick list = 22 drivers (the full grid),
-- Hadjar (Reserve) stays hidden, and scoring aligns 1:1
-- with the 22-car race result.
-- ============================================================
INSERT INTO drivers (id, driver_name, team) VALUES
  ('tsunoda', 'Yuki Tsunoda',   'Racing Bulls'),
  ('lindblad', 'Arvid Lindblad', 'Racing Bulls')
ON DUPLICATE KEY UPDATE team = 'Racing Bulls';

-- Verify: expect 22 pickable drivers (23 rows incl. Hadjar Reserve).
SELECT COUNT(*) AS total_roster FROM drivers;
SELECT COUNT(*) AS pickable FROM drivers WHERE team NOT LIKE '%Reserve%' OR team IS NULL;
SELECT id, driver_name, team FROM drivers WHERE id IN ('tsunoda','lindblad') ORDER BY id;