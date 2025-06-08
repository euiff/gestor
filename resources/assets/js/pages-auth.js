document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('formAuthentication');

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    // Limpar mensagens de erro anteriores
    const errorMessages = document.querySelectorAll('.text-danger');
    errorMessages.forEach(function (message) {
      message.remove();
    });

    let isValid = true;

    // Validação do número de WhatsApp
    const whatsappInput = document.getElementById('whatsapp');
    const whatsappValue = whatsappInput.value.trim();
    if (whatsappValue === '') {
      isValid = false;
      const errorMessage = document.createElement('span');
      errorMessage.classList.add('text-danger');
      errorMessage.textContent = 'Por favor, insira seu número de WhatsApp';
      whatsappInput.parentNode.appendChild(errorMessage);
    } else if (!/^\d{10,15}$/.test(whatsappValue)) {
      isValid = false;
      const errorMessage = document.createElement('span');
      errorMessage.classList.add('text-danger');
      errorMessage.textContent = 'Por favor, insira um número de WhatsApp válido';
      whatsappInput.parentNode.appendChild(errorMessage);
    }

    // Validação da senha
    const passwordInput = document.getElementById('password');
    const passwordValue = passwordInput.value.trim();
    if (passwordValue === '') {
      isValid = false;
      const errorMessage = document.createElement('span');
      errorMessage.classList.add('text-danger');
      errorMessage.textContent = 'Por favor, insira sua senha';
      passwordInput.parentNode.appendChild(errorMessage);
    } else if (passwordValue.length < 6) {
      isValid = false;
      const errorMessage = document.createElement('span');
      errorMessage.classList.add('text-danger');
      errorMessage.textContent = 'A senha deve ter pelo menos 6 caracteres';
      passwordInput.parentNode.appendChild(errorMessage);
    }

    if (isValid) {
      form.submit();
    }
  });
});
