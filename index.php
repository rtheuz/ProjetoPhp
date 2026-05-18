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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Controle de Entregas - Transportadora</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📦 Sistema de Controle de Frota</h1>
            <p>Gerenciamento de Motoristas, Veículos, Rotas e Entregas</p>
        </header>

        <div class="nav-menu">
            <a href="?page=dashboard" class="<?= $current_page == 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
            <a href="?page=motoristas" class="<?= $current_page == 'motoristas' ? 'active' : '' ?>">👨‍💼 Motoristas</a>
            <a href="?page=veiculos" class="<?= $current_page == 'veiculos' ? 'active' : '' ?>">🚛 Veículos</a>
            <a href="?page=rotas" class="<?= $current_page == 'rotas' ? 'active' : '' ?>">🗺️ Rotas</a>
            <a href="?page=entregas" class="<?= $current_page == 'entregas' ? 'active' : '' ?>">📋 Entregas</a>
            <a href="?page=ocorrencias" class="<?= $current_page == 'ocorrencias' ? 'active' : '' ?>">⚠️ Ocorrências</a>
            <a href="?page=manutencoes" class="<?= $current_page == 'manutencoes' ? 'active' : '' ?>">🔧 Manutenções</a>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="msg-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="msg-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- DASHBOARD -->
            <?php if ($current_page == 'dashboard'): ?>
                <h2>📊 Dashboard</h2>
                <div class="stats">
                    <div class="stat-card">
                        <h3><?= $total_motoristas ?></h3>
                        <p>Motoristas Cadastrados</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $total_veiculos ?></h3>
                        <p>Veículos na Frota</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $total_entregas ?></h3>
                        <p>Entregas Totais</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $entregas_pendentes ?></h3>
                        <p>Entregas Pendentes</p>
                    </div>
                </div>

                <h3>Últimas Entregas</h3>
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
                                <td><strong><?= $entrega['codigo_rastreio'] ?></strong></td>
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
            <?php endif; ?>

            <!-- MOTORISTAS -->
            <?php if ($current_page == 'motoristas'): ?>
                <h2>👨‍💼 Gerenciar Motoristas</h2>

                <h3>Adicionar Novo Motorista</h3>
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
                    <div style="grid-column: 1/-1;">
                        <button type="submit">✅ Adicionar Motorista</button>
                    </div>
                </form>

                <h3 style="margin-top: 40px;">Lista de Motoristas</h3>
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
            <?php endif; ?>

            <!-- VEÍCULOS -->
            <?php if ($current_page == 'veiculos'): ?>
                <h2>🚛 Gerenciar Veículos</h2>

                <h3>Adicionar Novo Veículo</h3>
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
                    <div style="grid-column: 1/-1;">
                        <button type="submit">✅ Adicionar Veículo</button>
                    </div>
                </form>

                <h3 style="margin-top: 40px;">Lista de Veículos</h3>
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
                                <td><strong><?= htmlspecialchars($veiculo['placa']) ?></strong></td>
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
            <?php endif; ?>

            <!-- ROTAS -->
            <?php if ($current_page == 'rotas'): ?>
                <h2>🗺️ Gerenciar Rotas</h2>

                <h3>Adicionar Nova Rota</h3>
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
                    <div style="grid-column: 1/-1;">
                        <button type="submit">✅ Adicionar Rota</button>
                    </div>
                </form>

                <h3 style="margin-top: 40px;">Lista de Rotas</h3>
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
            <?php endif; ?>

            <!-- ENTREGAS -->
            <?php if ($current_page == 'entregas'): ?>
                <h2>📋 Gerenciar Entregas</h2>

                <h3>Registrar Nova Entrega</h3>
                <form method="POST" class="form-row">
                    <input type="hidden" name="action" value="add_entrega">
                    <div class="form-group">
                        <label>Nome do Cliente *</label>
                        <input type="text" name="cliente_nome" required>
                    </div>
                    <div class="form-group">
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
                    <div style="grid-column: 1/-1;">
                        <button type="submit">✅ Registrar Entrega</button>
                    </div>
                </form>

                <h3 style="margin-top: 40px;">Lista de Entregas</h3>
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
                                <td><strong><?= $entrega['codigo_rastreio'] ?></strong></td>
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
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="atualizar_status_entrega">
                                        <input type="hidden" name="id_entrega" value="<?= $entrega['id_entrega'] ?>">
                                        <select name="novo_status" onchange="this.form.submit()">
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
            <?php endif; ?>

            <!-- OCORRÊNCIAS -->
            <?php if ($current_page == 'ocorrencias'): ?>
                <h2>⚠️ Registrar Ocorrências</h2>

                <h3>Adicionar Nova Ocorrência</h3>
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
                    <div style="grid-column: 1/-1;">
                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="descricao" placeholder="Detalhe a ocorrência..."></textarea>
                        </div>
                    </div>
                    <div style="grid-column: 1/-1;">
                        <button type="submit">✅ Registrar Ocorrência</button>
                    </div>
                </form>

                <h3 style="margin-top: 40px;">Histórico de Ocorrências</h3>
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
                                <td><strong><?= htmlspecialchars($ocorrencia['codigo_rastreio'] ?? '-') ?></strong></td>
                                <td>
                                    <span class="badge badge-danger"><?= htmlspecialchars($ocorrencia['tipo_ocorrencia']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($ocorrencia['descricao'] ?? '-') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($ocorrencia['data_hora'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- MANUTENÇÕES -->
            <?php if ($current_page == 'manutencoes'): ?>
                <h2>🔧 Gerenciar Manutenções</h2>

                <h3>Registrar Nova Manutenção</h3>
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
                    <div style="grid-column: 1/-1;">
                        <div class="form-group">
                            <label>Descrição</label>
                            <textarea name="descricao" placeholder="Detalhe serviços realizados..."></textarea>
                        </div>
                    </div>
                    <div style="grid-column: 1/-1;">
                        <button type="submit">✅ Registrar Manutenção</button>
                    </div>
                </form>

                <h3 style="margin-top: 40px;">Histórico de Manutenções</h3>
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
                                <td><strong><?= htmlspecialchars($manutencao['placa'] ?? '-') ?></strong></td>
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
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
