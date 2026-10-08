<?php
declare(strict_types=1);

class GuestBook
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Simpan pesan baru (prepared statement). */
    public function simpan(string $nama, string $email, string $pesan): bool
    {
        $sql = 'INSERT INTO buku_tamu (nama, email, pesan)
                VALUES (:nama, :email, :pesan)';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nama'  => $nama,
            ':email' => $email,
            ':pesan' => $pesan,
        ]);
    }

    /** Ambil semua pesan, terbaru di atas (prepared statement). */
    public function ambilSemua(): array
    {
        $sql = 'SELECT id, nama, email, pesan, tanggal_kirim
                FROM buku_tamu
                ORDER BY tanggal_kirim DESC, id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Validasi masukan; mengembalikan array pesan error (kosong = valid). */
    public static function validasi(string $nama, string $email, string $pesan): array
    {
        $errors = [];

        if ($nama === '') {
            $errors['nama'] = 'Nama tidak boleh kosong.';
        } elseif (mb_strlen($nama) > 100) {
            $errors['nama'] = 'Nama maksimal 100 karakter.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
            $errors['email'] = 'Format email tidak valid.';
        }

        if (mb_strlen($pesan) < 5) {
            $errors['pesan'] = 'Pesan minimal 5 karakter.';
        }

        return $errors;
    }
}
