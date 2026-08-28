<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = fn($v) => number_format((float)$v, 2, ',', '.') . ' TL';
?>

<style>
.personel-page { display: grid; gap: 14px; }
.personel-actions { display:flex; gap:8px; flex-wrap:wrap; }
.p-btn { display:inline-flex; align-items:center; gap:6px; border:0; border-radius:6px; padding:8px 12px; background:#1e293b; color:#fff; text-decoration:none; font-size:13px; font-weight:700; }
.p-btn.red { background:#dc2626; }
.p-btn.green { background:var(--success); }
.p-note { background:rgba(59,130,246,.15); border:1px solid #bfdbfe; color:var(--info); padding:12px; border-radius:8px; font-size:13px; }
.p-alert { border-radius:8px; padding:10px 12px; font-size:13px; font-weight:700; }
.p-alert.success { background:rgba(46,204,113,.15); border:1px solid #86efac; color:var(--success); }
.p-alert.error { background:rgba(231,76,60,.15); border:1px solid #fca5a5; color:var(--danger); }
.p-form { background:var(--card-bg); border:1px solid var(--border); border-radius:8px; padding:12px; display:grid; gap:10px; }
.p-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:10px; align-items:end; }
.p-field label { display:block; font-size:12px; color:var(--text2); font-weight:800; margin-bottom:4px; }
.p-field input, .p-field select, .p-field textarea { width:100%; min-height:36px; border:1px solid var(--border2); border-radius:6px; padding:7px 9px; font-size:13px; background:var(--card-bg); }
.p-field textarea { min-height:72px; resize:vertical; }
.p-table-wrap { overflow-x:auto; background:var(--card-bg); border:1px solid var(--border); border-radius:8px; }
.p-table { width:100%; border-collapse:collapse; min-width:860px; }
.p-table th { background:var(--surface-2); color:var(--text2); text-align:left; padding:10px; font-size:12px; white-space:nowrap; }
.p-table td { border-top:1px solid var(--border); padding:9px 10px; font-size:13px; color:var(--text); }
.p-empty { text-align:center; padding:28px 12px; color:var(--muted); }
.p-heading { font-size:16px; font-weight:800; color:var(--text); margin:6px 0 0; }
</style>

<div class="personel-page">
  <?php if (!empty($flash)): ?>
    <div class="p-alert <?= $e($flash['tip'] ?? '') ?>"><?= $e($flash['mesaj'] ?? '') ?></div>
  <?php endif; ?>

  <div class="personel-actions">
    <a class="p-btn" href="<?= BASE_URL ?>/rapor/financial/calisanlar"><i class="fa-solid fa-chart-line"></i> Çalışan Raporu</a>
  </div>

  <div class="p-note">
    Personel kayıtları <strong>personeller</strong>, maaş/prim/avans/kesinti hareketleri <strong>personel_hareketleri</strong> tablosunda tutulur. Bu sayfa mevcut kayıtları listeler; finansal toplamlar Çalışan Raporu'na yansır.
  </div>

  <?php if (Rbac::currentUserCan('PERSONEL_CREATE')): ?>
  <div class="p-heading">Yeni Personel Ekle</div>
  <form class="p-form" method="post" action="<?= BASE_URL ?>/personel/ekle">
    <div class="p-grid">
      <div class="p-field"><label>Ad Soyad</label><input name="ad" required></div>
      <div class="p-field"><label>Görev</label><input name="gorev"></div>
      <div class="p-field"><label>Departman</label><input name="departman"></div>
      <div class="p-field"><label>Maaş</label><input type="number" step="0.01" min="0" name="maas" value="0"></div>
      <div class="p-field"><label>Durum</label><select name="aktif_mi"><option value="1">Aktif</option><option value="0">Pasif</option></select></div>
      <div class="p-field"><label>Notlar</label><input name="notlar"></div>
      <button class="p-btn green" type="submit"><i class="fa-solid fa-plus"></i> Personel Ekle</button>
    </div>
  </form>
  <?php endif; ?>

  <?php if (Rbac::currentUserCan('PERSONEL_CREATE')): ?>
  <div class="p-heading">Personel Gideri / Hareketi Ekle</div>
  <form class="p-form" method="post" action="<?= BASE_URL ?>/personel/hareket_ekle">
    <div class="p-grid">
      <div class="p-field">
        <label>Personel</label>
        <select name="personel_id" required>
          <option value="">Seçiniz</option>
          <?php foreach ($personeller as $p): ?>
            <option value="<?= (int)$p['id'] ?>"><?= $e($p['ad']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="p-field"><label>Tarih</label><input type="date" name="tarih" value="<?= date('Y-m-d') ?>" required></div>
      <div class="p-field">
        <label>Hareket Tipi</label>
        <select name="tip" required>
          <option value="maas">Maaş</option>
          <option value="sgk">SGK</option>
          <option value="sigorta">Sigorta</option>
          <option value="yemek">Yemek</option>
          <option value="yol">Yol</option>
          <option value="prim">Prim</option>
          <option value="ek_odeme">Ek ödeme</option>
          <option value="avans">Avans</option>
          <option value="kesinti">Kesinti</option>
          <option value="odeme">Diğer ödeme</option>
        </select>
      </div>
      <div class="p-field"><label>Tutar</label><input type="number" step="0.01" min="0.01" name="tutar" required></div>
      <div class="p-field">
        <label>Ödeme Durumu</label>
        <select name="odeme_durumu">
          <option value="bekliyor">Bekliyor</option>
          <option value="odendi">Ödendi</option>
          <option value="iptal">İptal</option>
        </select>
      </div>
      <div class="p-field">
        <label>Kasa/Banka</label>
        <select name="kasa_id">
          <option value="">Seçiniz</option>
          <?php foreach (($hesaplar ?? []) as $h): ?>
            <option value="<?= (int)$h['id'] ?>"><?= $e($h['tip'] . ' - ' . $h['hesap_adi']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="p-field"><label>Belge No</label><input name="belge_no"></div>
      <div class="p-field"><label>Açıklama</label><input name="aciklama"></div>
      <button class="p-btn green" type="submit"><i class="fa-solid fa-receipt"></i> Hareket Ekle</button>
    </div>
  </form>
  <?php endif; ?>

  <div class="p-heading">Personel Listesi</div>
  <div class="p-table-wrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>Ad</th>
          <th>Görev</th>
          <th>Departman</th>
          <th>Maaş</th>
          <th>Toplam ödeme</th>
          <th>Avans/Kesinti</th>
          <th>Son ödeme</th>
          <th>Durum</th>
          <th>İşlem</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($personeller)): ?>
          <tr><td colspan="9"><div class="p-empty">Henüz personel kaydı yok.</div></td></tr>
        <?php else: ?>
          <?php foreach ($personeller as $p): ?>
            <tr>
              <td><?= $e($p['ad']) ?></td>
              <td><?= $e($p['gorev'] ?: '-') ?></td>
              <td><?= $e($p['departman'] ?: '-') ?></td>
              <td><?= $money($p['maas']) ?></td>
              <td><?= $money($p['toplam_odeme']) ?></td>
              <td><?= $money($p['toplam_kesinti_avans']) ?></td>
              <td><?= $e($p['son_odeme_tarihi'] ?: '-') ?></td>
              <td><?= ((int)$p['aktif_mi'] === 1) ? 'Aktif' : 'Pasif' ?></td>
              <td><?php if (Rbac::currentUserCan('PERSONEL_DELETE')): ?><a class="p-btn red" href="#" onclick="return nymPost('<?= BASE_URL ?>/personel/sil/<?= (int)$p['id'] ?>', 'Personel silinsin mi?')"><i class="fa-solid fa-trash"></i> Sil</a><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="p-heading">Son Personel Hareketleri</div>
  <div class="p-table-wrap">
    <table class="p-table">
      <thead>
        <tr>
          <th>Tarih</th>
          <th>Personel</th>
          <th>Tip</th>
          <th>Tutar</th>
          <th>Durum</th>
          <th>Kasa/Banka</th>
          <th>Açıklama</th>
          <th>İşlem</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($hareketler)): ?>
          <tr><td colspan="8"><div class="p-empty">Henüz personel hareketi yok.</div></td></tr>
        <?php else: ?>
          <?php foreach ($hareketler as $h): ?>
            <tr>
              <td><?= $e($h['tarih']) ?></td>
              <td><?= $e($h['personel_adi']) ?></td>
              <td><?= $e($h['tip']) ?></td>
              <td><?= $money($h['tutar']) ?></td>
              <td><?= $e($h['odeme_durumu']) ?></td>
              <td><?= $e($h['hesap_adi'] ?: '-') ?></td>
              <td><?= $e($h['aciklama'] ?: '-') ?></td>
              <td><?php if (Rbac::currentUserCan('PERSONEL_DELETE')): ?><a class="p-btn red" href="#" onclick="return nymPost('<?= BASE_URL ?>/personel/hareket_sil/<?= (int)$h['id'] ?>', 'Hareket silinsin mi?')"><i class="fa-solid fa-trash"></i> Sil</a><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
