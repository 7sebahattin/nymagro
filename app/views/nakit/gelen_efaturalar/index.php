<?php
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
$statusText = [
    'odenmedi' => 'Ödenmedi',
    'kismi_odendi' => 'Kısmi Ödendi',
    'odendi' => 'Ödendi',
];
$dueText = [
    'vade_yok' => 'Vade Yok',
    'vadesi_gecti' => 'Vadesi Geçti',
    'vadesi_yaklasiyor' => 'Vadesi Yaklaşıyor',
    'vadesinde' => 'Vadesinde',
    'odendi' => 'Ödendi',
];
$buildUrl = function(array $extra = []) use ($filters) {
    $params = array_merge($filters, $extra);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return BASE_URL . '/nakit/gelen-e-faturalar' . ($params ? '?' . http_build_query($params) : '');
};
?>
<style>
.gef-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.gef-btn{border:0;border-radius:4px;padding:8px 12px;font-size:12.5px;font-weight:700;color:#fff;text-decoration:none;display:inline-flex;align-items:center;gap:6px;cursor:pointer}
.gef-btn.green{background:#5cb85c}.gef-btn.blue{background:#337ab7}.gef-btn.gray{background:#64748b}.gef-btn.orange{background:#f0ad4e}.gef-btn.red{background:#d9534f}
.gef-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
.gef-card{background:var(--card-bg);border:1px solid var(--border);border-left:4px solid #337ab7;border-radius:6px;padding:12px 14px}
.gef-card .l{font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:700}.gef-card .v{font-size:17px;font-weight:800;color:var(--text);margin-top:4px}
.gef-filters{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;padding:12px;margin-bottom:14px}
.gef-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:8px}
.gef-grid label{font-size:11px;font-weight:700;color:var(--text2)}.gef-grid input,.gef-grid select{width:100%;padding:7px;border:1px solid var(--border2);border-radius:4px;font-size:12px}
.gef-quick{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}.gef-quick a{font-size:12px;padding:5px 9px;border-radius:4px;border:1px solid var(--border2);text-decoration:none;color:var(--text2);background:var(--surface-2)}
.gef-table-wrap{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;overflow:hidden}.gef-table{width:100%;border-collapse:collapse;min-width:1280px}
.gef-table th{background:#2c3e6b;color:var(--text);font-size:11.5px;text-align:left;padding:9px;white-space:nowrap}.gef-table td{font-size:12.5px;padding:8px 9px;border-bottom:1px solid var(--border);vertical-align:middle}
.txt-r{text-align:right}.badge{display:inline-block;border-radius:4px;padding:3px 7px;font-size:11px;font-weight:700;white-space:nowrap}
.b-red{background:rgba(231,76,60,.15);color:var(--danger)}.b-orange{background:rgba(243,156,18,.17);color:var(--warning)}.b-green{background:rgba(46,204,113,.15);color:var(--success)}.b-gray{background:var(--surface-2);color:var(--text2)}.b-yellow{background:rgba(243,156,18,.15);color:var(--warning)}
.empty{padding:42px;text-align:center;color:var(--muted)}.pag{display:flex;justify-content:space-between;align-items:center;padding:10px;background:var(--surface-2);border-top:1px solid var(--border)}.pag a{padding:5px 9px;border:1px solid var(--border2);border-radius:4px;text-decoration:none;color:var(--text2)}
@media(max-width:1100px){.gef-cards{grid-template-columns:repeat(2,1fr)}.gef-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:650px){.gef-cards,.gef-grid{grid-template-columns:1fr}}

/* ── TOPLU ÖDEME PANELİ ── */
.top-panel-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:none}
.top-panel-overlay.open{display:block}
.top-panel{position:fixed;top:0;right:-100%;height:100%;width:min(820px,98vw);background:var(--ink);border-left:1px solid var(--border2);z-index:1001;display:flex;flex-direction:column;box-shadow:-6px 0 32px rgba(0,0,0,.18);transition:right .28s cubic-bezier(.4,0,.2,1)}
.top-panel.open{right:0}
.top-panel-hdr{background:linear-gradient(135deg,#2c3e6b,#1e293b);color:#fff;padding:16px 22px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0}
.top-panel-hdr h5{margin:0;font-size:15px;font-weight:700;display:flex;align-items:center;gap:9px}
.top-panel-close{background:none;border:none;color:#fff;font-size:24px;cursor:pointer;opacity:.8;line-height:1;padding:0}
.top-panel-close:hover{opacity:1}
.top-panel-settings{background:var(--surface-2);border-bottom:1px solid var(--border);padding:14px 22px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;flex-shrink:0}
.top-panel-settings label{font-size:11px;font-weight:700;color:var(--text2);display:block;margin-bottom:4px}
.top-panel-settings input,.top-panel-settings select,.top-panel-settings textarea{padding:7px 9px;border:1px solid var(--border2);border-radius:5px;font-size:12.5px;color:var(--text);outline:none}
.top-panel-settings input:focus,.top-panel-settings select:focus{border-color:var(--text)}
.tp-settings-col{display:flex;flex-direction:column}
.tp-apply-all{background:#f0ad4e;color:#fff;border:none;border-radius:5px;padding:7px 14px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;align-self:flex-end}
.tp-apply-all:hover{filter:brightness(1.08)}
.top-panel-body{flex:1;overflow-y:auto;padding:16px 22px}
.top-panel-body table{width:100%;border-collapse:collapse}
.top-panel-body thead th{background:var(--surface-2);font-size:11.5px;font-weight:700;color:var(--text2);padding:8px 10px;text-align:left;position:sticky;top:0;z-index:2}
.top-panel-body tbody tr{border-bottom:1px solid var(--border)}
.top-panel-body tbody tr:hover{background:var(--surface-2)}
.top-panel-body tbody td{padding:9px 10px;font-size:12.5px;color:var(--text2);vertical-align:middle}
.tp-tutar-inp{width:110px;padding:6px 8px;border:1px solid var(--border2);border-radius:4px;font-size:13px;text-align:right;font-weight:600;color:var(--text)}
.tp-tutar-inp:focus{border-color:var(--info);outline:none}
.tp-tutar-inp.tam{border-color:#16a34a;background:rgba(46,204,113,.10)}
.tp-tutar-inp.kismi{border-color:#f0ad4e;background:rgba(243,156,18,.15)}
.top-panel-ftr{padding:14px 22px;border-top:1px solid var(--border);background:var(--surface-2);display:flex;justify-content:space-between;align-items:center;flex-shrink:0;flex-wrap:wrap;gap:10px}
.tp-toplam{font-size:13px;color:var(--text2)}.tp-toplam strong{font-size:17px;color:var(--text)}
.tp-kaydet-btn{background:#5cb85c;color:#fff;border:none;border-radius:5px;padding:10px 24px;font-size:14px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.tp-kaydet-btn:hover{filter:brightness(1.08)}
.tp-kaydet-btn:disabled{opacity:.55;cursor:not-allowed}
.tp-empty-warn{text-align:center;padding:32px;color:var(--muted);font-size:13px}
</style>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : 'danger' ?> mb-3"><?= htmlspecialchars($flash['mesaj']) ?></div>
<?php endif; ?>

<div class="gef-actions">
  <a class="gef-btn green" href="<?= BASE_URL ?>/nakit/gelen-e-faturalar/yeni"><i class="fa-solid fa-plus"></i> Yeni Gelen Fatura Ekle</a>
  <a class="gef-btn blue" href="<?= BASE_URL ?>/nakit/gelen-e-faturalar/toplu-yukle"><i class="fa-solid fa-file-excel"></i> Excel ile Toplu Yükle</a>
  <button class="gef-btn orange" type="button" onclick="topluOdemeAc()"><i class="fa-solid fa-money-bill"></i> Toplu Ödeme</button>
  <button class="gef-btn gray" type="button" onclick="submitBulk('not')"><i class="fa-solid fa-note-sticky"></i> Toplu Not</button>
  <a class="gef-btn gray" href="<?= $buildUrl(['quick'=>'pdf_eksik']) ?>"><i class="fa-solid fa-file-circle-exclamation"></i> PDF Eksik Faturalar</a>
  <a class="gef-btn red" href="<?= $buildUrl(['quick'=>'vadesi_gecen']) ?>"><i class="fa-solid fa-triangle-exclamation"></i> Vadesi Geçenler</a>
  <a class="gef-btn gray" href="<?= BASE_URL ?>/nakit/gelen-e-faturalar/export?<?= http_build_query(array_filter($filters)) ?>"><i class="fa-solid fa-download"></i> Excel'e Aktar</a>
</div>

<div class="gef-cards">
  <div class="gef-card"><div class="l">Toplam Fatura</div><div class="v"><?= (int)($ozetler['fatura_sayisi'] ?? 0) ?></div></div>
  <div class="gef-card"><div class="l">Toplam Borç TL</div><div class="v"><?= $fmt($ozetler['toplam_borc_tl'] ?? 0) ?> TL</div></div>
  <div class="gef-card"><div class="l">Toplam Ödenen TL</div><div class="v"><?= $fmt($ozetler['toplam_odenen_tl'] ?? 0) ?> TL</div></div>
  <div class="gef-card"><div class="l">Kalan Borç TL</div><div class="v"><?= $fmt($ozetler['kalan_borc_tl'] ?? 0) ?> TL</div></div>
  <div class="gef-card"><div class="l">Vadesi Geçen TL</div><div class="v"><?= $fmt($ozetler['vadesi_gecen_tl'] ?? 0) ?> TL</div></div>
  <div class="gef-card"><div class="l">Bu Ay Gelen</div><div class="v"><?= $fmt($ozetler['bu_ay_tl'] ?? 0) ?> TL</div></div>
  <div class="gef-card"><div class="l">PDF Eksik</div><div class="v"><?= (int)($ozetler['pdf_eksik_sayisi'] ?? 0) ?></div></div>
  <div class="gef-card"><div class="l">Kısmi Ödenen</div><div class="v"><?= (int)($ozetler['kismi_odenen_sayisi'] ?? 0) ?></div></div>
</div>

<form class="gef-filters" method="get" action="<?= BASE_URL ?>/nakit/gelen-e-faturalar">
  <div class="gef-grid">
    <div><label>Düzenleme Başlangıç</label><input type="date" name="baslangic" value="<?= htmlspecialchars($filters['baslangic'] ?? '') ?>"></div>
    <div><label>Düzenleme Bitiş</label><input type="date" name="bitis" value="<?= htmlspecialchars($filters['bitis'] ?? '') ?>"></div>
    <div><label>Vade Başlangıç</label><input type="date" name="vade_baslangic" value="<?= htmlspecialchars($filters['vade_baslangic'] ?? '') ?>"></div>
    <div><label>Vade Bitiş</label><input type="date" name="vade_bitis" value="<?= htmlspecialchars($filters['vade_bitis'] ?? '') ?>"></div>
    <div><label>Tedarikçi / Fatura No</label><input type="text" name="ara" value="<?= htmlspecialchars($filters['ara'] ?? '') ?>" placeholder="Arama"></div>
    <div><label>Kategori</label><input type="text" name="kategori" value="<?= htmlspecialchars($filters['kategori'] ?? '') ?>"></div>
    <div><label>Döviz</label><select name="doviz_tipi"><option value="">Tümü</option><option value="TRY" <?= ($filters['doviz_tipi'] ?? '') === 'TRY' ? 'selected' : '' ?>>TRY</option><option value="USD" <?= ($filters['doviz_tipi'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option><option value="EUR" <?= ($filters['doviz_tipi'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR</option></select></div>
    <div><label>Ödeme Durumu</label><select name="odeme_durumu"><option value="">Tümü</option><?php foreach($statusText as $k=>$v): ?><option value="<?= $k ?>" <?= ($filters['odeme_durumu'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div><label>Vade Durumu</label><select name="vade_durumu"><option value="">Tümü</option><?php foreach($dueText as $k=>$v): ?><option value="<?= $k ?>" <?= ($filters['vade_durumu'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div><label>Min TL</label><input type="text" name="min_tutar" value="<?= htmlspecialchars($filters['min_tutar'] ?? '') ?>"></div>
    <div><label>Max TL</label><input type="text" name="max_tutar" value="<?= htmlspecialchars($filters['max_tutar'] ?? '') ?>"></div>
    <div><label>PDF</label><select name="pdf"><option value="">Tümü</option><option value="var" <?= ($filters['pdf'] ?? '') === 'var' ? 'selected' : '' ?>>Var</option><option value="yok" <?= ($filters['pdf'] ?? '') === 'yok' ? 'selected' : '' ?>>Yok</option></select></div>
  </div>
  <div class="gef-quick">
    <button class="gef-btn blue" type="submit"><i class="fa-solid fa-filter"></i> Filtrele</button>
    <a href="<?= BASE_URL ?>/nakit/gelen-e-faturalar">Temizle</a>
    <a href="<?= $buildUrl(['quick'=>'bugun']) ?>">Bugün</a><a href="<?= $buildUrl(['quick'=>'bu_hafta']) ?>">Bu hafta</a><a href="<?= $buildUrl(['quick'=>'bu_ay']) ?>">Bu ay</a><a href="<?= $buildUrl(['quick'=>'gecen_ay']) ?>">Geçen ay</a><a href="<?= $buildUrl(['quick'=>'son_30']) ?>">Son 30 gün</a><a href="<?= $buildUrl(['quick'=>'odenmeyen']) ?>">Ödenmeyenler</a><a href="<?= $buildUrl(['quick'=>'kismi']) ?>">Kısmi ödenenler</a><a href="<?= $buildUrl(['quick'=>'usd']) ?>">USD</a><a href="<?= $buildUrl(['quick'=>'tl']) ?>">TL</a>
  </div>
</form>

<form id="bulkForm" method="post">
<div class="gef-table-wrap">
  <div style="overflow-x:auto">
    <table class="gef-table">
      <thead><tr>
        <th><input type="checkbox" onclick="document.querySelectorAll('.gef-check').forEach(c=>c.checked=this.checked)"></th>
        <th>Düzenleme</th><th>Vade</th><th>Belge</th><th>Fatura No</th><th>Tedarikçi / Çalışan</th><th>Kategori</th><th>Döviz</th><th class="txt-r">Genel</th><th class="txt-r">Genel TL</th><th class="txt-r">Ödenen TL</th><th class="txt-r">Kalan TL</th><th>Ödeme</th><th>Vade</th><th>Kalem</th><th>PDF</th><th>Not</th><th>İşlem</th>
      </tr></thead>
      <tbody>
      <?php if (empty($faturalar)): ?>
        <tr><td colspan="18"><div class="empty">Bu filtreye uygun kayıt bulunamadı.</div></td></tr>
      <?php else: foreach ($faturalar as $f):
        $odemeCls = $f['odeme_durumu'] === 'odendi' ? 'b-green' : ($f['odeme_durumu'] === 'kismi_odendi' ? 'b-orange' : 'b-red');
        $vadeCls = $f['vade_durumu'] === 'vadesi_gecti' ? 'b-red' : ($f['vade_durumu'] === 'vadesi_yaklasiyor' ? 'b-yellow' : ($f['vade_durumu'] === 'odendi' ? 'b-green' : 'b-gray'));
      ?>
        <tr>
          <td><input class="gef-check" type="checkbox" name="ids[]" value="<?= (int)$f['id'] ?>"
               data-id="<?= (int)$f['id'] ?>"
               data-fatura="<?= htmlspecialchars($f['fis_fatura_no'] ?? '-') ?>"
               data-tedarikci="<?= htmlspecialchars($f['tedarikci_unvan'] ?: ($f['tedarikci_calisan_adi'] ?? '-')) ?>"
               data-kalan="<?= number_format((float)$f['kalan_tutar_tl'], 2, '.', '') ?>"
               data-genel="<?= number_format((float)$f['genel_toplam_tl'], 2, '.', '') ?>"
               data-tarih="<?= htmlspecialchars($f['duzenleme_tarihi'] ?? '') ?>"></td>
          <td><?= $f['duzenleme_tarihi'] ? date('d.m.Y', strtotime($f['duzenleme_tarihi'])) : '-' ?></td>
          <td><?= $f['vade_tarihi'] ? date('d.m.Y', strtotime($f['vade_tarihi'])) : '-' ?></td>
          <td><?= htmlspecialchars($f['belge_turu'] ?? '') ?></td>
          <td><?= htmlspecialchars($f['fis_fatura_no'] ?? '') ?></td>
          <td><?= htmlspecialchars($f['tedarikci_unvan'] ?: ($f['tedarikci_calisan_adi'] ?? '')) ?></td>
          <td><?= htmlspecialchars($f['kategori'] ?? '') ?></td>
          <td><?= htmlspecialchars($f['doviz_tipi']) ?></td>
          <td class="txt-r"><?= $fmt($f['genel_toplam']) ?></td>
          <td class="txt-r"><strong><?= $fmt($f['genel_toplam_tl']) ?></strong></td>
          <td class="txt-r"><?= $fmt($f['toplam_odenen_tl']) ?></td>
          <td class="txt-r"><strong><?= $fmt($f['kalan_tutar_tl']) ?></strong></td>
          <td><span class="badge <?= $odemeCls ?>"><?= $statusText[$f['odeme_durumu']] ?? $f['odeme_durumu'] ?></span></td>
          <td><span class="badge <?= $vadeCls ?>"><?= $dueText[$f['vade_durumu']] ?? $f['vade_durumu'] ?></span></td>
          <td><?= (int)$f['kalem_sayisi'] ?></td>
          <td><?= !empty($f['pdf_path']) ? '<span class="badge b-green">PDF</span>' : '<span class="badge b-gray">Eksik</span>' ?></td>
          <td><?= (int)$f['not_sayisi'] > 0 || !empty($f['notlar']) ? '<span class="badge b-green">Var</span>' : '<span class="badge b-gray">Yok</span>' ?></td>
          <td><a href="<?= BASE_URL ?>/nakit/gelen-e-faturalar/detay/<?= (int)$f['id'] ?>">Detay</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="pag">
    <span>Toplam <?= (int)$toplam ?> kayıt, sayfa <?= (int)$sayfa ?> / <?= (int)$sayfaSay ?></span>
    <span>
      <?php if ($sayfa > 1): ?><a href="<?= $buildUrl(['sayfa'=>$sayfa-1]) ?>">Önceki</a><?php endif; ?>
      <?php if ($sayfa < $sayfaSay): ?><a href="<?= $buildUrl(['sayfa'=>$sayfa+1]) ?>">Sonraki</a><?php endif; ?>
    </span>
  </div>
</div>
</form>

<!-- ── TOPLU ÖDEME PANELİ ────────────────────────────── -->
<div class="top-panel-overlay" id="tpOverlay" onclick="topluOdemeKapat()"></div>

<div class="top-panel" id="tpPanel">
  <!-- Başlık -->
  <div class="top-panel-hdr">
    <h5><i class="fa-solid fa-money-bill-wave"></i> Toplu Ödeme <span id="tpSayac" style="font-size:12px;font-weight:400;opacity:.75;margin-left:6px;"></span></h5>
    <button class="top-panel-close" onclick="topluOdemeKapat()">&times;</button>
  </div>

  <!-- Global ayarlar -->
  <div class="top-panel-settings">
    <div class="tp-settings-col">
      <label>Ödeme Tarihi</label>
      <input type="date" id="tpTarih" value="<?= date('Y-m-d') ?>" style="width:150px;">
    </div>
    <div class="tp-settings-col" style="flex:1;min-width:180px;">
      <label>Ödeme Hesabı</label>
      <select id="tpHesap" style="width:100%;">
        <option value="">— Seçiniz —</option>
        <?php foreach (($hesaplar ?? []) as $h): ?>
        <option value="<?= (int)$h['id'] ?>"><?= htmlspecialchars($h['hesap_adi']) ?>
          (<?= number_format((float)$h['guncel_bakiye'], 2, ',', '.') ?> <?= $h['para_birimi'] ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="tp-settings-col" style="flex:2;min-width:180px;">
      <label>Açıklama</label>
      <input type="text" id="tpAciklama" value="Toplu gelen e-fatura ödemesi" style="width:100%;">
    </div>
    <div class="tp-settings-col">
      <label style="opacity:0;">_</label>
      <button class="tp-apply-all" onclick="tpHepsineUygula()" title="Tüm satırlara kalan tutarı yaz">
        <i class="fa-solid fa-arrows-rotate"></i> Kalan TL'yi Uygula
      </button>
    </div>
  </div>

  <!-- Tablo -->
  <div class="top-panel-body">
    <div id="tpBosList" class="tp-empty-warn" style="display:none;">
      <i class="fa-solid fa-circle-info" style="font-size:28px;display:block;margin-bottom:8px;"></i>
      Seçili fatura bulunamadı. Listeden fatura seçip tekrar açın.
    </div>
    <table id="tpTable" style="display:none;">
      <thead>
        <tr>
          <th style="width:30px;"></th>
          <th>Fatura No</th>
          <th>Tedarikçi</th>
          <th class="txt-r">Genel TL</th>
          <th class="txt-r">Kalan TL</th>
          <th class="txt-r" style="color:var(--info);">Ödenecek TL</th>
          <th style="width:34px;"></th>
        </tr>
      </thead>
      <tbody id="tpTbody"></tbody>
      <tfoot>
        <tr style="background:var(--surface-2);font-weight:700;">
          <td colspan="4" style="padding:10px;font-size:12px;color:var(--text2);">TOPLAM</td>
          <td class="txt-r" id="tpTotKalan" style="padding:10px;"></td>
          <td class="txt-r" id="tpTotOdeme" style="padding:10px;color:var(--info);font-size:14px;"></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <!-- Footer -->
  <div class="top-panel-ftr">
    <div class="tp-toplam">
      <span id="tpFatSay" style="color:var(--muted);font-size:12px;"></span><br>
      Ödenecek Toplam: <strong id="tpToplamGoster">0,00 TL</strong>
    </div>
    <div style="display:flex;gap:8px;">
      <button class="gef-btn gray" type="button" onclick="topluOdemeKapat()">
        <i class="fa-solid fa-xmark"></i> Vazgeç
      </button>
      <button class="tp-kaydet-btn" id="tpKaydetBtn" onclick="topluOdemeKaydet()">
        <i class="fa-solid fa-check"></i> Toplu Kaydet
      </button>
    </div>
  </div>
</div>

<script>
const BASE_URL_GEF = '<?= BASE_URL ?>';

/* ── TOPLU ÖDEME PANEL ─────────────────────────────── */
let _tpRows = []; // {id, fatura, tedarikci, kalan, genel}

function topluOdemeAc() {
  const secili = Array.from(document.querySelectorAll('.gef-check:checked'));
  if (!secili.length) { alert('Önce listeden fatura seçin.'); return; }

  _tpRows = secili.map(c => ({
    id:        c.dataset.id,
    fatura:    c.dataset.fatura,
    tedarikci: c.dataset.tedarikci,
    kalan:     parseFloat(c.dataset.kalan) || 0,
    genel:     parseFloat(c.dataset.genel) || 0,
  }));

  tpRenderTable();
  document.getElementById('tpSayac').textContent = '(' + _tpRows.length + ' fatura)';
  document.getElementById('tpOverlay').classList.add('open');
  document.getElementById('tpPanel').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function topluOdemeKapat() {
  document.getElementById('tpOverlay').classList.remove('open');
  document.getElementById('tpPanel').classList.remove('open');
  document.body.style.overflow = '';
}

function tpRenderTable() {
  const tbody  = document.getElementById('tpTbody');
  const table  = document.getElementById('tpTable');
  const bos    = document.getElementById('tpBosList');

  if (!_tpRows.length) { table.style.display='none'; bos.style.display='block'; return; }
  table.style.display = ''; bos.style.display = 'none';

  tbody.innerHTML = _tpRows.map((r, i) => `
    <tr id="tpRow_${r.id}">
      <td style="font-size:12px;color:var(--muted);">${i+1}</td>
      <td style="font-weight:600;">${escHtml(r.fatura)}</td>
      <td style="font-size:12px;color:var(--text2);">${escHtml(r.tedarikci)}</td>
      <td class="txt-r" style="color:var(--muted);">${fmtTR(r.genel)}</td>
      <td class="txt-r" style="font-weight:600;">${fmtTR(r.kalan)}</td>
      <td class="txt-r">
        <input type="number" class="tp-tutar-inp tam" id="tpInp_${r.id}"
               value="${r.kalan.toFixed(2)}" min="0" step="0.01"
               oninput="tpInpChange('${r.id}', ${r.kalan})">
      </td>
      <td>
        <button onclick="tpSilSatir('${r.id}')" title="Çıkar"
                style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:14px;">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </td>
    </tr>`).join('');

  tpGuncelleToplam();
}

function tpInpChange(id, kalan) {
  const inp = document.getElementById('tpInp_' + id);
  const val = parseFloat(inp.value) || 0;
  inp.classList.toggle('tam',   Math.abs(val - kalan) < 0.01);
  inp.classList.toggle('kismi', val > 0 && Math.abs(val - kalan) >= 0.01);
  tpGuncelleToplam();
}

function tpSilSatir(id) {
  _tpRows = _tpRows.filter(r => r.id !== id);
  document.getElementById('tpSayac').textContent = '(' + _tpRows.length + ' fatura)';
  tpRenderTable();
}

function tpHepsineUygula() {
  _tpRows.forEach(r => {
    const inp = document.getElementById('tpInp_' + r.id);
    if (inp) { inp.value = r.kalan.toFixed(2); inp.className = 'tp-tutar-inp tam'; }
  });
  tpGuncelleToplam();
}

function tpGuncelleToplam() {
  let totKalan = 0, totOdeme = 0, aktif = 0;
  _tpRows.forEach(r => {
    totKalan += r.kalan;
    const v = parseFloat(document.getElementById('tpInp_' + r.id)?.value) || 0;
    if (v > 0) { totOdeme += v; aktif++; }
  });
  document.getElementById('tpTotKalan').textContent  = fmtTR(totKalan) + ' ₺';
  document.getElementById('tpTotOdeme').textContent  = fmtTR(totOdeme) + ' ₺';
  document.getElementById('tpToplamGoster').textContent = fmtTR(totOdeme) + ' TL';
  document.getElementById('tpFatSay').textContent = aktif + ' fatura için ödeme';
}

function topluOdemeKaydet() {
  const tarih   = document.getElementById('tpTarih').value;
  const hesapId = document.getElementById('tpHesap').value;
  const aciklama= document.getElementById('tpAciklama').value;

  const satirlar = _tpRows
    .map(r => ({ id: r.id, tutar: parseFloat(document.getElementById('tpInp_' + r.id)?.value) || 0 }))
    .filter(s => s.tutar > 0);

  if (!satirlar.length) { alert('En az bir satır için tutar giriniz.'); return; }
  if (!tarih) { alert('Ödeme tarihi seçiniz.'); return; }

  const btn = document.getElementById('tpKaydetBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Kaydediliyor...';

  // Form oluştur ve gönder
  const form = document.getElementById('bulkForm');
  // Önceki hidden inputları temizle
  form.querySelectorAll('.tp-hidden').forEach(el => el.remove());

  const h = (n, v) => {
    const i = document.createElement('input');
    i.type='hidden'; i.name=n; i.value=v; i.className='tp-hidden';
    form.appendChild(i);
  };

  h('odeme_tarihi', tarih);
  h('kasa_banka_hesap_id', hesapId);
  h('aciklama', aciklama);
  satirlar.forEach(s => {
    h('ids[]', s.id);
    h('tutarlar[' + s.id + ']', s.tutar.toFixed(2));
  });

  // Checkbox'lar da ids[] gönderiyor — çift işlemi önlemek için devre dışı bırak
  document.querySelectorAll('.gef-check').forEach(c => c.disabled = true);

  form.action = BASE_URL_GEF + '/nakit/gelen-e-faturalar/toplu-odeme';
  form.method = 'post';
  form.submit();
}

/* ── TOPLU NOT ─────────────────────────────────────── */
function submitBulk(type) {
  if (type !== 'not') return;
  const checked = document.querySelectorAll('.gef-check:checked');
  if (!checked.length) { alert('Önce fatura seçin.'); return; }
  const note = prompt('Seçili faturalara eklenecek not:');
  if (!note) return;
  const form = document.getElementById('bulkForm');
  form.querySelectorAll('.tp-hidden').forEach(el => el.remove());
  const inp = document.createElement('input');
  inp.type='hidden'; inp.name='not_metni'; inp.value=note; inp.className='tp-hidden';
  form.appendChild(inp);
  form.action = BASE_URL_GEF + '/nakit/gelen-e-faturalar/toplu-not';
  form.submit(); // Toplu not için checkboxlar zaten ids[] gönderiyor, hidden input yok
}

/* ── YARDIMCILAR ────────────────────────────────────── */
function fmtTR(n) { return n.toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2}); }
function escHtml(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

/* ESC ile kapat */
document.addEventListener('keydown', e => { if (e.key==='Escape') topluOdemeKapat(); });
</script>
