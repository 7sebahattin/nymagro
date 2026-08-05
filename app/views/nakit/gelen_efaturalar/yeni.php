<style>
.panel{background:#fff;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;max-width:1100px}.panel h5{margin:0;background:#2c3e6b;color:#fff;padding:11px 14px;font-size:14px}.body{padding:16px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}label{font-size:12px;font-weight:700;color:#475569}input,select,textarea{width:100%;border:1px solid #cbd5e1;border-radius:4px;padding:7px;font-size:13px}.full{grid-column:1/-1}.btn{border:0;border-radius:4px;padding:8px 14px;font-weight:700;color:#fff;background:#5cb85c;text-decoration:none}.btn.gray{background:#64748b}@media(max-width:900px){.grid{grid-template-columns:1fr 1fr}}@media(max-width:600px){.grid{grid-template-columns:1fr}}
</style>
<?php if (!empty($flash)): ?><div class="alert alert-danger mb-3"><?= htmlspecialchars($flash['mesaj']) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>/nakit/gelen-e-faturalar/yeni" class="panel">
  <h5>Yeni Gelen Fatura / Gider</h5>
  <div class="body">
    <div class="grid">
      <div><label>Belge Türü</label><input name="belge_turu" value="Fatura"></div>
      <div><label>Düzenleme Tarihi</label><input type="date" name="duzenleme_tarihi" value="<?= date('Y-m-d') ?>"></div>
      <div><label>Vade Tarihi</label><input type="date" name="vade_tarihi"></div>
      <div><label>Fatura No</label><input name="fis_fatura_no"></div>
      <div><label>Tedarikçi</label><select name="tedarikci_id"><option value="">Eşleşmeden kaydet</option><?php foreach($tedarikciler as $t): ?><option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['unvan']) ?></option><?php endforeach; ?></select></div>
      <div><label>Tedarikçi / Çalışan Adı</label><input name="tedarikci_calisan_adi"></div>
      <div><label>Fatura İsmi</label><input name="fatura_ismi"></div>
      <div><label>Kategori</label><input name="kategori"></div>
      <div><label>Döviz Tipi</label><input name="doviz_tipi" value="TRY"></div>
      <div><label>Döviz Kuru</label><input name="doviz_kuru" value="1"></div>
      <div><label>Genel Toplam</label><input name="genel_toplam" required></div>
      <div><label>Genel Toplam TL</label><input name="genel_toplam_tl"></div>
      <div><label>Toplam Ödenen</label><input name="toplam_odenen" value="0"></div>
      <div><label>Toplam Ödenen TL</label><input name="toplam_odenen_tl" value="0"></div>
      <div><label>Son Ödeme Hesabı</label><input name="son_odeme_hesabi"></div>
      <div><label>Son Ödeme Tarihi</label><input type="date" name="son_odeme_tarihi"></div>
      <div><label>Toplam KDV</label><input name="toplam_kdv" value="0"></div>
      <div><label>Tevkifat</label><input name="toplam_tevkifat" value="0"></div>
      <div><label>Stopaj</label><input name="stopaj" value="0"></div>
      <div><label>PDF</label><input type="file" name="pdf" accept="application/pdf"></div>
      <div><label>Ürün/Hizmet</label><input name="urun_hizmet"></div>
      <div><label>Miktar</label><input name="miktar" value="1"></div>
      <div><label>Birim Fiyat</label><input name="birim_fiyati"></div>
      <div><label>Net Tutar TL</label><input name="net_tutar_tl"></div>
      <div class="full"><label>Ürün/Hizmet Açıklaması</label><textarea name="urun_hizmet_aciklamasi"></textarea></div>
      <div class="full"><label>Notlar</label><textarea name="notlar"></textarea></div>
    </div>
    <div style="display:flex;gap:8px;margin-top:14px">
      <button class="btn" type="submit">Kaydet</button>
      <a class="btn gray" href="<?= BASE_URL ?>/nakit/gelen-e-faturalar">Geri Dön</a>
    </div>
  </div>
</form>
