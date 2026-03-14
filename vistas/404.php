<div class="max-w-4xl mx-auto">
  <div class="bg-white rounded-2xl shadow p-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
      <div>
        <div class="text-sm text-gray-500">Error 404</div>
        <div class="mt-2 text-3xl font-extrabold text-gray-900">Página no encontrada</div>
        <div class="mt-3 text-gray-700">
          Puede que el enlace esté mal escrito, haya cambiado o no esté disponible.
        </div>
      </div>

      <div class="relative w-full md:w-56 h-28">
        <div class="absolute inset-0 flex items-center justify-center">
          <div
            class="w-24 h-24 rounded-3xl bg-teal-50 border border-teal-100 shadow-sm grid place-items-center animate-[float_3s_ease-in-out_infinite]">
            <i class="fas fa-compass text-teal-700 text-4xl"></i>
          </div>
        </div>
        <div
          class="absolute left-4 top-4 w-4 h-4 rounded-full bg-teal-200/70 blur-[1px] animate-[pulse_2.2s_ease-in-out_infinite]">
        </div>
        <div
          class="absolute right-6 bottom-6 w-5 h-5 rounded-full bg-teal-300/60 blur-[1px] animate-[pulse_2.8s_ease-in-out_infinite]">
        </div>
      </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row gap-3">
      <a href="<?= ($user && $role === 'superadmin') ? htmlspecialchars(view_url('vistas/superadmin/dashboard.php')) : htmlspecialchars(view_url($id_e ? 'vistas/public/inicio.php' : 'vistas/public/inicio.php', $id_e)) ?>"
        class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-teal-600 text-white hover:opacity-90">
        Ir al inicio
      </a>
      <!--<a href="<?= htmlspecialchars($login_href) ?>"
        class="inline-flex items-center justify-center px-4 py-2 rounded-lg border hover:bg-gray-50">
        Iniciar sesión
      </a>-->
    </div>

    <style>
      @keyframes float {

        0%,
        100% {
          transform: translateY(0px);
        }

        50% {
          transform: translateY(-8px);
        }
      }
    </style>
  </div>
</div>