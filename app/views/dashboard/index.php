<?php
/**
 * Dashboard View — Canlı Veri
 */
$tl  = fn($n) => number_format((float)$n, 2, ',', '.') . ' TL';
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');

// Varlıklar oranları (güvenli bölme)
$vT  = max((float)$varliklarToplam, 1);
$pKasa  = min(100, round($kasaBakiye  / $vT * 100));
$pStok  = min(100, round($stokDegeri  / $vT * 100));
$pAcik  = min(100, round($acikHesap   / $vT * 100));

$durumBadge = function(string $d): string {
    $map = [
        'onaylandi'    => ['Onaylı', 'var(--success)', 'rgba(46,204,113,.15)'],
        'taslak'       => ['Taslak', 'var(--warning)', 'rgba(243,156,18,.15)'],
        'kismi_odendi' => ['Kısmî', 'var(--info)', 'rgba(59,130,246,.15)'],
        'odendi'       => ['Ödendi', 'var(--accent)', 'var(--accent-soft)'],
        'iptal'        => ['İptal', 'var(--danger)', 'rgba(231,76,60,.15)'],
    ];
    [$label, $color, $bg] = $map[$d] ?? [htmlspecialchars($d), 'var(--muted)', 'var(--surface-2)'];
    return '<span style="background:' . $bg . ';color:' . $color . ';padding:2px 8px;border-radius:3px;font-size:11px;font-weight:700;">' . $label . '</span>';
};
?>
<style>
  /* ── Panel başlıkları ── */
  .phead { color:#fff; font-size:12px; font-weight:700; padding:10px 15px; border-radius:6px 6px 0 0; }
  .phead-dark   { background:#4a5a6a; }
  .phead-cyan   { background:#37a5d1; }
  .phead-green  { background:#22c55e; }
  .phead-orange { background:#f59e0b; }

  /* ── Tablolar ── */
  .mini-table { width:100%; border-collapse:collapse; font-size:12px; }
  .mini-table th { padding:8px 12px; background:var(--surface-2); color:var(--muted); font-weight:600; text-align:left; border-bottom:1px solid var(--border); }
  .mini-table td { padding:9px 12px; border-bottom:1px solid var(--border); color:var(--text2); vertical-align:middle; }
  .mini-table tr:last-child td { border-bottom:none; }
  .mini-table tr:hover td { background:var(--surface-2); }
  .no-data { padding:28px; text-align:center; color:var(--muted); font-size:13px; }

  /* ── Varlık / Borç kartları ── */
  .card-title-muted { font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; }
  .card-title-val   { font-size:13px; font-weight:700; color:var(--text2); }
  .progress-custom  { height:12px; border-radius:4px; background:var(--surface-2); margin-bottom:0; overflow:hidden; }
  .progress-custom .bar { height:100%; border-radius:4px; transition:width .4s ease; }

  /* ── Ürün stok listesi ── */
  .dash-section-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; gap:10px; flex-wrap:wrap; }
  .dash-section-head h3 { font-size:15px; font-weight:800; color:var(--text); margin:0; display:flex; align-items:center; gap:8px; }
  .dash-section-head h3 i { color:var(--accent); }
  .dash-section-head .dash-count { font-size:11.5px; color:var(--muted); font-weight:600; }
  .dash-section-head .dash-count a { color:var(--accent); text-decoration:none; font-weight:700; margin-left:6px; }

  .dash-action-wrap { position:relative; }
  .dash-action-btn {
    display:flex; align-items:center; gap:8px; padding:10px 16px;
    border-radius:var(--rsm); background:linear-gradient(135deg,var(--accent),var(--accent2)); color:#fff;
    font-size:12.5px; font-weight:700; border:none; cursor:pointer;
  }
  .dash-action-btn i.chev { font-size:10px; transition:transform .2s; }
  .dash-action-wrap:hover .dash-action-btn i.chev,
  .dash-action-wrap:focus-within .dash-action-btn i.chev { transform:rotate(180deg); }
  .dash-action-menu {
    position:absolute; top:calc(100% + 8px); right:0; min-width:210px;
    background:var(--ink); border:1px solid var(--border2); border-radius:var(--radius);
    box-shadow:0 20px 44px rgba(0,0,0,.45); padding:6px; z-index:60;
    opacity:0; visibility:hidden; transform:translateY(-6px);
    transition:all .16s ease;
  }
  .dash-action-wrap:hover .dash-action-menu,
  .dash-action-wrap:focus-within .dash-action-menu { opacity:1; visibility:visible; transform:translateY(0); }
  .dash-action-menu a {
    display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px;
    font-size:12.5px; font-weight:600; color:var(--text2); text-decoration:none;
  }
  .dash-action-menu a i { width:18px; text-align:center; color:var(--accent); }
  .dash-action-menu a:hover { background:var(--card-bg); color:var(--text); }

  .dash-urun-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px,1fr)); gap:12px; margin-bottom:26px; }
  .dash-urun-card {
    display:flex; flex-direction:column; gap:9px; padding:14px;
    background:var(--card-bg); border:1px solid var(--border); border-radius:var(--rsm);
    text-decoration:none; transition:all .18s ease; position:relative;
  }
  .dash-urun-card:hover {
    border-color:var(--accent); background:var(--accent-soft);
    box-shadow:0 10px 26px var(--glow); transform:translateY(-2px);
  }
  .dash-urun-ico { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:14px; background:var(--surface-2); color:var(--accent); }
  .dash-urun-ico.warn { color:var(--warning); background:rgba(243,156,18,.14); }
  .dash-urun-ico.danger { color:var(--danger); background:rgba(231,76,60,.14); }
  .dash-urun-name { font-size:12.5px; font-weight:700; color:var(--text); line-height:1.3; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; min-height:32px; }
  .dash-urun-meta { display:flex; justify-content:space-between; align-items:baseline; font-size:11px; color:var(--muted); gap:6px; }
  .dash-urun-stok { font-weight:700; color:var(--text2); }
  .dash-urun-stok.warn { color:var(--warning); }
  .dash-urun-stok.danger { color:var(--danger); }
  .dash-urun-fiyat { font-size:12.5px; font-weight:800; color:var(--text2); white-space:nowrap; }
  .dash-urun-empty { grid-column:1/-1; padding:30px; text-align:center; color:var(--muted); font-size:13px; background:var(--card-bg); border:1px dashed var(--border); border-radius:var(--rsm); }

  /* ── 6'lı özet kart ızgarası ── */
  .dash-grid6 { display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; align-items:start; }
  @media (max-width:1100px) { .dash-grid6 { grid-template-columns:repeat(2,1fr); } }
  @media (max-width:700px)  {
    .dash-grid6 { grid-template-columns:1fr; }
    .dash-urun-grid { grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); }
    /* "İşlem Yap" butonu uzun başlık yüzünden kendi satırına düşüp sola
       yaslanıyor; açılır menü dar kutunun sağına göre konumlanınca (right:0)
       ekranın solundan taşıyordu. Buton + menü artık aynı tam genişliğe
       sabit — menü hiçbir zaman ekran dışına çıkamaz. */
    .dash-action-wrap { width:100%; }
    .dash-action-btn { width:100%; justify-content:center; }
    .dash-action-menu { left:0; right:0; width:auto; min-width:0; }
  }
