<?php
/**
 * Masraflar — Liste
 * ────────────────────────────────────────────────────
 * $masraflar  : array
 * $toplam     : int
 * $ozetler    : array [toplam_tutar, odendi_tutar, bekliyor_tutar, gecikti_tutar, ...]
 * $durum      : '' | 'bekliyor' | 'odendi' | 'gecikti'
 * $arama      : string
 * $period     : string
 * $sayfa      : int
 * $sayfaSay   : int
 * $flash      : array
 */

$fmt = fn(float $n) => number_format($n, 2, ',', '.');

// URL üretici (mevcut parametreleri korur)
$buildUrl = function(array $override = []) use ($durum, $arama, $period, $sayfa): string {
    $p = ['durum' => $durum, 'ara' => $arama, 'period' => $period, 'sayfa' => $sayfa];
    $p = array_merge($p, $override);
    $p = array_filter($p, fn($v) => $v !== '' && $v !== null && !($v === 1 && array_key_exists('sayfa', $override) && $override['sayfa'] === 1));
    return BASE_URL . '/masraf?' . http_build_query($p);
};

$durumLabel = ['bekliyor' => 'Ödenecekler', 'odendi' => 'Ödenmişler', 'gecikti' => 'Gecikmişler', '' => 'Tümü'];
$periodLabel = ['1ay' => 'Son 1 Ay', '3ay' => 'Son 3 Ay', '6ay' => 'Son 6 Ay', '1yil' => 'Son 1 Yıl', 'tum' => 'Tüm Zamanlar'];
?>
<style>
  :root { --navy:#2c3e6b; --red:#d9534f; --green:#5cb85c; --orange:#f0ad4e; }

  /* ── AKSİYON SATIRI ── */
  .ms-action { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; align-items:center; }
  .btn-ms-ekle  { background:var(--red);   color:#fff; padding:7px 14px; border:none; border-radius:4px; font-size:12.5px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; }
  .btn-ms-ekle:hover  { filter:brightness(1.1); color:#fff; }
  .btn-ms-kat   { background:var(--green); color:#fff; padding:7px 14px; border:none; border-radius:4px; font-size:12.5px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; }
  .btn-ms-kat:hover   { filter:brightness(1.1); color:#fff; }

  /* ── ÖZET KARTLAR ── */
  .ms-ozet { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
  @media(max-width:900px) { .ms-ozet { grid-template-columns:1fr 1fr; } }
  .ozet-kart { background:var(--card-bg); border:1px solid var(--border); border-radius:7px; padding:14px 18px; border-left:4px solid var(--border2); }
  .ozet-kart.all     { border-left-color:var(--text); }
  .ozet-kart.paid    { border-left-color:#5cb85c; }
  .ozet-kart.pending { border-left-color:#f0ad4e; }
  .ozet-kart.late    { border-left-color:#d9534f; }
  .ozet-label { font-size:11.5px; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; }
  .ozet-val   { font-size:17px; font-weight:700; color:var(--text); }
  .ozet-sub   { font-size:11px; color:var(--muted); margin-top:2px; }

  /* ── FİLTRE ── */
  .ms-filters { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:8px; }
  .durum-btns { display:flex; gap:0; }
  .durum-btn {
    padding:6px 14px; border:1px solid var(--border); background:var(--card-bg);
    font-size:12.5px; font-weight:600; color:var(--text2); cursor:pointer;
    transition:background .12s, color .12s;
  }
  .durum-btn:first-child { border-radius:4px 0 0 4px; }
  .durum-btn:last-child  { border-radius:0 4px 4px 0; }
  .durum-btn + .durum-btn { border-left:none; }
  .durum-btn.active      { background:#2c3e6b; color:#fff; border-color:var(--text); }
  .durum-btn.active.paid { background:#5cb85c; border-color:#5cb85c; }
  .durum-btn.active.pend { background:#f0ad4e; border-color:#f0ad4e; }
  .durum-btn.active.late { background:#d9534f; border-color:#d9534f; }

  .filter-right { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .period-btn {
    padding:6px 14px; background:#34495e; color:var(--text);
    border:none; border-radius:4px; font-size:12.5px; font-weight:500; cursor:pointer;
  }
  .search-wrap { position:relative; }
  .search-wrap i { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:12px; pointer-events:none; }
  .ms-search { padding:6px 10px 6px 28px; border:1px solid var(--border2); border-radius:4px; font-size:13px; width:220px; outline:none; }
  .ms-search:focus { border-color:var(--text); }

  /* ── TABLO ── */
  .ms-table-wrap { background:var(--card-bg); border:1px solid var(--border); border-radius:6px; overflow:hidden; }
  .ms-table { width:100%; border-collapse:collapse; }
  .ms-table thead tr { background:#2c3e6b; }
  .ms-table thead th { padding:10px 12px; font-size:12px; font-weight:600; color:var(--muted); white-space:nowrap; text-align:left; }

  /* ── CUSTOM FIXED DROPDOWN ── */
  .ctx-menu {
    position:fixed; z-index:9999;
    background:var(--ink); border:1px solid var(--border2); border-radius:6px;
    box-shadow:0 6px 20px rgba(0,0,0,.13); min-width:160px;
    display:none; flex-direction:column; overflow:hidden;
  }
  .ctx-menu.open { display:flex; }
  .ctx-menu a {
    display:flex; align-items:center; gap:8px;
    padding:9px 14px; font-size:13px; color:var(--text2);
    text-decoration:none; transition:background .1s;
  }
  .ctx-menu a:hover { background:var(--surface-2); }
  .ctx-menu a.danger  { color:var(--danger); }
  .ctx-menu a.danger:hover  { background:rgba(231,76,60,.15); }
  .ctx-menu a.success { color:var(--success); }
  .ctx-menu a.success:hover { background:rgba(46,204,113,.15); }
  .ctx-menu a.belge   { color:var(--info); }
  .ctx-menu a.belge:hover   { background:rgba(59,130,246,.15); }
  .ctx-menu hr { margin:2px 0; border-color:var(--text); }
  .ms-table thead tr { background:#2c3e6b; }
  .ms-table thead th { padding:10px 12px; font-size:12px; font-weight:600; color:var(--muted); white-space:nowrap; text-align:left; }
  .ms-table tbody tr { border-bottom:1px solid var(--border); transition:background .1s; }
  .ms-table tbody tr:hover { background:var(--surface-2); }
  .ms-table tbody tr:last-child { border-bottom:none; }
  .ms-table tbody td { padding:9px 12px; font-size:13px; color:var(--text2); vertical-align:middle; }
  .txt-right { text-align:right; }

  .kat-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:3px; font-size:11.5px; font-weight:600; color:#fff; white-space:nowrap; }
  .durum-badge { display:inline-block; padding:3px 9px; border-radius:4px; font-size:12px; font-weight:600; white-space:nowrap; }
  .d-bekliyor { background:rgba(243,156,18,.15); color:var(--warning); }
  .d-odendi   { background:rgba(46,204,113,.15); color:var(--success); }
  .d-gecikti  { background:rgba(231,76,60,.15); color:var(--danger); }

  .btn-islem { font-size:11.5px; padding:3px 9px; background:var(--surface-2); border:1px solid var(--border); border-radius:4px; cursor:pointer; white-space:nowrap; }
  .btn-islem:hover { background:var(--surface-2); }

  /* ── EMPTY STATE ── */
  .empty-state { text-align:center; padding:48px 16px; color:var(--muted); font-size:14px; }
  .empty-state i { font-size:36px; display:block; margin-bottom:10px; }

  /* ── PAGİNATİON ── */
  .pag-bar { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-top:1px solid var(--border); background:var(--surface-2); }
  .pag-info { font-size:12px; color:var(--muted); }
  .pag-btns { display:flex; gap:3px; }
  .pag-btn { width:30px; height:30px; border:1px solid var(--border); border-radius:5px; background:var(--card-bg); font-size:12px; color:var(--text2); cursor:pointer; display:flex; align-items:center; justify-content:center; text-decoration:none; }
  .pag-btn:hover { background:var(--surface-2); }
  .pag-btn.active { background:#2c3e6b; border-color:var(--text); color:#4ade80; font-weight:700; }
  .pag-btn.disabled { opacity:.35; pointer-events:none; }

  /* ── MODAL ── */
  .ms-overlay { position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:900; display:none; align-items:center; justify-content:center; }
  .ms-overlay.open { display:flex; }
  .ms-modal { background:var(--ink); border:1px solid var(--border2); border-radius:7px; width:480px; max-width:96vw; box-shadow:0 8px 40px rgba(0,0,0,.25); overflow:hidden; display:flex; flex-direction:column; }
  .ms-mhdr { background:var(--navy); color:#fff; padding:13px 18px; font-size:14px; font-weight:700; display:flex; justify-content:space-between; align-items:center; }
  .ms-mhdr.green { background:var(--green); }
  .ms-mclose { background:none; border:none; color:#fff; font-size:20px; cursor:pointer; opacity:.8; }
  .ms-mclose:hover { opacity:1; }
  .ms-mbody { padding:18px 22px; display:flex; flex-direction:column; gap:12px; max-height:70vh; overflow-y:auto; }
  .ms-mrow { display:grid; grid-template-columns:120px 1fr; align-items:center; gap:10px; }
  .ms-mrow label { font-size:12.5px; font-weight:600; color:var(--text2); }
  .ms-minp, .ms-msel { padding:7px 10px; border:1px solid var(--border2); border-radius:5px; font-size:13px; color:var(--text); outline:none; width:100%; box-sizing:border-box; }
  .ms-minp:focus, .ms-msel:focus { border-color:var(--navy); }
  .ms-mftr { padding:12px 22px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:8px; }
  .ms-mbtn { display:inline-flex; align-items:center; gap:5px; padding:7px 16px; border:none; border-radius:5px; font-size:13px; font-weight:600; cursor:pointer; }
  .ms-mbtn:hover { filter:brightness(1.1); }
  .ms-mbtn.gray   { background:#64748b; color:#fff; }
  .ms-mbtn.green  { background:#5cb85c; color:#fff; }
  .ms-mbtn.orange { background:#f0ad4e; color:#fff; }

  /* ── TOAST ── */
  .toast-container { position:fixed; bottom:22px; right:22px; z-index:9999; display:flex; flex-direction:column; gap:7px; pointer-events:none; }
  .toast-msg { display:flex; align-items:center; gap:9px; padding:11px 16px; border-radius:8px; font-size:13px; font-weight:500; box-shadow:0 3px 14px rgba(0,0,0,.15); background:var(--ink); border:1px solid var(--border2); pointer-events:auto; }
  .toast-msg.success { border-left:4px solid #22c55e; }
  .toast-msg.error   { border-left:4px solid #ef4444; }
  .toast-msg.info    { border-left:4px solid #3b82f6; }

  .nav-link:focus { color:var(--muted); outline:none; }
  .nav-link.active:focus { color:#4ade80; outline:none; }
</style>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-3" role="alert">
  <?= htmlspecialchars($flash['mesaj']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Aksiyon Satırı -->
<div class="ms-action">
  <a href="<?= BASE_URL ?>/masraf/ekle" class="btn-ms-ekle">
    <i class="fa-solid fa-plus"></i> Yeni Masraf Gir
  </a>
  <a href="<?= BASE_URL ?>/masraf/kalemler" class="btn-ms-kat">
    <i class="fa-solid fa-pen-to-square"></i> Masraf Kalemleri
  </a>
</div>

<!-- Özet Kartlar -->
<div class="ms-ozet">
  <div class="ozet-kart all">
    <div class="ozet-label">Toplam</div>
    <div class="ozet-val"><?= $fmt((float)($ozetler['toplam_tutar'] ?? 0)) ?> ₺</div>
    <div class="ozet-sub"><?= (int)($ozetler['toplam_adet'] ?? 0) ?> kayıt — <?= $periodLabel[$period] ?? $period ?></div>
  </div>
  <div class="ozet-kart paid">
    <div class="ozet-label">Ödenen</div>
    <div class="ozet-val"><?= $fmt((float)($ozetler['odendi_tutar'] ?? 0)) ?> ₺</div>
    <div class="ozet-sub">Nakit çıkış</div>
  </div>
  <div class="ozet-kart pending">
    <div class="ozet-label">Ödenecek</div>
    <div class="ozet-val"><?= $fmt((float)($ozetler['bekliyor_tutar'] ?? 0)) ?> ₺</div>
    <div class="ozet-sub"><?= (int)($ozetler['bekliyor_adet'] ?? 0) ?> adet bekliyor</div>
  </div>
  <div class="ozet-kart late">
    <div class="ozet-label">Gecikmiş</div>
    <div class="ozet-val"><?= $fmt((float)($ozetler['gecikti_tutar'] ?? 0)) ?> ₺</div>
    <div class="ozet-sub"><?= (int)($ozetler['gecikti_adet'] ?? 0) ?> adet gecikti</div>
  </div>
</div>

<!-- Filtreler -->
<div class="ms-filters">
  <div class="durum-btns">
    <?php
    $durumBtnler = [
      ''         => ['Tümü',       ''],
      'bekliyor' => ['Ödenecekler','pend'],
      'odendi'   => ['Ödenmişler', 'paid'],
      'gecikti'  => ['Gecikmişler','late'],
    ];
    foreach ($durumBtnler as $val => [$label, $cls]):
      $active = ($durum === $val) ? 'active ' . $cls : '';
    ?>
    <a href="<?= $buildUrl(['durum' => $val, 'sayfa' => 1]) ?>" class="durum-btn <?= $active ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="filter-right">
    <!-- Period dropdown -->
    <div class="dropdown">
      <button class="period-btn dropdown-toggle" data-bs-toggle="dropdown">
        <?= $periodLabel[$period] ?? 'Son 6 Ay' ?> <i class="fa-solid fa-caret-down" style="font-size:10px;"></i>
      </button>
      <ul class="dropdown-menu shadow">
        <?php foreach ($periodLabel as $val => $label): ?>
        <li>
          <a class="dropdown-item <?= $period === $val ? 'fw-bold' : '' ?>"
             href="<?= $buildUrl(['period' => $val, 'sayfa' => 1]) ?>">
            <?= $label ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Arama -->
    <div class="search-wrap">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" id="msAra" class="ms-search" placeholder="Arama..." value="<?= htmlspecialchars($arama) ?>">
    </div>
  </div>
</div>

<!-- Tablo -->
<div class="ms-table-wrap">
  <div style="overflow-x:auto;">
    <table class="ms-table" id="msTable">
      <thead>
        <tr>
          <th>İşlem Tarihi</th>
          <th>Belge No</th>
          <th>Vade</th>
          <th>Masraf Kalemi</th>
          <th>Hesap</th>
          <th class="txt-right">Tutar (KDV Dahil)</th>
          <th>Durum</th>
          <th>Not / Açıklama</th>
          <th style="width:80px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($masraflar)): ?>
        <tr>
          <td colspan="9">
            <div class="empty-state">
              <i class="fa-solid fa-receipt"></i>
              <?= $durum !== '' || $arama !== '' ? 'Filtreye uyan masraf kaydı bulunamadı.' : 'Henüz masraf kaydı eklenmedi.' ?>
              <br><a href="<?= BASE_URL ?>/masraf/ekle" class="btn-ms-ekle mt-3" style="display:inline-flex; margin-top:12px;">
                <i class="fa-solid fa-plus"></i> İlk Masrafı Ekle
              </a>
            </div>
          </td>
        </tr>
        <?php else: foreach ($masraflar as $m):
          $brutTutar = (float)$m['tutar'] + (float)$m['kdv_tutari'];
          $dur       = $m['odeme_durumu'];
          // Vade geçmişse ve bekliyor ise gecikti sayılır
          if ($dur === 'bekliyor' && !empty($m['vade_tarihi']) && $m['vade_tarihi'] < date('Y-m-d')) {
              $dur = 'gecikti';
          }
          $durumCls = match($dur) { 'odendi' => 'd-odendi', 'gecikti' => 'd-gecikti', default => 'd-bekliyor' };
          $durumStr = match($dur) { 'odendi' => 'Ödenmiş', 'gecikti' => 'Gecikmiş', default => 'Bekliyor' };
          $katLabel = '';
          if (!empty($m['ana_kategori_adi'])) $katLabel = $m['ana_kategori_adi'] . ' / ';
          $katLabel .= $m['kategori_adi'] ?? '—';
        ?>
        <tr data-id="<?= $m['id'] ?>">
          <td><?= date('d.m.Y', strtotime($m['islem_tarihi'])) ?></td>
          <td style="color:var(--muted); font-size:12px;"><?= htmlspecialchars($m['belge_no'] ?? '') ?></td>
          <td style="color:<?= !empty($m['vade_tarihi']) && $m['vade_tarihi'] < date('Y-m-d') && $dur !== 'odendi' ? '#dc2626' : '#374151' ?>; font-size:12px;">
            <?= !empty($m['vade_tarihi']) ? date('d.m.Y', strtotime($m['vade_tarihi'])) : '' ?>
          </td>
          <td>
            <span class="kat-badge" style="background:<?= htmlspecialchars($m['kategori_renk'] ?? '#64748b') ?>;">
              <?= htmlspecialchars($katLabel) ?>
            </span>
          </td>
          <td style="font-size:12px;"><?= htmlspecialchars($m['hesap_adi'] ?? '—') ?></td>
          <td class="txt-right" style="font-weight:700;">
            <?= $fmt($brutTutar) ?> <?= $m['para_birimi'] ?>
            <?php if ((float)$m['kdv_orani'] > 0): ?>
            <div style="font-size:11px; color:var(--muted); font-weight:400;">KDV %<?= (int)$m['kdv_orani'] ?> = <?= $fmt((float)$m['kdv_tutari']) ?></div>
            <?php endif; ?>
          </td>
          <td><span class="durum-badge <?= $durumCls ?>"><?= $durumStr ?></span></td>
          <td style="font-size:12px; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
            <?= htmlspecialchars($m['notlar'] ?? $m['aciklama'] ?? '') ?>
          </td>
          <td>
            <button class="btn-islem"
                    data-masraf='<?= htmlspecialchars(json_encode([
                        'id'       => (int)$m['id'],
                        'tarih'    => $m['islem_tarihi'],
                        'hesap'    => $m['hesap_adi'] ?? '',
                        'aciklama' => $m['aciklama']  ?? '',
                        'notlar'   => $m['notlar']    ?? '',
                        'tutar'    => $brutTutar,
                        'para'     => $m['para_birimi'],
                        'belge_no' => $m['belge_no']  ?? '',
                        'kat'      => $katLabel,
                        'dur'      => $dur,
                    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'
                    onclick="ctxOpen(this)">
              İşlem <i class="fa-solid fa-caret-down" style="font-size:10px;"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($sayfaSay > 1): ?>
  <div class="pag-bar">
    <div class="pag-info">
      Toplam <strong><?= $toplam ?></strong> kayıt, sayfa <strong><?= $sayfa ?></strong> / <strong><?= $sayfaSay ?></strong>
    </div>
    <div class="pag-btns">
      <a href="<?= $buildUrl(['sayfa' => max(1, $sayfa - 1)]) ?>" class="pag-btn <?= $sayfa <= 1 ? 'disabled' : '' ?>">
        <i class="fa-solid fa-chevron-left" style="font-size:10px;"></i>
      </a>
      <?php
      $start = max(1, $sayfa - 2);
      $end   = min($sayfaSay, $sayfa + 2);
      for ($s = $start; $s <= $end; $s++):
      ?>
      <a href="<?= $buildUrl(['sayfa' => $s]) ?>" class="pag-btn <?= $s === $sayfa ? 'active' : '' ?>"><?= $s ?></a>
      <?php endfor; ?>
      <a href="<?= $buildUrl(['sayfa' => min($sayfaSay, $sayfa + 1)]) ?>" class="pag-btn <?= $sayfa >= $sayfaSay ? 'disabled' : '' ?>">
        <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
      </a>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- MODAL: Ödeme İşaretle -->
<div class="ms-overlay" id="mOdeme">
  <div class="ms-modal">
    <div class="ms-mhdr green">
      <span><i class="fa-solid fa-check"></i> Ödendi İşaretle</span>
      <button class="ms-mclose" onclick="closeModal('mOdeme')">&times;</button>
    </div>
    <div class="ms-mbody">
      <div style="font-size:13px; color:var(--text2); background:rgba(46,204,113,.10); padding:10px 14px; border-radius:5px; border:1px solid rgba(46,204,113,.28);">
        <strong id="odemeKatLabel"></strong> masrafını ödenmiş olarak işaretlemek üzeresiniz.
      </div>
      <input type="hidden" id="odemeId" value="">
      <div class="ms-mrow">
        <label>Ödeme Tarihi</label>
        <input type="date" id="odemeTarih" class="ms-minp" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="ms-mrow">
        <label>Ödenen Hesap</label>
        <select id="odemeKasa" class="ms-msel">
          <option value="">— Seçmek zorunlu değil —</option>
          <?php
          // Hesapları controller'dan almak için KasaHesap model gerekir
          // View'da doğrudan erişim yerine JSON endpoint kullanıyoruz
          // Başlangıçta boş, JS ile dolduracağız
          ?>
        </select>
      </div>
    </div>
    <div class="ms-mftr">
      <button class="ms-mbtn gray" onclick="closeModal('mOdeme')">Vazgeç</button>
      <button class="ms-mbtn green" id="btnOdemeKaydet" onclick="odemeKaydet()">
        <i class="fa-solid fa-check"></i> Onayla
      </button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast-container" id="toastContainer"></div>

<!-- Custom Fixed Dropdown (overflow sorunu yok) -->
<div class="ctx-menu" id="ctxMenu">
  <a href="#" id="ctxYazdir"><i class="fa-solid fa-print fa-fw"></i> Yazdır</a>
  <a href="#" id="ctxDuzenle"><i class="fa-solid fa-pen fa-fw"></i> Düzenle</a>
  <a href="#" id="ctxKopyala"><i class="fa-regular fa-copy fa-fw"></i> Kopyala</a>
  <hr>
  <a href="#" id="ctxSil" class="danger"><i class="fa-solid fa-trash fa-fw"></i> Sil</a>
  <hr>
  <a href="#" id="ctxBelge" class="belge"><i class="fa-solid fa-plus fa-fw"></i> Belge Ekle</a>
  <a href="#" id="ctxOdeme" class="success" style="display:none;"><i class="fa-solid fa-check fa-fw"></i> Ödendi İşaretle</a>
</div>

<!-- MODAL: Belge Yükleme -->
<div class="ms-overlay" id="mBelge">
  <div class="ms-modal" style="max-width:480px;">
    <div class="ms-mhdr" style="background:#3b82f6;">
      <span><i class="fa-solid fa-paperclip"></i> Dosya Yükleme</span>
      <button class="ms-mclose" onclick="closeModal('mBelge')">&times;</button>
    </div>
    <div class="ms-mbody">
      <div style="background:rgba(243,156,18,.13); border:1px solid rgba(243,156,18,.30); border-radius:5px; padding:10px 14px; font-size:12.5px; color:var(--warning); line-height:1.6;">
        Uzantısı <strong>doc, docx, xls, xlsx, pdf, jpg, gif, png, txt</strong> olan dosyaları yükleyebilirsiniz.
        Dosya boyu <strong>5MB</strong> geçmemelidir.
      </div>
      <input type="hidden" id="belgeMasrafId" value="">
      <div class="ms-mrow">
        <label>Yüklenecek Dosya</label>
        <input type="file" id="belgeDosya" class="ms-minp"
               accept=".doc,.docx,.xls,.xlsx,.pdf,.jpg,.jpeg,.gif,.png,.txt">
      </div>
    </div>
    <div class="ms-mftr">
      <button class="ms-mbtn gray" onclick="closeModal('mBelge')">
        <i class="fa-solid fa-xmark"></i> Vazgeç
      </button>
      <button class="ms-mbtn green" id="btnBelgeYukle" onclick="belgeYukle()">
        <i class="fa-solid fa-upload"></i> Yükle
      </button>
    </div>
  </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';

/* ── CUSTOM FIXED DROPDOWN ──────────────────────────── */
let _ctx = {};

function ctxOpen(btn) {
  const menu = document.getElementById('ctxMenu');
  try { _ctx = JSON.parse(btn.dataset.masraf); } catch(e) { return; }

  // Ödendi İşaretle — sadece ödenmediyse görünür
  const odemeEl = document.getElementById('ctxOdeme');
  odemeEl.style.display = (_ctx.dur !== 'odendi') ? 'flex' : 'none';
  odemeEl.onclick = e => { e.preventDefault(); closeCtx(); openOdeme(_ctx.id, _ctx.kat); };

  // Yazdır
  document.getElementById('ctxYazdir').onclick = e => { e.preventDefault(); closeCtx(); masrafYazdir(_ctx); };

  // Düzenle
  document.getElementById('ctxDuzenle').href = BASE + '/masraf/ekle?duzenle=' + _ctx.id;
  document.getElementById('ctxDuzenle').onclick = () => closeCtx();

  // Kopyala
  document.getElementById('ctxKopyala').onclick = e => { e.preventDefault(); closeCtx(); masrafKopyala(_ctx.id); };

  // Sil
  document.getElementById('ctxSil').onclick = e => { e.preventDefault(); closeCtx(); masrafSil(_ctx.id, btn); };

  // Belge Ekle
  document.getElementById('ctxBelge').onclick = e => { e.preventDefault(); closeCtx(); openBelgeModal(_ctx.id); };

  // Pozisyon: butonun altına — viewport dışına taşmaz
  menu.classList.add('open');
  const rect = btn.getBoundingClientRect();
  const mw   = menu.offsetWidth  || 170;
  const mh   = menu.offsetHeight || 180;
  let top    = rect.bottom + 4;
  let left   = rect.right  - mw;
  if (top + mh > window.innerHeight - 8) top = rect.top - mh - 4;
  if (left < 6) left = 6;
  menu.style.top  = top  + 'px';
  menu.style.left = left + 'px';
}

function closeCtx() { document.getElementById('ctxMenu').classList.remove('open'); }
document.addEventListener('click', e => {
  const m = document.getElementById('ctxMenu');
  if (!m.contains(e.target) && !e.target.closest('.btn-islem')) closeCtx();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCtx(); });

/* ── KOPYALA ─────────────────────────────────────────── */
function masrafKopyala(id) {
  if (!confirm('Bu masraf kaydını kopyalamak istiyor musunuz? Yeni kayıt bugün tarihiyle oluşturulur.')) return;
  fetch(BASE + '/masraf/kopyala/' + id)
    .then(r => r.json())
    .then(res => {
      if (res.success) { showToast(res.message, 'success'); setTimeout(() => location.reload(), 1000); }
      else showToast(res.message, 'error');
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'));
}

/* ── BELGE MODAL ─────────────────────────────────────── */
function openBelgeModal(id) {
  document.getElementById('belgeMasrafId').value = id;
  document.getElementById('belgeDosya').value    = '';
  openModal('mBelge');
}

function belgeYukle() {
  const id  = document.getElementById('belgeMasrafId').value;
  const inp = document.getElementById('belgeDosya');
  if (!inp.files.length) { showToast('Lütfen dosya seçin.', 'error'); return; }

  const btn = document.getElementById('btnBelgeYukle');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Yükleniyor...';

  const fd = new FormData();
  fd.append('masraf_id', id);
  fd.append('dosya', inp.files[0]);

  fetch(BASE + '/masraf/belgeYukle', { method:'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) { showToast(res.message, 'success'); closeModal('mBelge'); }
      else showToast(res.message, 'error');
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-upload"></i> Yükle'; });
}

/* ── TEDİYE MAKBUZU YAZDIR ───────────────────────────── */
function masrafYazdir(m) {
  const trTarih = (iso) => {
    const aylar = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    const gunler = ['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'];
    const d = new Date(iso + 'T00:00:00');
    return d.getDate() + ' ' + aylar[d.getMonth()] + ' ' + d.getFullYear() + ' ' + gunler[d.getDay()];
  };

  const tutarYazi = (n) => {
    const birler = ['','Bir','İki','Üç','Dört','Beş','Altı','Yedi','Sekiz','Dokuz'];
    const onlar  = ['','On','Yirmi','Otuz','Kırk','Elli','Altmış','Yetmiş','Seksen','Doksan'];
    const binler = ['','Bin','Milyon','Milyar'];
    const yazGrup = (k, grup) => {
      if (k === 0) return '';
      let t = '';
      const y = Math.floor(k / 100), on = Math.floor((k % 100) / 10), bir = k % 10;
      if (y === 1) t += 'Yüz'; else if (y > 1) t += birler[y] + 'Yüz';
      t += onlar[on];
      if (grup === 1 && k === 1) return 'Bin';
      t += birler[bir];
      return t + binler[grup];
    };
    let sayi = Math.floor(n), s = '', g = 0;
    if (sayi === 0) s = 'Sıfır';
    else {
      const tmp = sayi;
      while (sayi > 0) { s = yazGrup(sayi % 1000, g) + s; sayi = Math.floor(sayi / 1000); g++; }
      if (tmp === 0) s = 'Sıfır';
    }
    const kr = Math.round((n - Math.floor(n)) * 100);
    return 'Yalnız #' + s + 'TL' + (kr > 0 ? ',' + (kr < 10 ? '0'+kr : kr) + 'Kr.' : '') + '#';
  };

  const fmtTutar = (n) => n.toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2});

  const html = `<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">
  <title>Tediye Makbuzu — #${m.id}</title>
  <style>
    body { font-family:'Segoe UI',sans-serif; padding:30px 50px; color:var(--text); font-size:14px; }
    h1 { text-align:center; font-size:20px; letter-spacing:2px; margin-bottom:30px; font-weight:700; }
    table { width:100%; border-collapse:collapse; margin-bottom:30px; }
    td { padding:10px 8px; vertical-align:top; }
    td:first-child { font-weight:700; text-transform:uppercase; width:160px; white-space:nowrap; }
    .imza-row td { padding-top:50px; border-top:1px solid var(--border); font-weight:700; font-size:13px; }
    .imza-row td:last-child { text-align:right; }
    .tutar-yazi { font-size:12px; color:var(--text2); margin-top:4px; }
    @media print {
      body { padding:10px 20px; }
      button { display:none !important; }
    }
  </style></head><body>
  <h1>TEDİYE MAKBUZU</h1>
  <table>
    <tr><td>Tarih</td><td>${trTarih(m.tarih)}</td></tr>
    ${m.hesap ? `<tr><td>Ödeme</td><td>Banka (${m.hesap})</td></tr>` : ''}
    ${m.aciklama || m.notlar ? `<tr><td>Açıklama</td><td>${(m.aciklama || m.notlar).toUpperCase()}</td></tr>` : ''}
    ${m.belge_no ? `<tr><td>Belge No</td><td>${m.belge_no}</td></tr>` : ''}
    <tr>
      <td>Tutar</td>
      <td>
        <strong>${fmtTutar(m.tutar)} ₺</strong>
        <div class="tutar-yazi">${tutarYazi(m.tutar)}</div>
      </td>
    </tr>
    <tr>
      <td>Masraf Kalemi</td><td>${m.kat}</td>
    </tr>
  </table>
  <table>
    <tr class="imza-row">
      <td>Ödemeyi Yapan</td>
      <td>Ödemeyi Alan</td>
    </tr>
    <tr>
      <td style="padding-top:40px; font-size:13px;">Sebahattin</td>
      <td></td>
    </tr>
  </table>
  <script>window.onload=()=>{ window.print(); }<\/script>
  </body></html>`;

  const win = window.open('', '_blank', 'width=750,height=650,toolbar=0,scrollbars=1');
  win.document.write(html);
  win.document.close();
}

/* ── ARAMA (debounce) ───────────────────────────────── */
let searchTimer;
document.getElementById('msAra').addEventListener('input', function() {
  clearTimeout(searchTimer);
  const q = this.value.trim();
  searchTimer = setTimeout(() => {
    const url = new URL(window.location.href);
    if (q.length >= 2 || q.length === 0) {
      url.searchParams.set('ara', q);
      url.searchParams.set('sayfa', 1);
      window.location.href = url.toString();
    }
  }, 400);
});

/* ── MODAL ──────────────────────────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.ms-overlay.open').forEach(el => el.classList.remove('open')); });

/* ── ÖDEME MODAL ────────────────────────────────────── */
function openOdeme(id, katLabel) {
  document.getElementById('odemeId').value = id;
  document.getElementById('odemeKatLabel').textContent = katLabel;
  document.getElementById('odemeTarih').value = new Date().toISOString().split('T')[0];

  // Hesapları yükle
  fetch(BASE + '/hesap')
    .then(() => {}) // sayfa dönüyor, API yok; hesaplar PHP'den geliyor
    .catch(() => {});

  openModal('mOdeme');
}

function odemeKaydet() {
  const id     = document.getElementById('odemeId').value;
  const tarih  = document.getElementById('odemeTarih').value;
  const kasaId = document.getElementById('odemeKasa').value;
  const btn    = document.getElementById('btnOdemeKaydet');

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

  const fd = new FormData();
  fd.append('id',      id);
  fd.append('tarih',   tarih);
  fd.append('kasa_id', kasaId);

  fetch(BASE + '/masraf/odemeYap', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast(res.message, 'success');
        closeModal('mOdeme');
        setTimeout(() => location.reload(), 900);
      } else {
        showToast(res.message || 'Hata oluştu.', 'error');
      }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'))
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Onayla';
    });
}

/* ── MASRAF SİL ─────────────────────────────────────── */
function masrafSil(id, el) {
  if (!confirm('Bu masraf kaydını silmek istediğinizden emin misiniz?')) return;

  fetch(BASE + '/masraf/sil/' + id)
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast('Masraf silindi.', 'success');
        const row = el.closest('tr');
        if (row) row.remove();
        setTimeout(() => location.reload(), 1200);
      } else {
        showToast(res.message || 'Silinemedi.', 'error');
      }
    })
    .catch(() => showToast('Bağlantı hatası.', 'error'));
}

/* ── TOAST ──────────────────────────────────────────── */
function showToast(msg, type = 'info') {
  const icons = { success: 'check-circle', error: 'circle-xmark', info: 'circle-info' };
  const c  = document.getElementById('toastContainer');
  const el = document.createElement('div');
  el.className = 'toast-msg ' + type;
  el.innerHTML = `<i class="fa-solid fa-${icons[type] || 'circle-info'}"></i> ${msg}`;
  c.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

/* ── SİDEBAR ACCORDION ──────────────────────────────── */
document.querySelectorAll('.submenu').forEach(sub => {
  const link = document.querySelector(`[data-bs-target="#${sub.id}"]`);
  if (!link) return;
  sub.addEventListener('show.bs.collapse', () => {
    link.setAttribute('aria-expanded', 'true');
    document.querySelectorAll('.submenu.show').forEach(o => {
      if (o.id !== sub.id) bootstrap.Collapse.getInstance(o)?.hide();
    });
  });
  sub.addEventListener('hide.bs.collapse', () => link.setAttribute('aria-expanded', 'false'));
});

/* ── ÖDEME MODAL HESAP SEÇENEKLERİ ─────────────────── */
// Hesapları dinamik yükle (sayfa yüklendikten sonra)
(function loadHesaplar() {
  const hesaplar = <?= json_encode(
      array_map(fn($h) => ['id' => $h['id'], 'ad' => $h['hesap_adi'] . ' (' . number_format($h['guncel_bakiye'], 2, ',', '.') . ' ' . $h['para_birimi'] . ')'],
      (new KasaHesap())->hepsini())
  , JSON_UNESCAPED_UNICODE) ?>;

  const sel = document.getElementById('odemeKasa');
  hesaplar.forEach(h => {
    const opt = document.createElement('option');
    opt.value = h.id;
    opt.textContent = h.ad;
    sel.appendChild(opt);
  });
})();
</script>
