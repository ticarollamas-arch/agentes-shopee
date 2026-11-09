(() => {
'use strict';
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
const $ = id => document.getElementById(id);
async function api(path, options={}) {
  const headers = {'Accept':'application/json', ...(options.headers||{})};
  if (options.body) { headers['Content-Type']='application/json'; headers['X-CSRF-Token']=csrf; }
  const response = await fetch('/api/'+path, {...options,headers,credentials:'same-origin'});
  const type = response.headers.get('content-type') || '';
  if (!type.includes('application/json')) throw new Error('O servidor não retornou JSON. Verifique rota, sessão e logs PHP.');
  const data = await response.json();
  if (!response.ok || data.ok === false) throw new Error(data.error || 'Falha na API');
  return data;
}
const esc = s => String(s ?? '').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function tag(status){return `<span class="tag ${esc(status)}">${esc(status)}</span>`}
async function load() {
  try {
    const [dash, agents, tasks, approvals] = await Promise.all([
      api('dashboard'),api('agents'),api('tasks'),api('approvals')
    ]);
    $('connection').textContent='● Conectado ao PHP/MySQL';
    $('count-agents').textContent=dash.counts.agents;
    $('count-pending').textContent=dash.counts.pending_tasks;
    $('count-approvals').textContent=dash.counts.pending_approvals;
    $('count-products').textContent=dash.counts.products_imported;
    renderAgents(agents.agents); renderTasks(tasks.tasks); renderApprovals(approvals.approvals); renderActivity(dash.recent);
  } catch(e) { $('connection').textContent='● '+e.message; $('connection').style.color='#b74747'; }
}
function renderAgents(items){
  const host=$('avatars');
  if(!items.length){host.innerHTML='<div class="empty-office">Ainda não há agentes cadastrados.</div>';return;}
  const colors=['#5279a8','#579b79','#9672b1','#c47b50','#4d929d','#b36c82'];
  host.innerHTML=items.map((a,i)=>{
    const x=Math.max(8,Math.min(92,Number(a.x_pos)||18+(i%4)*21));
    const y=Math.max(8,Math.min(88,Number(a.y_pos)||42+(i%2)*23));
    const state=a.status==='working'?'Trabalhando':a.status==='waiting'?'Aguardando':'Disponível';
    return `<div class="avatar" data-status="${esc(a.status)}" style="left:${x}%;top:${y}%"><div class="sprite" style="--shirt:${colors[i%colors.length]}"><i class="hair"></i><i class="head"></i><i class="body"></i><i class="legs"></i></div><div class="avatar-name">${esc(a.name)}</div><div class="avatar-state">${state}</div></div>`;
  }).join('');
}
function renderTasks(items){
  $('task-list').innerHTML=items.length?items.slice(0,12).map(t=>`<div class="list-item"><div><b>${esc(t.title)}</b><small>${esc(t.agent_name||'Sem agente atribuído')} · ${esc(t.type)} · ${esc(t.created_at)}</small></div>${tag(t.status)}</div>`).join(''):'<div class="muted">Nenhuma tarefa cadastrada.</div>';
}
function renderApprovals(items){
  $('approval-list').innerHTML=items.length?items.filter(x=>x.status==='pending').slice(0,10).map(a=>`<div class="list-item"><div><b>${esc(a.title)}</b><small>${esc(a.description||'Sem descrição')} · Impacto: ${esc(a.impact||'não informado')}<div class="approval-actions"><button data-approval="${a.id}" data-decision="approved">Aprovar</button><button data-approval="${a.id}" data-decision="rejected">Rejeitar</button></div></small></div>${tag(a.status)}</div>`).join(''):'<div class="muted">Nenhuma proposta aguardando aprovação.</div>';
}
function renderActivity(items){
  $('activity-list').innerHTML=items?.length?items.slice(0,12).map(a=>`<div class="list-item"><div><b>${esc(a.message)}</b><small>${esc(a.type)} · ${esc(a.created_at)}</small></div></div>`).join(''):'<div class="muted">O histórico aparecerá conforme o sistema registrar atividades.</div>';
}
$('refresh')?.addEventListener('click',load);
$('new-task')?.addEventListener('click',()=>$('task-dialog').showModal());
$('task-form')?.addEventListener('submit',async e=>{
  if(e.submitter?.value==='cancel') return;
  e.preventDefault();
  try { await api('tasks',{method:'POST',body:JSON.stringify({title:$('task-title').value,description:$('task-description').value,type:$('task-type').value})}); $('task-dialog').close(); $('task-form').reset(); await load(); }
  catch(err){alert(err.message);}
});
$('approval-list')?.addEventListener('click',async e=>{
  const b=e.target.closest('[data-approval]'); if(!b)return;
  if(!confirm('Confirmar '+(b.dataset.decision==='approved'?'aprovação':'rejeição')+' desta proposta?'))return;
  try{await api('approvals',{method:'POST',body:JSON.stringify({id:Number(b.dataset.approval),decision:b.dataset.decision})});await load();}
  catch(err){alert(err.message);}
});
load(); setInterval(load,8000);
})();