<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/storage.php';
require_auth();

$error = '';
try {
    $records = list_certificate_records();
} catch (Throwable $e) {
    $records = [];
    $error = 'Impossible de charger les certificats. Vérifiez les permissions du dossier storage.';
    error_log('[ScanDetect] dashboard list_certificate_records: ' . $e->getMessage());
}
page_start('Tableau de bord');
?>
<section class="page-hero"><div class="container"><p class="eyebrow light">Vue d’ensemble</p><h1>Tableau de bord</h1><p>Créez et consultez les certificats d’origine générés.</p></div></section>
<section class="container section-space dashboard-container">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <div class="actions-row">
        <div><h2>Certificat généré</h2><p class="muted"><?= count($records) ?> certificat(s) enregistré(s)</p></div>
        <a class="btn-primary-app inline-btn" href="<?= e(app_route('certificat.php')) ?>">Générer un certificat <span>→</span></a>
    </div>
    <?php if ($records): ?>
    <div class="dashboard-filters" role="search" aria-label="Filtrer les certificats">
        <div class="dashboard-filter-search">
            <label for="dashboard-search">Rechercher</label>
            <div class="dashboard-search-control"><span aria-hidden="true">⌕</span><input class="form-control" id="dashboard-search" type="search" placeholder="N° certificat, exportateur, produit ou localisation" autocomplete="off"></div>
        </div>
        <div class="dashboard-filter-status">
            <label for="dashboard-status-filter">Statut</label>
            <select class="form-control" id="dashboard-status-filter">
                <option value="all">Tous les statuts</option>
                <option value="unprinted">Non imprimé</option>
                <option value="printed">Imprimé</option>
                <option value="latest_printed">Derniers imprimés</option>
            </select>
        </div>
        <button class="btn-secondary-app dashboard-filter-reset" id="dashboard-filter-reset" type="button">Réinitialiser</button>
    </div>
    <?php endif; ?>
    <div class="card-panel">
    <?php if (!$records): ?>
        <div class="empty-state"><div class="empty-icon">◇</div><h3>Aucun certificat</h3><p>Commencez par générer un certificat d’origine.</p><a href="<?= e(app_route('certificat.php')) ?>">Générer un certificat →</a></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table app-table dashboard-certificates-table"><thead><tr><th>N° Certificat</th><th>Exportateur</th><th>Produit</th><th>Date générée</th><th>Statut</th><th>Localisation du camion</th><th>Opération</th></tr></thead><tbody>
        <?php foreach ($records as $record):
            $data = (array) ($record['data'] ?? []);
            $token = (string) ($record['token'] ?? '');
            $created = (string) ($record['created_at'] ?? '');
            $dt = $created !== '' ? date_create($created) : false;
            $number = trim((string) ($data['certificate_number'] ?? ''));
            $printedAt = trim((string) ($record['printed_at'] ?? ''));
            $printed = $printedAt !== '';
            $printedTimestamp = $printed ? (strtotime($printedAt) ?: 0) : 0;
            $location = certificate_truck_location($record);
            $continueUrl = app_route('certificat.php?number=' . rawurlencode($number));
            // Toujours passer par view-certificate.php : si le PDF existe il est ouvert,
            // sinon un aperçu en lecture seule est rendu à partir des données sauvegardées.
            $viewUrl = app_route('view-certificate.php?id=' . rawurlencode($token));
        ?>
            <tr class="dashboard-certificate-row <?= $printed ? 'dashboard-row-printed' : 'dashboard-row-active' ?>" data-filter-status="<?= $printed ? 'printed' : 'unprinted' ?>" data-printed-at="<?= $printedTimestamp ?>" <?= !$printed ? 'data-href="' . e($continueUrl) . '" tabindex="0" role="link"' : '' ?>>
                <td><strong><?= e($number) ?></strong></td>
                <td><?= e((string) ($data['exporter'] ?? '')) ?></td>
                <td><?= e((string) ($data['product'] ?? '')) ?></td>
                <td><?= $dt ? e($dt->format('d/m/Y')) : '' ?></td>
                <td><span class="dashboard-status <?= $printed ? 'dashboard-status-printed' : 'dashboard-status-unprinted' ?>"><?= $printed ? 'Imprimé' : 'Non imprimé' ?></span></td>
                <td><a class="truck-location truck-location-link" href="<?= e(app_route('map-localisation.pdf')) ?>" data-location-modal-open data-location="<?= e($location) ?>" data-certificate="<?= e($number) ?>" title="Voir la localisation sur la carte">📍 <?= e($location) ?></a></td>
                <td class="dashboard-operation-cell">
                    <?php if ($printed): ?>
                        <a class="dashboard-view-link" href="<?= e($viewUrl) ?>" target="_blank" rel="noopener">Voir</a>
                    <?php else: ?>
                        <a class="dashboard-continue-link" href="<?= e($continueUrl) ?>">Imprimer</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr class="dashboard-filter-empty" id="dashboard-filter-empty" hidden><td colspan="7">Aucun certificat ne correspond à ces critères.</td></tr>
        </tbody></table></div>
    <?php endif; ?>
    </div>
</section>

