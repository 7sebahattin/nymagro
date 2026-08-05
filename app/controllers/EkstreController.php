<?php

final class EkstreController extends Controller
{
    private Cari $cariModel;

    public function __construct()
    {
        $this->cariModel = $this->model('Cari');
    }

    public function cari(string $tip = 'musteri', $id = 0): void
    {
        $tip = $this->normalizeTip($tip);
        if ($tip === null) {
            $this->redirect('dashboard');
        }

        $cariId = (int)$id;
        $cari = $this->cariModel->getirTipli($cariId, $tip);
        if (!$cari) {
            $this->setFlash('error', 'Cari kayıt bulunamadı.');
            $this->redirect($tip === 'musteri' ? 'musteri' : 'tedarikci');
        }

        [$baslangic, $bitis] = $this->dateRange();
        $rows = $this->buildStatementRows($cariId, $tip, $baslangic, $bitis);
        $totalBorc = $this->sum($rows, 'borc');
        $totalAlacak = $this->sum($rows, 'alacak');

        $this->view('ekstre/cari', [
            'pageTitle' => 'Hesap Ekstresi',
            'activeMenu' => $tip === 'musteri' ? 'musteriler' : 'tedarikciler',
            'topbarIcon' => 'fa-solid fa-list',
            'topbarTitle' => 'Hesap Ekstresi',
            'cari' => $cari,
            'cariTip' => $tip,
            'baslangic' => $baslangic,
            'bitis' => $bitis,
            'rows' => $rows,
            'toplamBorc' => $totalBorc,
            'toplamAlacak' => $totalAlacak,
            'bakiye' => $totalBorc - $totalAlacak,
        ]);
    }

    public function mutabakat(string $tip = 'musteri', $id = 0): void
    {
        $tip = $this->normalizeTip($tip);
        if ($tip === null) {
            $this->redirect('dashboard');
        }

        $cariId = (int)$id;
        $cari = $this->cariModel->getirTipli($cariId, $tip);
        if (!$cari) {
            $this->setFlash('error', 'Cari kayıt bulunamadı.');
            $this->redirect($tip === 'musteri' ? 'musteri' : 'tedarikci');
        }

        $tarih = $this->validDate((string)($_GET['tarih'] ?? '')) ?: date('Y-m-d');
        $nextDay = date('Y-m-d', strtotime($tarih . ' +1 day'));
        $bakiye = $this->cariModel->ekstreDevirBakiyesi($cariId, $tip, $nextDay);
        $company = TenantContext::activeCompany() ?? [];

        $filename = $this->safeFilename('mutabakat_' . ($cari['unvan'] ?? 'cari') . '_' . $tarih . '.doc');
        $html = $this->mutabakatHtml($cari, $company, $tarih, $bakiye);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/msword; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $html;
        exit;
    }

