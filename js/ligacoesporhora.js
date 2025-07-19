//////////////////////////////////////////////////////////////////////////////////////////////////////////////  FUNÇÕES GENÉRICAS.  ////////////////////////////////////////////////////////////////////////////////////////////////////////////// 

// Função para formatar o tempo em formato HH:MM:SS
function format_time(seconds) {
    let hours = Math.floor(seconds / 3600);
    let minutes = Math.floor((seconds % 3600) / 60);
    let secs = seconds % 60;
    return hours.toString().padStart(2, '0') + ':' + minutes.toString().padStart(2, '0') + ':' + secs.toString().padStart(2, '0');
}

// Função para formatar data
function formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2)
        month = '0' + month;
    if (day.length < 2)
        day = '0' + day;

    return [year, month, day].join('-');
}



function scrollToDiv(div) {
    const elemento = document.getElementById(div);
    elemento.scrollIntoView({ behavior: "smooth" });
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////  FUNÇÕES GENÉRICAS.  ////////////////////////////////////////////////////////////////////////////////////////////////////////////// 






////////////////////////////////////////////////////////////////////////////////////////////////////// DICT QUEUE V1.0 //////////////////////////////////////////////////////////////////////////////////////////////////////
function definirNomeFila() {
    var modal = document.getElementById('modal');
    var modalBody = document.getElementById('modal-body');

    // Fazendo uma requisição Ajax para buscar as filas da tabela `queues`
    $.ajax({
        url: 'api/filas_ajax.php',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            // Conteúdo do modal para permitir a inserção manual de `queue_number` e `queue_name`
            var content = '';
            content += '<p>Bem vindo a seção de alteração de nome das filas! Por favor, insira o número da fila (queue_number) e o novo nome da fila.</p>';
            content += '<table class="table"><thead><tr><th>Fila</th><th>Novo Nome</th></tr></thead><tbody>';

            // Adicionar uma linha para entrada de dados
            content += '<tr>';
            content += '<td><input type="text" id="queue_number" placeholder="Número da Fila" class="form-control"></td>'; // Campo para `queue_number`
            content += '<td><input type="text" id="queue_name" placeholder="Novo Nome da Fila" class="form-control"></td>'; // Campo para `queue_name`
            content += '</tr>';

            content += '</tbody></table>';

            modalBody.innerHTML = content;
            modal.style.display = 'flex'; // Exibe o modal como flexbox para centralizar

        },
        error: function () {
            modalBody.innerHTML = '<p>Ocorreu um erro ao buscar os dados das filas.</p>';
            modal.style.display = 'flex';
        }
    });
}


function salvarNomesFilas() {
    var queue_number = document.getElementById('queue_number').value.trim(); // Obtém o número da fila inserido pelo usuário
    var queue_name = document.getElementById('queue_name').value.trim(); // Obtém o nome da fila inserido pelo usuário

    if (!queue_number || !queue_name) {
        alert('Por favor, preencha ambos os campos: Número da Fila e Novo Nome.');
        return;
    }

    var updates = [{
        queue_number: queue_number, // Número da fila inserido pelo usuário
        queue_name: queue_name       // Novo nome da fila inserido pelo usuário
    }];

    $.ajax({
        url: 'utils/updatequeuename.php',
        method: 'POST',
        data: {
            updates: JSON.stringify(updates)
        },
        dataType: 'json', // Adiciona o tipo de dado esperado na resposta
        success: function (response) {
            if (response['error-queue']) { // Verifica se existe o erro específico
                alert('Erro: ' + response['error-queue']);
            } else if (response.error) { // Verifica erros genéricos
                alert('Erro: ' + response.error);
            } else if (response.status === 'success') {
                alert('Alterações salvas com sucesso!');
                closeModal();
            }
        },
        error: function (xhr, status, error) {
            // Captura erros na requisição Ajax (exemplo: problema de conexão)
            alert('Ocorreu um erro ao salvar as alterações: ' + status + ' ' + error);
        }
    });
}

////////////////////////////////////////////////////////////////////////////////////////////////////// DICT QUEUE V1.0 //////////////////////////////////////////////////////////////////////////////////////////////////////