</style>

<!-- ══════════ HEADER ══════════ -->
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
  <div>
    <h4 style="color:var(--text); font-size:20px; font-weight:600; margin-bottom:4px;"><?= htmlspecialchars($bugun) ?></h4>
  </div>
</div>

<!-- ══════════ ÜRÜN STOK LİSTESİ ══════════ -->
<div class="dash-section-head">
  <h3><i class="fa-solid fa-boxes-stacked"></i> Ürün Stok Listesi
    <span class="dash-count">(<?= number_format($urunStokToplam) ?> ürün<?= $urunStokToplam > count($urunStokListesi) ? ' — ilk ' . count($urunStokListesi) . ' gösteriliyor' : '' ?>)
      <a href="<?= BASE_URL ?>/urun">tümünü gör »</a>
    </span>
  </h3>
  <div class="dash-action-wrap" tabindex="0">
    <button type="button" class="dash-action-btn">
      <i class="fa-solid fa-bolt"></i> İşlem Yap <i class="fa-solid fa-chevron-down chev"></i>
    </button>
    <div class="dash-action-menu">
      <a href="<?= BASE_URL ?>/musteri/ekle"><i class="fa-solid fa-user-plus"></i> Müşteri Ekle</a>
      <a href="<?= BASE_URL ?>/tedarikci/ekle"><i class="fa-solid fa-industry"></i> Tedarikçi Ekle</a>
      <a href="<?= BASE_URL ?>/satis/ekle"><i class="fa-solid fa-file-invoice"></i> Satış Faturası</a>
      <a href="<?= BASE_URL ?>/alis/ekle"><i class="fa-solid fa-truck"></i> Alış Faturası</a>
      <a href="<?= BASE_URL ?>/urun/ekle"><i class="fa-solid fa-box"></i> Ürün Ekle</a>
      <a href="<?= BASE_URL ?>/musteri"><i class="fa-solid fa-list"></i> Müşteri Listesi</a>
    </div>
  </div>
