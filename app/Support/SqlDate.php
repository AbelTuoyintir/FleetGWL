<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SqlDate
{
    public static function year(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "cast(strftime('%Y', {$column}) as integer)",
            'pgsql' => "cast(extract(year from {$column}) as integer)",
            default => "YEAR({$column})",
        };
    }

    public static function month(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "cast(strftime('%m', {$column}) as integer)",
            'pgsql' => "cast(extract(month from {$column}) as integer)",
            default => "MONTH({$column})",
        };
    }
}
