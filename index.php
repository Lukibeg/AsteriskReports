<?php

require_once 'api/process_data.php'; // Dados vindos de $_SESSION como filas selecionadas e período selecionado.
!empty($_SESSION['filas']) ? $queues = $_SESSION['filas'] : '';
!empty($_SESSION['startDate']) ? $startDate = $_SESSION['startDate'] : '';
!empty($_SESSION['endDate']) ? $endDate = $_SESSION['endDate'] : '';
!empty($_SESSION['startTime'] ? $startTime = $_SESSION['startTime'] : '');
!empty($_SESSION['endTime'] ? $endTime = $_SESSION['endTime'] : '');


if ($_SESSION['fetch_data'] == null) {
    header('Location: nodataempty.php');
}
?>
<?php include_once('header.php'); ?>

<body>
    <!-------------------------------------------------------------------------------------------------------------------- RESUMO GERAL -------------------------------------------------------------------------------------------------------------------->
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


                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">Total de Ligações</div>
                            <div class="card-body">
                                <table class="table">
                                    <tbody>
                                        <tr class="bg-light">
                                            <td>Ligações Recebidas:</td>
                                            <td>
                                                <?= isset($metrics) ? htmlspecialchars($metrics['total_calls']) . ' Ligações Recebidas' : '0 Ligações recebidas'; ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Atendidas:</td>
                                            <td>
                                                <?= isset($metrics) ? htmlspecialchars($metrics['total_connects']) . ' Ligações Atendidas' : '0 Ligações atendidas'; ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Ligações Abandonadas:</td>
                                            <td>
                                                <?= isset($metrics) ? htmlspecialchars($metrics['total_abandons']) . ' Ligações Abandonadas' : '0 Ligações Abandonadas'; ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Ligações Transferidas:</td>
                                            <td>
                                                <?= isset($metrics) ? htmlspecialchars($metrics['total_transfers']) . ' Ligações Transferidas' : '0 Ligações transferidas'; ?>

                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Percentual de Abandono:</td>
                                            <td>
                                                <?php if (isset($metrics['total_abandons']) && isset($metrics['total_calls']) != 0): ?>
                                                    <?php
                                                    $percentualDeAbandono = ($metrics['total_abandons'] / $metrics['total_calls']) * 100;
                                                    $percentualAbandonoFormatado = number_format($percentualDeAbandono, 1);
                                                    echo $percentualAbandonoFormatado . '%'; ?>
                                                <?php else: ?>
                                                    0%
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <hr class="footer-divider">
                </div>

                <?php require_once 'api/process_chartinfo.php'; ?>



                <!-------------------------------------------------------------------------------------------------------------------- FIM DO RESUMO GERAL -------------------------------------------------------------------------------------------------------------------->



                <!-------------------------------------------------------------------------------------------------------------------- LIGAÇÕES POR FILA -------------------------------------------------------------------------------------------------------------------->

                <div class="container" id="secao-fila">
                    <h3 class="tituloprincipal">Ligações por Fila</h3>

                    <!-- Botões de Exportação -->
                    <div class="export-buttons">
                        <!-- Botão de CSV -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_fila.php?format=csv">
                            <img src="img/exportcsvicon.png" alt="Exportar CSV">
                            <div class="legenda">CSV</div>
                        </a>

                        <!-- Botão de PDF -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_fila.php?format=pdf">
                            <img src="img/exportpdficon.png" alt="Exportar PDF">
                            <div class="legenda">PDF</div>
                        </a>

                        <!-- Botão de XLSX -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_fila.php?format=xlsx">
                            <img src="img/xlsx.png" alt="Exportar XLSX">
                            <div class="legenda">XLSX</div>
                        </a>
                    </div>

                    <div class="tabela-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fila</th>
                                    <th>Recebidas</th>
                                    <th>Atendidas</th>
                                    <th>Abandonadas</th>
                                    <th>Transferidas</th>
                                    <th>TME</th>
                                    <th>TMA</th>
                                    <th>% Atendidas</th>
                                    <th>% Não Atendidas</th>
                                    <th>SLA</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($_SESSION['dadosPorFila'])): ?>
                                    <?php foreach ($_SESSION['dadosPorFila'] as $fila => $metrics): ?>
                                        <?php $sanitizedId = preg_replace('/[^a-zA-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $fila)); ?>
                                        <tr id="row-<?= $sanitizedId; ?>">
                                            <td> <?= isset($qnames[$fila]) ? htmlspecialchars($qnames[$fila]) : htmlspecialchars($fila); ?>
                                            </td>
                                            <td><?= $metrics['total_ligacoes_recebidas']; ?></td>
                                            <td><?= $metrics['total_ligacoes_atendidas']; ?></td>
                                            <td><?= $metrics['total_ligacoes_abandonadas']; ?></td>
                                            <td><?= $metrics['total_ligacoes_transferidas']; ?></td>
                                            <td><?= $metrics['tme']; ?></td>
                                            <td><?= $metrics['tmc']; ?></td>
                                            <td><?= round($metrics['percentual_atendidas'], 2); ?>%</td>
                                            <td><?= round($metrics['percentual_nao_atendidas'], 2); ?>%</td>
                                            <td><?= round($metrics['sla'], 2); ?>%</td>
                                            <td>
                                                <button class="btn btn-info"
                                                    onclick="toggleDetailsQueue(this, '<?= $sanitizedId; ?>')">Ver
                                                    Detalhes</button>
                                            </td>
                                        </tr>
                                        <tr id="details-<?= $sanitizedId; ?>" class="details-row" style="display:none;">
                                            <td colspan="12">
                                                <div id="details-container-<?= $sanitizedId; ?>"></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12">Nenhum dado encontrado</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    function toggleDetailsQueue(button, uniqueId) {
                        const detailsRow = document.getElementById(`details-${uniqueId}`);
                        const detailsContainer = document.getElementById(`details-container-${uniqueId}`);

                        if (!detailsRow || !detailsContainer) {
                            console.error(`Elementos com ID details-${uniqueId} ou details-container-${uniqueId} não encontrados.`);
                            return;
                        }

                        if (detailsRow.style.display === 'none') {
                            // Exibe os detalhes
                            detailsRow.style.display = '';

                            if (!detailsContainer.innerHTML.trim()) {
                                // Faz uma requisição AJAX para buscar os detalhes
                                $.ajax({
                                    url: 'api/process_queuedata.php',
                                    method: 'GET',
                                    data: {
                                        fila: uniqueId
                                    },
                                    dataType: 'json',
                                    success: function(data) {
                                        if (data.error) {
                                            // Exibe mensagem de erro, se aplicável
                                            detailsContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                                        } else {
                                            // Cria um ID único para a tabela dinâmica
                                            const tableId = `table-details-${uniqueId}`;

                                            // Adiciona o mapeamento de nomes amigáveis
                                            const friendlyNames = <?= json_encode($friendlyNames); ?>; // Mapeamento de nomes amigáveis

                                            // Gera o HTML para a tabela
                                            let detalhesHTML = `
                            <table class="table table-sm display" id="${tableId}">
                                <thead>
                                    <tr>
                                        <th>Agente</th>
                                        <th>Data</th>
                                        <th>Fila</th>
                                        <th>Número</th>
                                        <th>Evento</th>
                                        <th>Tempo de Toque</th>
                                        <th>Tempo Esperado</th>
                                        <th>Tempo Conversando</th>
                                        <th>Gravação</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                                            data.forEach(chamada => {
                                                // Substitui pelo nome amigável, se disponível
                                                const filaAmigavel = friendlyNames[chamada.fila] || chamada.fila;

                                                detalhesHTML += `
                                <tr>
                                    <td>${chamada.agente || '-'}</td>
                                    <td>${chamada.data || '-'}</td>
                                    <td>${filaAmigavel}</td>
                                    <td>${chamada.numero || '-'}</td>
                                    <td style="color: ${chamada.evento === 'COMPLETECALLER' || chamada.evento === 'COMPLETEAGENT' ? 'green' : 'red'};">
                                    ${chamada.evento || '-'}
                                    </td> 
                                    <td>${format_time(chamada.ringtime) || '-'}</td>
                                    <td>${format_time(chamada.wait_time) || '-'}</td>
                                    <td>${format_time(chamada.talk_time) || '-'}</td>
                                    <td>
                                        <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">Carregar gravação</button>
                                        <div class="audio-container"></div>
                                    </td>
                                </tr>`;
                                            });

                                            detalhesHTML += `</tbody></table>`;
                                            detailsContainer.innerHTML = detalhesHTML;

                                            // Inicializa o DataTables para a nova tabela
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
                                    error: function(xhr, status, error) {
                                        // Exibe mensagem de erro no container
                                        detailsContainer.innerHTML = `<div class="alert alert-danger">Erro ao carregar os detalhes: ${error}</div>`;
                                    },
                                });
                            }
                        } else {
                            // Oculta os detalhes
                            detailsRow.style.display = 'none';
                        }
                    }
                </script>


                <!-------------------------------------------------------------------------------------------------------------------- Representação Gráfica -------------------------------------------------------------------------------------------------------------------->
                <div class="container mt-5">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="ligacoesAtendidasChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="temposMediosChart"></canvas>
                        </div>
                    </div>
                </div>

                <div id="chartContainerPorFila"
                    data-labels="<?= htmlspecialchars(json_encode(array_keys($metricasPorFila))); ?>"
                    data-atendidas="<?= htmlspecialchars(json_encode(array_column($metricasPorFila, 'total_ligacoes_atendidas'))); ?>"
                    data-abandonadas="<?= htmlspecialchars(json_encode(array_column($metricasPorFila, 'total_ligacoes_abandonadas'))); ?>"
                    data-tme="<?= htmlspecialchars(json_encode(array_column($metricasPorFila, 'tme'))); ?>"
                    data-tma="<?= htmlspecialchars(json_encode(array_column($metricasPorFila, 'tma'))); ?>">
                </div>

            </div>
            <!-------------------------------------------------------------------------------------------------------------------- Fim da Representação Gráfica -------------------------------------------------------------------------------------------------------------------->


            <!-------------------------------------------------------------------------------------------------------------------- FIM LIGAÇÕES POR FILA -------------------------------------------------------------------------------------------------------------------->



            <!-- Divisor -->
            <hr class="soften" id="distribution_by_queue">
            <!-- Fim divisor -->




            <!-------------------------------------------------------------------------------------------------------------------- LIGAÇÕES DE SAÍDA -------------------------------------------------------------------------------------------------------------------->
            <div class="container" id="secao-saida">
                <h3 class="tituloprincipal">Ligações de Saída</h3>

                <!-- Botões de Exportação -->
                <div class="export-buttons">
                    <!-- Botão de CSV -->
                    <a target="_blank" href="relatorios/relatorios_ligacoes_saida.php?format=csv">
                        <img src="img/exportcsvicon.png" alt="Exportar CSV">
                        <div class="legenda">CSV</div>
                    </a>

                    <!-- Botão de PDF -->
                    <a target="_blank" href="relatorios/relatorios_ligacoes_saida.php?format=pdf">
                        <img src="img/exportpdficon.png" alt="Exportar PDF">
                        <div class="legenda">PDF</div>
                    </a>

                    <!-- Botão de XLSX -->
                    <a target="_blank" href="relatorios/relatorios_ligacoes_saida.php?format=xlsx">
                        <img src="img/xlsx.png" alt="Exportar XLSX">
                        <div class="legenda">XLSX</div>
                    </a>
                </div>

                <div class="tabela-container">
                    <table class="table" id="ligacoesdesaida">
                        <thead>
                            <tr>
                                <th>Fila</th>
                                <th>Total Efetuadas</th>
                                <th>Atendidas</th>
                                <th>Abandonadas</th>
                                <th>Transferidas</th>
                                <th>Tempo Médio Espera (TME)</th>
                                <th>Tempo Médio Atendimento (TMA)</th>
                                <th>% Atendidas</th>
                                <th>% Não Atendidas</th>
                                <th>SLA</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($_SESSION['outboundMetrics'])): ?>
                                <?php $metrics = $_SESSION['outboundMetrics']; ?>
                                <tr>
                                    <td>Ligações Efetuadas</td>
                                    <td><?= $metrics['total_ligacoes_recebidas']; ?></td>
                                    <td><?= $metrics['total_ligacoes_atendidas']; ?></td>
                                    <td><?= $metrics['total_ligacoes_abandonadas']; ?></td>
                                    <td><?= $metrics['total_ligacoes_transferidas']; ?></td>
                                    <td><?= $metrics['tme']; ?></td>
                                    <td><?= $metrics['tmc']; ?></td>
                                    <td><?= round($metrics['percentual_atendidas'], 2); ?>%</td>
                                    <td><?= round($metrics['percentual_nao_atendidas'], 2); ?>%</td>
                                    <td><?= round($metrics['sla'], 2); ?>%</td>
                                    <td>
                                        <button class="btn btn-info" onclick="toggleDetailsOutcalls(this)">
                                            Ver Detalhes
                                        </button>

                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12">Nenhum dado encontrado para Ligações Efetuadas.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>


            <script>
                function toggleDetailsOutcalls(button) {
                    const detailsRowId = 'details-outcalls';
                    const detailsContainerId = 'details-container-outcalls';

                    // Verifica se a linha e o container já existem
                    let detailsRow = document.getElementById(detailsRowId);
                    let detailsContainer = document.getElementById(detailsContainerId);

                    // Se a linha não existe, cria dinamicamente
                    if (!detailsRow) {
                        detailsRow = document.createElement('tr');
                        detailsRow.id = detailsRowId;

                        detailsContainer = document.createElement('td');
                        detailsContainer.id = detailsContainerId;
                        detailsContainer.colSpan = 11; // Define o colspan para ocupar toda a largura da tabela

                        detailsRow.appendChild(detailsContainer);
                        button.closest('tr').parentNode.appendChild(detailsRow);
                    }

                    // Alternar entre exibir e ocultar
                    if (detailsRow.style.display === 'none') {
                        detailsRow.style.display = '';

                        if (!detailsContainer.innerHTML.trim()) {
                            // Requisição AJAX para carregar os detalhes
                            $.ajax({
                                url: 'api/process_outcallsdata.php',
                                method: 'GET',
                                data: {
                                    detalhes: true
                                },
                                dataType: 'json',
                                success: function(data) {
                                    if (data.error) {
                                        detailsContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                                    } else {
                                        // Gera o HTML da tabela
                                        const tableId = 'table-details-outcalls';
                                        let detalhesHTML = `
                            <table class="table table-sm table-bordered display" id="${tableId}" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Agente</th>
                                        <th>Data</th>
                                        <th>Número</th>
                                        <th>Evento</th>
                                        <th>Tempo de Toque</th>
                                        <th>Tempo de Espera</th>
                                        <th>Tempo de Conversação</th>
                                        <th>Gravação</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                                        data.forEach(chamada => {
                                            detalhesHTML += `
                                <tr>
                                    <td>${chamada.agente || '-'}</td>
                                    <td>${chamada.data}</td>
                                    <td>${chamada.numero || '-'}</td>
                                    <td style="color: ${['COMPLETECALLER', 'COMPLETEAGENT'].includes(chamada.evento) ? 'green' : 'red'};">
                                        ${chamada.evento || '-'}
                                    </td>
                                    <td>${format_time(chamada.ringtime) || '00:00:00'}</td>
                                    <td>${format_time(chamada.wait_time) || '00:00:00'}</td>
                                    <td>${format_time(chamada.talk_time) || '00:00:00'}</td>
                                    <td>
                                        <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">Carregar gravação</button>
                                        <div class="audio-container"></div>
                                    </td>
                                </tr>`;
                                        });

                                        detalhesHTML += `</tbody></table>`;
                                        detailsContainer.innerHTML = detalhesHTML;

                                        // Inicializa o DataTables
                                        $(`#${tableId}`).DataTable({
                                            responsive: true,
                                            paging: true,
                                            ordering: true,
                                            info: true,
                                            searching: true,
                                            pageLength: 5,
                                            language: {
                                                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json'
                                            },
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    detailsContainer.innerHTML = `<div class="alert alert-danger">Erro ao carregar os detalhes: ${error}</div>`;
                                },
                            });
                        }
                    } else {
                        // Oculta a linha de detalhes
                        detailsRow.style.display = 'none';
                        detailsContainer.innerHTML = ''; // Limpa o conteúdo da tabela
                    }
                }
            </script>


            <!-------------------------------------------------------------------------------------------------------------------- FIM LIGAÇÕES DE SAÍDA -------------------------------------------------------------------------------------------------------------------->




            <!-- Divisor -->
            <hr class="soften" id="distribution_by_queue">
            <!-- Fim divisor -->



            <!-------------------------------------------------------------------------------------------------------------------- LIGAÇÕES POR MÊS -------------------------------------------------------------------------------------------------------------------->
            <div class="container" id="secao-mes">
                <h3 class="tituloprincipal">Ligações por Mês</h3>

                <!-- Botões de Exportação -->
                <div class="export-buttons">
                    <a target="_blank" href="relatorios/relatorios_ligacoes_mes.php?format=csv">
                        <img src="img/exportcsvicon.png" alt="Exportar CSV">
                        <div class="legenda">CSV</div>
                    </a>

                    <a target="_blank" href="relatorios/relatorios_ligacoes_mes.php?format=pdf">
                        <img src="img/exportpdficon.png" alt="Exportar PDF">
                        <div class="legenda">PDF</div>
                    </a>

                    <a target="_blank" href="relatorios/relatorios_ligacoes_mes.php?format=xlsx">
                        <img src="img/xlsx.png" alt="Exportar XLSX">
                        <div class="legenda">XLSX</div>
                    </a>
                </div>

                <div class="tabela-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mês/Ano</th>
                                <th>Total Recebidas</th>
                                <th>Atendidas</th>
                                <th>Abandonadas</th>
                                <th>Transferidas</th>
                                <th>Tempo Médio Espera (TME)</th>
                                <th>Tempo Médio Atendimento (TMA)</th>
                                <th>% Atendidas</th>
                                <th>% Não Atendidas</th>
                                <th>SLA</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($_SESSION['dadosPorMes'])): ?>
                                <?php foreach ($_SESSION['dadosPorMes'] as $mesAno => $metrics): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($mesAno); ?></td>
                                        <td><?= $metrics['total_ligacoes_recebidas']; ?></td>
                                        <td><?= $metrics['total_ligacoes_atendidas']; ?></td>
                                        <td><?= $metrics['total_ligacoes_abandonadas']; ?></td>
                                        <td><?= $metrics['total_ligacoes_transferidas']; ?></td>
                                        <td><?= $metrics['tme']; ?></td>
                                        <td><?= $metrics['tmc']; ?></td>
                                        <td><?= round($metrics['percentual_atendidas'], 2) . '%'; ?></td>
                                        <td><?= round($metrics['percentual_nao_atendidas'], 2) . '%'; ?></td>
                                        <td><?= round($metrics['sla'], 2) . '%'; ?></td>
                                        <td>
                                            <button class="btn btn-info"
                                                onclick="toggleMonthDetails(this, '<?= $mesAno; ?>')">Ver
                                                Detalhes</button>

                                        </td>
                                    </tr>
                                    <tr id="details-<?= $mesAno; ?>" style="display:none;">
                                        <td colspan="12">
                                            <div id="details-container-<?= $mesAno; ?>"></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12">Nenhum dado encontrado</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>


                <script>
                    function toggleMonthDetails(button, mesAno) {
                        const detailsRow = document.getElementById(`details-${mesAno}`);
                        const detailsContainer = document.getElementById(`details-container-${mesAno}`);

                        if (!detailsRow || !detailsContainer) {
                            console.error(`Elementos com ID details-${mesAno} ou details-container-${mesAno} não encontrados.`);
                            return;
                        }

                        if (detailsRow.style.display === 'none') {
                            detailsRow.style.display = '';

                            if (!detailsContainer.innerHTML.trim()) {
                                $.ajax({
                                    url: 'api/process_monthdata.php',
                                    method: 'GET',
                                    data: {
                                        mesAno: mesAno
                                    },
                                    dataType: 'json',
                                    success: function(data) {
                                        if (data.error) {
                                            detailsContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                                            console.log('mesAno enviado para o backend:', mesAno);
                                        } else {
                                            const friendlyNames = <?= json_encode($friendlyNames); ?>; // Nomes amigáveis para as filas
                                            const uniqueTableId = `table-details-${mesAno.replace(/[^a-zA-Z0-9]/g, '_')}`;
                                            let detalhesHTML = `<table class="table table-sm display" id="${uniqueTableId}">`;
                                            detalhesHTML += `
                            <thead>
                                <tr>
                                    <th>Agente</th>
                                    <th>Data</th>
                                    <th>Fila</th>
                                    <th>Número</th>
                                    <th>Evento</th>
                                    <th>Tempo de Toque</th>
                                    <th>Tempo Esperado</th>
                                    <th>Tempo Conversando</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>`;

                                            data.forEach(chamada => {
                                                const filaAmigavel = friendlyNames[chamada.queuename] || chamada.queuename; // Usa nome amigável ou padrão
                                                detalhesHTML += `
                                <tr>
                                    <td>${chamada.agent || '-'}</td>
                                    <td>${chamada.combined_time || '-'}</td>
                                    <td>${filaAmigavel}</td>
                                    <td>${chamada.callerid || '-'}</td>
                                    <td style="color: ${chamada.event === 'COMPLETECALLER' || chamada.event === 'COMPLETEAGENT' ? 'green' : 'red'};">
                                        ${chamada.event || '-'}
                                    </td> 
                                    <td>${format_time(chamada.ringtime) || '-'}</td>
                                    <td>${format_time(chamada.wait_time) || '-'}</td>
                                    <td>${format_time(chamada.call_time) || '-'}</td>
                                    <td>
                                        <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">Carregar gravação</button>
                                        <div class="audio-container"></div>
                                    </td>
                                </tr>`;
                                            });

                                            detalhesHTML += '</tbody></table>';
                                            detailsContainer.innerHTML = detalhesHTML;

                                            // Inicializa o DataTables após a tabela ser inserida no DOM
                                            $(`#${uniqueTableId}`).DataTable({
                                                responsive: true,
                                                paging: true,
                                                ordering: true,
                                                info: true,
                                                searching: true,
                                                language: {
                                                    url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json' // Tradução para português
                                                },
                                                pageLength: 5 // Número de registros por página
                                            });
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        detailsContainer.innerHTML = `<div class="alert alert-danger">Erro ao carregar os detalhes: ${error}</div>`;
                                    }
                                });
                            }
                        } else {
                            detailsRow.style.display = 'none';
                        }
                    }
                </script>

                <!-------------------------------------------------------------------------------------------------------------------- FIM DE LIGAÇÕES POR MÊS -------------------------------------------------------------------------------------------------------------------->

                <!-- Divisor -->
                <hr class="soften" id="distribution_by_queue">
                <!-- Fim divisor -->

                <!-------------------------------------------------------------------------------------------------------------------- LIGAÇÕES POR SEMANA -------------------------------------------------------------------------------------------------------------------->

                <div class="container" id="secao-semana">
                    <h3 class="tituloprincipal">Ligações por Semana</h3>

                    <!-- Botões de Exportação -->
                    <div class="export-buttons">
                        <!-- Botão de CSV -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_semana.php?format=csv">
                            <img src="img/exportcsvicon.png" alt="Exportar CSV">
                            <div class="legenda">CSV</div>
                        </a>

                        <!-- Botão de PDF -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_semana.php?format=pdf">
                            <img src="img/exportpdficon.png" alt="Exportar PDF">
                            <div class="legenda">PDF</div>
                        </a>

                        <!-- Botão de XLSX -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_semana.php?format=xlsx">
                            <img src="img/xlsx.png" alt="Exportar XLSX">
                            <div class="legenda">XLSX</div>
                        </a>
                    </div>

                    <div class="tabela-container">
                        <table id="ligacoes-semana" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Semana</th>
                                    <th>Recebidas</th>
                                    <th>Atendidas</th>
                                    <th>Abandonadas</th>
                                    <th>Transferidas</th>
                                    <th>TME</th>
                                    <th>TMA</th>
                                    <th>% Atendidas</th>
                                    <th>% Não Atendidas</th>
                                    <th>SLA</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($_SESSION['dadosPorSemana'])): ?>
                                    <?php foreach ($_SESSION['dadosPorSemana'] as $weekYear => $metrics): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($weekYear); ?></td>
                                            <td><?= $metrics['recebidas']; ?></td>
                                            <td><?= $metrics['atendidas']; ?></td>
                                            <td><?= $metrics['abandonadas']; ?></td>
                                            <td><?= $metrics['transferidas']; ?></td>
                                            <td><?= gmdate('H:i:s', $metrics['tme']); ?></td>
                                            <td><?= gmdate('H:i:s', $metrics['tma']); ?></td>
                                            <td><?= number_format($metrics['percent_atendidas'], 2); ?>%</td>
                                            <td><?= number_format($metrics['percent_nao_atendidas'], 2); ?>%</td>
                                            <td><?= number_format($metrics['sla'], 2); ?>%</td>
                                            <td>
                                                <button class="btn btn-info" onclick="toggleDetailsWeek(this)">Ver
                                                    Detalhes</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="13">Nenhum dado encontrado</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////// GERANDO TABELAS POR SEMANA //////////////////////////////////////////////////////////////////////////////////////////////////////////////

                    function toggleDetailsWeek(button) {
                        const tr = $(button).closest('tr');
                        const row = $('#ligacoes-semana').DataTable().row(tr);

                        if (row.child.isShown()) {
                            // Fecha os detalhes
                            row.child.hide();
                            tr.removeClass('shown');
                        } else {
                            // Obtém a semana (ano-semana) da primeira célula
                            const weekYear = tr.find('td:first').text();
                            const uniqueId = weekYear.replace(/[^a-zA-Z0-9]/g, '_'); // Gera um ID único baseado na semana

                            // Faz uma requisição AJAX para buscar os detalhes
                            $.ajax({
                                url: 'api/process_weekdata.php',
                                method: 'GET',
                                data: {
                                    week: weekYear
                                },
                                dataType: 'json',
                                success: function(data) {
                                    if (data.error) {
                                        alert(data.error);
                                    } else {
                                        const friendlyNames = <?= json_encode($friendlyNames); ?>; // Nomes amigáveis

                                        let detalhesHTML = `
                                                            <table class="table table-sm ligacoes-semana-detalhes-table" id="ligacoes-semana-detalhe-${uniqueId}">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Agente</th>
                                                                        <th>Data</th>
                                                                        <th>Fila</th>
                                                                        <th>Número</th>
                                                                        <th>Evento</th>
                                                                        <th>Tempo de Toque</th>
                                                                        <th>Tempo Esperado</th>
                                                                        <th>Tempo Conversando</th>
                                                                        <th>Gravação</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>`;

                                        data.forEach(function(chamada) {
                                            const filaAmigavel = friendlyNames[chamada.queuename] || chamada.queuename; // Substitui pelo nome amigável
                                            detalhesHTML += `
                                                                <tr>
                                                                    <td>${chamada.agent || '-'}</td>
                                                                    <td>${formatDate(chamada.combined_time)}</td>
                                                                    <td>${filaAmigavel}</td>
                                                                    <td>${chamada.callerid}</td>
                                                                    <td style="color: ${chamada.event === 'COMPLETECALLER' || chamada.event === 'COMPLETEAGENT' ? 'green' : 'red'};">
                                                                    ${chamada.event || '-'}
                                                                    </td> 
                                                                    <td>${format_time(chamada.ringtime)}</td>
                                                                    <td>${format_time(chamada.wait_time)}</td>
                                                                    <td>${format_time(chamada.call_time)}</td>
                                                                    <td>
                                                                        <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">Carregar gravação</button>
                                                                        <div class="audio-container"></div>
                                                                    </td>
                                                                </tr>`;
                                        });

                                        detalhesHTML += `</tbody></table>`;

                                        // Abre os detalhes
                                        row.child(detalhesHTML).show();
                                        tr.addClass('shown');

                                        // Inicializa o DataTables na tabela de detalhes
                                        $(`#ligacoes-semana-detalhe-${uniqueId}`).DataTable({
                                            responsive: true,
                                            paging: true,
                                            ordering: true,
                                            info: true,
                                            searching: true,
                                            pageLength: 5,
                                            language: {
                                                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json'
                                            }
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Erro na requisição AJAX:', error);
                                    alert('Erro ao carregar os detalhes da semana.');
                                }
                            });
                        }
                    }

                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////// FIM DA GERAÇÃO DE TABELAS POR SEMANA //////////////////////////////////////////////////////////////////////////////////////////////////////////////
                </script>


                <!-------------------------------------------------------------------------------------------------------------------- FIM DE LIGAÇÕES POR SEMANA -------------------------------------------------------------------------------------------------------------------->




                <!-- Divisor -->
                <hr class="soften" id="distribution_by_queue">
                <!-- Fim divisor -->


                <!-------------------------------------------------------------------------------------------------------------------- LIGAÇÕES POR DIA -------------------------------------------------------------------------------------------------------------------->


                <div class="container" id="secao-dia">

                    <h3 class="tituloprincipal">Ligações por Dia</h3>

                    <!-- Botões de Exportação -->
                    <div class="export-buttons">
                        <a target="_blank" href="relatorios/relatorios_ligacoes_dia.php?format=csv">
                            <img src="img/exportcsvicon.png" alt="Exportar CSV">
                            <div class="legenda">CSV</div>
                        </a>

                        <a target="_blank" href="relatorios/relatorios_ligacoes_dia.php?format=pdf">
                            <img src="img/exportpdficon.png" alt="Exportar PDF">
                            <div class="legenda">PDF</div>
                        </a>

                        <a target="_blank" href="relatorios/relatorios_ligacoes_dia.php?format=xlsx">
                            <img src="img/xlsx.png" alt="Exportar XLSX">
                            <div class="legenda">XLSX</div>
                        </a>
                    </div>



                    <!-- Exibir dados por dia -->
                    <div class="tabela-container">
                        <table class="table table-striped table-bordered" id="ligacoes-dia">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Recebidas</th>
                                    <th>Atendidas</th>
                                    <th>Abandonadas</th>
                                    <th>Transferidas</th>
                                    <th>TME</th>
                                    <th>TMA</th>
                                    <th>SLA</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($_SESSION['dadosPorDia'])): ?>
                                    <?php foreach ($_SESSION['dadosPorDia'] as $date => $metrics): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($date); ?></td>
                                            <td><?= $metrics['recebidas']; ?></td>
                                            <td><?= $metrics['atendidas']; ?></td>
                                            <td><?= $metrics['abandonadas']; ?></td>
                                            <td><?= $metrics['transferidas']; ?></td>
                                            <td><?= $metrics['tme']; ?></td>
                                            <td><?= $metrics['tma']; ?></td>
                                            <td><?= number_format($metrics['sla'], 2); ?>%</td>
                                            <td>
                                                <button class="btn btn-info" onclick="toggleDetailsDay(this)">Ver
                                                    Detalhes</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9">Nenhum dado encontrado</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    function toggleDetailsDay(button) {
                        let tr = $(button).closest('tr');
                        let row = $('#ligacoes-dia').DataTable().row(tr);

                        if (row.child.isShown()) {
                            row.child.hide();
                            tr.removeClass('shown');
                        } else {
                            let date = row.data()[0];

                            $.ajax({
                                url: 'api/process_daydata.php',
                                method: 'GET',
                                data: {
                                    date: date
                                },
                                success: function(data) {
                                    const registros = JSON.parse(data);
                                    if (registros.error) {
                                        alert(registros.error);
                                        return;
                                    }

                                    // Gera o HTML da tabela de detalhes
                                    let detalhesHTML = getDetalhesDiaHTML(date, registros);
                                    row.child(detalhesHTML).show();
                                    tr.addClass('shown');


                                    let detalheTableId = `#ligacoes-dia-detalhe-${date.replace(/[^a-zA-Z0-9]/g, '')}`;
                                    initializeDetailsTable(detalheTableId);
                                },
                                error: function() {
                                    alert('Erro ao carregar os detalhes.');
                                }
                            });
                        }
                    }



                    function getDetalhesDiaHTML(date, registros) {
                        const uniqueId = date.replace(/[^a-zA-Z0-9]/g, '');
                        const friendlyNames = <?= json_encode($friendlyNames); ?>; // Mapeamento de nomes amigáveis

                        let detalhesHTML = `
        <table class="table table-sm ligacoes-dia-detalhes-table" id="ligacoes-dia-detalhe-${uniqueId}">
            <thead>
                <tr>
                    <th>Agente</th>
                    <th>Data</th>
                    <th>Fila</th>
                    <th>Número</th>
                    <th>Evento</th>
                    <th>Tempo de Toque</th>
                    <th>Tempo Esperado</th>
                    <th>Tempo Conversando</th>
                    <th>Gravação</th>
                </tr>
            </thead>
            <tbody>`;

                        registros.forEach(function(chamada) {
                            const filaAmigavel = friendlyNames[chamada.fila] || chamada.fila;

                            detalhesHTML += `
            <tr>
                <td>${chamada.agent || '-'}</td>
                <td>${formatDate(chamada.data) || '-'}</td>
                <td>${filaAmigavel}</td>
                <td>${chamada.numero || '-'}</td>
                <td style="color: ${chamada.evento === 'COMPLETECALLER' || chamada.evento === 'COMPLETEAGENT' ? 'green' : 'red'};">
                        ${chamada.evento || '-'}
                </td> 
                <td>${chamada.ringtime || '-'}</td>
                <td>${chamada.wait_time || '-'}</td>
                <td>${chamada.call_time || '-'}</td>
                <td>
                    <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">Carregar gravação</button>
                    <div class="audio-container"></div>
                </td>
            </tr>`;
                        });

                        detalhesHTML += `</tbody></table>`;
                        return detalhesHTML;
                    }

                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////// FIM GERAÇÃO DAS TABELAS POR DIA //////////////////////////////////////////////////////////////////////////////////////////////////////////////
                </script>

                <!--------------------------------------------------------------------------------------------GERA AS TABELAS POR DIA E POR HORA. NÃO MEXA SE NÃO SOUBER O QUE ESTIVER FAZENDO. --------------------------------------------------------------------------------------------->
                <!-------------------------------------------------------------------------------------------------------------------- REPRESENTAÇÃO GRÁFICA -------------------------------------------------------------------------------------------------------------------->

                <div class="container mt-5">
                    <div class="row">
                        <div class="col-md-12">
                            <canvas id="ligacoesPorDiaChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-------------------------------------------------------------------------------------------------------------------- FIM DA REPRESENTAÇÃO GRÁFICA -------------------------------------------------------------------------------------------------------------------->


                <!-------------------------------------------------------------------------------------------------------------------- FIM DE LIGAÇÕES POR DIA -------------------------------------------------------------------------------------------------------------------->



                <!-- Divisor -->
                <hr class="soften" id="distribution_by_queue">
                <!-- Fim divisor -->



                <!-------------------------------------------------------------------------------------------------------------------- LIGAÇÕES POR HORA -------------------------------------------------------------------------------------------------------------------->

                <div class="container" id="secao-hora">
                    <h3 class="tituloprincipal">Ligações por Hora</h3>

                    <!-- Botões de Exportação -->
                    <div class="export-buttons">
                        <!-- Botão de CSV -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_hora.php?format=csv">
                            <img src="img/exportcsvicon.png" alt="Exportar CSV">
                            <div class="legenda">CSV</div>
                        </a>

                        <!-- Botão de PDF -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_hora.php?format=pdf">
                            <img src="img/exportpdficon.png" alt="Exportar PDF">
                            <div class="legenda">PDF</div>
                        </a>

                        <!-- Botão de XLSX -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_hora.php?format=xlsx">
                            <img src="img/xlsx.png" alt="Exportar XLSX">
                            <div class="legenda">XLSX</div>
                        </a>
                    </div>

                    <div class="tabela-container">
                        <table class="table" id="ligacoes-hora">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Recebidas</th>
                                    <th>Atendidas</th>
                                    <th>Abandonadas</th>
                                    <th>Transferidas</th>
                                    <th>TME</th>
                                    <th>TMA</th>
                                    <th>% Atendidas</th>
                                    <th>% Não Atendidas</th>
                                    <th>SLA</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($_SESSION['dadosPorHora'])): ?>
                                    <?php foreach ($_SESSION['dadosPorHora'] as $hora => $metrics): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($hora); ?></td>
                                            <td><?= $metrics['total_ligacoes_recebidas']; ?></td>
                                            <td><?= $metrics['total_ligacoes_atendidas']; ?></td>
                                            <td><?= $metrics['total_ligacoes_abandonadas']; ?></td>
                                            <td><?= $metrics['total_ligacoes_transferidas']; ?></td>
                                            <td><?= gmdate('H:i:s', $metrics['total_tme']); ?></td>
                                            <td><?= gmdate('H:i:s', $metrics['total_tma']); ?></td>
                                            <td><?= number_format($metrics['percentual_atendidas'], 2); ?>%</td>
                                            <td><?= number_format($metrics['percentual_nao_atendidas'], 2); ?>%</td>
                                            <td><?= number_format($metrics['sla'], 2); ?>%</td>
                                            <td><button class="btn btn-info" onclick="toggleDetailsHour(this)">Ver
                                                    Detalhes</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="12">Nenhum dado encontrado</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    function toggleDetailsHour(button) {
                        let tr = $(button).closest('tr'); // Encontra a linha correspondente ao botão clicado
                        let dataTable = $('#ligacoes-hora').DataTable(); // Acessa o DataTable

                        if (!dataTable) {
                            console.error('A tabela #ligacoes-hora não foi inicializada corretamente.');
                            return;
                        }

                        let row = dataTable.row(tr); // Captura a linha correspondente no DataTable

                        // Verifique se a linha foi encontrada
                        if (!row || !row.data()) {
                            console.error('Nenhuma linha correspondente encontrada no DataTable.');
                            return;
                        }


                        // Captura o intervalo de hora da primeira coluna
                        let intervaloHora = row.data()[0]; // Assume que a primeira coluna contém o intervalo de hora

                        if (!intervaloHora) {
                            console.error('Intervalo de hora é indefinido ou nulo.');
                            return;
                        }

                        if (row.child.isShown()) {
                            // Fechar a linha de detalhes
                            row.child.hide();
                            tr.removeClass('shown');
                        } else {
                            // Faz a requisição AJAX para buscar os detalhes da hora
                            $.ajax({
                                url: 'api/process_hourdata.php',
                                method: 'GET',
                                data: {
                                    intervaloHora: intervaloHora
                                },
                                dataType: 'json',
                                success: function(data) {
                                    if (data.error) {
                                        row.child(`<div class="alert alert-danger">${data.error}</div>`).show();
                                        tr.addClass('shown');
                                        return;
                                    }

                                    // Gera o HTML para os detalhes
                                    let detalhesHTML = getDetalhesHTML(data.registros, intervaloHora);

                                    // Exibe a linha de detalhes
                                    row.child(detalhesHTML).show();
                                    tr.addClass('shown');

                                    // Inicializa o DataTables na tabela de detalhes
                                    let detalheTableId = `#ligacao-hora-detalhe-${intervaloHora.replace(/[^a-zA-Z0-9]/g, '')}`;
                                    if (!$.fn.DataTable.isDataTable(detalheTableId)) {
                                        $(detalheTableId).DataTable({
                                            paging: true,
                                            searching: true,
                                            ordering: true,
                                            info: true,
                                            pageLength: 5,
                                            language: {
                                                url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json',
                                            },
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    row.child(`<div class="alert alert-danger">Erro ao carregar os dados: ${error}</div>`).show();
                                    tr.addClass('shown');
                                },
                            });
                        }
                    }

                    function getDetalhesHTML(registros, intervaloHora) {
                        const uniqueId = intervaloHora.replace(/[^a-zA-Z0-9]/g, ''); // Gera um ID único
                        let friendlyNames = <?= json_encode($friendlyNames); ?>;


                        if (!registros || registros.length === 0) {
                            return `<div class="alert alert-info">Nenhum registro encontrado para o intervalo de hora ${intervaloHora}.</div>`;

                        }


                        let detalhesHTML = `
                                            <table class="table table-sm detailsTable" id="ligacao-hora-detalhe-${uniqueId}">
                                                <thead>
                                                    <tr>
                                                        <th>Agente</th>
                                                        <th>Data</th>
                                                        <th>Fila</th>
                                                        <th>Número</th>
                                                        <th>Evento</th>
                                                        <th>Tempo de Toque</th>
                                                        <th>Tempo Esperado</th>
                                                        <th>Tempo Conversando</th>
                                                        <th>Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>`;

                        registros.forEach(function(chamada) {
                            let filaAmigavel = friendlyNames[chamada.queuename] || chamada.queuename; // Substitui pelo nome amigável, se existir
                            detalhesHTML += `
                                                <tr>
                                                    <td>${chamada.agent || '-'}</td>
                                                    <td>${chamada.combined_time || '-'}</td>
                                                    <td>${filaAmigavel || '-'}</td>
                                                    <td>${chamada.callerid || '-'}</td>
                                                    <td style="color: ${chamada.event === 'COMPLETECALLER' || chamada.event === 'COMPLETEAGENT' ? 'green' : 'red'};">
                                                    ${chamada.event || '-'}
                                                    </td> 
                                                    <td>${format_time(chamada.ringtime) || '-'}</td>
                                                    <td>${format_time(chamada.wait_time) || '-'}</td>
                                                    <td>${format_time(chamada.call_time) || '-'}</td>
                                                     <td>
                                                        <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">Carregar gravação</button>
                                                        <div class="audio-container"></div>
                                                     </td>
                                                </tr>`;
                        });

                        detalhesHTML += `</tbody></table>`;
                        return detalhesHTML;
                    }
                </script>


                <!-------------------------------------------------------------------------------------------------------------------- REPRESENTAÇÃO GRÁFICA -------------------------------------------------------------------------------------------------------------------->

                <div class="container mt-5">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="ligacoesPorHoraChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="temposPorHoraChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-------------------------------------------------------------------------------------------------------------------- FIM DA REPRESENTAÇÃO GRÁFICA -------------------------------------------------------------------------------------------------------------------->

                <!-------------------------------------------------------------------------------------------------------------------- FIM DE LIGAÇÕES POR HORA -------------------------------------------------------------------------------------------------------------------->



                <!-- Divisor -->
                <hr class="soften" id="distribution_by_queue">
                <!-- Fim divisor -->




                <!-------------------------------------------------------------------------------------------------------------------- LIGAÇÕES POR DIA DA SEMANA -------------------------------------------------------------------------------------------------------------------->
                <div class="container" id="secao-diasemana">
                    <h3 class="tituloprincipal">Ligações por Dia da Semana</h3>

                    <!-- Botões de Exportação -->
                    <div class="export-buttons">
                        <!-- Botão de CSV -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_diasemana.php?format=csv">
                            <img src="img/exportcsvicon.png" alt="Exportar CSV">
                            <div class="legenda">CSV</div>
                        </a>

                        <!-- Botão de PDF -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_diasemana.php?format=pdf">
                            <img src="img/exportpdficon.png" alt="Exportar PDF">
                            <div class="legenda">PDF</div>
                        </a>

                        <!-- Botão de XLSX -->
                        <a target="_blank" href="relatorios/relatorios_ligacoes_diasemana.php?format=xlsx">
                            <img src="img/xlsx.png" alt="Exportar XLSX">
                            <div class="legenda">XLSX</div>
                        </a>
                    </div>

                    <div class="tabela-container">
                        <table id="ligacoes-dia-semana" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Dia da Semana</th>
                                    <th>Recebidas</th>
                                    <th>Atendidas</th>
                                    <th>Abandonadas</th>
                                    <th>Transferidas</th>
                                    <th>Tempo Médio Espera (TME)</th>
                                    <th>Tempo Médio Atendimento (TMA)</th>
                                    <th>% Atendidas</th>
                                    <th>% Não Atendidas</th>
                                    <th>SLA</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['dadosPorDiaSemana'] as $diaSemana => $metrics): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($diaSemana); ?></td>
                                        <td><?= $metrics['recebidas']; ?></td>
                                        <td><?= $metrics['atendidas']; ?></td>
                                        <td><?= $metrics['abandonadas']; ?></td>
                                        <td><?= $metrics['transferidas']; ?></td>
                                        <td><?= gmdate('H:i:s', $metrics['tme']); ?></td>
                                        <td><?= gmdate('H:i:s', $metrics['tma']); ?></td>
                                        <td><?= number_format($metrics['percent_atendidas'], 2); ?>%</td>
                                        <td><?= number_format($metrics['percent_nao_atendidas'], 2); ?>%</td>
                                        <td><?= number_format($metrics['sla'], 2); ?>%</td>
                                        <td>
                                            <button class="btn btn-info" onclick="toggleDetailsDayOfWeek(this)">Ver
                                                Detalhes</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    function toggleDetailsDayOfWeek(button) {
                        const tr = $(button).closest('tr');
                        const row = $('#ligacoes-dia-semana').DataTable().row(tr);



                        if (row.child.isShown()) {
                            row.child.hide();
                            tr.removeClass('shown');
                        } else {
                            const diaSemana = row.data()[0]; // Nome do dia da semana
                            const registros = <?= json_encode($_SESSION['dadosPorDiaSemana']); ?>[diaSemana]['registros'];

                            const detalhesHTML = getDetalhesDiaSemanaHTML(diaSemana, registros);
                            row.child(detalhesHTML).show();
                            tr.addClass('shown');
                            initializeDetailsTable(`#ligacoes-dia-semana-detalhe-${diaSemana.replace(/[^a-zA-Z0-9]/g, '')}`);
                        }
                    }

                    function getDetalhesDiaSemanaHTML(diaSemana, registros) {
                        const uniqueId = diaSemana.replace(/[^a-zA-Z0-9]/g, ''); // Gera um ID único
                        let friendlyNames = <?= json_encode($friendlyNames); ?>;

                        let detalhesHTML = `
                                            <table class="table table-sm ligacoes-dia-semana-detalhes-table" id="ligacoes-dia-semana-detalhe-${uniqueId}">
                                                <thead>
                                                    <tr>
                                                        <th>Agente</th>
                                                        <th>Data</th>
                                                        <th>Fila</th>
                                                        <th>Número</th>
                                                        <th>Evento</th>
                                                        <th>Tempo de Toque</th>
                                                        <th>Tempo Esperado</th>
                                                        <th>Tempo Conversando</th>
                                                        <th>Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>`;

                        registros.forEach(function(chamada) {
                            let filaAmigavel = friendlyNames[chamada.queuename] || chamada.queuename; // Substitui pelo nome amigável, se existir

                            detalhesHTML += `
                                            <tr>
                                                <td>${chamada.agent || '-'}</td>
                                                <td>${formatDate(chamada.combined_time) || '-'}</td>
                                                <td>${filaAmigavel || '-'}</td>
                                                <td>${chamada.callerid || '-'}</td>
                                                <td style="color: ${chamada.event === 'COMPLETECALLER' || chamada.event === 'COMPLETEAGENT' ? 'green' : 'red'};">
                                                ${chamada.event || '-'}
                                                </td> 
                                                <td>${format_time(chamada.ringtime)}</td>
                                                <td>${format_time(chamada.wait_time)}</td>
                                                <td>${format_time(chamada.call_time)}</td>
                                                <td>
                                                <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">Carregar gravação</button>
                                                <div class="audio-container"></div>
                                                </td>
                                            </tr>`;
                        });

                        detalhesHTML += `</tbody></table>`;
                        return detalhesHTML;
                    }
                </script>

                <div class="container mt-5">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="atendidasPorDiaChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="naoAtendidasPorDiaChart"></canvas>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <canvas id="esperaMediaPorDiaChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <canvas id="duracaoMediaPorDiaChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Modal para exibir as gravações -->
                <div id="recModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <span class="header-title">Gravação</span>
                            <span class="close" id="close-modal">&times;</span>
                        </div>
                        <div id="recModal-body">
                        </div>
                        <div class="modal-footer">
                        </div>
                    </div>
                </div>

                <!-------------------------------------------------------------------------------------------------------------------- FIM DE LIGAÇÕES POR DIA DA SEMANA -------------------------------------------------------------------------------------------------------------------->



                <!---------------------------------------------------------------------------LINKS GENÉRICOS--------------------------------------------------------------------------------------------------->

                <!-- Scripts JavaScript/Jquery -->
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
                <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
                <!-- Script do DataTables -->
                <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
                <!-- <script src="js/ligacoesporhora.js"></script> -->

                <?php include_once 'footer.php'; ?>

</body>


</html>