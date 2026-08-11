<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash = $flash ?? [];
$kayitlar = $kayitlar ?? [];
$filters = $filters ?? [];
$eventLabels = ['login_success' => ['Başarılı Giriş', 'success'], 'login_failed' => ['Başarısız Giriş', 'danger'], 'logout' => ['Çıkış', 'secondary']];
$reasonLabels = [
    'invalid_credentials' => 'Hatalı kullanıcı adı/şifre', 'inactive_user' => 'Hesap pasif',
    'account_locked' => 'Hesap kilitli (yönetici)', 'rate_limited' => 'Çok fazla başarısız deneme',
];
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $h($flash['tip'] === 'danger' || $flash['tip'] === 'error' ? 'danger' : 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div>
<?php endif; ?>

<div class="mb-3">
  <a href="<?= BASE_URL ?>/audit" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Audit Log</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-auto"><input type="text" name="q" class="form-control form-control-sm" placeholder="Kullanıcı adı / IP ara" value="<?= $h($filters['q'] ?? '') ?>" style="min-width:220px"></div>
  <div class="col-auto">
    <select name="event" class="form-select form-select-sm">
      <option value="">Tüm olaylar</option>
      <option value="login_success" <?= ($filters['event'] ?? '') === 'login_success' ? 'selected' : '' ?>>Başarılı Giriş</option>
      <option value="login_failed" <?= ($filters['event'] ?? '') === 'login_failed' ? 'selected' : '' ?>>Başarısız Giriş</option>
      <option value="logout" <?= ($filters['event'] ?? '') === 'logout' ? 'selected' : '' ?>>Çıkış</option>
    </select>
  </div>
  <div class="col-auto"><input type="date" name="start" class="form-control form-control-sm" value="<?= $h($filters['start'] ?? '') ?>"></div>
  <div class="col-auto"><input type="date" name="end" class="form-control form-control-sm" value="<?= $h($filters['end'] ?? '') ?>"></div>
  <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Filtrele</button></div>
</form>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-transparent"><?= (int)$toplam ?> kayıt</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light"><tr><th>Tarih</th><th>Kullanıcı</th><th>Olay</th><th>Sebep</th><th>IP</th><th>Tarayıcı</th></tr></thead>
      <tbody>
      <?php if (empty($kayitlar)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>
      <?php endif; ?>
      <?php foreach ($kayitlar as $row): [$label, $cls] = $eventLabels[$row['event']] ?? [$row['event'], 'secondary']; ?>
        <tr>
          <td class="text-nowrap"><?= $h(date('d.m.Y H:i:s', strtotime($row['created_at']))) ?></td>
          <td><?= $h($row['full_name'] ?: $row['username_attempted'] ?: '-') ?></td>
          <td><span class="badge bg-<?= $cls ?>"><?= $label ?></span></td>
          <td><?= $h($reasonLabels[$row['fail_reason']] ?? ($row['fail_reason'] ?: '-')) ?></td>
          <td><?= $h($row['ip_address'] ?: '-') ?></td>
          <td class="text-truncate" style="max-width:260px" title="<?= $h($row['user_agent']) ?>"><?= $h($row['user_agent'] ?: '-') ?></td>
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