<div class="location-modal" id="location-modal" hidden aria-hidden="true">
    <div class="location-modal-backdrop" data-location-modal-close></div>
    <div class="location-modal-card" role="dialog" aria-modal="true" aria-labelledby="location-modal-title">
        <div class="location-modal-header">
            <div>
                <p class="eyebrow">Traçabilité du camion</p>
                <h2 id="location-modal-title">Localisation du camion</h2>
                <p class="location-modal-current"><strong id="location-modal-certificate"></strong><span id="location-modal-location"></span></p>
            </div>
            <button class="location-modal-close" type="button" data-location-modal-close aria-label="Fermer">×</button>
        </div>
        <div class="location-map-wrap">
            <img src="<?= e(app_route('images/map-localisation.png')) ?>" alt="Schéma des opérations de traçabilité CEEC" class="location-map-image">
        </div>
        <div class="location-modal-footer">
            <span>Schéma des opérations de traçabilité fourni par le CEEC.</span>
            <a class="btn-secondary-app" href="<?= e(app_route('map-localisation.pdf')) ?>" target="_blank" rel="noopener">Ouvrir la carte en plein écran</a>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('dashboard-search');
    var statusFilter = document.getElementById('dashboard-status-filter');
    var resetFilter = document.getElementById('dashboard-filter-reset');
    var filterCount = document.getElementById('dashboard-filter-count');
    var filterEmpty = document.getElementById('dashboard-filter-empty');
    var certificateRows = Array.prototype.slice.call(document.querySelectorAll('.dashboard-certificate-row'));
    var certificateBody = certificateRows.length ? certificateRows[0].parentNode : null;
    certificateRows.forEach(function (row, index) { row.dataset.originalOrder = String(index); });

    function normalizeFilterText(value) {
        var text = String(value || '').toLocaleLowerCase('fr');
        return typeof text.normalize === 'function' ? text.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : text;
    }

    function applyDashboardFilters() {
        var query = normalizeFilterText(searchInput ? searchInput.value.trim() : '');
        var selectedStatus = statusFilter ? statusFilter.value : 'all';
        var visibleCount = 0;

        var orderedRows = certificateRows.slice();
        if (selectedStatus === 'latest_printed') {
            orderedRows.sort(function (left, right) {
                return Number(right.dataset.printedAt || 0) - Number(left.dataset.printedAt || 0);
            });
        } else {
            orderedRows.sort(function (left, right) {
                return Number(left.dataset.originalOrder || 0) - Number(right.dataset.originalOrder || 0);
            });
        }
        if (certificateBody) orderedRows.forEach(function (row) { certificateBody.appendChild(row); });

        orderedRows.forEach(function (row) {
            var matchesText = query === '' || normalizeFilterText(row.textContent).indexOf(query) !== -1;
            var rowStatus = row.getAttribute('data-filter-status') || '';
            var matchesStatus = selectedStatus === 'all' || selectedStatus === rowStatus || (selectedStatus === 'latest_printed' && rowStatus === 'printed');
            var visible = matchesText && matchesStatus;
            row.hidden = !visible;
            if (visible) visibleCount += 1;
        });

        if (filterCount) filterCount.textContent = visibleCount + ' résultat(s)';
        if (filterEmpty) {
            filterEmpty.hidden = visibleCount !== 0;
            if (certificateBody) certificateBody.appendChild(filterEmpty);
        }
    }

    if (searchInput) searchInput.addEventListener('input', applyDashboardFilters);
    if (statusFilter) statusFilter.addEventListener('change', applyDashboardFilters);
    if (resetFilter) {
        resetFilter.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            if (statusFilter) statusFilter.value = 'all';
            applyDashboardFilters();
            if (searchInput) searchInput.focus();
        });
    }

    document.querySelectorAll('.dashboard-row-active[data-href]').forEach(function (row) {
        function openRow(event) {
            if (event.target.closest('a,button,input,select,textarea')) return;
            window.location.href = row.dataset.href;
        }
        row.addEventListener('click', openRow);
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                window.location.href = row.dataset.href;
            }
        });
    });


    var locationModal = document.getElementById('location-modal');
    var locationText = document.getElementById('location-modal-location');
    var certificateText = document.getElementById('location-modal-certificate');
    var locationCloseButtons = locationModal ? locationModal.querySelectorAll('[data-location-modal-close]') : [];

    function closeLocationModal() {
        if (!locationModal) return;
        locationModal.hidden = true;
        locationModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('location-modal-open');
    }

    document.querySelectorAll('[data-location-modal-open]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (!locationModal) return;
            var location = button.getAttribute('data-location') || 'Localisation non renseignée';
            var certificate = button.getAttribute('data-certificate') || '';
            if (locationText) locationText.textContent = location;
            if (certificateText) certificateText.textContent = certificate ? 'Certificat ' + certificate + ' — ' : '';
            locationModal.hidden = false;
            locationModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('location-modal-open');
            var closeButton = locationModal.querySelector('.location-modal-close');
            if (closeButton) window.setTimeout(function () { closeButton.focus(); }, 50);
        });
    });

    Array.prototype.forEach.call(locationCloseButtons, function (button) {
        button.addEventListener('click', closeLocationModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && locationModal && !locationModal.hidden) closeLocationModal();
    });
});
</script>
<?php page_end(); ?>
