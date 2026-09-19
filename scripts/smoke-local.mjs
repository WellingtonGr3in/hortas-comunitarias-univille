// Execute somente contra o banco local de demonstração. Cria registros e os exclui logicamente.
import assert from 'node:assert/strict';
const base = process.env.TEST_API_URL || 'http://127.0.0.1:8181/api/v1';
if (!['localhost', '127.0.0.1'].includes(new URL(base).hostname)) throw new Error('Este teste aceita apenas localhost.');
let token;
let checks = 0;
async function call(path, method = 'GET', body, status = 200, authenticated = true) {
  const response = await fetch(base + path, {
    method, headers: {'Content-Type': 'application/json', ...(authenticated && token ? {Authorization: `Bearer ${token}`} : {})},
    ...(body !== undefined ? {body: JSON.stringify(body)} : {})
  });
  const text = await response.text();
  assert.equal(response.status, status, `${method} ${path}: ${text.slice(0, 800)}`);
  assert.match(response.headers.get('content-type') || '', /application\/json/);
  checks++;
  return text ? JSON.parse(text) : null;
}
function cpf() {
 const d = Array.from({length:9}, () => Math.floor(Math.random()*10));
 for(let n=9;n<11;n++) { const r=d.reduce((s,x,i)=>s+x*(n+1-i),0)*10%11; d.push(r===10?0:r); }
 return d.join('');
}
const suffix = Date.now().toString();
await call('/hortas', 'GET', undefined, 401, false);
await call('/sessoes/login','POST',{email:'admin@email.com',senha:'senha-incorreta'},401,false);
token = (await call('/sessoes/login','POST',{email:process.env.TEST_EMAIL || 'admin@email.com',senha:process.env.TEST_PASSWORD || 'admin'},200,false)).token;
assert.ok(token);
const payload = JSON.parse(Buffer.from(token.split('.')[1], 'base64url'));
await call(`/usuarios/${payload.usuario_uuid}`);
for (const route of ['associacoes','hortas','canteiros','canteiros/search','Canteiristas','dependentes','pagamentos','notificacoes','usuarios']) await call('/'+route);
const horta = (await call('/hortas'))[0];
assert.ok(horta?.uuid, 'Execute as migrations para obter a horta de demonstração.');
const cleanup=[];
try {
 const assoc=await call('/associacoes','POST',{nome:'Associação Teste '+suffix,descricao:'Teste local',telefone:'47999999999',email:'teste@example.test',endereco:'Rua de Teste, 123'},201);
 cleanup.push(['/associacoes/',assoc.id]);
 assert.equal((await call('/associacoes/'+assoc.id)).endereco,'Rua de Teste, 123');
 await call('/associacoes/'+assoc.id,'PUT',{descricao:'Descrição editada'});
 assert.equal((await call('/associacoes/'+assoc.id)).descricao,'Descrição editada');
 const novaHorta=await call('/hortas','POST',{nome:'Horta Teste '+suffix,associacao_vinculada_uuid:assoc.id,localizacao:'Rua de Teste, 456',telefone:'47999999999',responsavel:'Responsável Teste'},201);
 cleanup.push(['/hortas/',novaHorta.id]);
 assert.equal((await call('/hortas/'+novaHorta.id)).localizacao,'Rua de Teste, 456');
 await call('/hortas/'+novaHorta.id,'PUT',{localizacao:'Rua Editada, 789',telefone:'47988888888'});
 const hortaEditada=await call('/hortas/'+novaHorta.id);
 assert.equal(hortaEditada.localizacao,'Rua Editada, 789');
 assert.equal(hortaEditada.telefone,'47988888888');
 const c=await call('/canteiros','POST',{numero_identificador:'TEST-'+suffix,tamanho_m2:12.5,horta_uuid:horta.uuid,localizacao:'Teste local',status:'Disponível',data_ultima_colheita:null},201);
 cleanup.push(['/canteiros/',c.id]);
 assert.equal((await call('/canteiros/'+c.id)).numero_identificador,'TEST-'+suffix);
 await call('/canteiros/'+c.id,'PUT',{plantio_atual:'Alface',tamanho_m2:15});
 assert.equal((await call('/canteiros/'+c.id)).plantio_atual,'Alface');
 const owner=await call('/Canteiristas','POST',{nome_completo:'Teste Local',cpf:cpf(),email:`smoke-${suffix}@example.test`,senha:'TesteLocal123!',data_de_nascimento:'1990-01-01',apelido:'Teste',telefone:'47999999999',horta_uuid:horta.uuid},201);
 cleanup.push(['/usuarios/',owner.usuario_uuid],['/Canteiristas/',owner.id]);
 assert.equal(owner.nome,'Teste Local');
 const owned=await call('/canteiros','POST',{numero_identificador:'OWN-'+suffix,tamanho_m2:8,horta_uuid:horta.uuid,usuario_uuid:owner.usuario_uuid},201);
 cleanup.push(['/canteiros/',owned.id]);
 const ownedGet=await call('/canteiros/'+owned.id);
 assert.equal(ownedGet.status,'Ocupado');
 for (const vinculo of ownedGet.historico || []) cleanup.push(['/canteiros-e-usuarios/',vinculo.uuid]);
 assert.ok(ownedGet.historico?.length, 'O proprietário deve ser persistido junto ao canteiro.');
 await call('/canteiros/'+owned.id,'DELETE',undefined,400);
 await call('/Canteiristas/'+owner.id,'PUT',{nome_completo:'Teste Editado',telefone:'47988888888'});
 assert.equal((await call('/Canteiristas/'+owner.id)).nome,'Teste Editado');
 const dep=await call('/dependentes','POST',{nome:'Dependente Teste',cpf:cpf(),idade:20,carteirista_uuid:owner.id},201);
 cleanup.push(['/dependentes/',dep.id]);
 assert.equal((await call('/dependentes/'+dep.id)).carteirista_uuid,owner.id);
 await call('/dependentes/'+dep.id,'PUT',{nome:'Dependente Editado'});
 assert.equal((await call('/dependentes/'+dep.id)).nome,'Dependente Editado');
 const pag=(await call('/pagamentos','POST',{carteirista_uuid:owner.id,valor:20,forma_pagamento:'dinheiro',data_pagamento:'2026-09-18'},201)).data;
 cleanup.push(['/pagamentos/',pag.uuid || pag.id]);
 const pagGet=(await call('/pagamentos/'+(pag.uuid || pag.id))).data;
 assert.equal(pagGet.carteirista_uuid,owner.id);
 assert.equal(pagGet.carteirista_nome,'Teste Editado');
 await call('/pagamentos/'+(pag.uuid||pag.id),'PUT',{valor:25});
 assert.equal((await call('/pagamentos/'+(pag.uuid||pag.id))).data.valor,25);
 const not=(await call('/notificacoes','POST',{tipo:'aviso_canteirista',titulo:'Teste '+suffix,mensagem:'Mensagem de teste local',carteirista_uuid:owner.id},201)).data;
 cleanup.push(['/notificacoes/',not.uuid || not.id]);
 assert.equal((await call('/notificacoes/'+(not.uuid||not.id))).data.carteirista_uuid,owner.id);
 await call('/notificacoes/'+(not.uuid||not.id),'PUT',{titulo:'Aviso editado'});
 assert.equal((await call('/notificacoes/'+(not.uuid||not.id))).data.titulo,'Aviso editado');
 await call('/Canteiristas/'+owner.id+'/desativar','PATCH',{});
 await call('/sessoes/login','POST',{email:`smoke-${suffix}@example.test`,senha:'TesteLocal123!'},401,false);
} finally {
 for(const [route,id] of cleanup.reverse()) {
  await call(route+id,'DELETE');
 }
}
console.log(`OK: ${checks} requisições verificadas; login, listagens, persistência, edição, exclusão e bloqueio de acesso.`);
