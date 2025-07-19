<?php
require 'utils/db_connect.php';
require 'vendor/autoload.php'; // Carrega dependências do Composer (PhpSpreadsheet e FPDF)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Verifica se os dados estão disponíveis
if (!isset($_SESSION['outboundMetrics']) || empty($_SESSION['outboundMetrics'])) {
    die('Nenhum dado disponível para exportação.');
}

$outboundMetrics = $_SESSION['outboundMetrics'];
$format = $_GET['format'] ?? 'xlsx';

switch ($format) {
    case 'xlsx':
        exportToXLSX($outboundMetrics);
        break;
    case 'csv':
        exportToCSV($outboundMetrics);
        break;
    case 'pdf':
        exportToPDF($outboundMetrics);
        break;
    default:
        die('Formato de exportação inválido.');
}

// Função para exportar informações gerais em XLSX
function exportToXLSX($metrics)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Estilos de cabeçalho
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '2C1A7D']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    // Escreve os cabeçalhos
    $sheet->setCellValue('A1', 'Fila');
    $sheet->setCellValue('B1', 'Recebidas');
    $sheet->setCellValue('C1', 'Atendidas');
    $sheet->setCellValue('D1', 'Abandonadas');
    $sheet->setCellValue('E1', 'Transferidas');
    $sheet->setCellValue('F1', 'TME');
    $sheet->setCellValue('G1', 'TMA');
    $sheet->setCellValue('H1', '% Atendidas');
    $sheet->setCellValue('I1', '% Não Atendidas');
    $sheet->setCellValue('J1', 'SLA');

    $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

    // Escreve os dados agregados
    $sheet->setCellValue('A2', 'Ligações Efetuadas');
    $sheet->setCellValue('B2', $metrics['total_ligacoes_recebidas']);
    $sheet->setCellValue('C2', $metrics['total_ligacoes_atendidas']);
    $sheet->setCellValue('D2', $metrics['total_ligacoes_abandonadas']);
    $sheet->setCellValue('E2', $metrics['total_ligacoes_transferidas']);
    $sheet->setCellValue('F2', format_time($metrics['tme']));
    $sheet->setCellValue('G2', format_time($metrics['tmc']));
    $sheet->setCellValue('H2', round($metrics['percentual_atendidas'], 2) . '%');
    $sheet->setCellValue('I2', round($metrics['percentual_nao_atendidas'], 2) . '%');
    $sheet->setCellValue('J2', round($metrics['sla'], 2) . '%');

    // Ajusta largura automática
    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Gera o arquivo
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_saida.xlsx"');
    $writer->save('php://output');
    exit;
}

// Função para exportar informações gerais em CSV
function exportToCSV($metrics)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_saida.csv"');
    $output = fopen('php://output', 'w');

    // Cabeçalhos
    fputcsv($output, ['Fila', 'Recebidas', 'Atendidas', 'Abandonadas', 'Transferidas', 'TME', 'TMA', '% Atendidas', '% Não Atendidas', 'SLA']);

    // Dados
    fputcsv($output, [
        'Ligações Efetuadas',
        $metrics['total_ligacoes_recebidas'],
        $metrics['total_ligacoes_atendidas'],
        $metrics['total_ligacoes_abandonadas'],
        $metrics['total_ligacoes_transferidas'],
        format_time($metrics['tme']),
        format_time($metrics['tmc']),
        round($metrics['percentual_atendidas'], 2) . '%',
        round($metrics['percentual_nao_atendidas'], 2) . '%',
        round($metrics['sla'], 2) . '%'
    ]);

    fclose($output);
    exit;
}

// Função para exportar informações gerais em PDF
function exportToPDF($metrics)
{
    require 'vendor/setasign/fpdf/fpdf.php'; // Biblioteca FPDF

    $pdf = new FPDF();
    $pdf->AddPage('L'); // Orientação Paisagem
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, utf8_decode('Relatório de Ligações Efetuadas'), 0, 1, 'C');
    $pdf->Ln(5);

    // Cabeçalhos
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(30, 8, utf8_decode('Fila'), 1);
    $pdf->Cell(25, 8, 'Recebidas', 1);
    $pdf->Cell(25, 8, 'Atendidas', 1);
    $pdf->Cell(25, 8, 'Abandonadas', 1);
    $pdf->Cell(25, 8, 'Transferidas', 1);
    $pdf->Cell(25, 8, 'TME', 1);
    $pdf->Cell(25, 8, 'TMA', 1);
    $pdf->Cell(25, 8, '% Atendidas', 1);
    $pdf->Cell(25, 8, utf8_decode('% Não Atendidas'), 1);
    $pdf->Cell(25, 8, 'SLA', 1);
    $pdf->Ln();

    // Dados
    $pdf->SetFont('Arial', '', 9);

    // Prepara os valores
    $tme = format_time($metrics['tme']);
    $tmc = format_time($metrics['tmc']);
    $percentAtendidas = round($metrics['percentual_atendidas'], 2) . '%';
    $percentNaoAtendidas = round($metrics['percentual_nao_atendidas'], 2) . '%';
    $sla = round($metrics['sla'], 2) . '%';

    // Linha com os dados
    $pdf->Cell(30, 8, utf8_decode('Ligações Efetuadas'), 1);
    $pdf->Cell(25, 8, $metrics['total_ligacoes_recebidas'], 1);
    $pdf->Cell(25, 8, $metrics['total_ligacoes_atendidas'], 1);
    $pdf->Cell(25, 8, $metrics['total_ligacoes_abandonadas'], 1);
    $pdf->Cell(25, 8, $metrics['total_ligacoes_transferidas'], 1);
    $pdf->Cell(25, 8, $tme, 1);
    $pdf->Cell(25, 8, $tmc, 1);
    $pdf->Cell(25, 8, $percentAtendidas, 1);
    $pdf->Cell(25, 8, $percentNaoAtendidas, 1);
    $pdf->Cell(25, 8, $sla, 1);
    $pdf->Ln();

    // Gera o PDF para download
    $pdf->Output('D', 'relatorio_ligacoes_saida.pdf');
    exit;
}

// Função auxiliar para formatar tempo (segundos -> HH:MM:SS)
function format_time($seconds)
{
    return gmdate('H:i:s', intval($seconds));
}
