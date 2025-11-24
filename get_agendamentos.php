<?php
require_once 'config/database.php';

$espaco_id = isset($_GET['espaco']) ? (int)$_GET['espaco'] : 0;

try {
    $stmt = $conn->prepare("
        SELECT 
            a.id,
            a.nome_evento as title,
            a.data_inicio as start,
            a.data_fim as end,
            a.status,
            a.nome_solicitante,
            a.posto_graduacao,
            a.setor,
            a.ramal,
            a.email_solicitante,
            a.quantidade_participantes,
            a.observacoes
        FROM agendamentos a
        WHERE a.espaco_id = ? 
        AND a.status != 'cancelado'
        ORDER BY a.data_inicio ASC
    ");
    
    $stmt->execute([$espaco_id]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatar datas para o FullCalendar
    foreach ($agendamentos as &$agendamento) {
        // Criar DateTime assumindo que a data do banco está em America/Sao_Paulo
        $start = new DateTime($agendamento['start'], new DateTimeZone('America/Sao_Paulo'));
        $end = new DateTime($agendamento['end'], new DateTimeZone('America/Sao_Paulo'));
        
        // Converter para formato ISO 8601 mantendo o timezone
        $agendamento['start'] = $start->format('c');
        $agendamento['end'] = $end->format('c');
        
        // Definir cor baseada no status
        switch ($agendamento['status']) {
            case 'aprovado':
                $agendamento['backgroundColor'] = '#28a745';
                $agendamento['borderColor'] = '#28a745';
                break;
            case 'pendente':
                $agendamento['backgroundColor'] = '#ffc107';
                $agendamento['borderColor'] = '#ffc107';
                break;
            default:
                $agendamento['backgroundColor'] = '#6c757d';
                $agendamento['borderColor'] = '#6c757d';
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($agendamentos);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar agendamentos: ' . $e->getMessage()]);
}
?> 
