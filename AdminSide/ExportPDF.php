<?php
// Remove the "use TCPDF;" line, not needed for TCPDF global class

include_once '../Includes/db.php';
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$result = $conn->query("SELECT nicheID, lastName, firstName, residency, informantName, dateDied, dateInternment FROM deceased ORDER BY id DESC");

require_once __DIR__ . '/../vendor/autoload.php';

$pdf = new \TCPDF();
$pdf->SetCreator('RestEase');
$pdf->SetAuthor('RestEase');
$pdf->SetTitle('Cemetery Masterlist');
$pdf->SetMargins(10, 15, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Cemetery Masterlist', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);

$html = '<table border="1" cellpadding="4">
<tr style="background-color:#f7f8fa;">
<th>Apt No.</th>
<th>Name of Deceased</th>
<th>Address of Deceased</th>
<th>Informant Name</th>
<th>Date Died</th>
<th>Date Internment</th>
<th>Validity</th>
</tr>';

while ($row = $result->fetch_assoc()) {
    $name = htmlspecialchars($row['lastName'] . ', ' . $row['firstName']);
    $apt = htmlspecialchars($row['nicheID']);
    $residency = htmlspecialchars($row['residency']);
    $informant = htmlspecialchars($row['informantName']);
    $dateDied = htmlspecialchars($row['dateDied']);
    $dateInternment = htmlspecialchars($row['dateInternment']);
    $validity = '';
    if ($dateInternment && $dateInternment !== '0000-00-00') {
        $dt = new DateTime($dateInternment);
        $dt->modify('+5 years');
        $validity = $dt->format('Y-m-d');
    }
    $html .= '<tr>
        <td>' . $apt . '</td>
        <td>' . $name . '</td>
        <td>' . $residency . '</td>
        <td>' . $informant . '</td>
        <td>' . $dateDied . '</td>
        <td>' . $dateInternment . '</td>
        <td>' . $validity . '</td>
    </tr>';
}
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('cemetery_masterlist.pdf', 'I');
$conn->close();
