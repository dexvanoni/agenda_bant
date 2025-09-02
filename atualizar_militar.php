<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['posto_graduacao']) || !isset($data['nome_guerra']) || 
    !isset($data['esquadrao_setor']) || !isset($data['email_fab']) || !isset($data['ramal'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

try {
    // Atualizar militar
    $stmt = $conn->prepare("
        UPDATE militares 
        SET posto_graduacao = ?,
            nome_guerra = ?,
            esquadrao_setor = ?,
            email_fab = ?,
            ramal = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['posto_graduacao'],
        $data['nome_guerra'],
        $data['esquadrao_setor'],
        $data['email_fab'],
        $data['ramal'],
        $data['id']
    ]);

    echo json_encode(['success' => true, 'message' => 'Dados do militar atualizados com sucesso']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar dados do militar: ' . $e->getMessage()]);
} 