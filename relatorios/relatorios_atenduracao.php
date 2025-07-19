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

// Recupera os dados
$data = $_SESSION['fetch_data'];

// Define os intervalos
$intervalos = [15, 30, 45, 60, 75, 90, 105, 120, 135, '150+'];
$agrupadas = [];

// Inicializa os dados
foreach ($intervalos as $limite) {
    $agrupadas[$limite] = [
        'completadas' => 0,
        'tempo_conversando' => 0
    ];
}

// Processa os dados
foreach ($data as $chamada) {
    if (isset($chamada['call_time']) && in_array($chamada['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
        $duracao = $chamada['call_time'];
        $intervaloSelecionado = '150+';

        foreach ($intervalos as $limite) {
            if ($limite !== '150+' && $duracao <= $limite) {
                $intervaloSelecionado = $limite;
                break;
            }
        }

        $agrupadas[$intervaloSelecionado]['completadas']++;
        $agrupadas[$intervaloSelecionado]['tempo_conversando'] += $duracao;
    }
}

// Função para formatar tempo em hh:mm:ss
function format_time($seconds) {
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
}

// Verifica o formato de exportação
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

if ($format === 'csv') {
    exportCSV($agrupadas);
} elseif ($format === 'xlsx') {
    exportXLSX($agrupadas);
} elseif ($format === 'pdf') {
    exportPDF($agrupadas);
} else {
    echo "Formato de exportação inválido.";
}

// Função para exportar em CSV
function exportCSV($dados) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="relatorio_duracao.csv"');
    $output = fopen('php://output', 'w');

    // Cabeçalhos
    fputcsv($output, ['Intervalo de Tempo', 'Chamadas Completadas', 'Tempo Total de Conversa']);

    foreach ($dados as $intervalo => $metrics) {
        fputcsv($output, [
            $intervalo,
            $metrics['completadas'],
            format_time($metrics['tempo_conversando'])
        ]);
    }

    fclose($output);
    exit();
}

// Função para exportar em XLSX
function exportXLSX($dados) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório de Duração');

    // Cabeçalhos
    $sheet->setCellValue('A1', 'Intervalo de Tempo');
    $sheet->setCellValue('B1', 'Chamadas Completadas');
    $sheet->setCellValue('C1', 'Tempo Total de Conversa');

    // Adiciona os dados
    $row = 2;
    foreach ($dados as $intervalo => $metrics) {
        $sheet->setCellValue("A$row", $intervalo);
        $sheet->setCellValue("B$row", $metrics['completadas']);
        $sheet->setCellValue("C$row", format_time($metrics['tempo_conversando']));
        $row++;
    }

    foreach (range('A', 'C') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_duracao.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// Função para exportar em PDF
function exportPDF($dados) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, utf8_decode('Relatório de Duração das Chamadas'), 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(60, 10, 'Intervalo de Tempo', 1);
    $pdf->Cell(60, 10, 'Chamadas Completadas', 1);
    $pdf->Cell(60, 10, 'Tempo Total de Conversa', 1);
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 10);
    foreach ($dados as $intervalo => $metrics) {
        $pdf->Cell(60, 10, $intervalo, 1);
        $pdf->Cell(60, 10, $metrics['completadas'], 1);
        $pdf->Cell(60, 10, format_time($metrics['tempo_conversando']), 1);
        $pdf->Ln();
    }

    $pdf->Output('D', 'relatorio_duracao.pdf');
    exit();
}
?>