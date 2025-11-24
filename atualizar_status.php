<?php
require_once 'config/database.php';
require_once 'config/email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

try {
    // Validar dados
    if (!isset($_POST['agendamento_id']) || !isset($_POST['status'])) {
        throw new Exception("Dados incompletos");
    }

    $agendamento_id = (int)$_POST['agendamento_id'];
    $status = $_POST['status'];

    // Validar status
    if (!in_array($status, ['pendente', 'aprovado', 'cancelado'])) {
        throw new Exception("Status inválido");
    }

    // Buscar informações do agendamento
    $stmt = $conn->prepare("
        SELECT a.*, e.nome as nome_espaco 
        FROM agendamentos a 
        JOIN espacos e ON a.espaco_id = e.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$agendamento_id]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agendamento) {
        throw new Exception("Agendamento não encontrado");
    }

    // Atualizar status
    $stmt = $conn->prepare("UPDATE agendamentos SET status = ? WHERE id = ?");
    $stmt->execute([$status, $agendamento_id]);

    // Preparar mensagem de email baseada no status
    $status_texto = [
        'aprovado' => 'aprovado',
        'cancelado' => 'cancelado',
        'pendente' => 'colocado em análise'
    ];

    $assunto = "Atualização de Status - Agendamento Sistema BANT";
    $mensagem = "
        <h2>Status do seu Agendamento foi Atualizado</h2>
        <p>Olá {$agendamento['nome_solicitante']},</p>
        <p>O status do seu agendamento foi {$status_texto[$status]}.</p>
        <p><strong>Evento:</strong> {$agendamento['nome_evento']}</p>
        <p><strong>Espaço:</strong> {$agendamento['nome_espaco']}</p>
        <p><strong>Data:</strong> " . (new DateTime($agendamento['data_inicio'], new DateTimeZone('America/Sao_Paulo')))->format('d/m/Y') . "</p>
        <p><strong>Horário:</strong> " . (new DateTime($agendamento['data_inicio'], new DateTimeZone('America/Sao_Paulo')))->format('H:i') . " às " . (new DateTime($agendamento['data_fim'], new DateTimeZone('America/Sao_Paulo')))->format('H:i') . "</p>
    ";

    if ($status === 'cancelado') {
        $mensagem .= "<p>Seu agendamento foi cancelado. Caso precise reagendar, por favor, faça uma nova solicitação.</p>";
    } elseif ($status === 'aprovado') {
        $mensagem .= "<p>Seu agendamento foi aprovado! Você pode utilizar o espaço conforme agendado.</p>";
    } else {
        $mensagem .= "<p>Seu agendamento está em análise. Você receberá uma nova notificação quando houver uma atualização.</p>";
    }

    // Enviar email para o solicitante
    enviarEmail($agendamento['email_solicitante'], $assunto, $mensagem);

    // Enviar email adicional para a Sala de Videoconferência
    if ($agendamento['nome_espaco'] === 'Sala de Videoconferência') {
        enviarEmail('etic.bant@fab.mil.br', $assunto, $mensagem);
    }

    // Enviar email adicional para o Auditório Cine Navy
    if ($agendamento['nome_espaco'] === 'Auditório Cine Navy') {
        // Buscar email do síndico
        $stmt = $conn->query("SELECT email_sindico_cine_navy FROM configuracoes LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        enviarEmail($config['email_sindico_cine_navy'], $assunto, $mensagem);
    }

    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Status atualizado com sucesso'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 