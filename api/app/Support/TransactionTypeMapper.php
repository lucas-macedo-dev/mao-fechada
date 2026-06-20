<?php

namespace App\Support;

class TransactionTypeMapper
{
    private const API_TO_DB = [
        'entrada' => 'income',
        'saida' => 'expense',
        'income' => 'income',
        'expense' => 'expense',
    ];

    private const DB_TO_API = [
        'income' => 'entrada',
        'expense' => 'saida',
        'entrada' => 'entrada',
        'saida' => 'saida',
    ];

    public static function toDatabase(string $type): string
    {
        return self::API_TO_DB[$type] ?? $type;
    }

    public static function toApi(string $type): string
    {
        return self::DB_TO_API[$type] ?? $type;
    }

    public static function incomeValues(): array
    {
        return ['income', 'entrada'];
    }

    public static function expenseValues(): array
    {
        return ['expense', 'saida'];
    }
}
