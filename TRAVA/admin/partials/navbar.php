<?php if(session_status() == PHP_SESSION_NONE) session_start(); ?>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container">

        <!-- LOGO -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php" style="text-decoration:none;">
            <img src="assets/img/logo-trava.png" class="logo-navbar" style="height:36px;width:auto;object-fit:contain;">
        </a>

        <!-- TOGGLE MOBILE -->
        <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav">

            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- MENU -->
        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav ms-auto align-items-center">

                <li class="nav-item me-3">
                    <a href="index.php" class="nav-link text-white">
                        Home
                    </a>
                </li>

                <?php if(isset($_SESSION['user_id'])): ?>

                    <li class="nav-item me-3 text-white">
                        👋 Halo, <?= $_SESSION['nama']; ?>
                    </li>

                    <li class="nav-item me-2">
                        <a href="wishlist.php" class="btn btn-light btn-sm">
                            ❤️ Wishlist
                        </a>
                    </li>

                    <li class="nav-item me-2">
                        <a href="profil.php" class="btn btn-light btn-sm">
                            👤 Profil
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="logout.php" class="btn btn-danger btn-sm">
                            Logout
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a href="login.php" class="btn btn-light btn-sm px-4">
                            Login
                        </a>
                    </li>

                <?php endif; ?>

            </ul>

        </div>

    </div>
</nav>