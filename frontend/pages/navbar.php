<nav class="navbar bg-dark border-bottom border-body navbar-expand-lg bg-body-tertiary" data-bs-theme="dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="site?page=home">Home</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link active" href="?page=dashboard">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="?page=management">Management</a>
        </li>
        <li class="nav-item">
          <?php
            if(isset($_COOKIE["authToken"])) {
              echo '<a class="nav-link active" href="?page=logout">Logout</a>';
            } else {
              echo '<a class="nav-link active" href="?page=login">Login</a>';
              echo '<a class="nav-link active" href="?page=register">Register</a>';
            }
          ?>
        </li>
      </ul>
    </div>
  </div>
</nav>