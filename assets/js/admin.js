document.addEventListener('DOMContentLoaded', function() {
    const orderDetailsModal = new bootstrap.Modal(document.getElementById('order-details-modal'));
    let allProducts = [];

    async function fetchAllProducts() {
        if (allProducts.length === 0) {
            try {
                const response = await fetch('../api/get_products.php');
                allProducts = await response.json();
            } catch (error) {
                console.error('Error fetching products:', error);
            }
        }
    }

    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const orderId = button.dataset.id;
            const response = await fetch(`../api/get_order_details.php?id=${orderId}`);
            const data = await response.json();

            if (data.error) {
                alert(data.error);
                return;
            }

            await fetchAllProducts();

            renderModalContent(data, orderId);
            orderDetailsModal.show();
        });
    });

    function renderModalContent(data, orderId) {
        const orderDate = new Date(data.details.created_at).toLocaleString('es-CO', {
            year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: true
        });

        let customerType = data.details.customer_type;
        if (!customerType || customerType.trim() === '') {
            customerType = 'No especificado';
        } else {
            const typeTranslations = {
                'distribuidor_salsamentaria': 'Cliente Salsamentaria',
                'Mercaderista': 'Mercaderista'
            };
            customerType = typeTranslations[customerType] || customerType;
        }

        let contentHtml = `
            <form id="order-details-form">
                <input type="hidden" name="order_id" value="${orderId}">
                <div class="row">
                    <div class="col-md-8">
                        <h6>Cliente: ${data.details.customer_name}</h6>
                        <p><strong>Tipo:</strong> ${customerType}</p>
                        <p><strong>Ciudad:</strong> ${data.details.customer_city}</p>
                        <p><strong>ID:</strong> ${data.details.customer_id_number}</p>
                        ${data.details.customer_email ? `<p><strong>Email:</strong> ${data.details.customer_email}</p>` : ''}
                        ${data.details.mercaderista_supermarket ? `<p><strong>Supermercado:</strong> ${data.details.mercaderista_supermarket}</p>` : ''}
                    </div>
                    <div class="col-md-4 text-md-end">
                        <p><strong>Estado:</strong> <span class="badge bg-info">${data.details.status_translated}</span></p>
                        <p><strong>Fecha:</strong> ${orderDate}</p>
                        ${data.details.hora_envio_despacho ? `<p><strong>Despachado:</strong> ${new Date(data.details.hora_envio_despacho).toLocaleString('es-CO', { timeZone: 'America/Bogota', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })}</p>` : ''}
                        ${data.details.hora_completado ? `<p><strong>Hora completado:</strong> ${new Date(data.details.hora_completado).toLocaleString('es-CO', { timeZone: 'America/Bogota', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true })}</p>` : ''}
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <label for="order-code" class="form-label">Código:</label>
                    <input type="text" id="order-code" name="code" class="form-control" value="${data.details.code || ''}">
                </div>
                <hr>
                <h6>Productos del Pedido:</h6>
                <table class="table" id="order-items-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Código Barras</th>
                            <th>Código Interno</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        data.items.forEach(item => {
            contentHtml += `
                <tr data-item-id="${item.product_id}">
                    <td>${item.name}</td>
                    <td><input type="number" name="quantity[${item.product_id}]" value="${item.quantity}" class="form-control" min="1"></td>
                    <td>${item.codigo_barras || ''}</td>
                    <td>${item.codigo_interno || ''}</td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-item-btn" data-item-id="${item.product_id}">Eliminar</button></td>
                </tr>
            `;
        });

        contentHtml += `
                    </tbody>
                </table>
                <hr>
                <!-- Add Product -->
                <h6>Añadir Producto:</h6>
                <div class="row">
                    <div class="col-md-6">
                        <select id="product-select" class="form-select">
                            <option value="">Seleccionar producto...</option>
                            ${allProducts.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" id="new-product-quantity" class="form-control" placeholder="Cantidad" min="1">
                    </div>
                    <div class="col-md-3">
                        <button type="button" id="add-product-btn" class="btn btn-primary">Añadir</button>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-end">
                    <button type="submit" name="action" value="save_changes" class="btn btn-success me-2">Guardar Cambios</button>
                    <button type="submit" name="action" value="send_to_dispatch" class="btn btn-primary me-2">Enviar a despacho</button>
                    <button type="submit" name="action" value="cancel_order" class="btn btn-danger">Cancelar Pedido</button>
                </div>
            </form>
        `;

        document.getElementById('order-details-content').innerHTML = contentHtml;
        attachEventListeners(data.details.status);
    }

    function attachEventListeners(currentStatus) {
        document.getElementById('add-product-btn').addEventListener('click', () => {
            const productId = document.getElementById('product-select').value;
            const quantity = document.getElementById('new-product-quantity').value;
            const product = allProducts.find(p => p.id == productId);

            if (productId && quantity && product) {
                const tableBody = document.getElementById('order-items-table').querySelector('tbody');
                const newRow = \`
                    <tr data-item-id="${product.id}" data-new="true">
                        <td>\${product.name}</td>
                        <td><input type="number" name="quantity[\${product.id}]" value="\${quantity}" class="form-control" min="1"></td>
                        <td>\${product.codigo_barras || ''}</td>
                        <td>\${product.codigo_interno || ''}</td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-item-btn" data-item-id="\${product.id}">Eliminar</button></td>
                    </tr>
                \`;
                tableBody.innerHTML += newRow;
            }
        });

        document.getElementById('order-items-table').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item-btn')) {
                e.target.closest('tr').remove();
            }
        });

        document.getElementById('order-details-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const action = e.submitter.value;
            const orderId = this.querySelector('input[name="order_id"]').value;

            if (action === 'cancel_order') {
                if (!confirm('¿Estás seguro de que quieres cancelar este pedido?')) {
                    return;
                }
                updateOrderStatus(orderId, 'cancelled');
                return;
            }

            if (action === 'send_to_dispatch') {
                updateOrderStatus(orderId, 'enviado a despacho');
                return;
            }

            if (action === 'save_changes') {
                const items = [];
                this.querySelectorAll('#order-items-table tbody tr').forEach(row => {
                    const id = row.dataset.itemId;
                    const quantity = row.querySelector('input[name^="quantity"]').value;
                    items.push({ id, quantity });
                });
                const code = this.querySelector('#order-code').value;

                const response = await fetch('../api/update_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, items, code })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Pedido actualizado con éxito.');
                    orderDetailsModal.hide();
                    location.reload();
                } else {
                    alert('Error al actualizar el pedido.');
                }
            }
        });
    }

    async function updateOrderStatus(orderId, status) {
        const response = await fetch('../api/update_order_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, status: status })
        });

        const result = await response.json();
        if (result.success) {
            alert(\`Pedido \${status}.\`);
            orderDetailsModal.hide();
            location.reload();
        } else {
            alert(\`Error al cambiar el estado del pedido.\`);
        }
    }
});