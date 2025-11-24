<?php
require_once 'config/database.php';

// Buscar espaços ativos
$stmt = $conn->query("SELECT * FROM espacos WHERE status = 'ativo' ORDER BY nome");
$espacos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar últimos agendamentos para cada espaço
$ultimos_agendamentos = [];
foreach ($espacos as $espaco) {
    $stmt = $conn->prepare("
        SELECT nome_evento, data_inicio, status 
        FROM agendamentos 
        WHERE espaco_id = ? 
        AND status != 'cancelado'
        ORDER BY data_inicio DESC 
        LIMIT 5
    ");
    $stmt->execute([$espaco['id']]);
    $ultimos_agendamentos[$espaco['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Agendamento - BANT</title>
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
        .card {
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .ultimos-agendamentos {
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .ultimos-agendamentos .list-group-item {
            padding: 0.5rem 1rem;
        }
        .status-badge {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-bant">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Sistema de Agendamento</h1>
                    <p class="mb-0">Base Aérea de Natal</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="admin/login.php" class="btn btn-light">Área Administrativa</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#meusAgendamentosModal">
                    <i class="fas fa-calendar-check"></i> Meus Agendamentos
                </button>
            </div>
        </div>
        <div class="row">
            <?php foreach ($espacos as $espaco): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($espaco['nome']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($espaco['descricao']); ?></p>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-users"></i> Capacidade: <?php echo $espaco['capacidade']; ?> pessoas
                            </small>
                        </p>
                        
                        <!-- Últimos Agendamentos -->
                        <div class="ultimos-agendamentos">
                            <h6 class="mb-2">Últimos Agendamentos:</h6>
                            <?php if (!empty($ultimos_agendamentos[$espaco['id']])): ?>
                                <div class="list-group">
                                    <?php foreach ($ultimos_agendamentos[$espaco['id']] as $agendamento): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($agendamento['nome_evento']); ?></strong>
                                                    <br>
                                                    <small>
                                                        <?php 
                                                        // Assumir que a data do banco está em America/Sao_Paulo
                                                        $data = new DateTime($agendamento['data_inicio'], new DateTimeZone('America/Sao_Paulo'));
                                                        echo $data->format('d/m/Y H:i');
                                                        ?>
                                                    </small>
                                                </div>
                                                <span class="badge <?php 
                                                    echo $agendamento['status'] === 'aprovado' ? 'bg-success' : 
                                                        ($agendamento['status'] === 'pendente' ? 'bg-warning' : 'bg-secondary');
                                                ?> status-badge">
                                                    <?php echo ucfirst($agendamento['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">Nenhum agendamento recente</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="agendamento.php?espaco=<?php echo $espaco['id']; ?>" class="btn btn-primary w-100">
                            Agendar
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Base Aérea de Natal. ETIC | Desenvolvimento.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modal Meus Agendamentos -->
    <div class="modal fade" id="meusAgendamentosModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Meus Agendamentos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label">Buscar Militar</label>
                        <input type="text" class="form-control" id="busca_militar_agendamentos" placeholder="Digite o nome ou posto do militar...">
                        <input type="hidden" id="militar_id_agendamentos">
                        <div id="resultados_busca_agendamentos" class="list-group mt-2" style="display: none; max-height: 200px; overflow-y: auto;"></div>
                    </div>

                    <div id="lista_agendamentos" style="display: none;">
                        <h6 class="mb-3">Agendamentos do Militar</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Evento</th>
                                        <th>Espaço</th>
                                        <th>Data</th>
                                        <th>Horário</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela_agendamentos">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Cancelamento -->
    <div class="modal fade" id="confirmarCancelamentoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Cancelamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja cancelar este agendamento?</p>
                    <input type="hidden" id="agendamento_id_cancelar">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarCancelamento">Sim, Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Função para buscar militares
            let timeoutId;
            const buscaMilitar = document.getElementById('busca_militar_agendamentos');
            const resultadosBusca = document.getElementById('resultados_busca_agendamentos');

            buscaMilitar.addEventListener('input', function() {
                clearTimeout(timeoutId);
                const busca = this.value.trim();

                if (busca.length < 2) {
                    resultadosBusca.style.display = 'none';
                    return;
                }

                timeoutId = setTimeout(() => {
                    fetch(`buscar_militares.php?busca=${encodeURIComponent(busca)}`)
                        .then(response => response.json())
                        .then(data => {
                            resultadosBusca.innerHTML = '';
                            
                            if (data.length === 0) {
                                resultadosBusca.style.display = 'none';
                                return;
                            }

                            data.forEach(militar => {
                                const item = document.createElement('a');
                                item.href = '#';
                                item.className = 'list-group-item list-group-item-action';
                                item.innerHTML = `${militar.posto_graduacao} ${militar.nome_guerra}`;
                                
                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    selecionarMilitar(militar);
                                });
                                
                                resultadosBusca.appendChild(item);
                            });
                            
                            resultadosBusca.style.display = 'block';
                        })
                        .catch(error => {
                            console.error('Erro ao buscar militares:', error);
                        });
                }, 300);
            });

            // Função para selecionar um militar
            function selecionarMilitar(militar) {
                document.getElementById('militar_id_agendamentos').value = militar.id;
                document.getElementById('busca_militar_agendamentos').value = `${militar.posto_graduacao} ${militar.nome_guerra}`;
                resultadosBusca.style.display = 'none';
                
                // Buscar agendamentos do militar
                buscarAgendamentos(militar.id);
            }

            // Função para buscar agendamentos
            function buscarAgendamentos(militarId) {
                fetch(`buscar_agendamentos_militar.php?militar_id=${militarId}`)
                    .then(response => response.json())
                    .then(data => {
                        const tabela = document.getElementById('tabela_agendamentos');
                        tabela.innerHTML = '';
                        
                        if (data.length === 0) {
                            tabela.innerHTML = '<tr><td colspan="6" class="text-center">Nenhum agendamento encontrado</td></tr>';
                        } else {
                            data.forEach(agendamento => {
                                const tr = document.createElement('tr');
                                
                                // Formatar data e hora
                                const data = new Date(agendamento.data_inicio);
                                const dataFormatada = data.toLocaleDateString('pt-BR');
                                const horaInicio = data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                                
                                const dataFim = new Date(agendamento.data_fim);
                                const horaFim = dataFim.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                                
                                // Definir cor do status
                                let statusClass = '';
                                switch(agendamento.status) {
                                    case 'aprovado':
                                        statusClass = 'success';
                                        break;
                                    case 'pendente':
                                        statusClass = 'warning';
                                        break;
                                    case 'cancelado':
                                        statusClass = 'danger';
                                        break;
                                }
                                
                                tr.innerHTML = `
                                    <td>${agendamento.nome_evento}</td>
                                    <td>${agendamento.nome_espaco}</td>
                                    <td>${dataFormatada}</td>
                                    <td>${horaInicio} - ${horaFim}</td>
                                    <td><span class="badge bg-${statusClass}">${agendamento.status}</span></td>
                                    <td>
                                        ${agendamento.status !== 'cancelado' ? `
                                            <button type="button" class="btn btn-sm btn-danger cancelar-agendamento" 
                                                    data-id="${agendamento.id}">
                                                <i class="fas fa-times"></i> Cancelar
                                            </button>
                                        ` : ''}
                                    </td>
                                `;
                                
                                tabela.appendChild(tr);
                            });
                        }
                        
                        document.getElementById('lista_agendamentos').style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Erro ao buscar agendamentos:', error);
                    });
            }

            // Evento para cancelar agendamento
            document.addEventListener('click', function(e) {
                if (e.target.closest('.cancelar-agendamento')) {
                    const agendamentoId = e.target.closest('.cancelar-agendamento').dataset.id;
                    document.getElementById('agendamento_id_cancelar').value = agendamentoId;
                    new bootstrap.Modal(document.getElementById('confirmarCancelamentoModal')).show();
                }
            });

            // Confirmar cancelamento
            document.getElementById('btnConfirmarCancelamento').addEventListener('click', function() {
                const agendamentoId = document.getElementById('agendamento_id_cancelar').value;
                
                fetch('cancelar_agendamento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: agendamentoId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Agendamento cancelado com sucesso!');
                        // Atualizar lista de agendamentos
                        const militarId = document.getElementById('militar_id_agendamentos').value;
                        buscarAgendamentos(militarId);
                        // Fechar modal de confirmação
                        bootstrap.Modal.getInstance(document.getElementById('confirmarCancelamentoModal')).hide();
                    } else {
                        alert('Erro ao cancelar agendamento: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao processar a requisição');
                });
            });

            // Fechar resultados ao clicar fora
            document.addEventListener('click', function(e) {
                if (!buscaMilitar.contains(e.target) && !resultadosBusca.contains(e.target)) {
                    resultadosBusca.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 
