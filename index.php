<?php
session_start();
require 'config.php';

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $message = '';
    $error = '';

    try {
        // MOTORISTAS
        if ($action == 'add_motorista') {
            $stmt = $pdo->prepare("INSERT INTO motoristas (nome, cnh, validade_cnh, status_motorista) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['nome'],
                $_POST['cnh'],
                $_POST['validade_cnh'],
                'disponivel'
            ]);
            $message = "Motorista adicionado com sucesso!";
        }

        // VEÍCULOS
        elseif ($action == 'add_veiculo') {
            $stmt = $pdo->prepare("INSERT INTO veiculos (placa, modelo, capacidade_carga, km_atual, data_ultima_manutencao, status_veiculo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['placa'],
                $_POST['modelo'],
                $_POST['capacidade_carga'],
                $_POST['km_atual'],
                $_POST['data_ultima_manutencao'],
                'disponivel'
            ]);
            $message = "Veículo adicionado com sucesso!";
        }

        // ROTAS
        elseif ($action == 'add_rota') {
            $stmt = $pdo->prepare("INSERT INTO rotas (origem, destino, distancia_estimada_km, tempo_estimado_horas) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['origem'],
                $_POST['destino'],
                $_POST['distancia_estimada_km'],
                $_POST['tempo_estimado_horas']
            ]);
            $message = "Rota adicionada com sucesso!";
        }

        // ENTREGAS
        elseif ($action == 'add_entrega') {
            $codigo_rastreio = 'TRK' . date('YmdHis') . rand(1000, 9999);
            $stmt = $pdo->prepare("INSERT INTO entregas (codigo_rastreio, cliente_nome, endereco_destino, status_entrega, id_motorista, id_veiculo, id_rota) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $codigo_rastreio,
                $_POST['cliente_nome'],
                $_POST['endereco_destino'],
                'pendente',
                $_POST['id_motorista'] ?: null,
                $_POST['id_veiculo'] ?: null,
                $_POST['id_rota'] ?: null
            ]);
            $message = "Entrega criada com sucesso! Código de rastreio: " . $codigo_rastreio;
        }

        // OCORRÊNCIAS
        elseif ($action == 'add_ocorrencia') {
            $stmt = $pdo->prepare("INSERT INTO ocorrencias (id_entrega, tipo_ocorrencia, descricao) VALUES (?, ?, ?)");
            $stmt->execute([
                $_POST['id_entrega'],
                $_POST['tipo_ocorrencia'],
                $_POST['descricao']
            ]);
            $message = "Ocorrência registrada com sucesso!";
        }

        // MANUTENÇÕES
        elseif ($action == 'add_manutencao') {
            $stmt = $pdo->prepare("INSERT INTO manutencoes (id_veiculo, data_manutencao, tipo_manutencao, km_na_manutencao, descricao, custo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id_veiculo'],
                $_POST['data_manutencao'],
                $_POST['tipo_manutencao'],
                $_POST['km_na_manutencao'],
                $_POST['descricao'],
                $_POST['custo']
            ]);
            $message = "Manutenção registrada com sucesso!";
        }

        // ATUALIZAR STATUS ENTREGA
        elseif ($action == 'atualizar_status_entrega') {
            $stmt = $pdo->prepare("UPDATE entregas SET status_entrega = ? WHERE id_entrega = ?");
            $stmt->execute([
                $_POST['novo_status'],
                $_POST['id_entrega']
            ]);
            $message = "Status da entrega atualizado!";
        }

        $_SESSION['message'] = $message;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $error = "Erro: " . $e->getMessage();
        $_SESSION['error'] = $error;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Obter dados para listas
$motoristas = $pdo->query("SELECT * FROM motoristas ORDER BY nome")->fetchAll();
$veiculos = $pdo->query("SELECT * FROM veiculos ORDER BY placa")->fetchAll();
$rotas = $pdo->query("SELECT * FROM rotas ORDER BY origem")->fetchAll();
$entregas = $pdo->query("SELECT e.*, m.nome as motorista_nome, v.placa, r.origem FROM entregas e LEFT JOIN motoristas m ON e.id_motorista = m.id_motorista LEFT JOIN veiculos v ON e.id_veiculo = v.id_veiculo LEFT JOIN rotas r ON e.id_rota = r.id_rota ORDER BY e.data_criacao DESC")->fetchAll();
$ocorrencias = $pdo->query("SELECT o.*, e.codigo_rastreio FROM ocorrencias o LEFT JOIN entregas e ON o.id_entrega = e.id_entrega ORDER BY o.data_hora DESC")->fetchAll();
$manutencoes = $pdo->query("SELECT m.*, v.placa FROM manutencoes m LEFT JOIN veiculos v ON m.id_veiculo = v.id_veiculo ORDER BY m.data_manutencao DESC")->fetchAll();

