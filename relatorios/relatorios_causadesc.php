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

$causas = ['COMPLETEAGENT' => 0, 'COMPLETECALLER' => 0];
$totalGeral = 0;


// Processa os dados agrupados por causa de desconexão
foreach ($data as $chamada) {
    if (isset($chamada['event']) && isset($causas[$chamada['event']])) {
        $causas[$chamada['event']]++;
        $totalGeral++;
    }
}


foreach ($causas as $causa => $quantidade) {
    $percentual = $totalGeral > 0 ? round(($quantidade / $totalGeral) * 100, 2) : 0;
    $percentualTotal = $totalGeral / $totalGeral * 100 . '%';
}

// Função para formatar tempo em hh:mm:ss
function format_time($seconds)
{
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
}


// Verifica o formato de exportação
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

if ($format === 'csv') {
    exportCSV($causas, $totalGeral);
} elseif ($format === 'xlsx') {
    exportXLSX($causas, $totalGeral);
} elseif ($format === 'pdf') {
    exportPDF($causas, $totalGeral);
} else {
    echo "Formato de exportação inválido.";
}



// Função para exportar CSV
function exportCSV($causas, $totalGeral)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="relatorio_causas.csv"');
    $output = fopen('php://output', 'w');

    // Adiciona o BOM para garantir compatibilidade UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalhos
    fputcsv($output, ['Causa', 'Quantidade', 'Percentual']);

    // Exporta os dados
    foreach ($causas as $causa => $quantidade) {
        $percentual = $totalGeral > 0 ? round(($quantidade / $totalGeral) * 100, 2) . '%' : '0%';
        fputcsv($output, [$causa, $quantidade, $percentual]);
    }

    fclose($output);
    exit();
}

// Função para exportar XLSX
function exportXLSX($causas, $totalGeral)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório de Causas');

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
    $sheet->setCellValue('A1', 'Causa');
    $sheet->setCellValue('B1', 'Quantidade');
    $sheet->setCellValue('C1', 'Percentual');

    // Aplica o estilo aos cabeçalhos
    $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

    // Adiciona os dados
    $row = 2;
    foreach ($causas as $causa => $quantidade) {
        $percentual = $totalGeral > 0 ? round(($quantidade / $totalGeral) * 100, 2) . '%' : '0%';
        $sheet->setCellValue("A$row", $causa);
        $sheet->setCellValue("B$row", $quantidade);
        $sheet->setCellValue("C$row", $percentual);

        $sheet->getStyle("A$row:C$row")->applyFromArray($contentStyle);
        $row++;
    }

    // Ajusta largura das colunas automaticamente
    foreach (range('A', 'C') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Envia o arquivo XLSX para download
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_causas.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();
}

// Função para exportar PDF
function exportPDF($causas, $totalGeral)
{
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, utf8_decode('Relatório de Causas de Chamadas'), 0, 1, 'C');
    $pdf->Ln(10);

    // Cabeçalhos
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(80, 10, 'Causa', 1);
    $pdf->Cell(50, 10, 'Quantidade', 1);
    $pdf->Cell(50, 10, 'Percentual', 1);
    $pdf->Ln();

    // Dados
    $pdf->SetFont('Arial', '', 10);
    foreach ($causas as $causa => $quantidade) {
        $percentual = $totalGeral > 0 ? round(($quantidade / $totalGeral) * 100, 2) . '%' : '0%';
        $pdf->Cell(80, 10, utf8_decode($causa), 1);
        $pdf->Cell(50, 10, $quantidade, 1);
        $pdf->Cell(50, 10, $percentual, 1);
        $pdf->Ln();
    }

    // Envia o PDF para download
    $pdf->Output('D', 'relatorio_causas.pdf');
    exit();
}



?>