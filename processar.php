<?php
header('Content-Type: application/json');

$host = '187.33.86.178';
$port = '5438';
$db   = 'sde_sinalvida';
$user = 'postgres';
$pass = '12345';
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro na conexão: ' . $e->getMessage()]);
    exit;
}

function gerarProtocolo() {
    return substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 10);
}

function salvarArquivo($campo, $prefixo) {
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] !== 0) return null;
    $ext = pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION);
    $nomeFinal = uniqid($prefixo . "_") . '.' . $ext;
    $destino = 'uploads/' . $nomeFinal;

    if (!is_dir('uploads')) mkdir('uploads', 0777, true);

    return move_uploaded_file($_FILES[$campo]['tmp_name'], $destino) ? $destino : null;
}

$tipo = trim($_POST['tipo_credencial'] ?? '');
$nome = $_POST['nome'] ?? '';
$data_nascimento = $_POST['data_nascimento'] ?? '';
$email = $_POST['email'] ?? '';
$telefone = $_POST['telefone'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$tipo_doc = $_POST['tipo_doc'] ?? '';
$data_envio = date('Y-m-d');
$protocolo = gerarProtocolo();

$caminho_residencia = salvarArquivo('residencia', 'residencia');
$caminho_laudo = ($tipo === 'PCD') ? salvarArquivo('laudo', 'laudo') : null;

$caminho_rg_frente = null;
$caminho_rg_verso = null;
$caminho_cnh_frente = null;
$caminho_cnh_verso = null;
$caminho_passaporte = null;

if ($tipo_doc === 'RG') {
    $caminho_rg_frente = salvarArquivo('doc_frente', 'rg_frente');
    $caminho_rg_verso = salvarArquivo('doc_verso', 'rg_verso');
} elseif ($tipo_doc === 'CNH') {
    $caminho_cnh_frente = salvarArquivo('doc_frente', 'cnh_frente');
    $caminho_cnh_verso = salvarArquivo('doc_verso', 'cnh_verso');
} elseif ($tipo_doc === 'Passaporte') {
    $caminho_passaporte = salvarArquivo('doc_passaporte', 'passaporte');
}

try {
    $stmt = $pdo->prepare("INSERT INTO solicitacoes (
        tipo_credencial, nome, data_nascimento, email, telefone, cpf, protocolo, 
        caminho_rg_frente, caminho_rg_verso, caminho_cnh_frente, caminho_cnh_verso, caminho_passaporte, 
        caminho_residencia, caminho_laudo, data_envio
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $tipo, $nome, $data_nascimento, $email, $telefone, $cpf, $protocolo,
        $caminho_rg_frente, $caminho_rg_verso, $caminho_cnh_frente, $caminho_cnh_verso, $caminho_passaporte,
        $caminho_residencia, $caminho_laudo, $data_envio
    ]);

    $response = ['success' => true, 'protocolo' => $protocolo];  // Store the base response

    //  Send confirmation email to Node.js API
    $node_api_url = 'http://localhost:3001/email/confirmacao'; // Adjust if needed
    $data_to_send = [
        'email' => $email,
        'nome' => $nome,
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
        $response['email_error'] = 'Erro ao enviar email de confirmação.';
        //  Log the error (optional)
        error_log("Erro ao enviar email de confirmação para: " . $email . " - Protocolo: " . $protocolo);
    }


    echo json_encode($response);  // Send the response (with potential email error)


} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar: ' . $e->getMessage()]);
}
exit;
?>