<?php
require 'utils/db_connect.php';
require 'vendor/autoload.php';
session_start();

if (!isset($_SESSION['dadosPorHora'])) {
    echo "Nenhum dado disponível para exportar.";
    exit();
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$dadosPorHora = $_SESSION['dadosPorHora'];

// Função que converte tempo em segundos para hh:mm:ss
function format_time($seconds)
{
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
}

$format = isset($_GET['format']) ? $_GET['format'] : 'xlsx';

if ($format === 'xlsx') {
    exportXLSX($dadosPorHora);
} elseif ($format === 'csv') {
    exportCSV($dadosPorHora);
} elseif ($format === 'pdf') {
    exportPDF($dadosPorHora);
} else {
    echo "Formato de exportação inválido.";
}

function exportXLSX($dadosPorHora)
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
    $sheet->setCellValue('A1', 'Hora');
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
    foreach ($dadosPorHora as $hora => $metrics) {
        $sheet->setCellValue('A' . $rowIndex, $hora);
        $sheet->setCellValue('B' . $rowIndex, $metrics['total_ligacoes_recebidas']);
        $sheet->setCellValue('C' . $rowIndex, $metrics['total_ligacoes_atendidas']);
        $sheet->setCellValue('D' . $rowIndex, $metrics['total_ligacoes_abandonadas']);
        $sheet->setCellValue('E' . $rowIndex, $metrics['total_ligacoes_transferidas']);
        $sheet->setCellValue('F' . $rowIndex, format_time($metrics['tme']));
        $sheet->setCellValue('G' . $rowIndex, format_time($metrics['tma']));
        $sheet->setCellValue('H' . $rowIndex, round($metrics['percentual_atendidas'], 2) . '%');
        $sheet->setCellValue('I' . $rowIndex, round($metrics['percentual_nao_atendidas'], 2) . '%');
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
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_hora.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();
}

function exportCSV($dadosPorHora)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_hora.csv"');
    $output = fopen('php://output', 'w');

    // Adiciona o BOM para compatibilidade UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalhos
    fputcsv($output, ['Hora', 'Recebidas', 'Atendidas', 'Abandonadas', 'Transferidas', 'TME', 'TMA', '% Atendidas', '% Não Atendidas', 'SLA']);

    // Escreve os dados no CSV
    foreach ($dadosPorHora as $hora => $metrics) {

        fputcsv($output, [
            $hora,
            $metrics['total_ligacoes_recebidas'],
            $metrics['total_ligacoes_atendidas'],
            $metrics['total_ligacoes_abandonadas'],
            $metrics['total_ligacoes_transferidas'],
            format_time($metrics['tme']),
            format_time($metrics['tma']),
            round($metrics['percentual_atendidas'], 2) . '%',
            round($metrics['percentual_nao_atendidas'], 2) . '%',
            round($metrics['sla'], 2) . '%'
        ]);
    }

    fclose($output);
    exit();
}

function exportPDF($dadosPorHora)
{
    require 'vendor/setasign/fpdf/fpdf.php';

    $pdf = new FPDF();
    $pdf->AddPage('L'); // Gira o PDF para paisagem
    $pdf->SetFont('Arial', 'B', 12); // Fonte menor para caber os dados
    // Título
    $pdf->Cell(0, 10, utf8_decode('Relatório de Ligações por Hora'), 0, 1, 'C');
    $pdf->Ln(5);

    // Cabeçalhos
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(30, 8, utf8_decode('Hora'), 1);
    $pdf->Cell(30, 8, utf8_decode('Recebidas'), 1);
    $pdf->Cell(30, 8, utf8_decode('Atendidas'), 1);
    $pdf->Cell(30, 8, utf8_decode('Abandonadas'), 1);
    $pdf->Cell(30, 8, utf8_decode('TME'), 1);
    $pdf->Cell(30, 8, utf8_decode('TMA'), 1);
    $pdf->Cell(30, 8, utf8_decode('% Atendidas'), 1);
    $pdf->Cell(30, 8, utf8_decode('% Não Atendidas'), 1);
    $pdf->Cell(30, 8, utf8_decode('SLA'), 1);
    $pdf->Ln();

    // Dados
    $pdf->SetFont('Arial', '', 10);
    foreach ($dadosPorHora as $hora => $metrics) {
        $pdf->Cell(30, 8, utf8_decode($hora), 1);
        $pdf->Cell(30, 8, $metrics['total_ligacoes_recebidas'], 1);
        $pdf->Cell(30, 8, $metrics['total_ligacoes_atendidas'], 1);
        $pdf->Cell(30, 8, $metrics['total_ligacoes_abandonadas'], 1);
        $pdf->Cell(30, 8, utf8_decode(format_time($metrics['total_tme'])), 1);
        $pdf->Cell(30, 8, utf8_decode(format_time($metrics['total_tma'])), 1);
        $pdf->Cell(30, 8, utf8_decode(number_format($metrics['percentual_atendidas'], 2) . '%'), 1);
        $pdf->Cell(30, 8, utf8_decode(number_format($metrics['percentual_nao_atendidas'], 2) . '%'), 1);
        $pdf->Cell(30, 8, utf8_decode(number_format($metrics['sla'], 2) . '%'), 1);
        $pdf->Ln();
    }

    // Saída do arquivo PDF
    $pdf->Output('D', 'relatorio_ligacoes_hora.pdf');
    exit();
}

?>