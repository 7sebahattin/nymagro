<?php
/**
 * Database Sınıfı
 * --------------------------------------------------------
 * PDO tabanlı, Singleton pattern'i ile tek bir bağlantı
 * üzerinden tüm veritabanı işlemlerini yönetir.
 *
 * SQL Injection Koruması:
 *  - Tüm sorgular prepared statement (bindValue) kullanır.
 *  - Doğrudan string interpolation KULLANILMAZ.
 *
 * Kullanım:
 *   $db = Database::getInstance();
 *   $rows = $db->select("SELECT * FROM cariler WHERE silindi_mi = :s", [':s' => 0]);
 *   $db->insert('cariler', ['unvan' => 'ABC Ltd', 'tip' => 'musteri']);
 */
final class Database
{
    /** @var Database|null */
    private static ?Database $instance = null;

    /** @var PDO */
    private PDO $pdo;

    /** @var PDOStatement|false */
    private $stmt = false;

    /** İç içe begin()/commit() çağrılarını doğru saymak için derinlik sayacı. */
    private int $transactionDepth = 0;

    // --------------------------------------------------------------
    // Yapıcı (private) — Singleton
    // --------------------------------------------------------------
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // Gerçek prepared statement
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_turkish_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Geliştirme ortamında detay göster, canlıda logla
            if (defined('APP_ENV') && APP_ENV === 'development') {
                die('Veritabanı bağlantı hatası: ' . $e->getMessage());
            }
            error_log('DB Connection Error: ' . $e->getMessage());
            die('Sistem şu anda hizmet veremiyor. Lütfen daha sonra tekrar deneyin.');
        }
    }

    // Klonlama ve unserialize engellendi
    private function __clone() {}
    public function __wakeup() { throw new Exception('Singleton sınıf unserialize edilemez.'); }

    // --------------------------------------------------------------
    // Tek instance
    // --------------------------------------------------------------
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // --------------------------------------------------------------
    // Ham PDO'ya erişim (ileri seviye ihtiyaçlar için)
    // --------------------------------------------------------------
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    // --------------------------------------------------------------
    // Sorgu çalıştır (prepared statement)
    // --------------------------------------------------------------
    public function query(string $sql, array $params = []): PDOStatement
    {
        [$sql, $params] = $this->expandRepeatedNamedParams($sql, $params);
        $this->stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            // :anahtar veya 1-tabanlı sıralı parametre desteği
            $param = is_int($key) ? $key + 1 : $key;
            if (is_string($param) && strpos($sql, $param) === false) {
                continue;
            }
            $this->stmt->bindValue($param, $value, $this->pdoType($value));
        }

        $this->stmt->execute();
        return $this->stmt;
    }

    // --------------------------------------------------------------
    // SELECT — birden fazla satır
    // --------------------------------------------------------------
    public function select(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    // --------------------------------------------------------------
    // SELECT — tek satır
    // --------------------------------------------------------------
    public function selectOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row !== false ? $row : null;
    }

    // --------------------------------------------------------------
    // INSERT — data = ['kolon' => 'değer', ...]
    // --------------------------------------------------------------
    public function insert(string $table, array $data): int
    {
        if (class_exists('TenantContext')) {
            TenantContext::assertWritablePeriod($table);
            $data = TenantContext::tenantAwareInsert($table, $data);
        }

        $table   = $this->escapeIdentifier($table);
        $cols    = array_keys($data);
        $colsSql = implode(', ', array_map([$this, 'escapeIdentifier'], $cols));
        $phSql   = ':' . implode(', :', $cols);

        $params = [];
        foreach ($data as $k => $v) {
            $params[':' . $k] = $v;
        }

        $this->query("INSERT INTO {$table} ({$colsSql}) VALUES ({$phSql})", $params);
        return (int)$this->pdo->lastInsertId();
    }

    // --------------------------------------------------------------
    // UPDATE — data + where
    // --------------------------------------------------------------
    public function update(string $table, array $data, array $where): int
    {
        if (class_exists('TenantContext')) {
            TenantContext::assertWritablePeriod($table);
            $where = TenantContext::tenantAwareWhere($table, $where);
        }

        $table  = $this->escapeIdentifier($table);

        $setParts = [];
        $params   = [];
        foreach ($data as $col => $val) {
            $setParts[]      = $this->escapeIdentifier($col) . ' = :set_' . $col;
            $params[':set_' . $col] = $val;
        }

        $whereParts = [];
        foreach ($where as $col => $val) {
            $whereParts[]    = $this->escapeIdentifier($col) . ' = :w_' . $col;
            $params[':w_' . $col] = $val;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts)
             . ' WHERE ' . implode(' AND ', $whereParts);

        return $this->query($sql, $params)->rowCount();
    }

    // --------------------------------------------------------------
    // Soft-Delete (silindi_mi = 1)
    // --------------------------------------------------------------
    public function softDelete(string $table, int $id): int
    {
        if (class_exists('TenantContext')) {
            TenantContext::assertWritablePeriod($table);
        }

        return $this->update($table, ['silindi_mi' => 1], ['id' => $id]);
    }

    // --------------------------------------------------------------
    // Transactions
    // --------------------------------------------------------------
    /**
     * İç içe begin()/commit() çağrılarını destekler: yalnızca en dıştaki
     * begin() gerçek bir PDO transaction'ı başlatır; iç çağrılar sadece
     * derinlik sayacını artırır. Böylece bir model başka bir modelin
     * (örn. Fatura::ekle() içinden Urun::stokHareketiEkle()) kendi
     * begin()/commit() çağrısı, dıştaki transaction'ı erken kapatmaz.
     */
    public function begin(): bool
    {
        if ($this->transactionDepth === 0) {
            $this->pdo->beginTransaction();
        }
        $this->transactionDepth++;
        return true;
    }

    /**
     * Şu an açık bir transaction var mı? DDL (ALTER TABLE/CREATE TABLE)
     * çalıştırmadan önce kontrol edilmeli — MySQL/MariaDB DDL'de örtük
     * commit yapar, bu da transactionDepth sayacını dış transaction'dan
     * habersiz bırakıp sonraki rollBack()'i etkisiz/tutarsız kılar.
     */
    public function inTransaction(): bool
    {
        return $this->transactionDepth > 0;
    }

    public function commit(): bool
    {
        if ($this->transactionDepth <= 0) {
            return false;
        }
        $this->transactionDepth--;
        if ($this->transactionDepth === 0 && $this->pdo->inTransaction()) {
            return $this->pdo->commit();
        }
        return true;
    }

    /** Herhangi bir seviyeden rollBack() tüm iç içe işlemi geri alır (savepoint desteği yok). */
    public function rollBack(): bool
    {
        if ($this->transactionDepth <= 0) {
            return false;
        }
        $this->transactionDepth = 0;
        if ($this->pdo->inTransaction()) {
            return $this->pdo->rollBack();
        }
        return false;
    }

    // --------------------------------------------------------------
    // Yardımcılar
    // --------------------------------------------------------------
    private function pdoType($value): int
    {
        if (is_int($value))  return PDO::PARAM_INT;
        if (is_bool($value)) return PDO::PARAM_BOOL;
        if (is_null($value)) return PDO::PARAM_NULL;
        return PDO::PARAM_STR;
    }

    private function expandRepeatedNamedParams(string $sql, array $params): array
    {
        $counts = [];
        $expanded = $params;

        $sql = preg_replace_callback('/:[A-Za-z_][A-Za-z0-9_]*/', function (array $match) use (&$counts, &$expanded, $params): string {
            $name = $match[0];
            if (!array_key_exists($name, $params)) {
                return $name;
            }

            $counts[$name] = ($counts[$name] ?? 0) + 1;
            if ($counts[$name] === 1) {
                return $name;
            }

            $newName = $name . '__' . $counts[$name];
            $expanded[$newName] = $params[$name];
            return $newName;
        }, $sql);

        return [$sql, $expanded];
    }

    /**
     * Tablo/kolon isimlerini backtick ile quote eder.
     * Sadece alfanumerik + underscore kabul edilir (SQL injection önleme).
     */
    private function escapeIdentifier(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Geçersiz tablo/kolon adı: {$name}");
        }
        return "`{$name}`";
    }
}
