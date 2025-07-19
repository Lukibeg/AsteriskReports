<?php
include_once 'api/process_data.php';
include_once 'utils/db_connect.php';
require_once 'header.php';

// Dados vindos de $_SESSION como filas selecionadas e período selecionado.
!empty($_SESSION['fetch_data']) ? $data = $_SESSION['fetch_data'] : [];
!empty($_SESSION['filas']) ? $queues = $_SESSION['filas'] : '';
!empty($_SESSION['startDate']) ? $startDate = $_SESSION['startDate'] : '';
!empty($_SESSION['endDate']) ? $endDate = $_SESSION['endDate'] : '';
!empty($_SESSION['startTime'] ? $startTime = $_SESSION['startTime'] : '');
!empty($_SESSION['endTime'] ? $endTime = $_SESSION['endTime'] : '');



$tmpa = 0;
$tmpe = 0;
$tempoTotalAtendimento = 0;
$totCalls = 0;
foreach ($data as $calls) {
    $tmpa += $calls['call_time'];
    $tmpe += $calls['wait_time'];
    $tempoTotalAtendimento += $calls['call_time'];

    if (in_array($calls['event'], ['ABANDON'])) {
        $totCalls++;
    }
}

//Divisão para cálculos de Média.
if($totCalls !== 0){
    $tempoMedioAtendimentoTotal = $tmpa / $totCalls;
    $tempoMedioEsperaTotal = $tmpe / $totCalls;
}else{
    $tempoMedioAtendimentoTotal = 0;
    $tempoMedioEsperaTotal = 0;
}

