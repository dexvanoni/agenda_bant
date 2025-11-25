<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Buscar estatísticas
$stmt = $conn->query("SELECT COUNT(*) FROM agendamentos");
$total_agendamentos = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM agendamentos WHERE status = 'pendente'");
$agendamentos_pendentes = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM espacos WHERE status = 'ativo'");
$espacos_ativos = $stmt->fetchColumn();

// Buscar últimos agendamentos
$stmt = $conn->query("
    SELECT a.*, e.nome as espaco_nome 
    FROM agendamentos a 
    JOIN espacos e ON a.espaco_id = e.id
    WHERE a.data_inicio >= CURRENT_DATE()
    ORDER BY 
        CASE 
            WHEN a.status = 'pendente' THEN 1
            WHEN a.status = 'aprovado' THEN 2
            ELSE 3
        END,
        DATE(a.data_inicio) ASC,
        TIME(a.data_inicio) ASC
");
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - BANT</title>
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
        .card-stats {
            transition: transform 0.2s;
        }
        .card-stats:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-bant">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Painel Administrativo</h1>
                    <p class="mb-0">Base Aérea de Natal</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="../index.php" class="btn btn-light me-2">Ver Site</a>
                    <a href="logout.php" class="btn btn-danger">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="container">
        <!-- Menu -->
        <div class="row mb-4">
            <div class="col">
                <div class="btn-group">
                    <a href="espacos.php" class="btn btn-primary">
                        <i class="fas fa-building"></i> Espaços
                    </a>
                    <a href="configuracoes.php" class="btn btn-primary">
                        <i class="fas fa-cog"></i> Configurações
                    </a>
                    <a href="relatorios.php" class="btn btn-primary">
                        <i class="fas fa-chart-bar"></i> Relatórios
                    </a>
                    <a href="militares.php" class="btn btn-primary">
                        <i class="fas fa-users"></i> Militares
                    </a>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card card-stats bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total de Agendamentos</h5>
                        <p class="card-text display-4"><?php echo $total_agendamentos; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stats bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Agendamentos Pendentes</h5>
                        <p class="card-text display-4"><?php echo $agendamentos_pendentes; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stats bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Espaços Ativos</h5>
                        <p class="card-text display-4"><?php echo $espacos_ativos; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimos Agendamentos -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Agendamentos</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-success btn-sm" id="btnAprovarSelecionados">
                        <i class="fas fa-check"></i> Aprovar Selecionados
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" id="btnCancelarSelecionados">
                        <i class="fas fa-times"></i> Cancelar Selecionados
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-striped table-hover">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selecionarTodos">
                                    </div>
                                </th>
                                <th>Evento</th>
                                <th>Espaço</th>
                                <th>Solicitante</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendamentos as $agendamento): ?>
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input agendamento-checkbox" type="checkbox" 
                                               value="<?php echo $agendamento['id']; ?>"
                                               data-status="<?php echo $agendamento['status']; ?>">
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($agendamento['nome_evento']); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['espaco_nome']); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['nome_solicitante']); ?></td>
                                <td><?php 
                                    $data = new DateTime($agendamento['data_inicio'], new DateTimeZone('America/Sao_Paulo'));
                                    echo $data->format('d/m/Y H:i');
                                ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $agendamento['status'] === 'aprovado' ? 'success' : 
                                            ($agendamento['status'] === 'pendente' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($agendamento['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="visualizar_agendamento.php?id=<?php echo $agendamento['id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Progress Bar Modal -->
    <div class="modal fade" id="progressModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <h5 class="mb-3">Processando agendamentos...</h5>
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
            const selecionarTodos = document.getElementById('selecionarTodos');
            const checkboxes = document.querySelectorAll('.agendamento-checkbox');
            const btnAprovarSelecionados = document.getElementById('btnAprovarSelecionados');
            const btnCancelarSelecionados = document.getElementById('btnCancelarSelecionados');
            const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));

            // Função para marcar/desmarcar todos
            selecionarTodos.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            // Função para atualizar status dos agendamentos selecionados
            async function atualizarStatusAgendamentos(status) {
                const selecionados = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selecionados.length === 0) {
                    alert('Selecione pelo menos um agendamento!');
                    return;
                }

                if (!confirm(`Deseja realmente ${status === 'aprovado' ? 'aprovar' : 'cancelar'} os agendamentos selecionados?`)) {
                    return;
                }

                try {
                    // Mostrar modal de progresso
                    progressModal.show();

                    const response = await fetch('atualizar_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            ids: selecionados,
                            status: status
                        })
                    });

                    const data = await response.json();
                    
                    // Esconder modal de progresso
                    progressModal.hide();
                    
                    if (data.success) {
                        alert('Status atualizado com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao atualizar status: ' + data.message);
                    }
                } catch (error) {
                    // Esconder modal de progresso em caso de erro
                    progressModal.hide();
                    alert('Erro ao processar a requisição: ' + error.message);
                }
            }

            // Eventos dos botões
            btnAprovarSelecionados.addEventListener('click', () => atualizarStatusAgendamentos('aprovado'));
            btnCancelarSelecionados.addEventListener('click', () => atualizarStatusAgendamentos('cancelado'));
        });
    </script>
</body>
</html> 
