<?php
require_once 'api/process_data.php'; // Dados vindos de $_SESSION como filas selecionadas e período selecionado.
require_once 'utils/db_connect.php';
!empty($_SESSION['filas']) ? $queues = $_SESSION['filas'] : '';
!empty($_SESSION['startDate']) ? $startDate = $_SESSION['startDate'] : '';
!empty($_SESSION['endDate']) ? $endDate = $_SESSION['endDate'] : '';
!empty($_SESSION['startTime'] ? $startTime = $_SESSION['startTime'] : '');
!empty($_SESSION['endTime'] ? $endTime = $_SESSION['endTime'] : '');

include_once 'utils/agent_info.php';

$query = "
SELECT agent_name, queue, paused_reason, start_time, end_time, duration_seconds, created_at 
FROM agent_pause_logs
WHERE created_at BETWEEN '$startDate $startTime' AND '$endDate $endTime'
";

$result = mysqli_query($conn, $query);

$dadosAtt = []; // Inicializa o array principal

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $dadosAtt[] = $row; // Armazena cada linha como um array dentro de $dadosAtt
    }
}

//Cabeçalho
require_once 'header.php';
?>


<?php

!empty($_SESSION['fetch_data']) ? $data = $_SESSION['fetch_data'] : null;

$totalAgentes = 0;
$quantAgentes = [];

