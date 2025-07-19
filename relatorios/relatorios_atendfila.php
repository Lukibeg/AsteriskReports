<?php
session_start();

// Verifica se os dados necessários estão disponíveis
if (!isset($_SESSION['fetch_data']) || !isset($_SESSION['friendlyNames'])) {
    echo "Nenhum dado disponível para exportar.";
    exit();
}

require 'vendor/autoload.php'; // Inclui a biblioteca PhpSpreadsheet
require 'vendor/setasign/fpdf/fpdf.php'; // Inclui a biblioteca FPDF
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Recupera os dados
$data = $_SESSION['fetch_data'];
$friendlyNames = $_SESSION['friendlyNames'];

$filas = [];
$totalAtendidas = 0;

// Processa os dados para as filas
foreach ($data as $chamada) {
    $fila = $chamada['queuename'];
    $evento = $chamada['event'];
    $filaAmigavel = isset($friendlyNames[$fila]) ? $friendlyNames[$fila] : $fila;

    if (!isset($filas[$filaAmigavel])) {
        $filas[$filaAmigavel] = [
            'recebidas' => 0,
            'completadas' => 0,
            'tempo_conversando' => 0,
            'ring_time' => 0,
            'wait_time' => 0,
        ];
    }

    // Atualiza os dados por fila
    if ($evento === 'COMPLETECALLER' || $evento === 'COMPLETEAGENT') {
        $filas[$filaAmigavel]['recebidas']++;
        $filas[$filaAmigavel]['completadas']++;
        $filas[$filaAmigavel]['tempo_conversando'] += $chamada['call_time'] ?? 0;
        $filas[$filaAmigavel]['ring_time'] += $chamada['ringtime'] ?? 0;
        $filas[$filaAmigavel]['wait_time'] += $chamada['wait_time'] ?? 0;

        $totalAtendidas++;
    }
}

// Se solicitado JSON, retorna os dados
if (isset($_GET['output']) && $_GET['output'] === 'json') {
    echo json_encode($filas);
    exit();
}

// Função para formatar tempo em hh:mm:ss
function format_time($seconds)
{
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
}

// Verifica o formato de exportação
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
if ($format === 'csv') {
    exportCSV($filas);
} elseif ($format === 'xlsx') {
    exportXLSX($filas);
} elseif ($format === 'pdf') {
    exportPDF($filas);
} else {
    echo "Formato de exportação inválido.";
    exit();
}

// Função para exportar CSV
function exportCSV($filas)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="relatorio_filas.csv"');
    $output = fopen('php://output', 'w');

    // Adiciona o BOM para compatibilidade UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalhos
    fputcsv($output, ['Fila', 'Recebidas', 'Completadas', 'TMA', 'Tempo de toque', 'Tempo de espera']);

    foreach ($filas as $fila => $metrics) {
        fputcsv($output, [
            $fila,
            $metrics['recebidas'],
            $metrics['completadas'],
            format_time($metrics['tempo_conversando'] / ($metrics['completadas'] > 0 ? $metrics['completadas'] : 1)),
            format_time($metrics['ring_time']),
            format_time($metrics['wait_time'])
        ]);
    }

    fclose($output);
    exit();
}

// Função para exportar XLSX
function exportXLSX($filas)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório por Fila');

    // Estilo dos cabeçalhos
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['argb' => Color::COLOR_WHITE],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => '2C1A7D'],
        ],
    ];

    // Cabeçalhos
    $sheet->setCellValue('A1', 'Fila');
    $sheet->setCellValue('B1', 'Recebidas');
    $sheet->setCellValue('C1', 'Completadas');
    $sheet->setCellValue('D1', 'TMA');
    $sheet->setCellValue('E1', 'Tempo de toque');
    $sheet->setCellValue('F1', 'Tempo de espera');
    $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

    // Dados
    $row = 2;
    foreach ($filas as $fila => $metrics) {
        $sheet->setCellValue("A$row", $fila);
        $sheet->setCellValue("B$row", $metrics['recebidas']);
        $sheet->setCellValue("C$row", $metrics['completadas']);
        $sheet->setCellValue("D$row", format_time($metrics['tempo_conversando'] / ($metrics['completadas'] > 0 ? $metrics['completadas'] : 1)));
        $sheet->setCellValue("E$row", format_time($metrics['ring_time']));
        $sheet->setCellValue("F$row", format_time($metrics['wait_time']));
        $row++;
    }

    // Exporta o arquivo XLSX
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_filas.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();
}

// Função para exportar PDF
function exportPDF($filas)
{
    $pdf = new FPDF();
    $pdf->AddPage('L');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, utf8_decode('Relatório de Ligações por Fila'), 0, 1, 'C');
    $pdf->Ln(10);

    // Cabeçalhos
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(45, 10, 'Fila', 1);
    $pdf->Cell(50, 10, 'Recebidas', 1);
    $pdf->Cell(50, 10, 'Completadas', 1);
    $pdf->Cell(50, 10, 'TMA', 1);
    $pdf->Cell(50, 10, 'Tempo de toque', 1);
    $pdf->Cell(35, 10, 'Tempo de espera', 1);
    $pdf->Ln();

    // Dados
    $pdf->SetFont('Arial', '', 10);
    foreach ($filas as $fila => $metrics) {
        $pdf->Cell(45, 10, utf8_decode($fila), 1);
        $pdf->Cell(50, 10, $metrics['recebidas'], 1);
        $pdf->Cell(50, 10, $metrics['completadas'], 1);
        $pdf->Cell(50, 10, format_time($metrics['tempo_conversando'] / ($metrics['completadas'] > 0 ? $metrics['completadas'] : 1)), 1);
        $pdf->Cell(50, 10, format_time($metrics['ring_time']), 1);
        $pdf->Cell(35, 10, format_time($metrics['wait_time']), 1);
        $pdf->Ln();
    }

    // Envia o PDF para download
    $pdf->Output('D', 'relatorio_filas.pdf');
    exit();
}
