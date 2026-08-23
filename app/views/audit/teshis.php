<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$companies = $companies ?? [];
$periods = $periods ?? [];
$dagilim = $dagilim ?? [];
$sonKayitlar = $sonKayitlar ?? [];
$ariyor = $ariyor ?? '';
$eslesenCariler = $eslesenCariler ?? [];
$cariFaturalari = $cariFaturalari ?? [];
$oturumCompanyId = $oturumCompanyId ?? null;
$oturumPeriodId = $oturumPeriodId ?? null;
$kolonTanimi = $kolonTanimi ?? null;
$kolonTanimiDurum = $kolonTanimiDurum ?? null;
$dagitimTumTipler = $dagitimTumTipler ?? [];
$dagitimTumDurumlar = $dagitimTumDurumlar ?? [];

$companyName = [];
foreach ($companies as $c) { $companyName[(int)$c['id']] = $c['company_name']; }
$periodName = [];
foreach ($periods as $p) { $periodName[(int)$p['id']] = ($p['period_name'] ?: $p['fiscal_year']) . ' (' . $p['status'] . ')'; }
?>
<div class="alert alert-info">
  <strong>Şu an oturumunuzda aktif olan:</strong>
  Şirket #<?= (int)$oturumCompanyId ?> (<?= $h($companyName[(int)$oturumCompanyId] ?? '?') ?>) —
  Dönem #<?= (int)$oturumPeriodId ?> (<?= $h($periodName[(int)$oturumPeriodId] ?? '?') ?>)
</div>

<h5>0) faturalar.belge_tipi / durum sütunlarının GERÇEK veritabanı tanımı</h5>
<p class="text-muted">Eğer bu bir ENUM ise ve listede 'numune'/'irsaliye'/'perakende'/'proforma'/'siparis' YOKSA, kök neden budur: kayıt gerçekten yazılıyor (bu yüzden stok düşüyor) ama veritabanı bu değeri sessizce başka bir şeye çeviriyor, bu yüzden hiçbir filtreye uymuyor. Bu sayfayı yeniden yüklediğinizde (bu değişiklik canlıya alındıktan sonra), uygulama bu ENUM'u otomatik olarak VARCHAR'a çevirip önceden bozulmuş kayıtları kendiliğinden onarır — aşağıdaki tanımın artık "varchar(20)" göstermesi düzeltmenin çalıştığının kanıtıdır.</p>
<div class="table-responsive mb-3">
  <table class="table table-sm table-bordered">
    <thead><tr><th>Sütun</th><th>COLUMN_TYPE (gerçek tanım)</th><th>NULL olabilir mi</th></tr></thead>
    <tbody>
      <tr><td>belge_tipi</td><td><code><?= $h($kolonTanimi['COLUMN_TYPE'] ?? '(bulunamadı)') ?></code></td><td><?= $h($kolonTanimi['IS_NULLABLE'] ?? '?') ?></td></tr>
      <tr><td>durum</td><td><code><?= $h($kolonTanimiDurum['COLUMN_TYPE'] ?? '(bulunamadı)') ?></code></td><td><?= $h($kolonTanimiDurum['IS_NULLABLE'] ?? '?') ?></td></tr>
    </tbody>
  </table>
</div>

