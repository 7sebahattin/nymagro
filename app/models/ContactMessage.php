<?php
/**
 * ContactMessage
 * --------------------------------------------------------
 * Public web sitesinden gelen teklif / iletişim formlarını saklar.
 * Tek tablo: contact_messages — tenant'a bağımlı değil.
 */
final class ContactMessage
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureTable();
    }

    public function kaydet(array $data): int
    {
        $this->db->query(
            "INSERT INTO contact_messages
               (locale, ad_soyad, firma, ulke, email, telefon, urun, mesaj, ip, user_agent, status)
             VALUES
               (:loc, :ad, :firma, :ulke, :em, :tel, :urun, :msg, :ip, :ua, 'new')",
            [
                ':loc'   => substr((string)($data['locale'] ?? 'tr'), 0, 5),
                ':ad'    => mb_substr((string)($data['ad_soyad'] ?? ''), 0, 150),
                ':firma' => mb_substr((string)($data['firma']   ?? ''), 0, 150),
                ':ulke'  => mb_substr((string)($data['ulke']    ?? ''), 0, 80),
                ':em'    => mb_substr((string)($data['email']   ?? ''), 0, 150),
                ':tel'   => mb_substr((string)($data['telefon'] ?? ''), 0, 50),
                ':urun'  => mb_substr((string)($data['urun']    ?? ''), 0, 120),
                ':msg'   => mb_substr((string)($data['mesaj']   ?? ''), 0, 4000),
                ':ip'    => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                ':ua'    => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]
        );
        return (int)$this->db->pdo()->lastInsertId();
    }

    public function tumMesajlar(int $limit = 100): array
    {
        return $this->db->select("SELECT * FROM contact_messages ORDER BY id DESC LIMIT {$limit}");
    }

    public function yeniSay(): int
    {
        $r = $this->db->select("SELECT COUNT(*) AS n FROM contact_messages WHERE status = 'new'");
        return (int)($r[0]['n'] ?? 0);
    }

    public function durumGuncelle(int $id, string $status): void
    {
        $allowed = ['new', 'read', 'replied', 'spam'];
        if (!in_array($status, $allowed, true)) return;
        $this->db->query(
            "UPDATE contact_messages SET status = :s WHERE id = :id",
            [':s' => $status, ':id' => $id]
        );
    }

    public function sil(int $id): void
    {
        $this->db->query("DELETE FROM contact_messages WHERE id = :id", [':id' => $id]);
    }

    private function ensureTable(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS contact_messages (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                locale      VARCHAR(8)   NOT NULL DEFAULT 'tr',
                ad_soyad    VARCHAR(160) NULL,
                firma       VARCHAR(160) NULL,
                ulke        VARCHAR(80)  NULL,
                email       VARCHAR(160) NULL,
                telefon     VARCHAR(60)  NULL,
                urun        VARCHAR(140) NULL,
                mesaj       TEXT NULL,
                ip          VARCHAR(45)  NULL,
                user_agent  VARCHAR(255) NULL,
                status      ENUM('new','read','replied','spam') NOT NULL DEFAULT 'new',
                created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_created (created_at),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci
        ");
    }
}
