<?php
require 'utils/db_connect.php';
require 'vendor/autoload.php';
session_start();

if (!isset($_SESSION['dadosPorDia'])) {
    echo "Nenhum dado disponível para exportar.";
    exit();
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$dadosPorDia = $_SESSION['dadosPorDia'];

// Função que converte tempo em segundos para hh:mm:ss
function format_time($seconds)
{
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
}

$format = isset($_GET['format']) ? $_GET['format'] : 'xlsx';

if ($format === 'xlsx') {
    exportXLSX($dadosPorDia);
} elseif ($format === 'csv') {
    exportCSV($dadosPorDia);
} elseif ($format === 'pdf') {
    exportPDF($dadosPorDia);
} else {
    echo "Formato de exportação inválido.";
}

function exportXLSX($dadosPorDia)
{
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

    // Escreve os cabeçalhos
    $sheet->setCellValue('A1', 'Data');
    $sheet->setCellValue('B1', 'Recebidas');
    $sheet->setCellValue('C1', 'Atendidas');
    $sheet->setCellValue('D1', 'Abandonadas');
    $sheet->setCellValue('E1', 'Transferidas');
    $sheet->setCellValue('F1', 'TME');
    $sheet->setCellValue('G1', 'TMA');
    $sheet->setCellValue('H1', '% Atendidas');
    $sheet->setCellValue('I1', '% Não Atendidas');
    $sheet->setCellValue('J1', 'SLA');

    // Aplica o estilo aos cabeçalhos
    $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

    // Escreve os dados na planilha
    $rowIndex = 2; // Começa na segunda linha
    foreach ($dadosPorDia as $data => $metrics) {
        $sheet->setCellValue('A' . $rowIndex, $data);
        $sheet->setCellValue('B' . $rowIndex, $metrics['recebidas']);
        $sheet->setCellValue('C' . $rowIndex, $metrics['atendidas']);
        $sheet->setCellValue('D' . $rowIndex, $metrics['abandonadas']);
        $sheet->setCellValue('E' . $rowIndex, $metrics['transferidas']);
        $sheet->setCellValue('F' . $rowIndex, format_time($metrics['tme']));
        $sheet->setCellValue('G' . $rowIndex, format_time($metrics['tma']));
        $sheet->setCellValue('H' . $rowIndex, round($metrics['percent_atendidas'], 2) . '%');
        $sheet->setCellValue('I' . $rowIndex, round($metrics['percent_nao_atendidas'], 2) . '%');
        $sheet->setCellValue('J' . $rowIndex, round($metrics['sla'], 2) . '%');
        $rowIndex++;
    }

    // Ajustar a largura das colunas automaticamente
    foreach (range('A', 'J') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Envia o arquivo XLSX para download
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_dia.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();
}

function exportCSV($dadosPorDia)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_dia.csv"');
    $output = fopen('php://output', 'w');

    // Adiciona o BOM para compatibilidade UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalhos
    fputcsv($output, ['Data', 'Recebidas', 'Atendidas', 'Abandonadas', 'Transferidas', 'TME', 'TMA', '% Atendidas', '% Não Atendidas', 'SLA']);

    // Escreve os dados no CSV
    foreach ($dadosPorDia as $data => $metrics) {
        fputcsv($output, [
            $data,
            $metrics['recebidas'],
            $metrics['atendidas'],
            $metrics['abandonadas'],
            $metrics['transferidas'],
            format_time($metrics['tme']),
            format_time($metrics['tma']),
            round($metrics['percent_atendidas'], 2) . '%',
            round($metrics['percent_nao_atendidas'], 2) . '%',
            round($metrics['sla'], 2) . '%'
        ]);
    }

    fclose($output);
    exit();
}

function exportPDF($dadosPorDia)
{
    require 'vendor/setasign/fpdf/fpdf.php';

    $pdf = new FPDF();
    $pdf->AddPage('L'); // Gira o PDF para paisagem
    $pdf->SetFont('Arial', 'B', 12); // Fonte menor para caber os dados
    $pdf->Cell(0, 10, utf8_decode('Relatório de Ligações por Dia'), 0, 1, 'C');
    $pdf->Ln(5);

    // Cabeçalhos
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(27, 8, 'Data', 1);
    $pdf->Cell(27, 8, 'Recebidas', 1);
    $pdf->Cell(27, 8, 'Atendidas', 1);
    $pdf->Cell(27, 8, 'Abandonadas', 1);
    $pdf->Cell(27, 8, 'Transferidas', 1);
    $pdf->Cell(27, 8, 'TME', 1);
    $pdf->Cell(27, 8, 'TMA', 1);
    $pdf->Cell(27, 8, '% Atendidas', 1);
    $pdf->Cell(28, 8, utf8_decode('% Não Atendidas'), 1);
    $pdf->Cell(27, 8, 'SLA', 1);
    $pdf->Ln();

    // Dados
    $pdf->SetFont('Arial', '', 10);
    foreach ($dadosPorDia as $data => $metrics) {
        $pdf->Cell(27, 10, $data, 1);
        $pdf->Cell(27, 10, $metrics['recebidas'], 1);
        $pdf->Cell(27, 10, $metrics['atendidas'], 1);
        $pdf->Cell(27, 10, $metrics['abandonadas'], 1);
        $pdf->Cell(27, 10, $metrics['transferidas'], 1);
        $pdf->Cell(27, 10, format_time($metrics['tme']), 1);
        $pdf->Cell(27, 10, format_time($metrics['tma']), 1);
        $pdf->Cell(27, 10, round($metrics['percent_atendidas'], 2) . '%', 1);
        $pdf->Cell(28, 10, round($metrics['percent_nao_atendidas'], 2) . '%', 1);
        $pdf->Cell(27, 10, round($metrics['sla'], 2) . '%', 1);
        $pdf->Ln();
    }

    // Enviar o PDF para download
    $pdf->Output('D', 'relatorio_ligacoes_dia.pdf');
    exit();
}