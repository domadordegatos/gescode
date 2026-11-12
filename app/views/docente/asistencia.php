<?php
$gid        = $grupo['grupo_id'];
$pageTitle  = 'Asistencia — '.$grupo['grado'].' '.$grupo['grupo'].' — '.APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$tab_activo = 'asistencia';
$breadcrumbs= [
  ['label'=>'Inicio',     'url'=>APP_URL.'/dashboard'],
  ['label'=>'Mis Grupos', 'url'=>APP_URL.'/mis-grupos'],
  ['label'=>$grupo['grado'].' '.$grupo['grupo'],'url'=>APP_URL.'/grupos/'.$gid.'/estudiantes'],
  ['label'=>'Asistencia', 'url'=>''],
];
$grupo_extra_title = ' <span class="sep">·</span> <span style="font-size:.82rem;font-weight:600;opacity:.85">Asistencia — '.date('d/m/Y', strtotime($fecha)).'</span>';
ob_start();
?>

<?php include __DIR__.'/_grupo_tabs.php'; ?>

<form method="POST" action="<?= APP_URL ?>/grupos/<?= $gid ?>/asistencia/guardar">
  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
  <input type="hidden" name="fecha" value="<?= $fecha ?>">

  <?php if (empty($estudiantes)): ?>
    <div class="empty-state"><i class="mdi mdi-account-school-outline"></i><p>Sin estudiantes matriculados.</p></div>
  <?php else: ?>

  <!-- Fila de selección global -->
  <?php
  $estados_cfg = [
    'presente' => ['label'=>'Presentes',   'icon'=>'mdi-check-circle-outline',  'tip'=>'Presente'],
    'ausente'  => ['label'=>'Ausentes',    'icon'=>'mdi-close-circle-outline',  'tip'=>'Ausente'],
    'tarde'    => ['label'=>'Tarde',       'icon'=>'mdi-clock-alert-outline',   'tip'=>'Tarde'],
    'excusa'   => ['label'=>'Con excusa',  'icon'=>'mdi-shield-check-outline',  'tip'=>'Excusa'],
    'incapacidad' => ['label'=>'Incapacidad', 'icon'=>'mdi-hospital-box-outline', 'tip'=>'Incapacidad médica'],
  ];
  ?>
  <div class="asist-global-bar">
    <span class="fw-600 text-sm">Marcar todos:</span>
    <?php foreach ($estados_cfg as $val => $cfg): ?>
      <button type="button" class="asist-global-btn <?= $val ?>" onclick="marcarTodos('<?= $val ?>')">
        <i class="mdi <?= $cfg['icon'] ?>"></i><?= $cfg['label'] ?>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="card" style="overflow:auto">
    <table class="data-table asist-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Estudiante</th>
          <th>Estado</th>
          <th>Observación (opcional)</th>
          <th>Reportado por (opcional)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($estudiantes as $i => $est): ?>
        <tr class="asist-row estado-<?= $est['estado'] ?>">
          <td class="text-muted"><?= $i+1 ?></td>
          <td><strong><?= e($est['apellido'].', '.$est['nombre']) ?></strong></td>
          <td>
            <input type="hidden"
                   name="asistencia[<?= $est['matricula_id'] ?>]"
                   class="asist-val"
                   value="<?= $est['estado'] ?>">
            <div class="asist-btn-group">
              <?php foreach ($estados_cfg as $val => $cfg): ?>
                <button type="button"
                        class="asist-btn <?= $val ?><?= $est['estado']===$val?' active':'' ?>"
                        data-tooltip="<?= $cfg['tip'] ?>"
                        onclick="setEstado(this, '<?= $val ?>')">
                  <i class="mdi <?= $cfg['icon'] ?>"></i>
                </button>
              <?php endforeach; ?>
            </div>
          </td>
          <td>
            <input type="text" name="obs[<?= $est['matricula_id'] ?>]"
                   class="form-input" style="font-size:.85rem;padding:5px 8px"
                   value="<?= e($est['observacion'] ?? '') ?>"
                   placeholder="Opcional...">
          </td>
          <td>
            <input type="text" name="reportado_por[<?= $est['matricula_id'] ?>]"
                   class="form-input" style="font-size:.85rem;padding:5px 8px"
                   value="<?= e($est['reportado_por'] ?? '') ?>"
                   placeholder="Ej: acudiente, enfermería...">
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="form-actions" style="margin-top:16px">
    <button type="submit" class="btn-primary">
      <i class="mdi mdi-content-save-outline"></i> Guardar asistencia
    </button>
    <span class="text-muted text-sm"><?= date('l d/m/Y', strtotime($fecha)) ?></span>
  </div>
  <?php endif; ?>
</form>

<script>
function setEstado(btn, val) {
  const row = btn.closest('tr');
  btn.closest('.asist-btn-group').querySelectorAll('.asist-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  row.querySelector('.asist-val').value = val;
  row.className = row.className.replace(/estado-\w+/, '');
  row.classList.add('estado-' + val);
  if (val !== 'presente') row.querySelector('input[type="text"]').focus();
}
function marcarTodos(val) {
  document.querySelectorAll('.asist-btn-group').forEach(group => {
    const btn = group.querySelector('.asist-btn.' + val);
    if (btn) setEstado(btn, val);
  });
}
</script>

<?php $content = ob_get_clean(); include __DIR__.'/../layouts/main.php'; ?>

