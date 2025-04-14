let addStudentPopup = document.getElementById("addStudentPopup");

function openPopup() {
    addStudentPopup.style.display = 'grid';
    let overlay = document.querySelector('.overlay');
    overlay.classList.add('overlay--active');
}

function closePopup() {
    addStudentPopup.style.display = 'none';
    let overlay = document.querySelector('.overlay');
    overlay.classList.remove('overlay--active');
}