<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Sotrudniki;
use App\Models\OrganizationStructure;

class SyncMilkCodesCommand extends Command
{
    protected $signature = 'sync:milk-codes {--dry : Do not modify DB, just report}';
    protected $description = 'Sync sotrudniki_codes from import_milk_codes by matching tabel_number and organization name_ru';

    public function handle()
    {
        $dry = $this->option('dry');

        $this->info('Starting sync of milk codes...');

        // 1) Get all root structures
        $roots = OrganizationStructure::whereNull('parent_id')->get();
        $totalUpdated = 0;
        foreach ($roots as $root) {
            $this->info("Processing root: {$root->id} - {$root->name_ru}");

            // 2) get all descendant structure ids (including root)
            $structureIds = $this->getDescendantIds($root->id);

            // 2b) select employees in these structures
            $employees = Sotrudniki::whereIn('organization_id', $structureIds)->get();
            $this->info('Found employees: ' . $employees->count());

            foreach ($employees as $emp) {
                $tabel = $emp->tabel_nomer;
                if (empty($tabel)) continue;

                // 3) search in import_milk_codes by tabel_number and psp matching root name_ru (case-insensitive)
                $pspName = $root->name_ru;

                $match = DB::table('import_milk_codes')
                    ->whereRaw('LOWER(tabel_number) = LOWER(?)', [$tabel])
                    ->whereRaw('LOWER(psp) = LOWER(?)', [$pspName])
                    ->first();

                if ($match) {
                    // delete existing in sotrudniki_codes for this sotrudniki (if any)
                    $existing = DB::table('sotrudniki_codes')->where('sotrudnik_id', $emp->id)->first();

                    if ($existing) {
                        $this->info("Will delete existing code for sotrudnik {$emp->id}");
                        if (!$dry) DB::table('sotrudniki_codes')->where('sotrudnik_id', $emp->id)->delete();
                    }

                    $insertData = [
                        'sotrudnik_id' => $emp->id,
                        'type' => 'milk',
                        'code' => $match->qr,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $this->info("Will insert code for sotrudnik {$emp->id}: {$match->qr}");
                    if (!$dry) DB::table('sotrudniki_codes')->insert($insertData);
                    if (!$dry) DB::table('import_milk_codes')->where('id', $match->id)->delete();
                    $totalUpdated++;
                }
            }
        }

        $this->info("Done. Total updated: $totalUpdated");

        return 0;
    }

    protected function getDescendantIds($rootId)
    {
        // BFS traversal to collect descendants to avoid recursive SQL (works with moderate tree sizes)
        $ids = [$rootId];
        $queue = [$rootId];
        while (!empty($queue)) {
            $current = array_shift($queue);
            $children = OrganizationStructure::where('parent_id', $current)->pluck('id')->toArray();
            foreach ($children as $c) {
                $ids[] = $c;
                $queue[] = $c;
            }
        }
        return $ids;
    }
}

