<?php
$tituloPagina = 'Pessoas atendidas';
require __DIR__ . '/../layouts/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 mb-1">Pessoas atendidas</h1>
    <p class="text-secondary mb-0">Cadastro, edição e inativação sem excluir o histórico.</p>
  </div>
  <button class="btn btn-success" type="button" onclick="novaPessoa()">Nova pessoa</button>
</div>
<div id="alerta"></div>
<div class="card border-0 shadow-sm mb-4 d-none" id="cardFormulario">
  <div class="card-body">
    <h2 class="h5" id="tituloFormulario">Nova pessoa</h2>
    <form id="formPessoa">
      <input type="hidden" name="id" id="pessoaId">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nome *</label>
          <input class="form-control" name="nome" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Documento *</label>
          <input class="form-control" name="documento" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Telefone</label>
          <input class="form-control" name="telefone">
        </div>
        <div class="col-md-4">
          <label class="form-label">Curso</label>
          <input class="form-control" name="curso">
        </div>
        <div class="col-md-4">
          <label class="form-label">Período</label>
          <input class="form-control" name="periodo">
        </div>
        <div class="col-md-4">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
          </select>
        </div>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-success" type="submit">Salvar</button>
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
          <th>Nome</th>
          <th>Documento</th>
          <th>Telefone</th>
          <th>Curso</th>
          <th>Período</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody id="tabelaPessoas">
        <tr><td colspan="7" class="text-center py-4">Carregando...</td></tr>
      </tbody>
    </table>
  </div>
</div>
<script>
const formPessoa = document.getElementById('formPessoa');
const cardFormulario = document.getElementById('cardFormulario');

function abrirFormulario() {
  cardFormulario.classList.remove('d-none');
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
function fecharFormulario() {
  cardFormulario.classList.add('d-none');
  formPessoa.reset();
  document.getElementById('pessoaId').value = '';
}
function novaPessoa() {
  fecharFormulario();
  document.getElementById('tituloFormulario').textContent = 'Nova pessoa';
  abrirFormulario();
}

async function carregarPessoas() {
  try {
    const dados = AtendeLabApi.toList(await AtendeLabApi.get('pessoas', 'listar'));
    const tbody = document.getElementById('tabelaPessoas');
    if (!dados.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Nenhuma pessoa cadastrada.</td></tr>';
      return;
    }
    tbody.innerHTML = dados.map(p => `
      <tr>
        <td>${AtendeLabApi.escape(p.nome)}</td>
        <td>${AtendeLabApi.escape(p.documento)}</td>
        <td>${AtendeLabApi.escape(p.telefone || '')}</td>
        <td>${AtendeLabApi.escape(p.curso || '')}</td>
        <td>${AtendeLabApi.escape(p.periodo || '')}</td>
        <td><span class="badge ${p.status === 'ativo' ? 'text-bg-success' : 'text-bg-secondary'}">${AtendeLabApi.escape(p.status)}</span></td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary" onclick="editarPessoa(${Number(p.id)})">Editar</button>
          <button class="btn btn-sm btn-outline-danger" onclick="inativarPessoa(${Number(p.id)})">Inativar</button>
        </td>
      </tr>
    `).join('');
  } catch (error) {
    AtendeLabApi.showAlert('alerta', error.message, 'danger');
  }
}

async function editarPessoa(id) {
  try {
    const p = AtendeLabApi.toObject(await AtendeLabApi.get('pessoas', 'buscar', { id }));
    novaPessoa();
    document.getElementById('tituloFormulario').textContent = 'Editar pessoa';
    for (const [key, value] of Object.entries(p)) {
      const field = formPessoa.elements.namedItem(key);
      if (field) field.value = value ?? '';
    }
    document.getElementById('pessoaId').value = p.id;
  } catch (error) {
    AtendeLabApi.showAlert('alerta', error.message, 'danger');
  }
}

formPessoa.addEventListener('submit', async event => {
  event.preventDefault();
  const id = document.getElementById('pessoaId').value;
  try {
    await AtendeLabApi.post('pessoas', id ? 'atualizar' : 'criar', new FormData(formPessoa));
    AtendeLabApi.showAlert('alerta', id ? 'Pessoa atualizada com sucesso.' : 'Pessoa cadastrada com sucesso.');
    fecharFormulario();
    await carregarPessoas();
  } catch (error) {
    AtendeLabApi.showAlert('alerta', error.message, 'danger');
  }
});

async function inativarPessoa(id) {
  if (!confirm('Deseja inativar esta pessoa?')) return;
  try {
    await AtendeLabApi.post('pessoas', 'inativar', { id });
    AtendeLabApi.showAlert('alerta', 'Pessoa inativada com sucesso.');
    await carregarPessoas();
  } catch (error) {
    AtendeLabApi.showAlert('alerta', error.message, 'danger');
  }
}

document.addEventListener('DOMContentLoaded', carregarPessoas);
</script>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
