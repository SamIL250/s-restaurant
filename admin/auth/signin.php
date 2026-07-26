<?php
session_start();
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Resto Restaurant | Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Permanent+Marker&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Sriracha&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        * {
            font-family: "Poppins";
        }
    </style>
</head>

<body class="bg-orange-50 min-vh-100 flex justify-center items-center p-5 lg:p-20">
    <?php
    if (isset($_SESSION['notification'])) {
    ?>
        <script>
            Toastify({
                text: "⚠️<?= $_SESSION['notification'] ?>",
                className: "info",
                close: true,
                stopOnFocus: true,
                backgroundColor: "linear-gradient(to right, #F97316, #EA580C)", // Orange gradient for Smart Resto theme
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
    ?>
    <div class="sm:w-[100%] lg:w-[70%] bg-white rounded-md border-1 border-gray-300 shadow-sm grid grid-cols-1  lg:grid-cols-2">
        <div class="border-r-1 border-gray-200 p-10">
            <div class="flex justify-center">
                <div class="flex items-center gap-2">
                    <div class="w-[60px] h-[60px]   rounded-full flex items-center justify-center">
                        <img src="../src/assets/img/icons/logo.png" alt="logo" width="150px" srcset="">
                    </div>
                    <p class="text-xl font-bold text-orange-600">Smart Resto Restaurant</p>
                </div>
            </div>
            <div class="py-20 text-center">
                <p class="text-gray-600 font-bold text-lg">Welcome to Smart Resto Restaurant Management System</p>
                <p class="text-gray-500 mt-2">Manage your inventory, orders, and restaurant operations</p>
            </div>
            <div class="text-center text-gray-600 text-sm">
                <p>Access the admin portal to manage your restaurant's daily operations, inventory, and customer orders.</p>
            </div>
            <div class="my-10 text-center">
                <button class="text-orange-600 font-bold py-2 px-6 border-2 border-orange-300 shadow-sm rounded-md cursor-pointer hover:bg-orange-50 transition-colors">Contact Support</button>
            </div>
        </div>
        <div class="py-10 px-10 lg:px-20">
            <div class="pb-3">
                <p class="text-lg text-gray-700 font-semibold">Staff Sign In</p>
            </div>
            <div><small class="text-gray-600">Sign in with your staff credentials to continue</small></div>
            <form action="../src/services/auth/signin.php" method="POST" class="py-10 grid gap-4">
                <div>
                    <input type="email" name="email" class="border-1 border-gray-300 py-3 px-4 w-[100%] rounded-md outline-none focus:border-orange-400 text-sm" placeholder="Enter your email" required>
                </div>
                <div>
                    <input type="password" name="password" class="border-1 border-gray-300 py-3 px-4 w-[100%] rounded-md outline-none focus:border-orange-400 text-sm" placeholder="Enter your password" required>
                </div>

                <div>
                    <p class="text-[12px]">Can't access your account? <a href="" class="border-b border-gray-300 text-orange-500 hover:text-orange-600">Contact your manager.</a></p>
                </div>
                <div class="mt-5">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-6 border-1 border-orange-500 shadow-sm rounded-md cursor-pointer transition-colors w-full">Sign In</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>