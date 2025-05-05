function showForm() {
    const selected = document.querySelector('input[name="tipo_credencial"]:checked');
    if (selected) {
        document.getElementById("step1").style.display = "none";
        document.getElementById("step2").style.display = "block";
        document.getElementById("tipo_credencial_escondido").value = selected.value;

        // Mostra o campo de laudo médico se a opção for PCD
        document.getElementById("laudo_group").style.display = selected.value === "PCD" ? "block" : "none";
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("step2");

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch("processar.php", {
            method: "POST",
            body: formData,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Erro na requisição");
                }
                return response.text();
            })
            .then((data) => {
                if (data.toLowerCase().includes("erro")) {
                    window.location.href = "erro.html";
                } else {
                    window.location.href = "sucesso.html";
                }
            })
            .catch((error) => {
                console.error("Erro ao enviar requisição:", error);
                window.location.href = "erro.html";
            });
    });
});
