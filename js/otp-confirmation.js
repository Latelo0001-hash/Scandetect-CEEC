document.addEventListener('DOMContentLoaded', function () {
  var start = document.getElementById('validate-otp-start');
  var modal = document.getElementById('otp-modal');
  var form = document.getElementById('otp-form');
  var code = document.getElementById('otp-code');
  var message = document.getElementById('otp-message');
  var recipient = document.getElementById('otp-recipient');
  var confirmButton = form ? form.querySelector('.otp-confirm-button') : null;
  var resend = document.getElementById('otp-resend');
  var finalForm = document.getElementById('final-generation-form');
  var closeButtons = modal ? modal.querySelectorAll('[data-otp-close]') : [];
  var csrf = start ? (start.getAttribute('data-csrf') || '') : '';
  var requestUrl = start ? (start.getAttribute('data-request-url') || 'request-otp.php') : 'request-otp.php';
  var verifyUrl = start ? (start.getAttribute('data-verify-url') || 'verify-otp.php') : 'verify-otp.php';
  var resendTimer = null;

  if (!start || !modal || !form || !finalForm) return;

  function showModal() {
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('otp-modal-open');
    window.setTimeout(function () { if (code) code.focus(); }, 100);
  }

  function hideModal() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('otp-modal-open');
  }

  function setMessage(text, type) {
    if (!message) return;
    message.textContent = text || '';
    message.className = 'otp-message' + (type ? ' otp-message-' + type : '');
  }

  function countdownResend(seconds) {
    if (!resend) return;
    if (resendTimer) clearInterval(resendTimer);
    var remaining = seconds;
    resend.disabled = true;
    resend.textContent = 'Renvoyer le code (' + remaining + ' s)';
    resendTimer = setInterval(function () {
      remaining -= 1;
      if (remaining <= 0) {
        clearInterval(resendTimer);
        resendTimer = null;
        resend.disabled = false;
        resend.textContent = 'Renvoyer le code';
      } else {
        resend.textContent = 'Renvoyer le code (' + remaining + ' s)';
      }
    }, 1000);
  }

  function post(url, data) {
    return fetch(url, {
      method: 'POST',
      body: data,
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (payload) {
        if (!response.ok || !payload.ok) throw new Error(payload.message || 'Une erreur est survenue.');
        return payload;
      });
    });
  }

  function requestOtp() {
    var data = new FormData();
    data.append('csrf', csrf);
    if (confirmButton) confirmButton.disabled = true;
    if (resend) resend.disabled = true;
    if (recipient) recipient.textContent = 'Envoi du code en cours…';
    setMessage('', '');

    return post(requestUrl, data).then(function (payload) {
      if (recipient) recipient.textContent = 'Code envoyé à ' + (payload.masked_email || 'l’adresse configurée') + '.';
      if (payload.debug_otp) {
        setMessage('Mode local : code de test ' + payload.debug_otp, 'info');
      } else {
        setMessage('Le code reste valable pendant 10 minutes.', 'info');
      }
      if (confirmButton) confirmButton.disabled = false;
      countdownResend(60);
    }).catch(function (error) {
      if (recipient) recipient.textContent = 'L’envoi du code a échoué.';
      setMessage(error.message, 'error');
      if (resend) resend.disabled = false;
    });
  }

  start.addEventListener('click', function () {
    showModal();
    requestOtp();
  });

  Array.prototype.forEach.call(closeButtons, function (button) {
    button.addEventListener('click', hideModal);
  });

  if (resend) {
    resend.addEventListener('click', function () {
      requestOtp();
    });
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    var otp = code ? code.value.trim() : '';
    if (!/^\d{6}$/.test(otp)) {
      setMessage('Saisissez le code à 6 chiffres reçu par e-mail.', 'error');
      return;
    }

    var data = new FormData();
    data.append('csrf', csrf);
    data.append('otp', otp);
    if (confirmButton) confirmButton.disabled = true;
    setMessage('Vérification du code…', 'info');

    post(verifyUrl, data).then(function () {
      setMessage('Code confirmé. Génération du certificat…', 'success');
      window.setTimeout(function () {
        finalForm.submit();
      }, 350);
    }).catch(function (error) {
      setMessage(error.message, 'error');
      if (confirmButton) confirmButton.disabled = false;
      if (code) {
        code.select();
        code.focus();
      }
    });
  });
});
