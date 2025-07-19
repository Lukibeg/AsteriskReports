<!-- Funções da página "index.php", onde nós apresentamos legendas onde passamos o mouse). --->
<style>
    #csvLegenda {
        display: none;
        position: absolute;
        background-color: #333;
        color: #fff;
        padding: 5px;
        border-radius: 5px;
        font-size: 12px;
        z-index: 1000;
        margin: 0;
        margin-left: 10px;
    }

    #pdfLegenda {
        display: none;
        position: absolute;
        background-color: #333;
        color: #fff;
        padding: 5px;
        border-radius: 5px;
        font-size: 12px;
        z-index: 1000;
        margin: 0;
        margin-left: 57px;

    }

    #xlsxLegenda {

        display: none;
        position: absolute;
        background-color: #333;
        color: #fff;
        padding: 5px;
        border-radius: 5px;
        font-size: 12px;
        z-index: 1000;
        margin: 0;
        margin-left: 100px;

    }

    .exportbutton {
        margin-bottom: 10px;
        margin-left: 5px;
        background-color: #f8f9fa !important;
        border-color: #6c757d !important;
    }

    .btn-primary:hover {
        background-color: #97acc2 !important;
    }
</style>

<!-- Funções da página "home.php", onde nós damos funcionalidade aos botões de consulta rápida(Este mês, Esta semana, Hoje, Ontem e Últimos 3 meses). --->



<!-- Funções da página "monitoramento.php" onde nós carregamos os nomes personalizados dos ramais, salvamos os nomes atualizados no Json gerado e atualizamos os ramais. -->
<script>
    let ramaisData = {};
    const agentNames = {}; // Dicionário de nomes dos agentes

    // Carregar nomes personalizados dos ramais
    function loadAgentNames() {
        fetch('./logs/agent_names.json?timestamp=' + new Date().getTime()) // Adiciona timestamp para evitar cache
            .then(response => response.json())
            .then(data => {
                Object.assign(agentNames, data);
            })
            .catch(error => console.error("Erro ao carregar nomes dos agentes:", error));
    }

    // Função para atualizar o JSON no servidor com o novo nome
    function saveAgentName(ramal, name) {
        fetch('api/save_agent_name.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ ramal, name })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    agentNames[ramal] = name; // Atualiza o dicionário local
                } else {
                    console.error("Erro ao salvar o nome do agente:", data.message);
                }
            })
            .catch(error => console.error("Erro ao salvar o nome do agente:", error));
    }

    function atualizarRamais() {
        fetch('./logs/chat_events.json?timestamp=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                ramaisData = data;
                const container = document.getElementById("ramalContainer");
                container.innerHTML = '';

                for (const ramal in data) {
                    const ramalInfo = data[ramal];
                    const card = document.createElement("div");
                    const protocol = 'PJSIP/';
                    card.classList.add("ramal-card");

                    // Define a cor de fundo do card conforme o status do ramal
                    card.style.backgroundColor = ramalInfo.status === 'available' ? '#d0f0d0' :
                        ramalInfo.status === 'in_call' ? '#b0d4f0' :
                            ramalInfo.status === 'paused' ? '#f0d0d0' : '#e0e0e0';

                    // Remove o prefixo 'PJSIP/' se presente
                    const ramalSemPrefixo = ramal.includes(protocol) ? ramal.replace(protocol, '') : ramal;

                    // Ícone de status
                    const icon = document.createElement("div");
                    icon.classList.add("status-icon", ramalInfo.status);
                    icon.innerHTML = ramalInfo.status === 'available' ? '✔️' :
                        ramalInfo.status === 'in_call' ? '📞' :
                            ramalInfo.status === 'paused' ? '⏸️' : '⚠️';
                    card.appendChild(icon);

                    // Nome do ramal sem o prefixo 'PJSIP/'
                    const title = document.createElement("h3");
                    title.textContent = agentNames[ramalSemPrefixo] || ramalSemPrefixo; // Usa o nome personalizado, se disponível
                    title.style.fontFamily = "Impact,Charcoal,sans-serif";
                    card.appendChild(title);

                    //Icone de edição
                    const editIcon = document.createElement("img");
                    editIcon.src = "./img/edit.svg"; // Define o caminho da imagem
                    editIcon.alt = "Editar";
                    editIcon.style.cursor = "pointer";
                    editIcon.style.marginLeft = "5px";
                    editIcon.style.width = "16px"; // Tamanho opcional para o ícone
                    editIcon.style.height = "16px"; // Tamanho opcional para o ícone

                    // Função de edição ao clicar no ícone
                    editIcon.onclick = () => {
                        const newName = prompt("Digite o novo nome para o ramal " + ramalSemPrefixo);
                        if (newName) {
                            saveAgentName(ramalSemPrefixo, newName);
                            title.textContent = newName;
                        }
                    };

                    // Adiciona o ícone ao título do ramal
                    title.appendChild(editIcon);

                    if (ramalInfo.status === 'in_call') {
                        const callStatus = document.createElement("p");
                        callStatus.textContent = ` Em ligação `;
                        callStatus.style.fontFamily = "system-ui";
                        card.appendChild(callStatus);
                    } else {
                        const status = document.createElement("p");
                        status.textContent = `Status: ${ramalInfo.status === 'no_register' ? 'Sem Registro' : 'Disponível'}`;
                        status.style.fontFamily = "system-ui";
                        card.appendChild(status);
                    }

                    container.appendChild(card);
                }
            })
            .catch(error => console.error("Erro ao buscar eventos:", error));
    }

    function format_time($seconds)
    {
        return sprintf('%02d:%02d:%02d', floor($seconds / 3600), ($seconds / 60) % 60, $seconds % 60);
    }

</script>



<!-- Funções da página index.php -->
<script>
    function toggleDetails(id) {
        const detailRow = document.getElementById(id);

        // Verifica se a linha de detalhes existe
        if (detailRow) {
            // Alterna a visibilidade da linha de detalhes
            if (detailRow.style.display === 'none' || detailRow.style.display === '') {
                detailRow.style.display = 'table-row'; // Mostra a linha de detalhes
            } else {
                detailRow.style.display = 'none'; // Oculta a linha de detalhes
            }
        } else {
            console.error("A linha de detalhes com o ID " + id + " não foi encontrada.");
        }
    }





</script>

<!--- Funções da página "header.php" -->
