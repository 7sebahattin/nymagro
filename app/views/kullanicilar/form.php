<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash = $flash ?? [];
$kullanici = $kullanici ?? null;
$roller = $roller ?? [];
$sirketler = $sirketler ?? [];
$atananlar = $atananlar ?? [];
$sirketRolleri = $sirketRolleri ?? [];
$isEdit = $kullanici !== null;
// Mevcut atamalardaki şirket rolü (hepsi aynı role sahip varsayılır; farklıysa
// ilk atamanınki gösterilir). Yeni kullanıcıda güvenli varsayılan seçilidir.
$seciliSirketRolu = 'viewer';
foreach ($atananlar as $atama) {
    $seciliSirketRolu = (string)($atama['role'] ?? 'viewer');
    break;
}
$meId = AuthGuard::userId();
$isSelf = $isEdit && (int)$kullanici['id'] === $meId;
$action = $isEdit ? (BASE_URL . '/kullanicilar/' . (int)$kullanici['id'] . '/guncelle') : (BASE_URL . '/kullanicilar/kaydet');
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $h($flash['tip'] === 'danger' || $flash['tip'] === 'error' ? 'danger' : 'success') ?>"><?= $h($flash['mesaj'] ?? '') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <form method="post" action="<?= $action ?>" class="row g-3">
      <?= Csrf::fieldHtml() ?>
      <div class="col-md-6">
        <label class="form-label">Ad Soyad *</label>
        <input type="text" name="full_name" class="form-control" required value="<?= $h($kullanici['full_name'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Kullanıcı Adı *</label>
        <input type="text" name="username" class="form-control" required pattern="[A-Za-z0-9_.\-]{3,80}" value="<?= $h($kullanici['username'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">E-posta</label>
        <input type="email" name="email" class="form-control" value="<?= $h($kullanici['email'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Telefon</label>
        <input type="text" name="phone" class="form-control" value="<?= $h($kullanici['phone'] ?? '') ?>">
      </div>
      <?php if ($isEdit): ?>
        <div class="col-md-6">
          <label class="form-label">Unvan</label>
          <input type="text" name="title" class="form-control" value="<?= $h($kullanici['title'] ?? '') ?>">
        </div>
      <?php endif; ?>

      <div class="col-md-6">
        <label class="form-label">Rol *</label>
        <select name="role_id" class="form-select" required <?= $isSelf ? 'disabled' : '' ?>>
          <?php foreach ($roller as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $isEdit && (int)$kullanici['role_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= $h($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($isSelf): ?>
          <input type="hidden" name="role_id" value="<?= (int)$kullanici['role_id'] ?>">
          <div class="form-text text-warning">Kendi rolünüzü değiştiremezsiniz.</div>
        <?php endif; ?>
      </div>

      <?php if (!$isEdit): ?>
        <div class="col-md-6">
          <label class="form-label">Durum</label>
          <select name="status" class="form-select">
            <option value="active">Aktif</option>
            <option value="passive">Pasif</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Şifre *</label>
          <input type="password" name="password" class="form-control" required minlength="10" autocomplete="new-password">
        </div>
        <div class="col-md-6">
          <label class="form-label">Şifre Tekrar *</label>
          <input type="password" name="password_confirm" class="form-control" required minlength="10" autocomplete="new-password">
        </div>
        <div class="col-12">
          <label class="form-label">Açıklama</label>
          <textarea name="note" class="form-control" rows="2"></textarea>
        </div>
      <?php endif; ?>

      <div class="col-12"><hr class="my-2"></div>

      <div class="col-12">
        <label class="form-label mb-1">Yetkili Şirketler *</label>
        <div class="form-text mb-2">
          Kullanıcı yalnızca burada işaretlenen şirketlere girebilir. En az bir şirket seçilmelidir —
          şirketi olmayan kullanıcı giriş yapsa bile hiçbir ekranı açamaz.
        </div>
        <?php if (empty($sirketler)): ?>
          <div class="alert alert-warning mb-0">Sistemde tanımlı şirket yok. Önce <a href="<?= BASE_URL ?>/companies/create">bir şirket oluşturun</a>.</div>
        <?php else: ?>
          <div class="row g-2">
            <?php foreach ($sirketler as $s): ?>
              <?php
                $sid = (int)$s['id'];
                $isChecked = array_key_exists($sid, $atananlar);
                $isPassive = ($s['status'] ?? 'active') !== 'active';
              ?>
              <div class="col-md-6">
                <div class="form-check border rounded p-2 ps-4">
                  <input class="form-check-input" type="checkbox" name="company_ids[]"
                         value="<?= $sid ?>" id="company-<?= $sid ?>" <?= $isChecked ? 'checked' : '' ?>>
                  <label class="form-check-label w-100" for="company-<?= $sid ?>">
                    <span class="fw-semibold"><?= $h($s['company_name']) ?></span>
                    <?php if ($isPassive): ?>
                      <span class="badge text-bg-secondary ms-1">pasif</span>
                    <?php endif; ?>
                    <?php if (!empty($atananlar[$sid]['is_default'])): ?>
                      <span class="badge text-bg-warning ms-1"><i class="fa-solid fa-star me-1"></i>varsayılan</span>
                    <?php endif; ?>
                    <span class="d-block text-muted small"><?= $h($s['short_name'] ?? '') ?></span>
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if (!empty(array_filter($sirketler, fn($s) => ($s['status'] ?? 'active') !== 'active'))): ?>
            <div class="form-text text-warning mt-2">
              Pasif şirketler seçilebilir ancak kullanıcı pasif bir şirkete giriş yapamaz. Kullanıcının
              çalışabilmesi için en az bir <strong>aktif</strong> şirket işaretleyin.
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="col-md-6">
        <label class="form-label">Şirket Yetki Düzeyi</label>
        <select name="company_role" class="form-select">
          <?php foreach ($sirketRolleri as $kod => $etiket): ?>
            <option value="<?= $h($kod) ?>" <?= $seciliSirketRolu === $kod ? 'selected' : '' ?>><?= $h($etiket) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">
          Yukarıdaki "Rol" kullanıcının hangi modülleri kullanabileceğini belirler.
          Bu alan ise yalnızca <strong>şirket ve dönem ayarlarını</strong> yönetip yönetemeyeceğini belirler.
          Emin değilseniz "Standart" bırakın.
        </div>
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Kaydet</button>
        <a href="<?= BASE_URL ?>/kullanicilar" class="btn btn-outline-secondary">Vazgeç</a>
      </div>
    </form>
  </div>
</div>
