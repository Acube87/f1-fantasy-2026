<?php
/**
 * Script to fetch drivers and constructors from F1 API
 * Run this once to get the current season's drivers and constructors
 * You can then use this data to populate the prediction forms
 */

require_once __DIR__ . '/../config.php';

echo "<h2>Fetching 2026 F1 Drivers and Constructors</h2>";

// Fetch from Ergast API
$url = F1_API_BASE . "/drivers.json?limit=100";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, F1_API_TIMEOUT);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo "<p style='color: red;'>Error fetching drivers from API. HTTP Code: $httpCode</p>";
    exit;
}

$data = json_decode($response, true);

if (!isset($data['MRData']['DriverTable']['Drivers'])) {
    echo "<p style='color: red;'>No drivers found in API response.</p>";
    exit;
}

$drivers = $data['MRData']['DriverTable']['Drivers'];

echo "<h3>Drivers Found:</h3>";
echo "<pre>";
echo "// Drivers array for predict.php\n";
echo "\$drivers = [\n";
foreach ($drivers as $driver) {
    $id = $driver['driverId'] ?? '';
    $givenName = $driver['givenName'] ?? '';
    $familyName = $driver['familyName'] ?? '';
    $fullName = trim($givenName . ' ' . $familyName);
    echo "    ['id' => '$id', 'name' => '$fullName'],\n";
}
echo "];\n";
echo "</pre>";

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
        
        echo "<h3>Constructors Found:</h3>";
        echo "<pre>";
        echo "// Constructors array for predict.php\n";
        echo "\$constructors = [\n";
        foreach ($constructors as $constructor) {
            $id = $constructor['constructorId'] ?? '';
            $name = $constructor['name'] ?? '';
            echo "    ['id' => '$id', 'name' => '$name'],\n";
        }
        echo "];\n";
        echo "</pre>";
    }
}

echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>Copy the drivers and constructors arrays above</li>";
echo "<li>Open predict.php</li>";
echo "<li>Replace the placeholder arrays with the copied data</li>";
echo "<li>Save and upload the file</li>";
echo "</ol>";

echo "<p><a href='../index.php'>Back to Homepage</a></p>";
?>

