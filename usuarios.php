<?php 

include_once 'header.php';
$query = 'SELECT * FROM usuarios';
$result = mysqli_query($conn, $query);
$total = mysqli_num_rows($result);
$usuarios = [];
if ($total > 0) {
    while ($linha = mysqli_fetch_assoc($result)) {

        $usuarios[] = $linha;
    }
} else {
    echo 'Nenhum registro encontrado';
}
?>



<body>
    <div class="container">
        <div class="tituloscontainer">
            <h3 class="tituloprincipal">Usuários</h3>
            <br>
            <h4>Esta página é destinada à visualização e edição dos usuários cadastrados no sistema.</h4>
        </div>
        <br>
        <br>
        <div class="row">
            <div class="col-md-12">
                <h4>Usuários cadastrados</h4>
                <hr class="footer-divider">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Permissão</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($usuarios as $usuario): ?>


                                <td><?= $usuario['username'] ?></td>
                                <td> <?= $usuario['permission'] ?></td>
                                <?php if ($usuario['active'] == 1): ?>
                                    <td>
                                        <?= 'Ativo'; ?>
                                    </td>

                                <?php else: ?>
                                    <td> <?= 'Inativo'; ?> </td>
                                <?php endif; ?>
                                <td>
                                    <button class="btn btn-primary"
                                        onclick="editarModal(<?= $usuario['id']; ?>)">Editar</button>
                                    <button class="btn btn-danger"
                                        onclick="excluirUsuario(<?= $usuario['id']; ?>)">Excluir</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="botaoAdicionar">
                    <button class="addUser" onclick="adicionarModal()"><span class="material-icons">add</span></button>
                </div>
            </div>

            <div id="modal-editar" class="modal-editar" style="display: none;">
                <div class="modal-editar-content">
                    <div class="modal-header">
                        <span class="header-title">Editar Usuário</span>
                        <span class="close" id="close-modal" onclick="fecharModal()">&times;</span>
                    </div>
                    <div id="modal-body-editar">
                        <form id="form-editar">
                            <div class="form-group">
                                <label for="username">Nome de Usuário</label>
                                <input type="text" id="username" name="username" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="email">E-mail</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Senha</label>
                                <input type="password" id="password" name="password" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="permission">Permissão</label>
                                <select id="permission" name="permission" class="form-control">
                                    <option value="admin">Admin</option>
                                    <option value="user">Usuário</option>
                                    <option value="supervisor">Supervisor(em implementação)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status-add">Status</label>
                                <select name="active" id="status-add" class="form-control">
                                    <option value="0">Desativo</option>
                                    <option value="1">Ativo</option>
                                </select>
                            </div>
                            <input type="hidden" id="user-id" name="user_id">
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="salvarAlteracoes()">Salvar
                                    Alterações</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <div id="modal-adicionar" class="modal-adicionar" style="display: none;">
                <div class="modal-editar-content">
                    <div class="modal-header">
                        <span class="header-title">Adicionar Usuário</span>
                        <span class="close" id="close-modal-adicionar" onclick="fecharModal()">&times;</span>
                    </div>
                    <div id="modal-body-adicionar">
                        <form id="form-adicionar">
                            <div class="form-group">
                                <label for="username-add">Nome de Usuário</label>
                                <input type="text" id="username-add" name="username" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="email-add">E-mail</label>
                                <input type="email" id="email-add" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="password-add">Senha</label>
                                <input type="password" id="password-add" name="password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="permission-add">Permissão</label>
                                <select id="permission-add" name="permission" class="form-control">
                                    <option value="admin">Admin</option>
                                    <option value="user">Usuário</option>
                                    <option value="supervisor">Supervisor (em implementação)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status-add">Status</label>
                                <select name="active" id="status-add1" class="form-control">
                                    <option value="0">Desativo</option>
                                    <option value="1">Ativo</option>
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="salvarNovoUsuario()">Adicionar
                                    Usuário</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</body>

<! -- //////////////////////////////////////////////////////////////// JAVASCRIPT //////////////////////////////////////////////////////////////// -->

<script>
    function excluirUsuario(id) {
        if (confirm('Tem certeza que deseja excluir este usuário?')) {
            fetch('excluir_usuario.php', {
                method: 'POST',
                body: JSON.stringify({ id }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert('Usuário excluído com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao excluir usuário: ' + data.message);
                    }
                })
                .catch((error) => {
                    console.error('Erro ao excluir usuário:', error);
                    alert('Erro ao excluir usuário.');
                });
        }


    }

    function adicionarModal() {
        document.getElementById('modal-adicionar').style.display = 'block';
    }

    function salvarNovoUsuario() {
        const formData = new FormData(document.getElementById('form-adicionar'));


        document.getElementById('email-add').value == '' ? alert('Preencha o campo de e-mail') : null;
        document.getElementById('password-add').value == '' ? alert('Preencha o campo de senha') : null;
        document.getElementById('username-add').value == '' ? alert('Preencha o campo de nome de usuário') : null;

        fetch('adicionar_usuario.php', {
            method: 'POST',
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert('Usuário adicionado com sucesso!');
                    location.reload(); // Recarrega a página para mostrar o novo usuário
                } else {
                    alert('Erro ao adicionar usuário: ' + data.message);
                }
            })
            .catch((error) => {
                console.error('Erro ao adicionar usuário:', error);
                alert('Erro ao adicionar usuário.');
            });

        fecharModal();
    }

    function editarModal(userId) {
        // Busca os dados do usuário no backend usando o ID
        fetch(`api/pegar_usuario.php?id=${userId}`)
            .then((response) => response.json())
            .then((usuario) => {
                // Preenche o modal com os dados retornados do backend
                document.getElementById('username').value = usuario.username;
                document.getElementById('email').value = usuario.email;
                document.getElementById('permission').value = usuario.permission;
                document.getElementById('user-id').value = usuario.id;
                document.getElementById('status-add').value = usuario.active;
                document.getElementById('password').value = ''; // Deixa a senha em branco por segurança
                document.getElementById('modal-editar').style.display = 'block';
            })
            .catch((error) => {
                console.error('Erro ao buscar usuário:', error);
                alert('Erro ao buscar os dados do usuário.');
            });
    }

    function fecharModal() {
        document.getElementById('modal-editar').style.display = 'none';
        document.getElementById('modal-adicionar').style.display = 'none';

        if (document.getElementById('modal-adicionar').style.display = 'block') {
            document.getElementById('modal-adicionar').style.display = 'none';
        }
    }

    function salvarAlteracoes() {
        const formData = new FormData(document.getElementById('form-editar'));

        fetch('editar_usuario.php', {
            method: 'POST',
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert('Usuário atualizado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao atualizar usuário: ' + data.message);
                }
            })
            .catch((error) => {
                console.error('Erro ao salvar alterações:', error);
                alert('Erro ao salvar alterações.');
            });

        fecharModal();
    }


</script>

<! -- //////////////////////////////////////////////////////////////// RODAPÉ //////////////////////////////////////////////////////////////// -->
<?php include_once 'footer.php'; ?>