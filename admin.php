<?php
session_start();
header('Content-Type: application/json');

// Configuração do banco
$host = '187.33.86.178';
$port = '5438';
$db   = 'sde_sinalvida';
$user = 'postgres';
$pass = '12345';
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $e->getMessage()]);
    exit;
}

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['acao']) || $_POST['acao'] === 'login')) {
    $usuario = trim($_POST['user'] ?? '');
    $senha = trim($_POST['pass'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM login WHERE TRIM("user") = ? AND pass = ?');
    $stmt->execute([$usuario, $senha]);
    $usuarioEncontrado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuarioEncontrado) {
        $_SESSION['admin_logado'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// LISTAR SOLICITAÇÕES
if (isset($_GET['listar'])) {
    $res = [];

    // Solicitações Aprovadas
    $stmt = $pdo->prepare("SELECT nome, protocolo FROM solicitacoes WHERE status = 'Aprovado'");
    $stmt->execute();
    $res['aprovadas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Solicitações Reprovadas
    $stmt = $pdo->prepare("SELECT nome, protocolo FROM solicitacoes WHERE status = 'Reprovado'");
    $stmt->execute();
    $res['reprovadas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Solicitações Pendentes (Aguardando Análise ou Pendente)
    $stmt = $pdo->prepare("SELECT nome, protocolo FROM solicitacoes WHERE status IS NULL OR status = '' OR status = 'Aguardando Análise' OR status = 'Pendente' OR status = 'Reenvio'");
    $stmt->execute();
    $res['pendentes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Solicitações Aguardando Reenvio (Status "Reenvio")
    $stmt = $pdo->prepare("SELECT nome, protocolo FROM solicitacoes WHERE status = 'Reenvio'");
    $stmt->execute();
    $res['reenvio'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($res);
    exit;
}

// DETALHES DE UMA SOLICITAÇÃO
if (isset($_GET['detalhes'])) {
    $protocolo = $_GET['detalhes'];

    $stmt = $pdo->prepare("SELECT * FROM solicitacoes WHERE protocolo = ?");
    $stmt->execute([$protocolo]);
    $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitacao) {
        echo json_encode(['error' => 'Solicitação não encontrada']);
        exit;
    }

    $documentos = [];
    $campos_documentos = [
        'caminho_rg_frente' => 'RG Frente',
        'caminho_rg_verso' => 'RG Verso',
        'caminho_cnh_frente' => 'CNH Frente',
        'caminho_cnh_verso' => 'CNH Verso',
        'caminho_passaporte' => 'Passaporte',
        'caminho_residencia' => 'Comprovante de Residência',
        'caminho_laudo' => 'Laudo Médico'
    ];


    foreach ($campos_documentos as $campo => $label) {
        if (!empty($solicitacao[$campo]) && preg_match('/\.(jpg|jpeg|png|pdf)$/i', $solicitacao[$campo])) {
            $documentos[$label] = '/soli/uploads/' . basename($solicitacao[$campo]);
        }
    }

    echo json_encode([
        'nome' => $solicitacao['nome'],
        'email' => $solicitacao['email'],
        'telefone' => $solicitacao['telefone'],
        'data_nascimento' => $solicitacao['data_nascimento'],
        'data_envio' => $solicitacao['data_envio'],
        'tipo_credencial' => $solicitacao['tipo_credencial'],
        'status' => $solicitacao['status'],
        'documentos' => $documentos
    ]);
    exit;
}

// ATUALIZAR STATUS E OBSERVAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'status') {
    $status = $_POST['status'] ?? '';
    $protocolo = $_POST['protocolo'] ?? '';
    $obs = $_POST['obs'] ?? '';

    $stmt = $pdo->prepare("UPDATE solicitacoes SET status = ?, obs = ? WHERE protocolo = ?");
    $stmt->execute([$status, $obs, $protocolo]);

    //  Fetch email and nome to send to Node.js
    $stmt = $pdo->prepare("SELECT email, nome FROM solicitacoes WHERE protocolo = ?");
    $stmt->execute([$protocolo]);
    $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($solicitacao) {
        $node_api_url = 'http://localhost:3001/email/status'; // Adjust if needed
        $data_to_send = [
            'email' => $solicitacao['email'],
            'nome' => $solicitacao['nome'],
            'protocolo' => $protocolo
        ];
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/json',
                'content' => json_encode($data_to_send)
            ]
        ];
        $context  = stream_context_create($options);
        $node_response = file_get_contents($node_api_url, false, $context);

        $node_result = json_decode($node_response, true);
        if (!$node_result || !$node_result['success']) {
            //  Log the error (optional)
            error_log("Erro ao enviar email de status para: " . $solicitacao['email'] . " - Protocolo: " . $protocolo);
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

// SOLICITAR REENVIO DE DOCUMENTOS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'pendente') {
    $documentos = $_POST['p'] ?? '';
    $protocolo = $_POST['protocolo'] ?? '';

    $stmt = $pdo->prepare("UPDATE solicitacoes SET status = 'Reenvio', p = ? WHERE protocolo = ?");
    $stmt->execute([$documentos, $protocolo]);

    //  Fetch email and nome to send to Node.js (similar to status update)
    $stmt = $pdo->prepare("SELECT email, nome FROM solicitacoes WHERE protocolo = ?");
    $stmt->execute([$protocolo]);
    $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($solicitacao) {
        $node_api_url = 'http://localhost:3001/email/status'; // Adjust if needed
        $data_to_send = [
            'email' => $solicitacao['email'],
            'nome' => $solicitacao['nome'],
            'protocolo' => $protocolo
        ];
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/json',
                'content' => json_encode($data_to_send)
            ]
        ];
        $context  = stream_context_create($options);
        $node_response = file_get_contents($node_api_url, false, $context);

        $node_result = json_decode($node_response, true);
        if (!$node_result || !$node_result['success']) {
            //  Log the error (optional)
            error_log("Erro ao enviar email de reenvio para: " . $solicitacao['email'] . " - Protocolo: " . $protocolo);
        }
    }


    echo json_encode(['success' => true]);
    exit;
}
?>