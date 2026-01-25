<?php
/**
 * Script to fetch drivers and constructors from F1 API
 * Run this once to get the current season's drivers and constructors
 * This script will populate the 'drivers' and 'constructors' tables in the database.
 */

require_once __DIR__ . '/../config.php';

echo "<h2>Fetching 2026 F1 Drivers and Constructors</h2>";

$db = getDB();

// Truncate tables to ensure fresh data
$db->query("TRUNCATE TABLE drivers");
$db->query("TRUNCATE TABLE constructors");

// Fetch drivers and their teams from Ergast API
$url = F1_API_BASE . "/driverStandings.json";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, F1_API_TIMEOUT);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo "<p style='color: red;'>Error fetching driver standings from API. HTTP Code: $httpCode</p>";
    exit;
}

$data = json_decode($response, true);

if (!isset($data['MRData']['StandingsTable']['StandingsLists'][0]['DriverStandings'])) {
    echo "<p style='color: red;'>No driver standings found in API response.</p>";
    exit;
}

$driverStandings = $data['MRData']['StandingsTable']['StandingsLists'][0]['DriverStandings'];

// Insert drivers into the database
$stmt = $db->prepare("INSERT INTO drivers (id, driver_name, team) VALUES (?, ?, ?)");
$driverCount = 0;
foreach ($driverStandings as $driverStanding) {
    $driver = $driverStanding['Driver'];
    $constructor = $driverStanding['Constructors'][0];
    
    $id = $driver['driverId'] ?? '';
    $givenName = $driver['givenName'] ?? '';
    $familyName = $driver['familyName'] ?? '';
    $fullName = trim($givenName . ' ' . $familyName);
    $team = $constructor['name'] ?? 'Unknown';
    
    $stmt->bind_param("sss", $id, $fullName, $team);
    $stmt->execute();
    $driverCount++;
}

echo "<p style='color: green;'>Successfully inserted $driverCount drivers into the database.</p>";

// Fetch constructors
$url = F1_API_BASE . "/constructors.json?limit=100";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, F1_API_TIMEOUT);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    
    if (isset($data['MRData']['ConstructorTable']['Constructors'])) {
        $constructors = $data['MRData']['ConstructorTable']['Constructors'];
        
        // Insert constructors into the database
        $stmt = $db->prepare("INSERT INTO constructors (id, name) VALUES (?, ?)");
        $constructorCount = 0;
        foreach ($constructors as $constructor) {
            $id = $constructor['constructorId'] ?? '';
            $name = $constructor['name'] ?? '';
            
            $stmt->bind_param("ss", $id, $name);
            $stmt->execute();
            $constructorCount++;
        }
        
        echo "<p style='color: green;'>Successfully inserted $constructorCount constructors into the database.</p>";
    }
}

echo "<p><strong>The 'drivers' and 'constructors' tables have been populated.</strong></p>";
echo "<p><a href='../index.php'>Back to Homepage</a></p>";
?>
