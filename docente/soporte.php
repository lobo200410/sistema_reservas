<?php
session_start();
?>

 <!-- Contenido en tarjetas (grid responsive) -->
    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

      <!-- Card: Manuales PDF -->
      <article class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 p-6">
        <div class="flex items-center gap-3">
          <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
            <i class="fa-solid fa-file-pdf"></i>
          </span>
          <h2 class="text-lg font-semibold text-gray-900">Manuales PDF</h2>
        </div>

        <ul class="mt-5 space-y-3">
          <li>
            <a href="../manuales/manual_reservas.pdf" target="_blank"
               class="group inline-flex w-full items-center justify-between rounded-xl border border-gray-200 px-4 py-3 text-left hover:border-brand-500 hover:bg-brand-50/40 transition">
              <span class="flex items-center gap-3">
                <i class="fa-solid fa-book text-gray-500 group-hover:text-brand-600"></i>
                <span class="text-sm font-medium text-gray-800 group-hover:text-brand-700">
                  Manual de uso del sistema de reservas
                </span>
              </span>
              <i class="fa-solid fa-arrow-up-right-from-square text-gray-400 group-hover:text-brand-600"></i>
            </a>
          </li>
          <li>
            <a href="../manuales/manual_grabaciones.pdf" target="_blank"
               class="group inline-flex w-full items-center justify-between rounded-xl border border-gray-200 px-4 py-3 text-left hover:border-brand-500 hover:bg-brand-50/40 transition">
              <span class="flex items-center gap-3">
                <i class="fa-solid fa-video text-gray-500 group-hover:text-brand-600"></i>
                <span class="text-sm font-medium text-gray-800 group-hover:text-brand-700">
                  Manual para grabación de clases
                </span>
              </span>
              <i class="fa-solid fa-arrow-up-right-from-square text-gray-400 group-hover:text-brand-600"></i>
            </a>
          </li>
        </ul>
      </article>

      <!-- Card: Contacto -->
      <article class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 p-6">
        <div class="flex items-center gap-3">
          <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
            <i class="fa-solid fa-address-book"></i>
          </span>
          <h2 class="text-lg font-semibold text-gray-900">Contacto</h2>
        </div>

        <div class="mt-5 space-y-3 text-sm">
          <p class="flex items-start gap-3">
            <i class="fa-solid fa-envelope mt-0.5 text-gray-500"></i>
            <span>
              <strong>Correo soporte:</strong>
              <a href="mailto:david.hernandez@utec.edu.sv" class="text-brand-600 hover:underline">
                david.hernandez@utec.edu.sv
              </a>
            </span>
          </p>

          <p class="flex items-start gap-3">
            <i class="fa-solid fa-phone mt-0.5 text-gray-500"></i>
            <span>
              <strong>Teléfono:</strong> (503) 2272-8888 ext. 8797
            </span>
          </p>

          <p class="flex items-start gap-3">
            <i class="fa-regular fa-clock mt-0.5 text-gray-500"></i>
            <span>
              <strong>Horario de atención:</strong> Lunes a Viernes, 8:00 AM – 12:00 PM
            </span>
          </p>

          <!-- Etiqueta de estado / aviso (opcional) -->
          <div class="mt-4 rounded-xl bg-brand-50/60 px-4 py-3 text-xs text-brand-700 border border-brand-100">
            Respuesta habitual en horario laboral.
          </div>
        </div>
      </article>

      <!-- Card: Enlaces útiles -->
      <article class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 p-6">
        <div class="flex items-center gap-3">
          <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
            <i class="fa-solid fa-link"></i>
          </span>
          <h2 class="text-lg font-semibold text-gray-900">Enlaces útiles</h2>
        </div>

        <div class="mt-5 space-y-3">
          <a href="https://www.utecvirtual.edu.sv/" target="_blank"
             class="group block rounded-xl border border-gray-200 p-4 hover:border-brand-500 hover:bg-brand-50/40 transition">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <i class="fa-solid fa-up-right-from-square text-gray-400 group-hover:text-brand-600"></i>
                <span class="text-sm font-medium text-gray-800 group-hover:text-brand-700">
                  Aula de apoyo Docentes Presenciales
                </span>
              </div>
            </div>
          </a>

          <a href="https://utecvirtual.blackboard.com/" target="_blank"
             class="group block rounded-xl border border-gray-200 p-4 hover:border-brand-500 hover:bg-brand-50/40 transition">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <i class="fa-solid fa-up-right-from-square text-gray-400 group-hover:text-brand-600"></i>
                <span class="text-sm font-medium text-gray-800 group-hover:text-brand-700">
                  Aula de apoyo Docentes Virtuales
                </span>
              </div>
            </div>
          </a>
        </div>

         <a href="https://portal.utec.edu.sv/" target="_blank"
             class="group block rounded-xl border border-gray-200 p-4 hover:border-brand-500 hover:bg-brand-50/40 transition">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <i class="fa-solid fa-up-right-from-square text-gray-400 group-hover:text-brand-600"></i>
                <span class="text-sm font-medium text-gray-800 group-hover:text-brand-700">
                  Portal Educativo
                </span>
              </div>
            </div>
          </a>
      </article>

    </section>