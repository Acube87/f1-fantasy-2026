<?php
// Run with: php tests/driver-substitution.php
// Uses a disposable LOCAL MySQL database; never connects to Railway.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$testName = 'f1_substitution_test_' . bin2hex(random_bytes(5));
$server = new mysqli('localhost', 'root', '');
$server->query("CREATE DATABASE `$testName`");
foreach (['RAILWAY_TCP_PROXY_DOMAIN', 'RAILWAY_TCP_PROXY_PORT', 'MYSQL_ROOT_PASSWORD', 'DB_PASS'] as $key) putenv($key);
putenv('MYSQLHOST=localhost');
putenv('MYSQLUSER=root');
putenv('MYSQLPASSWORD=');
putenv('MYSQLPORT=3306');
putenv('MYSQL_DATABASE=' . $testName);
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
try {
    require __DIR__ . '/../includes/functions.php';
    $db = getDB();
    $db->query('CREATE TABLE races (id INT PRIMARY KEY, race_name VARCHAR(100), country VARCHAR(50), race_date DATE)');
    $db->query('CREATE TABLE drivers (id VARCHAR(50) PRIMARY KEY, driver_name VARCHAR(100), team VARCHAR(100))');
    $db->query('CREATE TABLE predictions (id INT PRIMARY KEY, user_id INT, race_id INT, driver_id VARCHAR(50), driver_name VARCHAR(100), predicted_position INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id,race_id,driver_id))');
    $db->query('CREATE TABLE race_results (race_id INT, driver_id VARCHAR(50), driver_name VARCHAR(100), constructor_id VARCHAR(50), constructor_name VARCHAR(100), position INT)');
    $db->query('CREATE TABLE scores (user_id INT, race_id INT, driver_points INT, constructor_points INT, top3_bonus INT, constructor_top3_bonus INT, total_points INT, calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id,race_id))');
    $db->query('CREATE TABLE user_totals (user_id INT PRIMARY KEY, total_points INT, races_participated INT)');
    $db->query("INSERT INTO races VALUES (20,'Mexico City Grand Prix','Mexico','2026-11-01'),(21,'Brazilian Grand Prix','Brazil','2026-11-08')");
    $db->query("INSERT INTO drivers VALUES ('ocon','Esteban Ocon','Haas F1 Team'),('bearman','Oliver Bearman','Haas F1 Team'),('norris','Lando Norris','McLaren')");
    $db->query("INSERT INTO drivers VALUES ('hamilton','Lewis Hamilton','Ferrari'),('verstappen','Max Verstappen','Red Bull Racing'),('alonso','Fernando Alonso','Aston Martin')");
    $db->query("INSERT INTO predictions (id,user_id,race_id,driver_id,driver_name,predicted_position) VALUES (1,1,20,'ocon','Esteban Ocon',1),(2,1,20,'bearman','Oliver Bearman',2),(3,1,20,'norris','Lando Norris',3),(4,2,20,'ocon','Esteban Ocon',6),(5,1,21,'ocon','Esteban Ocon',6)");
    $db->query("INSERT INTO race_results VALUES (20,'ocon','Esteban Ocon',NULL,'Haas F1 Team',1),(20,'bearman','Oliver Bearman',NULL,'Haas F1 Team',2),(20,'norris','Lando Norris',NULL,'McLaren',3)");
    $db->query('INSERT INTO scores VALUES (1,21,11,5,0,0,16,NOW())');
    $snapshot = function($table) use ($db) {return $db->query("SELECT * FROM $table ORDER BY 1,2")->fetch_all(MYSQLI_ASSOC);};
    $beforePicks = $snapshot('predictions');
    $beforeRoster = $snapshot('drivers');
    $mexico = $db->query('SELECT * FROM races WHERE id=20')->fetch_assoc();
    $brazil = $db->query('SELECT * FROM races WHERE id=21')->fetch_assoc();
    $roster = $snapshot('drivers');
    $mexicoRoster = raceDriverNames($mexico, $roster);
    check(count($mexicoRoster) === count($roster), 'Replacement must not add a grid slot');
    check(array_column($mexicoRoster,'driver_name','id')['ocon'] === 'Ryo Hirakawa', 'Mexico must display Hirakawa');
    $brazilRoster = raceDriverNames($brazil, $roster);
    check(count($brazilRoster) === count($roster), 'Brazil replacement must not add a grid slot');
    check(array_column($brazilRoster,'driver_name','id')['ocon'] === 'Rafael Camara', 'Brazil must display Camara in the Ocon slot');
    check(raceDriverSlotId($brazil,'camara') === 'ocon', 'Camara must score against existing Ocon picks');
    check(raceDriverSlotId($mexico,'camara') === 'camara', 'Camara must not replace Hirakawa in Mexico');
    foreach ([['USA','2026-11-21'],['Qatar','2026-11-29'],['Abu Dhabi','2026-12-06'],['Mexico','2027-11-01']] as [$country,$date]) {
        $futureRace = ['country'=>$country,'race_date'=>$date];
        check(array_column(raceDriverNames($futureRace,$roster),'driver_name','id')['ocon'] === 'Rafael Camara', 'All future races must show Camara');
        check(raceDriverSlotId($futureRace,'camara') === 'ocon', 'All future Camara results must match existing picks');
    }
    $earlierBrazil = $brazil; $earlierBrazil['race_date'] = '2025-11-09';
    check(raceDriverNames($earlierBrazil,$roster) === $roster, 'Past Brazil races must be untouched');
    $otherYear = $mexico; $otherYear['race_date'] = '2025-11-01';
    check(raceDriverNames($otherYear, $roster) === $roster, 'Past seasons must be untouched');
    check(getRaceDriverSubstitutions([]) === [], 'Missing dates must not activate the replacement');
    check(raceDriverSlotId($brazil,'hirakawa') === 'hirakawa', 'Aliases must be race-specific');
    calculateRaceScores(20);
    $scoreQuery = 'SELECT user_id,race_id,driver_points,constructor_points,top3_bonus,total_points FROM scores ORDER BY user_id,race_id';
    $baseline = $db->query($scoreQuery)->fetch_all(MYSQLI_ASSOC);
    check((int)$baseline[0]['total_points'] === 82, 'Baseline must include F1, precision, podium and Haas constructor points');
    $classification = normalizeRaceClassification($mexico, [1=>'hirakawa',2=>'bearman',3=>'norris'], $mexicoRoster);
    check($classification[1] === 'ocon', 'Hirakawa result must match existing Ocon picks');
    foreach ([[1=>'ocon',2=>'hirakawa'], [1=>'missing'], [0=>'ocon'], ['01'=>'ocon',1=>'bearman']] as $invalid) {
        $rejected = false;
        try { normalizeRaceClassification($mexico,$invalid,$mexicoRoster); } catch (InvalidArgumentException $e) {$rejected=true;}
        check($rejected, 'Bad or duplicate results must be rejected before writes');
    }
    $db->begin_transaction();
    $db->query('DELETE FROM race_results WHERE race_id=20');
    $insert = $db->prepare('INSERT INTO race_results (race_id,driver_id,driver_name,constructor_name,position) VALUES (20,?,?,?,?)');
    $map = array_column($mexicoRoster,null,'id');
    foreach ($classification as $position=>$id) {
        $driver=$map[$id];
        $insert->bind_param('sssi',$id,$driver['driver_name'],$driver['team'],$position);
        $insert->execute();
    }
    calculateRaceScores(20);
    $db->commit();
    check($db->query($scoreQuery)->fetch_all(MYSQLI_ASSOC) === $baseline, 'Replacement must award identical points and preserve other race scores');
    calculateRaceScores(20);
    check($db->query($scoreQuery)->fetch_all(MYSQLI_ASSOC) === $baseline, 'Rescoring must be idempotent');
    check($snapshot('predictions') === $beforePicks, 'Every saved pick, ID, position and timestamp must remain identical');
    check($snapshot('drivers') === $beforeRoster, 'Global driver roster must remain identical');
    check($db->query("SELECT driver_name FROM race_results WHERE race_id=20 AND driver_id='ocon'")->fetch_assoc()['driver_name'] === 'Ryo Hirakawa', 'Classification must name Hirakawa');
    check((int)$db->query('SELECT total_points FROM user_totals WHERE user_id=1')->fetch_assoc()['total_points'] === 98, 'Season total must retain other race points');
    $mexicoResults = $db->query('SELECT * FROM race_results WHERE race_id=20 ORDER BY position')->fetch_all(MYSQLI_ASSOC);
    $brazilClassification = normalizeRaceClassification($brazil,[6=>'camara'],$brazilRoster);
    check($brazilClassification === [6=>'ocon'], 'Brazil classification must retain the predicted seat');
    $brazilDriver = array_column($brazilRoster,null,'id')[$brazilClassification[6]];
    $insertBrazil = $db->prepare('INSERT INTO race_results (race_id,driver_id,driver_name,constructor_name,position) VALUES (21,?,?,?,6)');
    $insertBrazil->bind_param('sss',$brazilDriver['id'],$brazilDriver['driver_name'],$brazilDriver['team']);
    $insertBrazil->execute();
    calculateRaceScores(21);
    calculateRaceScores(21);
    check($db->query($scoreQuery)->fetch_all(MYSQLI_ASSOC) === $baseline, 'Camara must earn identical position and constructor points, including on rescoring');
    check($snapshot('predictions') === $beforePicks, 'Brazil substitution must not alter any saved picks');
    check($snapshot('drivers') === $beforeRoster, 'Brazil substitution must not change the global roster');
    check($db->query('SELECT * FROM race_results WHERE race_id=20 ORDER BY position')->fetch_all(MYSQLI_ASSOC) === $mexicoResults, 'Brazil must not affect Mexico results');
    echo "PASS: Hirakawa in Mexico; Camara from Brazil onward, including future seasons; original prediction slots and positions preserved; scoring and rescoring correct; past results, saved predictions and global roster unchanged.\n";
} finally {
    $server->query("DROP DATABASE `$testName`");
}
