<?php
$pageTitle  = 'Confirmar carga de notas — ' . APP_NAME;
$noLeidos   = (new Usuario())->totalNoLeidos(Auth::id());
$breadcrumbs = [
    ['label' => 'Inicio',     'url' => APP_URL . '/dashboard'],
    ['label' => 'Mis clases', 'url' => APP_URL . '/mis-grupos'],
    ['label' => $asig['grado'] . ' ' . $asig['grupo'], 'url' => APP_URL . '/grupos/' . $gid . '/estudiantes'],
    ['label' => 'Cargar notas desde Excel', 'url' => ''],
];

$sinMatch = 0;
foreach ($pares as $p) { if (!$p['matricula_id']) $sinMatch++; }

ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Confirmar carga de notas</h1>
        <p class="page-subtitle">
            <?= e($asig['grado'] . ' ' . $asig['grupo'] . ' · ' . $asig['materia']) ?>
            — Evaluación: <strong><?= e($evDatos['eval_nombre'] ?? '') ?></strong>
            (<?= e($evDatos['codigo'] ? $evDatos['codigo'] . ' — ' . $evDatos['logro'] : ($evDatos['logro'] ?? '')) ?>)
        </p>
    </div>
    <a href="<?= APP_URL ?>/grupos/<?= $gid ?>/carga-notas?asig=<?= $asig['asignacion_id'] ?>&periodo=<?= $periodo_id ?>" class="btn-secondary">
        <i class="mdi mdi-arrow-left"></i> Empezar de nuevo
    </a>
</div>

<?php if ($sinMatch > 0): ?>
<div class="alert alert-error" style="margin-bottom:16px">
    <i class="mdi mdi-alert-circle"></i>
    <?= $sinMatch ?> fila(s) no se pudieron emparejar automáticamente con un estudiante del grupo —
    revísalas abajo (resaltadas) y elige el estudiante correcto, o desmarca "Incluir" para omitirlas.
</div>
<?php endif; ?>

<div class="info-box-blue" style="margin-bottom:16px">
    <i class="mdi mdi-information-outline"></i>
    <span>
        Se encontraron <strong><?= count($pares) ?></strong> fila(s) con nombre y nota válidos. Revisa cada
        una antes de confirmar — nada se guarda todavía.
    </span>
</div>

<form method="POST" action="<?= APP_URL ?>/grupos/<?= $gid ?>/carga-notas/confirmar" id="form-confirmar">
<input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
<input type="hidden" name="asig" value="<?= (int)$asig['asignacion_id'] ?>">
<input type="hidden" name="periodo" value="<?= (int)$periodo_id ?>">

<div class="card" style="padding:0;overflow:hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:40px">
                    <input type="checkbox" checked onclick="toggleTodos(this)" title="Incluir/excluir todas">
                </th>
                <th>Nombre en el Excel</th>
                <th style="text-align:center;width:80px">Nota</th>
                <th>Estudiante en Gescode</th>
                <th style="width:110px">Coincidencia</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pares as $i => $p):
                $sinCoincidencia = !$p['matricula_id'];
                $confianza = (int)$p['confianza'];
                $colorConf = $confianza >= 95 ? '#16A34A' : ($confianza >= 70 ? '#D97706' : '#DC2626');
            ?>
            <tr style="<?= $sinCoincidencia ? 'background:#FFF1F1' : '' ?>">
                <td style="text-align:center">
                    <input type="checkbox" name="incluir[<?= $i ?>]" value="1" class="chk-incluir" <?= $sinCoincidencia ? '' : 'checked' ?>>
                </td>
                <td style="font-size:.84rem"><?= e($p['nombre_excel']) ?></td>
                <td style="text-align:center;font-weight:700"><?= number_format($p['valor'], 1) ?></td>
                <td>
                    <select name="matricula[<?= $i ?>]" class="form-input" style="font-size:.82rem;padding:5px 8px">
                        <option value="">— No importar —</option>
                        <?php foreach ($estudiantes as $e): ?>
                        <option value="<?= $e['matricula_id'] ?>" <?= (int)($p['matricula_id'] ?? 0) === (int)$e['matricula_id'] ? 'selected' : '' ?>>
                            <?= e($e['apellido'] . ', ' . $e['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <?php if ($sinCoincidencia): ?>
                        <span style="color:#DC2626;font-size:.78rem;font-weight:700"><i class="mdi mdi-alert-outline"></i> Sin coincidencia</span>
                    <?php else: ?>
                        <span style="color:<?= $colorConf ?>;font-size:.78rem;font-weight:700"><?= $confianza ?>%</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="form-actions" style="margin-top:20px">
    <button type="submit" class="btn-primary" id="btn-confirmar">
        <i class="mdi mdi-check-circle-outline"></i> Confirmar y guardar notas
    </button>
    <span style="font-size:.8rem;color:var(--text-muted);margin-left:10px">
        Se guardarán solo las filas marcadas con "Incluir" y un estudiante asignado.
    </span>
</div>
</form>

<script>
function toggleTodos(master) {
    document.querySelectorAll('.chk-incluir').forEach(function(c) { c.checked = master.checked; });
}
document.getElementById('form-confirmar').addEventListener('submit', function(e) {
    var marcadas = document.querySelectorAll('.chk-incluir:checked').length;
    if (marcadas === 0) {
        e.preventDefault();
        alert('Marca al menos una fila para importar.');
        return;
    }
    if (!confirm('Se guardarán ' + marcadas + ' nota(s). ¿Confirmas?')) {
        e.preventDefault();
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
