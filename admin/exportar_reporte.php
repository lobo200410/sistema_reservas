<?php

session_start();
require_once '../conexion.php';
require_once '../vendor/autoload.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  exit('Acceso denegado.');
}

date_default_timezone_set('America/El_Salvador');

$anio   = isset($_POST['anio']) ? (int)$_POST['anio'] : 0;
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

if ($anio < 2000 || $anio > 2100) {
  exit('Año inválido.');
}


$facus = [];
$qr = $conn->query("SELECT id, nombre FROM facultades ORDER BY id ASC");
while ($f = $qr->fetch_assoc()) {
  $facus[(int)$f['id']] = $f['nombre'];
}


$meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
          7=>'Julio',8=>'Agosto',9=>'Sept.',10=>'Oct.',11=>'Nov.',12=>'Dic.'];

$tabla   = [];
$totFila = [];
$totMes  = array_fill(1, 12, 0);
foreach ($facus as $fid => $n) {
  $tabla[$fid]   = array_fill(1, 12, 0);
  $totFila[$fid] = 0;
}


$baseSQL = "
  SELECT facultad_id, MONTH(fecha_reserva) AS m, COUNT(*) AS c
  FROM reservations
  WHERE YEAR(fecha_reserva) = ?
";
$params = [$anio];
$types  = 'i';

if ($estado !== '') {
  $baseSQL .= " AND estado = ? ";
  $types   .= 's';
  $params[] = $estado;
}
$baseSQL .= " GROUP BY facultad_id, MONTH(fecha_reserva)";

$stmt = $conn->prepare($baseSQL);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rs = $stmt->get_result();

while ($row = $rs->fetch_assoc()) {
  $fid = (int)$row['facultad_id'];
  $m   = (int)$row['m'];
  $c   = (int)$row['c'];
  if (isset($tabla[$fid][$m])) {
    $tabla[$fid][$m] += $c;
    $totFila[$fid]   += $c;
    $totMes[$m]      += $c;
  }
}
$granTotal = array_sum($totMes);


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();


$colFin = 'N';


$sheet->setCellValue('A1', 'Universidad Tecnológica de El Salvador');
$sheet->mergeCells("A1:$colFin"."1");
$sheet->getStyle('A1')->getFont()->setBold(true)->setItalic(true)->setSize(18);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'DIRECCIÓN DE EDUCACIÓN VIRTUAL');
$sheet->mergeCells("A2:$colFin"."2");
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A3', 'Estadístico por áreas de toma de videos (Facultades-Administrativos)');
$sheet->mergeCells("A3:$colFin"."3");
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A4', "Año $anio");
$sheet->mergeCells("A4:$colFin"."4");
$sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


$headRow = 6;
$sheet->setCellValue("A$headRow", "Facultad y/otros");
$col = 'B';
for ($m=1;$m<=12;$m++) {
  $sheet->setCellValue($col.$headRow, $meses[$m]);
  $col++;
}
$sheet->setCellValue("N$headRow", "Total");


$sheet->getStyle("A$headRow:$colFin$headRow")->getFont()->setBold(true);
$sheet->getStyle("A$headRow:$colFin$headRow")->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_CENTER)
      ->setVertical(Alignment::VERTICAL_CENTER);


$sheet->getStyle("A$headRow:$colFin$headRow")->getFill()->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFEFEFEF');


$sheet->getStyle("A$headRow:$colFin$headRow")->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

$sheet->getRowDimension($headRow)->setRowHeight(22);


$row = $headRow + 1;
$firstDataRow = $row;
$i = 0;
foreach ($facus as $fid => $nombreF) {
  $sheet->setCellValue("A$row", $nombreF);

  $col = 'B'; $rowTotal = 0;
  for ($m=1;$m<=12;$m++) {
    $val = $tabla[$fid][$m] ?? 0;
    $sheet->setCellValue($col.$row, $val);
    $rowTotal += $val;
    $col++;
  }
  $sheet->setCellValue("N$row", $rowTotal);

 
  $sheet->getStyle("A$row:$colFin$row")->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);


  $sheet->getStyle("B$row:N$row")->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

  
  if ($i % 2 === 1) {
    $sheet->getStyle("A$row:$colFin$row")->getFill()->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setARGB('FFF9F9F9');
  }
  $i++;
  $row++;
}


$sheet->setCellValue("A$row", "Total del mes");
$sheet->getStyle("A$row")->getFont()->setBold(true);

$col = 'B';
for ($m=1;$m<=12;$m++) {
  $sheet->setCellValue($col.$row, $totMes[$m]);
  $col++;
}
$sheet->setCellValue("N$row", $granTotal);

$sheet->getStyle("A$row:$colFin$row")->getFont()->setBold(true);
$sheet->getStyle("A$row:$colFin$row")->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("B$row:N$row")->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_CENTER);


$notaRow = $row + 2; 
$textoTotal = "Total de videos del año $anio: " . number_format($granTotal, 0, '.', ',');
$sheet->setCellValue("A$notaRow", $textoTotal);
$sheet->mergeCells("A$notaRow:$colFin$notaRow");
$sheet->getStyle("A$notaRow")->getFont()->setBold(true)->setSize(12);
$sheet->getStyle("A$notaRow")->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_CENTER);


$sheet->getColumnDimension('A')->setWidth(42);
foreach (range('B','N') as $c) { $sheet->getColumnDimension($c)->setWidth(9); }
$sheet->getStyle("A{$firstDataRow}:A$row")->getAlignment()->setWrapText(true);

$sheet->freezePane('B'.($headRow+1));


$sheet->getPageSetup()
      ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE) 
      ->setPaperSize(PageSetup::PAPERSIZE_LETTER)        
      ->setFitToWidth(1)                                 
      ->setFitToHeight(0);                               

$sheet->getPageMargins()->setTop(0.4);
$sheet->getPageMargins()->setBottom(0.4);
$sheet->getPageMargins()->setLeft(0.3);
$sheet->getPageMargins()->setRight(0.3);


$sheet->getPageSetup()->setHorizontalCentered(true);


$ultimaFilaImprimir = $notaRow;
$sheet->getPageSetup()->setPrintArea("A1:$colFin$ultimaFilaImprimir");


$sheet->getStyle("A$headRow:$colFin$row")->getBorders()->getOutline()
      ->setBorderStyle(Border::BORDER_MEDIUM);

$nombre = "reporte_anual_facultades_$anio".($estado? "_$estado":'').".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$nombre.'"');
$writer = new Xlsx($ss);
$writer->save('php://output');
exit;
