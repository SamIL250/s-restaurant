<div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border" aria-labelledby="navbarDropdownUser">
    <div class="card position-relative border-0">
        <div class="card-body p-0">
            <div class="text-center pt-4 pb-3">
                <div class="avatar avatar-xl ">
                    <img class="rounded-circle" src="src/assets/img/icons/profile.png" alt="" />
                </div>
                <h6 class="mt-2 text-body-emphasis"><?php echo $_SESSION['user_name']; ?></h6>
                <p class="text-body-secondary mb-0"><?php echo ucfirst($_SESSION['user_role']); ?></p>
            </div>

        </div>
        <div class="overflow-auto scrollbar" style="height: 10rem;">
            <ul class="nav d-flex flex-column mb-2 pb-1">
                <li class="nav-item"><a class="nav-link px-3 d-block" href="profile"> <span class="me-2 text-body align-bottom" data-feather="user"></span><span>Profile</span></a></li>
                <li class="nav-item"><a class="nav-link px-3 d-block" href="index"><span class="me-2 text-body align-bottom" data-feather="pie-chart"></span>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link px-3 d-block" href="#!"> <span class="me-2 text-body align-bottom" data-feather="settings"></span>Settings &amp; Privacy </a></li>
                <li class="nav-item"><a class="nav-link px-3 d-block" href="#!"> <span class="me-2 text-body align-bottom" data-feather="help-circle"></span>Help Center</a></li>
            </ul>
        </div>
        <div class="card-footer p-0 border-top border-translucent">
            <hr />
            <div class="px-3"> <a class="btn btn-phoenix-secondary d-flex flex-center w-100" href="./src/services/auth/signout.php"> <span class="me-2" data-feather="log-out"> </span>Sign out</a></div>
            <div class="my-2 text-center fw-bold fs-10 text-body-quaternary"><a class="text-body-quaternary me-1" href="#!">Privacy policy</a>&bull;<a class="text-body-quaternary mx-1" href="#!">Terms</a>&bull;<a class="text-body-quaternary ms-1" href="#!">Cookies</a></div>
        </div>
    </div>
</div>