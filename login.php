<?php
session_start();
require_once 'utils/db_connect.php';

$mensagemErro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['usuario']) && !empty($_POST['senha'])) {
        $usuario = $_POST['usuario'];
        $senha = $_POST['senha'];

        $sql = "SELECT * FROM usuarios WHERE username = ? AND active = 1";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('s', $usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();

                 if (password_verify($senha, $user['password'])) {
                    // Senha correta, iniciar sessão
                    $phpsession = session_id();
                    $idusuario = $user['id'];

                    $_SESSION['usuario'] = $user['username'];
                    $_SESSION['permission'] = $user['permission'];
                    $_SESSION['session_id'] = $phpsession;

                    $stmt = $conn->prepare("
                        INSERT INTO user_sessions (session_id, user_id, login_time, valid_session) 
                        VALUES (?, ?, NOW(), 1)
                        ON DUPLICATE KEY UPDATE valid_session = 1, login_time = NOW()
                    ");
                    $stmt->bind_param("si", $phpsession, $idusuario);

                    if ($stmt->execute()) {
                        header('Location: home.php');
                        exit();
                    } else {
                        $mensagemErro = "Erro ao registrar sessão: " . $stmt->error;
                    }
                } else {
                    $mensagemErro = "Credenciais inválidas.";
                }
            } else {
                $mensagemErro = "Usuário inativo ou não encontrado.";
            }
            $stmt->close();
        } else {
            $mensagemErro = "Erro ao preparar consulta: " . $conn->error;
        }
    } else {
        $mensagemErro = "Por favor, preencha todos os campos.";
    }
}

// Fechar a conexão
$conn->close();
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinePBX - Relatórios</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <script src="js/preloader.js"></script>
    <link rel="stylesheet" href="css/preloader.css">
    <link rel="stylesheet" href="css/login.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login-form-07/fonts/icomoon/style.css">
    <link rel="stylesheet" href="login-form-07/css/owl.carousel.min.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="login-form-07/css/bootstrap.min.css">
    <!-- Style -->
    <link rel="stylesheet" href="login-form-07/css/style.css">

</head>

<div class="preloader">
    <img src="img/favicon.png" alt="loader" class="lds-ripple img-fluid" />
</div>

<body>



    <div class="content">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <img src="login-form-07/images/undraw_remotely_2j6y.svg" alt="Image" class="img-fluid">
                </div>
                <div class="col-md-6 contents">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="mb-4">
                                <h3>Se conecte à plataforma!</h3>
                                <p class="mb-4">Bem-vindo à nossa plataforma de relatórios! <br>
                                    Aqui, você pode gerenciar informações do dia a dia de seu callcenter.
                                </p>
                            </div>

                            <!-- Exibe a mensagem de erro, se houver -->
                            <?php if (!empty($mensagemErro)) { ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $mensagemErro; ?>
                                </div>
                            <?php } ?>

                            <form action="#" method="post">
                                <div class="form-group first">
                                    <label for="username">Usuário</label>
                                    <input type="text" class="form-control" id="username" name="usuario" required>
                                </div>

                                <div class="form-group last mb-4">
                                    <label for="password">Senha</label>
                                    <input type="password" class="form-control" id="password" name="senha" required>
                                </div>

                                <div class="d-flex mb-5 align-items-center">
                                    <!-- Inserindo a segunda imagem (imagem da seta) -->
                                    <!-- <img src="img/linereports.png" alt="Seta" class="img-fluid"
                                        style="width: 250px; height: 120px; margin-right: 15px;"> -->
                                    <!-- Comentado caso queira algo adicional, como lembrar de login ou um link -->
                                    <!-- <label class="control control--checkbox mb-0"><span class="caption">Lembrar-me</span>
                                    <input type="checkbox" checked="checked" />
                                    <div class="control__indicator"></div>
                                </label> -->
                                </div>

                                <input type="submit" value="Entrar" class="btn btn-block btn-primary">
                            </form>
                        </div>
                        <div class="naopossuiusuario">
                            Não possui um usuário? <a href="https://ingline.sz.chat/contactUs/3CIT95F9TI">Acionar
                                Suporte</a>
                        </div>
                    </div>
                </div>
                <div class="footer-note">
                    Módulo de Relatórios Versão 1.0.4 (Stable Version) - Desenvolvido pela Ingline Systems
                    <br>
                    Por - Luki
                </div>
            </div>
        </div>
    </div>



    <script src="login-form-07/js/jquery-3.3.1.min.js"></script>
    <script src="login-form-07/js/popper.min.js"></script>
    <script src="login-form-07/js/bootstrap.min.js"></script>
    <script src="login-form-07/js/main.js"></script>


</body>

</html>