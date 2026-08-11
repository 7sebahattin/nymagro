<?php
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
$statusText = ['odenmedi'=>'Ödenmedi','kismi_odendi'=>'Kısmi Ödendi','odendi'=>'Ödendi'];
$dueText = ['vade_yok'=>'Vade Yok','vadesi_gecti'=>'Vadesi Geçti','vadesi_yaklasiyor'=>'Vadesi Yaklaşıyor','vadesinde'=>'Vadesinde','odendi'=>'Ödendi'];
?>
<style>
.gef-btn{border:0;border-radius:4px;padding:8px 12px;font-size:12.5px;font-weight:700;color:#fff;text-decoration:none;background:#337ab7;display:inline-flex;gap:6px;align-items:center;cursor:pointer}.gef-btn.green{background:#5cb85c}.gef-btn.gray{background:#64748b}.gef-btn.red{background:#d9534f}
.gef-layout{display:grid;grid-template-columns:2fr 1fr;gap:14px}.panel{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;overflow:hidden}.panel h5{margin:0;background:#2c3e6b;color:#fff;padding:11px 14px;font-size:14px}.body{padding:14px}
.info{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.info div{background:var(--surface-2);border:1px solid var(--border);border-radius:5px;padding:9px}.info small{display:block;color:var(--muted);font-weight:700}.info b{font-size:14px}
table{width:100%;border-collapse:collapse}th{background:var(--surface-2);text-align:left;color:var(--text2);font-size:12px;padding:8px}td{border-bottom:1px solid var(--border);padding:8px;font-size:12.5px}.txt-r{text-align:right}
input,select,textarea{width:100%;border:1px solid var(--border2);border-radius:4px;padding:7px;font-size:13px;margin-bottom:8px}
@media(max-width:950px){.gef-layout,.info{grid-template-columns:1fr}}
</style>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : 'danger' ?> mb-3"><?= htmlspecialchars($flash['mesaj']) ?></div>
<?php endif; ?>

<div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
  <a class="gef-btn gray" href="<?= BASE_URL ?>/nakit/gelen-e-faturalar"><i class="fa-solid fa-rotate-left"></i> Geri Dön</a>
  <?php if (Rbac::currentUserCan('NAKIT_DELETE')): ?>
  <a class="gef-btn red" href="#" onclick="return nymPost('<?= BASE_URL ?>/nakit/gelen-e-faturalar/iptal/<?= (int)$fatura['id'] ?>', 'Fatura iptal/pasif yapılsın mı?')"><i class="fa-solid fa-ban"></i> İptal/Pasif Yap</a>
  <?php endif; ?>
</div>

<div class="gef-layout">
  <div class="panel">
    <h5>Gelen Fatura Detayı</h5>
    <div class="body">
      <div class="info">
        <div><small>Fatura No</small><b><?= htmlspecialchars($fatura['fis_fatura_no'] ?? '-') ?></b></div>
        <div><small>Tedarikçi / Çalışan</small><b><?= htmlspecialchars($fatura['tedarikci_unvan'] ?: ($fatura['tedarikci_calisan_adi'] ?? '-')) ?></b></div>
        <div><small>Kategori</small><b><?= htmlspecialchars($fatura['kategori'] ?? '-') ?></b></div>
        <div><small>Düzenleme</small><b><?= $fatura['duzenleme_tarihi'] ? date('d.m.Y', strtotime($fatura['duzenleme_tarihi'])) : '-' ?></b></div>
        <div><small>Vade</small><b><?= $fatura['vade_tarihi'] ? date('d.m.Y', strtotime($fatura['vade_tarihi'])) : '-' ?></b></div>
        <div><small>Döviz</small><b><?= htmlspecialchars($fatura['doviz_tipi']) ?> / <?= $fmt($fatura['doviz_kuru']) ?></b></div>
        <div><small>Genel Toplam TL</small><b><?= $fmt($fatura['genel_toplam_tl']) ?> TL</b></div>
        <div><small>Ödenen TL</small><b><?= $fmt($fatura['toplam_odenen_tl']) ?> TL</b></div>
        <div><small>Kalan TL</small><b><?= $fmt($fatura['kalan_tutar_tl']) ?> TL</b></div>
        <div><small>Ödeme Durumu</small><b><?= $statusText[$fatura['odeme_durumu']] ?? $fatura['odeme_durumu'] ?></b></div>
        <div><small>Vade Durumu</small><b><?= $dueText[$fatura['vade_durumu']] ?? $fatura['vade_durumu'] ?></b></div>
        <div><small>PDF</small><b><?= !empty($fatura['pdf_path']) ? '<a target="_blank" href="' . BASE_URL . '/' . htmlspecialchars($fatura['pdf_path']) . '">Görüntüle</a>' : 'PDF Eksik' ?></b></div>
      </div>

      <h5 style="margin-top:16px;background:#64748b">Fatura Kalemleri</h5>
      <div style="overflow-x:auto">
        <table>
          <thead><tr><th>Ürün/Hizmet</th><th>Kod</th><th>Açıklama</th><th class="txt-r">Miktar</th><th class="txt-r">Birim</th><th class="txt-r">KDV</th><th class="txt-r">Net TL</th></tr></thead>
          <tbody>
          <?php foreach ($kalemler as $k): ?>
            <tr><td><?= htmlspecialchars($k['urun_hizmet'] ?? '') ?></td><td><?= htmlspecialchars($k['urun_hizmet_kodu'] ?? '') ?></td><td><?= htmlspecialchars($k['urun_hizmet_aciklamasi'] ?? '') ?></td><td class="txt-r"><?= $fmt($k['miktar']) ?></td><td class="txt-r"><?= $fmt($k['birim_fiyati']) ?></td><td class="txt-r"><?= $fmt($k['kdv_tutari']) ?></td><td class="txt-r"><strong><?= $fmt($k['net_tutar_tl']) ?></strong></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <h5 style="margin-top:16px;background:#64748b">Ödemeler</h5>
      <table>
        <thead><tr><th>Tarih</th><th>Hesap</th><th>Açıklama</th><th class="txt-r">Tutar TL</th></tr></thead>
        <tbody>
        <?php if (empty($odemeler)): ?><tr><td colspan="4">Ödeme kaydı yok.</td></tr><?php endif; ?>
        <?php foreach ($odemeler as $o): ?><tr><td><?= $o['odeme_tarihi'] ? date('d.m.Y', strtotime($o['odeme_tarihi'])) : '-' ?></td><td><?= htmlspecialchars($o['odeme_hesabi'] ?? '') ?></td><td><?= htmlspecialchars($o['aciklama'] ?? '') ?></td><td class="txt-r"><?= $fmt($o['odeme_tutari_tl']) ?></td></tr><?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div>
    <?php if (Rbac::currentUserCan('NAKIT_CREATE')): ?>
    <div class="panel">
      <h5>PDF Yükle</h5>
      <div class="body">
        <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>/nakit/gelen-e-faturalar/pdf-yukle/<?= (int)$fatura['id'] ?>">
          <input type="file" name="pdf" accept="application/pdf" required>
          <button class="gef-btn" type="submit">PDF Yükle</button>
        </form>
      </div>
    </div>
    <div class="panel" style="margin-top:14px">
      <h5>Ödeme Ekle</h5>
      <div class="body">
        <form method="post" action="<?= BASE_URL ?>/nakit/gelen-e-faturalar/odeme-ekle/<?= (int)$fatura['id'] ?>">
          <input type="date" name="odeme_tarihi" value="<?= date('Y-m-d') ?>">
          <input type="text" name="odeme_tutari_tl" placeholder="Ödeme tutarı TL" value="<?= $fmt($fatura['kalan_tutar_tl']) ?>">
          <select name="odeme_yontemi"><option value="">Ödeme Yöntemi Seçiniz</option><?php foreach (['Nakit', 'Havale/EFT', 'Kredi Kartı', 'Çek', 'Senet', 'Virman'] as $oy): ?><option value="<?= $oy ?>"><?= $oy ?></option><?php endforeach; ?></select>
          <select name="kasa_banka_hesap_id"><option value="">Kasa/Banka seçmeden kaydet</option><?php foreach($hesaplar as $h): ?><option value="<?= (int)$h['id'] ?>"><?= htmlspecialchars($h['hesap_adi']) ?></option><?php endforeach; ?></select>
          <input type="text" name="odeme_hesabi" placeholder="Ödeme hesabı açıklaması">
          <textarea name="aciklama" placeholder="Açıklama"></textarea>
          <button class="gef-btn green" type="submit">Ödemeyi Kaydet</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
    <div class="panel" style="margin-top:14px">
      <h5>Notlar</h5>
      <div class="body">
        <?php if (Rbac::currentUserCan('NAKIT_CREATE')): ?>
        <form method="post" action="<?= BASE_URL ?>/nakit/gelen-e-faturalar/not-ekle/<?= (int)$fatura['id'] ?>">
          <textarea name="not_metni" placeholder="Not ekle"></textarea>
          <button class="gef-btn gray" type="submit">Not Ekle</button>
        </form>
        <?php endif; ?>
        <?php foreach ($notlar as $n): ?><div style="border-top:1px solid var(--border);padding-top:8px;margin-top:8px;font-size:12px"><?= htmlspecialchars($n['not_metni']) ?><br><small><?= htmlspecialchars($n['created_at']) ?></small></div><?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
