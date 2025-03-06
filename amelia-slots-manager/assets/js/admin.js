jQuery(document).ready(function($) {
    const toggleButton = $('#toggle-conditional');
    const conditionalFields = $('#conditional-fields');
    const enabledInput = $('#asm_conditional_enabled');
    const conditionalInputs = $('#conditional-fields input, #conditional-fields select');

    if (toggleButton.length) {
        toggleButton.on('click', function() {
            const isEnabled = toggleButton.attr('data-enabled') === 'true';
            const newState = !isEnabled;
            
            // Actualizar estado visual
            toggleButton.attr('data-enabled', newState);
            toggleButton.text(newState ? 'Quitar Condicional' : 'Agregar Condicional');
            conditionalFields.toggleClass('hidden', !newState);
            
            // Actualizar estado de los campos
            conditionalInputs.prop('disabled', !newState);
            enabledInput.val(newState ? '1' : '0');

            // Guardar estado en la base de datos
            $.post(slotsManagerAdmin.ajaxurl, {
                action: 'toggle_conditional',
                nonce: slotsManagerAdmin.nonce,
                enabled: newState
            });
        });

        // Inicializar estado
        const initialState = toggleButton.attr('data-enabled') === 'true';
        toggleButton.text(initialState ? 'Quitar Condicional' : 'Agregar Condicional');
    }

    // Código para la página de bloqueos
    const blockDate = $('#block_date');
    if (blockDate.length) {
        console.log('Inicializando datepicker...'); // Debug
        
        // Inicializar datepicker
        try {
            blockDate.datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                firstDay: 1,
                monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
                dayNamesMin: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
                onSelect: function(dateText) {
                    console.log('Fecha seleccionada:', dateText); // Debug
                }
            });
            console.log('Datepicker inicializado correctamente'); // Debug
        } catch (error) {
            console.error('Error al inicializar datepicker:', error); // Debug
        }

        // Agregar bloqueo
        $('#add_block').on('click', function() {
            const time = $('#block_time').val();
            const date = blockDate.val();
            const reason = $('#block_reason').val();

            console.log('Intentando agregar bloqueo:', { time, date, reason }); // Debug

            if (!time || !date) {
                alert('Por favor selecciona un horario y una fecha.');
                return;
            }

            // Deshabilitar el botón mientras se procesa
            const $button = $(this);
            $button.prop('disabled', true).text('Procesando...');

            $.post({
                url: slotsManagerAdmin.ajaxurl,
                data: {
                    action: 'add_blocked_slot',
                    nonce: slotsManagerAdmin.nonce,
                    time: time,
                    date: date,
                    reason: reason
                },
                success: function(response) {
                    console.log('Respuesta del servidor:', response); // Debug
                    if (response.success) {
                        alert(response.data.message || 'Bloqueo agregado exitosamente');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error al agregar el bloqueo.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error en la petición:', { xhr, status, error }); // Debug
                    alert('Error al procesar la solicitud: ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Agregar Bloqueo');
                }
            });
        });

        // Eliminar bloqueo
        $('.remove-block').on('click', function() {
            if (!confirm('¿Estás seguro de que deseas eliminar este bloqueo?')) {
                return;
            }

            const button = $(this);
            const id = button.data('id');

            // Deshabilitar el botón mientras se procesa
            button.prop('disabled', true);

            $.post({
                url: slotsManagerAdmin.ajaxurl,
                data: {
                    action: 'remove_blocked_slot',
                    nonce: slotsManagerAdmin.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('tr').fadeOut(400, function() {
                            $(this).remove();
                            if ($('.remove-block').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        alert(response.data.message || 'Error al eliminar el bloqueo.');
                        button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error en la petición:', { xhr, status, error });
                    alert('Error al procesar la solicitud: ' + error);
                    button.prop('disabled', false);
                }
            });
        });
    }
}); 