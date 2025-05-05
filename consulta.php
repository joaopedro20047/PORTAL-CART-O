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

function salvarArquivosReenviados($protocolo) {
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $arquivos_salvos = [];

    foreach ($_FILES['documentos']['name'] as $i => $name) {
        if ($_FILES['documentos']['error'][$i] === 0) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $nome_final = uniqid("reenviado_") . '.' . $ext;
            $destino = $upload_dir . $nome_final;
            if (move_uploaded_file($_FILES['documentos']['tmp_name'][$i], $destino)) {
                $arquivos_salvos[] = $destino;
            }
        }
    }

    // Atualiza status para "Aguardando Análise"
    $sql = "UPDATE solicitacoes SET status = 'Aguardando Análise', p = '' WHERE protocolo = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$protocolo]);

    return $arquivos_salvos;
}

function isLikelyBot() {
    $honeypot = $_POST['honeypot'] ?? '';
    $submission_time = $_POST['submission_time'] ?? 0;
    $time_elapsed = (time() * 1000) - (int)$submission_time; // Time in milliseconds

    if (!empty($honeypot) || ($submission_time > 0 && $time_elapsed < 1000)) { // Adjust time threshold (1000ms = 1 second)
        return true;
    }

    return false;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['protocolo']) && isset($_POST['cpf']) && !isset($_FILES['documentos'])) {
        // Basic bot check
        if (isLikelyBot()) {
            echo json_encode(['success' => false, 'error' => 'Suspected bot activity.']);
            exit;
        }

        // Consulta
        $protocolo = $_POST['protocolo'];
        $cpf = $_POST['cpf'];
        $stmt = $pdo->prepare("SELECT nome, protocolo, data_envio, status, p FROM solicitacoes WHERE protocolo = ? AND cpf = ?");
        $stmt->execute([$protocolo, $cpf]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res) {
            echo json_encode([
                'success' => true,
                'nome' => $res['nome'],
                'protocolo' => $res['protocolo'],
                'data_envio' => $res['data_envio'],
                'status' => $res['status'],
                'p' => $res['p']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Protocolo e CPF não encontrados ou não correspondem.']);
        }
    }

    if (isset($_FILES['documentos'])) {
        $protocolo = $_POST['protocolo'];
        $arquivos = salvarArquivosReenviados($protocolo);

        echo json_encode([
            'success' => true,
            'reenviados' => $arquivos
        ]);
    }
}
?>