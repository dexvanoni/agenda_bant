<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

// Receber dados do POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
    exit();
}

try {
    // Verificar se o militar está em uso em algum agendamento
    $stmt = $conn->prepare("SELECT COUNT(*) FROM agendamentos WHERE nome_solicitante = (SELECT nome_guerra FROM militares WHERE id = ?)");
    $stmt->execute([$data['id']]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        // Se estiver em uso, apenas marcar como inativo
        $stmt = $conn->prepare("UPDATE militares SET status = 'inativo' WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true, 'message' => 'Militar marcado como inativo pois possui agendamentos']);
    } else {
        // Se não estiver em uso, excluir permanentemente
        $stmt = $conn->prepare("DELETE FROM militares WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true, 'message' => 'Militar excluído com sucesso']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir militar: ' . $e->getMessage()]);
} 