<?php
$page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="css/footer.css">
<div class="footer">
    <hr class="footer-divider">
    <div class="footer-content">
        <a href="https://www.inglinesystems.com.br" target="_blank" class="footer-logo-link">
            <img src="img/linepbxico.png" alt="Logo Ingline Systems" class="footer-logo">
        </a>
        <div class="footer-text">
            <p>Versão 1.0.6 - Desenvolvido por 
                <a href="https://www.inglinesystems.com.br" target="_blank">Ingline Systems</a>
                <b> - Por Luki</b>
            </p>
        </div>
    </div>
</div>

<?php if ($page !== 'pesquisar.php' && $page !== 'agentes.php' && $page !== 'home.php'): ?>
    <script src="js/datatables.js"></script>
<?php endif; ?>
