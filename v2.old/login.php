<?php
require_once __DIR__ . '/app/bootstrap.php';

$empresa_info = $GLOBALS['empresa_info'] ?? null;
$id_e = $empresa_info['slug'] ?? request_id_e();
$empresa_nombre = $empresa_info ? (string) $empresa_info['nombre'] : 'Sistema';

// Si ya hay sesión, redirigir a un lugar razonable
$u = current_user();
if ($u) {
  $rol = $u['rol'] ?? null;
  if ($rol === 'superadmin') {
    header('Location: sadmin/dashboard.php');
    exit;
  }
  if (in_array($rol, ['admin', 'gerente', 'empleado'], true)) {
    $to = 'admin/dashboard.php' . ($id_e ? ('?id_e=' . rawurlencode((string) $id_e)) : '');
    header('Location: ' . $to);
    exit;
  }
  if ($rol === 'cliente') {
    $to = 'cadmin/citas.php' . ($id_e ? ('?id_e=' . rawurlencode((string) $id_e)) : '');
    header('Location: ' . $to);
    exit;
  }
}
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-50 min-h-screen">

  <div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-white shadow-xl rounded-2xl p-8 border border-gray-100">
      <div class="text-center mb-6">
        <div class="flex justify-center mb-2">
          <div class="h-14 w-14 rounded-full bg-teal-600 text-white grid place-items-center text-2xl font-bold">
            <i class="fas fa-user-circle"></i>
          </div>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Bienvenido</h1>
        <p class="text-sm text-gray-500">
          Accede a tu cuenta para continuar
            <?php if ($id_e): ?>
            <span class="block mt-1 font-semibold text-teal-700"><?= htmlspecialchars($empresa_nombre) ?></span>
            <?php endif; ?>
        </p>
      </div>

      <form id="formLogin" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
          <input type="email" id="email"
            class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
            placeholder="ejemplo@correo.com" required>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
          <input type="password" id="password"
            class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
            placeholder="••••••••" required>
        </div>

        <div id="msgLogin" class="text-sm text-center"></div>

        <button type="submit" id="btnLogin"
          class="w-full bg-teal-600 hover:bg-teal-700 text-white py-2.5 rounded-lg font-semibold transition">
          Ingresar
        </button>
      </form>
    </div>
  </div>

  <script>
    (function () {
      const form = document.getElementById('formLogin');
      const btn = document.getElementById('btnLogin');
      const msg = document.getElementById('msgLogin');

      function setMsg(html) {
        msg.innerHTML = html;
      }

      async function attemptLogin(force) {
        btn.disabled = true;
        btn.textContent = 'Verificando...';
        setMsg('');

        const fd = new FormData();
        fd.append('action', 'login');
        fd.append('email', document.getElementById('email').value.trim());
        fd.append('password', document.getElementById('password').value);
        fd.append('force_login', force ? '1' : '0');
        const slug = <?= json_encode($id_e ?: '') ?>;
        if (slug) fd.append('id_e', slug);

        let res;
        try {
          const r = await fetch('api/auth.php', { method: 'POST', body: fd });
          res = await r.json();
          if (!r.ok) {
            throw { status: r.status, body: res };
          }

          setMsg('<p class="text-green-600 font-medium">Acceso correcto, redirigiendo...</p>');
          const redirect = res.redirect_url || 'public/inicio.php';
          setTimeout(() => window.location.href = redirect, 700);
          return;
        } catch (e) {
          const status = e && e.status ? e.status : 0;
          const body = e && e.body ? e.body : {};

          if (status === 409 && body && body.error === 'session_active') {
            const ok = window.confirm(body.message || 'Ya tienes una sesión activa en otro dispositivo. ¿Deseas cerrarla e ingresar desde aquí?');
            if (ok) {
              btn.disabled = false;
              btn.textContent = 'Ingresar';
              return attemptLogin(true);
            }
            setMsg('<p class="text-yellow-600">Inicio cancelado.</p>');
          } else if (body && body.error === 'inactive') {
            setMsg('<p class="text-red-600">Tu cuenta se encuentra inactiva.</p>');
          } else if (body && body.error === 'inactive_empresa') {
            setMsg('<p class="text-red-600">' + (body.message || 'Empresa inactiva.') + '</p>');
          } else if (body && body.error === 'inactive_sucursal') {
            setMsg('<p class="text-red-600">' + (body.message || 'Sucursal inactiva.') + '</p>');
          } else if (body && body.error === 'login_disabled') {
            setMsg('<p class="text-red-600">' + (body.message || 'Login deshabilitado.') + '</p>');
          } else {
            setMsg('<p class="text-red-600">Credenciales inválidas.</p>');
          }

          btn.disabled = false;
          btn.textContent = 'Ingresar';
        }
      }

      form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        if (!email || !password) {
          setMsg('<p class="text-red-600">Todos los campos son obligatorios.</p>');
          return;
        }
        attemptLogin(false);
      });
    })();
  </script>

</body>

</html>