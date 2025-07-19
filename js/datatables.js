let datatableLanguage = null; // Variável global para armazenar o JSON de tradução

    $(document).ready(function () {
        // Carrega o JSON de tradução apenas uma vez e armazena na variável global
        $.getJSON('https://cdn.datatables.net/plug-ins/1.12.1/i18n/pt-BR.json', function (language) {
            datatableLanguage = language;

            // Inicializa todas as tabelas principais e genéricas
            initializeMainTable('#ligacoes-dia'); // Tabela Ligações por Dia
            initializeMainTable('#ligacoes-dia-semana'); // Tabela Ligações por Dia da Semana
            initializeMainTable('#ligacoes-semana'); // Tabela Ligações por Semana
            initializeMainTable('#ligacoes-hora'); // Tabela Ligações por Hora
            initializeTable('.ligacoes-mes-principal-table'); // Tabelas mensais genéricas
            initializeTable('.detailsTable'); // Tabelas de detalhes genéricas
        }).fail(function () {
            console.error('Falha ao carregar o JSON de tradução.');
        });
    });

    // Função para inicializar o DataTables em tabelas principais (como Dia, Semana, Hora)
    function initializeMainTable(tableId) {
        if (!$.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable({
                responsive: true,
                paging: true,
                ordering: true,
                order: [[0, 'asc']], // Ordena pela primeira coluna
                info: true,
                language: datatableLanguage, // Usa o JSON de tradução carregado
                columnDefs: [
                    { type: 'string', targets: 0 } // Define a primeira coluna como string (geralmente Data ou Dia)
                ]
            });
        }
    }

    // Função para inicializar DataTables em tabelas genéricas
    function initializeTable(selector) {
        if (!$.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                pageLength: 10, // Número de registros por página
                language: datatableLanguage // Usa o JSON carregado
            });
        }
    }

    // Função para inicializar DataTables dinamicamente em tabelas de detalhes
    function initializeDetailsTable(tableId) {
        if (!$.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable({
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                pageLength: 10, // Número de registros por página
                language: datatableLanguage // Usa o JSON carregado
            });
        }
    }