</div>

<div class="dash-urun-grid">
  <?php if (empty($urunStokListesi)): ?>
    <div class="dash-urun-empty">
      <i class="fa-solid fa-box-open" style="font-size:22px; display:block; margin-bottom:8px; color:var(--muted);"></i>
      Henüz ürün eklenmemiş.
      <div style="margin-top:8px;"><a href="<?= BASE_URL ?>/urun/ekle" style="color:var(--accent); font-weight:700; text-decoration:none;">İlk ürünü ekle »</a></div>
    </div>
  <?php else: ?>
    <?php foreach ($urunStokListesi as $u):
      $miktar = (float)($u['stok_miktari'] ?? 0);
      $kritik = (float)($u['kritik_stok'] ?? 0);
      $durum = $miktar <= 0 ? 'danger' : ($kritik > 0 && $miktar <= $kritik ? 'warn' : '');
    ?>
      <a href="<?= BASE_URL ?>/urun/detay/<?= (int)$u['id'] ?>" class="dash-urun-card">
        <div class="dash-urun-ico<?= $durum ? ' ' . $durum : '' ?>"><i class="fa-solid fa-box"></i></div>
        <div class="dash-urun-name" title="<?= htmlspecialchars($u['ad']) ?>"><?= htmlspecialchars($u['ad']) ?></div>
        <div class="dash-urun-meta">
          <span class="dash-urun-stok<?= $durum ? ' ' . $durum : '' ?>"><?= $fmt($miktar) ?> <?= htmlspecialchars($u['birim'] ?: 'Adet') ?></span>
          <span class="dash-urun-fiyat"><?= $fmt($u['satis_fiyati'] ?? 0) ?> TL</span>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ══════════ 6'LI ÖZET KARTLAR ══════════ -->
