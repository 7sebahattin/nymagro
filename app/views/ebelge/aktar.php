<?php
/** @var array $onizleme, $taraflar, $depolar */
$h   = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
$mik = fn($n) => rtrim(rtrim(number_format((float)$n, 4, ',', '.'), '0'), ',');

$belge    = $onizleme['belge'];
$kalemler = $onizleme['kalemler'];
$hesap    = $onizleme['karsilastirma']['hesaplanan'];
$fark     = (float)$onizleme['karsilastirma']['fark'];
$gonderen = $taraflar['gonderen'] ?? [];
$hedefTipi = $onizleme['belge_tipi'];
$temel = BASE_URL . '/ebelge/aktar/' . (int)$belge['id'];
?>
<style>
.ebl-btn{border:0;border-radius:4px;padding:8px 13px;font-size:12.5px;font-weight:700;color:#fff;text-decoration:none;display:inline-flex;gap:6px;align-items:center;cursor:pointer;background:#337ab7}
.ebl-btn.gray{background:#64748b}.ebl-btn.green{background:#5cb85c}
.ebl-btn[disabled]{opacity:.5;cursor:not-allowed}
.ebl-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.ebl-box{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;padding:16px;margin-bottom:14px}
.ebl-two{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.ebl-kv{display:grid;grid-template-columns:150px 1fr;gap:5px 12px;font-size:12.5px}
.ebl-kv dt{color:var(--muted);font-weight:700}.ebl-kv dd{margin:0;color:var(--text2);word-break:break-word}
.ebl-table{width:100%;border-collapse:collapse;min-width:860px}
.ebl-table th{background:#2c3e6b;color:#fff;font-size:11.5px;text-align:left;padding:9px;white-space:nowrap}
.ebl-table td{font-size:12.5px;padding:8px 9px;border-bottom:1px solid var(--border)}
.ebl-table tfoot td{font-weight:800;background:var(--surface-2)}
.ebl-table-wrap{overflow-x:auto}
.txt-r{text-align:right}
.ebl-hata{background:rgba(231,76,60,.12);border:1px solid var(--danger);border-radius:6px;padding:12px 14px;margin-bottom:14px;font-size:13px;color:var(--text2)}
.ebl-uyari{background:rgba(243,156,18,.12);border:1px solid var(--warning);border-radius:6px;padding:12px 14px;margin-bottom:14px;font-size:13px;color:var(--text2)}
.ebl-ok{background:rgba(46,204,113,.12);border:1px solid var(--success);border-radius:6px;padding:12px 14px;margin-bottom:14px;font-size:13px;color:var(--text2)}
.ebl-hata ul,.ebl-uyari ul{margin:8px 0 0;padding-left:18px}
.ebl-onay{display:block;margin:8px 0;font-size:12.5px;color:var(--text2);line-height:1.5}
.badge{display:inline-block;border-radius:4px;padding:3px 7px;font-size:11px;font-weight:700}
.b-gray{background:var(--surface-2);color:var(--text2)}.b-green{background:rgba(46,204,113,.15);color:var(--success)}
.ebl-mini{font-size:11.5px;color:var(--muted)}
h5{margin:0 0 10px;font-size:13.5px;font-weight:800;color:var(--text)}
select{padding:7px;border:1px solid var(--border2);border-radius:4px;font-size:12.5px}
@media(max-width:900px){.ebl-two{grid-template-columns:1fr}}
</style>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : 'danger' ?> mb-3"><?= $h($flash['mesaj']) ?></div>
<?php endif; ?>

<div class="ebl-actions">
  <a class="ebl-btn gray" href="<?= BASE_URL ?>/ebelge/detay/<?= (int)$belge['id'] ?>"><i class="fa-solid fa-rotate-left"></i> Belge Detayı</a>
  <a class="ebl-btn gray" href="<?= BASE_URL ?>/ebelge/eslestir/<?= (int)$belge['id'] ?>"><i class="fa-solid fa-link"></i> Eşleştirmeye Dön</a>
</div>

<?php if (!empty($onizleme['engeller'])): ?>
  <div class="ebl-hata">
    <strong>Bu belge şu an aktarılamaz</strong>
    <ul><?php foreach ($onizleme['engeller'] as $e): ?><li><?= $h($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php else: ?>
  <div class="ebl-ok">
    <strong>Aktarıma hazır.</strong> Aşağıdaki özet, çekirdek sistemde oluşacak faturanın birebir karşılığıdır.
    Kayıt tek bir işlem (transaction) içinde yapılır; en ufak hatada hiçbir şey yazılmaz.
  </div>
<?php endif; ?>

<?php if (!empty($onizleme['uyarilar'])): ?>
  <div class="ebl-uyari">
    <strong>Dönüşüm notları</strong>
    <ul><?php foreach ($onizleme['uyarilar'] as $u): ?><li><?= $h($u) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="ebl-box">
  <h5>Oluşacak fatura</h5>
  <div class="ebl-two">
    <dl class="ebl-kv">
      <dt>Belge tipi</dt>
      <dd>
        <span class="badge b-gray"><?= $hedefTipi === 'iade_alis' ? 'Alış İadesi' : 'Alış Faturası' ?></span>
        <?php if ($hedefTipi === 'iade_alis'): ?>
          <div class="ebl-mini">XML'deki fatura tipi IADE olduğu için alış iadesi olarak aktarılır: stok ÇIKIŞI yazılır ve cari borcu azalır.</div>
        <?php endif; ?>
      </dd>
      <dt>Fatura no</dt>
      <dd>
        <em>kayıt anında alış serisinden üretilecek</em>
        <div class="ebl-mini">Tedarikçinin numarası (<?= $h($belge['belge_no']) ?>) açıklama alanına yazılır — belge numarası çakışmasını önlemek için.</div>
      </dd>
      <dt>Cari</dt><dd><strong><?= $h($gonderen['unvan'] ?? '—') ?></strong> <span class="ebl-mini">(eşleşen cari #<?= (int)$belge['eslesen_cari_id'] ?>)</span></dd>
      <dt>Fatura tarihi</dt><dd><?= $h(date('d.m.Y', strtotime((string)$belge['belge_tarihi']))) ?></dd>
      <dt>Vade tarihi</dt><dd><?= $belge['vade_tarihi'] ? $h(date('d.m.Y', strtotime((string)$belge['vade_tarihi']))) : '—' ?></dd>
    </dl>
    <dl class="ebl-kv">
      <dt>Para birimi</dt><dd><?= $h($belge['para_birimi']) ?><?= (float)$onizleme['kur'] !== 1.0 ? ' · kur ' . $fmt($onizleme['kur']) : '' ?></dd>
      <dt>Ara toplam</dt><dd><?= $fmt($hesap['ara_toplam']) ?> TL</dd>
      <dt>İskonto</dt><dd><?= $fmt($hesap['iskonto_tutari']) ?> TL</dd>
      <dt>KDV</dt><dd><?= $fmt($hesap['kdv_tutari']) ?> TL</dd>
      <dt>Genel toplam</dt><dd><strong><?= $fmt($hesap['genel_toplam']) ?> TL</strong></dd>
      <dt>Belgedeki tutar</dt>
      <dd>
        <?= $fmt($onizleme['belge_toplam_tl']) ?> TL
        <?php if (abs($fark) > 0.001): ?>
          <span class="ebl-mini" style="color:var(--warning)">(fark: <?= $fmt($fark) ?> TL)</span>
        <?php endif; ?>
      </dd>
      <dt>Kalem sayısı</dt><dd><?= count($kalemler) ?></dd>
    </dl>
  </div>
</div>

<div class="ebl-box">
  <h5>Aktarılacak kalemler</h5>
  <div class="ebl-table-wrap">
    <table class="ebl-table">
      <thead>
        <tr>
          <th>#</th><th>Ürün / Hizmet</th><th>Stok etkisi</th>
          <th class="txt-r">Miktar</th><th>Birim</th>
          <th class="txt-r">Birim fiyat (TL)</th><th class="txt-r">İskonto %</th>
          <th class="txt-r">KDV %</th><th class="txt-r">Satır toplamı</th>
        </tr>
      </thead>
      <tbody>
      <?php $sira = 0; foreach ($kalemler as $k):
          $sira++;
          $ara = (float)$k['miktar'] * (float)$k['birim_fiyat'];
          $isk = $ara * ((float)$k['iskonto_orani'] / 100);
          $kdv = ($ara - $isk) * ((float)$k['kdv_orani'] / 100);
      ?>
        <tr>
          <td><?= $sira ?></td>
          <td><?= $h($k['urun_adi']) ?>
            <?php if (!empty($k['aciklama'])): ?><div class="ebl-mini"><?= $h($k['aciklama']) ?></div><?php endif; ?>
          </td>
          <td>
            <?php if ($k['urun_id'] === null): ?>
              <span class="badge b-gray">Stok hareketi yok</span>
              <div class="ebl-mini">Üründüz gider/hizmet kalemi</div>
            <?php else: ?>
              <span class="badge b-green"><?= $hedefTipi === 'iade_alis' ? 'Stok çıkışı' : 'Stok girişi' ?></span>
              <div class="ebl-mini">ürün #<?= (int)$k['urun_id'] ?></div>
            <?php endif; ?>
          </td>
          <td class="txt-r"><?= $mik($k['miktar']) ?></td>
          <td><?= $h($k['birim']) ?></td>
          <td class="txt-r"><?= $fmt($k['birim_fiyat']) ?></td>
          <td class="txt-r"><?= $mik($k['iskonto_orani']) ?></td>
          <td class="txt-r"><?= $mik($k['kdv_orani']) ?></td>
          <td class="txt-r"><strong><?= $fmt($ara - $isk + $kdv) ?></strong></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="8" class="txt-r">Genel toplam</td>
          <td class="txt-r"><?= $fmt($hesap['genel_toplam']) ?> TL</td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<form method="post" action="<?= $h($temel) ?>" class="ebl-box">
  <?= Csrf::fieldHtml() ?>
  <h5>Son onay</h5>

  <div style="margin-bottom:12px">
    <label class="ebl-mini" style="display:block;font-weight:700;margin-bottom:4px">Hedef depo</label>
    <select name="depo_id" required <?= empty($onizleme['aktarilabilir']) ? 'disabled' : '' ?>>
      <?php foreach ($depolar as $d): ?>
        <option value="<?= (int)$d['id'] ?>"><?= $h($d['ad']) ?></option>
      <?php endforeach; ?>
    </select>
    <div class="ebl-mini" style="margin-top:4px">
      Stok hareketi olan kalemler bu depoya işlenir. Üründüz kalemler depodan etkilenmez.
    </div>
  </div>

  <?php foreach ($onizleme['onaylar'] as $anahtar => $mesaj): ?>
    <label class="ebl-onay">
      <input type="checkbox" name="<?= $h($anahtar) ?>" value="1" required>
      <?= $h($mesaj) ?>
    </label>
  <?php endforeach; ?>

  <div style="margin-top:14px">
    <?php if (!empty($onizleme['aktarilabilir'])): ?>
      <button class="ebl-btn green" type="submit"
              onclick="return confirm('Bu e-Belge alış faturasına dönüştürülecek. Stok ve cari bakiye etkilenecek. Devam edilsin mi?')">
        <i class="fa-solid fa-file-invoice"></i> Çekirdek Sisteme Aktar
      </button>
    <?php else: ?>
      <button class="ebl-btn green" type="submit" disabled>
        <i class="fa-solid fa-file-invoice"></i> Çekirdek Sisteme Aktar
      </button>
      <span class="ebl-mini">Yukarıdaki engeller giderilmeden aktarım yapılamaz.</span>
    <?php endif; ?>
  </div>
</form>
