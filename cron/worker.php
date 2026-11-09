<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
$pdo=db();
$pdo->beginTransaction();
try {
    $task=$pdo->query("SELECT id,title,type FROM tasks WHERE status='queued' ORDER BY priority ASC, created_at ASC LIMIT 1 FOR UPDATE")->fetch();
    if (!$task) { $pdo->commit(); echo "Fila vazia\n"; exit(0); }
    $upd=$pdo->prepare("UPDATE tasks SET status='running',started_at=NOW(),updated_at=NOW() WHERE id=? AND status='queued'");
    $upd->execute([$task['id']]);
    if (!$upd->rowCount()) { $pdo->rollBack(); exit(0); }
    $pdo->commit();
    $pdo->prepare("INSERT INTO activities(type,message,meta_json,created_at) VALUES('task_started',?,?,NOW())")
        ->execute(['Iniciada tarefa: '.$task['title'],json_encode(['task_id'=>$task['id']])]);

    // A fundação não executa ações externas automaticamente. Cada tipo ganhará um handler
    // somente após integração, credenciais, limites e política de aprovação serem configurados.
    $result=['accepted'=>false,'reason'=>'Handler ainda não configurado','task_type'=>$task['type']];
    $pdo->prepare("UPDATE tasks SET status='failed',error_text=?,result_json=?,finished_at=NOW(),updated_at=NOW() WHERE id=?")
        ->execute(['Handler não configurado; nenhuma ação externa foi executada.',json_encode($result),$task['id']]);
    $pdo->prepare("INSERT INTO activities(type,message,meta_json,created_at) VALUES('task_needs_handler',?,?,NOW())")
        ->execute(['Tarefa aguardando integração: '.$task['title'],json_encode(['task_id'=>$task['id']])]);
    echo "Tarefa {$task['id']} identificada; handler pendente.\n";
} catch(Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[worker] '.$e->getMessage());
    fwrite(STDERR,"Falha no worker; consulte o log PHP.\n"); exit(1);
}
