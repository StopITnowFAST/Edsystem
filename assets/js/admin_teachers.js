let addTeacherPopup = document.getElementById("addTeacherPopup");

function openPopup() {
    addTeacherPopup.style.display = 'grid';
    let overlay = document.querySelector('.overlay');
    overlay.classList.add('overlay--active');
}

function closePopup() {
    addTeacherPopup.style.display = 'none';
    let overlay = document.querySelector('.overlay');
    overlay.classList.remove('overlay--active');
}