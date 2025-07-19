<?php
session_start();
require_once '../api/process_monthdata.php';
if (!isset($_SESSION['dadosPorMes'])) {
    echo "Nenhum dado disponível para exportar.";
    exit();
}
//Armazenando métricas por mês em $dadosPorMes
$dadosPorMes = $_SESSION['dadosPorMes'];


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;



/**
 * @var string $format Formato de exportação
 */
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Função que converte tempo em segundos para hh:mm:ss


function format_time($seconds)
{
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
}



// Verifica o formato e exporta de acordo
if ($format === 'csv') {
    exportCSV($dadosPorMes);
} elseif ($format === 'xlsx') {
    exportXLSX($dadosPorMes);
} elseif ($format === 'pdf') {
    exportPDF($dadosPorMes);
} else {
    echo "Formato de exportação inválido.";
}

// Função para exportar CSV
function exportCSV($dados)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_mes.csv"');
    $output = fopen('php://output', 'w');

    // Adiciona o BOM para garantir a compatibilidade UTF-8 com o Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalhos
    fputcsv($output, ['Mês', 'Recebidas', 'Atendidas', 'Abandonadas', 'Transferidas', 'TME', 'TMC', '% Atendidas', '% Não Atendidas', 'SLA']);

    foreach ($dados as $mesAno => $metrics) {
        // Utilize os valores de TME e TMC diretamente do array de métricas
        isset($metrics['soma_tempo_espera']) ? $tme = format_time($metrics['soma_tempo_espera']) : $tme = '00:00:00'; // Aqui usamos diretamente o valor de tme
        isset($metrics['soma_tempo_conversa']) ? $tmc = format_time($metrics['soma_tempo_conversa']) : $tmc = '00:00:00'; // Aqui usamos diretamente o valor de tmc

        // Cálculo de % Atendidas e % Não Atendidas
        $percentAtendidas = $metrics['total_ligacoes_recebidas'] > 0 ? round(($metrics['total_ligacoes_atendidas'] / $metrics['total_ligacoes_recebidas']) * 100, 2) : 0;
        $percentNaoAtendidas = $metrics['total_ligacoes_recebidas'] > 0 ? 100 - $percentAtendidas : 0;

        // Cálculo do SLA
        $sla = $percentAtendidas;

        // Exporta a linha no CSV
        fputcsv($output, [
            utf8_decode($mesAno),
            $metrics['total_ligacoes_recebidas'],
            $metrics['total_ligacoes_atendidas'],
            $metrics['total_ligacoes_abandonadas'],
            $metrics['total_ligacoes_transferidas'],
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


// Função para exportar XLSX
function exportXLSX($dados)
{
    require 'vendor/autoload.php'; // Inclua a biblioteca PhpSpreadsheet via Composer

    if (!isset($_SESSION['dadosPorMes'])) {
        echo "Nenhum dado disponível para exportar.";
        exit();
    }

    $dadosPorMes = $_SESSION['dadosPorMes'];

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

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Relatório Ligações por Mês');

    // Cabeçalhos
    $sheet->setCellValue('A1', 'Mês');
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

    // Dados
    $row = 2;
    foreach ($dados as $mesAno => $metrics) {
        // Utilize os valores de TME e TMC diretamente do array de métricas
        $tme = $metrics['tme']; // Aqui usamos diretamente o valor de tme
        $tmc = $metrics['tmc']; // Aqui usamos diretamente o valor de tmc

        // Cálculo de % Atendidas e % Não Atendidas
        $percentAtendidas = $metrics['total_ligacoes_recebidas'] > 0 ? round(($metrics['total_ligacoes_atendidas'] / $metrics['total_ligacoes_recebidas']) * 100, 2) : 0;
        $percentNaoAtendidas = $metrics['total_ligacoes_recebidas'] > 0 ? 100 - $percentAtendidas : 0;

        // Cálculo do SLA
        $sla = $percentAtendidas;

        // Exporta os dados na planilha
        $sheet->setCellValue("A$row", utf8_encode($mesAno));
        $sheet->setCellValue("B$row", $metrics['total_ligacoes_recebidas']);
        $sheet->setCellValue("C$row", $metrics['total_ligacoes_atendidas']);
        $sheet->setCellValue("D$row", $metrics['total_ligacoes_abandonadas']);
        $sheet->setCellValue("E$row", $metrics['total_ligacoes_transferidas']);
        $sheet->setCellValue("F$row", $tme);
        $sheet->setCellValue("G$row", $tmc);
        $sheet->setCellValue("H$row", $percentAtendidas . '%');
        $sheet->setCellValue("I$row", $percentNaoAtendidas . '%');
        $sheet->setCellValue("J$row", $sla . '%');

        $row++;
    }

    // Envia para o navegador para download
    // Enviando arquivo para download
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="relatorio_ligacoes_mes.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit();
}

function exportPDF($dados)
{
    require 'vendor/setasign/fpdf/fpdf.php'; // Inclua a biblioteca FPDF
    $dadosPorMes = $_SESSION['dadosPorMes'];

    $pdf = new FPDF();
    $pdf->AddPage('L');
    $pdf->SetFont('Arial', 'B', 12); // Reduzindo a fonte para 10
    $pdf->Cell(0, 10, utf8_decode('Relatório de Ligações por Mês'), 0, 1, 'C');
    $pdf->Ln(5);

    // Ajuste das larguras das colunas e a fonte do cabeçalho
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(27, 8, utf8_decode('Mês'), 1); // Reduzindo ainda mais a largura
    $pdf->Cell(27, 8, 'Recebidas', 1); // Encurtando os nomes de colunas
    $pdf->Cell(27, 8, 'Atendidas', 1);
    $pdf->Cell(27, 8, 'Abandonadas', 1);
    $pdf->Cell(27, 8, 'Transferidas', 1);
    $pdf->Cell(27, 8, 'TME', 1);
    $pdf->Cell(27, 8, 'TMC', 1);
    $pdf->Cell(27, 8, '% Atendidas', 1);
    $pdf->Cell(30, 8, utf8_decode('% Não Atendidas'), 1); // Ajustando aqui também
    $pdf->Cell(27, 8, 'SLA', 1);
    $pdf->Ln();

    // Dados
    $pdf->SetFont('Arial', '', 10); // Reduzindo a fonte para os dados
    foreach ($dadosPorMes as $mesAno => $metrics) {
        // Utilize os valores de TME e TMC diretamente do array de métricas
        $tme = $metrics['tme']; // Aqui usamos diretamente o valor de tme
        $tmc = $metrics['tmc']; // Aqui usamos diretamente o valor de tmc

        // Cálculo de % Atendidas e % Não Atendidas
        $percentAtendidas = $metrics['total_ligacoes_recebidas'] > 0 ? round(($metrics['total_ligacoes_atendidas'] / $metrics['total_ligacoes_recebidas']) * 100, 2) : 0;
        $percentNaoAtendidas = $metrics['total_ligacoes_recebidas'] > 0 ? 100 - $percentAtendidas : 0;

        // Cálculo do SLA
        $sla = $percentAtendidas;

        // Exporta os dados no PDF, com largura ajustada para evitar o corte
        $pdf->Cell(27, 8, utf8_decode($mesAno), 1);
        $pdf->Cell(27, 8, $metrics['total_ligacoes_recebidas'], 1);
        $pdf->Cell(27, 8, $metrics['total_ligacoes_atendidas'], 1);
        $pdf->Cell(27, 8, $metrics['total_ligacoes_abandonadas'], 1);
        $pdf->Cell(27, 8, $metrics['total_ligacoes_transferidas'], 1);
        $pdf->Cell(27, 8, $tme, 1);
        $pdf->Cell(27, 8, $tmc, 1);
        $pdf->Cell(27, 8, $percentAtendidas . '%', 1);
        $pdf->Cell(30, 8, $percentNaoAtendidas . '%', 1);
        $pdf->Cell(27, 8, $sla . '%', 1);
        $pdf->Ln();
    }

    // Envia o PDF para download
    $pdf->Output('D', 'relatorio_ligacoes_mes.pdf');
}