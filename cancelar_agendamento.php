<?php
require_once 'config/database.php';
require_once 'config/email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do agendamento não fornecido']);
    exit();
}

try {
    // Buscar informações do agendamento
    $stmt = $conn->prepare("
        SELECT a.*, e.nome as nome_espaco, m.email_fab, m.nome_guerra, m.posto_graduacao
        FROM agendamentos a
        JOIN espacos e ON a.espaco_id = e.id
        JOIN militares m ON a.militar_id = m.id
        WHERE a.id = ?
    ");
    $stmt->execute([$data['id']]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agendamento) {
        throw new Exception('Agendamento não encontrado');
    }

    // Atualizar status do agendamento
    $stmt = $conn->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = ?");
    $stmt->execute([$data['id']]);

    // Buscar configurações
    $stmt = $conn->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    // Formatar data e hora
    $data_inicio = new DateTime($agendamento['data_inicio']);
    $data_fim = new DateTime($agendamento['data_fim']);
    $data_inicio->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    $data_fim->setTimezone(new DateTimeZone('America/Sao_Paulo'));

    // Enviar email para a comunicação social
    $assunto = "Agendamento Cancelado - Sistema BANT";
    $mensagem = "
        <h2>Agendamento Cancelado</h2>
        <p><strong>Evento:</strong> {$agendamento['nome_evento']}</p>
        <p><strong>Espaço:</strong> {$agendamento['nome_espaco']}</p>
        <p><strong>Data:</strong> " . $data_inicio->format('d/m/Y') . "</p>
        <p><strong>Horário:</strong> " . $data_inicio->format('H:i') . " às " . $data_fim->format('H:i') . "</p>
        <p><strong>Solicitante:</strong> {$agendamento['posto_graduacao']} {$agendamento['nome_guerra']}</p>
        <p><strong>Email:</strong> {$agendamento['email_fab']}</p>
        <p><strong>Setor:</strong> {$agendamento['setor']}</p>
        <p><strong>Ramal:</strong> {$agendamento['ramal']}</p>
        <p><strong>Número de Participantes:</strong> {$agendamento['quantidade_participantes']}</p>
        <p><strong>Observações/Link:</strong> " . ($agendamento['observacoes'] ?: "Nenhuma") . "</p>
    ";

    // Enviar email para a comunicação social
    enviarEmail($config['email_comunicacao'], $assunto, $mensagem);

    // Enviar email adicional para a Sala de Videoconferência
    if ($agendamento['nome_espaco'] === 'Sala de Videoconferência') {
        enviarEmail('etic.bant@fab.mil.br', $assunto, $mensagem);
    }

    // Enviar email adicional para o Auditório Cine Navy
    if ($agendamento['nome_espaco'] === 'Auditório Cine Navy') {
        enviarEmail($config['email_sindico_cine_navy'], $assunto, $mensagem);
    }

    // Enviar email de confirmação para o solicitante
    $assunto_solicitante = "Seu Agendamento foi Cancelado - Sistema BANT";
    $mensagem_solicitante = "
        <h2>Agendamento Cancelado</h2>
        <p>Olá {$agendamento['nome_guerra']},</p>
        <p>Seu agendamento foi cancelado com sucesso.</p>
        <p><strong>Evento:</strong> {$agendamento['nome_evento']}</p>
        <p><strong>Espaço:</strong> {$agendamento['nome_espaco']}</p>
        <p><strong>Data:</strong> " . $data_inicio->format('d/m/Y') . "</p>
        <p><strong>Horário:</strong> " . $data_inicio->format('H:i') . " às " . $data_fim->format('H:i') . "</p>
    ";

    // Enviar email para o solicitante
    enviarEmail($agendamento['email_fab'], $assunto_solicitante, $mensagem_solicitante);

    echo json_encode(['success' => true, 'message' => 'Agendamento cancelado com sucesso']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao cancelar agendamento: ' . $e->getMessage()]);
} 