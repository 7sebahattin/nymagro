<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash = $flash ?? [];
$kullanici = $kullanici ?? [];
$kayitlar = $kayitlar ?? [];
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
  <a href="<?= BASE_URL ?>/kullanicilar" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Kullanıcılara Dön</a>
  <a href="<?= BASE_URL ?>/kullanicilar/<?= (int)$kullanici['id'] ?>/aktivite" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-clock-rotate-left"></i> Aktivite Geçmişi</a>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-transparent">
    <strong><?= $h($kullanici['full_name']) ?></strong> — Giriş Geçmişi (<?= (int)$toplam ?> kayıt)
  </div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light"><tr><th>Tarih</th><th>Olay</th><th>Sebep</th><th>IP</th><th>Tarayıcı</th></tr></thead>
      <tbody>
      <?php if (empty($kayitlar)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>
      <?php endif; ?>
      <?php foreach ($kayitlar as $row): [$label, $cls] = $eventLabels[$row['event']] ?? [$row['event'], 'secondary']; ?>
        <tr>
          <td class="text-nowrap"><?= $h(date('d.m.Y H:i:s', strtotime($row['created_at']))) ?></td>
          <td><span class="badge bg-<?= $cls ?>"><?= $label ?></span></td>
          <td><?= $h($reasonLabels[$row['fail_reason']] ?? ($row['fail_reason'] ?: '-')) ?></td>
          <td><?= $h($row['ip_address'] ?: '-') ?></td>
          <td class="text-truncate" style="max-width:280px" title="<?= $h($row['user_agent']) ?>"><?= $h($row['user_agent'] ?: '-') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (($sayfaSayisi ?? 1) > 1): ?>
<nav class="mt-3">
  <ul class="pagination pagination-sm justify-content-center">
    <?php for ($p = 1; $p <= $sayfaSayisi; $p++): ?>
      <li class="page-item <?= $p === $sayfa ? 'active' : '' ?>"><a class="page-link" href="?sayfa=<?= $p ?>"><?= $p ?></a></li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
