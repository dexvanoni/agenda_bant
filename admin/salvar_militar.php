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

if (!isset($data['posto_graduacao']) || !isset($data['nome_guerra']) || !isset($data['esquadrao_setor']) || 
    !isset($data['email_fab']) || !isset($data['ramal'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

try {
    if (empty($data['id'])) {
        // Inserir novo militar
        $stmt = $conn->prepare("
            INSERT INTO militares (posto_graduacao, nome_guerra, esquadrao_setor, email_fab, ramal, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['posto_graduacao'],
            $data['nome_guerra'],
            $data['esquadrao_setor'],
            $data['email_fab'],
            $data['ramal'],
            $data['status'] ?? 'ativo'
        ]);
    } else {
        // Atualizar militar existente
        $stmt = $conn->prepare("
            UPDATE militares 
            SET posto_graduacao = ?,
                nome_guerra = ?,
                esquadrao_setor = ?,
                email_fab = ?,
                ramal = ?,
                status = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['posto_graduacao'],
            $data['nome_guerra'],
            $data['esquadrao_setor'],
            $data['email_fab'],
            $data['ramal'],
            $data['status'],
            $data['id']
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Militar salvo com sucesso']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar militar: ' . $e->getMessage()]);
} 