<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Configuración de Horarios de Madrugada', 'slots-manager'); ?></h1>
    
    <div class="asm-container">
        <form method="post" action="options.php">
            <?php settings_fields('asm_morning_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr($this->hours_option); ?>">
                            <?php esc_html_e('Tiempo Mínimo de Anticipación', 'slots-manager'); ?>
                        </label>
                    </th>
                    <td>
                        <input 
                            type="number" 
                            id="<?php echo esc_attr($this->hours_option); ?>"
                            name="<?php echo esc_attr($this->hours_option); ?>"
                            value="<?php echo esc_attr($minimum_hours); ?>"
                            min="1"
                            max="24"
                            class="small-text"
                        >
                        <span class="description"><?php esc_html_e('horas', 'slots-manager'); ?></span>
                        <p class="description">
                            <?php esc_html_e('Número de horas mínimas requeridas antes de una reserva para el horario de madrugada del día siguiente. Se agregan automáticamente 2 minutos al tiempo configurado.', 'slots-manager'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr($this->time_option); ?>">
                            <?php esc_html_e('Horario de Madrugada', 'slots-manager'); ?>
                        </label>
                    </th>
                    <td>
                        <select 
                            id="<?php echo esc_attr($this->time_option); ?>"
                            name="<?php echo esc_attr($this->time_option); ?>"
                            class="regular-text"
                        >
                            <?php foreach ($time_options as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($target_time, $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Selecciona el horario de madrugada que deseas gestionar. Todos los horarios son con 10 minutos pasados de la hora.', 'slots-manager'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr($this->minimum_minutes_option); ?>">
                            Minutos mínimos antes de la cita
                        </label>
                    </th>
                    <td>
                        <input 
                            type="number" 
                            id="<?php echo esc_attr($this->minimum_minutes_option); ?>"
                            name="<?php echo esc_attr($this->minimum_minutes_option); ?>"
                            value="<?php echo esc_attr(get_option($this->minimum_minutes_option, $this->default_minimum_minutes)); ?>"
                            min="1"
                            max="120"
                            class="small-text"
                        >
                        <p class="description">
                            Tiempo mínimo requerido (en minutos) entre la hora actual y la cita más próxima.
                        </p>
                    </td>
                </tr>
            </table>

            <div class="asm-conditional-toggle">
                <button type="button" class="button button-secondary" id="toggle-conditional" data-enabled="<?php echo $conditional_enabled ? 'true' : 'false'; ?>">
                    <?php esc_html_e('Agregar Condicional', 'slots-manager'); ?>
                </button>
            </div>

            <div id="conditional-fields" class="<?php echo $conditional_enabled ? '' : 'hidden'; ?>">
                <input 
                    type="hidden" 
                    name="<?php echo esc_attr($this->conditional_enabled); ?>"
                    id="<?php echo esc_attr($this->conditional_enabled); ?>"
                    value="<?php echo $conditional_enabled ? '1' : '0'; ?>"
                >
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->hours_option_2); ?>">
                                <?php esc_html_e('Tiempo Mínimo de Anticipación (2)', 'slots-manager'); ?>
                            </label>
                        </th>
                        <td>
                            <input 
                                type="number" 
                                id="<?php echo esc_attr($this->hours_option_2); ?>"
                                name="<?php echo esc_attr($this->hours_option_2); ?>"
                                value="<?php echo esc_attr($minimum_hours_2); ?>"
                                min="1"
                                max="24"
                                class="small-text"
                                <?php echo !$conditional_enabled ? 'disabled' : ''; ?>
                            >
                            <span class="description"><?php esc_html_e('horas', 'slots-manager'); ?></span>
                            <p class="description">
                                <?php esc_html_e('Número de horas mínimas requeridas para el segundo horario de madrugada.', 'slots-manager'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->time_option_2); ?>">
                                <?php esc_html_e('Horario de Madrugada (2)', 'slots-manager'); ?>
                            </label>
                        </th>
                        <td>
                            <select 
                                id="<?php echo esc_attr($this->time_option_2); ?>"
                                name="<?php echo esc_attr($this->time_option_2); ?>"
                                class="regular-text"
                                <?php echo !$conditional_enabled ? 'disabled' : ''; ?>
                            >
                                <?php foreach ($time_options_2 as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($target_time_2, $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Selecciona el segundo horario de madrugada a gestionar.', 'slots-manager'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Guardar Cambios'); ?>
        </form>
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

.asm-container .form-table th {
    width: 300px;
}

.asm-container .description {
    margin-top: 8px;
    color: #666;
}

.asm-container select {
    min-width: 200px;
}

.asm-conditional-toggle {
    margin: 20px 0;
    padding: 10px 0;
    border-top: 1px solid #eee;
}

.hidden {
    display: none;
}

#conditional-fields {
    padding-top: 20px;
    border-top: 1px solid #eee;
}

#toggle-conditional {
    padding: 4px 12px;
}

#toggle-conditional[data-enabled="true"] {
    background: #dc3232;
    border-color: #dc3232;
    color: #fff;
}
</style> 