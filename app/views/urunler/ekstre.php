<?php
/**
 * View: urunler/ekstre.php — Stok Ekstresi
 * Beklenen değişkenler:
 *   $urun, $hareketler, $devir, $bas, $bit,
 *   $toplamGiris, $toplamCikis, $sonBakiye
 */
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');

/** aciklama alanından hareketin kaynağını türetir (Fatura.php bu ön ekleri yazıyor) */
$kaynak = static function (string $aciklama): array {
    if (str_starts_with($aciklama, 'Alış Faturası'))  return ['Alış Faturası', 'bg-green'];
    if (str_starts_with($aciklama, 'Satış Faturası')) return ['Satış Faturası', 'bg-blue'];
    return ['Manuel', 'bg-grey'];
};
?>
<style>
  .ek-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px; }
  .ek-back { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:var(--surface-2); border:1px solid var(--border); border-radius:4px; color:var(--text); font-size:13px; font-weight:600; text-decoration:none; }
  .ek-back:hover { background:var(--card-bg); color:var(--text); }

  .ek-sum { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:18px; }
  @media (max-width:1024px){ .ek-sum { grid-template-columns:repeat(2,1fr); } }
  @media (max-width:576px){ .ek-sum { grid-template-columns:1fr; } }
  .ek-sum-card { background:var(--card-bg); border:1px solid var(--border); border-radius:6px; padding:14px 16px; }
  .ek-sum-card .t { font-size:12px; color:var(--muted); font-weight:600; margin-bottom:6px; }
  .ek-sum-card .v { font-size:19px; font-weight:800; color:var(--text); }
  .v-green { color:var(--success) !important; }
  .v-red   { color:var(--danger)  !important; }

  .ek-filter { background:var(--card-bg); border:1px solid var(--border); border-radius:6px; padding:12px 14px; margin-bottom:16px; display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; }
  .ek-filter .fg { display:flex; flex-direction:column; gap:5px; }
  .ek-filter label { font-size:12px; font-weight:700; color:var(--text2); }
  .ek-filter input[type=date] { padding:7px 10px; border:1px solid var(--border2); border-radius:4px; background:var(--card-bg); color:var(--text); font-size:13px; }
  .ek-btn { padding:8px 16px; border:none; border-radius:4px; font-size:13px; font-weight:600; cursor:pointer; color:#fff; background:#337ab7; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
  .ek-btn-clear { background:var(--surface-2); color:var(--text); border:1px solid var(--border); }

  .ek-table-wrap { border:1px solid var(--border); border-radius:6px; overflow-x:auto; background:var(--card-bg); }
  .ek-table { width:100%; border-collapse:collapse; font-size:13px; color:var(--text); }
  .ek-table th { background:var(--surface-2); padding:10px 12px; text-align:left; font-size:11.5px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:var(--text2); border-bottom:2px solid var(--border); white-space:nowrap; }
  .ek-table td { padding:9px 12px; border-bottom:1px solid var(--border); vertical-align:middle; }
  .ek-table tbody tr:hover { background:var(--surface-2); }
  .ek-table tfoot td { padding:11px 12px; font-weight:800; background:var(--surface-2); border-top:2px solid var(--border); }
  .num { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
  .devir-row td { background:var(--surface-2); font-weight:700; color:var(--text2); }

  .k-badge { display:inline-block; font-size:10px; font-weight:800; padding:3px 8px; border-radius:3px; color:#fff; text-transform:uppercase; white-space:nowrap; }
  .bg-green { background:#5cb85c; }
  .bg-blue  { background:#337ab7; }
  .bg-grey  { background:#6b7280; }

  .ek-empty { padding:34px 16px; text-align:center; color:var(--muted); font-size:13px; }
</style>

<div class="page-container">

  <?php $flash = $flash ?? []; ?>
  <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= ($flash['tip'] ?? '') === 'success' ? 'success' : 'danger' ?> mb-3">
      <?= htmlspecialchars($flash['mesaj'] ?? '') ?>
    </div>
  <?php endif; ?>

  <div class="ek-head">
    <div>
      <h2 style="font-size:18px; font-weight:800; color:var(--text); margin:0;">
        <i class="fa-solid fa-list"></i> Stok Ekstresi — <?= htmlspecialchars($urun['ad']) ?>
      </h2>
      <p style="font-size:12.5px; color:var(--muted); margin:4px 0 0;">
        Fatura kaynaklı ve manuel tüm stok hareketleri, tarih sırasıyla.
      </p>
    </div>
    <a href="<?= BASE_URL ?>/urun/detay/<?= (int)$urun['id'] ?>" class="ek-back">
      <i class="fa-solid fa-arrow-left"></i> Ürün Detayına Dön
    </a>
  </div>

  <!-- Özet -->
  <div class="ek-sum">
    <div class="ek-sum-card">
      <div class="t">Toplam Giriş</div>
      <div class="v v-green"><?= $fmt($toplamGiris) ?> <?= htmlspecialchars($urun['birim']) ?></div>
    </div>
    <div class="ek-sum-card">
      <div class="t">Toplam Çıkış</div>
      <div class="v v-red"><?= $fmt($toplamCikis) ?> <?= htmlspecialchars($urun['birim']) ?></div>
    </div>
    <div class="ek-sum-card">
      <div class="t">Ekstre Sonu Bakiye</div>
      <div class="v"><?= $fmt($sonBakiye) ?> <?= htmlspecialchars($urun['birim']) ?></div>
    </div>
    <div class="ek-sum-card">
      <div class="t">Karttaki Güncel Stok</div>
      <div class="v"><?= $fmt($urun['stok_miktari']) ?> <?= htmlspecialchars($urun['birim']) ?></div>
    </div>
  </div>

  <!-- Tarih filtresi -->
  <form method="GET" action="<?= BASE_URL ?>/urun/stokEkstresi/<?= (int)$urun['id'] ?>" class="ek-filter">
    <div class="fg">
      <label for="bas">Başlangıç Tarihi</label>
      <input type="date" id="bas" name="bas" value="<?= htmlspecialchars($bas) ?>">
    </div>
    <div class="fg">
      <label for="bit">Bitiş Tarihi</label>
      <input type="date" id="bit" name="bit" value="<?= htmlspecialchars($bit) ?>">
    </div>
    <button type="submit" class="ek-btn"><i class="fa-solid fa-filter"></i> Filtrele</button>
    <?php if ($bas !== '' || $bit !== ''): ?>
      <a href="<?= BASE_URL ?>/urun/stokEkstresi/<?= (int)$urun['id'] ?>" class="ek-btn ek-btn-clear">
        <i class="fa-solid fa-xmark"></i> Filtreyi Temizle
      </a>
    <?php endif; ?>
  </form>

  <!-- Ekstre tablosu -->
  <div class="ek-table-wrap">
    <?php if (empty($hareketler)): ?>
      <div class="ek-empty">
        <i class="fa-solid fa-inbox" style="font-size:26px; display:block; margin-bottom:10px;"></i>
        Seçilen aralıkta bu ürüne ait stok hareketi bulunmuyor.
        <?php if ($bas !== ''): ?>
          <div style="margin-top:10px; color:var(--text2); font-weight:700;">
            Devreden bakiye (<?= htmlspecialchars($bas) ?> öncesi): <?= $fmt($devir) ?> <?= htmlspecialchars($urun['birim']) ?>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <table class="ek-table">
        <thead>
          <tr>
            <th>Tarih</th>
            <th>Kaynak</th>
            <th>Depo</th>
            <th>Açıklama</th>
            <th class="num">Giriş</th>
            <th class="num">Çıkış</th>
            <th class="num">Bakiye</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($bas !== ''): ?>
            <tr class="devir-row">
              <td colspan="6">Devreden bakiye (<?= htmlspecialchars($bas) ?> öncesi)</td>
              <td class="num"><?= $fmt($devir) ?></td>
            </tr>
          <?php endif; ?>

          <?php foreach ($hareketler as $h): ?>
            <?php [$kAd, $kSinif] = $kaynak((string)($h['aciklama'] ?? '')); ?>
            <tr>
              <td style="white-space:nowrap;"><?= htmlspecialchars(date('d.m.Y H:i', strtotime((string)$h['olusturulma_tarihi']))) ?></td>
              <td><span class="k-badge <?= $kSinif ?>"><?= htmlspecialchars($kAd) ?></span></td>
              <td><?= htmlspecialchars($h['depo_adi'] ?: '—') ?></td>
              <td><?= htmlspecialchars((string)($h['aciklama'] ?? '')) ?></td>
              <td class="num v-green"><?= $h['giris'] > 0 ? $fmt($h['giris']) : '' ?></td>
              <td class="num v-red"><?= $h['cikis'] > 0 ? $fmt($h['cikis']) : '' ?></td>
              <td class="num"><?= $fmt($h['bakiye']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4">TOPLAM (<?= count($hareketler) ?> hareket)</td>
            <td class="num v-green"><?= $fmt($toplamGiris) ?></td>
            <td class="num v-red"><?= $fmt($toplamCikis) ?></td>
            <td class="num"><?= $fmt($sonBakiye) ?></td>
          </tr>
        </tfoot>
      </table>
    <?php endif; ?>
  </div>

</div>