// Estatísticas
$total_motoristas = count($motoristas);
$total_veiculos = count($veiculos);
$total_entregas = count($entregas);
$entregas_pendentes = count(array_filter($entregas, fn($e) => $e['status_entrega'] == 'pendente'));

$current_page = $_GET['page'] ?? 'dashboard';
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$page_titles = [
    'dashboard' => ['title' => 'Command Center', 'subtitle' => 'Visão geral da operação logística em tempo real'],
    'motoristas' => ['title' => 'Motoristas', 'subtitle' => 'Cadastro e status da equipe de condução'],
    'veiculos' => ['title' => 'Frota', 'subtitle' => 'Gestão de veículos e capacidade de carga'],
    'rotas' => ['title' => 'Rotas', 'subtitle' => 'Mapeamento de origem, destino e métricas'],
    'entregas' => ['title' => 'Entregas', 'subtitle' => 'Rastreamento e fluxo de despacho'],
    'ocorrencias' => ['title' => 'Ocorrências', 'subtitle' => 'Registro de incidentes operacionais'],
    'manutencoes' => ['title' => 'Manutenções', 'subtitle' => 'Histórico preventivo e corretivo da frota'],
];
$page_meta = $page_titles[$current_page] ?? $page_titles['dashboard'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXUS FLEET — Controle Logístico</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" defer></script>
    <script src="app.js" defer></script>
</head>
<body>
    <canvas id="particle-canvas" aria-hidden="true"></canvas>
    <div class="scanlines" aria-hidden="true"></div>

    <button type="button" class="sidebar-toggle" aria-label="Abrir menu">
        <span></span><span></span><span></span>
    </button>

    <div class="hologram-deco" aria-hidden="true">
        <div class="hologram-cube">
            <div class="face f1"></div><div class="face f2"></div><div class="face f3"></div>
            <div class="face f4"></div><div class="face f5"></div><div class="face f6"></div>
        </div>
    </div>

    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-logo">
                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                    </div>
                    <div>
                        <h1>Nexus Fleet</h1>
                        <p>Logistics OS v2.0</p>
                    </div>
                </div>
            </div>

            <nav class="nav-menu">
                <a href="?page=dashboard" class="<?= $current_page == 'dashboard' ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                    Dashboard
                </a>
                <a href="?page=motoristas" class="<?= $current_page == 'motoristas' ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    Motoristas
                </a>
                <a href="?page=veiculos" class="<?= $current_page == 'veiculos' ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4z"/></svg>
                    Veículos
                </a>
                <a href="?page=rotas" class="<?= $current_page == 'rotas' ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM10 5.47l4 1.4v11.66l-4-1.4V5.47zm-5 .99l3-1.01v11.7l-3 1.01V6.46zm14 11.08l-3 1.01V6.86l3-1.01v11.69z"/></svg>
                    Rotas
                </a>
                <a href="?page=entregas" class="<?= $current_page == 'entregas' ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                    Entregas
                </a>
                <a href="?page=ocorrencias" class="<?= $current_page == 'ocorrencias' ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                    Ocorrências
                </a>
                <a href="?page=manutencoes" class="<?= $current_page == 'manutencoes' ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/></svg>
                    Manutenções
                </a>
            </nav>

            <div class="sidebar-footer">SYS ONLINE · <?= date('Y') ?></div>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div class="page-title-block">
                    <h2><?= htmlspecialchars($page_meta['title']) ?></h2>
                    <p class="subtitle"><?= htmlspecialchars($page_meta['subtitle']) ?></p>
                </div>
                <?php if ($current_page == 'dashboard'): ?>
                <div class="hero-3d-wrap">
                    <div id="hero-3d" aria-hidden="true"></div>
                </div>
                <?php endif; ?>
            </header>

            <?php if ($message): ?>
                <div class="msg-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="msg-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- DASHBOARD -->
            <?php if ($current_page == 'dashboard'): ?>
                <div class="stats">
                    <div class="stat-card">
                        <h3><?= $total_motoristas ?></h3>
                        <p>Motoristas Ativos</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $total_veiculos ?></h3>
                        <p>Unidades na Frota</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $total_entregas ?></h3>
                        <p>Despachos Totais</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $entregas_pendentes ?></h3>
                        <p>Em Fila de Envio</p>
                    </div>
                </div>

                <section class="panel">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Últimas Entregas</h3>
                    </div>
                    <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Código de Rastreio</th>
                            <th>Cliente</th>
                            <th>Motorista</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($entregas, 0, 10) as $entrega): ?>
                            <tr>
                                <td><span class="track-code"><?= $entrega['codigo_rastreio'] ?></span></td>
                                <td><?= htmlspecialchars($entrega['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars($entrega['motorista_nome'] ?? '-') ?></td>
                                <td>
                                    <span class="badge badge-<?= $entrega['status_entrega'] == 'entregue' ? 'success' : ($entrega['status_entrega'] == 'em_rota' ? 'info' : 'warning') ?>">
                                        <?= ucfirst($entrega['status_entrega']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($entrega['data_criacao'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                    </div>
                </section>
            <?php endif; ?>

            <!-- MOTORISTAS -->
            <?php if ($current_page == 'motoristas'): ?>
                <section class="panel glass-form">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Adicionar Novo Motorista</h3>
                    </div>
                <form method="POST" class="form-row">
                    <input type="hidden" name="action" value="add_motorista">
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label>CNH *</label>
                        <input type="text" name="cnh" required>
                    </div>
                    <div class="form-group">
                        <label>Validade da CNH *</label>
                        <input type="date" name="validade_cnh" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit">Adicionar Motorista</button>
                    </div>
                </form>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Lista de Motoristas</h3>
                    </div>
                    <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CNH</th>
                            <th>Validade CNH</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($motoristas as $motorista): ?>
                            <tr>
                                <td><?= htmlspecialchars($motorista['nome']) ?></td>
                                <td><?= htmlspecialchars($motorista['cnh']) ?></td>
                                <td><?= date('d/m/Y', strtotime($motorista['validade_cnh'])) ?></td>
                                <td>
                                    <span class="badge badge-<?= $motorista['status_motorista'] == 'disponivel' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($motorista['status_motorista']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                    </div>
                </section>
            <?php endif; ?>

            <!-- VEÍCULOS -->
            <?php if ($current_page == 'veiculos'): ?>
                <section class="panel glass-form">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Adicionar Novo Veículo</h3>
                    </div>
                <form method="POST" class="form-row">
                    <input type="hidden" name="action" value="add_veiculo">
                    <div class="form-group">
                        <label>Placa *</label>
                        <input type="text" name="placa" required placeholder="ABC-1234">
                    </div>
                    <div class="form-group">
                        <label>Modelo *</label>
                        <input type="text" name="modelo" required placeholder="Ex: Volvo FH 750">
                    </div>
                    <div class="form-group">
                        <label>Capacidade de Carga (kg) *</label>
                        <input type="number" name="capacidade_carga" required step="0.01">
                    </div>
                    <div class="form-group">
                        <label>KM Atual *</label>
                        <input type="number" name="km_atual" required step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label>Data Última Manutenção</label>
                        <input type="date" name="data_ultima_manutencao">
                    </div>
                    <div class="form-actions">
                        <button type="submit">Adicionar Veículo</button>
                    </div>
                </form>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Lista de Veículos</h3>
                    </div>
                    <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>Capacidade (kg)</th>
                            <th>KM Atual</th>
                            <th>Última Manutenção</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($veiculos as $veiculo): ?>
                            <tr>
                                <td><span class="track-code"><?= htmlspecialchars($veiculo['placa']) ?></span></td>
                                <td><?= htmlspecialchars($veiculo['modelo']) ?></td>
                                <td><?= number_format($veiculo['capacidade_carga'], 2, ',', '.') ?></td>
                                <td><?= number_format($veiculo['km_atual'], 2, ',', '.') ?></td>
                                <td><?= $veiculo['data_ultima_manutencao'] ? date('d/m/Y', strtotime($veiculo['data_ultima_manutencao'])) : '-' ?></td>
                                <td>
                                    <span class="badge badge-<?= $veiculo['status_veiculo'] == 'disponivel' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($veiculo['status_veiculo']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                    </div>
                </section>
            <?php endif; ?>

            <!-- ROTAS -->
            <?php if ($current_page == 'rotas'): ?>
                <section class="panel glass-form">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Adicionar Nova Rota</h3>
                    </div>
                <form method="POST" class="form-row">
                    <input type="hidden" name="action" value="add_rota">
                    <div class="form-group">
                        <label>Origem *</label>
                        <input type="text" name="origem" required placeholder="São Paulo, SP">
                    </div>
                    <div class="form-group">
                        <label>Destino *</label>
                        <input type="text" name="destino" required placeholder="Rio de Janeiro, RJ">
                    </div>
                    <div class="form-group">
                        <label>Distância Estimada (km) *</label>
                        <input type="number" name="distancia_estimada_km" required step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Tempo Estimado (horas) *</label>
                        <input type="number" name="tempo_estimado_horas" required step="0.5" min="0">
                    </div>
                    <div class="form-actions">
                        <button type="submit">Adicionar Rota</button>
                    </div>
                </form>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Lista de Rotas</h3>
                    </div>
                    <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Origem</th>
                            <th>Destino</th>
                            <th>Distância (km)</th>
                            <th>Tempo Estimado (h)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rotas as $rota): ?>
                            <tr>
                                <td><?= htmlspecialchars($rota['origem']) ?></td>
                                <td><?= htmlspecialchars($rota['destino']) ?></td>
                                <td><?= number_format($rota['distancia_estimada_km'], 2, ',', '.') ?></td>
                                <td><?= number_format($rota['tempo_estimado_horas'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                    </div>
                </section>
            <?php endif; ?>

            <!-- ENTREGAS -->
            <?php if ($current_page == 'entregas'): ?>
                <section class="panel glass-form">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Registrar Nova Entrega</h3>
                    </div>
                <form method="POST" class="form-row">
                    <input type="hidden" name="action" value="add_entrega">
                    <div class="form-group">
                        <label>Nome do Cliente *</label>
                        <input type="text" name="cliente_nome" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Endereço de Destino *</label>
                        <textarea name="endereco_destino" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Motorista</label>
                        <select name="id_motorista">
                            <option value="">Selecione um motorista</option>
                            <?php foreach ($motoristas as $motorista): ?>
                                <option value="<?= $motorista['id_motorista'] ?>"><?= htmlspecialchars($motorista['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Veículo</label>
                        <select name="id_veiculo">
                            <option value="">Selecione um veículo</option>
                            <?php foreach ($veiculos as $veiculo): ?>
                                <option value="<?= $veiculo['id_veiculo'] ?>"><?= htmlspecialchars($veiculo['placa'] . ' - ' . $veiculo['modelo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Rota</label>
                        <select name="id_rota">
                            <option value="">Selecione uma rota</option>
                            <?php foreach ($rotas as $rota): ?>
                                <option value="<?= $rota['id_rota'] ?>"><?= htmlspecialchars($rota['origem'] . ' → ' . $rota['destino']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit">Registrar Entrega</button>
                    </div>
                </form>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Lista de Entregas</h3>
                    </div>
                    <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Código Rastreio</th>
                            <th>Cliente</th>
                            <th>Motorista</th>
                            <th>Veículo</th>
                            <th>Status</th>
                            <th>Data Criação</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entregas as $entrega): ?>
                            <tr>
                                <td><span class="track-code"><?= $entrega['codigo_rastreio'] ?></span></td>
                                <td><?= htmlspecialchars($entrega['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars($entrega['motorista_nome'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($entrega['placa'] ?? '-') ?></td>
                                <td>
                                    <span class="badge badge-<?= $entrega['status_entrega'] == 'entregue' ? 'success' : ($entrega['status_entrega'] == 'em_rota' ? 'info' : 'warning') ?>">
                                        <?= ucfirst($entrega['status_entrega']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($entrega['data_criacao'])) ?></td>
                                <td>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="atualizar_status_entrega">
                                        <input type="hidden" name="id_entrega" value="<?= $entrega['id_entrega'] ?>">
                                        <select name="novo_status" class="status-select" onchange="this.form.submit()">
                                            <option value="<?= $entrega['status_entrega'] ?>">--</option>
                                            <option value="pendente">Pendente</option>
                                            <option value="em_rota">Em Rota</option>
                                            <option value="entregue">Entregue</option>
                                            <option value="cancelado">Cancelado</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                    </div>
                </section>
            <?php endif; ?>

            <!-- OCORRÊNCIAS -->
            <?php if ($current_page == 'ocorrencias'): ?>
                <section class="panel glass-form">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Adicionar Nova Ocorrência</h3>
                    </div>
                <form method="POST" class="form-row">
                    <input type="hidden" name="action" value="add_ocorrencia">
                    <div class="form-group">
                        <label>Entrega *</label>
                        <select name="id_entrega" required>
                            <option value="">Selecione uma entrega</option>
                            <?php foreach ($entregas as $entrega): ?>
                                <option value="<?= $entrega['id_entrega'] ?>"><?= htmlspecialchars($entrega['codigo_rastreio'] . ' - ' . $entrega['cliente_nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Ocorrência *</label>
                        <select name="tipo_ocorrencia" required>
                            <option value="">Selecione</option>
                            <option value="Atraso">Atraso na Entrega</option>
                            <option value="Acidente">Acidente</option>
                            <option value="Endereco_Nao_Encontrado">Endereço Não Encontrado</option>
                            <option value="Problema_Mecanico">Problema Mecânico</option>
                            <option value="Cliente_Ausente">Cliente Ausente</option>
                            <option value="Carga_Danificada">Carga Danificada</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Descrição</label>
                        <textarea name="descricao" placeholder="Detalhe a ocorrência..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit">Registrar Ocorrência</button>
                    </div>
                </form>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Histórico de Ocorrências</h3>
                    </div>
                    <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Código Rastreio</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Data/Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ocorrencias as $ocorrencia): ?>
                            <tr>
                                <td><span class="track-code"><?= htmlspecialchars($ocorrencia['codigo_rastreio'] ?? '-') ?></span></td>
                                <td>
                                    <span class="badge badge-danger"><?= htmlspecialchars($ocorrencia['tipo_ocorrencia']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($ocorrencia['descricao'] ?? '-') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($ocorrencia['data_hora'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                    </div>
                </section>
            <?php endif; ?>

            <!-- MANUTENÇÕES -->
            <?php if ($current_page == 'manutencoes'): ?>
                <section class="panel glass-form">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Registrar Nova Manutenção</h3>
                    </div>
                <form method="POST" class="form-row">
                    <input type="hidden" name="action" value="add_manutencao">
                    <div class="form-group">
                        <label>Veículo *</label>
                        <select name="id_veiculo" required>
                            <option value="">Selecione um veículo</option>
                            <?php foreach ($veiculos as $veiculo): ?>
                                <option value="<?= $veiculo['id_veiculo'] ?>"><?= htmlspecialchars($veiculo['placa'] . ' - ' . $veiculo['modelo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Data da Manutenção *</label>
                        <input type="date" name="data_manutencao" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo de Manutenção *</label>
                        <select name="tipo_manutencao" required>
                            <option value="">Selecione</option>
                            <option value="preventiva">Preventiva</option>
                            <option value="corretiva">Corretiva</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>KM na Manutenção *</label>
                        <input type="number" name="km_na_manutencao" required step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Custo (R$) *</label>
                        <input type="number" name="custo" required step="0.01">
                    </div>
                    <div class="form-group full-width">
                        <label>Descrição</label>
                        <textarea name="descricao" placeholder="Detalhe serviços realizados..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit">Registrar Manutenção</button>
                    </div>
                </form>
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <span class="dot"></span>
                        <h3>Histórico de Manutenções</h3>
                    </div>
                    <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Veículo</th>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>KM</th>
                            <th>Custo (R$)</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manutencoes as $manutencao): ?>
                            <tr>
                                <td><span class="track-code"><?= htmlspecialchars($manutencao['placa'] ?? '-') ?></span></td>
                                <td><?= date('d/m/Y', strtotime($manutencao['data_manutencao'])) ?></td>
                                <td>
                                    <span class="badge badge-info"><?= ucfirst($manutencao['tipo_manutencao']) ?></span>
                                </td>
                                <td><?= number_format($manutencao['km_na_manutencao'], 2, ',', '.') ?></td>
                                <td><?= number_format($manutencao['custo'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($manutencao['descricao'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
