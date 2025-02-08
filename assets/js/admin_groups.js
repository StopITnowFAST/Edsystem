let addGroupPopup = document.getElementById("addGroupPopup");

function openPopup() {
  addGroupPopup.style.display = 'grid';
    let overlay = document.querySelector('.overlay');
    overlay.classList.add('overlay--active');
}

function closePopup() {
  addGroupPopup.style.display = 'none';
    let overlay = document.querySelector('.overlay');
    overlay.classList.remove('overlay--active');
}