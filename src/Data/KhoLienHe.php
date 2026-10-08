<?php
namespace App\Data;

class KhoLienHe
{
    public function __construct(private string $tep) {}

    public function them(array $lh): void
    {
        $dong = json_encode($lh,
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        file_put_contents($this->tep, $dong . PHP_EOL,
                          FILE_APPEND | LOCK_EX);    // khóa tệp khi ghi
    }

    public function tatCa(): array                  // mới nhất đứng đầu
    {
        if (!is_file($this->tep)) return [];
        $dong = file($this->tep,
                     FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $ds = array_map(fn($d) => json_decode($d, true), $dong);
        return array_reverse(array_filter($ds, 'is_array'));
    }
}