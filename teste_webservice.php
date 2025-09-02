<?php
// Configurações do WebService
$ws_url = 'http://webservice.ccabr.intraer';
$ws_user = 'bant';
$ws_pass = 'b@nt_rt2022';
$om = 'bant';
$ws_om = 'bant';

// Função para fazer a requisição ao WebService
function fazerRequisicao($url, $user, $pass, $om) {
    $ch = curl_init();
    $url_completa = $url . "?om=" . urlencode($om);
    curl_setopt($ch, CURLOPT_URL, $url_completa);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('Erro na requisição: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'data' => $response
    ];
}

// Array para armazenar os resultados dos testes
$testes = [];

// Teste 1: Conexão básica
try {
    $resultado = fazerRequisicao("$ws_url/api/efetivo", $ws_user, $ws_pass, $om);
    $testes[] = [
        'nome' => 'Teste de Conexão',
        'status' => $resultado['code'] === 200 ? 'success' : 'danger',
        'mensagem' => $resultado['code'] === 200 ? 'Conexão estabelecida com sucesso' : 'Erro na conexão',
        'detalhes' => [
            'Código HTTP' => $resultado['code'],
            'Resposta' => $resultado['data']
        ]
    ];
} catch (Exception $e) {
    $testes[] = [
        'nome' => 'Teste de Conexão',
        'status' => 'danger',
        'mensagem' => 'Erro na conexão: ' . $e->getMessage(),
        'detalhes' => []
    ];
}

// Teste 2: Autenticação
try {
    $resultado = fazerRequisicao("$ws_url/api/efetivo", 'usuario_errado', 'senha_errada', $ws_om);
    $testes[] = [
        'nome' => 'Teste de Autenticação',
        'status' => $resultado['code'] === 401 ? 'success' : 'danger',
        'mensagem' => $resultado['code'] === 401 ? 'Autenticação funcionando corretamente' : 'Erro na autenticação',
        'detalhes' => [
            'Código HTTP' => $resultado['code'],
            'Resposta' => $resultado['data']
        ]
    ];
} catch (Exception $e) {
    $testes[] = [
        'nome' => 'Teste de Autenticação',
        'status' => 'danger',
        'mensagem' => 'Erro no teste de autenticação: ' . $e->getMessage(),
        'detalhes' => []
    ];
}

// Teste 3: Dados do Efetivo
try {
    $resultado = fazerRequisicao("$ws_url/api/efetivo", $ws_user, $ws_pass, $ws_om);
    $dados = json_decode($resultado['data'], true);
    
    $testes[] = [
        'nome' => 'Teste de Dados',
        'status' => is_array($dados) ? 'success' : 'danger',
        'mensagem' => is_array($dados) ? 'Dados recebidos com sucesso' : 'Erro ao processar dados',
        'detalhes' => [
            'Código HTTP' => $resultado['code'],
            'Formato dos Dados' => is_array($dados) ? 'JSON válido' : 'JSON inválido',
            'Quantidade de Registros' => is_array($dados) ? count($dados) : 'N/A',
            'Exemplo de Dados' => is_array($dados) ? array_slice($dados, 0, 1) : 'N/A'
        ]
    ];
} catch (Exception $e) {
    $testes[] = [
        'nome' => 'Teste de Dados',
        'status' => 'danger',
        'mensagem' => 'Erro ao testar dados: ' . $e->getMessage(),
        'detalhes' => []
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Integração - WebService BANT</title>
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
        .teste-card {
            margin-bottom: 1rem;
        }
        .detalhes-pre {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.25rem;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-bant">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Teste de Integração - WebService</h1>
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
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Configurações do WebService</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <p><strong>URL:</strong> <?php echo htmlspecialchars($ws_url); ?></p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Usuário:</strong> <?php echo htmlspecialchars($ws_user); ?></p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>OM:</strong> <?php echo htmlspecialchars($ws_om); ?></p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Status:</strong> 
                                    <?php 
                                    $todosSucesso = true;
                                    foreach ($testes as $teste) {
                                        if ($teste['status'] !== 'success') {
                                            $todosSucesso = false;
                                            break;
                                        }
                                    }
                                    echo $todosSucesso ? 
                                        '<span class="badge bg-success">Conectado</span>' : 
                                        '<span class="badge bg-danger">Erro</span>';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php foreach ($testes as $teste): ?>
                <div class="card teste-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-<?php echo $teste['status'] === 'success' ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i>
                            <?php echo htmlspecialchars($teste['nome']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo htmlspecialchars($teste['mensagem']); ?></p>
                        
                        <?php if (!empty($teste['detalhes'])): ?>
                        <div class="mt-3">
                            <h6>Detalhes:</h6>
                            <div class="detalhes-pre">
                                <pre><?php echo json_encode($teste['detalhes'], JSON_PRETTY_PRINT); ?></pre>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Base Aérea de Natal. ETIC | Desenvolvimento.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 