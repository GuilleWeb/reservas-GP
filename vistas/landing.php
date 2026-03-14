<?php
// vistas/landing.php
?>

<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
  <section class="bg-white rounded-3xl shadow-2xl overflow-hidden">
    <div class="grid grid-cols-1 lg:grid-cols-2">
      <div class="p-10 lg:p-14">
        <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 leading-tight">
          Gestión profesional de citas
        </h1>
        <p class="mt-4 text-lg text-gray-600">
          Un sistema multi-empresa para administrar sucursales, empleados, servicios y citas.
          Ideal para barberías, salones, consultorios y más.
        </p>

        <div class="mt-8 flex flex-col sm:flex-row gap-3">
          <a href="<?= view_url('vistas/public/login.php') ?>" class="inline-flex items-center justify-center px-8 py-3 rounded-full shadow-md text-white bg-teal-600 hover:bg-teal-700 font-bold">
            Iniciar sesión
          </a>
          <a href="#features" class="inline-flex items-center justify-center px-8 py-3 rounded-full border border-teal-600 text-teal-700 bg-white hover:bg-teal-50 font-bold">
            Ver características
          </a>
        </div>

        <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700">
          <div class="p-4 rounded-xl bg-teal-50 border border-teal-100">
            <div class="font-semibold text-teal-800">Multi-empresa</div>
            <div class="mt-1 text-gray-600">Cada negocio con su propio panel y configuración.</div>
          </div>
          <div class="p-4 rounded-xl bg-teal-50 border border-teal-100">
            <div class="font-semibold text-teal-800">Agenda inteligente</div>
            <div class="mt-1 text-gray-600">Servicios, duración, empleado y disponibilidad.</div>
          </div>
          <div class="p-4 rounded-xl bg-teal-50 border border-teal-100">
            <div class="font-semibold text-teal-800">Sucursales</div>
            <div class="mt-1 text-gray-600">Horarios y métricas por sucursal.</div>
          </div>
          <div class="p-4 rounded-xl bg-teal-50 border border-teal-100">
            <div class="font-semibold text-teal-800">Clientes</div>
            <div class="mt-1 text-gray-600">Agenda sin registro; cuenta opcional.</div>
          </div>
        </div>
      </div>

      <div class="bg-gradient-to-br from-teal-600 to-teal-800 p-10 lg:p-14 text-white">
        <h2 class="text-2xl font-bold">¿Qué incluye?</h2>
        <ul id="features" class="mt-6 space-y-3 text-white/95">
          <li class="flex gap-3"><span class="mt-1"><i class="fas fa-check"></i></span><span>Panel SuperAdmin para empresas y planes.</span></li>
          <li class="flex gap-3"><span class="mt-1"><i class="fas fa-check"></i></span><span>Panel Admin por empresa con sucursales, empleados, servicios y citas.</span></li>
          <li class="flex gap-3"><span class="mt-1"><i class="fas fa-check"></i></span><span>Panel Empleado con su agenda y disponibilidad.</span></li>
          <li class="flex gap-3"><span class="mt-1"><i class="fas fa-check"></i></span><span>Home público por empresa: reseñas, equipo, blog y formulario de contacto.</span></li>
        </ul>

        <div class="mt-10 rounded-2xl bg-white/10 border border-white/20 p-6">
          <div class="text-sm uppercase tracking-wide text-white/80">Demo</div>
          <div class="mt-2 text-lg font-semibold">Ejemplo de negocio</div>
          <div class="mt-3">
            <a href="<?= view_url('vistas/public/inicio.php', 'barberia') ?>" class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-white text-teal-700 hover:bg-white/90 font-bold">
              Ver /barberia/
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
