<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserJourneyHeatPoint;
use App\Models\UserJourneyLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeedUserJourneyDemo extends Command
{
    protected $signature = 'journey:seed-demo {--days=28} {--sessions=90}';

    protected $description = 'Replace user journey logs with dummy sessions for maps, funnels, heat, and visit pages';

    public function handle(): int
    {
        $days = max(7, (int) $this->option('days'));
        $sessionCount = max(20, (int) $this->option('sessions'));
        $users = User::query()->orderBy('id')->limit(20)->get(['id', 'name', 'email']);
        if ($users->isEmpty()) {
            $this->error('No users found.');

            return 1;
        }

        UserJourneyLog::query()->delete();
        UserJourneyHeatPoint::query()->delete();

        $places = [
            ['AE', 'Dubai', 'Dubai', 25.2048, 55.2708, '203.0.113.', '2001:db8:ae:1::'],
            ['AE', 'Abu Dhabi', 'Abu Dhabi', 24.4539, 54.3773, '203.0.113.', '2001:db8:ae:2::'],
            ['GB', 'England', 'London', 51.5074, -0.1278, '198.51.100.', '2001:db8:gb:1::'],
            ['US', 'New York', 'New York', 40.7128, -74.0060, '192.0.2.', '2001:db8:us:1::'],
            ['SG', 'Singapore', 'Singapore', 1.3521, 103.8198, '203.0.113.', '2001:db8:sg:1::'],
            ['DE', 'Berlin', 'Berlin', 52.5200, 13.4050, '198.51.100.', '2001:db8:de:1::'],
            ['IN', 'Maharashtra', 'Mumbai', 19.0760, 72.8777, '192.0.2.', '2001:db8:in:1::'],
            ['PK', 'Sindh', 'Karachi', 24.8607, 67.0011, '203.0.113.', '2001:db8:pk:1::'],
            ['FR', 'Île-de-France', 'Paris', 48.8566, 2.3522, '198.51.100.', '2001:db8:fr:1::'],
            ['CH', 'Zurich', 'Zurich', 47.3769, 8.5417, '192.0.2.', '2001:db8:ch:1::'],
        ];
        $journeys = [
            ['/dashboard/admin', '/client-management/clients', '/client-management/clients/edit/12', '/card-management/cards/list'],
            ['/home', '/dashboard/admin', '/client-management/clients', '/card-management/cards/list', '/crypto-management/crypto-transactions'],
            ['/dashboard/admin', '/card-management/cards/list', '/crypto-management/crypto-transactions'],
            ['/home', '/client-management/clients', '/settings/modules'],
            ['/dashboard/admin', '/user-trace/user-journey-logs', '/settings/modules'],
            ['/api/client/accounts', '/api/client/cards', '/api/fingate/card-details'],
        ];
        $devices = [
            ['Desktop', 'Chrome', 'Windows', 'Windows PC', 'portal_admin'],
            ['Desktop', 'Safari', 'macOS', 'Mac', 'portal_admin'],
            ['Desktop', 'Edge', 'Windows', 'Windows PC', 'portal_admin'],
            ['Desktop', 'Chrome', 'macOS', 'Mac', 'portal_client'],
            ['Mobile', 'Safari', 'iOS', 'iPhone 17', 'mobile'],
            ['Mobile', 'Chrome', 'Android', 'Google Pixel 8', 'mobile'],
        ];
        $heatZones = [
            [12, 8], [50, 6], [88, 8],
            [8, 28], [8, 48], [8, 68],
            [42, 36], [55, 44], [48, 58], [62, 40],
            [90, 30], [86, 52],
            [20, 92], [50, 90], [78, 92],
        ];

        $hasIpCols = Schema::hasColumn('user_journey_logs', 'ip_v4');
        $hasDeviceName = Schema::hasColumn('user_journey_logs', 'device_name');
        $now = Carbon::now();
        $logs = [];
        $heat = [];
        $logCount = 0;

        for ($s = 0; $s < $sessionCount; $s++) {
            $user = $users[$s % $users->count()];
            $place = $places[$s % count($places)];
            $device = $devices[$s % count($devices)];
            $pathList = $journeys[$s % count($journeys)];
            if ($s % 5 === 0) {
                $pathList = array_slice($pathList, 0, max(2, count($pathList) - 1));
            }
            $session = 'demo-' . Str::lower(Str::random(12));
            $host = rand(10, 240);
            $ipv4 = $place[5] . $host;
            $ipv6 = $place[6] . dechex($host);
            $vpn = ($s % 3 === 0) ? '198.51.100.' . rand(20, 200) : null;
            $started = $now->copy()->subMinutes(rand(15, $days * 24 * 60));
            $cursor = $started->copy();

            foreach ($pathList as $step => $path) {
                $row = [
                    'user_id' => $user->id,
                    'session_id' => $session,
                    'route_path' => $path,
                    'method' => $device[4] === 'mobile' ? 'GET' : 'GET',
                    'user_agent' => 'DemoSeed',
                    'ip_address' => $ipv4,
                    'country' => $place[0],
                    'region' => $place[1],
                    'city' => $place[2],
                    'latitude' => $place[3],
                    'longitude' => $place[4],
                    'device_type' => $device[0],
                    'browser' => $device[1],
                    'os' => $device[2],
                    'referrer' => $step === 0 ? null : $pathList[$step - 1],
                    'page_title' => null,
                    'channel' => $device[4],
                    'event_name' => 'page_view',
                    'screen_name' => null,
                    'created_at' => $cursor->toDateTimeString(),
                ];
                if ($hasDeviceName) {
                    $row['device_name'] = $device[3];
                }
                if ($hasIpCols) {
                    $row['ip_v4'] = $ipv4;
                    $row['ip_v6'] = $ipv6;
                    $row['ip_vpn'] = $vpn;
                    $row['ip_forwarded'] = trim($ipv4 . ', ' . $ipv6 . ($vpn ? ', ' . $vpn : ''), ', ');
                }
                $logs[] = $row;
                $logCount++;

                $clicks = $step === 0 ? 3 : rand(1, 4);
                for ($c = 0; $c < $clicks; $c++) {
                    $zone = $heatZones[array_rand($heatZones)];
                    $heat[] = [
                        'user_id' => $user->id,
                        'session_id' => $session,
                        'route_path' => $path,
                        'x' => max(3, min(97, $zone[0] + rand(-4, 4))),
                        'y' => max(3, min(97, $zone[1] + rand(-3, 3))),
                        'channel' => $device[4],
                        'created_at' => $cursor->copy()->addSeconds(rand(5, 40))->toDateTimeString(),
                    ];
                }

                $cursor->addSeconds(rand(25, 180));
            }

            if (count($logs) >= 200) {
                UserJourneyLog::insert($logs);
                $logs = [];
            }
            if (count($heat) >= 200) {
                UserJourneyHeatPoint::insert($heat);
                $heat = [];
            }
        }

        if ($logs) {
            UserJourneyLog::insert($logs);
        }
        if ($heat) {
            UserJourneyHeatPoint::insert($heat);
        }

        $this->info("Cleared old visits. Inserted {$logCount} page views across {$sessionCount} sessions ({$days} days).");

        return 0;
    }
}
