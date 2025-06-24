const navbar = document.querySelector('nav');
if (navbar) {
    window.addEventListener('scroll', function () {
        if (window.scrollY > 1) {
            navbar.classList.replace('bg-transparent', 'nav-color');
        } else {
            navbar.classList.replace('nav-color', 'bg-transparent');
        }
    });
}