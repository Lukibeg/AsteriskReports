<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page = basename($_SERVER['PHP_SELF']);
$isAdmin = ($_SESSION['permission'] === 'admin');

//  if ($page == 'agentes.php') {
//      header('Location: manutencao.php');
//  }

if ($page == 'monitoramento.php') {
    !$isAdmin ? header('Location: sempermissao.php') : '';
} elseif ($page == 'usuarios.php') {
    !$isAdmin ? header('Location: sempermissao.php') : '';

}

require_once 'utils/db_connect.php';
require_once 'auth.php';
require_once 'utils/listqueues.php';
$isRestricted = in_array($page, ['monitoramento.php', 'usuarios.php', 'informacoes.php', 'nodataempty.php']) && empty($_SESSION['fetch_data']);


?>

<!DOCTYPE html>
<html lang="pt-br">

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LineReports</title>
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="css/tables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="css/charts.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="icon" type="image/x-icon" href="img/favicon.ico">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?php if ($page === 'monitoramento.php'): ?>
    <link rel="stylesheet" href="css/monitoramento.css">
<?php endif; ?>

<link rel="stylesheet" href="css/index.css">
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="css/modal.css">
<link rel="stylesheet" href="css/preloader.css" />
<script src="js/preloader.js"></script>
<script src="js/ligacoesporhora.js"></script>

<?php if ($page == 'home.php'): ?>
    <link rel="stylesheet" href="css/form.css">
<?php endif; ?>

<link rel="stylesheet" type="text/css" href="css/header.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<?php
// Aqui nós só permitimos a utilização do script graficos.js, se a página atual que o usuário estiver acessando for diferente de home.php.
// afinal os gráficos são utilizados somente após a consulta do usuário aos agentes.
if ($page === 'index.php'): ?>
    <script src="js/graficos.js"></script>

<?php endif; ?>

<?php if ($page === 'chamadasatendidas.php'): ?>
    <script src="js/graficoschamadasatendidas.js"></script>
<?php endif; ?>

<style>
    td,
    th {
        margin: 20px;
    }


    /* Close Button */
    .close {
        /* color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            display: block;
            width: 3%;
            left: 850px;
            position: relative; */
        align-self: self-end;
    }


    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
</style>


</head>