<div class="dash-grid6">

  <!-- Varlıklar -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-3">
      <div class="d-flex justify-content-between mb-3">
        <span class="card-title-muted">VARLIKLAR</span>
        <span class="card-title-val"><?= $tl($varliklarToplam) ?></span>
      </div>
      <div class="mb-3">
        <div class="d-flex justify-content-between" style="font-size:11px; color:var(--success); margin-bottom:4px;">
          <span>Kasa / Banka</span><span><?= $tl($kasaBakiye) ?></span>
        </div>
        <div class="progress-custom"><div class="bar" style="width:<?= $pKasa ?>%; background:var(--success);"></div></div>
      </div>
      <div class="mb-3">
        <div class="d-flex justify-content-between" style="font-size:11px; color:var(--info); margin-bottom:4px;">
          <span>Stok</span><span><?= $tl($stokDegeri) ?></span>
        </div>
        <div class="progress-custom"><div class="bar" style="width:<?= $pStok ?>%; background:var(--info);"></div></div>
      </div>
      <div>
        <div class="d-flex justify-content-between" style="font-size:11px; color:var(--warning); margin-bottom:4px;">
          <span>Açık Hesap (Müşteri Alacak)</span><span><?= $tl($acikHesap) ?></span>
        </div>
        <div class="progress-custom"><div class="bar" style="width:<?= $pAcik ?>%; background:var(--warning);"></div></div>
      </div>
    </div>
  </div>

  <!-- Borçlar -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-3">
      <div class="d-flex justify-content-between mb-3">
        <span class="card-title-muted">BORÇLAR</span>
        <span class="card-title-val"><?= $tl($borclarToplam) ?></span>
      </div>
      <div>
        <div class="d-flex justify-content-between" style="font-size:11px; color:var(--danger); margin-bottom:4px;">
          <span>Tedarikçi Borcu</span><span><?= $tl($tedarikciBorc) ?></span>
        </div>
        <div class="progress-custom"><div class="bar" style="width:<?= $borclarToplam > 0 ? 100 : 0 ?>%; background:var(--danger);"></div></div>
      </div>
      <?php if ($borclarToplam <= 0): ?>
        <div style="margin-top:14px; text-align:center; color:var(--muted); font-size:12px;">
          <i class="fa-solid fa-check-circle" style="color:var(--success); font-size:18px; display:block; margin-bottom:6px;"></i>
          Açık tedarikçi borcu yok
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Son Satışlar -->
  <div class="card border-0 shadow-sm p-0" style="overflow:hidden;">
    <div class="phead phead-green d-flex justify-content-between align-items-center">
      <span><i class="fa-solid fa-shopping-cart me-1"></i> SON SATIŞLAR</span>
      <a href="<?= BASE_URL ?>/satis" style="color:rgba(255,255,255,.8); font-size:11px; text-decoration:none;">tümü »</a>
    </div>
    <?php if (empty($sonSatislar)): ?>
      <div class="no-data">Henüz satış kaydı yok.</div>
    <?php else: ?>
      <table class="mini-table">
        <thead><tr><th>Müşteri</th><th>Tarih</th><th style="text-align:right;">Tutar</th></tr></thead>
        <tbody>
          <?php foreach ($sonSatislar as $f): ?>
            <tr onclick="location.href='<?= BASE_URL ?>/satis/detay/<?= $f['id'] ?>'" style="cursor:pointer;">
              <td style="max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($f['cari_unvan'] ?? '') ?>">
                <?= htmlspecialchars(mb_strimwidth($f['cari_unvan'] ?? '—', 0, 22, '…')) ?>
              </td>
              <td style="color:var(--muted); white-space:nowrap;"><?= date('d.m.Y', strtotime($f['fatura_tarihi'])) ?></td>
              <td style="text-align:right; font-weight:700; white-space:nowrap;"><?= $fmt($f['genel_toplam']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <div style="padding:8px 12px; border-top:1px solid var(--border); text-align:right;">
      <a href="<?= BASE_URL ?>/satis/ekle" style="font-size:12px; font-weight:600; color:var(--success); text-decoration:none;">
        <i class="fa-solid fa-plus"></i> Yeni Satış Ekle
      </a>
    </div>
  </div>

  <!-- Son Alışlar -->
  <div class="card border-0 shadow-sm p-0" style="overflow:hidden;">
    <div class="phead phead-orange d-flex justify-content-between align-items-center">
      <span><i class="fa-solid fa-truck me-1"></i> SON ALIŞLAR</span>
      <a href="<?= BASE_URL ?>/alis" style="color:rgba(255,255,255,.8); font-size:11px; text-decoration:none;">tümü »</a>
    </div>
    <?php if (empty($sonAlislar)): ?>
      <div class="no-data">Henüz alış kaydı yok.</div>
    <?php else: ?>
      <table class="mini-table">
        <thead><tr><th>Tedarikçi</th><th>Tarih</th><th style="text-align:right;">Tutar</th></tr></thead>
        <tbody>
          <?php foreach ($sonAlislar as $f): ?>
            <tr onclick="location.href='<?= BASE_URL ?>/alis/detay/<?= $f['id'] ?>'" style="cursor:pointer;">
              <td style="max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($f['cari_unvan'] ?? '') ?>">
                <?= htmlspecialchars(mb_strimwidth($f['cari_unvan'] ?? '—', 0, 22, '…')) ?>
              </td>
              <td style="color:var(--muted); white-space:nowrap;"><?= date('d.m.Y', strtotime($f['fatura_tarihi'])) ?></td>
              <td style="text-align:right; font-weight:700; white-space:nowrap;"><?= $fmt($f['genel_toplam']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <div style="padding:8px 12px; border-top:1px solid var(--border); text-align:right;">
      <a href="<?= BASE_URL ?>/alis/ekle" style="font-size:12px; font-weight:600; color:var(--warning); text-decoration:none;">
        <i class="fa-solid fa-plus"></i> Yeni Alış Ekle
      </a>
    </div>
  </div>

  <!-- Aylık Satış -->
  <div class="card border-0 shadow-sm p-0" style="overflow:hidden;">
    <div class="phead phead-cyan">
      <i class="fa-solid fa-chart-bar me-1"></i> AYLIK SATIŞ (SON 6 AY)
    </div>
    <div class="card-body p-3">
      <?php if (empty($aylikTrend)): ?>
        <div class="no-data" style="padding:20px;">Henüz satış verisi yok.</div>
      <?php else: ?>
        <?php
          // Defansif: $aylikTrend boş gelebilir veya 'toplam' anahtarı eksik olabilir.
          $toplamlar = array_map('floatval', array_column((array)$aylikTrend, 'toplam'));
          $maxNumeric = !empty($toplamlar) ? max($toplamlar) : 0.0;
          $maxVal     = max((float)$maxNumeric, 1.0);   // en az 1 — sıfıra bölmeyi önler
          $ayAdlari  = ['01'=>'Oca','02'=>'Şub','03'=>'Mar','04'=>'Nis','05'=>'May','06'=>'Haz',
                        '07'=>'Tem','08'=>'Ağu','09'=>'Eyl','10'=>'Eki','11'=>'Kas','12'=>'Ara'];
        ?>
        <div style="display:flex; align-items:flex-end; gap:6px; height:120px; padding-bottom:4px;">
          <?php foreach ($aylikTrend as $ay): ?>
            <?php
              $ayToplam = is_numeric($ay['toplam'] ?? null) ? (float)$ay['toplam'] : 0.0;
              $oran     = max(1, (int)round($ayToplam / $maxVal * 100));
              $ayKod    = substr((string)($ay['ay'] ?? ''), 5, 2);
              $ayLabel  = $ayAdlari[$ayKod] ?? $ayKod;
            ?>
            <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:4px;">
              <div style="font-size:9px; color:var(--muted); font-weight:600;"><?= $fmt($ayToplam) ?></div>
              <div style="width:100%; background:linear-gradient(180deg,var(--info),#60a5fa); border-radius:3px 3px 0 0; height:<?= $oran ?>%;" title="<?= htmlspecialchars((string)($ay['ay'] ?? '')) ?>: <?= $fmt($ayToplam) ?> TL"></div>
              <div style="font-size:10px; color:var(--muted); font-weight:600;"><?= $ayLabel ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Vadesi Geçen Alacaklar -->
  <div class="card border-0 shadow-sm p-0" style="overflow:hidden;">
    <div class="phead phead-dark d-flex justify-content-between align-items-center">
      <span><i class="fa-solid fa-clock me-1"></i> VADESİ GEÇEN ALACAKLAR</span>
      <a href="<?= BASE_URL ?>/satis?durum=bekliyor" style="color:rgba(255,255,255,.7); font-size:11px; text-decoration:none;">tümü »</a>
    </div>
    <?php if (empty($vadesiGecenler)): ?>
      <div class="no-data"><i class="fa-solid fa-check-circle" style="color:var(--success); font-size:22px; display:block; margin-bottom:8px;"></i>Vadesi geçen alacak yok</div>
    <?php else: ?>
      <table class="mini-table">
        <thead><tr><th>Müşteri</th><th style="text-align:right;">Gün</th><th style="text-align:right;">Tutar</th></tr></thead>
        <tbody>
          <?php foreach ($vadesiGecenler as $row): ?>
            <tr onclick="location.href='<?= BASE_URL ?>/satis/detay/<?= (int)$row['id'] ?>'" style="cursor:pointer;">
              <td style="max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($row['ad'] ?? '') ?>">
                <?= htmlspecialchars(mb_strimwidth($row['ad'] ?? 'Belirtilmedi', 0, 28, '…')) ?>
              </td>
              <td style="text-align:right; color:var(--danger); font-weight:700;"><?= (int)$row['gun'] ?></td>
              <td style="text-align:right; font-weight:600;"><?= $fmt($row['tutar']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div><!-- /dash-grid6 -->

<?php if (!empty($superAdminSummary)): $sas = $superAdminSummary; $ozet = $sas['ozet']; ?>
<div class="mt-4">
  <h5 class="mb-3"><i class="fa-solid fa-user-shield me-2"></i>Süper Yönetici Özeti</h5>
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold"><?= (int)$ozet['users_total'] ?></div>
        <div class="text-muted small">Toplam Kullanıcı</div>
        <div class="small mt-1">
          <span class="badge bg-success"><?= (int)$ozet['users_active'] ?> aktif</span>
          <span class="badge bg-secondary"><?= (int)$ozet['users_passive'] ?> pasif</span>
          <?php if ($ozet['users_locked'] > 0): ?><span class="badge bg-danger"><?= (int)$ozet['users_locked'] ?> kilitli</span><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold"><?= (int)$ozet['actions_today'] ?></div>
        <div class="text-muted small">Bugünkü İşlemler</div>
        <div class="small mt-1 text-muted">Son 24s: <?= (int)$ozet['actions_24h'] ?> · Son 7g: <?= (int)$ozet['actions_7d'] ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold <?= $ozet['failed_logins_24h'] > 5 ? 'text-danger' : '' ?>"><?= (int)$ozet['failed_logins_24h'] ?></div>
        <div class="text-muted small">Başarısız Giriş (24s)</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm p-3 text-center">
        <div class="fs-4 fw-bold <?= $ozet['unauthorized_24h'] > 0 ? 'text-danger' : '' ?>"><?= (int)$ozet['unauthorized_24h'] ?></div>
        <div class="text-muted small">Yetkisiz Erişim Denemesi (24s)</div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-7">
      <div class="card border-0 shadow-sm p-0" style="overflow:hidden;">
        <div class="phead phead-dark d-flex justify-content-between align-items-center">
          <span><i class="fa-solid fa-triangle-exclamation me-1"></i> KRİTİK İŞLEMLER</span>
          <a href="<?= BASE_URL ?>/audit" style="color:rgba(255,255,255,.7); font-size:11px; text-decoration:none;">tümü »</a>
        </div>
        <?php if (empty($sas['kritikIslemler'])): ?>
          <div class="no-data">Henüz kritik işlem yok</div>
        <?php else: ?>
          <table class="mini-table">
            <thead><tr><th>Tarih</th><th>Kullanıcı</th><th>İşlem</th></tr></thead>
            <tbody>
              <?php foreach ($sas['kritikIslemler'] as $k): ?>
                <tr>
                  <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime($k['created_at']))) ?></td>
                  <td><?= htmlspecialchars($k['user_snapshot'] ?: 'Sistem') ?></td>
                  <td><?= htmlspecialchars($k['action']) ?> <span class="text-muted"><?= htmlspecialchars($k['module'] ?? '') ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-md-5">
      <div class="card border-0 shadow-sm p-0" style="overflow:hidden;">
        <div class="phead phead-cyan">
          <i class="fa-solid fa-users me-1"></i> SON 7 GÜNDE EN AKTİF KULLANICILAR
        </div>
        <?php if (empty($sas['aktifKullanicilar'])): ?>
          <div class="no-data">Aktivite yok</div>
        <?php else: ?>
          <table class="mini-table">
            <thead><tr><th>Kullanıcı</th><th style="text-align:right;">İşlem</th></tr></thead>
            <tbody>
              <?php foreach ($sas['aktifKullanicilar'] as $a): ?>
                <tr>
                  <td><?= htmlspecialchars($a['user_snapshot'] ?: '-') ?></td>
                  <td style="text-align:right; font-weight:700;"><?= (int)$a['islem_sayisi'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
