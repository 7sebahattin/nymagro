<?php
/** @var array $belge, $taraflar, $kalemler, $eslesenCari, $cariAdaylari, $urunAdaylari, $ozet, $birimListesi */
$h   = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$fmt = fn($n) => number_format((float)$n, 2, ',', '.');
$mik = fn($n) => rtrim(rtrim(number_format((float)$n, 4, ',', '.'), '0'), ',');

$gonderen = $taraflar['gonderen'] ?? [];
$temel    = BASE_URL . '/ebelge/eslestir/' . (int)$belge['id'];

$eslesmeEtiket = [
    'vkn_otomatik'    => 'VKN ile otomatik',
    'ogrenilmis'      => 'Öğrenilmiş',
    'manuel'          => 'Manuel',
    'yeni_olusturuldu' => 'Yeni kart',
    'barkod'          => 'Barkod ile otomatik',
    'alici_kodu'      => 'Ürün kodu ile otomatik',
    'urunsuz'         => 'Üründüz gider kalemi',
];
?>
<style>
.ebl-btn{border:0;border-radius:4px;padding:7px 11px;font-size:12.5px;font-weight:700;color:#fff;text-decoration:none;display:inline-flex;gap:6px;align-items:center;cursor:pointer;background:#337ab7}
.ebl-btn.gray{background:#64748b}.ebl-btn.green{background:#5cb85c}.ebl-btn.orange{background:#f0ad4e}.ebl-btn.red{background:#d9534f}
.ebl-btn.sm{padding:5px 9px;font-size:11.5px}
.ebl-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;align-items:center}
.ebl-box{background:var(--card-bg);border:1px solid var(--border);border-radius:6px;padding:16px;margin-bottom:14px}
.ebl-two{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.ebl-kv{display:grid;grid-template-columns:130px 1fr;gap:5px 12px;font-size:12.5px}
.ebl-kv dt{color:var(--muted);font-weight:700}.ebl-kv dd{margin:0;color:var(--text2);word-break:break-word}
.ebl-table{width:100%;border-collapse:collapse;min-width:1100px}
.ebl-table th{background:#2c3e6b;color:#fff;font-size:11.5px;text-align:left;padding:9px;white-space:nowrap}
.ebl-table td{font-size:12.5px;padding:8px 9px;border-bottom:1px solid var(--border);vertical-align:top}
.ebl-table-wrap{overflow-x:auto}
.txt-r{text-align:right}
.badge{display:inline-block;border-radius:4px;padding:3px 7px;font-size:11px;font-weight:700}
.b-green{background:rgba(46,204,113,.15);color:var(--success)}.b-yellow{background:rgba(243,156,18,.15);color:var(--warning)}
.b-gray{background:var(--surface-2);color:var(--text2)}.b-red{background:rgba(231,76,60,.15);color:var(--danger)}
.ebl-uyari{background:rgba(243,156,18,.12);border:1px solid var(--warning);border-radius:6px;padding:12px 14px;margin-bottom:14px;font-size:13px;color:var(--text2)}
.ebl-uyari ul{margin:8px 0 0;padding-left:18px}
.ebl-ok{background:rgba(46,204,113,.12);border:1px solid var(--success);border-radius:6px;padding:12px 14px;margin-bottom:14px;font-size:13px;color:var(--text2)}
.ebl-mini{font-size:11.5px;color:var(--muted)}
.ebl-inline{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
select,input[type=text],input[type=number]{padding:6px;border:1px solid var(--border2);border-radius:4px;font-size:12px;max-width:100%}
select{max-width:320px}
h5{margin:0 0 10px;font-size:13.5px;font-weight:800;color:var(--text)}
.ebl-satir-form{border-top:1px dashed var(--border);margin-top:6px;padding-top:6px}
@media(max-width:900px){.ebl-two{grid-template-columns:1fr}}
</style>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= $flash['tip'] === 'success' ? 'success' : ($flash['tip'] === 'warning' ? 'warning' : 'danger') ?> mb-3"><?= $h($flash['mesaj']) ?></div>
<?php endif; ?>

<div class="ebl-actions">
  <a class="ebl-btn gray" href="<?= BASE_URL ?>/ebelge/detay/<?= (int)$belge['id'] ?>"><i class="fa-solid fa-rotate-left"></i> Belge Detayı</a>
  <form method="post" action="<?= $h($temel) ?>" style="display:inline">
    <?= Csrf::fieldHtml() ?>
    <input type="hidden" name="islem" value="otomatik">
    <button class="ebl-btn" type="submit"><i class="fa-solid fa-wand-magic-sparkles"></i> Otomatik Eşleştir</button>
  </form>
  <span class="ebl-mini">Otomatik eşleştirme yalnızca <strong>VKN/TCKN</strong>, <strong>barkod</strong> ve <strong>ürün kodu</strong> kullanır — unvan/ürün adı benzerliğiyle <strong>asla</strong> eşleştirme yapılmaz.</span>
</div>

<?php if (empty($ozet['engelleyiciler'])): ?>
  <div class="ebl-ok">
    <strong>Eşleştirme tamamlandı.</strong> Bu belge aktarıma hazır.
    Sisteme aktarım (fatura/stok/cari kaydı oluşturma) <strong>Faz 3</strong> ile devreye girecektir.
  </div>
<?php else: ?>
  <div class="ebl-uyari">
    <strong>Aktarım için çözülmesi gerekenler</strong>
    <ul><?php foreach ($ozet['engelleyiciler'] as $e): ?><li><?= $h($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<!-- ══════════ CARİ EŞLEŞTİRME ══════════ -->
<div class="ebl-box">
  <h5>1 · Cari eşleştirme</h5>
  <div class="ebl-two">
    <div>
      <div class="ebl-mini" style="margin-bottom:6px">XML'den gelen gönderen</div>
      <dl class="ebl-kv">
        <dt>Unvan</dt><dd><strong><?= $h($gonderen['unvan'] ?? '—') ?></strong></dd>
        <dt>VKN / TCKN</dt><dd><?= $h(($gonderen['vkn_tckn'] ?? '') ?: '—') ?></dd>
        <dt>Vergi Dairesi</dt><dd><?= $h($gonderen['vergi_dairesi'] ?? '—') ?></dd>
        <dt>Adres</dt><dd><?= $h(trim(($gonderen['adres'] ?? '') . ' ' . ($gonderen['ilce'] ?? '') . ' ' . ($gonderen['il'] ?? ''))) ?: '—' ?></dd>
      </dl>
    </div>
    <div>
      <div class="ebl-mini" style="margin-bottom:6px">Sistemdeki karşılığı</div>
      <?php if ($eslesenCari): ?>
        <div class="ebl-ok" style="margin:0 0 10px">
          <span class="badge b-green"><?= $h($eslesmeEtiket[$belge['cari_eslesme_tipi']] ?? $belge['cari_eslesme_tipi']) ?></span>
          <strong><?= $h($eslesenCari['unvan']) ?></strong><br>
          <span class="ebl-mini">
            VKN/TCKN: <?= $h(($eslesenCari['vergi_no'] ?: $eslesenCari['tc_kimlik_no']) ?: '—') ?>
            · Tip: <?= $h($eslesenCari['tip']) ?>
            · Bakiye: <?= $fmt($eslesenCari['bakiye']) ?>
          </span>
        </div>
      <?php else: ?>
        <div class="ebl-uyari" style="margin:0 0 10px">Bu belge henüz bir cariye bağlanmadı.</div>
      <?php endif; ?>

      <form method="get" action="<?= $h($temel) ?>" class="ebl-inline" style="margin-bottom:10px">
        <input type="text" name="cari_ara" placeholder="Cari ara (unvan / VKN / kod)" value="<?= $h($cariAramasi) ?>">
        <button class="ebl-btn gray sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Ara</button>
      </form>

      <form method="post" action="<?= $h($temel) ?>" class="ebl-inline">
        <?= Csrf::fieldHtml() ?>
        <input type="hidden" name="islem" value="cari_ata">
        <select name="cari_id" required>
          <option value="">— cari seçin —</option>
          <?php foreach ($cariAdaylari as $c): ?>
            <option value="<?= (int)$c['id'] ?>">
              <?= $h($c['unvan']) ?>
              <?php if (!empty($c['vergi_no']) || !empty($c['tc_kimlik_no'])): ?>
                · <?= $h($c['vergi_no'] ?: $c['tc_kimlik_no']) ?>
              <?php endif; ?>
              <?php if (isset($c['benzerlik'])): ?> · benzerlik %<?= (int)$c['benzerlik'] ?><?php endif; ?>
            </option>
          <?php endforeach; ?>
        </select>
        <label class="ebl-mini"><input type="checkbox" name="ogren" value="1" checked> bu eşleşmeyi hatırla</label>
        <button class="ebl-btn green sm" type="submit"><i class="fa-solid fa-link"></i> Bağla</button>
      </form>
      <div class="ebl-mini" style="margin-top:6px">
        Listedeki “benzerlik” yüzdesi yalnızca sıralama içindir; doğru cariyi <strong>siz</strong> seçersiniz.
      </div>

      <details style="margin-top:12px">
        <summary style="cursor:pointer;font-size:12.5px;font-weight:700">Yeni cari kartı oluştur</summary>
        <form method="post" action="<?= $h($temel) ?>" style="margin-top:8px">
          <?= Csrf::fieldHtml() ?>
          <input type="hidden" name="islem" value="cari_yeni">
          <div class="ebl-inline">
            <input type="text" name="unvan" value="<?= $h($gonderen['unvan'] ?? '') ?>" required style="min-width:260px">
            <select name="tip">
              <option value="tedarikci">Tedarikçi</option>
              <option value="her_ikisi">Her ikisi</option>
            </select>
            <button class="ebl-btn orange sm" type="submit"><i class="fa-solid fa-user-plus"></i> Oluştur ve Bağla</button>
          </div>
          <div class="ebl-mini" style="margin-top:6px">
            VKN/TCKN, vergi dairesi, adres ve iletişim bilgileri XML'den doldurulur.
            Aynı VKN ile kayıtlı cari varsa yeni kart açılmaz.
          </div>
        </form>
      </details>
    </div>
  </div>
</div>

<!-- ══════════ KALEM EŞLEŞTİRME ══════════ -->
<div class="ebl-box">
  <h5>2 · Kalem eşleştirme
    <span class="ebl-mini" style="font-weight:400">
      (<?= (int)$ozet['eslesen_kalem'] ?> eşleşti · <?= (int)$ozet['urunsuz_kalem'] ?> üründüz · <?= (int)$ozet['bekleyen_kalem'] ?> bekliyor)
    </span>
  </h5>

  <div class="ebl-actions">
    <form method="get" action="<?= $h($temel) ?>" class="ebl-inline">
      <input type="text" name="urun_ara" placeholder="Tüm satırlar için ürün ara (ad / stok kodu / barkod)" value="<?= $h($urunAramasi) ?>" style="min-width:300px">
      <button class="ebl-btn gray sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Ara</button>
    </form>
    <?php if ((int)$ozet['bekleyen_kalem'] > 0): ?>
      <form method="post" action="<?= $h($temel) ?>">
        <?= Csrf::fieldHtml() ?>
        <input type="hidden" name="islem" value="toplu_urunsuz">
        <button class="ebl-btn orange sm" type="submit"
                onclick="return confirm('Eşleşmemiş tüm kalemler üründüz gider/hizmet kalemi olarak işaretlensin mi? Bu kalemler için stok hareketi oluşmaz.')">
          <i class="fa-solid fa-receipt"></i> Kalanları üründüz gider yap
        </button>
      </form>
    <?php endif; ?>
  </div>

  <div class="ebl-table-wrap">
    <table class="ebl-table">
      <thead>
        <tr>
          <th>#</th><th>XML kalemi</th><th>Kodlar</th><th class="txt-r">Miktar</th>
          <th>Durum / Eşleştirme</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($kalemler as $k):
          $kid = (int)$k['id'];
          $tip = (string)($k['urun_eslesme_tipi'] ?? '');
          $urunsuz = $tip === 'urunsuz';
          $eslesti = !empty($k['eslesen_urun_id']);
          $birimCozum = EBelgeEslestirme::birimCozumle($k['birim_kodu'] ?? null);
      ?>
        <tr>
          <td><?= (int)$k['sira_no'] ?></td>
          <td>
            <strong><?= $h($k['urun_adi']) ?></strong>
            <?php if (!empty($k['aciklama'])): ?><div class="ebl-mini"><?= $h(mb_substr((string)$k['aciklama'], 0, 90)) ?></div><?php endif; ?>
            <div class="ebl-mini"><?= $fmt($k['birim_fiyat']) ?> × <?= $mik($k['miktar']) ?> = <?= $fmt($k['satir_tutari']) ?></div>
          </td>
          <td class="ebl-mini">
            <?php if (!empty($k['barkod'])): ?>Barkod: <?= $h($k['barkod']) ?><br><?php endif; ?>
            <?php if (!empty($k['alici_urun_kodu'])): ?>Bizim kod: <?= $h($k['alici_urun_kodu']) ?><br><?php endif; ?>
            <?php if (!empty($k['satici_urun_kodu'])): ?>Satıcı kodu: <?= $h($k['satici_urun_kodu']) ?><?php endif; ?>
          </td>
          <td class="txt-r">
            <?= $mik($k['miktar']) ?>
            <div class="ebl-mini">
              <?= $h($k['birim_kodu'] ?: '—') ?>
              <?= $birimCozum !== null ? '→ ' . $h($birimCozum) : '<span style="color:var(--warning)">(çözülemedi)</span>' ?>
            </div>
          </td>
          <td>
            <?php if ($urunsuz): ?>
              <span class="badge b-gray">Üründüz gider kalemi</span>
              <div class="ebl-mini">Aktarımda stok hareketi oluşmaz, tutar cariye işlenir.</div>
            <?php elseif ($eslesti): ?>
              <span class="badge <?= empty($k['hedef_birim']) ? 'b-yellow' : 'b-green' ?>">
                <?= $h($eslesmeEtiket[$tip] ?? $tip) ?>
              </span>
              <div><strong><?= $h($k['urun_ad']) ?></strong>
                <span class="ebl-mini"><?= $h($k['urun_stok_kodu'] ?: '') ?> · sistem birimi: <?= $h($k['urun_birim']) ?></span>
              </div>
              <?php if (empty($k['hedef_birim'])): ?>
                <div class="ebl-mini" style="color:var(--warning)">
                  Birim çelişkisi: XML <?= $h($k['birim_kodu'] ?: '—') ?> ↔ ürün <?= $h($k['urun_birim']) ?>.
                  Aşağıdan hedef birim ve çarpanı onaylayın.
                </div>
              <?php else: ?>
                <div class="ebl-mini">Hedef birim: <?= $h($k['hedef_birim']) ?> · çarpan: <?= $mik($k['birim_carpani']) ?></div>
              <?php endif; ?>
            <?php else: ?>
              <span class="badge b-yellow">Eşleşme bekliyor</span>
            <?php endif; ?>

            <?php if (!$urunsuz): ?>
              <div class="ebl-satir-form">
                <form method="post" action="<?= $h($temel) ?>" class="ebl-inline">
                  <?= Csrf::fieldHtml() ?>
                  <input type="hidden" name="islem" value="kalem">
                  <input type="hidden" name="kalem_islem" value="urun">
                  <input type="hidden" name="kalem_id" value="<?= $kid ?>">
                  <select name="urun_id" required>
                    <option value="">— ürün/hizmet seçin —</option>
                    <?php foreach (($urunAdaylari[$kid] ?? []) as $u): ?>
                      <option value="<?= (int)$u['id'] ?>" <?= (int)($k['eslesen_urun_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= $h($u['ad']) ?><?= !empty($u['stok_kodu']) ? ' · ' . $h($u['stok_kodu']) : '' ?>
                        · <?= $h($u['birim']) ?><?php if (isset($u['benzerlik'])): ?> · %<?= (int)$u['benzerlik'] ?><?php endif; ?>
                      </option>
                    <?php endforeach; ?>
                    <?php if ($eslesti && empty($urunAdaylari[$kid])): ?>
                      <option value="<?= (int)$k['eslesen_urun_id'] ?>" selected><?= $h($k['urun_ad']) ?> (mevcut)</option>
                    <?php endif; ?>
                  </select>
                  <select name="hedef_birim" title="Hedef birim">
                    <?php foreach ($birimListesi as $b): ?>
                      <option value="<?= $b ?>" <?= ($k['hedef_birim'] ?? $birimCozum) === $b ? 'selected' : '' ?>><?= $b ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="number" name="birim_carpani" step="any" min="0.000001" style="width:90px"
                         value="<?= $h($k['birim_carpani'] ?: 1) ?>" title="XML miktarı × çarpan = sistem miktarı">
                  <label class="ebl-mini"><input type="checkbox" name="ogren" value="1" checked> hatırla</label>
                  <button class="ebl-btn green sm" type="submit"><i class="fa-solid fa-link"></i> Eşleştir</button>
                </form>

                <form method="post" action="<?= $h($temel) ?>" class="ebl-inline" style="margin-top:6px">
                  <?= Csrf::fieldHtml() ?>
                  <input type="hidden" name="islem" value="urun_yeni">
                  <input type="hidden" name="kalem_id" value="<?= $kid ?>">
                  <input type="text" name="ad" value="<?= $h($k['urun_adi']) ?>" style="min-width:200px" required>
                  <input type="text" name="stok_kodu" value="<?= $h($k['alici_urun_kodu'] ?? '') ?>" placeholder="stok kodu" style="width:130px">
                  <select name="tip"><option value="urun">Ürün</option><option value="hizmet">Hizmet</option></select>
                  <select name="birim">
                    <?php foreach ($birimListesi as $b): ?>
                      <option value="<?= $b ?>" <?= $birimCozum === $b ? 'selected' : '' ?>><?= $b ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="ebl-btn orange sm" type="submit"><i class="fa-solid fa-plus"></i> Yeni kart aç</button>
                </form>
              </div>
            <?php endif; ?>

            <?php if ($eslesti || $urunsuz): ?>
              <form method="post" action="<?= $h($temel) ?>" style="margin-top:6px">
                <?= Csrf::fieldHtml() ?>
                <input type="hidden" name="islem" value="kalem_kaldir">
                <input type="hidden" name="kalem_id" value="<?= $kid ?>">
                <button class="ebl-btn gray sm" type="submit"><i class="fa-solid fa-xmark"></i> Eşleşmeyi kaldır</button>
              </form>
            <?php else: ?>
              <form method="post" action="<?= $h($temel) ?>" style="margin-top:6px">
                <?= Csrf::fieldHtml() ?>
                <input type="hidden" name="islem" value="kalem">
                <input type="hidden" name="kalem_islem" value="urunsuz">
                <input type="hidden" name="kalem_id" value="<?= $kid ?>">
                <button class="ebl-btn gray sm" type="submit">
                  <i class="fa-solid fa-receipt"></i> Üründüz gider yap
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
