<?php
// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_orgao = $_POST['nome_orgao'] ?? '';
    $nome_responsavel = $_POST['nome_responsavel'] ?? '';  // ADDED FIELD
    $envio_automatico = isset($_POST['envio_automatico']) ? 1 : 0;
    $observacao_personalizada = isset($_POST['observacao_personalizada']) ? 1 : 0;

    $dados = [
        'nome_orgao' => $nome_orgao,
        'nome_responsavel' => $nome_responsavel,  // ADDED FIELD
        'envio_automatico' => $envio_automatico,
        'observacao_personalizada' => $observacao_personalizada
    ];

    if (!is_dir('config')) {
        mkdir('config', 0777, true);
    }

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_nome = 'config/logo.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $foto_nome);
        $dados['foto'] = $foto_nome;
    }

    file_put_contents('config/config.json', json_encode($dados));
    header('Location: configuracoes.html');
    exit;
}
?>