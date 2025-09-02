<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$agendamento_id = (int)$_GET['id'];

// Buscar agendamento
$stmt = $conn->prepare("
    SELECT a.*, e.nome as espaco_nome
    FROM agendamentos a
    JOIN espacos e ON a.espaco_id = e.id
    WHERE a.id = ?
");
$stmt->execute([$agendamento_id]);
$agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$agendamento) {
    header('Location: index.php');
    exit();
}

// Processar alteração de status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    try {
        // Atualizar status no banco de dados
        $stmt = $conn->prepare("UPDATE agendamentos SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $agendamento_id]);

        // Buscar informações atualizadas do agendamento
        $stmt = $conn->prepare("
            SELECT a.*, e.nome as nome_espaco 
            FROM agendamentos a 
            JOIN espacos e ON a.espaco_id = e.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$agendamento_id]);
        $agendamento_atualizado = $stmt->fetch(PDO::FETCH_ASSOC);

        // Preparar mensagem de email baseada no status
        $status_texto = [
            'aprovado' => 'aprovado',
            'cancelado' => 'cancelado',
            'pendente' => 'colocado em análise'
        ];

        require_once '../config/email.php';

        $assunto = "Atualização de Status - Agendamento Sistema BANT";
        $mensagem = "
            <h2>Status do seu Agendamento foi Atualizado</h2>
            <p>Olá {$agendamento_atualizado['nome_solicitante']},</p>
            <p>O status do seu agendamento foi {$status_texto[$_POST['status']]}.</p>
            <p><strong>Evento:</strong> {$agendamento_atualizado['nome_evento']}</p>
            <p><strong>Espaço:</strong> {$agendamento_atualizado['nome_espaco']}</p>
            <p><strong>Data:</strong> " . date('d/m/Y', strtotime($agendamento_atualizado['data_inicio'])) . "</p>
            <p><strong>Horário:</strong> " . date('H:i', strtotime($agendamento_atualizado['data_inicio'])) . " às " . date('H:i', strtotime($agendamento_atualizado['data_fim'])) . "</p>
        ";

        if ($_POST['status'] === 'cancelado') {
            $mensagem .= "<p>Seu agendamento foi cancelado. Caso precise reagendar, por favor, faça uma nova solicitação.</p>";
        } elseif ($_POST['status'] === 'aprovado') {
            $mensagem .= "<p>Seu agendamento foi aprovado! Você pode utilizar o espaço conforme agendado.</p>";
        } else {
            $mensagem .= "<p>Seu agendamento está em análise. Você receberá uma nova notificação quando houver uma atualização.</p>";
        }

        // Enviar email para o solicitante
        enviarEmail($agendamento_atualizado['email_solicitante'], $assunto, $mensagem);

        header("Location: visualizar_agendamento.php?id={$agendamento_id}&success=1");
    } catch (Exception $e) {
        header("Location: visualizar_agendamento.php?id={$agendamento_id}&error=" . urlencode($e->getMessage()));
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Agendamento - BANT</title>
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
                    <h1>Visualizar Agendamento</h1>
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
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Status atualizado com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            Erro ao atualizar o status: <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Informações do Evento</h5>
                        <?php
                        // Verificar se existe documento enviado para este agendamento
                        $uploadDir = __DIR__ . '/../uploads/agendamentos';
                        $arquivo_url = null;
                        if (is_dir($uploadDir)) {
                            $pattern = $uploadDir . DIRECTORY_SEPARATOR . 'agendamento_' . $agendamento_id . '_*';
                            $files = glob($pattern);
                            if ($files && count($files) > 0) {
                                $filePath = $files[0];
                                $filename = basename($filePath);
                                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // e.g. /admin
                                $rootPath = rtrim(dirname($basePath), '/\\'); // subir um nível para raiz do app
                                $rootPrefix = ($rootPath === '.' || $rootPath === '') ? '' : $rootPath;
                                $arquivo_url = $protocol . '://' . $host . $rootPrefix . '/uploads/agendamentos/' . rawurlencode($filename);
                            }
                        }
                        ?>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Evento:</strong> <?php echo htmlspecialchars($agendamento['nome_evento']); ?></p>
                                <p><strong>Espaço:</strong> <?php echo htmlspecialchars($agendamento['espaco_nome']); ?></p>
                                <p><strong>Data Início:</strong> <?php echo date('d/m/Y H:i', strtotime($agendamento['data_inicio'])); ?></p>
                                <p><strong>Data Fim:</strong> <?php echo date('d/m/Y H:i', strtotime($agendamento['data_fim'])); ?></p>
                                <?php if ($arquivo_url): ?>
                                <p class="mt-3">
                                    <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars($arquivo_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                        <i class="fa-solid fa-file-arrow-down me-1"></i> Baixar documento
                                    </a>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($agendamento['posto_graduacao'] . ' ' . $agendamento['nome_solicitante']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($agendamento['email_solicitante']); ?></p>
                                <p><strong>Setor:</strong> <?php echo htmlspecialchars($agendamento['setor']); ?></p>
                                <p><strong>Ramal:</strong> <?php echo htmlspecialchars($agendamento['ramal']); ?></p>
                                <p><strong>Participantes:</strong> <?php echo $agendamento['quantidade_participantes']; ?></p>
                                <p>
                                    <strong>Status:</strong>
                                    <span class="badge bg-<?php 
                                        echo $agendamento['status'] === 'aprovado' ? 'success' : 
                                            ($agendamento['status'] === 'pendente' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($agendamento['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Observações/Links de Reunião:</h5>
                        <?php if ($agendamento['observacoes']): ?>
                        <div class="mt-3">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($agendamento['observacoes'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Alterar Status</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="pendente" <?php echo $agendamento['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="aprovado" <?php echo $agendamento['status'] === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                                    <option value="cancelado" <?php echo $agendamento['status'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Atualizar Status</button>
                        </form>
                    </div>
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
</body>
</html> 