//Conversão para 00:00:00
$tempoMedioEsperaTotal = gmdate('H:i:s', $tempoMedioEsperaTotal);
$tempoMedioAtendimentoTotal = gmdate('H:i:s', $tempoMedioAtendimentoTotal);
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
    if (isset($chamada['wait_time']) && in_array($chamada['event'], ['ABANDON'])) {
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
                            <div class="card-header">Total de Ligações Não Atendidas </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr class="bg-light">
                                            <td>Ligações Não Completadas:</td>
                                            <td>
                                                <?= isset($metrics['total_abandons']) ? $metrics['total_abandons'] . ' Ligações' : '0 Ligações'; ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Ligações Abandonadas:</td>
                                            <td>
                                                <?= isset($metrics['total_abandons']) ? $metrics['total_abandons'] . ' Ligações' : '0 Ligações'; ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Não Atendidas</td>
                                            <td> <?= isset($metrics['total_cancel']) ? $metrics['total_cancel'] . ' Ligações' : '0 Ligações'; ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Espera Média antes do Abandono:</td>
                                            <td> <?= $average_wait_time_formatted; ?>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Posição Média na Fila ao Abandonar</td>
                                            <td><?= 1 ?></td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Posição Média Inicial na Fila de quem Abandonou</td>
                                            <td><?= 1 ?></td>
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
            <div class="container" id="secao-serviconaoatendidas">
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
                            <th>Não Atendidas em até X segundos</th>
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
                                    <button class="btn btn-info"
                                        onclick="toggleDetailsNaoAtendidas(this, '<?= $limite; ?>')">
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


            <script>

                function toggleDetailsNaoAtendidas(button, tempoLimite) {
                    const normalizedId = tempoLimite.replace('+', '_'); // Normaliza o ID
                    const detailsRow = document.getElementById(`details-${normalizedId}`);
                    const detailsContainer = document.getElementById(`details-container-${normalizedId}`);

                    if (!detailsRow || !detailsContainer) {
                        console.error(`Elementos com ID details-${normalizedId} ou details-container-${normalizedId} não encontrados.`);
                        return;
                    }

                    if (detailsRow.style.display === 'none') {
                        detailsRow.style.display = '';

                        if (!detailsContainer.innerHTML.trim()) {
                            // Faz uma requisição AJAX para buscar os detalhes
                            $.ajax({
                                url: 'api/process_naoatendidas.php',
                                method: 'GET',
                                data: { tempo_limite: tempoLimite },
                                dataType: 'json',
                                success: function (data) {
                                    if (data.error) {
                                        detailsContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                                    } else {
                                        const tableId = `table-atendidas-${normalizedId}`;

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
                                <tbody>
                                    ${data.map(chamada => `
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
                                                <div class="audio-container"></div>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>`;

                                        detailsContainer.innerHTML = tabelaHTML;

                                        // Inicializa o DataTables com o ID normalizado
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
                                },
                            });
                        }
                    } else {
                        detailsRow.style.display = 'none';
                    }
                }


            </script>



            <!-- Divisor -->
            <hr class="soften" id="distribution_by_queue">
            <!-- Fim divisor -->


            <div class="container" id="secao-naoatendidasevento">
                <h3 class="tituloprincipal">Ligações Não Atendidas - Por Evento</h3>
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
                            <th>Evento</th>
                            <th>Total</th>
                            <th>% Chamadas</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $totalNaoAtendidas = 0;
                        $totalPercentual = 0;
                        $naoAtendidas = [];
                        // Filtra chamadas Não atendidas (Abandon) e organiza por evento
                        foreach ($data as $chamada) {
                            $tipoEvento = $chamada['event'];
                            if (in_array($tipoEvento, ['ABANDON', 'CANCEL', 'CHANUNAVAIL', 'EXITWITHTIMEOUT'])) {
                                $totalNaoAtendidas++;


                                if (!isset($naoAtendidas[$tipoEvento])) {
                                    $naoAtendidas[$tipoEvento] = 0;
                                }
                                $naoAtendidas[$tipoEvento]++;

                            }
                        }

                        // Gera a tabela com os dados processados
                        foreach ($naoAtendidas as $tipoEvento => $quantidade): ?>

                            <?php
                            $resultados = [];
                            $percentual = ($totalNaoAtendidas > 0) ? round(($quantidade / $totalNaoAtendidas) * 100, 2) : 0;
                            $resultados[$tipoEvento] = [
                                'quantidade' => $quantidade,
                                'percentual' => $percentual
                            ];

                            // Define IDs únicos e sanitizados para cada linha de detalhes
                            $idDetailsContainer = 'details-container-' . preg_replace('/[^a-zA-Z0-9-_]/', '_', $tipoEvento);
                            $idDetailsRow = 'details-row-' . preg_replace('/[^a-zA-Z0-9-_]/', '_', $tipoEvento);
                            ?>

                            <?php foreach ($resultados as $tipoEvento => $dados): ?>
                                <tr>
                                    <td><?= htmlspecialchars($tipoEvento); ?></td>
                                    <td><?= htmlspecialchars($dados['quantidade']); ?></td>
                                    <td><?= htmlspecialchars($dados['percentual']); ?>%</td>
                                    <td>
                                        <button class="btn btn-info"
                                            onclick="fetchDetailsNaoAtendidas('<?= htmlspecialchars($tipoEvento); ?>', '<?= htmlspecialchars($idDetailsContainer); ?>')">
                                            Ver Detalhes
                                        </button>
                                    </td>
                                </tr>

                                <tr id="<?= htmlspecialchars($idDetailsRow); ?>" class="details-row" style="display: none;">
                                    <td colspan="4">
                                        <div id="<?= htmlspecialchars($idDetailsContainer); ?>"></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

            <script>
                function fetchDetailsNaoAtendidas(evento, containerId) {
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

                    // Faz a requisição para buscar os detalhes do evento ABANDON
                    fetch('api/process_chamadasnaoatendidaseventodata.php?evento=' + encodeURIComponent(evento))
                        .then(response => response.json())
                        .then(data => {

                            // Verifica se os dados são válidos
                            if (data.error) {
                                container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                            } else if (data.length > 0) {
                                // Gera a tabela dinâmica apenas se houver registros
                                let tableHTML = `
                        <table class="table table-sm" id="detailsTable-${evento}">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Fila</th>
                                    <th>Número</th>
                                    <th>Evento</th>
                                    <th>Tempo de Espera</th>
                                    <th>Posição na Fila</th>
                                </tr>
                            </thead>
                            <tbody>`;

                                // Itera pelos dados e monta as linhas da tabela
                                data.forEach(chamada => {
                                    tableHTML += `
                            <tr>
                                <td>${chamada.data || '-'}</td>
                                <td>${chamada.fila || '-'}</td>
                                <td>${chamada.numero || '-'}</td>
                                <td style="color: red;">${chamada.evento || '-'}</td>
                                <td>${format_time(chamada.wait_time) || '00:00:00'}</td>
                                <td>${chamada.posicao_fila || '-'}</td>
                            </tr>`;
                                });

                                tableHTML += `</tbody></table>`;
                                container.innerHTML = tableHTML;

                                // Inicializa DataTables na tabela gerada
                                $(`#detailsTable-${evento}`).DataTable({
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


            <!-- Divisor -->
            <hr class="soften" id="distribution_by_queue">
            <!-- Fim divisor -->


            <div class="container" id="secao-naoatendidasfila">
                <h3 class="tituloprincipal">Ligações Não Atendidas - Por Fila</h3>
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
                            <th>Não atendidas</th>
                            <th>% Chamadas</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $filas = [];
                        $totalAtendidas = 0;
                        $totalPercentual = 0;

                        // Filtra chamadas não atendidas (ABANDON) e organiza por fila
                        foreach ($data as $chamada) {
                            if (in_array($chamada['event'], ['ABANDON', 'CANCEL', 'CHANUNAVAIL'])) {
                                $fila = $chamada['queuename'];
                                if (!isset($filas[$fila])) {
                                    $filas[$fila] = ['nao_atendidas' => 0];
                                }
                                $filas[$fila]['nao_atendidas']++;
                                $totalAtendidas++;
                            }
                        }

                        // Gera a tabela com os dados processados
                        foreach ($filas as $fila => $metrics):
                            $filaNome = isset($qnames[$fila]) ? htmlspecialchars($qnames[$fila]) : htmlspecialchars($fila);
                            $recebidas = $metrics['nao_atendidas'];
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
                                        onclick="fetchDetailsFilaNaoAtendidas('<?= $fila ?>', '<?= $idDetailsContainer ?>')">
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
                function fetchDetailsFilaNaoAtendidas(fila, containerId) {
                    const container = document.getElementById(containerId);

                    if (!container) {
                        console.error('Container não encontrado:', containerId);
                        return;
                    }

                    console.log("Fila recebida:", fila);

                    // Sanitiza o nome da fila para evitar problemas no ID
                    const sanitizedFila = fila.replace(/[^a-zA-Z0-9-_]/g, '_'); // Remove caracteres especiais

                    // Verifica se os detalhes já foram carregados
                    if (container.innerHTML.trim()) {
                        const detailsRow = container.parentElement.parentElement;
                        detailsRow.style.display = detailsRow.style.display === 'none' ? '' : 'none';
                        return;
                    }

                    fetch('api/process_chamadasnaoatendidasfiladata.php?fila=' + encodeURIComponent(fila))
                        .then(response => response.json())
                        .then(data => {

                            // Verifica se os dados retornaram corretamente
                            if (!data || Object.keys(data).length === 0) {
                                container.innerHTML = `<div class="alert alert-warning">Nenhum dado encontrado.</div>`;
                                return;
                            }

                            // Verifica se a fila existe nos dados
                            if (!data[fila]) {
                                container.innerHTML = `<div class="alert alert-warning">Nenhum dado encontrado para a fila ${fila}.</div>`;
                                return;
                            }

                            let chamadas = data[fila]; // Obtém as chamadas da fila específica

                            let tableHTML = `
            <table class="table table-sm" id="detailsTable-${sanitizedFila}">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Número</th>
                        <th>Evento</th>
                        <th>Tempo esperado até a finalização</th>
                    </tr>
                </thead>
                <tbody>`;

                            chamadas.forEach(chamada => {
                                tableHTML += `
                <tr>
                    <td>${chamada.data || '-'}</td>
                    <td>${chamada.numero || '-'}</td>
                    <td style="color: ${chamada.evento === 'COMPLETECALLER' || chamada.evento === 'COMPLETEAGENT' ? 'green' : 'red'};">
                        ${chamada.evento || '-'}
                    </td>
                    <td>00:00:00 </td>
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
                        })
                        .catch(error => {
                            console.error('Erro ao buscar detalhes:', error);
                            container.innerHTML = `<div class="alert alert-danger">Erro ao carregar os detalhes.</div>`;
                        });
                }

            </script>
</body>

<?php include_once 'footer.php' ?>