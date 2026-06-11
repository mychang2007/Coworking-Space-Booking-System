// Toggle Profile dropdown menu actions drawer
function toggleDropdown(event) {
  event.stopPropagation();
  document.getElementById('userMenu').classList.toggle('show');
}

// Global window event listener to close menu when clicking anywhere outside of it
window.addEventListener('click', () => {
  const menu = document.getElementById('userMenu');
  if (menu && menu.classList.contains('show')) {
    menu.classList.remove('show');
  }
});