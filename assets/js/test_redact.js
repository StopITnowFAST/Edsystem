function submit(type) {
    form = document.getElementById("test");
    input = document.getElementById("actionType");
    input.value = type;
    if (form.reportValidity()) {
        form.submit();
    }
}