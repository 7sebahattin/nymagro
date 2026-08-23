<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash = $flash ?? [];
$kayitlar = $kayitlar ?? [];
$filters = $filters ?? [];
$moduller = $moduller ?? [];
$islemler = $islemler ?? [];
$moduleLabels = $moduleLabels ?? [];
$sirketler = $sirketler ?? [];
$donemler = $donemler ?? [];
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $h($flash['tip'] === 'danger' || $flash['tip'] === 'error' ? 'danger' : 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div>
<?php endif; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/audit/giris-gecmisi" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-right-to-bracket"></i> Giriş Geçmişi</a>
  <a href="<?= BASE_URL ?>/audit/export?<?= http_build_query($filters) ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-file-csv"></i> CSV Dışa Aktar</a>
  <a href="<?= BASE_URL ?>/audit/teshis" class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-magnifying-glass"></i> Teşhis (Geçici)</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-auto"><input type="text" name="q" class="form-control form-control-sm" placeholder="Açıklama / kayıt ara" value="<?= $h($filters['q'] ?? '') ?>" style="min-width:200px"></div>
  <div class="col-auto"><input type="text" name="record_id" class="form-control form-control-sm" placeholder="Kayıt ID" value="<?= $h($filters['record_id'] ?? '') ?>" style="width:110px"></div>
  <div class="col-auto">
    <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">Tüm şirketler</option>
      <?php foreach ($sirketler as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int)($filters['company_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= $h($s['company_name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <select name="period_id" class="form-select form-select-sm">
      <option value="">Tüm dönemler</option>
      <?php foreach ($donemler as $d): ?><option value="<?= (int)$d['id'] ?>" <?= (int)($filters['period_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>><?= $h($d['fiscal_year'] ?? $d['id']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <select name="module" class="form-select form-select-sm">
      <option value="">Tüm modüller</option>
      <?php foreach ($moduller as $m): ?><option value="<?= $h($m) ?>" <?= ($filters['module'] ?? '') === $m ? 'selected' : '' ?>><?= $h($moduleLabels[$m] ?? $m) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <select name="action" class="form-select form-select-sm">
      <option value="">Tüm işlemler</option>
      <?php foreach ($islemler as $a): ?><option value="<?= $h($a) ?>" <?= ($filters['action'] ?? '') === $a ? 'selected' : '' ?>><?= $h($a) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <select name="success" class="form-select form-select-sm">
      <option value="">Başarılı/Başarısız</option>
      <option value="1" <?= ($filters['success'] ?? '') === '1' ? 'selected' : '' ?>>Başarılı</option>
      <option value="0" <?= ($filters['success'] ?? '') === '0' ? 'selected' : '' ?>>Başarısız</option>
    </select>
  </div>
  <div class="col-auto"><input type="text" name="ip" class="form-control form-control-sm" placeholder="IP" value="<?= $h($filters['ip'] ?? '') ?>"></div>
  <div class="col-auto"><input type="date" name="start" class="form-control form-control-sm" value="<?= $h($filters['start'] ?? '') ?>"></div>
  <div class="col-auto"><input type="date" name="end" class="form-control form-control-sm" value="<?= $h($filters['end'] ?? '') ?>"></div>
  <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Filtrele</button></div>
</form>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-transparent"><?= (int)$toplam ?> kayıt</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light"><tr><th>Tarih</th><th>Kullanıcı</th><th>İşlem</th><th>Modül</th><th>Kayıt</th><th>Açıklama</th><th>Sonuç</th><th>IP</th></tr></thead>
      <tbody>
      <?php if (empty($kayitlar)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>
      <?php endif; ?>
      <?php foreach ($kayitlar as $log): ?>
        <?php
          $before = $log['before_json'] ? json_decode($log['before_json'], true) : null;
          $after = $log['after_json'] ? json_decode($log['after_json'], true) : null;
          $diff = Audit::diff($before, $after);
        ?>
        <tr>
          <td class="text-nowrap"><?= $h(date('d.m.Y H:i:s', strtotime($log['created_at']))) ?></td>
          <td><?= $h($log['user_snapshot'] ?: 'Sistem') ?></td>
          <td><span class="badge bg-light text-dark border"><?= $h($log['action']) ?></span></td>
          <td><?= $h($moduleLabels[$log['module']] ?? $log['module'] ?? '-') ?></td>
          <td><?= $h($log['record_id'] ?: '-') ?></td>
          <td style="max-width:340px">
            <?= $h($log['description'] ?? '') ?>
            <?php if (!empty($diff)): ?>
              <ul class="mb-0 ps-3 small text-muted">
                <?php foreach ($diff as $field => $vals): ?>
                  <li><?= $h($field) ?>: <code><?= $h(is_array($vals['old']) ? json_encode($vals['old']) : $vals['old']) ?></code> → <code><?= $h(is_array($vals['new']) ? json_encode($vals['new']) : $vals['new']) ?></code></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </td>
          <td><?= $log['success'] ? '<span class="badge bg-success">Başarılı</span>' : '<span class="badge bg-danger">Başarısız</span>' ?></td>
          <td class="text-nowrap"><?= $h($log['ip_address'] ?: '-') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (($sayfaSayisi ?? 1) > 1): ?>
<nav class="mt-3">
  <ul class="pagination pagination-sm justify-content-center">
    <?php for ($p = 1; $p <= $sayfaSayisi; $p++): $qs = array_merge($filters, ['sayfa' => $p]); ?>
      <li class="page-item <?= $p === $sayfa ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query($qs) ?>"><?= $p ?></a></li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
