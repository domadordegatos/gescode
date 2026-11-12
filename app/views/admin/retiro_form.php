<?php
$pageTitle   = 'Retiro de ' . $estudiante['nombre'] . ' — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Retiro anticipado', 'url' => APP_URL . '/retiro-anticipado'],
    ['label' => $estudiante['nombre'] . ' ' . $estudiante['apellido'], 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></h1>
        <p class="page-subtitle"><?= e($estudiante['documento']) ?></p>
    </div>
</div>

<div class="card" style="padding:22px 24px;max-width:680px">
    <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:12px 14px;font-size:.8rem;color:#92400E;margin-bottom:18px">
        <i class="mdi mdi-shield-check-outline"></i>
        Entrega este dispositivo a la persona que retira al estudiante para que tome la fotografía siguiendo la instrucción indicada.
    </div>

    <form method="POST" action="<?= APP_URL ?>/retiro-anticipado/<?= $estudiante['id'] ?>/guardar" id="form-retiro">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <input type="hidden" name="instruccion" value="<?= e($instruccion) ?>">
        <input type="hidden" name="foto_base64" id="input-foto">

        <div class="form-group">
            <label>¿Quién retira al estudiante?</label>
            <select name="acudiente_id" id="sel-acudiente" class="form-input">
                <option value="">Persona distinta a los acudientes registrados</option>
                <?php foreach ($acudientes as $a): ?>
                <option value="<?= $a['id'] ?>" data-nombre="<?= e($a['nombre'] . ' ' . $a['apellido']) ?>" data-parentesco="<?= e($a['parentesco'] ?? '') ?>">
                    <?= e($a['nombre'] . ' ' . $a['apellido']) ?> (<?= e($a['parentesco'] ?? 'Acudiente') ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row-2">
            <div class="form-group">
                <label>Nombre completo de quien retira <span class="required">*</span></label>
                <input type="text" name="nombre_persona" id="input-nombre" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Documento <span class="required">*</span></label>
                <input type="text" name="documento_persona" id="input-documento" class="form-input" required>
            </div>
        </div>

        <div class="form-group">
            <label>Parentesco con el estudiante <span class="required">*</span></label>
            <input type="text" name="parentesco" id="input-parentesco" class="form-input" required placeholder="Ej: Madre, Padre, Tío, Conductor autorizado...">
        </div>

        <?php if ($acudientes): ?>
        <div class="form-group" id="grupo-autoriza" style="display:none">
            <label>Si no es un acudiente registrado, ¿quién autorizó este retiro?</label>
            <select name="autorizado_por_acudiente_id" class="form-input">
                <option value="">No especificado</option>
                <?php foreach ($acudientes as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['nombre'] . ' ' . $a['apellido']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Motivo del retiro</label>
            <input type="text" name="motivo" class="form-input" placeholder="Ej: Cita médica">
        </div>

        <div class="form-group">
            <label>Fotografía de verificación <span class="required">*</span></label>

            <div style="background:#DBEAFE;border:1px solid #BFDBFE;border-radius:10px;padding:12px 14px;margin-bottom:10px;text-align:center">
                <div style="font-size:.75rem;color:#1E40AF;font-weight:600">LA PERSONA QUE SE FOTOGRAFÍA DEBE:</div>
                <div style="font-size:1rem;font-weight:700;color:#1E3A8A;margin-top:4px"><?= e($instruccion) ?></div>
            </div>

            <div style="position:relative;max-width:360px;margin:0 auto">
                <video id="video" autoplay playsinline style="width:100%;border-radius:12px;background:#000;display:block"></video>
                <canvas id="canvas" style="display:none"></canvas>
                <img id="preview" style="width:100%;border-radius:12px;display:none">
            </div>

            <div style="text-align:center;margin-top:14px">
                <button type="button" id="btn-capturar" class="btn-primary" disabled>
                    <i class="mdi mdi-camera"></i> <span id="btn-capturar-texto">Iniciando cámara...</span>
                </button>
                <button type="button" id="btn-retomar" class="btn-secondary" style="display:none">
                    <i class="mdi mdi-camera-retake-outline"></i> Tomar de nuevo
                </button>
            </div>
            <div id="camara-error" style="display:none;color:var(--danger);font-size:.82rem;text-align:center;margin-top:10px">
                No se pudo acceder a la cámara. Debes permitir el acceso para registrar el retiro.
            </div>
        </div>

        <div class="form-actions" style="margin-top:18px">
            <button type="submit" id="btn-firmar" class="btn-primary" disabled>
                <i class="mdi mdi-check-circle-outline"></i> Registrar retiro
            </button>
        </div>
    </form>
</div>

<script>
// Autocompletar nombre/parentesco al elegir un acudiente registrado
const selAcudiente = document.getElementById('sel-acudiente');
const grupoAutoriza = document.getElementById('grupo-autoriza');
if (selAcudiente) {
    selAcudiente.addEventListener('change', () => {
        const opt = selAcudiente.selectedOptions[0];
        if (selAcudiente.value) {
            document.getElementById('input-nombre').value = opt.dataset.nombre || '';
            document.getElementById('input-parentesco').value = opt.dataset.parentesco || '';
            if (grupoAutoriza) grupoAutoriza.style.display = 'none';
        } else {
            document.getElementById('input-nombre').value = '';
            document.getElementById('input-parentesco').value = '';
            if (grupoAutoriza) grupoAutoriza.style.display = 'block';
        }
    });
}

const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const preview = document.getElementById('preview');
const btnCapturar = document.getElementById('btn-capturar');
const btnCapturarTexto = document.getElementById('btn-capturar-texto');
const btnRetomar = document.getElementById('btn-retomar');
const btnFirmar = document.getElementById('btn-firmar');
const inputFoto = document.getElementById('input-foto');
const camaraError = document.getElementById('camara-error');

let stream = null;

navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
    .then(s => {
        stream = s;
        video.srcObject = s;
        let segundos = 3;
        btnCapturarTexto.textContent = `Prepárate... (${segundos})`;
        const intervalo = setInterval(() => {
            segundos--;
            if (segundos <= 0) {
                clearInterval(intervalo);
                btnCapturarTexto.textContent = 'Tomar fotografía';
                btnCapturar.disabled = false;
            } else {
                btnCapturarTexto.textContent = `Prepárate... (${segundos})`;
            }
        }, 1000);
    })
    .catch(() => {
        camaraError.style.display = 'block';
        btnCapturarTexto.textContent = 'Cámara no disponible';
    });

btnCapturar.addEventListener('click', () => {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
    inputFoto.value = dataUrl;

    preview.src = dataUrl;
    preview.style.display = 'block';
    video.style.display = 'none';
    btnCapturar.style.display = 'none';
    btnRetomar.style.display = 'inline-block';
    btnFirmar.disabled = false;
});

btnRetomar.addEventListener('click', () => {
    preview.style.display = 'none';
    video.style.display = 'block';
    btnRetomar.style.display = 'none';
    btnCapturar.style.display = 'inline-block';
    btnFirmar.disabled = true;
    inputFoto.value = '';
});

document.getElementById('form-retiro').addEventListener('submit', () => {
    if (stream) stream.getTracks().forEach(t => t.stop());
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
