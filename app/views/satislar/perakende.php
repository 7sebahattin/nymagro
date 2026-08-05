<?php
/**
 * View: satislar/perakende.php
 * Perakende satış ekranı - Veritabanı bağlantılı sürüm
 */
?>
<style>
    .page-actions { display: flex; gap: 8px; margin-bottom: 16px; }
    .btn-kaydet {
      display: inline-flex; align-items: center; gap: 6px;
      background: #e74c3c; color: #fff; border: none;
      padding: 8px 18px; border-radius: 5px; font-size: 13px; font-weight: 600;
      cursor: pointer; transition: filter .15s;
    }
    .btn-kaydet:hover { filter: brightness(1.1); }
    .btn-geri {
      display: inline-flex; align-items: center; gap: 6px;
      background: #e67e22; color: #fff; border: none;
      padding: 8px 18px; border-radius: 5px; font-size: 13px; font-weight: 600;
      cursor: pointer; transition: filter .15s; text-decoration: none;
    }
    .btn-geri:hover { filter: brightness(1.1); color: #fff; }

    .two-col { display: grid; grid-template-columns: 420px 1fr; gap: 20px; align-items: start; }

    .panel { background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden; }
    .p-header { padding: 12px 16px; font-size: 13.5px; font-weight: 700; color: #fff; text-transform: uppercase; }
    .p-header-blue { background: #2f73b6; }
    .p-header-green { background: #27ae60; }
    .p-body { padding: 20px; display: flex; flex-direction: column; gap: 15px; }

    .fg { display: flex; flex-direction: column; gap: 6px; }
    .fg label { font-size: 12.5px; font-weight: 600; color: #475569; }
    .fi { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 13px; color: #1e293b; outline: none; transition: border-color 0.2s; }
    .fi:focus { border-color: #2f73b6; }

    /* Ürün Arama */
    .search-wrap { position: relative; }
    .search-drop { 
        position: absolute; top: 100%; left: 0; right: 0; background: #fff; 
        border: 1px solid #cbd5e1; border-top: none; border-radius: 0 0 6px 6px; 
        box-shadow: 0 6px 16px rgba(0,0,0,.12); z-index: 1000; max-height: 250px; 
        overflow-y: auto; display: none; 
    }
    .search-drop.open { display: block; }
    .search-item { padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; font-size: 13px; }
    .search-item:hover { background: #f0fdf4; }
    .search-item:last-child { border-bottom: none; }

    /* Tablo */
    .k-tablo { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .k-tablo th { text-align: left; padding: 10px; font-size: 12px; color: #64748b; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .k-tablo td { padding: 10px; border-bottom: 1px solid #f1f5f9; font-size: 13px; vertical-align: middle; }
    .k-fi { width: 100%; padding: 5px 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 13px; }
    
    /* Özet */
    .summary { margin-top: 20px; border-top: 2px solid #e2e8f0; padding-top: 15px; display: flex; flex-direction: column; gap: 8px; align-items: flex-end; }
    .s-row { display: flex; gap: 20px; font-size: 14px; color: #475569; }
    .s-row b { min-width: 100px; text-align: right; color: #1e293b; }
    .s-grand { font-size: 18px; font-weight: 800; color: #27ae60; }

    @media (max-width: 900px) { .two-col { grid-template-columns: 1fr; } }
</style>

<div style="display:flex;align-items:center;gap:6px;margin-bottom:14px;font-size:12.5px;color:#94a3b8;">
  <a href="<?= BASE_URL ?>/satis" style="color:#64748b;text-decoration:none;">
    <i class="fa-solid fa-shopping-cart"></i> Satışlar
  </a>
  <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
  <span>Perakende Satış</span>
</div>

<div class="page-actions">
  <button class="btn-kaydet" id="btnKaydet">
    <i class="fa-solid fa-bolt"></i> Satışı Tamamla
  </button>
  <a href="<?= BASE_URL ?>/satis" class="btn-geri">
    <i class="fa-solid fa-rotate-left"></i> Vazgeç
  </a>
</div>

<div class="two-col">
    <!-- Sol Panel: Satış Bilgileri -->
    <div class="panel">
        <div class="p-header p-header-blue">SATIŞ BİLGİLERİ</div>
        <div class="p-body">
            <div class="fg">
                <label>Tarih / Saat</label>
                <div style="display:flex; gap:10px;">
                    <input type="date" id="tarih" class="fi" style="flex:2;">
                    <input type="time" id="saat" class="fi" style="flex:1;">
                </div>
            </div>
            
            <div class="fg">
                <label>Kasa / Hesap</label>
                <select id="kasa_id" class="fi">
                    <option value="1">Merkez Kasa</option>
                    <option value="2">Banka Hesabı</option>
                </select>
            </div>

            <div class="fg">
                <label>Açıklama</label>
                <textarea id="aciklama" class="fi" rows="3" placeholder="Not ekleyin..."></textarea>
            </div>
            
            <div id="genelOzet" class="summary" style="margin-top:0; border:none; padding:0;">
                <div class="s-row s-grand">
                    <span>GENEL TOPLAM:</span>
                    <b id="lblGenel">0,00 ₺</b>
                </div>
            </div>
        </div>
    </div>

    <!-- Sağ Panel: Ürünler -->
    <div class="panel">
        <div class="p-header p-header-green">ÜRÜN / HİZMET EKLE</div>
        <div class="p-body">
            <div class="search-wrap">
                <input type="text" id="urunAra" class="fi" style="width:100%; padding:12px;" placeholder="Ürün adı, kodu veya barkod okutun...">
                <div id="searchDrop" class="search-drop"></div>
            </div>

            <div style="overflow-x:auto;">
                <table class="k-tablo" id="urunTablo">
                    <thead>
                        <tr>
                            <th>Ürün Adı</th>
                            <th style="width:80px;">Miktar</th>
                            <th style="width:100px;">Birim Fiyat</th>
                            <th style="width:70px;">KDV</th>
                            <th style="width:100px; text-align:right;">Toplam</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="kalemler">
                        <tr id="emptyRow">
                            <td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">
                                <i class="fa-solid fa-basket-shopping" style="font-size:24px; display:block; margin-bottom:10px;"></i>
                                Henüz ürün eklenmedi.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="summary" id="ozetBolumu" style="display:none;">
                <div class="s-row"><span>Ara Toplam:</span> <b id="lblAra">0,00 ₺</b></div>
                <div class="s-row"><span>KDV Toplam:</span> <b id="lblKdv">0,00 ₺</b></div>
                <div class="s-row s-grand"><span>GENEL TOPLAM:</span> <b id="lblGenelSag">0,00 ₺</b></div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    const BASE = '<?= BASE_URL ?>';
    let sepet = [];

    // Tarih/Saat ilklendir
    const now = new Date();
    document.getElementById('tarih').value = now.toISOString().split('T')[0];
    document.getElementById('saat').value = now.toTimeString().slice(0,5);

    // Ürün Arama
    const inp = document.getElementById('urunAra');
    const drop = document.getElementById('searchDrop');
    let timer;

    inp.addEventListener('input', () => {
        clearTimeout(timer);
        const q = inp.value.trim();
        if(q.length < 2) { drop.classList.remove('open'); return; }
        timer = setTimeout(() => {
            fetch(`${BASE}/satis/urunBul?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    drop.innerHTML = '';
                    if(!data.length) {
                        drop.innerHTML = '<div style="padding:10px; color:#999; font-size:12px;">Ürün bulunamadı</div>';
                    } else {
                        data.forEach(u => {
                            const d = document.createElement('div');
                            d.className = 'search-item';
                            d.innerHTML = `<span>${u.ad}</span> <b>${parseFloat(u.satis_fiyati).toLocaleString('tr-TR')} ₺</b>`;
                            d.onclick = () => {
                                sepeteEkle(u);
                                inp.value = '';
                                drop.classList.remove('open');
                            };
                            drop.appendChild(d);
                        });
                    }
                    drop.classList.add('open');
                });
        }, 300);
    });

    document.addEventListener('click', e => { if(!inp.contains(e.target)) drop.classList.remove('open'); });

    function sepeteEkle(u) {
        const varmi = sepet.find(x => x.id === u.id);
        if(varmi) {
            varmi.miktar++;
        } else {
            sepet.push({
                id: u.id,
                ad: u.ad,
                miktar: 1,
                fiyat: parseFloat(u.satis_fiyati) || 0,
                kdv: parseInt(u.kdv_orani) || 20
            });
        }
        render();
    }

    window.sil = (id) => {
        sepet = sepet.filter(x => x.id !== id);
        render();
    };

    window.guncelle = (id, field, val) => {
        const item = sepet.find(x => x.id === id);
        if(item) {
            item[field] = field === 'ad' ? val : parseFloat(val) || 0;
            render();
        }
    };

    function render() {
        const tbody = document.getElementById('kalemler');
        const empty = document.getElementById('emptyRow');
        const ozet = document.getElementById('ozetBolumu');
        
        if(!sepet.length) {
            tbody.innerHTML = '';
            tbody.appendChild(empty);
            ozet.style.display = 'none';
            document.getElementById('lblGenel').textContent = '0,00 ₺';
            return;
        }

        empty.remove();
        ozet.style.display = 'flex';
        tbody.innerHTML = sepet.map(i => `
            <tr>
                <td>${i.ad}</td>
                <td><input type="number" step="any" class="k-fi" value="${i.miktar}" onchange="guncelle(${i.id},'miktar',this.value)"></td>
                <td><input type="number" step="any" class="k-fi" value="${i.fiyat}" onchange="guncelle(${i.id},'fiyat',this.value)"></td>
                <td><select class="k-fi" onchange="guncelle(${i.id},'kdv',this.value)">
                    <option value="20" ${i.kdv==20?'selected':''}>20%</option>
                    <option value="10" ${i.kdv==10?'selected':''}>10%</option>
                    <option value="1" ${i.kdv==1?'selected':''}>1%</option>
                    <option value="0" ${i.kdv==0?'selected':''}>0%</option>
                </select></td>
                <td style="text-align:right; font-weight:700;">${(i.miktar * i.fiyat * (1 + i.kdv/100)).toLocaleString('tr-TR', {minimumFractionDigits:2})} ₺</td>
                <td><button onclick="sil(${i.id})" style="border:none; background:none; color:#e74c3c; cursor:pointer;"><i class="fa-solid fa-trash-can"></i></button></td>
            </tr>
        `).join('');

        let ara = 0, kdv = 0;
        sepet.forEach(i => {
            const a = i.miktar * i.fiyat;
            ara += a;
            kdv += a * (i.kdv / 100);
        });
        const genel = ara + kdv;

        const gStr = genel.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺';
        document.getElementById('lblAra').textContent = ara.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺';
        document.getElementById('lblKdv').textContent = kdv.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺';
        document.getElementById('lblGenelSag').textContent = gStr;
        document.getElementById('lblGenel').textContent = gStr;
    }

    document.getElementById('btnKaydet').onclick = () => {
        if(!sepet.length) { alert('Sepet boş!'); return; }
        
        let ara = 0, kdv = 0;
        sepet.forEach(i => {
            const a = i.miktar * i.fiyat;
            ara += a;
            kdv += a * (i.kdv / 100);
        });
        const genel = ara + kdv;

        const payload = {
            tarih: document.getElementById('tarih').value,
            saat: document.getElementById('saat').value,
            kasa_id: document.getElementById('kasa_id').value,
            aciklama: document.getElementById('aciklama').value,
            sepet: sepet,
            araToplam: ara,
            kdvToplam: kdv,
            genelToplam: genel
        };

        const btn = document.getElementById('btnKaydet');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Kaydediliyor...';

        fetch(`${BASE}/satis/perakende_kaydet`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if(res.status === 'success') {
                alert('Satış başarıyla kaydedildi: ' + res.fatura_no);
                window.location.href = `${BASE}/satis`;
            } else {
                alert('Hata: ' + res.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Satışı Tamamla';
            }
        })
        .catch(err => {
            alert('Bağlantı hatası!');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-bolt"></i> Satışı Tamamla';
        });
    };

})();
</script>
