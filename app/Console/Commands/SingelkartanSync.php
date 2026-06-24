<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RegionLevel;
use App\Enums\Sex;
use App\Models\Demographic;
use App\Models\Region;
use App\Services\ScbClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;

#[Signature('singelkartan:sync {--dry-run : Fetch and parse but do not write to database}')]
#[Description('Sync demographic data from SCB PxWebApi 2 into the demographics table.')]
class SingelkartanSync extends Command
{
    /** All kommuner in Sweden (list of SCB region codes). */
    private const array REGION_CODES = [
        '0114', '0115', '0117', '0120', '0123', '0125', '0126', '0127', '0128', '0136',
        '0138', '0139', '0140', '0160', '0162', '0163', '0180', '0181', '0182', '0183',
        '0184', '0186', '0187', '0188', '0191', '0192', '0305', '0319', '0330', '0331',
        '0360', '0380', '0381', '0382', '0428', '0461', '0480', '0481', '0482', '0483',
        '0484', '0486', '0488', '0509', '0512', '0513', '0560', '0561', '0562', '0563',
        '0580', '0581', '0582', '0583', '0584', '0586', '0604', '0617', '0642', '0643',
        '0662', '0665', '0680', '0682', '0683', '0684', '0685', '0686', '0687', '0760',
        '0761', '0763', '0764', '0765', '0767', '0780', '0781', '0821', '0834', '0840',
        '0860', '0861', '0862', '0880', '0881', '0882', '0883', '0884', '0885', '0980',
        '1060', '1080', '1081', '1082', '1083', '1084', '1085', '1160', '1180', '1181',
        '1182', '1183', '1184', '1185', '1186', '1187', '1188', '1191', '1192', '1193',
        '1214', '1230', '1231', '1233', '1256', '1257', '1260', '1261', '1262', '1263',
        '1264', '1265', '1266', '1267', '1270', '1272', '1273', '1275', '1276', '1277',
        '1278', '1280', '1281', '1282', '1283', '1284', '1285', '1286', '1287', '1290',
        '1291', '1292', '1293', '1315', '1380', '1381', '1382', '1383', '1384', '1401',
        '1402', '1407', '1415', '1419', '1421', '1427', '1430', '1435', '1438', '1439',
        '1440', '1441', '1442', '1443', '1444', '1445', '1446', '1447', '1452', '1460',
        '1461', '1462', '1463', '1465', '1466', '1470', '1471', '1472', '1473', '1480',
        '1481', '1482', '1484', '1485', '1486', '1487', '1488', '1489', '1490', '1491',
        '1492', '1493', '1494', '1495', '1496', '1497', '1498', '1499', '1504', '1506',
        '1507', '1508', '1509', '1510', '1512', '1513', '1514', '1515', '1516', '1517',
        '1560', '1561', '1562', '1563', '1564', '1565', '1566', '1567', '1580', '1581',
        '1582', '1583', '1584', '1585', '1586', '1587', '1590', '1591', '1592', '1593',
        '1594', '1680', '1681', '1682', '1683', '1684', '1685', '1686', '1687', '1688',
        '1689', '1690', '1737', '1760', '1761', '1762', '1763', '1764', '1765', '1766',
        '1780', '1781', '1782', '1783', '1784', '1785', '1814', '1860', '1861', '1862',
        '1863', '1864', '1880', '1881', '1882', '1883', '1884', '1885', '1904', '1907',
        '1960', '1961', '1962', '1980', '1981', '1982', '1983', '1984', '2021', '2023',
        '2026', '2029', '2031', '2034', '2039', '2061', '2062', '2080', '2081', '2082',
        '2083', '2084', '2085', '2101', '2104', '2121', '2132', '2161', '2180', '2181',
        '2182', '2183', '2184', '2260', '2262', '2280', '2281', '2282', '2283', '2284',
        '2303', '2305', '2309', '2313', '2321', '2326', '2361', '2380', '2401', '2403',
        '2404', '2409', '2417', '2418', '2421', '2422', '2425', '2460', '2462', '2463',
        '2480', '2481', '2482', '2505', '2506', '2510', '2513', '2514', '2518', '2521',
        '2523', '2560', '2580', '2581', '2582', '2583', '2584',
    ];

