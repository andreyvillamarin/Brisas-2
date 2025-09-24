    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.querySelector('.notification-bell');
    if (notificationBell) {
        const notificationCount = notificationBell.querySelector('.notification-count');
        const notificationSound = document.getElementById('notification-sound');
        
        // Usamos sessionStorage para persistir el último conteo a través de recargas de página
        let lastCount = sessionStorage.getItem('lastOrderCount') || 0;

        async function fetchNewOrderCount() {
            try {
                const response = await fetch('../api/get_new_order_count.php');
                const data = await response.json();
                const newCount = parseInt(data.count, 10);

                notificationCount.textContent = newCount;

                if (newCount > lastCount) {
                    sessionStorage.setItem('lastOrderCount', newCount);
                    notificationSound.play().catch(error => console.error("Error al reproducir sonido:", error));

                    // Si estamos en el dashboard de despacho y la función de refresco existe, la llamamos.
                    if (typeof window.refreshDespachoTable === 'function') {
                        window.refreshDespachoTable();
                    }
                } else {
                    // Si no hay nuevos pedidos, nos aseguramos que el sessionStorage esté actualizado
                    sessionStorage.setItem('lastOrderCount', newCount);
                }
                // Actualizamos la variable local para la próxima comprobación en 30s
                lastCount = newCount;
            } catch (error) {
                console.error('Error fetching new order count:', error);
            }
        }

        // Fetch count immediately on page load
        fetchNewOrderCount();

        // Then fetch every 30 seconds
        setInterval(fetchNewOrderCount, 30000);
    }
});
</script>
</body>
</html>