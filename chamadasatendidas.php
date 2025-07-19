<?php
include_once 'api/process_data.php';
include_once 'utils/db_connect.php';
include_once 'header.php';


// Dados vindos de $_SESSION como filas selecionadas e período selecionado.
!empty($_SESSION['fetch_data']) ? $data = $_SESSION['fetch_data'] : [];
!empty($_SESSION['filas']) ? $queues = $_SESSION['filas'] : '';
!empty($_SESSION['startDate']) ? $startDate = $_SESSION['startDate'] : '';
!empty($_SESSION['endDate']) ? $endDate = $_SESSION['endDate'] : '';
!empty($_SESSION['startTime'] ? $startTime = $_SESSION['startTime'] : '');
!empty($_SESSION['endTime'] ? $endTime = $_SESSION['endTime'] : '');

$tmpa = 1;
$tmpe = 1;
$tempoTotalAtendimento = 0;
$totCalls = 1;

foreach ($data as $calls) {

    if (in_array($calls['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
        $totCalls++;
        $tmpa += $calls['call_time'];
        $tmpe += $calls['wait_time'];
        $tempoTotalAtendimento += $calls['call_time'];
    }
}

//Divisão para cálculos de Média.

$tempoMedioAtendimentoTotal = $tmpa / $totCalls;
$tempoMedioEsperaTotal = $tmpe / $totCalls;

//Conversão para 00:00:00
$tempoMedioEsperaTotal = gmdate('H:i:s', $tempoMedioEsperaTotal);
$tempoMedioEsperaTotal == 0 ? $tempoMedioEsperaTotal = "00:00:00" : $tempoMedioEsperaTotal;
$tempoMedioAtendimentoTotal = gmdate('H:i:s', $tempoMedioAtendimentoTotal);
$tempoMedioAtendimentoTotal == 0 ? $tempoMedioAtendimentoTotal = "00:00:00" : $tempoMedioAtendimentoTotal;
$tempoTotalAtendimento = gmdate('H:i:s', $tempoTotalAtendimento);

// Fim de cálculo TMA total.

$nivelServico = [];
$contagemCumulativa = 0;
$registrosPorIntervalo = []; // Armazena os registros agrupados por intervalo

// Define os intervalos (com "150+" para tempos restantes)
$intervalos = [15, 30, 45, 60, 75, 90, 105, 120, 135, 150, '150_'];
foreach ($intervalos as $limite) {
    $nivelServico[$limite] = 0;
    $registrosPorIntervalo[$limite] = []; // Inicializa os grupos
}

// Agrupa os registros
foreach ($data as $chamada) {
    if (isset($chamada['wait_time']) && in_array($chamada['event'], ['COMPLETEAGENT', 'COMPLETECALLER'])) {
        $tempoEspera = $chamada['wait_time'];
        $classificado = false;

        foreach ($intervalos as $limite) {
            if ($limite !== '150_' && $tempoEspera <= $limite) {
                $nivelServico[$limite]++;
                $registrosPorIntervalo[$limite][] = $chamada; // Agrupa o registro
                $classificado = true;
                break;
            }
        }

        // Se não se encaixa em intervalos anteriores, vai para "150+"
        if (!$classificado) {
            $nivelServico['150_']++;
            $registrosPorIntervalo['150_'][] = $chamada;
        }
    }
}

// Calcula o total de chamadas atendidas
$totalChamadas = array_sum($nivelServico);
?>



<body>

    <div class="row">
        <div class="col-12">
            <!-- Início da div distribuicao-summary -->
            <div class="distribuicao-summary">
                <div class="row" id="distribution_summary.7">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">Resumo</div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <caption></caption>
                                    <tbody>
                                        <tr class="bg-light">
                                            <td>Fila:</td>
                                            <td>
                                                <?php
                                                if (isset($queues)) {
                                                    $friendlyNames = []; // Array para armazenar os nomes amigáveis
                                                    foreach ($queues as $queueNumber) {
                                                        // Busca o nome amigável ou mantém o número se não existir
                                                        $friendlyNames[] = isset($qnames[$queueNumber]) ? $qnames[$queueNumber] : $queueNumber;
                                                    }
                                                    echo implode(", ", $friendlyNames); // Exibe os nomes amigáveis das filas, separados por vírgula
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Data Inicial:</td>
                                            <td><span id="rstart">
                                                    <?php echo isset($startDate) ? date('d/m/Y', strtotime($startDate)) : ''; ?>
                                                </span></td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Data Final:</td>
                                            <td><span id="rend">
                                                    <?php echo isset($endDate) ? date('d/m/Y', strtotime($endDate)) : ''; ?>
                                                </span></td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Horário:</td>
                                            <td>
                                                <?php echo isset($startTime) ? $startTime : '';
                                                echo ' - ';
                                                echo isset($endTime) ? $endTime : ''; ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Período:</td>
                                            <td>
                                                <?php
                                                if (isset($startDate) && isset($endDate)) {
                                                    // Converter as datas para objetos DateTime
                                                    $start = new DateTime($startDate);
                                                    $end = new DateTime($endDate);

                                                    // Calcular a diferença entre as datas
                                                    $interval = $start->diff($end);

                                                    // Exibir o número de dias no período
                                                    echo $interval->days . ' dias';
                                                } else {
                                                    echo 'Período não definido';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Total de Ligações -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">Total de Ligações Atendidas</div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr class="bg-light">
                                            <td>Atendidas:</td>
                                            <td>
                                                <?= isset($metrics['total_connects']) ? $metrics['total_connects'] . ' Ligações Atendidas' : '0 Ligações Atendidas'; ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Ligações Transferidas:</td>
                                            <td>
                                                <?= isset($metrics['total_transfers']) ? $metrics['total_transfers'] . ' Ligações Transferidas' : '0 Ligações Transferidas'; ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Tempo médio de conversa:</td>
                                            <td><?= $tempoMedioAtendimentoTotal ?></td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Duração total:</td>
                                            <td><?= $tempoTotalAtendimento ?></td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Tempo médio de espera:</td>
                                            <td><?= $tempoMedioEsperaTotal ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="footer-divider">
            </div>


            <!-- Renderização da Tabela -->
            <div class="container" id="secao-servicoatendidas">
                <h3 class="tituloprincipal">Nível de Serviço</h3>
                <!-- Botões de Exportação -->
                <div class="export-buttons">
                    <!-- Botão de CSV -->
                    <a target="_blank" href="relatorios/relatorios_sla.php?format=csv">
                        <img src="img/exportcsvicon.png" alt="Exportar CSV">
                        <div class="legenda">CSV</div>
                    </a>

                    <!-- Botão de PDF -->
                    <a target="_blank" href="relatorios/relatorios_sla.php?format=pdf">
                        <img src="img/exportpdficon.png" alt="Exportar PDF">
                        <div class="legenda">PDF</div>
                    </a>

                    <!-- Botão de XLSX -->
                    <a target="_blank" href="relatorios/relatorios_sla.php?format=xlsx">
                        <img src="img/xlsx.png" alt="Exportar XLSX">
                        <div class="legenda">XLSX</div>
                    </a>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Atendidas em até X segundos</th>
                            <th>Contagem</th>
                            <th>Delta</th>
                            <th>% do Total</th>
                            <th>Ver Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $contagemCumulativa = 0;
                        foreach ($nivelServico as $limite => $delta):
                            $contagemCumulativa += $delta; // Soma acumulativa
                            $percentual = $totalChamadas > 0 ? round(($contagemCumulativa / $totalChamadas) * 100, 2) : 0;
                            $idDetalhes = "details-{$limite}"; // ID único para cada linha de detalhes
                            ?>
                            <tr>
                                <td><?= $limite === '150_' ? '150+ segundos' : $limite . ' segundos'; ?></td>
                                <td><?= $contagemCumulativa; ?></td>
                                <td><?= '+' . $delta; ?></td>
                                <td><?= $percentual . '%'; ?></td>
                                <td>
                                    <button class="btn btn-info" onclick="toggleDetailsAtendidas(this, '<?= $limite; ?>')">
                                        Ver Detalhes
                                    </button>
                                </td>
                            </tr>

                            <!-- Tabela de Detalhes Oculta -->
                            <tr id="details-<?= $limite ?>" class="details-row" style="display: none;">
                                <td colspan="5">
                                    <div id="details-container-<?= $limite ?>"></div>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                        <!-- Linha Total -->
                        <tr>
                            <th>Total</th>
                            <th><?= $totalChamadas; ?></th>
                            <th></th>
                            <th>100%</th>
                            <th></th>
                        </tr>
                    </tbody>
                </table>
            </div>


            <div class="container mt-5">
                <div class="row">
                    <div class="col-md-12">
                        <canvas id="deltaChart"></canvas>
                    </div>
                </div>
            </div>



            <script>

                function toggleDetailsAtendidas(button, tempoLimite) {
                    const detailsRow = document.getElementById(`details-${tempoLimite}`);
                    const detailsContainer = document.getElementById(`details-container-${tempoLimite}`);

                    if (!detailsRow || !detailsContainer) {
                        console.error(`Elementos com ID details-${tempoLimite} ou details-container-${tempoLimite} não encontrados.`);
                        return;
                    }

                    if (detailsRow.style.display === 'none') {
                        // Exibe os detalhes
                        detailsRow.style.display = '';

                        if (!detailsContainer.innerHTML.trim()) {
                            // Faz uma requisição AJAX para buscar os detalhes
                            $.ajax({
                                url: 'api/process_atendidasdata.php',
                                method: 'GET',
                                data: { tempo_limite: tempoLimite }, // Parâmetro enviado ao PHP
                                dataType: 'json',
                                success: function (data) {
                                    if (data.error) {
                                        detailsContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                                    } else {
                                        // Cria um ID único para a tabela
                                        const tableId = `table-atendidas-${tempoLimite}`;

                                        // Gera o HTML da tabela
                                        let tabelaHTML = `
                        <table class="table table-sm display" id="${tableId}">
                            <thead>
                                <tr>
                                    <th>Agente</th>
                                    <th>Data</th>
                                    <th>Número</th>
                                    <th>Evento</th>
                                    <th>Tempo de Espera</th>
                                    <th>Tempo de Conversa</th>
                                    <th>Gravação</th>
                                </tr>
                            </thead>
                            <tbody>`;

                                        // Adiciona os registros à tabela
                                        data.forEach(chamada => {
                                            tabelaHTML += `
                                <tr>
                                    <td>${chamada.agente || '-'}</td>
                                    <td>${chamada.data || '-'}</td>
                                    <td>${chamada.numero || '-'}</td>
                                    <td style="color: ${chamada.evento === 'COMPLETECALLER' || chamada.evento === 'COMPLETEAGENT' ? 'green' : 'red'};">
                                        ${chamada.evento || '-'}
                                    </td>
                                    <td>${format_time(chamada.tme) || '00:00:00'}</td>
                                    <td>${format_time(chamada.tma) || '00:00:00'}</td>
                                    <td>
                                     <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">
                            Carregar gravação
                            </button>
                                    <div class="audio-container"></div></div>
                                    </td>
                                </tr>`;
                                        });

                                        tabelaHTML += `</tbody></table>`;
                                        detailsContainer.innerHTML = tabelaHTML;
                                        // Inicializa o DataTables
                                        $(`#${tableId}`).DataTable({
                                            responsive: true,
                                            paging: true,
                                            ordering: true,
                                            info: true,
                                            searching: true,
                                            pageLength: 5,
                                            language: {
                                                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json',
                                            },
                                        });
                                    }
                                },
                                error: function (xhr, status, error) {
                                    detailsContainer.innerHTML = `<div class="alert alert-danger">Erro ao carregar os detalhes: ${error}</div>`;
                                }
                            });
                        }
                    } else {
                        // Oculta os detalhes
                        detailsRow.style.display = 'none';
                    }
                }
            </script>



            <!-- Divisor -->
            <hr class="soften" id="distribution_by_queue">
            <!-- Fim divisor -->

            <div class="container" id="secao-atendidasporfila">
                <h3 class="tituloprincipal">Atendidas por Fila</h3>
                <!-- Botões de Exportação -->
                <div class="export-buttons">
                    <!-- Botão de CSV -->
                    <a target="_blank" href="relatorios/relatorios_atendfila.php?format=csv">
                        <img src="img/exportcsvicon.png" alt="Exportar CSV">
                        <div class="legenda">CSV</div>
                    </a>

                    <!-- Botão de PDF -->
                    <a target="_blank" href="relatorios/relatorios_atendfila.php?format=pdf">
                        <img src="img/exportpdficon.png" alt="Exportar PDF">
                        <div class="legenda">PDF</div>
                    </a>

                    <!-- Botão de XLSX -->
                    <a target="_blank" href="relatorios/relatorios_atendfila.php?format=xlsx">
                        <img src="img/xlsx.png" alt="Exportar XLSX">
                        <div class="legenda">XLSX</div>
                    </a>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fila</th>
                            <th>Atendidas</th>
                            <th>% Chamadas</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $filas = [];
                        $totalAtendidas = 0;
                        $totalPercentual = 0;

                        // Filtra chamadas atendidas (COMPLETECALLER e COMPLETEAGENT) e organiza por fila
                        foreach ($data as $chamada) {
                            if (in_array($chamada['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
                                $fila = $chamada['queuename'];
                                if (!isset($filas[$fila])) {
                                    $filas[$fila] = ['recebidas' => 0];
                                }
                                $filas[$fila]['recebidas']++;
                                $totalAtendidas++;
                            }
                        }

                        // Gera a tabela com os dados processados
                        foreach ($filas as $fila => $metrics):
                            $filaNome = isset($qnames[$fila]) ? htmlspecialchars($qnames[$fila]) : htmlspecialchars($fila);
                            $recebidas = $metrics['recebidas'];
                            $percentual = $totalAtendidas > 0 ? round(($recebidas / $totalAtendidas) * 100, 2) : 0;
                            $totalPercentual += $percentual;

                            $idDetailsRow = 'details-' . str_replace(' ', '-', $filaNome);
                            $idDetailsContainer = 'details-container-' . str_replace(' ', '-', $filaNome);
                            ?>
                            <tr>
                                <td><?= $filaNome ?></td>
                                <td><?= $recebidas ?></td>
                                <td><?= $percentual ?>%</td>
                                <td>
                                    <button class="btn btn-info"
                                        onclick="fetchDetailsAtendidas('<?= $fila ?>', '<?= $idDetailsContainer ?>')">
                                        Ver Detalhes
                                    </button>
                                </td>
                            </tr>
                            <tr id="<?= $idDetailsRow ?>" class="details-row" style="display: none;">
                                <td colspan="4">
                                    <div id="<?= $idDetailsContainer ?>"></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><strong>Total</strong></td>
                            <td><strong><?= $totalAtendidas ?></strong></td>
                            <td><strong><?= round($totalPercentual, 2) ?>%</strong></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>


            <script>
                function fetchDetailsAtendidas(fila, containerId) {
                    const container = document.getElementById(containerId);

                    if (!container) {
                        console.error('Container não encontrado:', containerId);
                        return;
                    }

                    // Verifica se os detalhes já foram carregados
                    if (container.innerHTML.trim()) {
                        const detailsRow = container.parentElement.parentElement;
                        detailsRow.style.display = detailsRow.style.display === 'none' ? '' : 'none';
                        return;
                    }

                    // Sanitiza o valor da fila para evitar problemas no ID
                    const sanitizedFila = fila.replace(/[^a-zA-Z0-9-_]/g, '_');

                    fetch('api/process_chamadasatendidasfiladata.php?fila=' + encodeURIComponent(fila))
                        .then(response => response.json())
                        .then(data => {

                            // Verifica se os dados são válidos
                            if (data.error) {
                                container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                            } else if (data.length > 0) {
                                // Gera a tabela dinâmica apenas se houver registros
                                let tableHTML = `
                    <table class="table table-sm" id="detailsTable-${sanitizedFila}">
                        <thead>
                            <tr>
                                <th>Agente</th>
                                <th>Data</th>
                                <th>Número</th>
                                <th>Evento</th>
                                <th>Tempo de Espera</th>
                                <th>Tempo de Conversa</th>
                                <th>Gravação</th>
                            </tr>
                        </thead>
                        <tbody>`;

                                // Itera pelos dados e monta as linhas da tabela
                                data.forEach(chamada => {
                                    tableHTML += `
                        <tr>
                            <td>${chamada.agent || '-'}</td>
                            <td>${chamada.data || '-'}</td>
                            <td>${chamada.numero || '-'}</td>
                            <td style="color: ${chamada.evento === 'COMPLETECALLER' || chamada.evento === 'COMPLETEAGENT' ? 'green' : 'red'};">
                                ${chamada.evento || '-'}
                            </td>
                            <td>${format_time(chamada.wait_time) || '00:00:00'}</td>
                            <td>${format_time(chamada.call_time) || '00:00:00'}</td>
                            <td>
                             <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">
                            Carregar gravação
                            </button>
                                <div class="audio-container"></div>
                            </td>
                        </tr>`;
                                });

                                tableHTML += `</tbody></table>`;
                                container.innerHTML = tableHTML;

                                // Inicializa DataTables na tabela gerada
                                $(`#detailsTable-${sanitizedFila}`).DataTable({
                                    responsive: true,
                                    paging: true,
                                    searching: true,
                                    info: true,
                                    language: datatableLanguage
                                });

                                // Mostra a linha com detalhes
                                container.parentElement.parentElement.style.display = '';
                            } else {
                                // Caso o array esteja vazio
                                container.innerHTML = `<div class="alert alert-warning">Nenhum dado encontrado.</div>`;
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao buscar detalhes:', error);
                            container.innerHTML = `<div class="alert alert-danger">Erro ao carregar os detalhes.</div>`;
                        });
                }
            </script>


            <div class="container mt-5">
                <div class="row">
                    <div class="col-md-12">
                        <canvas id="atendidasPorFilaChart"></canvas>
                    </div>
                </div>
            </div>



            <div class="container" id="secao-atendidasporagente">
                <h3 class="tituloprincipal">Atendidas por Agente</h3>
                <!-- Botões de Exportação -->
                <div class="export-buttons">
                    <!-- Botão de CSV -->
                    <a target="_blank" href="relatorios/relatorios_atenagent.php?format=csv">
                        <img src="img/exportcsvicon.png" alt="Exportar CSV">
                        <div class="legenda">CSV</div>
                    </a>

                    <!-- Botão de PDF -->
                    <a target="_blank" href="relatorios/relatorios_atenagent.php?format=pdf">
                        <img src="img/exportpdficon.png" alt="Exportar PDF">
                        <div class="legenda">PDF</div>
                    </a>

                    <!-- Botão de XLSX -->
                    <a target="_blank" href="relatorios/relatorios_atenagent.php?format=xlsx">
                        <img src="img/xlsx.png" alt="Exportar XLSX">
                        <div class="legenda">XLSX</div>
                    </a>
                </div>

                <div class="tabela-container">
                    <table class="table table-striped" id="atendidas-por-agente">
                        <thead>
                            <tr>
                                <th>Agente</th>
                                <th>Atendidas</th>
                                <th>Transferidas</th>
                                <th>% Chamadas</th>
                                <th>Tempo Conversando</th>
                                <th>% Tempo Total</th>
                                <th>TMA</th>
                                <th>Ring Time</th>
                                <th>Tempo Esperado até Finalização</th>
                                <th>TME</th>
                                <th>Tempo Máximo de Espera</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $agentes = [];
                            $totalAtendidas = 0;

                            // Inicializa os totais
                            $totalGeral = [
                                'recebidas' => 0,
                                'completadas' => 0,
                                'transferidas' => 0,
                                'tempo_conversando' => 0,
                                'ring_time' => 0,
                                'wait_time' => []
                            ];

                            // Processa os dados agregados por agente
                            foreach ($data as $chamada) {
                                if (in_array($chamada['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
                                    $agente = $chamada['agent'] ?? 'Desconhecido';

                                    if (!isset($agentes[$agente])) {
                                        $agentes[$agente] = [
                                            'recebidas' => 0,
                                            'completadas' => 0,
                                            'transferidas' => 0,
                                            'tempo_conversando' => 0,
                                            'ring_time' => 0,
                                            'wait_time' => []
                                        ];
                                    }

                                    // Atualiza os dados por agente
                                    $agentes[$agente]['recebidas']++;
                                    $agentes[$agente]['tempo_conversando'] += $chamada['call_time'] ?? 0;
                                    $agentes[$agente]['ring_time'] += $chamada['ringtime'] ?? 0;
                                    $agentes[$agente]['wait_time'][] = $chamada['wait_time'] ?? 0;

                                    // Atualiza os totais gerais
                                    $totalGeral['recebidas']++;
                                    $totalGeral['completadas']++;
                                    $totalGeral['tempo_conversando'] += $chamada['call_time'] ?? 0;
                                    $totalGeral['ring_time'] += $chamada['ringtime'] ?? 0;
                                    $totalGeral['wait_time'][] = $chamada['wait_time'] ?? 0;

                                    $totalAtendidas++;
                                }
                            }

                            foreach ($agentes as $agente => $metrics):
                                $tma = $metrics['completadas'] > 0 ? gmdate("H:i:s", $metrics['tempo_conversando'] / $metrics['completadas']) : '00:00:00';
                                $tme = !empty($metrics['wait_time']) ? gmdate("H:i:s", array_sum($metrics['wait_time']) / count($metrics['wait_time'])) : '00:00:00';
                                $tempoMaxEspera = !empty($metrics['wait_time']) ? gmdate("H:i:s", max($metrics['wait_time'])) : '00:00:00';
                                $percentualChamadas = round(($metrics['recebidas'] / $totalAtendidas) * 100, 2);

                                $idDetailsRow = 'details-' . str_replace(' ', '-', $agente);
                                $idDetailsContainer = 'details-container-' . str_replace(' ', '-', $agente);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($agente) ?></td>
                                    <td><?= $metrics['recebidas'] ?></td>
                                    <td><?= $metrics['transferidas'] ?></td>
                                    <td><?= $percentualChamadas ?>%</td>
                                    <td><?= gmdate("H:i:s", $metrics['tempo_conversando']) ?></td>
                                    <td><?= $percentualChamadas ?>%</td>
                                    <td><?= $tma ?></td>
                                    <td><?= gmdate("H:i:s", $metrics['ring_time']) ?></td>
                                    <td><?= gmdate("H:i:s", array_sum($metrics['wait_time'])) ?></td>
                                    <td><?= $tme ?></td>
                                    <td><?= $tempoMaxEspera ?></td>
                                    <td>
                                        <button class="btn btn-info"
                                            onclick="fetchDetailsAgente('<?= $agente ?>', '<?= $idDetailsContainer ?>')">
                                            Ver Detalhes
                                        </button>
                                    </td>
                                </tr>
                                <tr id="<?= $idDetailsRow ?>" class="details-row" style="display: none;">
                                    <td colspan="13">
                                        <div id="<?= $idDetailsContainer ?>"></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Linha Total -->
                            <tr>
                                <td><strong>Total</strong></td>
                                <td><strong><?= $totalGeral['recebidas'] ?></strong></td>
                                <td><strong><?= $totalGeral['completadas'] ?></strong></td>
                                <td><strong><?= $totalGeral['transferidas'] ?></strong></td>
                                <td><strong>100%</strong></td>
                                <td><strong><?= gmdate("H:i:s", $totalGeral['tempo_conversando']) ?></strong></td>
                                <td><strong>100%</strong></td>
                                <td><strong><?= gmdate("H:i:s", $totalGeral['tempo_conversando'] / max($totalGeral['completadas'], 1)) ?></strong>
                                </td>
                                <td><strong><?= gmdate("H:i:s", $totalGeral['ring_time']) ?></strong></td>
                                <td><strong><?= gmdate("H:i:s", array_sum($totalGeral['wait_time'])) ?></strong></td>
                                <td><strong><?= gmdate("H:i:s", array_sum($totalGeral['wait_time']) / max(count($totalGeral['wait_time']), 1)) ?></strong>
                                </td>
                                <?php if ($totalGeral['wait_time']): ?>
                                    <td><strong><?= gmdate("H:i:s", max($totalGeral['wait_time'])) ?></strong></td>
                                <?php else: ?>
                                    <td><strong>00:00:00</strong></td>
                                <?php endif; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>



            <script>
                function fetchDetailsAgente(agente, containerId) {
                    const container = document.getElementById(containerId);

                    if (!container) {
                        console.error('Container não encontrado:', containerId);
                        return;
                    }

                    // Verifica se a tabela já foi carregada
                    if (container.innerHTML.trim()) {
                        const row = container.parentElement.parentElement;
                        row.style.display = row.style.display === 'none' ? '' : 'none';
                        return;
                    }

                    fetch('api/process_atendidasporagentedata.php?agente=' + encodeURIComponent(agente))
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                            } else {
                                // Cria a tabela dinamicamente
                                const tableId = `detailsTable-${agente.replace(/\s+/g, '-')}`;
                                let tableHTML = `
                    <table class="table table-sm table-striped" id="${tableId}">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Número</th>
                                <th>Evento</th>
                                <th>Tempo de Espera</th>
                                <th>Tempo de Conversa</th>
                                <th>Gravação</th>
                            </tr>
                        </thead>
                        <tbody>`;

                                data.forEach(chamada => {
                                    tableHTML += `
                        <tr>
                            <td>${chamada.data}</td>
                            <td>${chamada.numero}</td>
                            <td style="color: ${chamada.evento === 'COMPLETECALLER' || chamada.evento === 'COMPLETEAGENT' ? 'green' : 'red'};">
                                        ${chamada.evento || '-'}
                            </td>
                            <td>${format_time(chamada.wait_time)}</td>
                            <td>${format_time(chamada.call_time)}</td>
                            <td>
                               <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">
                            Carregar gravação
                            </button>
                                <div class="audio-container"></div>
                            </td>
                        </tr>`;
                                });

                                tableHTML += `</tbody></table>`;
                                container.innerHTML = tableHTML;

                                // Inicializa o DataTables após a tabela ser renderizada
                                $(`#${tableId}`).DataTable({
                                    responsive: true,
                                    paging: true,
                                    searching: true,
                                    ordering: true,
                                    info: true,
                                    pageLength: 5,
                                    language: {
                                        url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/pt-BR.json'
                                    }
                                });

                                // Exibe a linha de detalhes
                                container.parentElement.parentElement.style.display = '';
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao buscar detalhes:', error);
                            container.innerHTML = `<div class="alert alert-danger">Erro ao carregar os detalhes.</div>`;
                        });
                }
            </script>

            <div class="container mt-5">
                <div class="row">
                    <div class="col-md-12">
                        <canvas id="atendidasPorAgenteChart"></canvas>
                    </div>
                </div>
            </div>


            <div class="container" id="secao-atendidasporagente">
                <h3 class="tituloprincipal">Ligações Atendidas(Agente) Filtrada por Fila</h3>
                <div class="export-buttons">
        <!-- Botões de Exportação (mantidos conforme original) -->
    </div>

    <div class="tabela-container">
        <table class="table table-striped" id="atendidas-por-agente">
            <thead>
                <tr>
                    <th>Agente</th>
                    <th>Filas</th>
                    <th>Atendidas</th>
                    <th>% Chamadas</th>
                    <th>Tempo Conversando</th>
                    <th>TMA</th>
                    <th>Tempo de Toque</th>
                    <th>Tempo Total de Espera</th>
                    <th>TME</th>
                    <th>Tempo Máximo de Espera</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $agentes = [];
                $totalGeral = [
                    'recebidas' => 0,
                    'tempo_conversando' => 0,
                    'ring_time' => 0,
                    'wait_time' => [],
                    'filas' => []
                ];

                // Processamento dos Dados
                foreach ($data as $chamada) {
                    if (in_array($chamada['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
                        $agente = $chamada['agent'] ?? 'Desconhecido';
                        $fila = $chamada['queuename'];

                        // Inicializa estrutura do agente + fila
                        if (!isset($agentes[$agente][$fila])) {
                            $agentes[$agente][$fila] = [
                                'recebidas' => 0,
                                'tempo_conversando' => 0,
                                'ring_time' => 0,
                                'wait_time' => []
                            ];
                        }

                        // Atualiza métricas da fila específica
                        $agentes[$agente][$fila]['recebidas']++;
                        $agentes[$agente][$fila]['tempo_conversando'] += $chamada['call_time'] ?? 0;
                        $agentes[$agente][$fila]['ring_time'] += $chamada['ringtime'] ?? 0;
                        $agentes[$agente][$fila]['wait_time'][] = $chamada['wait_time'] ?? 0;

                        // Atualiza totais gerais
                        $totalGeral['recebidas']++;
                        $totalGeral['tempo_conversando'] += $chamada['call_time'] ?? 0;
                        $totalGeral['ring_time'] += $chamada['ringtime'] ?? 0;
                        $totalGeral['wait_time'][] = $chamada['wait_time'] ?? 0;

                        // Armazena filas únicas
                        if (!in_array($fila, $totalGeral['filas'])) {
                            $totalGeral['filas'][] = $fila;
                        }
                    }
                }
                ?>

                <!-- Exibição dos Dados -->
                <?php foreach ($agentes as $agente => $filasDoAgente): ?>
                        <?php foreach ($filasDoAgente as $fila => $metrics):
                            // Cálculos
                            $tma = $metrics['recebidas'] > 0 ?
                                gmdate("H:i:s", $metrics['tempo_conversando'] / $metrics['recebidas']) : '00:00:00';

                            $tme = !empty($metrics['wait_time']) ?
                                gmdate("H:i:s", array_sum($metrics['wait_time']) / count($metrics['wait_time'])) : '00:00:00';

                            $tempoMaxEspera = !empty($metrics['wait_time']) ?
                                gmdate("H:i:s", max($metrics['wait_time'])) : '00:00:00';

                            $percentualChamadas = $totalGeral['recebidas'] > 0 ?
                                round(($metrics['recebidas'] / $totalGeral['recebidas']) * 100, 2) : 0;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($agente) ?></td>
                                    <td><?= htmlspecialchars($fila) ?></td>
                                    <td><?= $metrics['recebidas'] ?></td>
                                    <td><?= $percentualChamadas ?>%</td>
                                    <td><?= gmdate("H:i:s", $metrics['tempo_conversando']) ?></td>
                                    <td><?= $tma ?></td>
                                    <td><?= gmdate("H:i:s", $metrics['ring_time']) ?></td>
                                    <td><?= gmdate("H:i:s", array_sum($metrics['wait_time'])) ?></td>
                                    <td><?= $tme ?></td>
                                    <td><?= $tempoMaxEspera ?></td>
                                    <td>
                                        <button class="btn btn-info" 
                                            onclick="fetchDetailsAgente('<?= $agente ?>', '<?= $fila ?>')">
                                            Ver Detalhes
                                        </button>
                                    </td>
                                </tr>
                        <?php endforeach; ?>
                <?php endforeach; ?>

                <!-- Linha Total -->
                <tr>
                    <td><strong>Total</strong></td>
                    <td><?= implode(', ', array_unique($totalGeral['filas'])) ?></td>
                    <td><strong><?= $totalGeral['recebidas'] ?></strong></td>
                    <td><strong>100%</strong></td>
                    <td><strong><?= gmdate("H:i:s", $totalGeral['tempo_conversando']) ?></strong></td>
                    <td><strong><?= gmdate("H:i:s", $totalGeral['tempo_conversando'] / max($totalGeral['recebidas'], 1)) ?></strong></td>
                    <td><strong><?= gmdate("H:i:s", $totalGeral['ring_time']) ?></strong></td>
                    <td><strong><?= gmdate("H:i:s", array_sum($totalGeral['wait_time'])) ?></strong></td>
                    <td><strong><?= gmdate("H:i:s", array_sum($totalGeral['wait_time']) / max(count($totalGeral['wait_time']), 1)) ?></strong></td>
                    <td><strong><?= !empty($totalGeral['wait_time']) ? gmdate("H:i:s", max($totalGeral['wait_time'])) : '00:00:00' ?></strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>



            <script>
                function fetchDetailsAgente(agente, containerId) {
                    const container = document.getElementById(containerId);

                    if (!container) {
                        console.error('Container não encontrado:', containerId);
                        return;
                    }

                    // Verifica se a tabela já foi carregada
                    if (container.innerHTML.trim()) {
                        const row = container.parentElement.parentElement;
                        row.style.display = row.style.display === 'none' ? '' : 'none';
                        return;
                    }

                    fetch('api/process_atendidasporagentedata.php?agente=' + encodeURIComponent(agente))
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                            } else {
                                // Cria a tabela dinamicamente
                                const tableId = `detailsTable-${agente.replace(/\s+/g, '-')}`;
                                let tableHTML = `
                    <table class="table table-sm table-striped" id="${tableId}">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Número</th>
                                <th>Evento</th>
                                <th>Tempo de Espera</th>
                                <th>Tempo de Conversa</th>
                                <th>Gravação</th>
                            </tr>
                        </thead>
                        <tbody>`;

                                data.forEach(chamada => {
                                    tableHTML += `
                        <tr>
                            <td>${chamada.data}</td>
                            <td>${chamada.numero}</td>
                            <td style="color: ${chamada.evento === 'COMPLETECALLER' || chamada.evento === 'COMPLETEAGENT' ? 'green' : 'red'};">
                                        ${chamada.evento || '-'}
                            </td>
                            <td>${format_time(chamada.wait_time)}</td>
                            <td>${format_time(chamada.call_time)}</td>
                            <td>
                               <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">
                            Carregar gravação
                            </button>
                                <div class="audio-container"></div>
                            </td>
                        </tr>`;
                                });

                                tableHTML += `</tbody></table>`;
                                container.innerHTML = tableHTML;

                                // Inicializa o DataTables após a tabela ser renderizada
                                $(`#${tableId}`).DataTable({
                                    responsive: true,
                                    paging: true,
                                    searching: true,
                                    ordering: true,
                                    info: true,
                                    pageLength: 5,
                                    language: {
                                        url: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/pt-BR.json'
                                    }
                                });

                                // Exibe a linha de detalhes
                                container.parentElement.parentElement.style.display = '';
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao buscar detalhes:', error);
                            container.innerHTML = `<div class="alert alert-danger">Erro ao carregar os detalhes.</div>`;
                        });
                }
            </script>

            <div class="container mt-5">
                <div class="row">
                    <div class="col-md-12">
                        <canvas id="atendidasPorAgenteChart"></canvas>
                    </div>
                </div>
            </div>



            <!-- Seção de Causa da Desconexão -->
            <div class="container" id="secao-causadesconexao">
                <h3 class="tituloprincipal">Causa de Desconexão</h3>
                <!-- Botões de Exportação -->
                <div class="export-buttons">
                    <!-- Botão de CSV -->
                    <a target="_blank" href="relatorios/relatorios_causadesc.php?format=csv">
                        <img src="img/exportcsvicon.png" alt="Exportar CSV">
                        <div class="legenda">CSV</div>
                    </a>

                    <!-- Botão de PDF -->
                    <a target="_blank" href="relatorios/relatorios_causadesc.php?format=pdf">
                        <img src="img/exportpdficon.png" alt="Exportar PDF">
                        <div class="legenda">PDF</div>
                    </a>

                    <!-- Botão de XLSX -->
                    <a target="_blank" href="relatorios/relatorios_causadesc.php?format=xlsx">
                        <img src="img/xlsx.png" alt="Exportar XLSX">
                        <div class="legenda">XLSX</div>
                    </a>
                </div>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Causa</th>
                            <th>Quantidade</th>
                            <th>% do Total</th>
                            <th>Ver Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $causas = ['COMPLETEAGENT' => 0, 'COMPLETECALLER' => 0];

                        // Processa os dados agrupados por causa de desconexão
                        foreach ($data as $chamada) {
                            if (isset($chamada['event']) && isset($causas[$chamada['event']])) {
                                $causas[$chamada['event']]++;
                            }
                        }

                        foreach ($causas as $causa => $quantidade):
                            $percentual = $totalGeral['recebidas'] > 0 ? round(($quantidade / $totalChamadas) * 100, 2) : 0;
                            //Verifica se existe valor para o total de chamadas recebidas
                            $totalGeral['recebidas'] > 0 ? $percentualTotal = $totalGeral['recebidas'] / $totalGeral['recebidas'] * 100 . '%' : $percentualTotal = '0%';
                            $idDetailsRow = "details-row-{$causa}";
                            $idDetailsContainer = "details-container-{$causa}";
                            ?>
                                <tr>
                                    <td><?= $causa === 'COMPLETEAGENT' ? 'Encerrada por Agente' : 'Encerrada por Chamador'; ?>
                                    </td>
                                    <td><?= $quantidade ?></td>
                                    <td><?= $percentual ?>%</td>
                                    <td>
                                        <button class="btn btn-info"
                                            onclick="fetchDetailsCausa('<?= $causa ?>', '<?= $idDetailsContainer ?>')">
                                            Ver Detalhes
                                        </button>
                                    </td>
                                </tr>
                                <tr id="<?= $idDetailsRow ?>" class="details-row" style="display: none;">
                                    <td colspan="4">
                                        <div id="<?= $idDetailsContainer ?>"></div>
                                    </td>
                                </tr>
                        <?php endforeach; ?>
                        <!-- Linha Total -->
                        <tr>
                            <th>Total</th>
                            <th><?= $totalGeral['recebidas']; ?></th>
                            <th><?= $percentualTotal; ?></th>
                            <th></th>
                        </tr>
                    </tbody>
                </table>
            </div>


            <script>

                function fetchDetailsCausa(causa, containerId) {
                    const container = document.getElementById(containerId);

                    if (!container) {
                        console.error('Container não encontrado:', containerId);
                        return;
                    }

                    // Verifica se os detalhes já foram carregados
                    if (container.innerHTML.trim()) {
                        const detailsRow = container.parentElement.parentElement;
                        detailsRow.style.display = detailsRow.style.display === 'none' ? '' : 'none';
                        return;
                    }

                    fetch('api/process_causadesc.php?causa=' + encodeURIComponent(causa))
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                            } else if (data.length > 0) {
                                // Monta a tabela de detalhes dinamicamente
                                let tableHTML = `
                    <table class="table table-sm display" id="table-details-${causa}">
                        <thead>
                            <tr>
                                <th>Agente</th>
                                <th>Data</th>
                                <th>Número</th>
                                <th>Evento</th>
                                <th>Tempo de Espera</th>
                                <th>Tempo de Conversa</th>
                                <th>Gravação</th>
                            </tr>
                        </thead>
                        <tbody>`;

                                data.forEach(item => {
                                    tableHTML += `
                        <tr>
                            <td>${item.agente || '-'}</td>
                            <td>${item.data || '-'}</td>
                            <td>${item.numero || '-'}</td>
                            <td style="color: ${item.evento === 'COMPLETECALLER' || item.evento === 'COMPLETEAGENT' ? 'green' : 'red'};">
                                        ${item.evento || '-'}
                            </td>
                            <td>${format_time(item.wait_time) || '0'}</td>
                            <td>${format_time(item.call_time) || '0'}</td>
                            <td>
                                  <button class="btn btn-secondary" onclick="loadRecording('${item.callid}', this)">
                            Carregar gravação
                            </button>
                                <div class="audio-container"></div>
                            </td>
                        </tr>`;
                                });

                                tableHTML += `</tbody></table>`;
                                container.innerHTML = tableHTML;

                                // Inicializa o DataTables
                                $(`#table-details-${causa}`).DataTable({
                                    responsive: true,
                                    paging: true,
                                    searching: true,
                                    info: true,
                                    language: datatableLanguage
                                });

                                // Mostra a linha com os detalhes
                                container.parentElement.parentElement.style.display = '';
                            } else {
                                container.innerHTML = `<div class="alert alert-warning">Nenhum dado encontrado.</div>`;
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao buscar detalhes:', error);
                            container.innerHTML = `<div class="alert alert-danger">Erro ao carregar os detalhes.</div>`;
                        });
                }


            </script>



            <div class="container mt-5">
                <div class="row">
                    <div class="col-md-12">
                        <canvas id="causaChart" width="400" height="400"></canvas>
                    </div>
                </div>
            </div>




            <?php

            // Verifica se os dados estão disponíveis na sessão
            if (empty($_SESSION['fetch_data'])) {
                echo '<div class="alert alert-danger">Nenhum dado encontrado.</div>';
                exit;
            }

            // Recupera os dados
            $data = $_SESSION['fetch_data'];

            // Definição dos intervalos de tempo
            $intervalos = [15, 30, 45, 60, 75, 90, 105, 120, 135, '150_'];
            $agrupadas = [];

            // Inicializa o array com valores padrão
            foreach ($intervalos as $limite) {
                $agrupadas[$limite] = [
                    'recebidas' => 0,
                    'completadas' => 0,
                    'tempo_conversando' => 0,
                    'wait_time' => []
                ];
            }

            $totalChamadas = 0;

            // Processa os dados e agrupa conforme os intervalos
            foreach ($data as $chamada) {
                if (isset($chamada['call_time']) && in_array($chamada['event'], ['COMPLETECALLER', 'COMPLETEAGENT'])) {
                    $duracao = $chamada['call_time'];
                    $intervaloSelecionado = '150_';

                    // Identifica o intervalo correto
                    foreach ($intervalos as $limite) {
                        if ($limite !== '150_' && $duracao <= $limite) {
                            $intervaloSelecionado = $limite;
                            break;
                        }
                    }

                    // Atualiza métricas
                    $agrupadas[$intervaloSelecionado]['recebidas']++;
                    $agrupadas[$intervaloSelecionado]['completadas']++;
                    $agrupadas[$intervaloSelecionado]['tempo_conversando'] += $duracao;
                    $agrupadas[$intervaloSelecionado]['wait_time'][] = $chamada['wait_time'] ?? 0;
                    $totalChamadas++;
                }
            }

            // Variáveis para somar os totais globais
            $totalRecebidas = 0;
            $totalCompletadas = 0;
            $totalTempoConversando = 0;
            $totalWaitTime = 0; // Soma total de tempos de espera
            $totalWaitCount = 0; // Contagem total de tempos de espera
            ?>

            <div class="container" id="secao-atendidasporintervalo">
                <h3 class="tituloprincipal">Atendidas por Duração</h3>
                <!-- Botões de Exportação -->
                <div class="export-buttons">
                    <!-- Botão de CSV -->
                    <a target="_blank" href="relatorios/relatorios_atenduracao.php?format=csv">
                        <img src="img/exportcsvicon.png" alt="Exportar CSV">
                        <div class="legenda">CSV</div>
                    </a>

                    <!-- Botão de PDF -->
                    <a target="_blank" href="relatorios/relatorios_atenduracao.php?format=pdf">
                        <img src="img/exportpdficon.png" alt="Exportar PDF">
                        <div class="legenda">PDF</div>
                    </a>

                    <!-- Botão de XLSX -->
                    <a target="_blank" href="relatorios/relatorios_atenduracao.php?format=xlsx">
                        <img src="img/xlsx.png" alt="Exportar XLSX">
                        <div class="legenda">XLSX</div>
                    </a>
                </div>

                <div class="tabela-container">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Duração</th>
                                <th>Atendidas</th>
                                <th>% Chamadas</th>
                                <th>Tempo Conversando</th>
                                <th>TMA</th>
                                <th>TME</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agrupadas as $intervalo => $dados): ?>
                                    <?php
                                    // Calcula percentuais e TMA
                                    $percentualChamadas = $totalChamadas > 0 ? round(($dados['recebidas'] / $totalChamadas) * 100, 2) : 0;
                                    $tma = $dados['completadas'] > 0 ? gmdate("H:i:s", $dados['tempo_conversando'] / $dados['completadas']) : '00:00:00';
                                    $tme = !empty($dados['wait_time']) ? gmdate("H:i:s", array_sum($dados['wait_time']) / count($dados['wait_time'])) : '00:00:00';

                                    // Soma os valores totais
                                    $totalRecebidas += $dados['recebidas'];
                                    $totalCompletadas += $dados['completadas'];
                                    $totalTempoConversando += $dados['tempo_conversando'];
                                    $totalWaitTime += array_sum($dados['wait_time']);
                                    $totalWaitCount += count($dados['wait_time']);
                                    ?>
                                    <tr>
                                        <td><?= $intervalo === '150_' ? '150+ segundos' : $intervalo . ' segundos'; ?></td>
                                        <td><?= $dados['recebidas']; ?></td>
                                        <td><?= $percentualChamadas; ?>%</td>
                                        <td><?= gmdate("H:i:s", $dados['tempo_conversando']); ?></td>
                                        <td><?= $tma; ?></td>
                                        <td><?= $tme; ?></td>
                                        <td>
                                            <button class="btn btn-info"
                                                onclick="fetchDetalhesDuracao('<?= $intervalo; ?>', 'container-<?= $intervalo; ?>')">
                                                Ver Detalhes
                                            </button>
                                        </td>
                                    </tr>
                                    <tr id="details-row-<?= $intervalo; ?>" style="display: none;">
                                        <td colspan="8">
                                            <div id="container-<?= $intervalo; ?>"></div>
                                        </td>
                                    </tr>
                            <?php endforeach; ?>

                            <!-- Linha Total -->
                            <tr>
                                <th>Total</th>
                                <th><?= $totalRecebidas; ?></th>
                                <th>100%</th>
                                <th><?= gmdate("H:i:s", $totalTempoConversando); ?></th>
                                <th><?= $totalCompletadas > 0 ? gmdate("H:i:s", $totalTempoConversando / $totalCompletadas) : '00:00:00'; ?>
                                </th>
                                <th><?= $totalWaitCount > 0 ? gmdate("H:i:s", $totalWaitTime / $totalWaitCount) : '00:00:00'; ?>
                                </th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
                function fetchDetalhesDuracao(intervalo, containerId) {
                    const detailsRow = document.getElementById(`details-row-${intervalo}`);
                    const container = document.getElementById(containerId);

                    if (!detailsRow || !container) {
                        console.error('Elementos não encontrados:', containerId);
                        return;
                    }

                    // Verifica se os detalhes já estão carregados
                    if (detailsRow.style.display === '' && container.innerHTML.trim() !== '') {
                        // Oculta a linha se estiver visível
                        detailsRow.style.display = 'none';
                        return;
                    }

                    // Exibe a linha de detalhes
                    detailsRow.style.display = '';

                    // Evita carregar novamente se já estiver populado
                    if (container.innerHTML.trim()) {
                        return;
                    }

                    // Busca os dados de detalhes via AJAX
                    fetch('api/process_atendidasdurac.php?intervalo=' + encodeURIComponent(intervalo))
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                            } else if (data.length > 0) {
                                // Monta a tabela com os detalhes
                                let tableHTML = `
                    <table class="table table-sm" id="details-table-${intervalo}">
                        <thead>
                            <tr>
                                <th>Agente</th>
                                <th>Data</th>
                                <th>Número</th>
                                <th>Evento</th>
                                <th>Tempo de Espera</th>
                                <th>Tempo de Conversa</th>
                                <th>Gravação</th>
                            </tr>
                        </thead>
                        <tbody>`;
                                data.forEach(chamada => {
                                    tableHTML += `
                        <tr>
                            <td>${chamada.agent || '-'}</td>
                            <td>${chamada.combined_time || '-'}</td>
                            <td>${chamada.callerid || '-'}</td>
                            <td style="color: ${chamada.event === 'COMPLETECALLER' || chamada.event === 'COMPLETEAGENT' ? 'green' : 'red'};">
                                        ${chamada.event || '-'}
                            </td>                            
                            <td>${format_time(chamada.wait_time) || '0'}</td>
                            <td>${format_time(chamada.call_time) || '0'}</td>
                        <td>
                                ${chamada.callid
                                            ? `<button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">
                                        Carregar gravação
                                    </button>`
                                            : '-'}
                                <div class="audio-container"></div>
                            </td>
                        </tr>`;
                                });

                                tableHTML += `</tbody></table>`;
                                container.innerHTML = tableHTML;

                                // Inicializa o DataTables na tabela gerada
                                $(`#details-table-${intervalo}`).DataTable({
                                    responsive: true,
                                    paging: true,
                                    searching: true,
                                    ordering: true,
                                    info: true,
                                    pageLength: 5,
                                    language: {
                                        url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json'
                                    }
                                });
                            } else {
                                container.innerHTML = `<div class="alert alert-warning">Nenhum detalhe encontrado para o intervalo ${intervalo}.</div>`;
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao carregar os detalhes:', error);
                            container.innerHTML = `<div class="alert alert-danger">Erro ao carregar os detalhes.</div>`;
                        });
                }

            </script>

            <div class="container mt-5">
                <div class="row">
                    <div class="col-md-12">
                        <canvas id="duracaoChart"></canvas>
                    </div>
                </div>
            </div>

            <script>
                $(document).ready(function () {
                    // Inicializa DataTables para as tabelas principais
                    $('#nivelServicoTable').DataTable({
                        "language": {
                            "url": "https://cdn.datatables.net/plug-ins/1.12.1/i18n/pt-BR.json"
                        },
                        "pageLength": 10
                    });
                    $('#atendidasPorFilaTable').DataTable({
                        "language": {
                            "url": "https://cdn.datatables.net/plug-ins/1.12.1/i18n/pt-BR.json"
                        },
                        "pageLength": 10
                    });
                });

                function toggleDetails(rowId) {
                    var row = document.getElementById(rowId);
                    if (row.style.display === "none" || row.style.display === "") {
                        row.style.display = "table-row";

                        // Seleciona a tabela de detalhes dentro da linha
                        var table = row.querySelector('.detailsDataTable');

                        // Valida se a tabela existe antes de inicializar o DataTables
                        if (table && !$.fn.DataTable.isDataTable('#' + table.id)) {
                            $('#' + table.id).DataTable({
                                "language": {
                                    "url": "https://cdn.datatables.net/plug-ins/1.12.1/i18n/pt-BR.json"
                                },
                                "pageLength": 5
                            });
                        }
                    } else {
                        row.style.display = "none";
                    }
                }


            </script>

            <!-- <script src="js/ligacoesporhora.js"></script> -->
</body>
<?php include_once 'footer.php'; ?>