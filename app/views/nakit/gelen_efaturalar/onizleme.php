<?php
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
$statusText = ['odenmedi'=>'Ödenmedi','kismi_odendi'=>'Kısmi Ödendi','odendi'=>'Ödendi'];
?>
<style>
.gef-summary{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:14px}.gef-card{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;padding:12px}.gef-card b{display:block;font-size:18px}
.gef-btn{border:0;border-radius:4px;padding:8px 12px;font-size:12.5px;font-weight:700;color:#fff;text-decoration:none;background:#337ab7;display:inline-flex;gap:6px;align-items:center;cursor:pointer}.gef-btn.green{background:#5cb85c}.gef-btn.gray{background:#64748b}
.gef-table{width:100%;border-collapse:collapse;min-width:1320px}.gef-table th{background:#2c3e6b;color:#fff;font-size:11.5px;text-align:left;padding:8px}.gef-table td{border-bottom:1px solid var(--border);padding:7px;font-size:12px;vertical-align:top}.txt-r{text-align:right}
.badge{display:inline-block;border-radius:4px;padding:3px 7px;font-size:11px;font-weight:700}.b-red{background:rgba(231,76,60,.15);color:var(--danger)}.b-green{background:rgba(46,204,113,.15);color:var(--success)}.b-orange{background:rgba(243,156,18,.17);color:#9a3412}.b-gray{background:var(--surface-2);color:var(--text2)}
.mini{width:120px;border:1px solid var(--border2);border-radius:4px;padding:5px;font-size:12px}.wide{width:180px}.warn{color:#b45309;font-size:11px;margin-top:4px}.lines{background:var(--surface-2);padding:8px;border-radius:5px;margin-top:6px}
@media(max-width:900px){.gef-summary{grid-template-columns:1fr 1fr}}
</style>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : 'danger' ?> mb-3"><?= htmlspecialchars($flash['mesaj']) ?></div>
<?php endif; ?>

<div class="gef-summary">
  <div class="gef-card">Dosya <b><?= htmlspecialchars($preview['dosya_adi']) ?></b></div>
  <div class="gef-card">Satır <b><?= (int)$preview['toplam_satir'] ?></b></div>
  <div class="gef-card">Fatura <b><?= (int)$preview['bulunan_fatura_sayisi'] ?></b></div>
  <div class="gef-card">Kalem <b><?= (int)$preview['bulunan_kalem_sayisi'] ?></b></div>
  <div class="gef-card">Duplicate <b><?= (int)$preview['duplicate_fatura'] ?></b></div>
</div>

<form method="post" action="<?= BASE_URL ?>/nakit/gelen-e-faturalar/kaydet">
  <div style="display:flex;gap:8px;margin-bottom:12px">
    <button class="gef-btn green" type="submit"><i class="fa-solid fa-check"></i> Seçili İşlemleri Kaydet</button>
    <a class="gef-btn gray" href="<?= BASE_URL ?>/nakit/gelen-e-faturalar/toplu-yukle"><i class="fa-solid fa-rotate-left"></i> Geri Dön</a>
  </div>
  <div style="overflow-x:auto;background:var(--card-bg);border:1px solid var(--border);border-radius:6px">
    <table class="gef-table">
      <thead><tr>
        <th>İşlem</th><th>Durum</th><th>Belge Türü</th><th>Düzenleme</th><th>Vade</th><th>Tedarikçi / Çalışan</th><th>Tedarikçi Eşleştir</th><th>Fatura İsmi</th><th>Kategori</th><th>Döviz</th><th class="txt-r">Genel</th><th class="txt-r">Genel TL</th><th class="txt-r">Ödenen TL</th><th class="txt-r">Kalan TL</th><th>Fatura No</th><th class="txt-r">KDV</th><th>Kalem</th><th>Ödeme</th><th>Not / Uyarı</th>
      </tr></thead>
      <tbody>
      <?php foreach (($preview['invoices'] ?? []) as $i => $f): ?>
        <tr>
          <td>
            <select class="mini" name="islem[<?= $i ?>]">
              <option value="kaydet" <?= ($f['islem'] ?? 'kaydet') === 'kaydet' ? 'selected' : '' ?>>Kaydet</option>
              <option value="atla" <?= ($f['islem'] ?? '') === 'atla' ? 'selected' : '' ?>>Atla</option>
            </select>
          </td>
          <td><span class="badge <?= !empty($f['is_duplicate']) ? 'b-orange' : 'b-green' ?>"><?= !empty($f['is_duplicate']) ? 'Duplicate' : 'Yeni' ?></span></td>
          <td><input class="mini" name="belge_turu[<?= $i ?>]" value="<?= htmlspecialchars($f['belge_turu'] ?? '') ?>"></td>
          <td><input class="mini" type="date" name="duzenleme_tarihi[<?= $i ?>]" value="<?= htmlspecialchars($f['duzenleme_tarihi'] ?? '') ?>"></td>
          <td><input class="mini" type="date" name="vade_tarihi[<?= $i ?>]" value="<?= htmlspecialchars($f['vade_tarihi'] ?? '') ?>"></td>
          <td><input class="mini wide" name="tedarikci_calisan_adi[<?= $i ?>]" value="<?= htmlspecialchars($f['tedarikci_calisan_adi'] ?? '') ?>"></td>
          <td>
            <select class="mini wide" name="tedarikci_id[<?= $i ?>]">
              <option value="">Eşleşmeden kaydet</option>
              <?php foreach ($tedarikciler as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= (int)($f['tedarikci_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['unvan']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input class="mini wide" name="fatura_ismi[<?= $i ?>]" value="<?= htmlspecialchars($f['fatura_ismi'] ?? '') ?>"></td>
          <td><input class="mini" name="kategori[<?= $i ?>]" value="<?= htmlspecialchars($f['kategori'] ?? '') ?>"></td>
          <td><input class="mini" name="doviz_tipi[<?= $i ?>]" value="<?= htmlspecialchars($f['doviz_tipi'] ?? '') ?>"><input class="mini" name="doviz_kuru[<?= $i ?>]" value="<?= $fmt($f['doviz_kuru'] ?? 1) ?>"></td>
          <td class="txt-r"><input class="mini" name="genel_toplam[<?= $i ?>]" value="<?= $fmt($f['genel_toplam'] ?? 0) ?>"></td>
          <td class="txt-r"><input class="mini" name="genel_toplam_tl[<?= $i ?>]" value="<?= $fmt($f['genel_toplam_tl'] ?? 0) ?>"></td>
          <td class="txt-r"><input class="mini" name="toplam_odenen_tl[<?= $i ?>]" value="<?= $fmt($f['toplam_odenen_tl'] ?? 0) ?>"><input type="hidden" name="toplam_odenen[<?= $i ?>]" value="<?= $fmt($f['toplam_odenen'] ?? 0) ?>"></td>
          <td class="txt-r"><strong><?= $fmt($f['kalan_tutar_tl'] ?? 0) ?></strong></td>
          <td><input class="mini" name="fis_fatura_no[<?= $i ?>]" value="<?= htmlspecialchars($f['fis_fatura_no'] ?? '') ?>"></td>
          <td class="txt-r"><input class="mini" name="toplam_kdv[<?= $i ?>]" value="<?= $fmt($f['toplam_kdv'] ?? 0) ?>"></td>
          <td>
            <?= count($f['kalemler'] ?? []) ?>
            <details class="lines"><summary>Kalemleri gör</summary>
              <?php foreach (($f['kalemler'] ?? []) as $k): ?>
                <div><?= htmlspecialchars($k['urun_hizmet'] ?: $k['urun_hizmet_aciklamasi']) ?> | Miktar: <?= $fmt($k['miktar']) ?> | Net TL: <?= $fmt($k['net_tutar_tl']) ?></div>
              <?php endforeach; ?>
            </details>
          </td>
          <td><span class="badge <?= ($f['odeme_durumu'] ?? '') === 'odendi' ? 'b-green' : (($f['odeme_durumu'] ?? '') === 'kismi_odendi' ? 'b-orange' : 'b-red') ?>"><?= $statusText[$f['odeme_durumu']] ?? $f['odeme_durumu'] ?></span></td>
          <td>
            <input class="mini wide" name="notlar[<?= $i ?>]" value="<?= htmlspecialchars($f['notlar'] ?? '') ?>" placeholder="Not">
            <?php foreach (($f['uyari'] ?? []) as $u): ?><div class="warn"><?= htmlspecialchars($u) ?></div><?php endforeach; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</form>