<h5>0b) faturalar tablosunda GERÇEKTE var olan tüm belge_tipi / durum değerleri (filtre YOK)</h5>
<p class="text-muted">Burada 'numune'/'irsaliye' yerine boş bir satır ya da hiç beklemediğiniz bir metin görüyorsanız, kayıtlarınız o değerin altında saklı demektir.</p>
<div class="row">
<div class="col-md-6">
<div class="table-responsive mb-4">
  <table class="table table-sm table-bordered">
    <thead><tr><th>belge_tipi (ham değer)</th><th>Adet</th></tr></thead>
    <tbody>
    <?php foreach ($dagitimTumTipler as $d): ?>
      <tr>
        <td><code>"<?= $h($d['belge_tipi']) ?>"</code><?= $d['belge_tipi'] === '' ? ' <strong style="color:#e74c3c;">← BOŞ STRING</strong>' : '' ?></td>
        <td><?= (int)$d['adet'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
</div>
<div class="col-md-6">
<div class="table-responsive mb-4">
  <table class="table table-sm table-bordered">
    <thead><tr><th>durum (ham değer)</th><th>Adet</th></tr></thead>
    <tbody>
    <?php foreach ($dagitimTumDurumlar as $d): ?>
      <tr>
        <td><code>"<?= $h($d['durum']) ?>"</code><?= $d['durum'] === '' ? ' <strong style="color:#e74c3c;">← BOŞ STRING</strong>' : '' ?></td>
        <td><?= (int)$d['adet'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
</div>
</div>

<h5>1) Şirketler</h5>
<div class="table-responsive mb-4">
  <table class="table table-sm table-bordered">
    <thead><tr><th>ID</th><th>Ad</th><th>Durum</th><th>Silinme Tarihi</th></tr></thead>
    <tbody>
    <?php foreach ($companies as $c): ?>
      <tr <?= (int)$c['id'] === (int)$oturumCompanyId ? 'style="background:rgba(46,204,113,.15);"' : '' ?>>
        <td><?= (int)$c['id'] ?></td><td><?= $h($c['company_name']) ?></td><td><?= $h($c['status']) ?></td><td><?= $h($c['deleted_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<h5>2) Muhasebe Dönemleri</h5>
<div class="table-responsive mb-4">
  <table class="table table-sm table-bordered">
    <thead><tr><th>ID</th><th>Şirket ID</th><th>Ad</th><th>Mali Yıl</th><th>Durum</th><th>Aktif mi</th></tr></thead>
    <tbody>
    <?php foreach ($periods as $p): ?>
      <tr <?= (int)$p['id'] === (int)$oturumPeriodId ? 'style="background:rgba(46,204,113,.15);"' : '' ?>>
        <td><?= (int)$p['id'] ?></td><td><?= (int)$p['company_id'] ?></td><td><?= $h($p['period_name']) ?></td>
        <td><?= $h($p['fiscal_year']) ?></td><td><?= $h($p['status']) ?></td><td><?= $h($p['is_active']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<h5>3) Numune / İrsaliye / Perakende — şirket + dönem + tip + durum bazında sayım</h5>
<p class="text-muted">Yeşil satır, şu an oturumda AKTİF olan şirket+dönem kombinasyonunu gösterir. Aradığınız kayıt burada başka bir şirket/dönem satırında görünüyorsa, kök neden odur.</p>
<div class="table-responsive mb-4">
  <table class="table table-sm table-bordered">
    <thead><tr><th>Şirket</th><th>Dönem</th><th>Belge Tipi</th><th>Durum</th><th>Silindi mi</th><th>Adet</th><th>En Eski Tarih</th><th>En Yeni Tarih</th></tr></thead>
    <tbody>
    <?php if (empty($dagilim)): ?>
      <tr><td colspan="8" class="text-center text-muted">Hiç numune/irsaliye/perakende kaydı yok (hiçbir şirkette/dönemde).</td></tr>
    <?php endif; ?>
    <?php foreach ($dagilim as $d): ?>
      <?php $aktifMi = (int)$d['company_id'] === (int)$oturumCompanyId && (int)$d['period_id'] === (int)$oturumPeriodId; ?>
      <tr <?= $aktifMi ? 'style="background:rgba(46,204,113,.15);"' : '' ?>>
        <td><?= (int)$d['company_id'] ?> — <?= $h($companyName[(int)$d['company_id']] ?? '?') ?></td>
        <td><?= (int)$d['period_id'] ?> — <?= $h($periodName[(int)$d['period_id']] ?? '?') ?></td>
        <td><?= $h($d['belge_tipi']) ?></td>
        <td><?= $h($d['durum']) ?></td>
        <td><?= $h($d['silindi_mi']) ?></td>
        <td><strong><?= (int)$d['adet'] ?></strong></td>
        <td><?= $h($d['en_eski']) ?></td>
        <td><?= $h($d['en_yeni']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<h5>4) Son 30 numune/irsaliye/perakende kaydı (tüm şirket/dönemler)</h5>
<div class="table-responsive mb-4">
  <table class="table table-sm table-bordered">
    <thead><tr><th>ID</th><th>Şirket</th><th>Dönem</th><th>Tip</th><th>No</th><th>Tarih</th><th>Durum</th><th>Silindi</th><th>Cari</th><th>Tutar</th></tr></thead>
    <tbody>
    <?php if (empty($sonKayitlar)): ?>
      <tr><td colspan="10" class="text-center text-muted">Hiç kayıt yok.</td></tr>
    <?php endif; ?>
    <?php foreach ($sonKayitlar as $r): ?>
      <?php $aktifMi = (int)$r['company_id'] === (int)$oturumCompanyId && (int)$r['period_id'] === (int)$oturumPeriodId; ?>
      <tr <?= $aktifMi ? 'style="background:rgba(46,204,113,.15);"' : '' ?>>
        <td><?= (int)$r['id'] ?></td>
        <td><?= (int)$r['company_id'] ?></td>
        <td><?= (int)$r['period_id'] ?></td>
        <td><?= $h($r['belge_tipi']) ?></td>
        <td><?= $h($r['fatura_no']) ?></td>
        <td><?= $h($r['fatura_tarihi']) ?></td>
        <td><?= $h($r['durum']) ?></td>
        <td><?= $h($r['silindi_mi']) ?></td>
        <td><?= $h($r['cari_unvan']) ?></td>
        <td><?= $h($r['genel_toplam']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<h5>5) Belirli bir cariyi ara (o cariye bağlı TÜM faturaları, tip filtresi olmadan gösterir)</h5>
<form class="row g-2 mb-3" method="get">
  <div class="col-auto"><input type="text" name="cari" class="form-control form-control-sm" placeholder="Örn: MELSABERRY" value="<?= $h($ariyor) ?>" style="min-width:240px"></div>
  <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Ara</button></div>
</form>

<h5>6) Belirli bir belge numarasını ara (örn: NUM-2026-000001) — şirket/tip/durum filtresi YOK</h5>
<form class="row g-2 mb-3" method="get">
  <div class="col-auto"><input type="text" name="no" class="form-control form-control-sm" placeholder="Örn: NUM-2026-000001" value="<?= $h($noAriyor ?? '') ?>" style="min-width:240px"></div>
  <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Ara</button></div>
</form>
<?php if (($noAriyor ?? '') !== ''): ?>
  <div class="table-responsive mb-4">
    <table class="table table-sm table-bordered">
      <thead><tr><th>ID</th><th>Şirket</th><th>Dönem</th><th>belge_tipi (ham)</th><th>No</th><th>Tarih</th><th>Durum</th><th>Silindi</th><th>Cari</th><th>Tutar</th></tr></thead>
      <tbody>
      <?php if (empty($noEslesenler)): ?>
        <tr><td colspan="10" class="text-center text-muted">"<?= $h($noAriyor) ?>" içeren HİÇBİR belge yok (hiçbir şirkette, hiçbir tipte).</td></tr>
      <?php endif; ?>
      <?php foreach (($noEslesenler ?? []) as $f): ?>
        <tr>
          <td><?= (int)$f['id'] ?></td><td><?= (int)$f['company_id'] ?></td><td><?= (int)$f['period_id'] ?></td>
          <td><code>"<?= $h($f['belge_tipi']) ?>"</code></td><td><?= $h($f['fatura_no']) ?></td><td><?= $h($f['fatura_tarihi']) ?></td>
          <td><?= $h($f['durum']) ?></td><td><?= $h($f['silindi_mi']) ?></td><td><?= $h($f['cari_unvan']) ?></td><td><?= $h($f['genel_toplam']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php if ($ariyor !== ''): ?>
  <?php if (empty($eslesenCariler)): ?>
    <div class="alert alert-warning">"<?= $h($ariyor) ?>" adında/içeren hiçbir cari bulunamadı (hiçbir şirkette).</div>
  <?php endif; ?>
  <?php foreach ($eslesenCariler as $c): ?>
    <h6>Cari #<?= (int)$c['id'] ?> — <?= $h($c['unvan']) ?> (tip: <?= $h($c['tip']) ?>, şirket: <?= (int)$c['company_id'] ?> — <?= $h($companyName[(int)$c['company_id']] ?? '?') ?>, silindi_mi: <?= $h($c['silindi_mi']) ?>)</h6>
    <div class="table-responsive mb-4">
      <table class="table table-sm table-bordered">
        <thead><tr><th>ID</th><th>Şirket</th><th>Dönem</th><th>Tip</th><th>No</th><th>Tarih</th><th>Durum</th><th>Silindi</th><th>Tutar</th></tr></thead>
        <tbody>
        <?php if (empty($cariFaturalari[$c['id']])): ?>
          <tr><td colspan="9" class="text-center text-muted">Bu cariye bağlı HİÇBİR fatura/belge yok.</td></tr>
        <?php endif; ?>
        <?php foreach (($cariFaturalari[$c['id']] ?? []) as $f): ?>
          <tr>
            <td><?= (int)$f['id'] ?></td><td><?= (int)$f['company_id'] ?></td><td><?= (int)$f['period_id'] ?></td>
            <td><?= $h($f['belge_tipi']) ?></td><td><?= $h($f['fatura_no']) ?></td><td><?= $h($f['fatura_tarihi']) ?></td>
            <td><?= $h($f['durum']) ?></td><td><?= $h($f['silindi_mi']) ?></td><td><?= $h($f['genel_toplam']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
