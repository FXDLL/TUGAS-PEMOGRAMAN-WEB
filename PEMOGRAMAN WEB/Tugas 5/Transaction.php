<?php

declare(strict_types=1);

/**
 * Merepresentasikan satu transaksi keuangan (deposit atau penarikan).
 * Properti bersifat private dan hanya dapat diakses melalui getter,
 * menjaga enkapsulasi objek.
 */
class Transaction
{
    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly float $amount
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Memproses transaksi terhadap saldo yang diberikan (passed by reference).
     * Mengembalikan true bila berhasil, false bila ditolak
     * (misalnya penarikan melebihi saldo yang tersedia).
     */
    public function process(float &$balance): bool
    {
        return match ($this->type) {
            'deposit'    => $this->processDeposit($balance),
            'withdrawal' => $this->processWithdrawal($balance),
            default      => false,
        };
    }

    private function processDeposit(float &$balance): bool
    {
        $balance += $this->amount;
        return true;
    }

    private function processWithdrawal(float &$balance): bool
    {
        if ($this->amount > $balance) {
            return false;
        }

        $balance -= $this->amount;
        return true;
    }

    /**
     * Representasi array dari transaksi, untuk disimpan ke riwayat sesi.
     *
     * @return array{id: string, type: string, amount: float}
     */
    public function toArray(): array
    {
        return [
            'id'     => $this->id,
            'type'   => $this->type,
            'amount' => $this->amount,
        ];
    }
}