<header id="cabecalho">
    <div id="logo">
        <a href="home.php">
            <img src="img/linepbxlogopequena.png" alt="Logomarca LinePBX">
        </a>
    </div>

    <ul id="itens">
        <!-- Menu de Início -->
        <li>
            <a href="home.php" class="menu-item">
                <span class="material-icons">home</span>
            </a>
            <div id="capInicio">Início</div>
        </li>



        <!-- Menu de Sair -->
        <li>
            <a href="index.php?logout=1" class="menu-item">
                <span class="material-icons">logout</span>
            </a>
            <div id="capSair">Sair</div>
        </li>

        <?php if (!$isRestricted): ?>

            <!-- Menu de Chamadas Atendidas -->
            <?php if ($page !== 'home.php'): ?>

                <!-- Menu de Distribuição -->

                <li class="dropdown">
                    <a href="#" class="menu-item">
                        <span class="material-icons">assessment</span>
                    </a>
                    <!-- Será implementado -->
                    <!-- <div id="capDistribuicao">Distribuição</div> -->

                    <ul class="dropdown-menu">
                        <li>
                            <a href="index.php" class="submenu-item">
                                Resumo
                            </a>
                        </li>
                        <?php if ($page == 'index.php'): ?>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-fila')">
                                <a href="#secao-fila" class="submenu-item">
                                    Ligações por Fila
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-saida')">
                                <a href="#secao-saida" class="submenu-item">
                                    Ligações de Saída
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-mes')">
                                <a href="#secao-mes" class="submenu-item">
                                    Ligações por Mês
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-semana')">
                                <a href="#secao-semana" class="submenu-item">
                                    Ligações por Semana
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-dia')">
                                <a href="#secao-dia" class="submenu-item">
                                    Ligações por Dia
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-hora')">
                                <a href="#secao-hora" class="submenu-item">
                                    Ligações por Hora
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-diasemana')">
                                <a href="#secao-diasemana" class="submenu-item">
                                    Ligações por D. da Semana
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>



                <!-- Menu de Chamadas Atendidas -->
                <li class="dropdown">
                    <a href="chamadasatendidas.php" class="menu-item">
                        <span class="material-icons">call</span>
                    </a>
                    <div id="capAtendidas">Chamadas Atendidas</div>

                    <ul class="dropdown-menu">
                        <li class="scroll-to-div" onclick="scrollToDiv('secao-resumoatendidas')">
                            <a href="chamadasatendidas.php" class="scroll-to-div">
                                Resumo - Atendidas
                            </a>
                        </li>
                        <?php if ($page == 'chamadasatendidas.php'): ?>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-servicoatendidas')">
                                <a href="#secao-servicoatendidas" class="submenu-item">
                                    Nível de Serviço - SLA
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-atendidasporfila')">
                                <a href="#secao-atendidasporfila" class="submenu-item">
                                    Atendidas por Fila
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-atendidasporagente')">
                                <a href="#secao-atendidasporagente" class="submenu-item">
                                    Atendidas por Agente
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-causadesconexao')">
                                <a href="#secao-causadesconexao" class="submenu-item">
                                    Causa de Desconexão
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-atendidasporintervalo')">
                                <a href="#secao-atendidasporintervalo" class="submenu-item">
                                    Atendidas por Duração
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item">
                                    Transferências
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Menu de Chamadas Não Atendidas -->
                <li class="dropdown">
                    <a href="chamadasnaoatendidas.php" class="menu-item">
                        <span class="material-icons">call_missed</span>
                    </a>
                    <div id="capNaoatendidas">Chamadas Não Atendidas</div>

                    <ul class="dropdown-menu">
                        <li class="scroll-to-div" onclick="scrollToDiv('secao-resumonaoatendidas')">
                            <a href="chamadasatendidas.php" class="scroll-to-div">
                                Resumo - Não Atendidas
                            </a>
                        </li>
                        <?php if ($page == 'chamadasnaoatendidas.php'): ?>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-serviconaoatendidas')">
                                <a href="#secao-servicoesnaoatendidas" class="submenu-item">
                                    Nível de Serviço - SLA
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-naoatendidasevento')">
                                <a href="#secao-naoatendidasevento" class="submenu-item">
                                    Ligações Não Aten. - Por Evento
                                </a>
                            </li>
                            <li class="scroll-to-div" onclick="scrollToDiv('secao-naoatendidasfila')">
                                <a href="#secao-naoatendidasfila" class="submenu-item">
                                    Ligações Não Aten. - Por Fila
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>


                <!-- Menu de Pesquisar -->
                <li>
                    <a href="pesquisar.php" class="menu-item">
                        <span class="material-icons">search</span>
                    </a>
                    <div id="capPesquisar">Pesquisar</div>
                </li>


                <!-- Menu de Agentes -->
                <li class="dropdown">
                    <a href="agentes.php" class="menu-item">
                        <span class="material-icons">supervisor_account</span>
                    </a>
                    <div id="capAgentes">Agentes</div>

                    <ul class="dropdown-menu">
                        <li>
                            <a href="agentes.php" class="submenu-item">
                                Resumo de Agentes
                            </a>
                        </li>
                        <li>
                            <a href="#" class="submenu-item">
                                Disponibilidade dos Agentes
                            </a>
                        </li>
                        <li>
                            <a href="#" class="submenu-item">
                                Status de Ligações por Agente
                            </a>
                        </li>
                        <li>
                            <a href="#" class="submenu-item">
                                Detalhamento por Agente
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>
        <?php endif; ?>


        <!-- Menu de Configurações -->
        <li class="dropdown">
            <a href="#" class="menu-item">
                <span class="material-icons">settings</span>
            </a>
            <!-- Submenu de Configurações -->
            <ul class="dropdown-menu">
                <?php if ($isAdmin == 'admin'): ?>
                    <?php if ($page !== 'home.php'): ?>
                        <li>
                            <a href="#" onclick="definirNomeFila()" class="submenu-item">
                                <span class="material-icons">edit</span> Filas
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a href="monitoramento.php" class="submenu-item">
                            <span class="material-icons">monitor</span> Monitoramento
                        </a>
                    </li>
                    <li>
                        <a href="usuarios.php" class="submenu-item">
                            <span class="material-icons">people</span> Usuários
                        </a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="informacoes.php"><span class="material-icons">help</span>Informações</a>
                </li>
            </ul>
        </li>
    </ul>
</header>


<div class="preloader">
    <img src="img/favicon.png" alt="loader" class="lds-ripple img-fluid" />
</div>


<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="header-title">Insira a fila e o novo nome!</span>
            <span class="close" id="close-modal">&times;</span>
        </div>
        <div id="modal-body">
            <!-- Conteúdo aqui -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="salvarNomesFilas()">Salvar Alterações</button>
        </div>
    </div>
</div>