function dinamDivs() {

    var menu = document.getElementById('menu-opcoes');
    menu.style.display = "block";
}

function esconderDivs() {
    var menu = document.getElementById('menu-opcoes');
    menu.style.display = "none";
}

/*Aqui nós estamos ocultando os elementos para evitar erros como acessar uma propriedade inexistente,
afinal o elemento de configurações está oculto em home.php, portanto o código abaixo irá procurar pelo elemento inexistente.*/
//
//     document.querySelector(".dropdown").addEventListener("mouseenter", dinamDivs);
//     document.querySelector(".dropdown").addEventListener("mouseleave", esconderDivs);

//





//////////////////////////////////////////////////////////////////////////////////////////////////////////////  GERA AS TABELAS POR DIA E POR HORA. NÃO MEXA SE NÃO SOUBER O QUE ESTIVER FAZENDO.  ////////////////////////////////////////////////////////////////////////////////////////////////////////////// 



////////////////////////////////////////////////////////////////////////////////////////////////////////////// GERANDO TABELAS POR DIA //////////////////////////////////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////////////////////////////////// FIM DA GERAÇÃO DE TABELAS POR DIA //////////////////////////////////////////////////////////////////////////////////////////////////////////////




//////////////////////////////////////////////////////////////////////////////////////////////////////////////  GERA AS TABELAS POR DIA E POR HORA. NÃO MEXA SE NÃO SOUBER O QUE ESTIVER FAZENDO.  ////////////////////////////////////////////////////////////////////////////////////////////////////////////// 









//////////////////////////////////////////////////////////////////////////////////////////////////////////////  BUSCA AS GRAVAÇÕES DE UM A UM PARA NÃO SOBRECARREGAR O SERVIDOR.  ////////////////////////////////////////////////////////////////////////////////////////////////////////////// 

function loadRecording(callid, button) {
    console.log('CallID recebido no frontend:', callid);

    if (!callid) {
        alert('Parâmetro inválido.');
        return;
    }

    // Fazer a requisição ao backend para buscar a gravação
    fetch('api/fetch_recording.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({ callid }) // Envia o CallID ao backend
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                console.log('Gravação encontrada:', data.path);

                const bodyModal = document.getElementById('recModal-body');
                const recordingModal = document.getElementById('recModal');

                //Mostrando o modal
                recordingModal.style.display = 'flex';

                // Adicionar o player de áudio dinamicamente
                const audioPlayer = document.createElement('audio');
                audioPlayer.controls = true;
                audioPlayer.src = data.path;
                audioPlayer.style.display = 'block';

                // Remove players antigos antes de adicionar o novo
                bodyModal.innerHTML = ''; // Limpa o conteúdo existente
                bodyModal.appendChild(audioPlayer);


            } else {
                alert(data.message || 'Erro ao carregar a gravação.');
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            alert('Erro ao carregar a gravação.');
        });
}

// Seção para fechar modal após clicar no 'x' 

function closeModal(modalElement) {
    if (modalElement) {
        modalElement.style.display = 'none';
    }
}


document.addEventListener('DOMContentLoaded', function () {
    // Fecha ao clicar no botão "close"
    var closeButtons = document.getElementsByClassName('close');
    for (var i = 0; i < closeButtons.length; i++) {
        closeButtons[i].addEventListener('click', function(event) {
            // Procura o modal mais próximo na hierarquia
            var modal = this.closest('.modal');
            closeModal(modal);
        });
    }

    // Fecha ao clicar fora do conteúdo do modal
    var modals = document.getElementsByClassName('modal');
    for (var j = 0; j < modals.length; j++) {
        modals[j].addEventListener('click', function (event) {
            if (event.target === this) {
                closeModal(this);
            }
        });
    }
});
//////////////////////////////////////////////////////////////////////////////////////////////////////////////  BUSCA AS GRAVAÇÕES DE UM A UM PARA NÃO SOBRECARREGAR O SERVIDOR.  //////////////////////////////////////////////////////////////////////////////////////////////////////////////
