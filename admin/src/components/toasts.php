<?php

if (isset($_SESSION['notification'])) {
    $success_notifications = [
        "Welcome back!",
        "New product created successfully.",
        "Product removed successfully"
    ];
    if (in_array($_SESSION['notification'],$success_notifications)) {
?>
        <script>
            Toastify({
                text: "<?= $_SESSION['notification'] ?>",
                className: "info",
                close: true,
                stopOnFocus: true,
                backgroundColor: "linear-gradient(to right, #0F172A, #1E293B)", // Dark blue gradient
                className: "custom-toast",
                stopOnFocus: true, // Prevents dismissing on hover
                style: {
                    borderRadius: "8px",
                    padding: "12px 16px",
                    color: "#fff",
                    fontSize: "14px",
                    fontWeight: "500",
                    boxShadow: "0px 4px 10px rgba(0, 0, 0, 0.15)",
                }
            }).showToast();
        </script>
    <?php
    } else {
    ?>
        <script>
            Toastify({
                text: "⚠️<?= $_SESSION['notification'] ?>",
                className: "info",
                close: true,
                stopOnFocus: true,
                backgroundColor: "linear-gradient(to right, #F97316, #EA580C)", // Dark blue gradient
                className: "warning-toast ",
                stopOnFocus: true, // Prevents dismissing on hover
                style: {
                    borderRadius: "8px",
                    padding: "12px 16px",
                    color: "#fff",
                    fontSize: "14px",
                    fontWeight: "500",
                    boxShadow: "0px 4px 10px rgba(0, 0, 0, 0.15)",
                    borderLeft: "5px solid #C2410C" // A left border for emphasis
                }
            }).showToast();
        </script>
<?php
    }

    unset($_SESSION['notification']);
}
?>