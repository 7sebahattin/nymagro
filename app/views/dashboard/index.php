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
    return match($d) {
        'onaylandi'    => '<span style="background:#22c55e;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;">Onaylı</span>',
        'taslak'       => '<span style="background:#f59e0b;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;">Taslak</span>',
        'kismi_odendi' => '<span style="background:#3b82f6;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;">Kısmî</span>',
        'odendi'       => '<span style="background:#6366f1;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;">Ödendi</span>',
        'iptal'        => '<span style="background:#ef4444;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;">İptal</span>',
        default        => '<span style="background:#94a3b8;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;">' . htmlspecialchars($d) . '</span>',
    };
};
?>
<style>
  /* ── Stat Kartları ── */
  .stat-box { color:#fff; padding:20px 24px; border-radius:8px; display:flex; align-items:center; gap:16px; box-shadow:0 2px 8px rgba(0,0,0,.12); margin-bottom:16px; }
  .stat-box i { font-size:38px; opacity:.8; flex-shrink:0; }
  .stat-box .value { font-size:22px; font-weight:700; letter-spacing:.5px; }
  .stat-box .label { font-size:12px; opacity:.9; }
  .bg-blue      { background: linear-gradient(135deg,#3b7dd8,#5b82b9); }
  .bg-salmon    { background: linear-gradient(135deg,#e05f50,#f47463); }
  .bg-lightblue { background: linear-gradient(135deg,#2fa4ca,#49b4d8); }

  /* ── Sayaç kutuları ── */
  .counter-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
  .counter-box { background:var(--card-bg); border-radius:8px; padding:16px 20px; text-align:center; box-shadow:0 1px 6px rgba(0,0,0,.07); }
  .counter-box .cval { font-size:28px; font-weight:800; color:var(--text); line-height:1; }
  .counter-box .clabel { font-size:11px; color:var(--muted); font-weight:600; text-transform:uppercase; margin-top:4px; }
  .counter-box .cicon { font-size:20px; margin-bottom:8px; }

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

  @media (max-width:768px) {
    .stat-box { padding:14px; } .counter-row { grid-template-columns:repeat(2,1fr); }
  }
</style>

<!-- ══════════ HEADER ══════════ -->
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
  <div>
    <h4 style="color:var(--text); font-size:20px; font-weight:600; margin-bottom:4px;"><?= htmlspecialchars($bugun) ?></h4>
    <p style="font-size:12px; color:var(--muted); margin:0;"><?= htmlspecialchars(APP_NAME) ?> — Özet Dashboard</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <div class="bg-white border text-center" style="min-width:130px; padding:10px 15px; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,.06);">
      <div style="font-size:10px; color:var(--muted); font-weight:700; margin-bottom:4px;">BUGÜNKÜ SATIŞ</div>
      <div style="font-size:14px; color:var(--text); font-weight:700;"><?= $tl($bugunkuSatis) ?></div>
    </div>
    <div class="bg-white border text-center" style="min-width:130px; padding:10px 15px; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,.06);">
      <div style="font-size:10px; color:var(--muted); font-weight:700; margin-bottom:4px;">BUGÜNKÜ TAHSİLAT</div>
      <div style="font-size:14px; color:var(--text); font-weight:700;"><?= $tl($bugunkuTahsilat) ?></div>
    </div>
  </div>
</div>

<!-- ══════════ SAYAÇLAR ══════════ -->
<div class="counter-row">
  <div class="counter-box">
    <div class="cicon" style="color:#3b82f6;"><i class="fa-solid fa-users"></i></div>
    <div class="cval"><?= number_format($musteriSay) ?></div>
    <div class="clabel">Müşteri</div>
  </div>
  <div class="counter-box">
    <div class="cicon" style="color:#f59e0b;"><i class="fa-solid fa-industry"></i></div>
    <div class="cval"><?= number_format($tedarikciSay) ?></div>
    <div class="clabel">Tedarikçi</div>
  </div>
  <div class="counter-box">
    <div class="cicon" style="color:#22c55e;"><i class="fa-solid fa-tags"></i></div>
    <div class="cval"><?= number_format($urunSay) ?></div>
    <div class="clabel">Ürün / Hizmet</div>
  </div>
  <div class="counter-box">
    <div class="cicon" style="color:<?= $bekleyenFatur > 0 ? '#ef4444' : '#94a3b8' ?>;"><i class="fa-solid fa-file-invoice"></i></div>
    <div class="cval" style="color:<?= $bekleyenFatur > 0 ? '#ef4444' : '#1e293b' ?>;"><?= number_format($bekleyenFatur) ?></div>
    <div class="clabel">Bekleyen Fatura</div>
  </div>
</div>

<!-- ══════════ 3 STAT KUTU ══════════ -->
<div class="row">
  <div class="col-md-4">
    <div class="stat-box bg-blue">
      <i class="fa-solid fa-chart-line"></i>
      <div>
        <div class="value"><?= $tl($buAyCiro) ?></div>
        <div class="label"><?= htmlspecialchars($buAy) ?> Cirosu (Satış)</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-box bg-salmon">
      <i class="fa-solid fa-truck"></i>
      <div>
        <div class="value"><?= $tl($buAyAlis) ?></div>
        <div class="label"><?= htmlspecialchars($buAy) ?> Alış Toplamı</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-box bg-lightblue">
      <i class="fa-solid fa-warehouse"></i>
      <div>
        <div class="value"><?= $tl($stokDegeri) ?></div>
        <div class="label">Toplam Stok Değeri</div>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ ANA GRID ══════════ -->
<div class="row g-3">

  <!-- ── Sol Kolon ── -->
  <div class="col-lg-4">

    <!-- Varlıklar -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between mb-3">
          <span class="card-title-muted">VARLIKLAR</span>
          <span class="card-title-val"><?= $tl($varliklarToplam) ?></span>
        </div>
        <div class="mb-3">
          <div class="d-flex justify-content-between" style="font-size:11px; color:#22c55e; margin-bottom:4px;">
            <span>Kasa / Banka</span><span><?= $tl($kasaBakiye) ?></span>
          </div>
          <div class="progress-custom"><div class="bar" style="width:<?= $pKasa ?>%; background:#22c55e;"></div></div>
        </div>
        <div class="mb-3">
          <div class="d-flex justify-content-between" style="font-size:11px; color:#3b82f6; margin-bottom:4px;">
            <span>Stok</span><span><?= $tl($stokDegeri) ?></span>
          </div>
          <div class="progress-custom"><div class="bar" style="width:<?= $pStok ?>%; background:#3b82f6;"></div></div>
        </div>
        <div>
          <div class="d-flex justify-content-between" style="font-size:11px; color:#f59e0b; margin-bottom:4px;">
            <span>Açık Hesap (Müşteri Alacak)</span><span><?= $tl($acikHesap) ?></span>
          </div>
          <div class="progress-custom"><div class="bar" style="width:<?= $pAcik ?>%; background:#f59e0b;"></div></div>
        </div>
      </div>
    </div>

    <!-- Borçlar -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body p-3">
        <div class="d-flex justify-content-between mb-3">
          <span class="card-title-muted">BORÇLAR</span>
          <span class="card-title-val"><?= $tl($borclarToplam) ?></span>
        </div>
        <div>
          <div class="d-flex justify-content-between" style="font-size:11px; color:#ef4444; margin-bottom:4px;">
            <span>Tedarikçi Borcu</span><span><?= $tl($tedarikciBorc) ?></span>
          </div>
          <div class="progress-custom"><div class="bar" style="width:<?= $borclarToplam > 0 ? 100 : 0 ?>%; background:#ef4444;"></div></div>
        </div>
      </div>
    </div>

    <!-- Vadesi Geçen Alacaklar -->
    <div class="card border-0 shadow-sm">
      <div class="phead phead-dark d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-clock me-1"></i> VADESİ GEÇEN ALACAKLAR</span>
        <a href="<?= BASE_URL ?>/satis?durum=bekliyor" style="color:rgba(255,255,255,.7); font-size:11px; text-decoration:none;">tümü »</a>
      </div>
      <?php if (empty($vadesiGecenler)): ?>
        <div class="no-data"><i class="fa-solid fa-check-circle" style="color:#22c55e; font-size:22px; display:block; margin-bottom:8px;"></i>Vadesi geçen alacak yok</div>
      <?php else: ?>
        <table class="mini-table">
          <thead><tr><th>Müşteri</th><th style="text-align:right;">Gün</th><th style="text-align:right;">Tutar</th></tr></thead>
          <tbody>
            <?php foreach ($vadesiGecenler as $row): ?>
              <tr>
                <td style="max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($row['ad'] ?? '') ?>">
                  <?= htmlspecialchars(mb_strimwidth($row['ad'] ?? 'Belirtilmedi', 0, 28, '…')) ?>
                </td>
                <td style="text-align:right; color:#ef4444; font-weight:700;"><?= (int)$row['gun'] ?></td>
                <td style="text-align:right; font-weight:600;"><?= $fmt($row['tutar']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div><!-- /Sol -->

  <!-- ── Orta Kolon ── -->
  <div class="col-lg-4">

    <!-- Son Satışlar -->
    <div class="card border-0 shadow-sm mb-3">
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
        <a href="<?= BASE_URL ?>/satis/ekle" style="font-size:12px; font-weight:600; color:#22c55e; text-decoration:none;">
          <i class="fa-solid fa-plus"></i> Yeni Satış Ekle
        </a>
      </div>
    </div>

    <!-- Son Alışlar -->
    <div class="card border-0 shadow-sm">
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
        <a href="<?= BASE_URL ?>/alis/ekle" style="font-size:12px; font-weight:600; color:#f59e0b; text-decoration:none;">
          <i class="fa-solid fa-plus"></i> Yeni Alış Ekle
        </a>
      </div>
    </div>

  </div><!-- /Orta -->

  <!-- ── Sağ Kolon ── -->
  <div class="col-lg-4">

    <!-- Aylık Satış Grafiği -->
    <div class="card border-0 shadow-sm mb-3">
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
                <div style="width:100%; background:linear-gradient(180deg,#3b82f6,#60a5fa); border-radius:3px 3px 0 0; height:<?= $oran ?>%;" title="<?= htmlspecialchars((string)($ay['ay'] ?? '')) ?>: <?= $fmt($ayToplam) ?> TL"></div>
                <div style="font-size:10px; color:var(--muted); font-weight:600;"><?= $ayLabel ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Hızlı Erişim -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="phead" style="background:#1e293b;">
        <i class="fa-solid fa-bolt me-1"></i> HIZLI ERİŞİM
      </div>
      <div class="card-body p-2">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
          <a href="<?= BASE_URL ?>/musteri/ekle" class="d-flex align-items-center gap-2 p-2 rounded text-decoration-none" style="background:rgba(46,204,113,.10); color:var(--success); font-size:12px; font-weight:600;">
            <i class="fa-solid fa-user-plus"></i> Müşteri Ekle
          </a>
          <a href="<?= BASE_URL ?>/tedarikci/ekle" class="d-flex align-items-center gap-2 p-2 rounded text-decoration-none" style="background:rgba(243,156,18,.15); color:var(--warning); font-size:12px; font-weight:600;">
            <i class="fa-solid fa-industry"></i> Tedarikçi Ekle
          </a>
          <a href="<?= BASE_URL ?>/satis/ekle" class="d-flex align-items-center gap-2 p-2 rounded text-decoration-none" style="background:rgba(59,130,246,.15); color:var(--info); font-size:12px; font-weight:600;">
            <i class="fa-solid fa-file-invoice"></i> Satış Faturası
          </a>
          <a href="<?= BASE_URL ?>/alis/ekle" class="d-flex align-items-center gap-2 p-2 rounded text-decoration-none" style="background:rgba(243,156,18,.13); color:var(--warning); font-size:12px; font-weight:600;">
            <i class="fa-solid fa-truck"></i> Alış Faturası
          </a>
          <a href="<?= BASE_URL ?>/urun/ekle" class="d-flex align-items-center gap-2 p-2 rounded text-decoration-none" style="background:rgba(243,156,18,.11); color:var(--warning); font-size:12px; font-weight:600;">
            <i class="fa-solid fa-box"></i> Ürün Ekle
          </a>
          <a href="<?= BASE_URL ?>/musteri" class="d-flex align-items-center gap-2 p-2 rounded text-decoration-none" style="background:rgba(59,130,246,.10); color:var(--info); font-size:12px; font-weight:600;">
            <i class="fa-solid fa-list"></i> Müşteri Listesi
          </a>
        </div>
      </div>
    </div>

    <!-- Sistem Durumu -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-3">
        <p class="card-title-muted mb-3">SİSTEM DURUMU</p>
        <div style="font-size:12px; color:var(--text2); line-height:2;">
          <div class="d-flex justify-content-between">
            <span>PHP Versiyonu</span>
            <span style="font-weight:600; color:var(--text);"><?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?></span>
          </div>
          <div class="d-flex justify-content-between">
            <span>Veritabanı</span>
            <span style="font-weight:600; color:#22c55e;"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Bağlı</span>
          </div>
          <div class="d-flex justify-content-between">
            <span>Sunucu Saati</span>
            <span style="font-weight:600; color:var(--text);"><?= date('H:i') ?></span>
          </div>
          <div class="d-flex justify-content-between">
            <span>Uygulama</span>
            <span style="font-weight:600; color:var(--text);"><?= htmlspecialchars(APP_NAME) ?></span>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /Sağ -->
</div><!-- /row -->
