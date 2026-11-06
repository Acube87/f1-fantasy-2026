<?php
/**
 * Replacement drivers occupy the ORIGINAL prediction slot for applicable races.
 * Never rename a global driver or rewrite a user's saved picks for these swaps.
 * Result imports must use raceDriverSlotId() before storing results, so scoring,
 * accuracy and achievements continue to join on the original driver_id.
 */
function getRaceDriverSubstitutions(array $race): array {
    if (($race['country'] ?? '') === 'Mexico' && ($race['race_date'] ?? '') === '2026-11-01') {
        return ['ocon' => ['id' => 'hirakawa', 'driver_name' => 'Ryo Hirakawa', 'replaces' => 'Esteban Ocon']];
    }
    // Permanent replacement from Brazil onward, including future seasons.
    // Earlier races keep their original driver, including Hirakawa in Mexico.
    if (($race['race_date'] ?? '') >= '2026-11-08') {
        return ['ocon' => ['id' => 'camara', 'driver_name' => 'Rafael Camara', 'replaces' => 'Esteban Ocon']];
    }
    return [];
}

function raceDriverSlotId(array $race, string $driverId): string {
    foreach (getRaceDriverSubstitutions($race) as $slot => $replacement) {
        if ($driverId === $replacement['id']) return $slot;
    }
    return $driverId;
}

/** Change display names only; retain IDs, positions, teams and row ordering. */
function raceDriverNames(array $race, array $rows): array {
    $substitutions = getRaceDriverSubstitutions($race);
    foreach ($rows as &$row) {
        $id = $row['driver_id'] ?? $row['id'] ?? '';
        if (isset($substitutions[$id])) {
            $row['driver_name'] = $substitutions[$id]['driver_name'];
        }
    }
    unset($row);
    return $rows;
}

function raceDriverSubstitutionNote(array $race): string {
    $notes = [];
    foreach (getRaceDriverSubstitutions($race) as $replacement) {
        $notes[] = $replacement['driver_name'] . ' replaces ' . $replacement['replaces']
            . ' for this race. Existing picks keep their positions and count for the replacement automatically.';
    }
    return implode(' ', $notes);
}

/** Validate the whole classification before any existing results are cleared. */
function normalizeRaceClassification(array $race, array $results, array $drivers): array {
    $known = array_column($drivers, null, 'id');
    $normalized = [];
    $seen = [];
    foreach ($results as $position => $driverId) {
        if (!ctype_digit((string)$position) || (int)$position < 1 || (int)$position > count($drivers)
            || !is_string($driverId)) {
            throw new InvalidArgumentException('Invalid finishing position or driver. Check the results grid.');
        }
        $slotId = raceDriverSlotId($race, $driverId);
        if (!isset($known[$slotId])) {
            throw new InvalidArgumentException('Unknown driver in the results grid: ' . $driverId);
        }
        if (isset($seen[$slotId]) || isset($normalized[(int)$position])) {
            throw new InvalidArgumentException('A driver appears more than once in the results grid. No results were changed.');
        }
        $seen[$slotId] = true;
        $normalized[(int)$position] = $slotId;
    }
    return $normalized;
}
