<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/Transaction.php';

// Inisialisasi token CSRF untuk sesi ini bila belum ada
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inisialisasi saldo dan riwayat transaksi dalam sesi
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0.0;
}
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

$errors = [];
$successMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors[] = 'Token CSRF tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {
        $type = $_POST['type'] ?? '';
        $amountRaw = $_POST['amount'] ?? '';

        // Validasi jenis transaksi
        $validTypes = ['deposit', 'withdrawal'];
        if (!is_string($type) || !in_array($type, $validTypes, true)) {
            $errors[] = 'Jenis transaksi tidak valid.';
        }

        // Validasi jumlah: harus angka desimal positif (maks. 2 desimal)
        if (!is_string($amountRaw) || $amountRaw === '' || !preg_match('/^\d+(\.\d{1,2})?$/', $amountRaw)) {
            $errors[] = 'Jumlah transaksi harus berupa angka desimal positif (contoh: 50000 atau 50000.50).';
        }

        if (empty($errors)) {
            $amount = (float) $amountRaw;

            if ($amount <= 0.0) {
                $errors[] = 'Jumlah transaksi harus lebih besar dari nol.';
            } else {
                $id = bin2hex(random_bytes(8));
                $transaction = new Transaction($id, $type, $amount);

                $balance = (float) $_SESSION['balance'];
                $processed = $transaction->process($balance);

                if (!$processed) {
                    $errors[] = 'Saldo tidak mencukupi untuk melakukan penarikan.';
                } else {
                    $_SESSION['balance'] = $balance;
                    $_SESSION['history'][] = $transaction->toArray();
                    $successMessage = 'Transaksi berhasil diproses.';
                }
            }
        }
    }

    // Regenerasi token CSRF setelah setiap POST untuk mencegah replay
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$balance = (float) $_SESSION['balance'];
$history = $_SESSION['history'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sistem Manajemen Keuangan Sederhana</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; color: #222; }
    h1 { font-size: 1.4rem; }
    .balance { font-size: 1.2rem; font-weight: bold; margin: 1rem 0; }
    .errors { background: #fdecea; border: 1px solid #f5c2c0; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
    .errors ul { margin: 0; padding-left: 1.2rem; }
    .success { background: #e6f6e9; border: 1px solid #b7e2c0; padding: 0.6rem 1rem; border-radius: 4px; }
    form { display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1.5rem; max-width: 320px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 0.4rem 0.6rem; text-align: left; font-size: 0.9rem; }
    th { background: #f4f4f4; }
</style>
</head>
<body>

<h1>Sistem Manajemen Keuangan Sederhana</h1>

<?php if (!empty($errors)): ?>
    <div class="errors">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($successMessage !== null): ?>
    <p class="success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<p class="balance">
    Saldo saat ini: Rp <?= htmlspecialchars(number_format($balance, 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>
</p>

<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <label for="type">Jenis Transaksi</label>
    <select name="type" id="type" required>
        <option value="deposit">Deposit</option>
        <option value="withdrawal">Penarikan</option>
    </select>

    <label for="amount">Jumlah</label>
    <input type="text" name="amount" id="amount" required
           pattern="^\d+(\.\d{1,2})?$" placeholder="Contoh: 50000.00">

    <button type="submit">Proses Transaksi</button>
</form>

<h2>Riwayat Transaksi</h2>

<?php if (empty($history)): ?>
    <p>Belum ada transaksi.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Jenis</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_reverse($history) as $item): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?= htmlspecialchars(
                            $item['type'] === 'deposit' ? 'Deposit' : 'Penarikan',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </td>
                    <td><?= htmlspecialchars(number_format((float) $item['amount'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>
