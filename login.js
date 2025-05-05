document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const form = new FormData(this);

    fetch('admin.php', {
        method: 'POST',
        body: form
    }).then(res => res.json())
    .then(data => {
        if (data.success) {
            localStorage.setItem('admin_logado', '1');
            window.location.href = 'admin.html';
        } else {
            document.getElementById('erroLogin').style.display = 'block';
        }
    });
});