    public function excel(string $tip = 'musteri', $id = 0): void
    {
        $tip = $this->normalizeTip($tip);
        if ($tip === null) {
            $this->redirect('dashboard');
        }

        $cariId = (int)$id;
        $cari = $this->cariModel->getirTipli($cariId, $tip);
        if (!$cari) {
            $this->redirect($tip === 'musteri' ? 'musteri' : 'tedarikci');
        }

        [$baslangic, $bitis] = $this->dateRange();
        $rows = $this->buildStatementRows($cariId, $tip, $baslangic, $bitis);
        $filename = $this->safeFilename('hesap_ekstresi_' . ($cari['unvan'] ?? 'cari') . '_' . $bitis . '.xls');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        echo '<table border="1">';
        echo '<tr><th colspan="11">' . htmlspecialchars((string)($cari['unvan'] ?? '-')) . ' HESAP EKSTRESİ</th></tr>';
        echo '<tr><th>Tarih</th><th>Vade</th><th>Hareket</th><th>Belge No</th><th>Açıklama</th><th>Miktar</th><th>Ödeme</th><th>Fiyat</th><th>Borç</th><th>Alacak</th><th>Bakiye</th></tr>';
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($this->dateTr((string)($row['tarih'] ?? ''))) . '</td>';
            echo '<td>' . htmlspecialchars($this->dateTr((string)($row['vade'] ?? ''))) . '</td>';
            echo '<td>' . htmlspecialchars((string)($row['hareket'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars((string)($row['belge_no'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars((string)($row['aciklama'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars((string)($row['miktar'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars((string)($row['odeme'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars((string)($row['fiyat'] ?? '')) . '</td>';
            echo '<td>' . htmlspecialchars($this->money((float)($row['borc'] ?? 0))) . '</td>';
            echo '<td>' . htmlspecialchars($this->money((float)($row['alacak'] ?? 0))) . '</td>';
            echo '<td>' . htmlspecialchars($this->money((float)($row['bakiye'] ?? 0))) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        exit;
    }

    private function buildStatementRows(int $cariId, string $tip, string $baslangic, string $bitis): array
    {
        $rows = [];
        $devir = $this->cariModel->ekstreDevirBakiyesi($cariId, $tip, $baslangic);
        $balance = 0.0;

        $rows[] = [
            'tarih' => $baslangic,
            'vade' => '',
            'hareket' => '',
            'belge_no' => '',
            'aciklama' => 'Devir bakiye',
            'miktar' => '',
            'odeme' => '',
            'fiyat' => '',
            'borc' => $devir > 0 ? $devir : 0.0,
            'alacak' => $devir < 0 ? abs($devir) : 0.0,
            'bakiye' => $devir,
        ];
        $balance = $devir;

        foreach ($this->cariModel->ekstreHareketleri($cariId, $tip, $baslangic, $bitis) as $row) {
            $balance += (float)$row['borc'] - (float)$row['alacak'];
            $row['bakiye'] = $balance;
            $rows[] = $row;
        }

        return $rows;
    }

    private function dateRange(): array
    {
        $bitis = $this->validDate((string)($_GET['bitis'] ?? '')) ?: date('Y-m-d');
        $baslangic = $this->validDate((string)($_GET['baslangic'] ?? '')) ?: date('Y-m-d', strtotime($bitis . ' -1 month'));
        if ($baslangic > $bitis) {
            [$baslangic, $bitis] = [$bitis, $baslangic];
        }
        return [$baslangic, $bitis];
    }

    private function validDate(string $date): ?string
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date ? $date : null;
    }

    private function normalizeTip(string $tip): ?string
    {
        $tip = strtolower(trim($tip));
        return in_array($tip, ['musteri', 'tedarikci'], true) ? $tip : null;
    }

    private function sum(array $rows, string $field): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float)($row[$field] ?? 0);
        }
        return $total;
    }

    private function mutabakatHtml(array $cari, array $company, string $date, float $balance): string
    {
        $companyName = (string)($company['company_name'] ?? APP_NAME);
        $dateTr = date('d.m.Y', strtotime($date));
        $amount = $this->money(abs($balance));
        $balanceType = $balance >= 0 ? 'ALACAK' : 'BORÇ';
        $amountText = $this->amountText(abs($balance));

        return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page { margin: 2.2cm 2.4cm; }
body { font-family: Arial, sans-serif; font-size: 12pt; color: #000; line-height: 1.25; }
.date { text-align: right; font-weight: bold; margin-bottom: 34px; }
.subject { margin-bottom: 34px; }
.bold { font-weight: bold; }
.stamp { color: #777; margin: 18px 0 96px; }
ol { margin-left: 24px; font-size: 10pt; }
li { margin-bottom: 14px; }
.line { border-top: 2px solid #4d8fe8; margin: 36px 0 30px; }
.reply { margin-top: 20px; }
</style>
</head>
<body>
  <div class="date">' . htmlspecialchars($dateTr) . '</div>
  <div class="subject"><span class="bold">Konu :</span> Hesap Mutabakatı Hakkında</div>
  <p class="bold">Sayın ' . htmlspecialchars((string)($cari['unvan'] ?? '-')) . ',</p>
  <p>Şirketimiz nezdindeki cari hesabınız <span class="bold">' . htmlspecialchars($dateTr) . '</span> tarihi itibariyle <span class="bold">' . htmlspecialchars($amount) . ' TL (' . htmlspecialchars($amountText) . ') ' . htmlspecialchars($balanceType) . '</span><br>
  bakiyesi vermektedir.<br>
  Mutabık olup olmadığınızı bildirmenizi rica ederiz.</p>
  <p>Saygılarımızla,<br>' . htmlspecialchars($companyName) . '</p>
  <p class="stamp">[Kaşe İmza]</p>
  <ol>
    <li>Mutabakat veya itirazınızı bildirmediğiniz takdirde T.T.K’nın 94. maddesi gereğince mutabık sayılacağımızı hatırlatırız.</li>
    <li>Bakiyede mutabık olmadığınız takdirde bir hesap ekstrenizin gönderilmesini rica ederiz.</li>
    <li>Mutabakat yazımızı alınca firmanızın kaşe ve yetkili imzası ile tarafımıza gönderilmesini rica ederiz.</li>
    <li>Hata ve unutma müstesnadır.</li>
  </ol>
  <div class="line"></div>
  <p class="bold">Sayın ' . htmlspecialchars($companyName) . ',</p>
  <p class="reply">........................ tarihi itibari ile .............................. BORÇ / ALACAK bakiyesinde mutabık<br>
  OLDUĞUMUZU / OLMADIĞIMIZI bildiririz.</p>
  <p>Hesap ekstremiz ektedir.</p>
  <p>Saygılarımızla,</p>
</body>
</html>';
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }

    private function dateTr(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date('d.m.Y', $ts) : '';
    }

    private function amountText(float $value): string
    {
        if ($value < 0.01) {
            return 'Sıfır TL';
        }
        $whole = (int)floor($value);
        $cents = (int)round(($value - $whole) * 100);
        $text = $this->numberToTurkish($whole) . ' TL';
        if ($cents > 0) {
            $text .= ' ' . $this->numberToTurkish($cents) . ' Kuruş';
        }
        return $text;
    }

    private function numberToTurkish(int $number): string
    {
        if ($number === 0) {
            return 'Sıfır';
        }

        $ones = ['', 'Bir', 'İki', 'Üç', 'Dört', 'Beş', 'Altı', 'Yedi', 'Sekiz', 'Dokuz'];
        $tens = ['', 'On', 'Yirmi', 'Otuz', 'Kırk', 'Elli', 'Altmış', 'Yetmiş', 'Seksen', 'Doksan'];
        $thousands = ['', 'Bin', 'Milyon', 'Milyar'];
        $parts = [];
        $group = 0;

        while ($number > 0) {
            $chunk = $number % 1000;
            if ($chunk > 0) {
                $chunkText = '';
                $hundreds = intdiv($chunk, 100);
                $remainder = $chunk % 100;
                if ($hundreds > 0) {
                    $chunkText .= ($hundreds === 1 ? 'Yüz' : $ones[$hundreds] . ' Yüz');
                }
                if ($remainder > 0) {
                    $chunkText .= ($chunkText !== '' ? ' ' : '') . $tens[intdiv($remainder, 10)];
                    $one = $remainder % 10;
                    if ($one > 0) {
                        $chunkText .= ($tens[intdiv($remainder, 10)] !== '' ? ' ' : '') . $ones[$one];
                    }
                }
                if ($group === 1 && $chunk === 1) {
                    $chunkText = 'Bin';
                } elseif ($group > 0) {
                    $chunkText .= ' ' . $thousands[$group];
                }
                array_unshift($parts, trim($chunkText));
            }
            $number = intdiv($number, 1000);
            $group++;
        }

        return implode(' ', $parts);
    }

    private function safeFilename(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $name) ?: 'mutabakat.doc';
        return trim($name, '_');
    }
}
