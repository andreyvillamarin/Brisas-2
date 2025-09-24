document.addEventListener('DOMContentLoaded', function() {
    const productsModal = new bootstrap.Modal(document.getElementById('products-modal'));
    const successModal = new bootstrap.Modal(document.getElementById('success-modal'));
    
    let tempCart = {}; // { productId: { name, quantity } }
    let mainCart = {};

    // Abrir modal de productos al hacer clic en categoría
    document.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('click', async () => {
            const categoryId = card.dataset.id;
            const categoryName = card.dataset.name;
            document.getElementById('products-modal-title').textContent = `Productos - ${categoryName}`;
            
            const response = await fetch(`api/get_products.php?category_id=${categoryId}`);
            const products = await response.json();
            
            const productsContainer = document.getElementById('products-container');
            productsContainer.innerHTML = ''; // Limpiar
            
            if (products.length > 0) {
                products.forEach(product => {
                    const currentQuantity = tempCart[product.id]?.quantity || 0;
                    productsContainer.innerHTML += `
                        <div class="col">
                            <div class="card product-card h-100">
                                <img src="${product.image_url || 'assets/img/placeholder.png'}" class="card-img-top" alt="${product.name}">
                                <div class="card-body text-center">
                                    <h6 class="card-title">${product.name}</h6>
                                    <div class="d-flex justify-content-center">
                                        <input type="number" class="form-control quantity-input" value="${currentQuantity}" min="0" data-id="${product.id}" data-name="${product.name}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                productsContainer.innerHTML = '<p class="text-center col-12">No hay productos en esta categoría.</p>';
            }
            productsModal.show();
        });
    });

    // Actualizar carrito temporal al cambiar cantidad en el modal
    document.getElementById('products-container').addEventListener('change', e => {
        if (e.target.classList.contains('quantity-input')) {
            const productId = e.target.dataset.id;
            const productName = e.target.dataset.name;
            const quantity = parseInt(e.target.value, 10);

            if (quantity > 0) {
                tempCart[productId] = { name: productName, quantity: quantity };
            } else {
                delete tempCart[productId];
            }
        }
    });

    // Botón "Agregar Productos al Pedido" del modal
    document.getElementById('add-to-cart-btn').addEventListener('click', () => {
        mainCart = { ...mainCart, ...tempCart };
        tempCart = {};
        renderMainCart();
        productsModal.hide();
    });

    // Renderizar la tabla principal del pedido
    function renderMainCart() {
        const container = document.getElementById('cart-items-container');
        if (Object.keys(mainCart).length === 0) {
            container.innerHTML = '<p class="text-center text-muted">Aún no has agregado productos.</p>';
            document.getElementById('submit-order-btn').disabled = true;
            return;
        }

        let tableHtml = `
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
        `;
        for (const [id, item] of Object.entries(mainCart)) {
            let nameCellHtml = item.name;
            if (item.promoDescription) {
                nameCellHtml += `<br><span class="badge bg-danger">${item.promoDescription}</span>`;
            }

            tableHtml += `
                <tr>
                    <td>${nameCellHtml}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-secondary edit-item-btn" data-id="${id}">Editar</button>
                        <button class="btn btn-sm btn-danger remove-item-btn" data-id="${id}">Eliminar</button>
                    </td>
                </tr>
            `;
        }
        tableHtml += '</tbody></table>';
        container.innerHTML = tableHtml;
        document.getElementById('submit-order-btn').disabled = false;
    }
    
    // Añadir promoción al carrito principal desde el listado
    const promoSection = document.getElementById('promociones');
    if (promoSection) {
        promoSection.addEventListener('click', e => {
            if (e.target.classList.contains('add-promo-btn')) {
                const button = e.target;
                const inputGroup = button.closest('.input-group');
                const input = inputGroup.querySelector('.quantity-input');
                
                const quantity = parseInt(input.value, 10);
                const min = parseInt(input.min, 10);
                const max = input.hasAttribute('max') ? parseInt(input.max, 10) : Infinity;
                const step = parseInt(input.step, 10) || 1;
                
                if (isNaN(quantity) || quantity < min) {
                    alert(`La cantidad mínima para esta promoción es ${min}.`);
                    input.value = min;
                    return;
                }
                if (quantity > max) {
                    alert(`La cantidad máxima para esta promoción es ${max}.`);
                    input.value = max;
                    return;
                }
                if (quantity % step !== 0) {
                    alert(`La cantidad para esta promoción debe ser un múltiplo de ${step}.`);
                    input.value = min; // Reset to the minimum valid value
                    return;
                }

                const productId = input.dataset.id;
                const productName = input.dataset.name;
                const promoDescription = inputGroup.dataset.promoDescription;

                if (quantity > 0) {
                    if (mainCart[productId]) {
                        // If promo already in cart, just add quantity. 
                        // The rules (min, step) are already stored.
                        mainCart[productId].quantity += quantity;
                    } else {
                        mainCart[productId] = { 
                            name: productName, 
                            quantity: quantity,
                            promoDescription: promoDescription,
                            min: min,
                            step: step
                        };
                    }
                    renderMainCart();
                    
                    // Feedback visual
                    button.textContent = '¡Agregado!';
                    button.classList.add('btn-success');
                    setTimeout(() => {
                        button.textContent = 'Agregar';
                        button.classList.remove('btn-success');
                        input.value = 1; // Reset quantity
                    }, 1500);
                }
            }
        });
    }

    // Delegación de eventos para botones de editar, guardar y eliminar
    document.getElementById('cart-items-container').addEventListener('click', e => {
        const target = e.target;
        const productId = target.dataset.id;

        if (!productId) return; // Ignore clicks on non-button areas

        if (target.classList.contains('remove-item-btn')) {
            delete mainCart[productId];
            renderMainCart();
        } else if (target.classList.contains('edit-item-btn')) {
            const row = target.closest('tr');
            const quantityCell = row.children[1];
            const item = mainCart[productId];
            
            const min = item.min || 1;
            const step = item.step || 1;

            quantityCell.innerHTML = `<input type="number" class="form-control form-control-sm quantity-edit-input" value="${item.quantity}" min="${min}" step="${step}">`;
            
            target.textContent = 'Guardar';
            target.classList.remove('edit-item-btn', 'btn-secondary');
            target.classList.add('save-item-btn', 'btn-success');
        } else if (target.classList.contains('save-item-btn')) {
            const row = target.closest('tr');
            const input = row.querySelector('.quantity-edit-input');
            let newQuantity = parseInt(input.value, 10);

            const min = parseInt(input.min, 10);
            const step = parseInt(input.step, 10);

            if (isNaN(newQuantity) || newQuantity < min) {
                alert(`La cantidad no puede ser menor que ${min}.`);
                renderMainCart(); // Re-render to cancel edit
                return;
            }

            if (newQuantity % step !== 0) {
                alert(`La cantidad debe ser un múltiplo de ${step}.`);
                renderMainCart(); // Re-render to cancel edit
                return;
            }
            
            mainCart[productId].quantity = newQuantity;
            renderMainCart(); // Re-renderizar el carrito para mostrar el cambio
        }
    });

    // --- Lógica para selección de tipo de cliente con botones ---

    const customerTypeButtonsContainer = document.getElementById('customer-type-buttons');
    const customerTypeInput = document.getElementById('customer_type');
    const dynamicFieldsContainer = document.getElementById('dynamic-fields-container');

    async function updateCustomerFields(type) {
        let fieldsHtml = '';
        dynamicFieldsContainer.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'; // Show a spinner

        if (type === 'Cliente Salsamentaria') {
            fieldsHtml = `
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Nombre del cliente o establecimiento <span class="text-danger">*</span></label><input type="text" name="customer_name" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Cédula o NIT <span class="text-danger">*</span></label><input type="text" name="customer_id_number" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Ciudad <span class="text-danger">*</span></label><input type="text" name="customer_city" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Correo electrónico</label><input type="email" name="customer_email" class="form-control"></div>
                </div>
            `;
        } else if (type === 'Mercaderista') {
            let establishmentOptions = '<option value="">Selecciona un establecimiento...</option>';
            try {
                const response = await fetch('api/get_establishments.php');
                if (!response.ok) throw new Error('Network response was not ok');
                const establishments = await response.json();
                
                if (establishments.length > 0) {
                    establishments.forEach(est => {
                        establishmentOptions += `<option value="${est.name}">${est.name}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error fetching establishments:', error);
                establishmentOptions = '<option value="">Error al cargar establecimientos</option>';
            }

            fieldsHtml = `
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">Nombre del mercaderista <span class="text-danger">*</span></label><input type="text" name="mercaderista_name" class="form-control" required></div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Establecimiento o supermercado <span class="text-danger">*</span></label>
                        <select name="mercaderista_supermarket" class="form-select" required>
                            ${establishmentOptions}
                        </select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Ciudad <span class="text-danger">*</span></label><input type="text" name="customer_city" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Correo electrónico</label><input type="email" name="customer_email" class="form-control"></div>
                </div>
            `;
        }
        dynamicFieldsContainer.innerHTML = fieldsHtml;
    }

    if (customerTypeButtonsContainer) {
        customerTypeButtonsContainer.addEventListener('click', (e) => {
            const button = e.target.closest('button');
            if (!button) return;

            const type = button.dataset.type;
            if (!type) return;

            // Update hidden input
            customerTypeInput.value = type;

            // Update button styles
            const allButtons = customerTypeButtonsContainer.querySelectorAll('button');
            allButtons.forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
            });
            button.classList.add('btn-primary');
            button.classList.remove('btn-outline-primary');

            // Update fields
            updateCustomerFields(type);
        });

        // Set a default selection on page load
        const defaultButton = customerTypeButtonsContainer.querySelector('button');
        if (defaultButton) {
            defaultButton.click();
        }
    }

    // Enviar el formulario
    document.getElementById('order-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const recaptchaKey = form.dataset.recaptchaKey;

        if (!recaptchaKey) {
            alert('Error de configuración: La clave de reCAPTCHA no está disponible.');
            return;
        }

        grecaptcha.ready(function() {
            grecaptcha.execute(recaptchaKey, { action: 'submit' }).then(async function(token) {
                
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                data.cart = mainCart;
                data.recaptcha_token = token;

                const submitBtn = document.getElementById('submit-order-btn');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Enviando...';

                try {
                    const response = await fetch('api/submit_order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();

                    if (result.success) {
                        mainCart = {};
                        form.reset();
                        document.getElementById('dynamic-fields-container').innerHTML = '';
                        renderMainCart();
                        successModal.show();
                    } else {
                        alert('Hubo un error al enviar el pedido: ' + result.message);
                    }
                } catch (error) {
                    alert('Hubo un error de conexión. Por favor, inténtalo de nuevo.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Enviar Pedido';
                }
            });
        });
    });
});