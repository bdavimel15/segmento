<div id="explicacaoDrawer" class="explicacao-drawer" aria-hidden="true">
  <div class="explicacao-drawer-backdrop" onclick="Segmentador.closeDrawer()"></div>
  <aside class="explicacao-drawer-panel" role="dialog" aria-label="Detalhes do cliente">
    <div class="explicacao-drawer-header">
      <h2>Por que este cliente entrou?</h2>
      <button type="button" class="btn btn-ghost btn-sm" onclick="Segmentador.closeDrawer()" aria-label="Fechar">✕</button>
    </div>
    <div id="explicacaoDrawerBody" class="explicacao-drawer-body"></div>
  </aside>
</div>

<div id="modalRemoveGroup" class="modal-overlay" aria-hidden="true">
  <div class="modal-card">
    <h3>Excluir grupo?</h3>
    <p class="muted">As condições deste grupo serão removidas. Esta ação não pode ser desfeita.</p>
    <div class="modal-actions">
      <button type="button" class="btn btn-secondary" onclick="Segmentador.cancelRemoveGroup()">Cancelar</button>
      <button type="button" class="btn btn-danger" onclick="Segmentador.executeRemoveGroup()">Excluir</button>
    </div>
  </div>
</div>
