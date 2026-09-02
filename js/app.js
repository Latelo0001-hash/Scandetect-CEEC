document.addEventListener('DOMContentLoaded', function () {
  var passwordToggles = Array.prototype.slice.call(document.querySelectorAll('[data-password-toggle]'));
  passwordToggles.forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      var targetId = toggle.getAttribute('data-password-toggle');
      var input = document.getElementById(targetId);
      if (!input) return;
      var visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      toggle.setAttribute('aria-pressed', visible ? 'false' : 'true');
      toggle.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
      var label = toggle.querySelector('.password-toggle-label');
      if (label) label.textContent = visible ? 'Afficher' : 'Masquer';
      input.focus();
    });
  });

  var form = document.getElementById('certificate-form');
  if (!form) return;

  var track = document.querySelector('.progress-track span');
  var numberSelect = document.getElementById('certificate_number');
  var details = document.getElementById('certificate-details');
  var recordToken = document.getElementById('record_token');
  var statusBox = document.getElementById('selected-certificate-status');
  var completedNote = document.getElementById('completed-certificate-note');
  var completedLink = document.getElementById('completed-certificate-link');
  var reviewButton = document.getElementById('review-certificate-button');
  var endpoint = form.getAttribute('data-certificate-data-url') || 'certificate-data.php';
  var selfUrl = form.getAttribute('data-self-url') || 'certificat.php';
  var initialNumber = form.getAttribute('data-initial-number') || '';
  var initialLoaded = form.getAttribute('data-initial-loaded') === '1';
  var initialStatus = form.getAttribute('data-initial-status') || 'red';
  var initialStatusLabel = form.getAttribute('data-initial-status-label') || 'Non imprimé';
  var initialLocked = form.getAttribute('data-initial-locked') === '1';
  var initialViewUrl = form.getAttribute('data-initial-view-url') || '';
  var detailFields = Array.prototype.slice.call(form.querySelectorAll('.certificate-detail-field'));
  var fields = Array.prototype.slice.call(form.querySelectorAll('[required]'));

  function updateProgress() {
    if (!track) return;
    var enabledFields = fields.filter(function (field) { return !field.disabled; });
    var completed = enabledFields.filter(function (field) { return String(field.value || '').trim() !== ''; }).length;
    track.style.width = (enabledFields.length ? Math.round((completed / enabledFields.length) * 100) : 100) + '%';
  }

  function setStatus(status, label) {
    if (!statusBox) return;
    statusBox.hidden = false;
    statusBox.className = 'selected-certificate-status status-' + status;
    statusBox.textContent = '● ' + label;
  }

  function setFieldLock(locked) {
    detailFields.forEach(function (field) {
      if (field.tagName === 'SELECT') {
        field.disabled = !!locked;
      } else {
        field.readOnly = !!locked;
      }
      field.classList.toggle('field-readonly', !!locked);
    });
    if (reviewButton) reviewButton.disabled = !!locked;
    if (completedNote) completedNote.hidden = !locked;
  }

  function clearDetails() {
    detailFields.forEach(function (field) {
      field.value = '';
      field.readOnly = false;
      field.disabled = false;
      field.classList.remove('field-readonly');
    });
    if (recordToken) recordToken.value = '';
    if (completedLink) {
      completedLink.hidden = true;
      completedLink.href = '#';
    }
    setFieldLock(false);
  }

  function fillDetails(data, locked) {
    detailFields.forEach(function (field) {
      if (Object.prototype.hasOwnProperty.call(data, field.name)) {
        var value = data[field.name] == null ? '' : String(data[field.name]);
        if (!locked && (field.name === 'ceec_representative' || field.name === 'mines_representative')) {
          value = '';
        }
        if (field.classList.contains('money-input')) {
          value = value.replace(/\s*\$\s*/g, '').trim();
        }
        field.value = value;
      } else {
        field.value = '';
      }
    });
  }

  function serverFallback(number) {
    var separator = selfUrl.indexOf('?') === -1 ? '?' : '&';
    window.location.href = selfUrl + separator + 'number=' + encodeURIComponent(number);
  }

  function loadCertificate(number) {
    if (!number) {
      if (details) details.hidden = true;
      clearDetails();
      if (statusBox) statusBox.hidden = true;
      updateProgress();
      return;
    }

    if (details) details.hidden = false;
    clearDetails();
    setStatus('red', 'Chargement des informations…');

    fetch(endpoint + '?number=' + encodeURIComponent(number), {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      cache: 'no-store'
    })
      .then(function (response) {
        if (!response.ok) throw new Error('Impossible de charger ce certificat.');
        var contentType = response.headers.get('content-type') || '';
        if (contentType.indexOf('application/json') === -1) {
          throw new Error('Le serveur n’a pas retourné les données attendues.');
        }
        return response.json();
      })
      .then(function (payload) {
        if (!payload || !payload.ok) throw new Error((payload && payload.message) || 'Réponse invalide.');
        fillDetails(payload.data || {}, !!payload.locked);
        if (recordToken) recordToken.value = payload.token || '';
        setStatus(payload.status || 'red', payload.status_label || 'Non imprimé');
        setFieldLock(!!payload.locked);

        if (completedLink && payload.certificate_url) {
          completedLink.href = payload.certificate_url;
          completedLink.hidden = false;
        }
        updateProgress();
      })
      .catch(function (error) {
        console.error(error);
        // Fallback production : recharger le même certificat côté PHP.
        // Cela évite une page vide si un proxy/WAF bloque fetch/AJAX.
        serverFallback(number);
      });
  }

  fields.forEach(function (field) {
    field.addEventListener('invalid', function () {
      if (field.validity.valueMissing) field.setCustomValidity('Remplissez ce champ');
    });
    field.addEventListener('input', function () {
      field.setCustomValidity('');
      updateProgress();
    });
    field.addEventListener('change', function () {
      field.setCustomValidity('');
      updateProgress();
    });
  });

  if (numberSelect) {
    numberSelect.addEventListener('change', function () {
      loadCertificate(numberSelect.value.trim());
    });

    if (numberSelect.value.trim() !== '') {
      if (initialLoaded && numberSelect.value.trim() === initialNumber) {
        // Les données sont déjà présentes dans le HTML rendu par PHP.
        setStatus(initialStatus, initialStatusLabel);
        setFieldLock(initialLocked);
        if (completedLink && initialViewUrl) {
          completedLink.href = initialViewUrl;
          completedLink.hidden = false;
        }
      } else {
        loadCertificate(numberSelect.value.trim());
      }
    }
  }

  updateProgress();
});
