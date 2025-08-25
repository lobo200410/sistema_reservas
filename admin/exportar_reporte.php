<?php
require_once '../vendor/autoload.php';
require_once '../conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// === Parámetros ===
$anio = isset($_GET['anio']) && preg_match('/^\d{4}$/', $_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

// === Traer datos agregados: conteos por facultad y mes ===
$sql = "
  SELECT COALESCE(f.nombre, 'Otros') AS facultad,
         MONTH(r.fecha_reserva) AS mes,
         COUNT(*) AS cnt
  FROM reservations r
  LEFT JOIN facultades f ON r.facultad_id = f.id
  WHERE YEAR(r.fecha_reserva) = ?
  GROUP BY COALESCE(f.nombre, 'Otros'), MONTH(r.fecha_reserva)
  ORDER BY facultad ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $anio);
$stmt->execute();
$res = $stmt->get_result();

// Construir matriz: facultad -> [1..12] = 0
$matriz = [];
while ($row = $res->fetch_assoc()) {
  $fac = $row['facultad'] ?: 'Otros';
  $mes = (int)$row['mes'];
  $cnt = (int)$row['cnt'];
  if (!isset($matriz[$fac])) $matriz[$fac] = array_fill(1, 12, 0);
  if ($mes >= 1 && $mes <= 12) $matriz[$fac][$mes] = $cnt;
}
$stmt->close();

// Si no hay datos, crear al menos una fila
if (empty($matriz)) {
  $matriz['—'] = array_fill(1, 12, 0);
}

// Ordenar filas por nombre (puedes cambiar a orden personalizado si quieres)
ksort($matriz, SORT_NATURAL | SORT_FLAG_CASE);

// Totales por mes
$totalesMes = array_fill(1, 12, 0);
foreach ($matriz as $fac => $arrMeses) {
  for ($m = 1; $m <= 12; $m++) $totalesMes[$m] += (int)$arrMeses[$m];
}
$granTotal = array_sum($totalesMes);

// Nombres de meses (cortos)
$mesN = [1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Sept.','Oct.','Nov.','Dic.'];

// === Spreadsheet ===
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Estadístico $anio");

// Márgenes/estética rápida
$sheet->getDefaultColumnDimension()->setWidth(10);
$sheet->getColumnDimension('A')->setWidth(38); // Facultad y/otros
$sheet->getColumnDimension('N')->setWidth(10);

// Fila base (iremos sumando)
$row = 1;

// Encabezado superior (títulos)
$sheet->mergeCells("A{$row}:N{$row}");
$sheet->setCellValue("A{$row}", "Universidad Tecnológica de El Salvador");
$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
$row++;

$sheet->mergeCells("A{$row}:N{$row}");
$sheet->setCellValue("A{$row}", "DIRECCIÓN DE EDUCACIÓN VIRTUAL");
$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
$row++;

$sheet->mergeCells("A{$row}:N{$row}");
$sheet->setCellValue("A{$row}", "Estadístico por áreas de toma de videos (Facultades-Administrativos)");
$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A{$row}")->getFont()->setSize(11);
$row++;

$sheet->mergeCells("A{$row}:N{$row}");
$sheet->setCellValue("A{$row}", "Año {$anio}");
$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
$row += 1;

// Encabezado de la tabla
$headRow = $row;
$sheet->setCellValue("A{$headRow}", "Facultad y/otros");
$col = 'B';
for ($m = 1; $m <= 12; $m++, $col++) {
  $sheet->setCellValue("{$col}{$headRow}", $mesN[$m]);
}
$sheet->setCellValue("N{$headRow}", "Total");

// Estilo encabezado
$sheet->getStyle("A{$headRow}:N{$headRow}")->getFont()->setBold(true);
$sheet->getStyle("A{$headRow}:N{$headRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A{$headRow}:N{$headRow}")
      ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');

$row++;

// Cuerpo (filas por facultad)
$startDataRow = $row;
foreach ($matriz as $facultad => $arrMeses) {
  $sheet->setCellValue("A{$row}", $facultad);
  $col = 'B';
  $subtotal = 0;
  for ($m = 1; $m <= 12; $m++, $col++) {
    $val = (int)$arrMeses[$m];
    $sheet->setCellValue("{$col}{$row}", $val);
    $subtotal += $val;
  }
  $sheet->setCellValue("N{$row}", $subtotal);
  $row++;
}
$endDataRow = $row - 1;

// Fila “Total del mes”
$sheet->setCellValue("A{$row}", "Total del mes");
$sheet->getStyle("A{$row}")->getFont()->setBold(true);
$col = 'B';
for ($m = 1; $m <= 12; $m++, $col++) {
  $sheet->setCellValue("{$col}{$row}", (int)$totalesMes[$m]);
  $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
}
$sheet->setCellValue("N{$row}", (int)$granTotal);
$sheet->getStyle("N{$row}")->getFont()->setBold(true);
$row++;

// Texto total anual debajo
$sheet->mergeCells("A{$row}:N{$row}");
$sheet->setCellValue("A{$row}", "Total, de videos del año {$anio}: " . number_format($granTotal, 0, '.', ','));
$sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A{$row}")->getFont()->setBold(true);
$row++;

// Bordes para toda la tabla (encabezado + datos + totales)
$sheet->getStyle("A{$headRow}:N" . ($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Alineaciones
$sheet->getStyle("B{$startDataRow}:N{$endDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("B{$headRow}:N{$headRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("B" . ($endDataRow+1) . ":N" . ($endDataRow+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Ajustes de texto en la primera columna
$sheet->getStyle("A{$startDataRow}:A{$endDataRow}")->getAlignment()->setWrapText(true);
$sheet->getRowDimension($headRow)->setRowHeight(22);

// (Opcional) un poquito de padding visual arriba
$sheet->getRowDimension(1)->setRowHeight(20);

// === Enviar al navegador ===
$filename = "Estadistico_por_areas_{$anio}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
