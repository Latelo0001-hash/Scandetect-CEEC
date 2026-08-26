(function () {
  'use strict';

  function qrDataUrl(value, targetPixels, foreground, background) {
    if (!window.ScanDetectQRCode || !window.ScanDetectQRErrorCorrectLevel) {
      throw new Error('Moteur QR indisponible.');
    }

    var qr = new window.ScanDetectQRCode(-1, window.ScanDetectQRErrorCorrectLevel.M);
    qr.addData(value);
    qr.make();

    var count = qr.getModuleCount();
    var quiet = 4;
    var total = count + quiet * 2;
    var size = Math.max(220, targetPixels || 420);
    var cell = Math.max(1, Math.floor(size / total));
    var canvasSize = cell * total;
    var canvas = document.createElement('canvas');
    canvas.width = canvasSize;
    canvas.height = canvasSize;
    var ctx = canvas.getContext('2d');

    ctx.clearRect(0, 0, canvasSize, canvasSize);
    if (background && background !== 'transparent') {
      ctx.fillStyle = background;
      ctx.fillRect(0, 0, canvasSize, canvasSize);
    }
    ctx.fillStyle = foreground || '#000000';

    for (var row = 0; row < count; row++) {
      for (var col = 0; col < count; col++) {
        if (qr.isDark(row, col)) {
          ctx.fillRect((col + quiet) * cell, (row + quiet) * cell, cell, cell);
        }
      }
    }

    return canvas.toDataURL('image/png');
  }

  function renderQrSlots() {
    var slots = Array.prototype.slice.call(document.querySelectorAll('.qr-slot[data-qr-value]'));
    slots.forEach(function (slot) {
      var value = slot.getAttribute('data-qr-value') || '';
      if (!value) return;

      try {
        var img = document.createElement('img');
        img.alt = slot.getAttribute('aria-label') || 'QR code ScanDetect';
        img.src = qrDataUrl(
          value,
          520,
          slot.getAttribute('data-qr-color') || '#000000',
          slot.getAttribute('data-qr-background') || 'transparent'
        );
        img.className = 'qr-generated-image';
        slot.innerHTML = '';
        slot.appendChild(img);
        slot.setAttribute('data-qr-ready', '1');
      } catch (error) {
        slot.textContent = 'QR';
        slot.classList.add('qr-error');
        console.error(error);
      }
    });
    document.dispatchEvent(new CustomEvent('scandetect:qr-ready'));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderQrSlots);
  } else {
    renderQrSlots();
  }
})();
