<?php

session_start();

// Verifica se os dados estão disponíveis
if (!isset($_SESSION['fetch_data'])) {
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

$data = $_SESSION['fetch_data'];

$agentes = [];
$totalAtendidas = 0;

$infoRegister = [
    'recebidas' => 0,
    'completadas' => 0,
    'transferidas' => 0,
    'tempo_conversando' => 0,
    'ring_time' => 0,
    'wait_time' => []
];

foreach ($data as $chamadas) {
    $agent = $chamadas['agent'];
    $evento = $chamadas['event'];

    if (!isset($agentes[$agent])) {
        $agentes[$agent] = $infoRegister;
    }

    // Atualiza os dados por agente
    if ($evento == 'COMPLETECALLER' || $evento == 'COMPLETEAGENT') {
        $agentes[$agent]['recebidas']++;
        $agentes[$agent]['completadas']++;
        $agentes[$agent]['tempo_conversando'] += $chamadas['call_time'] ?? 0;
        $agentes[$agent]['ring_time'] += $chamadas['ringtime'] ?? 0;
        $agentes[$agent]['wait_time'][] = $chamadas['wait_time'] ?? 0;

        $totalAtendidas++;

    }

}

foreach ($agentes as $agente => &$metrics) {
    $metrics['tma'] = $metrics['completadas'] > 0 ? gmdate("H:i:s", $metrics['tempo_conversando'] / $metrics['completadas']) : '00:00:00';
    $metrics['tme'] = !empty($metrics['wait_time']) ? gmdate("H:i:s", array_sum($metrics['wait_time']) / count($metrics['wait_time'])) : '00:00:00';
    $metrics['tempo_max_espera'] = !empty($metrics['wait_time']) ? gmdate("H:i:s", max($metrics['wait_time'])) : '00:00:00';
    $metrics['percentual_chamadas'] = round(($metrics['recebidas'] / $totalAtendidas) * 100, 2);
}

// Verifica o formato de exportação
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

if ($format === 'csv') {
    exportCSV($agentes);
} elseif ($format === 'xlsx') {
    exportXLSX($agentes);
} elseif ($format === 'pdf') {
    exportPDF($agentes);
} else {
    echo "Formato de exportação inválido.";
}

// Função para exportar CSV
function exportCSV($agentes)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="relatorio_agentes.csv"');
    $output = fopen('php://output', 'w');

    // Adiciona o BOM para garantir compatibilidade UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalhos
    fputcsv($output, ['Agente', 'Recebidas', 'Completadas', 'TMA', 'TME', 'Tempo Máximo de Espera', 'Percentual Chamadas (%)']);

    // Exporta os dados
    foreach ($agentes as $agente => $metrics) {
        fputcsv($output, [
            $agente,
            $metrics['recebidas'],
            $metrics['completadas'],
            $metrics['tma'],
            $metrics['tme'],
            $metrics['tempo_max_espera'],
            $metrics['percentual_chamadas']
        ]);
    }

    fclose($output);
    exit();
}

// Função para exportar XLSX
function exportXLSX($agentes)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório por Agente');

    // Estilo para os cabeçalhos
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['argb' => Color::COLOR_WHITE],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => '2C1A7D'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => Color::COLOR_BLACK],
            ],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    // Estilo para o conteúdo
    $contentStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => Color::COLOR_BLACK],
            ],
        ],
    ];

    // Cabeçalhos
    $sheet->setCellValue('A1', 'Agente');
    $sheet->setCellValue('B1', 'Recebidas');
    $sheet->setCellValue('C1', 'Completadas');
    $sheet->setCellValue('D1', 'TMA');
    $sheet->setCellValue('E1', 'TME');
    $sheet->setCellValue('F1', 'Tempo Máximo de Espera');
    $sheet->setCellValue('G1', 'Percentual Chamadas (%)');

    // Aplica o estilo aos cabeçalhos
    $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

    // Adiciona os dados
    $row = 2;
    foreach ($agentes as $agente => $metrics) {
        $sheet->setCellValue("A$row", $agente);
        $sheet->setCellValue("B$row", $metrics['recebidas']);
        $sheet->setCellValue("C$row", $metrics['completadas']);
        $sheet->setCellValue("D$row", $metrics['tma']);
        $sheet->setCellValue("E$row", $metrics['tme']);
        $sheet->setCellValue("F$row", $metrics['tempo_max_espera']);
        $sheet->setCellValue("G$row", $metrics['percentual_chamadas'] . '%');

        $sheet->getStyle("A$row:G$row")->applyFromArray($contentStyle);
        $row++;
    }

    // Ajusta largura das colunas automaticamente
    foreach (range('A', 'G') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Envia o arquivo XLSX para download
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_agentes.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();
}

// Função para exportar PDF
function exportPDF($agentes)
{
    $pdf = new FPDF();
    $pdf->AddPage('L');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, utf8_decode('Relatório por Agente'), 0, 1, 'C');
    $pdf->Ln(10);

    // Cabeçalhos centralizados
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 10, 'Agente', 1, 0, 'C');
    $pdf->Cell(35, 10, 'Recebidas', 1, 0, 'C');
    $pdf->Cell(35, 10, 'Completadas', 1, 0, 'C');
    $pdf->Cell(35, 10, 'TMA', 1, 0, 'C');
    $pdf->Cell(35, 10, 'TME', 1, 0, 'C');
    $pdf->Cell(50, 10, 'Tempo Max. Espera', 1, 0, 'C');
    $pdf->Cell(35, 10, 'Percentual (%)', 1, 0, 'C');
    $pdf->Ln();

    // Dados centralizados
    $pdf->SetFont('Arial', '', 10);
    foreach ($agentes as $agente => $metrics) {
        $pdf->Cell(50, 10, utf8_decode($agente), 1, 0, 'C');
        $pdf->Cell(35, 10, $metrics['recebidas'], 1, 0, 'C');
        $pdf->Cell(35, 10, $metrics['completadas'], 1, 0, 'C');
        $pdf->Cell(35, 10, $metrics['tma'], 1, 0, 'C');
        $pdf->Cell(35, 10, $metrics['tme'], 1, 0, 'C');
        $pdf->Cell(50, 10, $metrics['tempo_max_espera'], 1, 0, 'C');
        $pdf->Cell(35, 10, $metrics['percentual_chamadas'] . '%', 1, 0, 'C');
        $pdf->Ln();
    }

    // Envia o PDF para download
    $pdf->Output('D', 'relatorio_agentes.pdf');
    exit();
}
