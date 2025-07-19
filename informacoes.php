<?php include_once 'header.php';?>

<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f6f9;
        margin: 0;
        padding: 0;
    }


    .event-list {
        list-style: none;
        padding: 0;
    }

    .event-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        background-color: #f9f9f9;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .event-item p {
        margin: 0;
        font-size: 16px;
        line-height: 1.5;
    }

    .event-item.red {
        border-left: 6px solid #e74c3c;
    }

    .event-item.green {
        border-left: 6px solid #2ecc71;
    }

    .event-item.yellow {
        border-left: 6px solid #f1c40f;
    }

    .event-icon {
        font-size: 20px;
        color: #333;
    }
</style>

<body>
    <div class="container">

        <h3 class="tituloprincipal">Como funcionam os eventos de chamada?</h3>
        <ul class="event-list">
            <li class="event-item red">
                <span class="event-icon">&#9888;</span>
                <p><strong>ABANDON</strong>: O chamador abandonou a ligação durante a espera na fila. (Pode ser
                    categorizado como não atendido).</p>
            </li>
            <li class="event-item green">
                <span class="event-icon">&#9742;</span>
                <p><strong>COMPLETECALLER</strong>: A ligação foi desligada/encerrada pelo chamador.</p>
            </li>
            <li class="event-item green">
                <span class="event-icon">&#9742;</span>
                <p><strong>COMPLETEAGENT</strong>: A ligação foi desligada/encerrada pelo agente.</p>
            </li>
            <li class="event-item red">
                <span class="event-icon">&#10060;</span>
                <p><strong>CANCEL</strong>: Tentativa de ligação encerrada pelo agente.</p>
            </li>
            <li class="event-item yellow">
                <span class="event-icon">&#9200;</span>
                <p><strong>EXITWITHTIMEOUT</strong>: O chamador esperou por muito tempo e alcançou o tempo limite.</p>
            </li>
            <li class="event-item yellow">
                <span class="event-icon">&#9200;</span>
                <p><strong>EXITWITHKEY</strong>: O chamador optou por usar uma tecla de menu (disponível na URA)
                    para sair/encerrar a ligação.</p>
            </li>
            <li class="event-item red">
                <span class="event-icon">&#8987;</span>
                <p><strong>RINGCANCEL</strong>: O valor de tempo limite para tocar na fila foi excedido(Ou operador recusou a ligação). Retornando
                    assim um evento chamado RINGCANCEL, onde é vinculado ao operador que tocou por último antes de
                    expirar.</p>
            </li>
        </ul>

    </div>
</body>

<?php include_once 'footer.php'; ?>