foreach ($data as $users) {

    $name = $users['agent'];
    $evento = $users['event'];

    if (in_array($evento, ['COMPLETECALLER', 'COMPLETEAGENT', 'CHANUNAVAIL', 'CANCEL'])) {
        if (!isset($quantAgentes[$name])) {
            $quantAgentes[$name] = [
                'tmes' => 0,
                'mts' => 0,
                'sml' => 0,
                'tts' => 0
            ];
            $totalAgentes++;
        }
    }
}
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


                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">Total Agentes</div>
                            <div class="card-body">
                                <table class="table">
                                    <tbody>
                                        <tr class="bg-light">
                                            <td>Número de Agentes</td>
                                            <td>
                                                <?= $totalAgentes ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Tempo Médio das Sessões:</td>
                                            <td>
                                                00:00:00
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Menor Tempo de Sessão:</td>
                                            <td>
                                                00:00:07
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Sessão Mais longa:</td>
                                            <td>
                                                00:00:07
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td>Tempo Total de Sessão</td>
                                            <td>
                                                00:00:07
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <hr class="footer-divider">
                </div>



                <?php
                $dpausa = [];
                $totalPausa = 0;
                $totalDuracao = 0;

                // 1️⃣ Calcula a duração total da pausa e conta os tipos de pausa por agente
                foreach ($dadosAtt as $datas) {
                    $paused_reason = $datas['paused_reason'];
                    $duration_seconds = $datas['duration_seconds'];
                    $agent_name = $datas['agent_name'];

                    if (!isset($dpausa[$agent_name])) {
                        $dpausa[$agent_name] = [
                            'duracao_pausa' => 0,
                            'countpause' => []
                        ];
                    }

                    $dpausa[$agent_name]['duracao_pausa'] += $duration_seconds;

                    if (!isset($dpausa[$agent_name]['countpause'][$paused_reason])) {
                        $dpausa[$agent_name]['countpause'][$paused_reason] = 0;
                    }
                    $dpausa[$agent_name]['countpause'][$paused_reason]++;
                }


                // 2️⃣ Obtém os dados da sessão
                $data = !empty($_SESSION['fetch_data']) ? $_SESSION['fetch_data'] : [];
                $agentes = [];
                $totalAtendidas = 0;
                $totaldeAgentes = 0;
                $totaldeTMA = 0;
                $agentesSomados = []; // ✅ Controle para evitar soma duplicada
                $totalFalha = 0;
                $totalChamadasRejeitadas = 0;



                // 3️⃣ Associa os dados de pausa e chamadas por agente
                foreach ($data as $agent) {
                    $nameAgent = $agent['agent'];
                    if ($nameAgent === null) {
                        continue;
                    }
                    $eventType = $agent['event'];
                    $calltime = $agent['call_time'];

                    if (in_array($eventType, ['COMPLETECALLER', 'COMPLETEAGENT', 'CHANUNAVAIL', 'CANCEL'])) {

                        if (!isset($agentes[$nameAgent])) {
                            $agentes[$nameAgent] = [
                                'atendidas' => 0,
                                'falha' => 0,
                                'duracaodapause' => 0,
                                'pausas' => [], // Agora armazena todas as pausas corretamente
                                'tempoconversando' => 0,
                                'tma' => 0,
                                'rejeitadas' => 0
                            ];
                        }

                        // ✅ Se o agente existe no array de pausas, adicionamos os valores corretamente
                        if (isset($dpausa[$nameAgent])) {
                            $agentes[$nameAgent]['duracaodapause'] = $dpausa[$nameAgent]['duracao_pausa'];

                            // ✅ SOMA A PAUSA APENAS UMA VEZ POR AGENTE
                            if (!isset($agentesSomados[$nameAgent])) {
                                $totalDuracao += $dpausa[$nameAgent]['duracao_pausa'];
                                $agentesSomados[$nameAgent] = true; // Marca que já somamos esse agente
                            }

                            // ✅ Certifica-se de que há pausas antes de atribuir
                            if (!empty($dpausa[$nameAgent]['countpause'])) {
                                $agentes[$nameAgent]['pausas'] = $dpausa[$nameAgent]['countpause'];
                            } else {
                                $agentes[$nameAgent]['pausas'] = []; // Garante que não fique indefinido
                            }
                        }

                        // ✅ Incrementa os valores de chamadas corretamente
                        $totaldeAgentes++;
                        $agentes[$nameAgent]['atendidas']++;
                        if (in_array($eventType, ['CHANUNAVAIL', 'CANCEL'])) {
                            $agentes[$nameAgent]['falha']++;
                        }
                        $agentes[$nameAgent]['tempoconversando'] += $calltime;
                        $totalAtendidas++;
                        $totaldeTMA += $calltime;
                    }
                }
                // echo '<pre>';
                // print_r($agentes);

                $agent = [];
                foreach ($noAnswerData as $noAnswer) {

                    $name = $noAnswer['agent'];
                    $evento = $noAnswer['event'];
                    $ringTime = $noAnswer['ringtime'];

                    if (!isset($agent[$name])) {
                        $agent[$name] = [
                            'falha' => 0,
                            'rejeitadas' => 0,
                        ];
                    }
                    if (array_key_exists($name, $agentes)) {
                        if (in_array($evento, ['RINGNOANSWER'])) {
                            $agentes[$name]['falha']++;
                            if ($ringTime < 15000) {
                                $agentes[$name]['rejeitadas']++;
                            }
                        }
                    }
                }
                ?>

                <!-- Renderização da Tabela -->
                <div class="container" id="secao-servicoatendidas">
                    <h3 class="tituloprincipal">Disponibilidade do Agente</h3>
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
                                <th>Agente</th>
                                <th>Ligações Atendidas</th>
                                <th>Falha</th>
                                <th>Duração da Sessão</th>
                                <th>Duração da Pausa</th>
                                <th>Sessões</th>
                                <th>Pausas</th>
                                <th>Tempo Conversando</th>
                                <th>TMA</th>
                                <th>Falha de Saída</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php echo '<pre>';
                            print_r($agentes); ?>
                            <?php foreach ($agentes as $nameAgent => $dados): if ($agentes[$nameAgent]['falha']) {
                                    $totalFalha += $agentes[$nameAgent]['falha'];
                                    $totalChamadasRejeitadas += $agentes[$nameAgent]['rejeitadas'];
                                }


                            ?>

                                <tr>
                                    <td><?= htmlspecialchars($nameAgent); ?></td> <!-- Nome do agente -->
                                    <td><?= htmlspecialchars($dados['atendidas']); ?></td> <!-- Ligações atendidas -->
                                    <td><?= htmlspecialchars($agentes[$nameAgent]['falha']); ?></td> <!-- Falhas -->
                                    <td>00:00:00</td>
                                    <!-- Duração da pausa -->
                                    <td><?= format_time($dados['duracaodapause']); ?></td>
                                    <!-- Preencha com os dados corretos -->
                                    <td>00:00:00</td>
                                    <td>
                                        <?php foreach ($dados['pausas'] as $key => $valor):
                                            $totalPausa += $valor;
                                            $key == '' ? $key = 'Não identificado' : "" ?>
                                            <?= htmlspecialchars($key) . ": " . htmlspecialchars($valor) ?><br>
                                        <?php endforeach; ?>
                                    </td>
                                    <td><?= htmlspecialchars(format_time($dados['tempoconversando'])); ?></td>
                                    <!-- Tempo conversando -->
                                    <td><?= htmlspecialchars(format_time($dados['tma'])); ?></td> <!-- TMA -->
                                    <td><?= htmlspecialchars($agentes[$nameAgent]['rejeitadas']); ?></td>
                                    <td>
                                        <button class="btn btn-info"
                                            onclick="toggleDetailsAtendidas(this, '<?= $nameAgent; ?>')">
                                            Ver Detalhes
                                        </button>
                                    </td>
                                </tr>

                                <!-- Detalhes do agente -->
                                <tr id="details-<?= $nameAgent ?>" class="details-row" style="display: none;">
                                    <td colspan="12">
                                        <div id="details-container-<?= $nameAgent ?>"></div>
                                    </td>
                                </tr>

                            <?php endforeach; ?>




                            <!-- Linha Total -->
                            <tr>
                                <th>Total</th>
                                <th><?= $totalAtendidas; ?></th>
                                <th><?= $totalFalha; ?></th>
                                <th> 00:00:00 </th>
                                <th><?= format_time($totalDuracao); ?></th>
                                <th>00:00:00</th>
                                <th><?= $totalPausa . ' pausas' ?></th>
                                <th><?= format_time($totaldeTMA); ?></th>
                                <th>00:00:00</th>
                                <th><?= $totalChamadasRejeitadas ?></th>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <script>
                    // Função para alternar a exibição dos detalhes do agente
                    function toggleDetailsAtendidas(button, agentName) {
                        const detailsRow = document.getElementById('details-' + agentName);
                        if (detailsRow.style.display === 'none') {
                            detailsRow.style.display = 'table-row';
                            fetchDetailsAtendidas(agentName);
                        } else {
                            detailsRow.style.display = 'none';
                        }
                    }

                    // Função para buscar os detalhes das ligações atendidas
                    function fetchDetailsAtendidas(agentName) {
                        const container = document.getElementById('details-container-' + agentName);
                        container.innerHTML = 'Carregando...';
                        const friendlyNames = <?= json_encode($friendlyNames); ?>; // Mapeamento de nomes amigáveis 

                        fetch('api/fetch_details_pause.php?agent=' + encodeURIComponent(agentName))
                            .then(response => response.json())
                            .then(data => {
                                if (data.error) {
                                    container.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
                                } else {
                                    let detalhesHTML = `
                                    <table class="table table-sm display">
                                        <thead>
                                            <tr>
                                                <th>Agente</th>
                                                <th>Data</th>
                                                <th>Fila</th>
                                                <th>Número</th>
                                                <th>Origem</th>
                                                <th>Evento</th>
                                                <th>Tempo de Toque</th>
                                                <th>Tempo Esperado</th>
                                                <th>Tempo Conversando</th>
                                                <th>Gravação</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                                    data.data.forEach(chamada => {
                                        const filaAmigavel = friendlyNames || chamada.fila;
                                        detalhesHTML += `
                <tr>
                    <td>${chamada.agent || '-'}</td>
                    <td>${chamada.combined_time || '-'}</td>
                    <td>${filaAmigavel}</td>
                    <td>${chamada.callerid || '-'}</td>
                    <td>${chamada.diallerid || '-'}</td>
                    <td style="color: ${['COMPLETECALLER','COMPLETEAGENT'].includes(chamada.event) ? 'green' : 'red'};">
                        ${chamada.event || '-'}
                    </td>
                    <td>${format_time(chamada.ringtime) || '-'}</td>
                    <td>${chamada.wait_time ? format_time(chamada.wait_time) : '-'}</td>
                    <td>${chamada.wait_time ? format_time(chamada.call_time) : '-'}</td>
                    <td>
                        <button class="btn btn-secondary" onclick="loadRecording('${chamada.callid}', this)">Carregar gravação</button>
                        <div class="audio-container"></div>
                    </td>
                </tr>`;
                                    });

                                    detalhesHTML += `</tbody></table>`;
                                    container.innerHTML = detalhesHTML;
                                }
                            })
                            .catch(error => {
                                console.error('Erro ao buscar detalhes:', error);
                                container.innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes.</div>';
                            });
                    }
                </script>

                <!--------------------------------- LIGAÇÕES TRANSBORDADAS --------------------------------->
                <div class="container" id="secao-naoatendidasevento">
                    <h3 class="tituloprincipal">Ligações Transbordadas</h3>
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
                            $totalTransbordadas = 0;
                            $totalPercentual = 0;
                            $tipoEvento = [];
                            $transbordadas = [];
                            // Filtra chamadas Não atendidas (Abandon) e organiza por evento
                            foreach ($data as $chamada) {
                                $tipoEvento = $chamada['event'];
                                if (in_array($tipoEvento, ['EXITWITHTIMEOUT'])) {
                                    $totalTransbordadas++;

                                    if (!isset($naoAtendidas[$tipoEvento])) {
                                        $transbordadas[$tipoEvento] = 0;
                                    }
                                    $transbordadas[$tipoEvento]++;
                                }
                            }

                            // Gera a tabela com os dados processados
                            foreach ($transbordadas as $tipoEvento => $quantidade): ?>

                                <?php
                                $resultados = [];
                                $percentual = ($totalTransbordadas > 0) ? round(($quantidade / $totalTransbordadas) * 100, 2) : 0;
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


                <!--------------------------------- FIM DE LIGAÇÕES TRANSBORDADAS --------------------------------->


                <script>


                </script>
</body>



<?php

include 'footer.php';


?>