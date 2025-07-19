<?php
require 'utils/db_connect.php';
require 'vendor/autoload.php';
require_once 'listqueues.php';
session_start();

if (!isset($_SESSION['dadosPorFila'])) {
    echo "Nenhum dado disponível para exportar.";
    exit();
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$dadosPorFila = $_SESSION['dadosPorFila'];
!empty($_SESSION['filas']) ? $queues = $_SESSION['filas'] : '';

$friendlyNames = [];

// Associa o número da fila ao nome amigável ou mantém o número se não existir um nome amigável.
foreach ($queues as $queueNumber) {
    $friendlyNames[$queueNumber] = isset($qnames[$queueNumber]) ? $qnames[$queueNumber] : $queueNumber;
}

$_SESSION['friendlyNames'] = $friendlyNames;

// Função que converte tempo em segundos para hh:mm:ss
function format_time($seconds)
{
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
}

$format = isset($_GET['format']) ? $_GET['format'] : 'xlsx';

if ($format === 'xlsx') {
    exportXLSX($dadosPorFila, $friendlyNames);
} elseif ($format === 'csv') {
    exportCSV($dadosPorFila, $friendlyNames);
} elseif ($format === 'pdf') {
    exportPDF($dadosPorFila, $friendlyNames);
} else {
    echo "Formato de exportação inválido.";
}

function exportXLSX($dadosPorFila, $friendlyNames) {
    // Cria uma nova planilha
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

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

    // Escreve os cabeçalhos
    $sheet->setCellValue('A1', 'Fila');
    $sheet->setCellValue('B1', 'Recebidas');
    $sheet->setCellValue('C1', 'Atendidas');
    $sheet->setCellValue('D1', 'Abandonadas');
    $sheet->setCellValue('E1', 'Transferidas');
    $sheet->setCellValue('F1', 'TME');
    $sheet->setCellValue('G1', 'TMC');
    $sheet->setCellValue('H1', '% Atendidas');
    $sheet->setCellValue('I1', '% Não Atendidas');
    $sheet->setCellValue('J1', 'SLA');

    // Aplica o estilo aos cabeçalhos
    $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

    // Escreve os dados na planilha
    $rowIndex = 2; // Começa na segunda linha
    foreach ($dadosPorFila as $fila => $metrics) {
        $totalRecebidas = $metrics['total_ligacoes_recebidas'];
        $totalAtendidas = $metrics['total_ligacoes_atendidas'];
        $totalAbandonadas = $metrics['total_ligacoes_abandonadas'];
        $totalTransferidas = $metrics['total_ligacoes_transferidas'];
        $tme = $metrics['tme'];
        $tmc = $metrics['tmc'];
        $percentAtendidas = $metrics['percentual_atendidas'];
        $percentNaoAtendidas = $metrics['percentual_nao_atendidas'];
        $sla = $metrics['sla'];

        // Escreve os dados nas células
        $sheet->setCellValue('A' . $rowIndex, isset($friendlyNames[$fila]) ? $friendlyNames[$fila] : $fila);
        $sheet->setCellValue('B' . $rowIndex, $totalRecebidas);
        $sheet->setCellValue('C' . $rowIndex, $totalAtendidas);
        $sheet->setCellValue('D' . $rowIndex, $totalAbandonadas);
        $sheet->setCellValue('E' . $rowIndex, $totalTransferidas);
        $sheet->setCellValue('F' . $rowIndex, $tme);
        $sheet->setCellValue('G' . $rowIndex, $tmc);
        $sheet->setCellValue('H' . $rowIndex, $percentAtendidas . '%');
        $sheet->setCellValue('I' . $rowIndex, $percentNaoAtendidas . '%');
        $sheet->setCellValue('J' . $rowIndex, $sla . '%');

        $sheet->getStyle('A' . $rowIndex . ':J' . $rowIndex)->applyFromArray($contentStyle);
        $rowIndex++;
    }

    // Ajustar a largura das colunas automaticamente
    foreach (range('A', 'J') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Envia o arquivo XLSX para download
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_filas.xlsx"');
    header('Cache-Control: max-age=0');

    $writer->save('php://output');
    exit();
}

function exportCSV($dadosPorFila, $friendlyNames) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_filas.csv"');
    $output = fopen('php://output', 'w');

    // Adiciona o BOM para compatibilidade UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalhos
    fputcsv($output, ['Fila', 'Recebidas', 'Atendidas', 'Abandonadas', 'Transferidas', 'TME', 'TMC', '% Atendidas', '% Não Atendidas', 'SLA']);

    // Escreve os dados no CSV
    foreach ($dadosPorFila as $fila => $metrics) {
        $totalRecebidas = $metrics['total_ligacoes_recebidas'];
        $totalAtendidas = $metrics['total_ligacoes_atendidas'];
        $totalAbandonadas = $metrics['total_ligacoes_abandonadas'];
        $totalTransferidas = $metrics['total_ligacoes_transferidas'];
        $tme = $metrics['tme'];
        $tmc = $metrics['tmc'];
        $percentAtendidas = $metrics['percentual_atendidas'];
        $percentNaoAtendidas = $metrics['percentual_nao_atendidas'];
        $sla = $metrics['sla'];

        fputcsv($output, [
            isset($friendlyNames[$fila]) ? $friendlyNames[$fila] : $fila,
            $totalRecebidas,
            $totalAtendidas,
            $totalAbandonadas,
            $totalTransferidas,
            $tme,
            $tmc,
            $percentAtendidas . '%',
            $percentNaoAtendidas . '%',
            $sla . '%'
        ]);
    }

    fclose($output);
    exit();
}

function exportPDF($dadosPorFila, $friendlyNames) {
    require 'vendor/setasign/fpdf/fpdf.php';

    $pdf = new FPDF();
    $pdf->AddPage('L');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, utf8_decode('Relatorio de Ligações por Fila'), 0, 1, 'C');
    $pdf->Ln(10);

    // Cabeçalhos
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(27, 10, 'Fila', 1);
    $pdf->Cell(27, 10, 'Recebidas', 1);
    $pdf->Cell(27, 10, 'Atendidas', 1);
    $pdf->Cell(27, 10, 'Abandonadas', 1);
    $pdf->Cell(27, 10, 'Transferidas', 1);
    $pdf->Cell(27, 10, 'TME', 1);
    $pdf->Cell(27, 10, 'TMC', 1);
    $pdf->Cell(27, 10, '% Atendidas', 1);
    $pdf->Cell(30, 10, '% Nao Atendidas', 1);
    $pdf->Cell(27, 10, 'SLA', 1);
    $pdf->Ln();

    // Dados
    $pdf->SetFont('Arial', '', 10);
    foreach ($dadosPorFila as $fila => $metrics) {
        $totalRecebidas = $metrics['total_ligacoes_recebidas'];
        $totalAtendidas = $metrics['total_ligacoes_atendidas'];
        $totalAbandonadas = $metrics['total_ligacoes_abandonadas'];
        $totalTransferidas = $metrics['total_ligacoes_transferidas'];
        $tme = $metrics['tme'];
        $tmc = $metrics['tmc'];
        $percentAtendidas = $metrics['percentual_atendidas'];
        $percentNaoAtendidas = $metrics['percentual_nao_atendidas'];
        $sla = $metrics['sla'];

        // Exportar dados no PDF
        $pdf->Cell(27, 10, isset($friendlyNames[$fila]) ? $friendlyNames[$fila] : $fila, 1);
        $pdf->Cell(27, 10, $totalRecebidas, 1);
        $pdf->Cell(27, 10, $totalAtendidas, 1);
        $pdf->Cell(27, 10, $totalAbandonadas, 1);
        $pdf->Cell(27, 10, $totalTransferidas, 1);
        $pdf->Cell(27, 10, $tme, 1);
        $pdf->Cell(27, 10, $tmc, 1);
        $pdf->Cell(27, 10, $percentAtendidas . '%', 1);
        $pdf->Cell(30, 10, $percentNaoAtendidas . '%', 1);
        $pdf->Cell(27, 10, $sla . '%', 1);
        $pdf->Ln();
    }

    // Enviar o PDF para download
    $pdf->Output('D', 'relatorio_ligacoes_filas.pdf');
    exit();
}
