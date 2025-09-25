document.addEventListener('DOMContentLoaded', function() {
    const orderDetailsModal = new bootstrap.Modal(document.getElementById('order-details-modal'));

    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const orderId = button.dataset.id;
            const response = await fetch(`../api/get_order_details.php?id=${orderId}`);
            const data = await response.json();

            if (data.error) {
                alert(data.error);
                return;
            }

            const orderDate = new Date(data.details.created_at).toLocaleString('es-CO', {
                year: 'numeric', month: 'long', day: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: true
            });

            const currentPage = window.location.pathname.split('/').pop();

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
                <form id="order-details-form" action="${currentPage}" method="POST">
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
                            ${(data.details.status === 'completed' && data.details.note) ? `<p><strong>Nota de Despacho:</strong> ${data.details.note}</p>` : ''}
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="order-code" class="form-label">Código:</label>
                        <input type="text" id="order-code" name="code" class="form-control" value="${data.details.code || ''}">
                    </div>
                    <hr>
                    <h6>Productos del Pedido:</h6>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                ${data.details.status === 'completed' ? '<th>Despachado</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>
            `;

            data.items.forEach(item => {
                let nameCell = item.name;
                if (item.promotion_text) {
                    nameCell += `<br><small class="text-danger">${item.promotion_text}</small>`;
                }

                let rowHtml = `<td>${nameCell}</td><td>${item.quantity}</td>`;
                let rowClass = '';

                // Hacemos la comprobación del estado más robusta (insensible a mayúsculas/minúsculas y espacios)
                if (data.details.status && data.details.status.trim().toLowerCase() === 'completed') {
                    const isDispatched = parseInt(item.dispatched, 10) === 1;
                    rowClass = isDispatched ? '' : 'text-decoration-line-through';
                    const dispatchedText = isDispatched ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>';
                    rowHtml += `<td>${dispatchedText}</td>`;
                }
                
                contentHtml += `<tr class="${rowClass}">${rowHtml}</tr>`;
            });

            contentHtml += `
                        </tbody>
                    </table>
                    <hr>
                    <div class="d-flex justify-content-end">
                        ${(data.details.status !== 'completed' && data.details.status !== 'archived' && data.details.status !== 'cancelled') ? `
                            <button type="submit" name="action" value="save_details" class="btn btn-success me-2">Guardar Cambios</button>
                            <button type="submit" name="action" value="send_to_dispatch" class="btn btn-primary me-2">Enviar a despacho</button>
                            <button type="submit" name="action" value="cancel_order" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que quieres cancelar este pedido?');">Cancelar Pedido</button>
                        ` : ''}
                    </div>
                </form>
            `;

            document.getElementById('order-details-content').innerHTML = contentHtml;

            orderDetailsModal.show();
        });
    });
});