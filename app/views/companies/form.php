<?php
$company = $company ?? [];
$settings = $settings ?? [];
$flash = $flash ?? [];
$v = fn(string $key, string $default = '') => htmlspecialchars((string)($company[$key] ?? $settings[$key] ?? $default));
$themeColors = [
  'emerald' => ['label' => 'Yeşil', 'hex' => '#22c55e'],
  'blue' => ['label' => 'Mavi', 'hex' => '#3b82f6'],
  'violet' => ['label' => 'Mor', 'hex' => '#8b5cf6'],
  'amber' => ['label' => 'Sarı', 'hex' => '#f59e0b'],
  'rose' => ['label' => 'Kırmızı', 'hex' => '#f43f5e'],
  'cyan' => ['label' => 'Camgöbeği', 'hex' => '#06b6d4'],
  'slate' => ['label' => 'Gri', 'hex' => '#94a3b8'],
];
$selectedTheme = $settings['theme_color'] ?? 'emerald';
?>
<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= htmlspecialchars($flash['tip'] === 'error' ? 'danger' : $flash['tip']) ?>"><?= htmlspecialchars($flash['mesaj']) ?></div>
<?php endif; ?>

<style>
  .company-palette { display:flex; flex-wrap:wrap; gap:8px; }
  .company-palette input { position:absolute; opacity:0; pointer-events:none; }
  .company-palette label { width:32px; height:32px; border-radius:8px; border:2px solid var(--border); display:flex; align-items:center; justify-content:center; cursor:pointer; background:var(--card-bg); transition:border-color .15s, box-shadow .15s, transform .15s; }
  .company-palette label span { width:20px; height:20px; border-radius:6px; background:var(--swatch); box-shadow:inset 0 0 0 1px rgba(255,255,255,.35); }
  .company-palette input:checked + label { border-color:var(--swatch); box-shadow:0 0 0 3px color-mix(in srgb, var(--swatch) 22%, transparent); transform:translateY(-1px); }
</style>

