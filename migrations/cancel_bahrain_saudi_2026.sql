-- Migration: Cancel Bahrain GP and Saudi Arabian GP 2026
-- Reason: Cancelled due to Middle East conflict (March 2026)
-- Next race: Miami Grand Prix (2026-05-03)
-- Run date: 2026-03-29

UPDATE races SET status = 'cancelled' WHERE id IN (4, 5);

-- Verify:
-- SELECT id, race_name, country, race_date, status FROM races WHERE id IN (4, 5, 6);
