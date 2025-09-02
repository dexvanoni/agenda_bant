<?php
require_once 'config/database.php';

if (!isset($_GET['espaco'])) {
    header('Location: index.php');
    exit();
}

$espaco_id = (int)$_GET['espaco'];
$stmt = $conn->prepare("SELECT * FROM espacos WHERE id = ? AND status = 'ativo'");
$stmt->execute([$espaco_id]);
$espaco = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$espaco) {
    header('Location: index.php');
    exit();
}

// Buscar configurações
$stmt = $conn->query("SELECT * FROM configuracoes LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento - <?php echo htmlspecialchars($espaco['nome']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .fc-event {
            cursor: pointer;
        }
        .header-bant {
            background-color: #1a237e;
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .campo-somente-leitura {
            background-color:rgb(165, 165, 165) !important;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-bant">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Agendamento</h1>
                    <p class="mb-0"><?php echo htmlspecialchars($espaco['nome']); ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-light">Voltar</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="container">
        <div class="row">
            <div class="col-md-8">
                <div id="calendario"></div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Informações do Espaço</h5>
                        <p class="card-text"><?php echo htmlspecialchars($espaco['descricao']); ?></p>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-users"></i> Capacidade: <?php echo $espaco['capacidade']; ?> pessoas
                            </small>
                        </p>
                        <hr>
                        <h5>Regras de Agendamento</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-clock"></i> Antecedência mínima: <?php echo $config['antecedencia_horas']; ?> horas</li>
                            <li><i class="fas fa-hourglass-half"></i> Máximo de horas consecutivas: <?php echo $config['max_horas_consecutivas']; ?> horas</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Agendamento -->
    <div class="modal fade" id="agendamentoModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Novo Agendamento</h5>
                        <small class="text-muted">Preencha os dados abaixo para solicitar seu agendamento</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-secondary py-2 mb-3">
                        <strong>Data/Hora:</strong> <span id="dataHoraSelecionada"></span>
                    </div>
                    <form id="formAgendamento">
                        <input type="hidden" id="data_inicio" name="data_inicio">
                        <input type="hidden" id="data_fim" name="data_fim">
                        <input type="hidden" id="espaco_id" name="espaco_id">

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="mb-3">Dados do Evento</h6>
                                        <div class="row g-2 mb-2">
                                            <div class="col-md-4">
                                                <label class="form-label">Data</label>
                                                <input type="date" class="form-control" id="data_agendamento" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Hora Início</label>
                                                <input type="time" class="form-control" id="hora_inicio" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Duração (horas)</label>
                                                <select class="form-control" id="duracao" required>
                                                    <?php 
                                                    $max_horas = $config['max_horas_consecutivas'];
                                                    for ($i = 1; $i <= $max_horas; $i++) {
                                                        echo "<option value='{$i}'>" . $i . " hora" . ($i > 1 ? 's' : '') . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-md-8">
                                                <label class="form-label">Nome do Evento</label>
                                                <input type="text" class="form-control" id="nome_evento" name="nome_evento" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Participantes</label>
                                                <input type="number" class="form-control" name="quantidade_participantes" required>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Observações/Links de Reunião</label>
                                            <textarea class="form-control" name="observacoes" rows="3" required></textarea>
                                        </div>
                                        <div>
                                            <label class="form-label">Anexo (opcional - PNG, JPG, JPEG ou PDF, até 2MB)</label>
                                            <input type="file" class="form-control" id="anexo" name="anexo" accept=".png,.jpg,.jpeg,.pdf">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="mb-3">Dados do Militar</h6>
                                        <div class="mb-2">
                                            <label class="form-label">Buscar Militar</label>
                                            <input type="text" class="form-control" id="busca_militar" placeholder="Digite o nome ou posto do militar e clique no nome encontrado abaixo...">
                                            <input type="hidden" id="militar_id" name="militar_id">
                                            <div id="resultados_busca" class="list-group mt-2" style="display: none; max-height: 200px; overflow-y: auto;"></div>
                                        </div>
                                        <small style="color: red;" class="text-muted">Caso necessário, o militar poderá atualizar as informações abaixo.</small>
                                        <div class="row g-2 mt-1">
                                            <div class="col-md-4">
                                                <label class="form-label">Posto/Graduação</label>
                                                <input type="text" class="form-control campo-somente-leitura" name="posto_graduacao">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Nome do Solicitante</label>
                                                <input type="text" class="form-control campo-somente-leitura" name="nome_solicitante">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control campo-somente-leitura" name="email_solicitante">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Setor</label>
                                                <input type="text" class="form-control campo-somente-leitura" name="setor">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Ramal</label>
                                                <input type="text" class="form-control campo-somente-leitura" name="ramal">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-3">Você receberá uma confirmação por email quando o status do agendamento for atualizado.</small>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSalvarAgendamento">Salvar</button>
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
                    <h5 class="mb-3">Salvando agendamento...</h5>
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
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendario');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                slotMinTime: '08:00:00',
                slotMaxTime: '18:00:00',
                allDaySlot: false,
                selectable: true,
                select: function(info) {
                    var data = info.start;
                    var dataFormatada = data.toLocaleDateString('pt-BR');
                    var horaFormatada = data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                    
                    // Verificar se a data é passada
                    if (data < new Date()) {
                        alert('Não é possível agendar datas ou horários passados.');
                        calendar.unselect();
                        return;
                    }
                    
                    document.getElementById('dataHoraSelecionada').textContent = dataFormatada + ' às ' + horaFormatada;
                    
                    // Preencher os campos de data e hora
                    document.getElementById('data_agendamento').value = data.toISOString().split('T')[0];
                    document.getElementById('hora_inicio').value = data.toTimeString().slice(0, 5);
                    
                    // Atualizar datas no formulário
                    atualizarDatas();
                    
                    document.getElementById('espaco_id').value = '<?php echo $espaco_id; ?>';
                    
                    var modal = new bootstrap.Modal(document.getElementById('agendamentoModal'));
                    modal.show();
                },
                events: 'get_agendamentos.php?espaco=<?php echo $espaco_id; ?>'
            });
            calendar.render();

            // Função para atualizar as datas quando o usuário alterar os campos
            function atualizarDatas() {
                var data = document.getElementById('data_agendamento').value;
                var hora = document.getElementById('hora_inicio').value;
                var duracao = parseInt(document.getElementById('duracao').value);
                
                // Criar data no fuso horário local
                var dataInicio = new Date(data + 'T' + hora + ':00-03:00');
                var dataFim = new Date(dataInicio);
                dataFim.setHours(dataFim.getHours() + duracao);
                
                // Converter para ISO string mantendo o fuso horário local
                document.getElementById('data_inicio').value = dataInicio.toISOString();
                document.getElementById('data_fim').value = dataFim.toISOString();
                
                // Atualizar texto da data/hora selecionada
                document.getElementById('dataHoraSelecionada').textContent = 
                    dataInicio.toLocaleDateString('pt-BR') + ' às ' + 
                    dataInicio.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            }

            // Adicionar listeners para atualizar as datas quando os campos mudarem
            document.getElementById('data_agendamento').addEventListener('change', atualizarDatas);
            document.getElementById('hora_inicio').addEventListener('change', atualizarDatas);
            document.getElementById('duracao').addEventListener('change', atualizarDatas);

            // Função para buscar militares
            let timeoutId;
            const buscaMilitar = document.getElementById('busca_militar');
            const resultadosBusca = document.getElementById('resultados_busca');

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
                document.getElementById('militar_id').value = militar.id;
                document.getElementById('busca_militar').value = `${militar.posto_graduacao} ${militar.nome_guerra}`;
                document.querySelector('input[name="posto_graduacao"]').value = militar.posto_graduacao;
                document.querySelector('input[name="nome_solicitante"]').value = militar.nome_guerra;
                document.querySelector('input[name="setor"]').value = militar.esquadrao_setor;
                document.querySelector('input[name="ramal"]').value = militar.ramal;
                document.querySelector('input[name="email_solicitante"]').value = militar.email_fab;
                resultadosBusca.style.display = 'none';
            }

            // Adicionar listeners para atualizar dados do militar quando os campos forem alterados
            const camposMilitar = [
                { input: 'posto_graduacao', campo: 'posto_graduacao' },
                { input: 'nome_solicitante', campo: 'nome_guerra' },
                { input: 'setor', campo: 'esquadrao_setor' },
                { input: 'ramal', campo: 'ramal' },
                { input: 'email_solicitante', campo: 'email_fab' }
            ];

            camposMilitar.forEach(campo => {
                document.querySelector(`input[name="${campo.input}"]`).addEventListener('change', async function() {
                    const militarId = document.getElementById('militar_id').value;
                    if (!militarId) return;

                    const dados = {
                        id: militarId,
                        posto_graduacao: document.querySelector('input[name="posto_graduacao"]').value,
                        nome_guerra: document.querySelector('input[name="nome_solicitante"]').value,
                        esquadrao_setor: document.querySelector('input[name="setor"]').value,
                        email_fab: document.querySelector('input[name="email_solicitante"]').value,
                        ramal: document.querySelector('input[name="ramal"]').value
                    };

                    try {
                        const response = await fetch('atualizar_militar.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(dados)
                        });

                        const result = await response.json();
                        if (!result.success) {
                            console.error('Erro ao atualizar dados do militar:', result.message);
                        }
                    } catch (error) {
                        console.error('Erro ao processar a requisição:', error);
                    }
                });
            });

            // Fechar resultados ao clicar fora
            document.addEventListener('click', function(e) {
                if (!buscaMilitar.contains(e.target) && !resultadosBusca.contains(e.target)) {
                    resultadosBusca.style.display = 'none';
                }
            });

            // Salvar agendamento
            document.getElementById('btnSalvarAgendamento').addEventListener('click', function() {
                // Validar data/hora
                var dataInicio = new Date(document.getElementById('data_inicio').value);
                if (dataInicio < new Date()) {
                    alert('Não é possível agendar datas ou horários passados.');
                    return;
                }

                // Validar anexo (se presente)
                const fileInput = document.getElementById('anexo');
                if (fileInput && fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    const allowedTypes = ['image/png', 'image/jpeg', 'application/pdf'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Tipo de arquivo inválido. Permitidos: PNG, JPG, JPEG ou PDF.');
                        return;
                    }
                    if (file.size > 2 * 1024 * 1024) { // 2MB
                        alert('O arquivo deve ter no máximo 2MB.');
                        return;
                    }
                }

                const formData = new FormData(document.getElementById('formAgendamento'));
                formData.append('espaco_id', '<?php echo $espaco_id; ?>');

                // Mostrar modal de progresso
                var progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
                progressModal.show();

                fetch('salvar_agendamento.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    progressModal.hide();
                    if (data.success) {
                        alert('Agendamento realizado com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao realizar agendamento: ' + data.message);
                    }
                })
                .catch(error => {
                    progressModal.hide();
                    alert('Erro ao realizar agendamento: ' + error.message);
                });
            });
        });
    </script>
</body>
</html> 