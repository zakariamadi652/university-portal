<?php
/* footer.php — Pied de page commun */
?>
    </main><!-- /.main-content -->
    </div><!-- /.app-layout -->

    <script>
    // Fermer les alertes automatiquement après 4 secondes
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 300);
        }, 4000);
    });
    </script>
</body>
</html>
