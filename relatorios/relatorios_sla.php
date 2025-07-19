<?php
session_start();
require 'vendor/autoload.php'; // Inclui as bibliotecas necessárias

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (!isset($_SESSION['fetch_data'])) {
    die(json_encode(['error' => 'Nenhum dado disponível para processar.']));
}

$data = $_SESSION['fetch_data'];

// Define os intervalos de tempo
$intervalos = [15, 30, 45, 60, 75, 90, 105, 120, 135, 150, '150+'];
$nivelServico = [];
$totalChamadas = 0;

// Inicializa os contadores para cada intervalo
foreach ($intervalos as $limite) {
    $nivelServico[$limite] = 0;
}

// Agrupa as chamadas por intervalo
foreach ($data as $chamada) {
    if (isset($chamada['wait_time']) && in_array($chamada['event'], ['COMPLETEAGENT', 'COMPLETECALLER'])) {
        $tempoEspera = $chamada['wait_time'];
        $classificado = false;

        foreach ($intervalos as $limite) {
            if ($limite !== '150+' && $tempoEspera <= $limite) {
                $nivelServico[$limite]++;
                $classificado = true;
                break;
            }
        }

        if (!$classificado) {
            $nivelServico['150+']++;
        }

        $totalChamadas++;
    }
}

$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Verifica o formato e exporta de acordo
if ($format === 'csv') {
    exportCSV($nivelServico, $totalChamadas);
} elseif ($format === 'xlsx') {
    exportXLSX($nivelServico, $totalChamadas);
} elseif ($format === 'pdf') {
    exportPDF($nivelServico, $totalChamadas);
} else {
    echo "Formato de exportação inválido.";
}

// Função para exportar CSV
function exportCSV($nivelServico, $totalChamadas)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="relatorio_nivel_servico.csv"');
    $output = fopen('php://output', 'w');

    // Adiciona o BOM para garantir a compatibilidade UTF-8 com o Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalhos
    fputcsv($output, ['Atendidas em até X segundos', 'Contagem', 'Delta', '% do Total']);

    $contagemCumulativa = 0;
    foreach ($nivelServico as $limite => $delta) {
        $contagemCumulativa += $delta;
        $percentual = $totalChamadas > 0 ? round(($contagemCumulativa / $totalChamadas) * 100, 2) : 0;

        fputcsv($output, [
            $limite === '150+' ? '150+ segundos' : $limite . ' segundos',
            $contagemCumulativa,
            $delta,
            $percentual . '%'
        ]);
    }

    fclose($output);
    exit;
}

// Função para exportar XLSX
function exportXLSX($nivelServico, $totalChamadas)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório Nível de Serviço');

    // Cabeçalhos
    $sheet->setCellValue('A1', 'Atendidas em até X segundos');
    $sheet->setCellValue('B1', 'Contagem');
    $sheet->setCellValue('C1', 'Delta');
    $sheet->setCellValue('D1', '% do Total');

    // Dados
    $row = 2;
    $contagemCumulativa = 0;
    foreach ($nivelServico as $limite => $delta) {
        $contagemCumulativa += $delta;
        $percentual = $totalChamadas > 0 ? round(($contagemCumulativa / $totalChamadas) * 100, 2) : 0;

        $sheet->setCellValue("A$row", $limite === '150+' ? '150+ segundos' : $limite . ' segundos');
        $sheet->setCellValue("B$row", $contagemCumulativa);
        $sheet->setCellValue("C$row", $delta);
        $sheet->setCellValue("D$row", $percentual . '%');

        $row++;
    }

    // Envia para o navegador para download
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_nivel_servico.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}

// Função para exportar PDF
// Função para exportar PDF
function exportPDF($nivelServico, $totalChamadas)
{
    $pdf = new FPDF();
    $pdf->AddPage('L');
    $pdf->SetFont('Arial', 'B', 12);

    // Título centralizado
    $pdf->Cell(0, 10, utf8_decode('Relatório de Nível de Serviço'), 0, 1, 'C');
    $pdf->Ln(5);

    // Largura total da tabela
    $tableWidth = 170; // Soma das larguras de todas as colunas
    $pageWidth = $pdf->GetPageWidth();
    $marginLeft = ($pageWidth - $tableWidth) / 2; // Margem para centralizar a tabela

    // Ajusta a posição inicial da tabela
    $pdf->SetX($marginLeft);

    // Cabeçalhos
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 8, utf8_decode('Atendidas em até X segundos'), 1, 0, 'C');
    $pdf->Cell(40, 8, 'Contagem', 1, 0, 'C');
    $pdf->Cell(40, 8, 'Delta', 1, 0, 'C');
    $pdf->Cell(40, 8, '% do Total', 1, 0, 'C');
    $pdf->Ln();

    // Dados
    $pdf->SetFont('Arial', '', 10);
    $contagemCumulativa = 0;
    foreach ($nivelServico as $limite => $delta) {
        $contagemCumulativa += $delta;
        $percentual = $totalChamadas > 0 ? round(($contagemCumulativa / $totalChamadas) * 100, 2) : 0;

        // Ajusta a posição inicial da tabela para centralização
        $pdf->SetX($marginLeft);

        $pdf->Cell(50, 8, utf8_decode($limite === '150+' ? '150+ segundos' : $limite . ' segundos'), 1, 0, 'C');
        $pdf->Cell(40, 8, $contagemCumulativa, 1, 0, 'C');
        $pdf->Cell(40, 8, '+'. $delta, 1, 0, 'C');
        $pdf->Cell(40, 8, $percentual . '%', 1, 0, 'C');
        $pdf->Ln();
    }

    $pdf->Output('D', 'relatorio_nivel_servico.pdf');
    exit;
}

