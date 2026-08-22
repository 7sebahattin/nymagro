<?php
/** @var array $belgeler, $ozetler, $paketler, $hataliDosyalar, $filtreler, $sonSonuclar */
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
$h   = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$tipEtiket = [
    'efatura'   => 'e-Fatura',
    'earsiv'    => 'e-Arşiv',
    'eirsaliye' => 'e-İrsaliye',
];
$durumEtiket = [
    'yeni'             => 'Yeni',
    'dogrulandi'       => 'Uyarılı',
    'eslesme_bekliyor' => 'Eşleşme Bekliyor',
    'izleme'           => 'İzleme (aktarım kapalı)',
    'aktarima_hazir'   => 'Aktarıma Hazır',
    'aktarildi'        => 'Aktarıldı',
    'hatali'           => 'Hatalı',
    'reddedildi'       => 'Reddedildi',
];
$durumRenk = [
    'eslesme_bekliyor' => 'b-yellow',
    'dogrulandi'       => 'b-orange',
    'izleme'           => 'b-gray',
    'aktarildi'        => 'b-green',
    'hatali'           => 'b-red',
];
$url = function (array $ek = []) use ($filtreler) {
    $p = array_filter(array_merge($filtreler, $ek), fn($v) => $v !== '' && $v !== null);
    return BASE_URL . '/ebelge' . ($p ? '?' . http_build_query($p) : '');
};
?>
<style>
.ebl-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.ebl-btn{border:0;border-radius:4px;padding:8px 12px;font-size:12.5px;font-weight:700;color:#fff;text-decoration:none;display:inline-flex;align-items:center;gap:6px;cursor:pointer}
.ebl-btn.blue{background:#337ab7}.ebl-btn.gray{background:#64748b}
.ebl-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
.ebl-card{background:var(--card-bg);border:1px solid var(--border);border-left:4px solid #337ab7;border-radius:6px;padding:12px 14px}
.ebl-card .l{font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:700}
.ebl-card .v{font-size:17px;font-weight:800;color:var(--text);margin-top:4px}
.ebl-box{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;padding:12px;margin-bottom:14px}
.ebl-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:8px}
.ebl-grid label{font-size:11px;font-weight:700;color:var(--text2)}
.ebl-grid input,.ebl-grid select{width:100%;padding:7px;border:1px solid var(--border2);border-radius:4px;font-size:12px}
.ebl-table-wrap{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;overflow-x:auto}
.ebl-table{width:100%;border-collapse:collapse;min-width:1080px}
.ebl-table th{background:#2c3e6b;color:#fff;font-size:11.5px;text-align:left;padding:9px;white-space:nowrap}
.ebl-table td{font-size:12.5px;padding:8px 9px;border-bottom:1px solid var(--border);vertical-align:middle}
.txt-r{text-align:right}
.badge{display:inline-block;border-radius:4px;padding:3px 7px;font-size:11px;font-weight:700;white-space:nowrap}
.b-red{background:rgba(231,76,60,.15);color:var(--danger)}.b-orange{background:rgba(243,156,18,.17);color:var(--warning)}
.b-green{background:rgba(46,204,113,.15);color:var(--success)}.b-gray{background:var(--surface-2);color:var(--text2)}
.b-yellow{background:rgba(243,156,18,.15);color:var(--warning)}
.empty{padding:42px;text-align:center;color:var(--muted)}
.pag{display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--surface-2);border-top:1px solid var(--border)}
.pag a{padding:5px 9px;border:1px solid var(--border2);border-radius:4px;text-decoration:none;color:var(--text2)}
.ebl-mini{font-size:11.5px;color:var(--muted)}
.ebl-list{margin:0;padding-left:18px;font-size:12.5px;color:var(--text2)}
@media(max-width:1100px){.ebl-cards{grid-template-columns:repeat(2,1fr)}.ebl-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:650px){.ebl-cards,.ebl-grid{grid-template-columns:1fr}}
</style>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : ($flash['tip'] === 'warning' ? 'warning' : 'danger') ?> mb-3"><?= $h($flash['mesaj']) ?></div>
<?php endif; ?>

<?php if (empty($semaHazir)): ?>
  <div class="alert alert-danger mb-3">
    e-Belge tabloları oluşturulamadı. Veritabanı kullanıcısının <code>CREATE TABLE</code> yetkisi olduğundan emin olun;
    teknik ayrıntı sunucu hata günlüğüne yazıldı.
  </div>
<?php endif; ?>

<div class="ebl-actions">
  <a class="ebl-btn blue" href="<?= BASE_URL ?>/ebelge/yukle"><i class="fa-solid fa-file-import"></i> XML / ZIP Yükle</a>
  <a class="ebl-btn gray" href="<?= BASE_URL ?>/ebelge"><i class="fa-solid fa-rotate-left"></i> Filtreleri Temizle</a>
</div>

<?php if (!empty($sonSonuclar)): ?>
  <div class="ebl-box">
    <strong style="font-size:13px">Son yükleme sonucu (dosya bazlı)</strong>
    <ul class="ebl-list" style="margin-top:8px">
      <?php foreach ($sonSonuclar as $s): ?>
        <li>
          <?php
          $rozet = match ($s['durum']) {
              'parse_edildi' => 'b-green',
              'mukerrer'     => 'b-yellow',
              default        => 'b-red',
          };
          $etiket = match ($s['durum']) {
              'parse_edildi' => 'Alındı',
              'mukerrer'     => 'Mükerrer',
              default        => 'Hata',
          };
          ?>
          <span class="badge <?= $rozet ?>"><?= $etiket ?></span>
          <strong><?= $h($s['dosya_adi']) ?></strong> — <?= $h($s['mesaj']) ?>
          <?php if (!empty($s['belge_id'])): ?>
            · <a href="<?= BASE_URL ?>/ebelge/detay/<?= (int)$s['belge_id'] ?>">belgeyi aç</a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="ebl-cards">
  <div class="ebl-card"><div class="l">Belge</div><div class="v"><?= (int)($ozetler['belge_sayisi'] ?? 0) ?></div></div>
  <div class="ebl-card"><div class="l">Toplam Tutar</div><div class="v"><?= $fmt($ozetler['toplam_tutar'] ?? 0) ?></div></div>
  <div class="ebl-card"><div class="l">Eşleşme Bekleyen</div><div class="v"><?= (int)($ozetler['eslesme_bekleyen'] ?? 0) ?></div></div>
  <div class="ebl-card"><div class="l">Uyarılı</div><div class="v"><?= (int)($ozetler['uyarili'] ?? 0) ?></div></div>
</div>

<form method="get" action="<?= BASE_URL ?>/ebelge" class="ebl-box">
  <div class="ebl-grid">
    <div><label>Ara (no / ETTN / VKN)</label><input type="text" name="ara" value="<?= $h($filtreler['ara'] ?? '') ?>"></div>
    <div><label>Belge Tipi</label>
      <select name="belge_tipi">
        <option value="">Hepsi</option>
        <?php foreach ($tipEtiket as $k => $v): ?>
          <option value="<?= $k ?>" <?= ($filtreler['belge_tipi'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label>Durum</label>
      <select name="durum">
        <option value="">Hepsi</option>
        <?php foreach ($durumEtiket as $k => $v): ?>
          <option value="<?= $k ?>" <?= ($filtreler['durum'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label>Başlangıç</label><input type="date" name="baslangic" value="<?= $h($filtreler['baslangic'] ?? '') ?>"></div>
    <div><label>Bitiş</label><input type="date" name="bitis" value="<?= $h($filtreler['bitis'] ?? '') ?>"></div>
    <div style="display:flex;align-items:flex-end"><button class="ebl-btn blue" type="submit"><i class="fa-solid fa-filter"></i> Filtrele</button></div>
  </div>
</form>

<div class="ebl-table-wrap">
  <table class="ebl-table">
    <thead>
      <tr>
        <th>Tarih</th><th>Tip</th><th>Belge No</th><th>Gönderen</th><th>VKN/TCKN</th>
        <th class="txt-r">Matrah</th><th class="txt-r">KDV</th><th class="txt-r">Genel Toplam</th>
        <th>Döviz</th><th>Durum</th><th>Kalem</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($belgeler)): ?>
      <tr><td colspan="12" class="empty">Henüz e-Belge yok. “XML / ZIP Yükle” ile başlayın.</td></tr>
    <?php else: foreach ($belgeler as $b): ?>
      <tr>
        <td><?= $h(date('d.m.Y', strtotime((string)$b['belge_tarihi']))) ?></td>
        <td><span class="badge b-gray"><?= $h($tipEtiket[$b['belge_tipi']] ?? $b['belge_tipi']) ?></span></td>
        <td><strong><?= $h($b['belge_no']) ?></strong><div class="ebl-mini"><?= $h(mb_substr((string)$b['belge_uuid'], 0, 18)) ?>…</div></td>
        <td><?= $h($b['gonderen_unvan'] ?? '—') ?></td>
        <td><?= $h($b['gonderen_vkn_tckn'] ?: '—') ?></td>
        <td class="txt-r"><?= $fmt($b['matrah_toplami']) ?></td>
        <td class="txt-r"><?= $fmt($b['vergi_toplami']) ?></td>
        <td class="txt-r"><strong><?= $fmt($b['genel_toplam']) ?></strong></td>
        <td><?= $h($b['para_birimi']) ?></td>
        <td><span class="badge <?= $durumRenk[$b['durum']] ?? 'b-gray' ?>"><?= $h($durumEtiket[$b['durum']] ?? $b['durum']) ?></span></td>
        <td><?= (int)$b['kalem_sayisi'] ?></td>
        <td style="white-space:nowrap">
          <a class="ebl-btn gray" href="<?= BASE_URL ?>/ebelge/detay/<?= (int)$b['id'] ?>">Detay</a>
          <?php if ($b['belge_tipi'] !== 'eirsaliye' && empty($b['aktarilan_fatura_id'])): ?>
            <a class="ebl-btn blue" href="<?= BASE_URL ?>/ebelge/eslestir/<?= (int)$b['id'] ?>">Eşleştir</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
  <?php if (($sayfaSay ?? 1) > 1): ?>
    <div class="pag">
      <span class="ebl-mini">Toplam <?= (int)$toplam ?> belge · sayfa <?= (int)$sayfa ?>/<?= (int)$sayfaSay ?></span>
      <span>
        <?php if ($sayfa > 1): ?><a href="<?= $url(['sayfa' => $sayfa - 1]) ?>">← Önceki</a><?php endif; ?>
        <?php if ($sayfa < $sayfaSay): ?><a href="<?= $url(['sayfa' => $sayfa + 1]) ?>">Sonraki →</a><?php endif; ?>
      </span>
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($hataliDosyalar)): ?>
  <div class="ebl-box" style="margin-top:14px;border-left:4px solid var(--danger)">
    <strong style="font-size:13px">Ayrıştırılamayan dosyalar</strong>
    <div class="ebl-mini" style="margin:4px 0 8px">Bu dosyalar diskte saklanıyor; sorun giderildikten sonra yeniden yüklenebilirler.</div>
    <ul class="ebl-list">
      <?php foreach ($hataliDosyalar as $d): ?>
        <li><strong><?= $h($d['orijinal_ad']) ?></strong> — <?= $h($d['parse_hatasi']) ?>
          <span class="ebl-mini">(<?= $h(date('d.m.Y H:i', strtotime((string)$d['created_at']))) ?>)</span></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<?php if (!empty($paketler)): ?>
  <div class="ebl-box" style="margin-top:14px">
    <strong style="font-size:13px">Son yüklemeler</strong>
    <ul class="ebl-list" style="margin-top:8px">
      <?php foreach ($paketler as $p): ?>
        <li>
          <a href="<?= $url(['paket_id' => (int)$p['id'], 'sayfa' => '']) ?>"><?= $h($p['paket_adi']) ?></a>
          — <?= (int)$p['basarili_dosya'] ?> alındı, <?= (int)$p['mukerrer_dosya'] ?> mükerrer, <?= (int)$p['hatali_dosya'] ?> hatalı
          <span class="ebl-mini">(<?= $h(date('d.m.Y H:i', strtotime((string)$p['created_at']))) ?> · <?= $h($p['yukleyen_adi'] ?? 'sistem') ?>)</span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
