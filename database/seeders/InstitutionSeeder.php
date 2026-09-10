<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Seeds three independent institution trees so the app has more than
     * one root to exercise (institution filter cascade, multi-organisation
     * imports), spanning different fleet-owner types: PTT (matches the
     * sample import spreadsheets under docs/), a fictional logistics
     * company (ANKA LOJİSTİK), and a fictional municipality
     * (BAŞKENT BELEDİYESİ).
     *
     * Institutions are the seeder's own reference data (no CRUD elsewhere
     * ever touches them), so each is synced by its unique `code` —
     * updateOrCreate() rather than firstOrCreate() — making this seeder the
     * single source of truth: re-running it against an already-seeded
     * database both fills in anything missing and corrects name/parent_id
     * if they've changed here.
     */
    public function run(): void
    {
        $this->seedPtt();
        $this->seedAnkaLojistik();
        $this->seedBaskentBelediyesi();
    }

    private function seedPtt(): void
    {
        $ptt = Institution::updateOrCreate(['code' => 'PTT'], ['name' => 'PTT']);

        Institution::updateOrCreate(
            ['code' => 'PTT-ANADOLUM'],
            ['name' => 'PTT ANADOLUM', 'parent_id' => $ptt->id],
        );

        $eAvm = Institution::updateOrCreate(
            ['code' => 'PTT-EAVM'],
            ['name' => 'PTT E-AVM', 'parent_id' => $ptt->id],
        );

        Institution::updateOrCreate(
            ['code' => 'PTTEM'],
            ['name' => 'PTTEM', 'parent_id' => $eAvm->id],
        );

        Institution::updateOrCreate(
            ['code' => 'PTT-POSTA-KARGO'],
            ['name' => 'PTT POSTA KARGO', 'parent_id' => $eAvm->id],
        );
    }

    /**
     * ANKA LOJİSTİK
     * ├── ANKA MARMARA BÖLGE MÜDÜRLÜĞÜ
     * │   ├── ANKA İSTANBUL ŞUBE
     * │   └── ANKA KOCAELİ ŞUBE
     * └── ANKA EGE BÖLGE MÜDÜRLÜĞÜ
     *     ├── ANKA İZMİR ŞUBE
     *     └── ANKA MANİSA ŞUBE
     */
    private function seedAnkaLojistik(): void
    {
        $anka = Institution::updateOrCreate(['code' => 'ANKA'], ['name' => 'ANKA LOJİSTİK']);

        $marmara = Institution::updateOrCreate(
            ['code' => 'ANKA-MARMARA'],
            ['name' => 'ANKA MARMARA BÖLGE MÜDÜRLÜĞÜ', 'parent_id' => $anka->id],
        );

        $ege = Institution::updateOrCreate(
            ['code' => 'ANKA-EGE'],
            ['name' => 'ANKA EGE BÖLGE MÜDÜRLÜĞÜ', 'parent_id' => $anka->id],
        );

        Institution::updateOrCreate(
            ['code' => 'ANKA-MARMARA-ISTANBUL'],
            ['name' => 'ANKA İSTANBUL ŞUBE', 'parent_id' => $marmara->id],
        );

        Institution::updateOrCreate(
            ['code' => 'ANKA-MARMARA-KOCAELI'],
            ['name' => 'ANKA KOCAELİ ŞUBE', 'parent_id' => $marmara->id],
        );

        Institution::updateOrCreate(
            ['code' => 'ANKA-EGE-IZMIR'],
            ['name' => 'ANKA İZMİR ŞUBE', 'parent_id' => $ege->id],
        );

        Institution::updateOrCreate(
            ['code' => 'ANKA-EGE-MANISA'],
            ['name' => 'ANKA MANİSA ŞUBE', 'parent_id' => $ege->id],
        );
    }

    /**
     * BAŞKENT BELEDİYESİ
     * ├── FEN İŞLERİ MÜDÜRLÜĞÜ
     * │   ├── ASFALT EKİBİ
     * │   └── PARK VE BAHÇELER EKİBİ
     * └── TEMİZLİK İŞLERİ MÜDÜRLÜĞÜ
     *     ├── ÇÖP TOPLAMA EKİBİ
     *     └── CADDE TEMİZLİK EKİBİ
     */
    private function seedBaskentBelediyesi(): void
    {
        $baskent = Institution::updateOrCreate(['code' => 'BASKENT'], ['name' => 'BAŞKENT BELEDİYESİ']);

        $fen = Institution::updateOrCreate(
            ['code' => 'BASKENT-FEN'],
            ['name' => 'FEN İŞLERİ MÜDÜRLÜĞÜ', 'parent_id' => $baskent->id],
        );

        $temizlik = Institution::updateOrCreate(
            ['code' => 'BASKENT-TEMIZLIK'],
            ['name' => 'TEMİZLİK İŞLERİ MÜDÜRLÜĞÜ', 'parent_id' => $baskent->id],
        );

        Institution::updateOrCreate(
            ['code' => 'BASKENT-FEN-ASFALT'],
            ['name' => 'ASFALT EKİBİ', 'parent_id' => $fen->id],
        );

        Institution::updateOrCreate(
            ['code' => 'BASKENT-FEN-PARK'],
            ['name' => 'PARK VE BAHÇELER EKİBİ', 'parent_id' => $fen->id],
        );

        Institution::updateOrCreate(
            ['code' => 'BASKENT-TEMIZLIK-COP'],
            ['name' => 'ÇÖP TOPLAMA EKİBİ', 'parent_id' => $temizlik->id],
        );

        Institution::updateOrCreate(
            ['code' => 'BASKENT-TEMIZLIK-CADDE'],
            ['name' => 'CADDE TEMİZLİK EKİBİ', 'parent_id' => $temizlik->id],
        );
    }
}
