<?php $titulo = 'Ficha única del estudiante'; ?>
<div class="card" style="max-width:720px;margin:0 auto">
    <h2 style="margin-top:0"><i class="mdi mdi-card-account-details-outline"></i> Ficha única del estudiante</h2>
    <p style="color:var(--text-muted)">Busca un estudiante para ver su información consolidada: datos personales, notas, asistencia, observaciones, autorizaciones, retiros y PQRS de su familia.</p>

    <input type="text" id="buscar-estudiante" class="form-input" placeholder="Nombre, apellido o documento..." autocomplete="off" style="font-size:1rem;padding:12px">
    <div id="resultados-estudiante" style="margin-top:14px"></div>
</div>

<script>
const inputEst = document.getElementById('buscar-estudiante');
const resultadosEst = document.getElementById('resultados-estudiante');
let timerEst = null;

inputEst.addEventListener('input', () => {
    clearTimeout(timerEst);
    const q = inputEst.value.trim();
    if (q.length < 2) { resultadosEst.innerHTML = ''; return; }
    timerEst = setTimeout(() => {
        fetch('<?= APP_URL ?>/ficha-estudiante/buscar?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.length) { resultadosEst.innerHTML = '<p style="color:var(--text-muted)">Sin resultados.</p>'; return; }
                resultadosEst.innerHTML = data.map(e => `
                    <a href="<?= APP_URL ?>/ficha-estudiante/${e.estudiante_id}" style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border:1px solid var(--border);border-radius:8px;margin-bottom:8px;text-decoration:none;color:inherit">
                        <div>
                            <strong>${e.nombre} ${e.apellido}</strong><br>
                            <span style="font-size:.82rem;color:var(--text-muted)">Doc: ${e.documento || 'N/A'}</span>
                        </div>
                        <span style="font-size:.82rem;color:var(--primary)">${e.grupo_actual || 'Sin grupo'} <i class="mdi mdi-chevron-right"></i></span>
                    </a>
                `).join('');
            });
    }, 250);
});
</script>
