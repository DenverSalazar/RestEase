<?php
// Try alternate path for Composer autoloader if ../vendor/autoload.php is missing
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php'
];
$autoloadFound = false;
foreach ($autoloadPaths as $autoload) {
    if (file_exists($autoload)) {
        require $autoload;
        $autoloadFound = true;
        break;
    }
}
if (!$autoloadFound) {
    exit('Composer autoload.php not found. Please run "composer install" in your project root.');
}

use PhpOffice\PhpSpreadsheet\IOFactory;

$conn = new mysqli("localhost", "root", "", "cemeterydb");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $fileTmp = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = IOFactory::load($fileTmp);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $inserted = 0;
    $errors = [];

    // Skip header row, start from row 2
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        // Adjust indexes based on your Excel columns
        $firstName = $row[0] ?? '';
        $lastName = $row[1] ?? '';
        $age = $row[2] ?? '';
        $born = $row[3] ?? '';
        $residency = $row[4] ?? '';
        $dateDied = $row[5] ?? '';
        $dateInternment = $row[6] ?? '';
        $nicheID = $row[7] ?? '';
        $informantName = $row[8] ?? '';

        // Basic validation (optional)
        if ($firstName && $lastName && is_numeric($age)) {
            $stmt = $conn->prepare("INSERT INTO deceased (firstName, lastName, age, born, residency, dateDied, dateInternment, nicheID, informantName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                $errors[] = "Prepare failed for row $i: " . $conn->error;
                continue;
            }
            $stmt->bind_param("ssissssss", $firstName, $lastName, $age, $born, $residency, $dateDied, $dateInternment, $nicheID, $informantName);
            if ($stmt->execute()) {
                $inserted++;
            } else {
                $errors[] = "Insert failed for row $i: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Skipped row $i: missing required fields or invalid age.";
        }
    }
    $conn->close();

    // Show result for debugging
    if (!empty($errors)) {
        echo "<h3>Import completed with some issues:</h3>";
        echo "<p>Rows inserted: $inserted</p>";
        echo "<ul>";
        foreach ($errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>";
        echo "</ul>";
        echo '<a href="Records.php">Back to Records</a>';
        exit();
    } else {
        header("Location: Records.php?import=success&count=$inserted");
        exit();
    }
} else {
    echo "No file uploaded.";
}
