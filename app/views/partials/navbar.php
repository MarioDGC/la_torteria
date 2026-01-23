<!-- Top Navbar -->
<nav class="navbar">
    <button class="btn-menu" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="navbar-content">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar...">
        </div>

        <div class="navbar-right">
            <button class="btn-icon" id="notificationBtn">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </button>

            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=Admin+User&background=e67e22&color=fff"
                    alt="Usuario">
                <div class="user-details">
                    <span class="user-name"><?php //echo htmlspecialchars($userName); 
                                                    echo htmlspecialchars('Mario'); ?></span>
                    <span class="user-role"><?php //echo htmlspecialchars($userRole) 
                                                    echo htmlspecialchars('Administrador');?></span>
                </div>
            </div>
        </div>
    </div>
</nav>