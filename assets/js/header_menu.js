const headerOverlay = document.getElementById("header_overlay");
const allMenu = Array.from(document.querySelectorAll('[id^="header_sub_menu_"]'));

function openHeaderMenu(id) {    
    // Сначала закрываем все открытые меню
    closeAllMenus();
    
    const subMenu = document.getElementById(`header_sub_menu_${id}`);
    if (!subMenu) return;
    
    headerOverlay.classList.add('header__overlay--active');
    subMenu.style.display = "block";
    
    setTimeout(() => {
        subMenu.classList.add('header__sub-menu--active');
    }, 10);
    
    document.body.style.overflow = "hidden";
}

function closeHeaderMenu(id) {
    if (id === 'all') {
        closeAllMenus();
        return;
    }
    
    const subMenu = document.getElementById(`header_sub_menu_${id}`);
    if (!subMenu) return;
    
    subMenu.classList.remove('header__sub-menu--active');
    
    setTimeout(() => {
        subMenu.style.display = "none";
        
        // Проверяем, остались ли открытые меню
        const hasActiveMenus = allMenu.some(menu => 
            menu.classList.contains('header__sub-menu--active')
        );
        
        if (!hasActiveMenus) {
            headerOverlay.classList.remove('header__overlay--active');
            document.body.style.overflow = "";
        }
    }, 300);
}

function closeAllMenus() {
    allMenu.forEach(subMenu => {
        subMenu.classList.remove('header__sub-menu--active');
        subMenu.style.display = "none";
    });
    
    headerOverlay.classList.remove('header__overlay--active');
    document.body.style.overflow = "";
}

// Обработчики событий
headerOverlay.addEventListener('click', () => closeHeaderMenu('all'));

allMenu.forEach(subMenu => {
    subMenu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.header__menu-group') && 
        !e.target.closest('[id^="header_sub_menu_"]')) {
        closeHeaderMenu('all');
    }
});

document.querySelectorAll('.header__menu-group').forEach(button => {
    button.addEventListener('click', function(e) {
        e.stopPropagation();
        const menuId = this.dataset.menuId;
        if (menuId) {
            openHeaderMenu(menuId);
        }
    });
});