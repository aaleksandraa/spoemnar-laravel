<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class FullNameSearch
{
    public static function expression(string $firstNameColumn = 'first_name', string $lastNameColumn = 'last_name'): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "COALESCE({$firstNameColumn}, '') || ' ' || COALESCE({$lastNameColumn}, '')";
        }

        return "CONCAT(COALESCE({$firstNameColumn}, ''), ' ', COALESCE({$lastNameColumn}, ''))";
    }
}
