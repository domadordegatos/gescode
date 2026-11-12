<?php
$pageTitle   = 'Firmar autorización — ' . APP_NAME;
$breadcrumbs = [
    ['label' => 'Inicio', 'url' => APP_URL . '/dashboard'],
    ['label' => 'Autorizaciones', 'url' => APP_URL . '/autorizaciones'],
    ['label' => 'Firmar', 'url' => ''],
];
$noLeidos = 0;

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($autorizacion['titulo']) ?></h1>
        <p class="page-subtitle">Para: <?= e($autorizacion['hijo_nombre'] . ' ' . $autorizacion['hijo_apellido']) ?></p>
    </div>
</div>

<div class="card" style="padding:22px 24px;max-width:680px;margin-bottom:18px">
    <div style="font-weight:700;font-size:.9rem;margin-bottom:10px">Texto del consentimiento</div>
    <div style="font-size:.9rem;white-space:pre-wrap;line-height:1.6;background:var(--surface-2);padding:14px 16px;border-radius:10px"><?= nl2br(e($textoFinal)) ?></div>
</div>

<div class="card" style="padding:22px 24px;max-width:680px">
    <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:12px 14px;font-size:.8rem;color:#92400E;margin-bottom:18px">
        <i class="mdi mdi-shield-check-outline"></i>
        Para verificar que eres tú quien firma, te pediremos tomar una fotografía en vivo (no se permite subir una imagen) siguiendo una instrucción aleatoria. Esta foto se almacena de forma protegida y solo la institución puede consultarla en caso de ser necesario.
    </div>

    <form method="POST" action="<?= APP_URL ?>/autorizaciones/firmar/<?= $estudianteId ?>/<?= $autorizacion['id'] ?>/guardar" id="form-firma">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= csrfToken() ?>">
        <input type="hidden" name="instruccion" value="<?= e($instruccion) ?>">
        <input type="hidden" name="foto_base64" id="input-foto">

        <div class="form-group">
            <label>Confirma tu número de documento <span class="required">*</span></label>
            <input type="text" name="documento_confirmado" id="input-documento" class="form-input" required
                   placeholder="<?= $documentoPadre ? 'Debe coincidir con tu documento registrado' : 'Escribe tu número de documento' ?>">
        </div>

        <div class="form-group">
            <label>Fotografía de verificación <span class="required">*</span></label>

            <div id="reto-banner" style="background:#DBEAFE;border:1px solid #BFDBFE;border-radius:10px;padding:12px 14px;margin-bottom:10px;text-align:center">
                <div style="font-size:.75rem;color:#1E40AF;font-weight:600">MIENTRAS TE GRABAS, HAZ ESTO:</div>
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
                No se pudo acceder a la cámara. Debes permitir el acceso para poder firmar.
            </div>
        </div>

        <div class="form-actions" style="margin-top:18px">
            <button type="submit" id="btn-firmar" class="btn-primary" disabled>
                <i class="mdi mdi-draw-pen"></i> Firmar autorización
            </button>
        </div>
    </form>
</div>

<script>
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
        // Pequeña espera antes de habilitar la captura, para que la persona
        // alcance a hacer el gesto solicitado con la cámara ya en vivo.
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

document.getElementById('form-firma').addEventListener('submit', () => {
    if (stream) stream.getTracks().forEach(t => t.stop());
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
