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
    $required_fields = [
        'militar_id', 'espaco_id', 'nome_solicitante', 'posto_graduacao', 'setor', 'ramal',
        'email_solicitante', 'nome_evento', 'quantidade_participantes',
        'data_inicio', 'data_fim', 'observacoes'
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Campo obrigatório não preenchido: {$field}");
        }
    }

    // Validar formato do email
    if (!filter_var($_POST['email_solicitante'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inválido");
    }

    // Converter datas para o formato MySQL, considerando o fuso horário
    $data_inicio = new DateTime($_POST['data_inicio']);
    $data_fim = new DateTime($_POST['data_fim']);
    
    // Ajustar para o fuso horário local (UTC-3)
    $data_inicio->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    $data_fim->setTimezone(new DateTimeZone('America/Sao_Paulo'));
    
    $data_inicio_mysql = $data_inicio->format('Y-m-d H:i:s');
    $data_fim_mysql = $data_fim->format('Y-m-d H:i:s');

    // Buscar configurações
    $stmt = $conn->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    // Validar antecedência
    $agora = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    $diferenca = $agora->diff($data_inicio);
    $horas_antecedencia = ($diferenca->days * 24) + $diferenca->h;
    
    if ($horas_antecedencia < $config['antecedencia_horas']) {
        throw new Exception("O agendamento deve ser feito com pelo menos {$config['antecedencia_horas']} horas de antecedência");
    }

    // Validar duração máxima
    $duracao = $data_inicio->diff($data_fim);
    
    if ($duracao->h > $config['max_horas_consecutivas']) {
        throw new Exception("A duração máxima permitida é de {$config['max_horas_consecutivas']} horas");
    }

    // Verificar conflitos de horário
    $stmt = $conn->prepare("
        SELECT nome_evento, data_inicio, data_fim 
        FROM agendamentos 
        WHERE espaco_id = ? 
        AND (
            (data_inicio < ? AND data_fim > ?) OR  -- Novo agendamento começa durante um existente
            (data_inicio < ? AND data_fim > ?) OR  -- Novo agendamento termina durante um existente
            (data_inicio >= ? AND data_fim <= ?)   -- Novo agendamento está completamente dentro de um existente
        )
    ");
    $stmt->execute([
        $_POST['espaco_id'],
        $data_fim_mysql, $data_inicio_mysql,  // Para verificar se começa durante
        $data_fim_mysql, $data_inicio_mysql,  // Para verificar se termina durante
        $data_inicio_mysql, $data_fim_mysql   // Para verificar se está dentro
    ]);
    
    $conflito = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($conflito) {
        $data_conflito = new DateTime($conflito['data_inicio']);
        $data_conflito->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        $fim_conflito = new DateTime($conflito['data_fim']);
        $fim_conflito->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        
        throw new Exception(
            "Já existe um agendamento para este horário: " .
            $conflito['nome_evento'] . " das " .
            $data_conflito->format('H:i') . " às " .
            $fim_conflito->format('H:i')
        );
    }

    // Inserir agendamento
    $stmt = $conn->prepare("
        INSERT INTO agendamentos (
            militar_id, espaco_id, nome_solicitante, posto_graduacao, setor, ramal,
            email_solicitante, nome_evento, quantidade_participantes,
            observacoes, data_inicio, data_fim
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['militar_id'],
        $_POST['espaco_id'],
        $_POST['nome_solicitante'],
        $_POST['posto_graduacao'],
        $_POST['setor'],
        $_POST['ramal'],
        $_POST['email_solicitante'],
        $_POST['nome_evento'],
        $_POST['quantidade_participantes'],
        isset($_POST['observacoes']) ? $_POST['observacoes'] : null,
        $data_inicio_mysql,
        $data_fim_mysql
    ]);

    $agendamento_id = $conn->lastInsertId();

    // Buscar informações do espaço
    $stmt = $conn->prepare("SELECT nome FROM espacos WHERE id = ?");
    $stmt->execute([$_POST['espaco_id']]);
    $espaco = $stmt->fetch(PDO::FETCH_ASSOC);

    // Enviar email para a comunicação social
    $assunto = "Novo Agendamento - Sistema BANT";
    $mensagem = "
        <h2>Novo Agendamento Realizado</h2>
        <p><strong>Evento:</strong> {$_POST['nome_evento']}</p>
        <p><strong>Espaço:</strong> {$espaco['nome']}</p>
        <p><strong>Data:</strong> " . $data_inicio->format('d/m/Y') . "</p>
        <p><strong>Horário:</strong> " . $data_inicio->format('H:i') . " às " . $data_fim->format('H:i') . "</p>
        <p><strong>Solicitante:</strong> {$_POST['posto_graduacao']} {$_POST['nome_solicitante']}</p>
        <p><strong>Email:</strong> {$_POST['email_solicitante']}</p>
        <p><strong>Setor:</strong> {$_POST['setor']}</p>
        <p><strong>Ramal:</strong> {$_POST['ramal']}</p>
        <p><strong>Número de Participantes:</strong> {$_POST['quantidade_participantes']}</p>
        <p><strong>Observações/Link:</strong> " . (isset($_POST['observacoes']) ? $_POST['observacoes'] : "Nenhuma") . "</p>
        <p>Acesse o sistema para aprovar ou cancelar este agendamento.</p>
    ";

    // Se houver arquivo enviado, salvar no servidor e adicionar link no email (sem anexos)
    if (isset($_FILES['anexo']) && isset($_FILES['anexo']['error']) && $_FILES['anexo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['anexo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo.');
        }
        $tmpPath = $_FILES['anexo']['tmp_name'];
        $originalName = $_FILES['anexo']['name'];
        $sizeBytes = $_FILES['anexo']['size'];

        if ($sizeBytes > 2 * 1024 * 1024) {
            throw new Exception('O arquivo deve ter no máximo 2MB.');
        }

        $allowedMime = ['image/png', 'image/jpeg', 'application/pdf'];
        $detectedType = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detectedType = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);
            }
        }
        if ($detectedType === null && function_exists('mime_content_type')) {
            $detectedType = mime_content_type($tmpPath);
        }
        if ($detectedType === null) {
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (in_array($ext, ['png'])) $detectedType = 'image/png';
            if (in_array($ext, ['jpg','jpeg'])) $detectedType = 'image/jpeg';
            if ($ext === 'pdf') $detectedType = 'application/pdf';
        }
        if ($detectedType === null || !in_array($detectedType, $allowedMime, true)) {
            throw new Exception('Tipo de arquivo inválido. Permitidos: PNG, JPG, JPEG ou PDF.');
        }

        $extMap = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'application/pdf' => 'pdf'
        ];
        $safeExt = isset($extMap[$detectedType]) ? $extMap[$detectedType] : strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $uploadDir = __DIR__ . '/uploads/agendamentos';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                throw new Exception('Não foi possível criar o diretório de upload.');
            }
        }
        $filename = 'agendamento_' . $agendamento_id . '_' . date('Ymd_His') . '.' . $safeExt;
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmpPath, $destPath)) {
            throw new Exception('Falha ao salvar o arquivo enviado.');
        }
        // Montar URL pública
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $publicPath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
        $fileUrl = $protocol . '://' . $host . $publicPath . '/uploads/agendamentos/' . rawurlencode($filename);
        $mensagem .= "<p><strong>Documento enviado:</strong> <a href='" . htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8') . "' target='_blank' rel='noopener noreferrer'>Clique para visualizar</a></p>";
    }

    // Enviar email para a comunicação social
    enviarEmail($config['email_comunicacao'], $assunto, $mensagem);

    // Enviar email adicional para a Sala de Videoconferência (sem anexos; com link, se houver)
    if ($espaco['nome'] === 'Sala de Videoconferência') {
        enviarEmail('etic.bant@fab.mil.br', $assunto, $mensagem);
    }

    // Enviar email adicional para o Auditório Cine Navy
    if ($espaco['nome'] === 'Auditório Cine Navy') {
        enviarEmail($config['email_sindico_cine_navy'], $assunto, $mensagem);
    }

    // Enviar email de confirmação para o solicitante
    $assunto_solicitante = "Confirmação de Agendamento - Sistema BANT";
    $mensagem_solicitante = "
        <h2>Seu Agendamento foi Registrado</h2>
        <p>Olá {$_POST['nome_solicitante']},</p>
        <p>Seu agendamento foi registrado com sucesso e está aguardando aprovação.</p>
        <p><strong>Evento:</strong> {$_POST['nome_evento']}</p>
        <p><strong>Espaço:</strong> {$espaco['nome']}</p>
        <p><strong>Data:</strong> " . $data_inicio->format('d/m/Y') . "</p>
        <p><strong>Horário:</strong> " . $data_inicio->format('H:i') . " às " . $data_fim->format('H:i') . "</p>
        <p>Você receberá um email quando o status do seu agendamento for atualizado.</p>
    ";

    // Enviar email para o solicitante
    enviarEmail($_POST['email_solicitante'], $assunto_solicitante, $mensagem_solicitante);

    // Retornar sucesso com status 200
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Agendamento realizado com sucesso',
        'agendamento_id' => $agendamento_id
    ]);
} catch (Exception $e) {
    // Retornar erro com status 400 apenas para erros de validação
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error_type' => 'validation_error'
    ]);
}
?> 