<form method="post" action="<?= htmlspecialchars($action) ?>" class="row g-3" enctype="multipart/form-data">
  <div class="col-12 d-flex justify-content-between align-items-center">
    <div>
      <h1 class="h4 mb-1"><?= !empty($company['id']) ? 'Şirket Düzenle' : 'Yeni Şirket' ?></h1>
      <div class="text-muted small">Vergi bilgileri, adres ve şirket bazlı fatura ayarları.</div>
    </div>
    <button class="btn btn-success"><i class="fa-solid fa-floppy-disk me-1"></i> Kaydet</button>
  </div>

  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-body row g-3">
        <div class="col-md-8">
          <label class="form-label">Şirket adı</label>
          <input name="company_name" class="form-control" required value="<?= $v('company_name') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Kısa ad</label>
          <input name="short_name" class="form-control" value="<?= $v('short_name') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Vergi no</label>
          <input name="tax_number" class="form-control" value="<?= $v('tax_number') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Vergi dairesi</label>
          <input name="tax_office" class="form-control" value="<?= $v('tax_office') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">MERSİS no</label>
          <input name="mersis_no" class="form-control" value="<?= $v('mersis_no') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Telefon</label>
          <input name="phone" class="form-control" value="<?= $v('phone') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">E-posta</label>
          <input name="email" type="email" class="form-control" value="<?= $v('email') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Web sitesi</label>
          <input name="website" class="form-control" value="<?= $v('website') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Şehir</label>
          <input name="city" class="form-control" value="<?= $v('city') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">İlçe</label>
          <input name="district" class="form-control" value="<?= $v('district') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Ülke</label>
          <input name="country" class="form-control" value="<?= $v('country', 'Türkiye') ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Adres</label>
          <textarea name="address" class="form-control" rows="3"><?= $v('address') ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body row g-3">
        <div class="col-12">
          <label class="form-label">Şirket logosu</label>
          <input name="logo" type="file" class="form-control" accept="image/png,image/jpeg,image/webp,image/gif">
          <div class="form-text">PNG, JPG, WEBP veya GIF. Yazdırma şablonlarında şirket adının yanında kullanılır.</div>
          <?php if (!empty($company['logo_path'])): ?>
            <div class="mt-2 d-flex align-items-center gap-2">
              <img src="<?= BASE_URL ?>/companies/logo/<?= (int)$company['id'] ?>" alt="" style="width:64px;height:64px;object-fit:contain;border:1px solid var(--border);border-radius:8px;padding:6px;background:var(--card-bg);">
              <span class="text-muted small"><?= htmlspecialchars((string)$company['logo_path']) ?></span>
            </div>
          <?php endif; ?>
        </div>
        <div class="col-6">
          <label class="form-label">Para birimi</label>
          <input name="currency" class="form-control" value="<?= $v('currency', 'TRY') ?>">
        </div>
        <div class="col-6">
          <label class="form-label">Durum</label>
          <select name="status" class="form-select">
            <?php foreach (['active' => 'Aktif', 'passive' => 'Pasif', 'archived' => 'Arşiv'] as $key => $label): ?>
              <option value="<?= $key ?>" <?= ($company['status'] ?? 'active') === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label">Satış prefix</label>
          <input name="invoice_prefix" class="form-control" value="<?= $v('invoice_prefix') ?>">
        </div>
        <div class="col-6">
          <label class="form-label">Alış prefix</label>
          <input name="purchase_invoice_prefix" class="form-control" value="<?= $v('purchase_invoice_prefix') ?>">
        </div>
        <div class="col-6">
          <label class="form-label">Teklif prefix</label>
          <input name="quote_prefix" class="form-control" value="<?= $v('quote_prefix') ?>">
        </div>
        <div class="col-6">
          <label class="form-label">Varsayılan KDV</label>
          <input name="default_vat_rate" type="number" step="0.01" class="form-control" value="<?= $v('default_vat_rate', '20') ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Şirket rengi</label>
          <div class="company-palette">
            <?php foreach ($themeColors as $key => $color): ?>
              <input type="radio" name="theme_color" value="<?= htmlspecialchars($key) ?>" id="theme_<?= htmlspecialchars($key) ?>" <?= $selectedTheme === $key ? 'checked' : '' ?>>
              <label for="theme_<?= htmlspecialchars($key) ?>" title="<?= htmlspecialchars($color['label']) ?>" style="--swatch: <?= htmlspecialchars($color['hex']) ?>;">
                <span></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-6">
          <label class="form-label">Mali yıl başlangıç</label>
          <input name="fiscal_year_start_month" type="number" min="1" max="12" class="form-control" value="<?= $v('fiscal_year_start_month', '1') ?>">
        </div>
        <div class="col-6">
          <label class="form-label">Mali yıl bitiş</label>
          <input name="fiscal_year_end_month" type="number" min="1" max="12" class="form-control" value="<?= $v('fiscal_year_end_month', '12') ?>">
        </div>
        <div class="col-12 form-check ms-2">
          <input class="form-check-input" type="checkbox" name="stock_tracking_enabled" value="1" id="stock" <?= (int)($settings['stock_tracking_enabled'] ?? 1) === 1 ? 'checked' : '' ?>>
          <label class="form-check-label" for="stock">Stok takibi aktif</label>
        </div>
        <div class="col-12 form-check ms-2">
          <input class="form-check-input" type="checkbox" name="e_invoice_enabled" value="1" id="einvoice" <?= (int)($settings['e_invoice_enabled'] ?? 0) === 1 ? 'checked' : '' ?>>
          <label class="form-check-label" for="einvoice">E-fatura aktif</label>
        </div>
        <div class="col-12 form-check ms-2">
          <input class="form-check-input" type="checkbox" name="is_storefront_source" value="1" id="storefront" <?= (int)($settings['is_storefront_source'] ?? 0) === 1 ? 'checked' : '' ?>>
          <label class="form-check-label" for="storefront">Vitrin şirketi (ürünler nymagro.com ile eşitlensin)</label>
          <div class="form-text">Yalnızca bir şirket vitrin şirketi olabilir; burada işaretlerseniz başka bir şirkette işaretliyse oradaki otomatik kaldırılır.</div>
        </div>
      </div>
    </div>
  </div>
</form>
