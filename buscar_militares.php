<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['busca'])) {
    echo json_encode([]);
    exit();
}

$busca = '%' . $_GET['busca'] . '%';

try {
    $stmt = $conn->prepare("
        SELECT id, posto_graduacao, nome_guerra, esquadrao_setor, email_fab, ramal
        FROM militares 
        WHERE status = 'ativo' 
        AND (
            posto_graduacao LIKE ? 
            OR nome_guerra LIKE ? 
            OR esquadrao_setor LIKE ?
        )
        ORDER BY posto_graduacao, nome_guerra
        LIMIT 10
    ");
    
    $stmt->execute([$busca, $busca, $busca]);
    $militares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($militares);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar militares']);
} 