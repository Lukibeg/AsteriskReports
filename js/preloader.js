// Fun��o para mostrar o preloader
function showPreloader() {
    var preloader = document.querySelector('.preloader');
    if (preloader) {
      preloader.classList.add('active');
    }
  }
  
  // Fun��o para ocultar o preloader
  function hidePreloader() {
    var preloader = document.querySelector('.preloader');
    if (preloader) {
      preloader.classList.remove('active');
    }
  }
  
  // Evento acionado quando a p�gina est� totalmente carregada
  window.addEventListener('load', function () {
    hidePreloader(); // Oculta o preloader ap�s a p�gina ser totalmente carregada
  });
  
  // Evento acionado antes de a p�gina ser descarregada
  window.addEventListener('beforeunload', function () {
    showPreloader(); // Exibe o preloader antes de recarregar a p�gina
  });
  
  // Evento acionado quando a p�gina est� prestes a ser descarregada
  window.addEventListener('unload', function () {
    showPreloader(); // Exibe o preloader antes de trocar de uma se��o para outra
  });
  
  // Exibe o preloader imediatamente quando a p�gina � carregada pela primeira vez
  showPreloader();