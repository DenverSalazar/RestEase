<?php
include_once '../Includes/db.php';
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$result = $conn->query("SELECT nicheID, lastName, firstName, middleName, suffix, residency, informantName, dateDied, dateInternment FROM deceased ORDER BY id DESC");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header
$sheet->setCellValue('A1', 'Apt No.');
$sheet->setCellValue('B1', 'Name of Deceased');
$sheet->setCellValue('C1', 'Address of Deceased');
$sheet->setCellValue('D1', 'Informant Name');
$sheet->setCellValue('E1', 'Date Died');
$sheet->setCellValue('F1', 'Date Internment');
$sheet->setCellValue('G1', 'Validity');

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    // Compose full name: LastName, FirstName M. Suffix
    $middleInitial = $row['middleName'] ? strtoupper(substr(trim($row['middleName']), 0, 1)) . '.' : '';
    $suffix = $row['suffix'] ? ' ' . $row['suffix'] : '';
    $name = $row['lastName'] . ', ' . $row['firstName'];
    if ($middleInitial) $name .= ' ' . $middleInitial;
    if ($suffix) $name .= $suffix;

    $apt = $row['nicheID'];
    $residency = $row['residency'];
    $informant = $row['informantName'];
    $dateDied = $row['dateDied'];
    $dateInternment = $row['dateInternment'];
    $validity = '';
    if ($dateInternment && $dateInternment !== '0000-00-00') {
        $dt = new DateTime($dateInternment);
        $dt->modify('+5 years');
        $validity = $dt->format('Y-m-d');
    }

    $sheet->setCellValue('A' . $rowNum, $apt);
    $sheet->setCellValue('B' . $rowNum, $name);
    $sheet->setCellValue('C' . $rowNum, $residency);
    $sheet->setCellValue('D' . $rowNum, $informant);
    $sheet->setCellValue('E' . $rowNum, $dateDied);
    $sheet->setCellValue('F' . $rowNum, $dateInternment);
    $sheet->setCellValue('G' . $rowNum, $validity);

    $rowNum++;
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="cemetery_masterlist.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
$conn->close();
exit;
