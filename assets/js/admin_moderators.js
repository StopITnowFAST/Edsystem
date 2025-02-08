const URL = '/request/change/role';
var checkboxes = document.querySelectorAll("input[type=checkbox]");

checkboxes.forEach(function(checkbox) {
  checkbox.addEventListener('change', async function() {    
    let response = await fetch(URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({id: this.value})
    });
    if (!response.ok) { 
      alert("Ошибка HTTP: " + response.status);
    } 
  })
});