<?php
declare(strict_types=1);
ob_start();
require dirname(__DIR__) . '/app/bootstrap.php';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (str_starts_with($path, '/api/')) {
    // Never allow PHP notices/warnings to corrupt API JSON.
    ini_set('display_errors', '0');
    set_exception_handler(function(Throwable $e): void {
        error_log('[Achadinhos API] '.$e->getMessage());
        if (!headers_sent()) json_response(['ok'=>false,'error'=>'Erro interno da API'], 500);
    });
    $route = substr($path, 5);
    if ($route === 'health') {
        require_method('GET');
        try {
            $version = PHP_VERSION;
            $pdoDrivers = PDO::getAvailableDrivers();
            db()->query('SELECT 1');
            json_response(['ok'=>true,'php'=>$version,'database'=>'connected','pdo_drivers'=>$pdoDrivers,'time'=>date(DATE_ATOM)]);
        } catch (Throwable $e) {
            error_log('[health] '.$e->getMessage());
            json_response(['ok'=>false,'php'=>PHP_VERSION,'database'=>'unavailable','error'=>'Verifique a configuração e PDO MySQL'], 503);
        }
    }
    require_auth();
    if ($route !== 'dashboard' && $route !== 'agents' && $route !== 'tasks' && $route !== 'approvals' && $route !== 'activity' && $route !== 'messages') {
        json_response(['ok'=>false,'error'=>'Rota não encontrada'], 404);
    }
    if ($route === 'dashboard') {
        require_method('GET');
        $pdo = db();
        $counts = [];
        foreach (['agents','tasks','approvals'] as $table) {
            $counts[$table] = (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        }
        $counts['pending_tasks'] = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('queued','running')")->fetchColumn();
        $counts['pending_approvals'] = (int)$pdo->query("SELECT COUNT(*) FROM approvals WHERE status='pending'")->fetchColumn();
        $counts['products_imported'] = (int)$pdo->query("SELECT COUNT(*) FROM product_import_log")->fetchColumn();
        json_response(['ok'=>true,'counts'=>$counts,'recent'=>recent_activity(12)]);
    }
    if ($route === 'agents') {
        require_method('GET');
        $rows = db()->query('SELECT id, name, role, status, room, x_pos, y_pos, last_seen_at FROM agents ORDER BY id')->fetchAll();
        json_response(['ok'=>true,'agents'=>$rows]);
    }
    if ($route === 'tasks') {
        if ($method === 'GET') {
            $rows = db()->query('SELECT t.id,t.title,t.description,t.type,t.status,t.priority,t.created_at,a.name AS agent_name FROM tasks t LEFT JOIN agents a ON a.id=t.agent_id ORDER BY t.created_at DESC LIMIT 100')->fetchAll();
            json_response(['ok'=>true,'tasks'=>$rows]);
        }
        require_method('POST'); require_csrf();
        $in = request_json();
        $title = trim((string)($in['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 180) json_response(['ok'=>false,'error'=>'Informe um título de até 180 caracteres'], 422);
        $stmt = db()->prepare("INSERT INTO tasks (title,description,type,status,priority,created_by,created_at,updated_at) VALUES (?,?,?,'queued',?,?,NOW(),NOW())");
        $stmt->execute([$title,trim((string)($in['description'] ?? '')),preg_replace('/[^a-z0-9_-]/i','',(string)($in['type'] ?? 'general')) ?: 'general',max(1,min(5,(int)($in['priority'] ?? 3))),'admin']);
        log_activity('task_created','Nova tarefa criada: '.$title,['task_id'=>(int)db()->lastInsertId()]);
        json_response(['ok'=>true,'id'=>(int)db()->lastInsertId()], 201);
    }
    if ($route === 'approvals') {
        if ($method === 'GET') {
            $rows = db()->query("SELECT id,title,description,impact,status,created_at FROM approvals ORDER BY created_at DESC LIMIT 100")->fetchAll();
            json_response(['ok'=>true,'approvals'=>$rows]);
        }
        require_method('POST'); require_csrf();
        $in = request_json(); $id = (int)($in['id'] ?? 0); $decision = (string)($in['decision'] ?? '');
        if ($id < 1 || !in_array($decision,['approved','rejected'],true)) json_response(['ok'=>false,'error'=>'Decisão inválida'],422);
        $stmt = db()->prepare("UPDATE approvals SET status=?, reviewed_at=NOW(), reviewed_by='admin' WHERE id=? AND status='pending'");
        $stmt->execute([$decision,$id]);
        if (!$stmt->rowCount()) json_response(['ok'=>false,'error'=>'Aprovação não encontrada ou já analisada'],409);
        log_activity('approval_'.$decision,'Proposta '.$decision,['approval_id'=>$id]);
        json_response(['ok'=>true]);
    }
    if ($route === 'activity') {
        require_method('GET'); json_response(['ok'=>true,'activity'=>recent_activity(50)]);
    }
    if ($route === 'messages') {
        if ($method === 'GET') {
            $rows = db()->query('SELECT m.id,m.message,m.created_at,a.name AS agent_name FROM messages m LEFT JOIN agents a ON a.id=m.agent_id ORDER BY m.created_at DESC LIMIT 30')->fetchAll();
            json_response(['ok'=>true,'messages'=>array_reverse($rows)]);
        }
        require_method('POST'); require_csrf();
        $in=request_json(); $msg=trim((string)($in['message']??''));
        if ($msg==='' || mb_strlen($msg)>1000) json_response(['ok'=>false,'error'=>'Mensagem inválida'],422);
        $stmt=db()->prepare('INSERT INTO messages (agent_id,message,kind,created_at) VALUES (NULL,?,?,NOW())');
        $stmt->execute([$msg,'admin']);
        log_activity('message','Mensagem administrativa enviada');
        json_response(['ok'=>true],201);
    }
}
function recent_activity(int $limit): array {
    $stmt=db()->prepare('SELECT id,type,message,meta_json,created_at FROM activities ORDER BY id DESC LIMIT ?');
    $stmt->bindValue(1,$limit,PDO::PARAM_INT); $stmt->execute(); return $stmt->fetchAll();
}

if ($path === '/login' || $path === '/') {
    start_secure_session();
    if ($method === 'POST') {
        $username = (string)($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        if (hash_equals((string)app_config('admin.username'),$username) && password_verify($password,(string)app_config('admin.password_hash'))) {
            session_regenerate_id(true); $_SESSION['admin_ok']=true; $_SESSION['csrf']=bin2hex(random_bytes(32));
            header('Location: /dashboard',true,303); exit;
        }
        $loginError='Usuário ou senha incorretos.';
    }
    if (!empty($_SESSION['admin_ok'])) { header('Location: /dashboard',true,302); exit; }
    ?><!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Entrar · Central de Agentes</title><link rel="stylesheet" href="/assets/app.css"><body class="login-body"><form class="login-card" method="post"><div class="brand-mark">A</div><h1>Central de Agentes</h1><p>Entre no escritório virtual.</p><?php if(!empty($loginError)): ?><div class="alert"><?=htmlspecialchars($loginError,ENT_QUOTES,'UTF-8')?></div><?php endif; ?><label>Usuário<input name="username" autocomplete="username" required></label><label>Senha<input type="password" name="password" autocomplete="current-password" required></label><button>Entrar no escritório</button><small>Conexão protegida · PHP <?=htmlspecialchars(PHP_VERSION)?></small></form></body></html><?php
    exit;
}
if ($path === '/logout') { start_secure_session(); $_SESSION=[]; session_destroy(); header('Location: /login'); exit; }
if ($path === '/dashboard' || $path === '/office') {
    start_secure_session();
    if (empty($_SESSION['admin_ok'])) { header('Location: /login'); exit; }
    $csrf=htmlspecialchars($_SESSION['csrf'] ?? '',ENT_QUOTES,'UTF-8');
    ?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="<?=$csrf?>"><title>Escritório · Central de Agentes</title><link rel="stylesheet" href="/assets/app.css"></head><body><div class="app-shell"><header class="topbar"><div class="brand"><span class="brand-mark small">A</span><div><b>ACHADINHOS</b><small>Central de agentes</small></div></div><div class="top-actions"><span id="connection" class="connection">Conectando…</span><a href="/logout">Sair</a></div></header><div class="layout"><aside class="sidebar"><div class="side-label">ESCRITÓRIO</div><a class="nav active" href="/dashboard">▦ Visão geral</a><a class="nav" href="#agents">♟ Agentes</a><a class="nav" href="#tasks">▤ Tarefas</a><a class="nav" href="#approvals">✓ Aprovações</a><a class="nav" href="#activity">◷ Atividades</a><div class="side-label">SISTEMA</div><a class="nav" href="/api/health" target="_blank">⌁ Diagnóstico PHP</a><div class="sidebar-note">Seu site público permanece separado e intacto.</div></aside><main class="main"><div class="page-heading"><div><div class="eyebrow">CENTRAL DE OPERAÇÕES</div><h1>Escritório virtual</h1><p>Acompanhe o trabalho real dos seus agentes.</p></div><button class="button primary" id="new-task">＋ Nova tarefa</button></div><section class="stats" id="stats"><article><span>Agentes</span><strong id="count-agents">—</strong></article><article><span>Tarefas na fila/em execução</span><strong id="count-pending">—</strong></article><article><span>Aprovações pendentes</span><strong id="count-approvals">—</strong></article><article><span>Produtos registrados na importação</span><strong id="count-products">—</strong></article></section><section class="office-panel"><div class="section-heading"><div><h2>🏢 Escritório dos agentes</h2><p>Parede amarela · cenário original · estados sincronizados com o sistema</p></div><span class="live-pill"><i></i> AO VIVO</span></div><div class="game-frame"><div class="game-room"><div class="back-wall"><div class="wall-clock">◷</div><div class="wall-window"><span></span></div><div class="wall-shelf">▰　▰　▰</div><div class="wall-sign">CENTRAL DE OPERAÇÕES</div></div><div class="floor"><div class="rug"></div><div class="plant plant-one">🌿</div><div class="plant plant-two">🪴</div><div class="desk desk-one"><div class="monitor">▣</div><div class="desk-label">SEO</div></div><div class="desk desk-two"><div class="monitor">▣</div><div class="desk-label">SHOPEE API</div></div><div class="desk desk-three"><div class="monitor">▣</div><div class="desk-label">GOOGLE</div></div><div class="meeting-table">REUNIÃO</div><div id="avatars" class="avatars"><div class="empty-office">Carregando equipe…</div></div></div></div><div class="room-legend"><span>🟢 trabalhando</span><span>🟡 aguardando</span><span>⚪ disponível</span></div></div></section><div class="columns"><section class="panel" id="tasks"><div class="section-heading"><div><h2>Fila de tarefas</h2><p>Trabalhos registrados no banco</p></div><button class="text-button" id="refresh">Atualizar</button></div><div id="task-list" class="list"><div class="muted">Carregando…</div></div></section><section class="panel" id="approvals"><div class="section-heading"><div><h2>Precisa da sua aprovação</h2><p>Nenhuma alteração sensível é aplicada sozinha</p></div></div><div id="approval-list" class="list"><div class="muted">Carregando…</div></div></section></div><section class="panel activity-panel" id="activity"><div class="section-heading"><div><h2>Histórico de atividades</h2><p>Eventos registrados pelo sistema</p></div></div><div id="activity-list" class="list"></div></section><footer>PHP <?=htmlspecialchars(PHP_VERSION)?> · MySQL/MariaDB · Sem Node.js · Atualização periódica por HTTPS</footer></main></div></div><dialog id="task-dialog"><form method="dialog" id="task-form"><h2>Criar tarefa</h2><label>Título<input id="task-title" maxlength="180" required></label><label>Descrição<textarea id="task-description" rows="3"></textarea></label><label>Tipo<select id="task-type"><option value="seo">SEO</option><option value="shopee">Shopee / catálogo</option><option value="google">Google</option><option value="audit">Auditoria</option><option value="general">Geral</option></select></label><div class="dialog-actions"><button class="button" value="cancel">Cancelar</button><button class="button primary" id="save-task" value="default">Criar tarefa</button></div></form></dialog><script src="/assets/app.js" defer></script></body></html><?php
    exit;
}
http_response_code(404); echo 'Página não encontrada';
