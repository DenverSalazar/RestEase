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

include_once '../Includes/db.php';
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Add this function to handle date conversion
function convertExcelDate($value) {
    if (empty($value) || $value === '0000-00-00') {
        return '0000-00-00';
    }
    
    // If it's already a valid date format
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }
    
    // If it's an Excel serial number
    if (is_numeric($value) && $value > 25569) {
        try {
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            return '0000-00-00';
        }
    }
    
    // Try to parse various date formats
    $formats = ['Y-m-d', 'm/d/Y', 'd/m/Y', 'Y/m/d', 'm-d-Y', 'd-m-Y'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $value);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }
    
    return '0000-00-00';
}

// Helper to parse full name into first, middle, last, suffix
function parseFullName($fullName) {
    $parts = preg_split('/\s+/', trim($fullName));
    $suffixes = ['Jr', 'Sr', 'III', 'IV', 'II', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
    $suffix = '';
    if (count($parts) > 2 && in_array(str_replace('.', '', end($parts)), $suffixes)) {
        $suffix = array_pop($parts);
    }
    $firstName = $parts[0] ?? '';
    $middleName = (count($parts) > 2) ? $parts[1] : '';
    $lastName = (count($parts) > 2) ? $parts[2] : ($parts[1] ?? '');
    return [$firstName, $middleName, $lastName, $suffix];
}

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
        // Excel columns:
        // 0: Apt No.
        // 1: Name of Deceased
        // 2: Age
        // 3: Date of Birth
        // 4: Address of Deceased
        // 5: Informant Name
        // 6: Date Died
        // 7: Date Internment
        // 8: Validity

        $nicheID = $row[0] ?? '';
        $nameOfDeceased = $row[1] ?? '';
        $age = $row[2] ?? '';
        $born = convertExcelDate($row[3] ?? '');
        $residency = $row[4] ?? '';
        $informantName = $row[5] ?? '';
        $dateDied = convertExcelDate($row[6] ?? '');
        $dateInternment = convertExcelDate($row[7] ?? '');
        $validity = convertExcelDate($row[8] ?? '');

        // Parse name
        list($firstName, $middleName, $lastName, $suffix) = parseFullName($nameOfDeceased);

        // Basic validation (optional)
        if ($firstName && $lastName && is_numeric($age)) {
            $stmt = $conn->prepare("INSERT INTO deceased (firstName, middleName, lastName, suffix, age, born, residency, dateDied, dateInternment, nicheID, informantName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                $errors[] = "Prepare failed for row $i: " . $conn->error;
                continue;
            }
            $stmt->bind_param("ssssissssss", $firstName, $middleName, $lastName, $suffix, $age, $born, $residency, $dateDied, $dateInternment, $nicheID, $informantName);
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
