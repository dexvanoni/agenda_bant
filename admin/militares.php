<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Buscar militares
$stmt = $conn->query("
    SELECT * FROM militares 
    ORDER BY posto_graduacao, nome_guerra
");
$militares = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Militares - BANT</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .header-bant {
            background-color: #1a237e;
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-bant">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Gerenciar Militares</h1>
                    <p class="mb-0">Base Aérea de Natal</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-light me-2">Voltar</a>
                    <a href="logout.php" class="btn btn-danger">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Lista de Militares</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#militarModal">
                    <i class="fas fa-plus"></i> Novo Militar
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Posto/Graduação</th>
                                <th>Nome de Guerra</th>
                                <th>Esquadrão/Setor</th>
                                <th>Email FAB</th>
                                <th>Ramal</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($militares as $militar): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($militar['posto_graduacao']); ?></td>
                                <td><?php echo htmlspecialchars($militar['nome_guerra']); ?></td>
                                <td><?php echo htmlspecialchars($militar['esquadrao_setor']); ?></td>
                                <td><?php echo htmlspecialchars($militar['email_fab']); ?></td>
                                <td><?php echo htmlspecialchars($militar['ramal']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $militar['status'] === 'ativo' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($militar['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info editar-militar" 
                                            data-id="<?php echo $militar['id']; ?>"
                                            data-posto="<?php echo htmlspecialchars($militar['posto_graduacao']); ?>"
                                            data-nome="<?php echo htmlspecialchars($militar['nome_guerra']); ?>"
                                            data-setor="<?php echo htmlspecialchars($militar['esquadrao_setor']); ?>"
                                            data-email="<?php echo htmlspecialchars($militar['email_fab']); ?>"
                                            data-ramal="<?php echo htmlspecialchars($militar['ramal']); ?>"
                                            data-status="<?php echo $militar['status']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger excluir-militar"
                                            data-id="<?php echo $militar['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Militar -->
    <div class="modal fade" id="militarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Militar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formMilitar">
                        <input type="hidden" id="militar_id" name="id">
                        
                        <div class="mb-3">
                            <label class="form-label">Posto/Graduação</label>
                            <input type="text" class="form-control" id="posto_graduacao" name="posto_graduacao" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nome de Guerra</label>
                            <input type="text" class="form-control" id="nome_guerra" name="nome_guerra" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Esquadrão/Setor</label>
                            <input type="text" class="form-control" id="esquadrao_setor" name="esquadrao_setor" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email FAB</label>
                            <input type="email" class="form-control" id="email_fab" name="email_fab" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ramal</label>
                            <input type="text" class="form-control" id="ramal" name="ramal" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSalvarMilitar">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bar Modal -->
    <div class="modal fade" id="progressModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <h5 class="mb-3">Processando...</h5>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Base Aérea de Natal. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const militarModal = new bootstrap.Modal(document.getElementById('militarModal'));
            const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
            const formMilitar = document.getElementById('formMilitar');
            const btnSalvarMilitar = document.getElementById('btnSalvarMilitar');

            // Limpar formulário ao abrir modal
            document.getElementById('militarModal').addEventListener('show.bs.modal', function (event) {
                if (!event.relatedTarget) {
                    formMilitar.reset();
                    document.getElementById('militar_id').value = '';
                }
            });

            // Editar militar
            document.querySelectorAll('.editar-militar').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const posto = this.dataset.posto;
                    const nome = this.dataset.nome;
                    const setor = this.dataset.setor;
                    const email = this.dataset.email;
                    const ramal = this.dataset.ramal;
                    const status = this.dataset.status;

                    document.getElementById('militar_id').value = id;
                    document.getElementById('posto_graduacao').value = posto;
                    document.getElementById('nome_guerra').value = nome;
                    document.getElementById('esquadrao_setor').value = setor;
                    document.getElementById('email_fab').value = email;
                    document.getElementById('ramal').value = ramal;
                    document.getElementById('status').value = status;

                    militarModal.show();
                });
            });

            // Excluir militar
            document.querySelectorAll('.excluir-militar').forEach(button => {
                button.addEventListener('click', async function() {
                    const id = this.dataset.id;
                    
                    if (!confirm('Deseja realmente excluir este militar?')) {
                        return;
                    }

                    try {
                        progressModal.show();

                        const response = await fetch('excluir_militar.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: id })
                        });

                        const data = await response.json();
                        
                        progressModal.hide();
                        
                        if (data.success) {
                            alert('Militar excluído com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro ao excluir militar: ' + data.message);
                        }
                    } catch (error) {
                        progressModal.hide();
                        alert('Erro ao processar a requisição: ' + error.message);
                    }
                });
            });

            // Salvar militar
            btnSalvarMilitar.addEventListener('click', async function() {
                const formData = new FormData(formMilitar);
                const data = Object.fromEntries(formData.entries());

                try {
                    progressModal.show();

                    const response = await fetch('salvar_militar.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();
                    
                    progressModal.hide();
                    
                    if (result.success) {
                        alert('Militar salvo com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao salvar militar: ' + result.message);
                    }
                } catch (error) {
                    progressModal.hide();
                    alert('Erro ao processar a requisição: ' + error.message);
                }
            });
        });
    </script>
</body>
</html> 