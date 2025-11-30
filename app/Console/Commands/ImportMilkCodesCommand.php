<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportMilkCode;
use Illuminate\Support\Facades\DB;

class ImportMilkCodesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:milk-codes {file : Path to CSV file} {--chunk=1000 : Number of rows per insert chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import CSV file into import_milk_codes table in chunks';

    public function handle()
    {
        $path = $this->argument('file');
        $chunkSize = (int) $this->option('chunk');

        if (!file_exists($path)) {
            $this->error("File not found: $path");
            return 1;
        }

        $this->info("Starting import from $path");

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error('Failed to open file');
            return 1;
        }

        // Handle potential BOM on first line
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            $this->error('Empty file');
            fclose($handle);
            return 1;
        }

        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
        $headers = str_getcsv($firstLine);

        // Normalize header names
        $headers = array_map(function ($h) {
            return trim(mb_strtolower($h));
        }, $headers);

        $expected = ['company','psp','tabel_number','full_name','qr'];

        // If header contains only subset, try to map by name; otherwise proceed assuming columns present
        $map = [];
        foreach ($headers as $index => $h) {
            $map[$h] = $index;
        }

        $rows = [];
        $count = 0;
        $inserted = 0;

        while (!feof($handle)) {
            $line = fgets($handle);
            if ($line === false) break;

            // Try to parse CSV line robustly
            $data = str_getcsv($line);

            // If number of columns less than expected and line looks like a broken quoted value, try to merge with next line(s)
            if (count($data) < 4) {
                // Attempt to read until we get matching quotes or enough columns
                $acc = $line;
                $tries = 0;
                while (count($data) < 4 && $tries < 10 && !feof($handle)) {
                    $next = fgets($handle);
                    if ($next === false) break;
                    $acc .= "\n" . $next;
                    $data = str_getcsv($acc);
                    $tries++;
                }
            }

            if (empty($data) || (count($data) === 1 && trim($data[0]) === '')) {
                continue;
            }

            // Map fields by header names if headers present
            $psp = null;
            $tabel = null;
            $full = null;
            $qr = null;

            // If headers include psp/tabel_number/full_name/qr use mapping, else assume order: company,psp,tabel_number,full_name,qr
            if (isset($map['psp']) || isset($map['tabel_number']) || isset($map['full_name']) || isset($map['qr'])) {
                $psp = isset($map['psp']) && isset($data[$map['psp']]) ? $data[$map['psp']] : null;
                $tabel = isset($map['tabel_number']) && isset($data[$map['tabel_number']]) ? $data[$map['tabel_number']] : null;
                $full = isset($map['full_name']) && isset($data[$map['full_name']]) ? $data[$map['full_name']] : null;
                $qr = isset($map['qr']) && isset($data[$map['qr']]) ? $data[$map['qr']] : null;
            } else {
                // Fallback to positional parsing: company,psp,tabel_number,full_name,qr
                $psp = $data[1] ?? null;
                $tabel = $data[2] ?? null;
                $full = $data[3] ?? null;
                $qr = $data[4] ?? null;
            }

            // Trim values
            $psp = $psp !== null ? trim($psp) : null;
            $tabel = $tabel !== null ? trim($tabel) : null;
            $full = $full !== null ? trim($full) : null;
            $qr = $qr !== null ? trim($qr) : null;

            $rows[] = [
                'psp' => $psp,
                'tabel_number' => $tabel,
                'full_name' => $full,
                'qr' => $qr,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $count++;

            if (count($rows) >= $chunkSize) {
                DB::table('import_milk_codes')->insert($rows);
                $inserted += count($rows);
                $this->info("Inserted $inserted rows...");
                $rows = [];
            }
        }

        if (count($rows) > 0) {
            DB::table('import_milk_codes')->insert($rows);
            $inserted += count($rows);
        }

        fclose($handle);

        $this->info("Done. Total rows processed: $count. Inserted: $inserted");
        return 0;
    }
}

