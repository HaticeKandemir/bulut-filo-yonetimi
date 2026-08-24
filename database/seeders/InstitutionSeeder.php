<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Seed the PTT institution tree (matches the kurum_kodu values used in
     * the sample import spreadsheets under docs/).
     */
    public function run(): void
    {
        $ptt = Institution::create(['name' => 'PTT', 'code' => 'PTT']);

        Institution::create([
            'name' => 'PTT ANADOLUM',
            'code' => 'PTT-ANADOLUM',
            'parent_id' => $ptt->id,
        ]);

        $eAvm = Institution::create([
            'name' => 'PTT E-AVM',
            'code' => 'PTT-EAVM',
            'parent_id' => $ptt->id,
        ]);

        Institution::create([
            'name' => 'PTTEM',
            'code' => 'PTTEM',
            'parent_id' => $eAvm->id,
        ]);

        Institution::create([
            'name' => 'PTT POSTA KARGO',
            'code' => 'PTT-POSTA-KARGO',
            'parent_id' => $eAvm->id,
        ]);
    }
}
