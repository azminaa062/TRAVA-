<?php
session_start();
?>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top custom-navbar">

<div class="container">

<a class="navbar-brand fw-bold fs-3"
href="index.php">

TRAVA

</a>

<button class="navbar-toggler"
type="button"
data-bs-toggle="collapse"
data-bs-target="#navbarNav">

<span class="navbar-toggler-icon"></span>

</button>

<div class="collapse navbar-collapse"
id="navbarNav">

<ul class="navbar-nav ms-auto align-items-center">

<li class="nav-item">
<a class="nav-link" href="index.php">
Home
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="destinasi.php">
Destinasi
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="trip.php">
Trip Planner
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="wishlist.php">
Wishlist
</a>
</li>

<?php if(isset($_SESSION['login'])) : ?>

<li class="nav-item dropdown ms-3">

<a class="btn btn-primary rounded-pill px-4 dropdown-toggle"
data-bs-toggle="dropdown">

<?= $_SESSION['nama']; ?>

</a>

<ul class="dropdown-menu dropdown-menu-dark">

<li>
<a class="dropdown-item" href="profil.php">
Profil
</a>
</li>

<li>
<a class="dropdown-item" href="logout.php">
Logout
</a>
</li>

</ul>

</li>

<?php else : ?>

<li class="nav-item ms-3">

<a href="login.php"
class="btn btn-primary rounded-pill px-4">

Login

</a>

</li>

<?php endif; ?>

</ul>

</div>
</div>
</nav>