<?php
/** @var array $belge, $taraflar, $kalemler, $vergiler, $uyarilar */
$h   = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
$mik = fn($n) => rtrim(rtrim(number_format((float)$n, 4, ',', '.'), '0'), ',');

$tipEtiket = ['efatura' => 'e-Fatura', 'earsiv' => 'e-Arşiv Fatura', 'eirsaliye' => 'e-İrsaliye'];
$gonderen  = $taraflar['gonderen'] ?? [];
$alici     = $taraflar['alici'] ?? [];
$irsaliyeMi = ($belge['belge_tipi'] ?? '') === 'eirsaliye';
?>
<style>
.ebl-btn{border:0;border-radius:4px;padding:8px 12px;font-size:12.5px;font-weight:700;color:#fff;text-decoration:none;display:inline-flex;gap:6px;align-items:center;cursor:pointer;background:#337ab7}
.ebl-btn.gray{background:#64748b}.ebl-btn.red{background:#d9534f}
.ebl-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.ebl-box{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;padding:16px;margin-bottom:14px}
.ebl-two{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.ebl-kv{display:grid;grid-template-columns:150px 1fr;gap:6px 12px;font-size:12.5px}
.ebl-kv dt{color:var(--muted);font-weight:700}
.ebl-kv dd{margin:0;color:var(--text2);word-break:break-word}
.ebl-table{width:100%;border-collapse:collapse;min-width:900px}
.ebl-table th{background:#2c3e6b;color:#fff;font-size:11.5px;text-align:left;padding:9px;white-space:nowrap}
.ebl-table td{font-size:12.5px;padding:8px 9px;border-bottom:1px solid var(--border)}
.ebl-table-wrap{overflow-x:auto}
.txt-r{text-align:right}
.ebl-uyari{background:rgba(243,156,18,.12);border:1px solid var(--warning);border-radius:6px;padding:12px 14px;margin-bottom:14px;font-size:13px;color:var(--text2)}
.ebl-uyari ul{margin:8px 0 0;padding-left:18px}
.badge{display:inline-block;border-radius:4px;padding:3px 7px;font-size:11px;font-weight:700}
.b-gray{background:var(--surface-2);color:var(--text2)}
.ebl-mini{font-size:11.5px;color:var(--muted)}
h5{margin:0 0 10px;font-size:13.5px;font-weight:800;color:var(--text)}
@media(max-width:900px){.ebl-two{grid-template-columns:1fr}}
</style>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : 'danger' ?> mb-3"><?= $h($flash['mesaj']) ?></div>
<?php endif; ?>

<div class="ebl-actions">
  <a class="ebl-btn gray" href="<?= BASE_URL ?>/ebelge"><i class="fa-solid fa-rotate-left"></i> Listeye Dön</a>
  <?php
  // Aktarım butonu ÇİFT İZİN ister: e-Belge güncelleme + alış faturası kesme.
  // Menüde/butonda gizlemek tek başına güvenlik değildir — backend'de
  // EBelgeController::aktarimYetkisiZorunlu() aynı kontrolü tekrar yapar.
  $aktarimYetkisi = class_exists('Rbac')
      && Rbac::currentUserCan('EBELGE_UPDATE')
      && Rbac::currentUserCan('ALIS_CREATE');
  ?>
  <?php if (!$irsaliyeMi && empty($belge['aktarilan_fatura_id'])): ?>
    <a class="ebl-btn" href="<?= BASE_URL ?>/ebelge/eslestir/<?= (int)$belge['id'] ?>"><i class="fa-solid fa-link"></i> Cari / Ürün Eşleştir</a>
    <?php if ($aktarimYetkisi && ($belge['durum'] ?? '') === 'aktarima_hazir'): ?>
      <a class="ebl-btn" style="background:#5cb85c" href="<?= BASE_URL ?>/ebelge/aktar/<?= (int)$belge['id'] ?>">
        <i class="fa-solid fa-file-invoice"></i> Faturaya Dönüştür
      </a>
    <?php endif; ?>
  <?php endif; ?>
  <a class="ebl-btn" href="<?= BASE_URL ?>/ebelge/indir/<?= (int)$belge['id'] ?>"><i class="fa-solid fa-download"></i> Ham XML indir</a>
  <?php if (empty($belge['aktarilan_fatura_id'])): ?>
    <a class="ebl-btn red" href="#" onclick="return nymPost('<?= BASE_URL ?>/ebelge/iptal/<?= (int)$belge['id'] ?>', 'Bu e-Belge reddedilsin mi? (Ham XML dosyası saklanmaya devam eder)')"><i class="fa-solid fa-ban"></i> Reddet</a>
  <?php endif; ?>
</div>

<?php if ($irsaliyeMi): ?>
  <div class="ebl-uyari">
    Bu bir <strong>e-İrsaliye</strong> belgesidir. İlk fazda e-İrsaliye yalnızca <strong>izleme amaçlı</strong>
    saklanır; sisteme aktarımı kapalıdır.
  </div>
<?php endif; ?>

<?php if (!empty($aktarilanFatura)): ?>
  <div class="ebl-box" style="border-left:4px solid var(--success)">
    <h5>Bu belge çekirdek sisteme aktarıldı</h5>
    <dl class="ebl-kv">
      <dt>Fatura no</dt><dd><strong><?= $h($aktarilanFatura['fatura_no']) ?></strong> <span class="ebl-mini">(#<?= (int)$aktarilanFatura['id'] ?>)</span></dd>
      <dt>Belge tipi</dt><dd><?= $aktarilanFatura['belge_tipi'] === 'iade_alis' ? 'Alış İadesi' : 'Alış Faturası' ?></dd>
      <dt>Fatura tarihi</dt><dd><?= $h(date('d.m.Y', strtotime((string)$aktarilanFatura['fatura_tarihi']))) ?></dd>
      <dt>Genel toplam</dt><dd><?= $fmt($aktarilanFatura['genel_toplam']) ?> TL</dd>
      <dt>Durum</dt><dd><?= $h($aktarilanFatura['durum']) ?></dd>
      <dt>Aktarım</dt><dd><?= $belge['aktarim_tarihi'] ? $h(date('d.m.Y H:i', strtotime((string)$belge['aktarim_tarihi']))) : '—' ?></dd>
    </dl>
    <div class="ebl-mini" style="margin-top:8px">
      Faturayı iptal etmek/düzenlemek için Alışlar modülünü kullanın. Bu belgeden ikinci bir fatura üretilemez.
    </div>
  </div>
<?php elseif (!empty($belge['aktarim_hatasi'])): ?>
  <div class="ebl-uyari">
    <strong>Son aktarım denemesi başarısız oldu — hiçbir kayıt oluşturulmadı.</strong>
    <div style="margin-top:6px"><?= $h($belge['aktarim_hatasi']) ?></div>
  </div>
<?php endif; ?>

<?php if (!empty($uyarilar)): ?>
  <div class="ebl-uyari">
    <strong>Doğrulama uyarıları</strong>
    <ul><?php foreach ($uyarilar as $u): ?><li><?= $h($u) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="ebl-box">
  <h5><?= $h($tipEtiket[$belge['belge_tipi']] ?? $belge['belge_tipi']) ?> · <?= $h($belge['belge_no']) ?></h5>
  <div class="ebl-two">
    <dl class="ebl-kv">
      <dt>ETTN (UUID)</dt><dd><?= $h($belge['belge_uuid']) ?></dd>
      <dt>Belge Tarihi</dt><dd><?= $h(date('d.m.Y', strtotime((string)$belge['belge_tarihi']))) ?><?= $belge['belge_saati'] ? ' ' . $h(substr((string)$belge['belge_saati'], 0, 5)) : '' ?></dd>
      <dt>Vade Tarihi</dt><dd><?= $belge['vade_tarihi'] ? $h(date('d.m.Y', strtotime((string)$belge['vade_tarihi']))) : '—' ?></dd>
      <dt>Senaryo / Tip</dt><dd><?= $h($belge['profil_id'] ?: '—') ?> / <?= $h($belge['fatura_tipi_kodu'] ?: '—') ?></dd>
      <dt>Para Birimi</dt><dd><?= $h($belge['para_birimi']) ?><?= $belge['kur'] !== null ? ' (kur: ' . $fmt($belge['kur']) . ')' : '' ?></dd>
    </dl>
    <dl class="ebl-kv">
      <dt>Satır Toplamı</dt><dd><?= $fmt($belge['satir_toplami']) ?></dd>
      <dt>İskonto</dt><dd><?= $fmt($belge['iskonto_toplami']) ?></dd>
      <dt>Matrah</dt><dd><?= $fmt($belge['matrah_toplami']) ?></dd>
      <dt>Vergi (KDV)</dt><dd><?= $fmt($belge['vergi_toplami']) ?></dd>
      <dt>Tevkifat</dt><dd><?= $fmt($belge['tevkifat_toplami']) ?></dd>
      <dt>Genel Toplam</dt><dd><strong><?= $fmt($belge['genel_toplam']) ?></strong></dd>
      <dt>Ödenecek</dt><dd><?= $fmt($belge['odenecek_tutar']) ?></dd>
    </dl>
  </div>
</div>

<div class="ebl-two">
  <div class="ebl-box">
    <h5>Gönderen</h5>
    <dl class="ebl-kv">
      <dt>Unvan</dt><dd><?= $h($gonderen['unvan'] ?? '—') ?></dd>
      <dt>VKN / TCKN</dt><dd><?= $h(($gonderen['vkn_tckn'] ?? '') ?: '—') ?> <span class="ebl-mini"><?= $h($gonderen['kimlik_semasi'] ?? '') ?></span></dd>
      <dt>Vergi Dairesi</dt><dd><?= $h($gonderen['vergi_dairesi'] ?? '—') ?></dd>
      <dt>Adres</dt><dd><?= $h(trim(($gonderen['adres'] ?? '') . ' ' . ($gonderen['ilce'] ?? '') . ' ' . ($gonderen['il'] ?? ''))) ?: '—' ?></dd>
      <dt>İletişim</dt><dd><?= $h($gonderen['telefon'] ?? '—') ?> · <?= $h($gonderen['eposta'] ?? '') ?></dd>
    </dl>
  </div>
  <div class="ebl-box">
    <h5>Alıcı</h5>
    <dl class="ebl-kv">
      <dt>Unvan</dt><dd><?= $h($alici['unvan'] ?? '—') ?></dd>
      <dt>VKN / TCKN</dt><dd><?= $h(($alici['vkn_tckn'] ?? '') ?: '—') ?> <span class="ebl-mini"><?= $h($alici['kimlik_semasi'] ?? '') ?></span></dd>
      <dt>Vergi Dairesi</dt><dd><?= $h($alici['vergi_dairesi'] ?? '—') ?></dd>
      <dt>Adres</dt><dd><?= $h(trim(($alici['adres'] ?? '') . ' ' . ($alici['ilce'] ?? '') . ' ' . ($alici['il'] ?? ''))) ?: '—' ?></dd>
    </dl>
  </div>
</div>

<div class="ebl-box">
  <h5>Kalemler (<?= count($kalemler) ?>)</h5>
  <div class="ebl-table-wrap">
    <table class="ebl-table">
      <thead>
        <tr>
          <th>#</th><th>Ürün / Hizmet</th><th>Alıcı Kodu</th><th>Barkod</th><th>Satıcı Kodu</th>
          <th class="txt-r">Miktar</th><th>Birim</th>
          <?php if (!$irsaliyeMi): ?>
            <th class="txt-r">Birim Fiyat</th><th class="txt-r">İskonto</th>
            <th class="txt-r">KDV %</th><th class="txt-r">KDV</th><th class="txt-r">Satır Tutarı</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($kalemler)): ?>
        <tr><td colspan="12" style="padding:24px;text-align:center;color:var(--muted)">Kalem yok.</td></tr>
      <?php else: foreach ($kalemler as $k): ?>
        <tr>
          <td><?= (int)$k['sira_no'] ?></td>
          <td><?= $h($k['urun_adi']) ?><?php if (!empty($k['aciklama'])): ?><div class="ebl-mini"><?= $h(mb_substr((string)$k['aciklama'], 0, 120)) ?></div><?php endif; ?></td>
          <td><?= $h($k['alici_urun_kodu'] ?: '—') ?></td>
          <td><?= $h($k['barkod'] ?: '—') ?></td>
          <td><?= $h($k['satici_urun_kodu'] ?: '—') ?></td>
          <td class="txt-r"><?= $mik($k['miktar']) ?></td>
          <td><?= $h($k['birim_kodu'] ?: '—') ?></td>
          <?php if (!$irsaliyeMi): ?>
            <td class="txt-r"><?= $fmt($k['birim_fiyat']) ?></td>
            <td class="txt-r"><?= $fmt($k['iskonto_tutari']) ?><?= (float)$k['iskonto_orani'] > 0 ? ' <span class="ebl-mini">(%' . $mik($k['iskonto_orani']) . ')</span>' : '' ?></td>
            <td class="txt-r"><?= $mik($k['kdv_orani']) ?></td>
            <td class="txt-r"><?= $fmt($k['kdv_tutari']) ?></td>
            <td class="txt-r"><strong><?= $fmt($k['satir_tutari']) ?></strong></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (!empty($vergiler)): ?>
  <div class="ebl-box">
    <h5>Vergi kırılımı</h5>
    <div class="ebl-mini" style="margin-bottom:8px">
      Tevkifat, ÖTV, ÖİV gibi kalemler burada eksiksiz saklanır. Çekirdek fatura sisteminde bu alanların
      karşılığı bulunmadığı için aktarımda matrah ve KDV tutarı olarak yansıtılacaktır.
    </div>
    <div class="ebl-table-wrap">
      <table class="ebl-table" style="min-width:640px">
        <thead><tr><th>Kapsam</th><th>Kod</th><th>Ad</th><th class="txt-r">Matrah</th><th class="txt-r">Oran %</th><th class="txt-r">Tutar</th><th>İstisna</th></tr></thead>
        <tbody>
        <?php foreach ($vergiler as $v): ?>
          <tr>
            <td><?= $v['kalem_id'] === null ? '<span class="badge b-gray">Belge</span>' : ('Kalem #' . (int)$v['sira_no']) ?></td>
            <td><?= $h($v['vergi_kodu'] ?: '—') ?></td>
            <td><?= $h($v['vergi_adi'] ?: '—') ?></td>
            <td class="txt-r"><?= $fmt($v['matrah']) ?></td>
            <td class="txt-r"><?= $mik($v['oran']) ?></td>
            <td class="txt-r"><?= $fmt($v['tutar']) ?></td>
            <td><?= $h($v['istisna_kodu'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<div class="ebl-box">
  <h5>Kaynak dosya</h5>
  <dl class="ebl-kv">
    <dt>Orijinal ad</dt><dd><?= $h($belge['orijinal_ad']) ?></dd>
    <dt>SHA-256</dt><dd style="font-family:monospace;font-size:11.5px"><?= $h($belge['dosya_hash']) ?></dd>
    <dt>Boyut</dt><dd><?= number_format((float)$belge['boyut'] / 1024, 1, ',', '.') ?> KB</dd>
    <dt>Yüklenme</dt><dd><?= $h(date('d.m.Y H:i', strtotime((string)$belge['yuklenme_tarihi']))) ?></dd>
  </dl>
</div>
