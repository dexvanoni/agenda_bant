<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Buscar dados para os gráficos
$stmt = $conn->query("
    SELECT 
        e.nome as espaco,
        COUNT(*) as total
    FROM agendamentos a
    JOIN espacos e ON a.espaco_id = e.id
    GROUP BY e.id, e.nome
    ORDER BY total DESC
");
$agendamentos_por_espaco = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("
    SELECT 
        DATE_FORMAT(data_inicio, '%Y-%m') as mes,
        COUNT(*) as total
    FROM agendamentos
    WHERE status != 'cancelado'
    GROUP BY DATE_FORMAT(data_inicio, '%Y-%m')
    ORDER BY mes ASC
    LIMIT 12
");
$agendamentos_por_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatar os meses para exibição
foreach ($agendamentos_por_mes as &$mes) {
    $data = DateTime::createFromFormat('Y-m', $mes['mes']);
    $mes['mes_formatado'] = $data->format('M/Y');
}

// Buscar agendamentos para a tabela
$stmt = $conn->query("
    SELECT 
        a.*,
        e.nome as espaco_nome
    FROM agendamentos a
    JOIN espacos e ON a.espaco_id = e.id
    ORDER BY a.data_inicio DESC
");
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - BANT</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <h1>Relatórios</h1>
                    <p class="mb-0">Base Aérea de Natal</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-light">Voltar</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="container">
        <!-- Gráficos -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Agendamentos por Espaço</h5>
                        <canvas id="graficoEspacos"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Agendamentos por Mês</h5>
                        <canvas id="graficoMeses"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Agendamentos -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Agendamentos</h5>
                    <button class="btn btn-success" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="tabelaAgendamentos">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Espaço</th>
                                <th>Solicitante</th>
                                <th>Data Início</th>
                                <th>Data Fim</th>
                                <th>Status</th>
                                <th>Participantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendamentos as $agendamento): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agendamento['nome_evento']); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['espaco_nome']); ?></td>
                                <td><?php echo htmlspecialchars($agendamento['nome_solicitante']); ?></td>
                                <td><?php 
                                    $data_inicio = new DateTime($agendamento['data_inicio'], new DateTimeZone('America/Sao_Paulo'));
                                    echo $data_inicio->format('d/m/Y H:i');
                                ?></td>
                                <td><?php 
                                    $data_fim = new DateTime($agendamento['data_fim'], new DateTimeZone('America/Sao_Paulo'));
                                    echo $data_fim->format('d/m/Y H:i');
                                ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $agendamento['status'] === 'aprovado' ? 'success' : 
                                            ($agendamento['status'] === 'pendente' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($agendamento['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $agendamento['quantidade_participantes']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Base Aérea de Natal. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- SheetJS -->
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    
    <script>
        // Configurar DataTable
        $(document).ready(function() {
            $('#tabelaAgendamentos').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                },
                order: [[3, 'desc']]
            });
        });

        // Gráfico de Agendamentos por Espaço
        new Chart(document.getElementById('graficoEspacos'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($agendamentos_por_espaco, 'espaco')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($agendamentos_por_espaco, 'total')); ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                        '#5a5c69', '#858796', '#6f42c1', '#20c9a6', '#fd7e14'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Gráfico de Agendamentos por Mês
        new Chart(document.getElementById('graficoMeses'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($agendamentos_por_mes, 'mes_formatado')); ?>,
                datasets: [{
                    label: 'Agendamentos',
                    data: <?php echo json_encode(array_column($agendamentos_por_mes, 'total')); ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Função para exportar para Excel
        function exportarExcel() {
            const table = document.getElementById('tabelaAgendamentos');
            const wb = XLSX.utils.table_to_book(table, {sheet: "Agendamentos"});
            XLSX.writeFile(wb, "agendamentos_bant.xlsx");
        }
    </script>
</body>
</html> 
