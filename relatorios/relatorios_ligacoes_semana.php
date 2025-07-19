<?php
require 'utils/db_connect.php';
require 'vendor/autoload.php';
session_start();

if (!isset($_SESSION['dadosPorSemana'])) {
    echo "Nenhum dado disponível para exportar.";
    exit();
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$dadosPorSemana = $_SESSION['dadosPorSemana'];

// Função para converter segundos em formato hh:mm:ss
function format_time($seconds)
{
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
}

$format = isset($_GET['format']) ? $_GET['format'] : 'xlsx';

if ($format === 'xlsx') {
    exportXLSX($dadosPorSemana);
} elseif ($format === 'csv') {
    exportCSV($dadosPorSemana);
} elseif ($format === 'pdf') {
    exportPDF($dadosPorSemana);
} else {
    echo "Formato de exportação inválido.";
}

// Função para exportar em XLSX
function exportXLSX($dadosPorSemana)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Estilo dos cabeçalhos
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '2C1A7D']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => Color::COLOR_BLACK]]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ];

    // Escrevendo cabeçalhos
    $sheet->setCellValue('A1', 'Semana');
    $sheet->setCellValue('B1', 'Recebidas');
    $sheet->setCellValue('C1', 'Atendidas');
    $sheet->setCellValue('D1', 'Abandonadas');
    $sheet->setCellValue('E1', 'Transferidas');
    $sheet->setCellValue('F1', 'TME');
    $sheet->setCellValue('G1', 'TMA');
    $sheet->setCellValue('H1', 'Max. Ligações');
    $sheet->setCellValue('I1', '% Atendidas');
    $sheet->setCellValue('J1', '% Não Atendidas');
    $sheet->setCellValue('K1', 'SLA');

    $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

    // Escrevendo os dados
    $rowIndex = 2;
    foreach ($dadosPorSemana as $weekYear => $metrics) {
        $sheet->setCellValue('A' . $rowIndex, $weekYear);
        $sheet->setCellValue('B' . $rowIndex, $metrics['recebidas']);
        $sheet->setCellValue('C' . $rowIndex, $metrics['atendidas']);
        $sheet->setCellValue('D' . $rowIndex, $metrics['abandonadas']);
        $sheet->setCellValue('E' . $rowIndex, $metrics['transferidas']);
        $sheet->setCellValue('F' . $rowIndex, format_time($metrics['tme']));
        $sheet->setCellValue('G' . $rowIndex, format_time($metrics['tma']));
        $sheet->setCellValue('H' . $rowIndex, $metrics['max_calls']);
        $sheet->setCellValue('I' . $rowIndex, number_format($metrics['percent_atendidas'], 2) . '%');
        $sheet->setCellValue('J' . $rowIndex, number_format($metrics['percent_nao_atendidas'], 2) . '%');
        $sheet->setCellValue('K' . $rowIndex, number_format($metrics['sla'], 2) . '%');
        $rowIndex++;
    }

    // Ajustando largura das colunas
    foreach (range('A', 'K') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Enviando arquivo para download
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_semana.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();
}

// Função para exportar em CSV
function exportCSV($dadosPorSemana)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_semana.csv"');
    $output = fopen('php://output', 'w');

    // Adiciona o BOM para compatibilidade UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalhos
    fputcsv($output, ['Semana', 'Recebidas', 'Atendidas', 'Abandonadas', 'Transferidas', 'TME', 'TMA', 'Max. Ligações', '% Atendidas', '% Não Atendidas', 'SLA']);

    // Dados
    foreach ($dadosPorSemana as $weekYear => $metrics) {
        fputcsv($output, [
            $weekYear,
            $metrics['recebidas'],
            $metrics['atendidas'],
            $metrics['abandonadas'],
            $metrics['transferidas'],
            format_time($metrics['tme']),
            format_time($metrics['tma']),
            $metrics['max_calls'],
            number_format($metrics['percent_atendidas'], 2) . '%',
            number_format($metrics['percent_nao_atendidas'], 2) . '%',
            number_format($metrics['sla'], 2) . '%'
        ]);
    }

    fclose($output);
    exit();
}


// Função para exportar em PDF
function exportPDF($dadosPorSemana)
{
    require 'vendor/setasign/fpdf/fpdf.php';

    $pdf = new FPDF();
    $pdf->AddPage('L'); // Gira o PDF para paisagem
    $pdf->SetFont('Arial', 'B', 12); // Fonte menor para caber os dados
    // Título
    $pdf->Cell(0, 10, utf8_decode('Relatório de Ligações por Semana'), 0, 1, 'C');
    $pdf->Ln(5);

    // Cabeçalhos
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(25, 8, 'Semana', 1);
    $pdf->Cell(25, 8, 'Recebidas', 1);
    $pdf->Cell(25, 8, 'Atendidas', 1);
    $pdf->Cell(25, 8, 'Abandonadas', 1);
    $pdf->Cell(25, 8, 'Transferidas', 1);
    $pdf->Cell(25, 8, 'TME', 1);
    $pdf->Cell(25, 8, 'TMA', 1);
    $pdf->Cell(25, 8, utf8_decode('Max. Ligações'), 1);
    $pdf->Cell(25, 8, '% Atendidas', 1);
    $pdf->Cell(30, 8, utf8_decode('% Não Atendidas'), 1);
    $pdf->Cell(25, 8, 'SLA', 1);
    $pdf->Ln();

    // Dados
    $pdf->SetFont('Arial', '', 10);
    foreach ($dadosPorSemana as $weekYear => $metrics) {
        $pdf->Cell(25, 8, $weekYear, 1);
        $pdf->Cell(25, 8, $metrics['recebidas'], 1);
        $pdf->Cell(25, 8, $metrics['atendidas'], 1);
        $pdf->Cell(25, 8, $metrics['abandonadas'], 1);
        $pdf->Cell(25, 8, $metrics['transferidas'], 1);
        $pdf->Cell(25, 8, format_time($metrics['tme']), 1);
        $pdf->Cell(25, 8, format_time($metrics['tma']), 1);
        $pdf->Cell(25, 8, $metrics['max_calls'], 1);
        $pdf->Cell(25, 8, number_format($metrics['percent_atendidas'], 2) . '%', 1);
        $pdf->Cell(30, 8, number_format($metrics['percent_nao_atendidas'], 2) . '%', 1);
        $pdf->Cell(25, 8, number_format($metrics['sla'], 2) . '%', 1);
        $pdf->Ln();
    }

    $pdf->Output('D', 'relatorio_ligacoes_semana.pdf');
    exit();
}
