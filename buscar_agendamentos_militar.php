<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['militar_id'])) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT a.*, e.nome as nome_espaco
        FROM agendamentos a
        JOIN espacos e ON a.espaco_id = e.id
        WHERE a.militar_id = ?
        ORDER BY a.data_inicio DESC
    ");
    
    $stmt->execute([$_GET['militar_id']]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatar datas para incluir timezone (assumindo que estão em America/Sao_Paulo)
    foreach ($agendamentos as &$agendamento) {
        $data_inicio = new DateTime($agendamento['data_inicio'], new DateTimeZone('America/Sao_Paulo'));
        $data_fim = new DateTime($agendamento['data_fim'], new DateTimeZone('America/Sao_Paulo'));
        
        // Converter para formato ISO com timezone para o JavaScript interpretar corretamente
        $agendamento['data_inicio'] = $data_inicio->format('c');
        $agendamento['data_fim'] = $data_fim->format('c');
    }
    
    echo json_encode($agendamentos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar agendamentos']);
} 