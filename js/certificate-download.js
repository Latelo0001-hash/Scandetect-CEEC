document.addEventListener('DOMContentLoaded', function () {
  var downloadButton = document.getElementById('download-pdf');
  var printButton = document.getElementById('print-certificate');
  var certificate = document.getElementById('certificate-to-download');
  var status = document.getElementById('pdf-status');
  var paperModal = document.getElementById('paper-verification-modal');
  var paperForm = document.getElementById('paper-verification-form');
  var paperInput = document.getElementById('paper-number-input');
  var paperConfirmation = document.getElementById('paper-print-confirmation');
  var paperError = document.getElementById('paper-verification-error');

  if (!downloadButton || !printButton || !certificate || typeof html2pdf === 'undefined') return;

  var printBlob = null;
  var printObjectUrl = null;
  var preparationInProgress = false;
  var verifyPaperWhenReady = false;
  var initialPrintButtonText = printButton.textContent;

  function waitForImages() {
    var images = Array.prototype.slice.call(certificate.querySelectorAll('img'));
    return Promise.all(images.map(function (img) {
      if (img.complete) return Promise.resolve();
      return new Promise(function (resolve) {
        img.addEventListener('load', resolve, { once: true });
        img.addEventListener('error', resolve, { once: true });
      });
    }));
  }

  function qrIsReady() {
    var slots = Array.prototype.slice.call(certificate.querySelectorAll('.qr-slot'));
    return slots.length === 2 && slots.every(function (slot) {
      return slot.getAttribute('data-qr-ready') === '1';
    });
  }

  function waitForQr() {
    if (qrIsReady()) return Promise.resolve();
    return new Promise(function (resolve) {
      var timeout = setTimeout(resolve, 3500);
      document.addEventListener('scandetect:qr-ready', function () {
        clearTimeout(timeout);
        resolve();
      }, { once: true });
    });
  }

  function options(kind) {
    var number = downloadButton.dataset.certificateNumber || 'certificat';
    var suffix = kind === 'verification' ? 'consultation-pdf1-' : 'impression-pdf4-';
    return {
      margin: 0,
      filename: 'certificat-' + suffix + number.replace(/[^a-z0-9_-]/gi, '-') + '.pdf',
      image: { type: 'jpeg', quality: 0.96 },
      html2canvas: {
        scale: 2,
        useCORS: true,
        backgroundColor: null,
        logging: false,
        windowWidth: 1750,
        windowHeight: 1100
      },
      jsPDF: { unit: 'mm', format: [366, 210], orientation: 'landscape', compress: true },
      pagebreak: { mode: ['avoid-all'] }
    };
  }

  function renderPdfBlob(kind) {
    var bodyClass = kind === 'print' ? 'pdf4-exporting' : 'verification-exporting';
    document.body.classList.add(bodyClass);
    return html2pdf().set(options(kind)).from(certificate).outputPdf('blob').then(function (blob) {
        document.body.classList.remove(bodyClass);
        return blob;
      }, function (error) {
        document.body.classList.remove(bodyClass);
        throw error;
      });
  }

  function uploadPdf(blob, type) {
    var form = new FormData();
    form.append('csrf', downloadButton.dataset.csrf || '');
    form.append('id', downloadButton.dataset.recordId || '');
    form.append('type', type);
    form.append('pdf', blob, options(type).filename);

    var saveUrl = downloadButton.getAttribute('data-save-url') || 'save-generated-pdf.php';
    return fetch(saveUrl, {
      method: 'POST',
      body: form,
      credentials: 'same-origin'
    }).then(function (response) {
      if (!response.ok) {
        return response.text().then(function (text) {
          throw new Error(text || 'Enregistrement impossible.');
        });
      }
      return response.json();
    });
  }

  function openPaperModal() {
    if (!paperModal || !paperForm || !paperInput) return;
    paperForm.reset();
    if (paperError) {
      paperError.hidden = true;
      paperError.textContent = '';
    }
    paperModal.hidden = false;
    paperModal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('paper-verification-open');
    window.setTimeout(function () { paperInput.focus(); }, 80);
  }

  function closePaperModal() {
    if (!paperModal) return;
    paperModal.hidden = true;
    paperModal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('paper-verification-open');
    printButton.focus();
  }

  function showPaperError(message) {
    if (!paperError) return;
    paperError.textContent = message;
    paperError.hidden = false;
  }

  function submitSecurePrint(paperNumber) {
    printButton.disabled = true;
    if (status) status.textContent = 'Verrouillage de l’impression unique…';

    var markData = new FormData();
    markData.append('csrf', downloadButton.dataset.csrf || '');
    markData.append('id', downloadButton.dataset.recordId || '');
    markData.append('paper_number', paperNumber);

    var markUrl = downloadButton.getAttribute('data-mark-printed-url') || 'mark-printed.php';
    fetch(markUrl, {
      method: 'POST',
      body: markData,
      credentials: 'same-origin'
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (payload) {
        if (!response.ok || !payload.ok) {
          throw new Error(payload.message || 'Impossible de confirmer l’impression unique.');
        }
        return payload;
      });
    }).then(function () {
      closePaperModal();
      document.body.classList.add('pdf4-exporting', 'secure-certificate-printing');
      if (status) status.textContent = 'Certificat verrouillé. Ouverture de l’impression…';

      window.addEventListener('afterprint', function () {
        document.body.classList.remove('pdf4-exporting', 'secure-certificate-printing');
        if (status) status.textContent = 'Impression terminée. Retour au tableau de bord…';
        window.setTimeout(function () {
          window.location.href = downloadButton.getAttribute('data-dashboard-url') || 'dashboard.php';
        }, 3000);
      }, { once: true });

      window.print();
    }).catch(function (error) {
      printButton.disabled = false;
      var detail = error && error.message ? error.message : 'Erreur inconnue.';
      showPaperError(detail);
      if (status) status.textContent = 'L’impression n’a pas pu être lancée.';
    });
  }

  function prepare() {
    if (preparationInProgress) return;
    preparationInProgress = true;
    downloadButton.disabled = true;
    printButton.disabled = true;
    printButton.textContent = 'Préparation en cours…';
    if (status) status.textContent = 'Génération des trois codes QR…';

    var verificationBlob = null;

    Promise.all([waitForQr(), waitForImages()])
      .then(function () {
        if (status) status.textContent = 'Création de la version de consultation PDF …';
        return renderPdfBlob('verification');
      })
      .then(function (blob) {
        verificationBlob = blob;
        if (status) status.textContent = 'Enregistrement du certificat de vérification…';
        return uploadPdf(verificationBlob, 'verification');
      })
      .then(function () {
        if (status) status.textContent = 'Création du calque d’impression PDF 4…';
        return renderPdfBlob('print');
      })
      .then(function (blob) {
        printBlob = blob;
        if (printObjectUrl) URL.revokeObjectURL(printObjectUrl);
        printObjectUrl = URL.createObjectURL(blob);
        if (status) status.textContent = 'Enregistrement du calque d’impression…';
        return uploadPdf(blob, 'print');
      })
      .then(function () {
        preparationInProgress = false;
        downloadButton.disabled = false;
        printButton.disabled = false;
        printButton.textContent = 'Vérifier le papier et imprimer →';
        var expectedNumber = downloadButton.dataset.certificateNumber || '';
        if (status) status.textContent = 'PDF prêt. Vérification du papier officiel ' + expectedNumber + '…';
        if (downloadButton.dataset.autoDownload === '1' && printObjectUrl) {
          window.setTimeout(function () {
            var autoLink = document.createElement('a');
            autoLink.href = printObjectUrl;
            autoLink.download = options('print').filename;
            document.body.appendChild(autoLink);
            autoLink.click();
            autoLink.remove();
            downloadButton.dataset.autoDownload = '0';
          }, 250);
        }
        if (verifyPaperWhenReady) {
          verifyPaperWhenReady = false;
          window.setTimeout(function () {
            printButton.click();
          }, 100);
        }
      })
      .catch(function (error) {
        console.error(error);
        preparationInProgress = false;
        downloadButton.disabled = false;
        printButton.disabled = false;
        printButton.textContent = initialPrintButtonText;
        var detail = error && error.message ? error.message : 'Erreur inconnue.';
        if (status) status.textContent = 'Échec de la préparation : ' + detail;
      });
  }

  downloadButton.addEventListener('click', function () {
    if (!printBlob || !printObjectUrl) {
      prepare();
      return;
    }
    var link = document.createElement('a');
    link.href = printObjectUrl;
    link.download = options('print').filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
  });

  printButton.addEventListener('click', function () {
    if (!printBlob || !printObjectUrl) {
      verifyPaperWhenReady = true;
      prepare();
      return;
    }

    openPaperModal();
  });

  if (paperModal && paperForm && paperInput) {
    Array.prototype.forEach.call(paperModal.querySelectorAll('[data-paper-modal-close]'), function (button) {
      button.addEventListener('click', closePaperModal);
    });

    paperForm.addEventListener('submit', function (event) {
      event.preventDefault();
      var expectedNumber = String(downloadButton.dataset.certificateNumber || '').trim();
      var enteredNumber = String(paperInput.value || '').trim();

      if (enteredNumber !== expectedNumber) {
        showPaperError('Le numéro saisi ne correspond pas. Numéro attendu : ' + expectedNumber + '.');
        paperInput.focus();
        paperInput.select();
        return;
      }
      if (!paperConfirmation || !paperConfirmation.checked) {
        showPaperError('Cochez la confirmation d’impression unique avant de continuer.');
        return;
      }

      if (paperError) paperError.hidden = true;
      submitSecurePrint(enteredNumber);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !paperModal.hidden) closePaperModal();
    });
  }

  window.addEventListener('beforeunload', function () {
    if (printObjectUrl) URL.revokeObjectURL(printObjectUrl);
  });

  prepare();
});
