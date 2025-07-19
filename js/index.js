document.addEventListener('DOMContentLoaded', function () {
    // Fun��o para adicionar op��es a um elemento select
    function addOptions(selectElement, start, end, prefix) {
        for (let i = start; i <= end; i++) {
            let value = i.toString().padStart(2, '0');
            let option = document.createElement('option');
            option.value = value;
            option.textContent = prefix + value;
            selectElement.appendChild(option);
        }
    }

    // Adicionando op��es de hora
    addOptions(document.getElementById('startTimeHour'), 0, 23, '');
    addOptions(document.getElementById('endTimeHour'), 0, 23, '');

    // Adicionando op��es de minuto
    addOptions(document.getElementById('startTimeMinute'), 0, 59, '');
    addOptions(document.getElementById('endTimeMinute'), 0, 59, '');
});

// Script para exibir o mês atual e consultar com base nele.
document.getElementById('thisMonth').addEventListener('click', function() {
    var now = new Date();
    var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    var lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

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

    document.getElementById('startDate').value = formatDate(firstDay);
    document.getElementById('endDate').value = formatDate(lastDay);
    
    // Submete o formulário
    document.querySelector('form').submit();
});