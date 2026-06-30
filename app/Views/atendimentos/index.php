<?php
$tituloPagina = 'Atendimentos';
require __DIR__ . '/../layouts/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 mb-1">Atendimentos</h1>
    <p class="text-secondary mb-0">Registro e acompanhamento dos atendimentos acadêmicos.</p>
  </div>
  <button class="btn btn-success" type="button" onclick="novoAtendimento()">Novo atendimento</button>
</div>
<div id="alerta"></div>
<div class="card border-0 shadow-sm mb-4 d-none" id="cardFormulario">
  <div class="card-body">
    <h2 class="h5">Novo atendimento</h2>
    <form id="formAtendimento">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Pessoa *</label>
          <select class="form-select" name="pessoa_id" id="pessoaSelect" required></select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tipo *</label>
          <select class="form-select" name="tipo_atendimento_id" id="tipoSelect" required></select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Data *</label>
          <input class="form-control" type="date" name="data_atendimento" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Horário *</label>
          <input class="form-control" type="time" name="hora_atendimento" required>
        </div>
        <div class="col-12">
          <label class="form-label">Descrição *</label>
          <textarea class="form-control" name="descricao" rows="3" required></textarea>
        </div>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-success" type="submit">Registrar</button>
        <button class="btn btn-outline-secondary" type="button" onclick="fecharFormulario()">Cancelar</button>
      </div>
    </form>
  </div>
</div>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Pessoa</th>
          <th>Tipo</th>
          <th>Responsável</th>
          <th>Data</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody id="tabelaAtendimentos">
        <tr><td colspan="7" class="text-center py-4">Carregando...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="modalStatus" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title fs-5">Alterar status</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formStatus">
        <div class="modal-body">
          <input type="hidden" name="id" id="statusId">
          <div class="mb-3">
            <label class="form-label">Novo status</label>
            <select class="form-select" name="status" required>
              <option value="aberto">Aberto</option>
              <option value="em_andamento">Em andamento</option>
              <option value="concluido">Concluído</option>
            </select>
          </div>
          <div>
            <label class="form-label">Observação</label>
            <textarea class="form-control" name="observacao" rows="3" placeholder="Obrigatória ao concluir"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-success" type="submit">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
const formAtendimento = document.getElementById('formAtendimento');
const cardFormulario = document.getElementById('cardFormulario');
const statusModal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('modalStatus'));

function novoAtendimento() {
  cardFormulario.classList.remove('d-none');
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
function fecharFormulario() {
  cardFormulario.classList.add('d-none');
  formAtendimento.reset();
}
function labelRegistro(obj, ...keys) {
  for (const key of keys) {
    if (obj[key] !== undefined && obj[key] !== null) return obj[key];
  }
  return '';
}

async function carregarCombos() {
  const [pessoasResp, tiposResp] = await Promise.all([
    AtendeLabApi.get('pessoas', 'listar'),
    AtendeLabApi.get('tipos', 'listar')
  ]);
  const pessoas = AtendeLabApi.toList(pessoasResp).filter(p => p.status !== 'inativo');
  const tipos = AtendeLabApi.toList(tiposResp).filter(t => t.status !== 'inativo');

  document.getElementById('pessoaSelect').innerHTML =
    '<option value="">Selecione</option>' +
    pessoas.map(p => `<option value="${Number(p.id)}">${AtendeLabApi.escape(p.nome)}</option>`).join('');

  document.getElementById('tipoSelect').innerHTML =
    '<option value="">Selecione</option>' +
    tipos.map(t => `<option value="${Number(t.id)}">${AtendeLabApi.escape(t.nome)}</option>`).join('');
}

async function carregarAtendimentos() {
  try {
    const resposta = await AtendeLabApi.get('atendimentos', 'listar');
    const atendimentos = AtendeLabApi.toList(resposta);
    const tbody = document.getElementById('tabelaAtendimentos');
    if (!atendimentos.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Nenhum atendimento registrado.</td></tr>';
      return;
    }
    tbody.innerHTML = atendimentos.map(atendimento => {
      const pessoa = labelRegistro(atendimento, 'pessoa_nome', 'pessoa', 'nome_pessoa');
      const tipo = labelRegistro(atendimento, 'tipo_nome', 'tipo', 'tipo_atendimento', 'nome_tipo');
      const responsavel = labelRegistro(atendimento, 'responsavel_nome', 'responsavel', 'usuario', 'usuario_nome', 'nome_usuario');
      const data = labelRegistro(atendimento, 'data_atendimento', 'data');
      const classeStatus = atendimento.status === 'concluido'
        ? 'text-bg-success'
        : atendimento.status === 'em_andamento'
          ? 'text-bg-warning'
          : 'text-bg-primary';
      return `
        <tr>
          <td>${AtendeLabApi.escape(atendimento.id)}</td>
          <td>${AtendeLabApi.escape(pessoa)}</td>
          <td>${AtendeLabApi.escape(tipo)}</td>
          <td>${AtendeLabApi.escape(responsavel)}</td>
          <td>${AtendeLabApi.escape(data)}</td>
          <td><span class="badge ${classeStatus}">${AtendeLabApi.escape(atendimento.status)}</span></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" onclick="abrirStatus(${Number(atendimento.id)}, '${AtendeLabApi.escapeAttr(atendimento.status)}')">Status</button>
          </td>
        </tr>
      `;
    }).join('');
  } catch (error) {
    AtendeLabApi.showAlert('alerta', error.message, 'danger');
  }
}

formAtendimento.addEventListener('submit', async event => {
  event.preventDefault();
  try {
    await AtendeLabApi.post('atendimentos', 'criar', new FormData(formAtendimento));
    AtendeLabApi.showAlert('alerta', 'Atendimento registrado com sucesso.');
    fecharFormulario();
    await carregarAtendimentos();
  } catch (error) {
    AtendeLabApi.showAlert('alerta', error.message, 'danger');
  }
});

function abrirStatus(id, status) {
  document.getElementById('statusId').value = id;
  document.querySelector('#formStatus [name="status"]').value = status || 'aberto';
  document.querySelector('#formStatus [name="observacao"]').value = '';
  statusModal().show();
}

document.getElementById('formStatus').addEventListener('submit', async event => {
  event.preventDefault();
  try {
    await AtendeLabApi.post('atendimentos', 'alterarStatus', new FormData(event.target));
    statusModal().hide();
    AtendeLabApi.showAlert('alerta', 'Status atualizado com sucesso.');
    await carregarAtendimentos();
  } catch (error) {
    AtendeLabApi.showAlert('alerta', error.message, 'danger');
  }
});

document.addEventListener('DOMContentLoaded', async () => {
  try {
    await carregarCombos();
    await carregarAtendimentos();
  } catch (error) {
    AtendeLabApi.showAlert('alerta', error.message, 'danger');
  }
});
</script>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
