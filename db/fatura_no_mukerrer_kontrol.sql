-- =====================================================================
--  NYMAGRO — Mükerrer belge numarası kontrolü
--
--  Uygulama, faturalar tablosuna aşağıdaki tekillik kısıtını kendiliğinden
--  eklemeye çalışır (Fatura::ensureFaturaNoTekilligi):
--
--      UNIQUE (company_id, period_id, fatura_no_aktif)
--
--  Mevcut veride aynı şirket+dönem içinde tekrar eden bir belge numarası
--  varsa bu ALTER başarısız olur ve SESSİZCE geçilir — uygulama çalışmaya
--  devam eder ama koruma DEVREYE GİRMEZ.
--
--  Bu betik yalnızca RAPORLAR; hiçbir şeyi değiştirmez veya silmez.
--  Önce 1. sorguyu çalıştırın: boş dönerse 3. adımdaki doğrulamaya geçin.
--
--  ⚠️ Herhangi bir düzeltmeden önce yedek alın:
--      mysqldump -u KULLANICI -p VERITABANI > yedek_$(date +%F).sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) Mükerrer belge numaraları
--    Aynı şirket+dönem içinde birden fazla kez kullanılmış numaralar.
--    Farklı belge tipleri de dahildir — asıl sorun zaten budur:
--    bir numune/irsaliye, satış faturası serisinden numara almış olabilir.
-- ---------------------------------------------------------------------
SELECT
    f.company_id,
    f.period_id,
    f.fatura_no,
    COUNT(*)                                   AS tekrar_sayisi,
    GROUP_CONCAT(f.id ORDER BY f.id)           AS fatura_idler,
    GROUP_CONCAT(DISTINCT f.belge_tipi)        AS belge_tipleri,
    GROUP_CONCAT(f.fatura_tarihi ORDER BY f.id) AS tarihler
FROM faturalar f
WHERE f.silindi_mi = 0
GROUP BY f.company_id, f.period_id, f.fatura_no
HAVING COUNT(*) > 1
ORDER BY f.company_id, f.period_id, f.fatura_no;

-- ---------------------------------------------------------------------
-- 2) Serisi belge tipine uymayan belgeler
--    Ör. belge_tipi='numune' olduğu hâlde numarası satış serisinden
--    (invoice_prefix) verilmiş kayıtlar. Bunlar henüz mükerrer olmasa bile
--    ileride mükerrerliğe yol açar; numaraları düzeltilmelidir.
--
--    NOT: Ön ekler şirkete göre değişir (company_settings.invoice_prefix /
--    purchase_invoice_prefix), bu yüzden sorgu ayarlardan okur.
-- ---------------------------------------------------------------------
SELECT
    f.id,
    f.company_id,
    f.period_id,
    f.belge_tipi,
    f.fatura_no,
    f.fatura_tarihi,
    CASE f.belge_tipi
        WHEN 'satis'      THEN COALESCE(NULLIF(cs.invoice_prefix, ''), 'SAT')
        WHEN 'alis'       THEN COALESCE(NULLIF(cs.purchase_invoice_prefix, ''), 'ALI')
        WHEN 'perakende'  THEN 'PRK'
        WHEN 'iade_satis' THEN 'IDS'
        WHEN 'iade_alis'  THEN 'IDA'
        WHEN 'proforma'   THEN 'PRO'
        WHEN 'siparis'    THEN 'SIP'
        WHEN 'irsaliye'   THEN 'IRS'
        WHEN 'numune'     THEN 'NUM'
    END AS beklenen_on_ek
FROM faturalar f
JOIN accounting_periods p
      ON p.id = f.period_id
LEFT JOIN company_settings cs
      ON cs.company_id = f.company_id
WHERE f.silindi_mi = 0
  AND f.fatura_no NOT LIKE CONCAT(
        CASE f.belge_tipi
            WHEN 'satis'      THEN COALESCE(NULLIF(cs.invoice_prefix, ''), 'SAT')
            WHEN 'alis'       THEN COALESCE(NULLIF(cs.purchase_invoice_prefix, ''), 'ALI')
            WHEN 'perakende'  THEN 'PRK'
            WHEN 'iade_satis' THEN 'IDS'
            WHEN 'iade_alis'  THEN 'IDA'
            WHEN 'proforma'   THEN 'PRO'
            WHEN 'siparis'    THEN 'SIP'
            WHEN 'irsaliye'   THEN 'IRS'
            WHEN 'numune'     THEN 'NUM'
        END,
        '-', p.fiscal_year, '-%')
ORDER BY f.company_id, f.period_id, f.belge_tipi, f.id;

-- ---------------------------------------------------------------------
-- 3) Tekillik kısıtı gerçekten kuruldu mu?
--    Bir satır dönmelidir. Boş dönüyorsa koruma YOKTUR: 1. sorgudaki
--    mükerrerleri temizleyip uygulamayı bir kez daha açın (kısıt otomatik
--    kurulmaya çalışılır) veya aşağıdaki ALTER'ı elle çalıştırın.
-- ---------------------------------------------------------------------
SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS kolonlar
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'faturalar'
  AND INDEX_NAME   = 'uq_faturalar_no_aktif'
GROUP BY INDEX_NAME;

-- ---------------------------------------------------------------------
-- 4) Kısıtı elle kurmak (yalnızca 1. sorgu BOŞ döndükten sonra)
--    Sanal kolon, silinmiş satırlarda NULL üretir; UNIQUE indeks birden çok
--    NULL'a izin verdiği için silinen faturaların numaraları kısıtı kilitlemez.
-- ---------------------------------------------------------------------
-- ALTER TABLE faturalar
--     ADD COLUMN fatura_no_aktif VARCHAR(80)
--         AS (IF(silindi_mi = 0, fatura_no, NULL)) VIRTUAL;
--
-- ALTER TABLE faturalar
--     ADD UNIQUE KEY uq_faturalar_no_aktif (company_id, period_id, fatura_no_aktif);
