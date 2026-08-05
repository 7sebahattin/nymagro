<?php
$pageTitle = $pageTitle ?? 'Fatura Yazdir';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="icon" href="<?= htmlspecialchars(BASE_URL) ?>/favicon.ico?v=20260501" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= htmlspecialchars(BASE_URL) ?>/favicon-32x32.png?v=20260501">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= htmlspecialchars(BASE_URL) ?>/apple-touch-icon.png?v=20260501">
  <style>
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #eef2f7; color: #111827; font-family: Arial, Helvetica, sans-serif; }
    .print-shell { min-height: 100vh; padding: 18px; }
    .print-toolbar { width: 190mm; max-width: 100%; margin: 0 auto 12px; display: flex; justify-content: flex-end; gap: 8px; }
    .print-toolbar button, .print-toolbar a { border: 0; border-radius: 6px; padding: 9px 13px; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; }
    .print-toolbar button { background: #111827; color: #fff; }
    .print-toolbar a { background: #64748b; color: #fff; }
    @page { size: A4; margin: 10mm; }
    @media print {
      html, body { width: 210mm; min-height: 297mm; background: #fff !important; }
      .print-shell { padding: 0 !important; }
      .print-toolbar, .no-print { display: none !important; }
      .invoice-print-area { width: 190mm !important; margin: 0 auto !important; box-shadow: none !important; border-radius: 0 !important; }
    }
  </style>
</head>
<body>
  <div class="print-shell">
    <div class="print-toolbar no-print">
      <a href="javascript:window.close()">Kapat</a>
      <button type="button" onclick="window.print()">Yazdir / PDF Kaydet</button>
    </div>
    <?= $content ?>
  </div>
  <script>
    window.addEventListener('load', function () {
      setTimeout(function () { window.print(); }, 250);
    });
  </script>
</body>
</html>
