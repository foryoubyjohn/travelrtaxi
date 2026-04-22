    </div><!-- .admin-content -->
</main><!-- .admin-main -->

<script>
// Sidebar toggle
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('adminSidebar').classList.toggle('collapsed');
    document.querySelector('.admin-main').classList.toggle('expanded');
});

// Auto-dismiss alerts
document.querySelectorAll('.alert').forEach(function(el) {
    setTimeout(function() { el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 300); }, 5000);
});

// Confirm delete
document.querySelectorAll('.btn-delete').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>
