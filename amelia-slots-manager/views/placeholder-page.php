<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1>Configuración de Cliente Iniciador</h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('asm_placeholder_settings');
        $current_id = get_option($this->placeholder_customer_option, $this->default_placeholder_customer);
        error_log('Amelia Slots Manager - Current ID in form: ' . $current_id);
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="customer-search">Buscar Cliente</label>
                </th>
                <td>
                    <input 
                        type="text" 
                        id="customer-search" 
                        class="regular-text"
                        placeholder="Buscar por nombre o email..."
                    >
                    <div id="search-results" class="search-results" style="display: none;">
                        <ul></ul>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="<?php echo esc_attr($this->placeholder_customer_option); ?>">
                        Cliente Iniciador Seleccionado
                    </label>
                </th>
                <td>
                    <input 
                        type="hidden" 
                        id="<?php echo esc_attr($this->placeholder_customer_option); ?>"
                        name="<?php echo esc_attr($this->placeholder_customer_option); ?>"
                        value="<?php echo esc_attr($current_id); ?>"
                    >
                    <div id="selected-customer" class="selected-customer">
                        <?php
                        global $wpdb;
                        $customer = $wpdb->get_row($wpdb->prepare(
                            "SELECT firstName, lastName FROM {$wpdb->prefix}amelia_users WHERE id = %d AND type = 'customer'",
                            $current_id
                        ));
                        if ($customer) {
                            echo '<div class="customer-info">';
                            echo '<strong>Cliente Iniciador Actual:</strong><br>';
                            echo esc_html($customer->firstName . ' ' . $customer->lastName . ' (ID: ' . $current_id . ')');
                            echo '</div>';
                        } else {
                            echo '<div class="customer-info">';
                            echo '<em>No se ha encontrado el cliente iniciador (ID: ' . esc_html($current_id) . ')</em>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <p class="description">
                        ID actual: <?php echo esc_html($current_id); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>

<style>
.search-results {
    position: absolute;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.search-results ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.search-results li {
    padding: 12px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.search-results li:hover {
    background: #f5f5f5;
}

.search-results .customer-name {
    font-weight: 600;
    color: #23282d;
}

.search-results .customer-email {
    color: #666;
    font-size: 0.9em;
}

.search-results .customer-id {
    color: #999;
    font-size: 0.8em;
}

.selected-customer {
    margin-top: 10px;
    padding: 12px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.selected-customer .customer-name {
    font-weight: 600;
    color: #23282d;
}

.selected-customer .customer-email {
    color: #666;
    font-size: 0.9em;
}

.selected-customer .customer-id {
    color: #999;
    font-size: 0.8em;
}

.customer-info {
    padding: 8px;
    background: #fff;
    border-left: 4px solid #2271b1;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.customer-info strong {
    color: #1d2327;
    display: block;
    margin-bottom: 4px;
}

.customer-info em {
    color: #666;
}
</style>

<script>
jQuery(document).ready(function($) {
    let searchTimeout;
    const searchInput = $('#customer-search');
    const searchResults = $('#search-results');
    const resultsList = searchResults.find('ul');
    const selectedCustomer = $('#selected-customer');
    const customerIdInput = $('#<?php echo esc_js($this->placeholder_customer_option); ?>');

    function escapeHtml(unsafe) {
        // Convertir a string si no lo es
        unsafe = String(unsafe);
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatCustomerDisplay(customer) {
        const name = escapeHtml(customer.firstName + ' ' + customer.lastName);
        const id = escapeHtml(customer.id);
        return `
            <div class="customer-name">${name} (ID: ${id})</div>
        `;
    }

    searchInput.on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();

        if (searchTerm.length < 2) {
            searchResults.hide();
            return;
        }

        searchTimeout = setTimeout(function() {
            $.ajax({
                url: asmAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'search_amelia_customers',
                    search: searchTerm,
                    nonce: asmAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        resultsList.empty();
                        response.data.forEach(function(customer) {
                            const name = escapeHtml(customer.firstName + ' ' + customer.lastName);
                            const id = escapeHtml(customer.id);
                            resultsList.append(`
                                <li data-id="${id}" data-name="${name}">
                                    ${formatCustomerDisplay(customer)}
                                </li>
                            `);
                        });
                        searchResults.show();
                    }
                }
            });
        }, 300);
    });

    resultsList.on('click', 'li', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        customerIdInput.val(id);
        selectedCustomer.html(`
            <div class="customer-info">
                <strong>Cliente Iniciador Actual:</strong><br>
                ${name} (ID: ${id})
            </div>
            <p class="description">ID actual: ${id}</p>
        `);
        searchResults.hide();
        searchInput.val('');

        // Log para debug
        console.log('Setting customer ID to:', id);
        
        // Submit the form
        const form = customerIdInput.closest('form');
        form.submit();
    });

    // Debug para ver el valor del input
    customerIdInput.on('change', function() {
        console.log('Customer ID changed to:', $(this).val());
    });

    // Cerrar resultados al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customer-search, #search-results').length) {
            searchResults.hide();
        }
    });
});
</script> 