    /** Standard 5-year age groups used in SCB HushallT08CKM. */
    private const array AGE_CODES = [
        '20-24', '25-29', '30-34', '35-39', '40-44',
        '45-49', '50-54', '55-59', '60-64', '65-69',
    ];

    public function handle(ScbClient $client): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $year = (int) config('singelkartan.sync_year');

        $this->info("Syncing SCB data for year {$year}".($isDryRun ? ' (dry run)' : '').'...');

        try {
            $this->syncHouseholdData($client, $isDryRun);
        } catch (ConnectionException $e) {
            $this->error('SCB API connection failed: '.$e->getMessage());

            return Command::FAILURE;
        } catch (RequestException $e) {
            $this->error('SCB API request failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Sync complete.');

        return Command::SUCCESS;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    private function syncHouseholdData(ScbClient $client, bool $isDryRun): void
    {
        $this->info('Fetching household data from SCB...');

        $observations = $client->fetchHouseholdData(
            regionCodes: self::REGION_CODES,
            ageCodes: self::AGE_CODES,
            sexes: [Sex::Male, Sex::Female],
        );

        $this->info("Received {$observations->count()} observations.");

        if ($isDryRun) {
            return;
        }

        $this->upsertRegions();

        $regionMap = Region::where('level', RegionLevel::Kommun->value)
            ->pluck('id', 'code');

        $rows = $observations
            ->filter(fn ($obs): bool => $regionMap->has($obs->regionCode))
            ->map(fn ($obs): array => [
                'region_id' => $regionMap->get($obs->regionCode),
                'year' => $obs->year,
                'sex' => $obs->sex->value,
                'age_min' => $obs->ageMin,
                'age_max' => $obs->ageMax,
                'household_status' => $obs->householdStatus->value,
                'count' => $obs->count,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ])
            ->values()
            ->all();

        $this->info('Upserting '.count($rows).' demographic rows...');

        foreach (array_chunk($rows, 500) as $chunk) {
            Demographic::upsert(
                $chunk,
                uniqueBy: ['region_id', 'year', 'sex', 'age_min', 'age_max', 'household_status'],
                update: ['count', 'updated_at'],
            );
        }
    }

    private function upsertRegions(): void
    {
        $regionNames = $this->defaultRegionNames();

        $rows = array_map(fn (string $code): array => [
            'code' => $code,
            'name' => $regionNames[$code] ?? "Kommun {$code}",
            'level' => RegionLevel::Kommun->value,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ], self::REGION_CODES);

        foreach (array_chunk($rows, 100) as $chunk) {
            Region::upsert(
                $chunk,
                uniqueBy: ['code'],
                update: ['name', 'updated_at'],
            );
        }
    }

    /** @return array<string, string> */
    private function defaultRegionNames(): array
    {
        return [
            '0114' => 'Upplands Väsby', '0115' => 'Vallentuna', '0117' => 'Österåker',
            '0120' => 'Värmdö', '0123' => 'Järfälla', '0125' => 'Ekerö',
            '0126' => 'Huddinge', '0127' => 'Botkyrka', '0128' => 'Salem',
            '0136' => 'Haninge', '0138' => 'Tyresö', '0139' => 'Upplands-Bro',
            '0140' => 'Nykvarn', '0160' => 'Täby', '0162' => 'Danderyd',
            '0163' => 'Sollentuna', '0180' => 'Stockholm', '0181' => 'Södertälje',
            '0182' => 'Nacka', '0183' => 'Sundbyberg', '0184' => 'Solna',
            '0186' => 'Lidingö', '0187' => 'Vaxholm', '0188' => 'Norrtälje',
            '0191' => 'Sigtuna', '0192' => 'Nynäshamn',
        ];
    }
}
