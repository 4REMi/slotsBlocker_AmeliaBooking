<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Bloqueos de Horarios', 'slots-manager'); ?></h1>
    
    <div class="asm-container">
        <!-- Formulario para agregar bloqueos -->
        <div class="asm-add-block">
            <h2><?php esc_html_e('Agregar Nuevo Bloqueo', 'slots-manager'); ?></h2>
            <div class="asm-form-row">
                <label for="block_time">
                    <?php esc_html_e('Horario a Bloquear:', 'slots-manager'); ?>
                </label>
                <select id="block_time" class="regular-text">
                    <?php foreach ($time_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>">
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="asm-form-row">
                <label for="block_date">
                    <?php esc_html_e('Fecha:', 'slots-manager'); ?>
                </label>
                <input 
                    type="text" 
                    id="block_date" 
                    class="regular-text"
                    placeholder="Seleccionar fecha"
                    autocomplete="off"
                >
            </div>

            <div class="asm-form-row">
                <label for="block_reason">
                    <?php esc_html_e('Razón (opcional):', 'slots-manager'); ?>
                </label>
                <input 
                    type="text" 
                    id="block_reason" 
                    class="regular-text"
                    placeholder="Motivo del bloqueo"
                >
            </div>

            <div class="asm-form-row">
                <button type="button" id="add_block" class="button button-primary">
                    <?php esc_html_e('Agregar Bloqueo', 'slots-manager'); ?>
                </button>
            </div>
        </div>

        <!-- Tabla de bloqueos activos -->
        <div class="asm-blocks-list">
            <h2><?php esc_html_e('Bloqueos Activos', 'slots-manager'); ?></h2>
            
            <?php if (empty($blocks)) : ?>
                <p class="asm-no-blocks">
                    <?php esc_html_e('No hay bloqueos activos.', 'slots-manager'); ?>
                </p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Horario', 'slots-manager'); ?></th>
                            <th><?php esc_html_e('Fecha', 'slots-manager'); ?></th>
                            <th><?php esc_html_e('Razón', 'slots-manager'); ?></th>
                            <th><?php esc_html_e('Creado por', 'slots-manager'); ?></th>
                            <th><?php esc_html_e('Acciones', 'slots-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocks as $block) : ?>
                            <tr>
                                <td><?php echo esc_html($block->slot_time); ?></td>
                                <td><?php echo esc_html(date_i18n('j F, Y', strtotime($block->block_date))); ?></td>
                                <td><?php echo esc_html($block->reason ?: '—'); ?></td>
                                <td><?php echo esc_html(get_user_by('id', $block->created_by)->display_name); ?></td>
                                <td>
                                    <button 
                                        type="button" 
                                        class="button button-small remove-block" 
                                        data-id="<?php echo esc_attr($block->id); ?>"
                                    >
                                        <?php esc_html_e('Eliminar', 'slots-manager'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.asm-container {
    margin-top: 20px;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.asm-add-block {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.asm-form-row {
    margin: 15px 0;
}

.asm-form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.asm-no-blocks {
    padding: 20px;
    background: #f8f8f8;
    border-left: 4px solid #646970;
}

.asm-blocks-list {
    margin-top: 30px;
}

/* Datepicker customization */
.ui-datepicker {
    z-index: 999999 !important;
}
</style> 