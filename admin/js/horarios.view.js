// js/horarios.view.js
(function () {
  if (typeof window.jQuery === 'undefined') {
    console.error('jQuery no está disponible.');
    return;
  }
  const $ = window.jQuery;

  window.initHorarios = function(){
    const API = 'horarios_api.php';

    function toast(m, ok=true){
      const id = 't' + Date.now();
      const el = $(`<div id="${id}" class="fixed right-4 top-4 z-50 rounded-lg px-4 py-2 text-sm shadow ${ok ? 'bg-green-600' : 'bg-red-600'} text-white">${m}</div>`);
      $('body').append(el);
      setTimeout(()=> el.fadeOut(250, ()=> el.remove()), 1600);
    }

    let cache = [];
    let showAll = $('#toggleAll').is(':checked');

    function fetchList(){
      $('#tbody').html('<tr><td colspan="7" class="p-4 text-center text-gray-500">Cargando...</td></tr>');
      $.getJSON(API, { action: 'list' })
        .done(r => { cache = Array.isArray(r.items) ? r.items : []; render(); })
        .fail(xhr => {
          console.error('LIST fail:', xhr.status, xhr.responseText);
          $('#tbody').html('<tr><td colspan="7" class="p-4 text-center text-red-600">Error cargando lista</td></tr>');
        });
    }

    function render(){
      const $tb = $('#tbody').empty();
      const items = showAll ? cache : cache.filter(i => +i.activo === 1);
      if (!items.length) {
        $tb.append('<tr><td colspan="7" class="p-4 text-center text-gray-500">Sin horarios</td></tr>');
        return;
      }
      items.forEach(it => {
        $tb.append(`
          <tr data-id="${it.id}" class="hover:bg-gray-50">
            <td class="px-3 py-2 text-center">
              <button class="up px-2 py-1 rounded bg-gray-200">↑</button>
              <button class="down px-2 py-1 rounded bg-gray-200 ml-1">↓</button>
            </td>
            <td class="px-3 py-2"><input type="text" class="tag w-40 border rounded px-2 py-1" value="${it.etiqueta ?? ''}"></td>
            <td class="px-3 py-2"><input type="time" class="ini w-32 border rounded px-2 py-1" value="${it.inicio}"></td>
            <td class="px-3 py-2"><input type="time" class="fin w-32 border rounded px-2 py-1" value="${it.fin}"></td>
            <td class="px-3 py-2 text-center">
              <label class="inline-flex items-center gap-2">
                <input type="checkbox" class="act" ${+it.activo === 1 ? 'checked' : ''}>
                <span class="text-xs ${+it.activo === 1 ? 'text-green-700' : 'text-gray-500'}">${+it.activo === 1 ? 'Activo' : 'Inactivo'}</span>
              </label>
            </td>
            <td class="px-3 py-2 text-center"><button class="save bg-indigo-600 text-white px-3 py-1 rounded">Guardar</button></td>
            <td class="px-3 py-2 text-center"><button class="del bg-red-600 text-white px-3 py-1 rounded">Eliminar</button></td>
          </tr>
        `);
      });
    }

    function post(action, data){
      return $.ajax({
        url: API,
        method: 'POST',
        data: Object.assign({ action }, data || {}),
        dataType: 'json'
      });
    }

    $('#formNew').off('.hor');
    $('#toggleAll').off('.hor');
    $('#tbody').off('.hor');

    $('#toggleAll').on('change.hor', function(){
      showAll = this.checked;
      render();
    });

    $('#formNew').on('submit.hor', e => {
      e.preventDefault();
      const inicio   = $('#nIni').val().trim();
      const fin      = $('#nFin').val().trim();
      const etiqueta = $('#nTag').val().trim();
      const activo   = $('#nAct').is(':checked') ? 1 : 0;

      post('create', { inicio, fin, etiqueta, activo })
        .done(() => { toast('Creado'); e.target.reset(); fetchList(); })
        .fail(xhr => { console.error('CREATE fail:', xhr.status, xhr.responseText); toast(xhr.responseJSON?.error || 'Error', false); });
    });

    $('#tbody').on('click.hor', '.save', function(){
      const $tr = $(this).closest('tr');
      const id       = $tr.data('id');
      const etiqueta = $tr.find('.tag').val().trim();
      const inicio   = $tr.find('.ini').val().trim();
      const fin      = $tr.find('.fin').val().trim();
      const activo   = $tr.find('.act').is(':checked') ? 1 : 0;

      post('update', { id, etiqueta, inicio, fin, activo })
        .done(() => { toast('Guardado'); fetchList(); })
        .fail(xhr => { console.error('UPDATE fail:', xhr.status, xhr.responseText); toast(xhr.responseJSON?.error || 'Error', false); });
    });

    $('#tbody').on('click.hor', '.del', function(){
      const id = $(this).closest('tr').data('id');
      if (!confirm('¿Eliminar este horario?')) return;
      post('delete', { id })
        .done(() => { toast('Eliminado'); fetchList(); })
        .fail(xhr => { console.error('DELETE fail:', xhr.status, xhr.responseText); toast('No se guardó orden', false); });
    });

    $('#tbody').on('click.hor', '.up,.down', function(){
      const $tr = $(this).closest('tr');
      if ($(this).hasClass('up'))  { $tr.prev().before($tr); } else { $tr.next().after($tr); }
      const ids = $('#tbody tr').map(function(){ return $(this).data('id'); }).get();
      post('reorder', { ids })
        .done(() => toast('Orden actualizado'))
        .fail(xhr => { console.error('REORDER fail:', xhr.status, xhr.responseText); toast('No se guardó orden', false); });
    });

    fetchList();
  };
})();
