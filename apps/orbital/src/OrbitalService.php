<?php

namespace App\Orbital;

use PDO;
use Exception;
use Syntax\Core\Database\ConnectionManager;
use Syntax\Core\Support\Env;

class OrbitalService
{
    protected static ?PDO $pdo = null;

    /**
     * Get active database connection with automatic SQLite fallback.
     */
    public static function getPdo(): PDO
    {
        if (static::$pdo !== null) {
            return static::$pdo;
        }

        try {
            // Attempt standard connection from environment (MySQL)
            $pdo = ConnectionManager::connection('default');
            // Test connection
            $pdo->query("SELECT 1");
            static::$pdo = $pdo;
        } catch (\Throwable $e) {
            // Fallback to local SQLite file for standalone execution
            $sqliteDir = dirname(__DIR__) . '/database';
            if (!is_dir($sqliteDir)) {
                mkdir($sqliteDir, 0777, true);
            }
            $sqlitePath = $sqliteDir . '/orbital.sqlite';

            ConnectionManager::register('default', [
                'driver' => 'sqlite',
                'database' => $sqlitePath
            ]);

            $pdo = ConnectionManager::connection('default');
            static::$pdo = $pdo;

            // Run migration if tables do not exist
            require_once dirname(__DIR__) . '/database/migrations/001_create_orbital_tables.php';
            $migration = new \CreateOrbitalTables();
            $migration->up($pdo);
        }

        return static::$pdo;
    }

    /**
     * Get all active satellites.
     */
    public static function getSatellites(): array
    {
        try {
            $pdo = static::getPdo();
            $stmt = $pdo->query("SELECT * FROM satellites ORDER BY altitude_km ASC");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            if (!empty($rows)) {
                return $rows;
            }
        } catch (\Throwable $e) {
            // fallback to default mock array if database error occurs
        }

        return static::getDefaultSatellites();
    }

    /**
     * Get single satellite with simulated live telemetry.
     */
    public static function getTelemetry(int $satelliteId): ?array
    {
        $satellites = static::getSatellites();
        $target = null;
        foreach ($satellites as $sat) {
            if ((int) $sat['id'] === $satelliteId) {
                $target = $sat;
                break;
            }
        }

        if (!$target) {
            return null;
        }

        // Generate realistic space telemetry
        $time = time();
        $orbitFraction = ($time % ((int) $target['period_min'] * 60)) / ($target['period_min'] * 60);
        $subLat = round(sin($orbitFraction * 2 * M_PI) * $target['inclination_deg'], 4);
        $subLon = round((($time * 0.05) % 360) - 180, 4);

        $telemetry = [
            'satellite_id' => $target['id'],
            'name' => $target['name'],
            'norad_id' => $target['norad_id'],
            'orbit_type' => $target['orbit_type'],
            'altitude_km' => (float) $target['altitude_km'],
            'velocity_kms' => (float) $target['velocity_kms'],
            'inclination_deg' => (float) $target['inclination_deg'],
            'period_min' => (float) $target['period_min'],
            'status' => $target['status'],
            'battery_pct' => (int) $target['battery_pct'],
            'solar_output_w' => (float) $target['solar_output_w'],
            'downlink_snr_db' => round(18.5 + sin($time / 10) * 3.2, 2),
            'payload_temp_c' => round(21.4 + cos($time / 15) * 4.1, 1),
            'sub_lat' => $subLat,
            'sub_lon' => $subLon,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
        ];

        // Log to database if connected
        try {
            $pdo = static::getPdo();
            $stmt = $pdo->prepare("INSERT INTO telemetry_logs (satellite_id, downlink_snr_db, payload_temp_c, sub_lat, sub_lon) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $telemetry['satellite_id'],
                $telemetry['downlink_snr_db'],
                $telemetry['payload_temp_c'],
                $telemetry['sub_lat'],
                $telemetry['sub_lon']
            ]);
        } catch (\Throwable $e) {
            // Ignore logging error in fallback mode
        }

        return $telemetry;
    }

    /**
     * Dispatch an uplink ground command to a satellite.
     */
    public static function dispatchCommand(int $satelliteId, string $commandType, array $args = []): array
    {
        $commandId = rand(1000, 9999);
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');

        try {
            $pdo = static::getPdo();
            $stmt = $pdo->prepare("INSERT INTO uplink_commands (satellite_id, command_type, payload_args, status) VALUES (?, ?, ?, 'transmitted')");
            $stmt->execute([$satelliteId, $commandType, json_encode($args)]);
            $commandId = (int) $pdo->lastInsertId() ?: $commandId;
        } catch (\Throwable $e) {
            // Proceed with generated ID if DB is mock
        }

        return [
            'command_id' => $commandId,
            'satellite_id' => $satelliteId,
            'command_type' => $commandType,
            'status' => 'transmitted',
            'uplink_frequency_ghz' => 14.25,
            'latency_ms' => rand(18, 45),
            'transmitted_at' => $timestamp
        ];
    }

    public static function getDefaultSatellites(): array
    {
        return [
            [
                'id' => 1,
                'norad_id' => 'SYN-48201',
                'name' => 'SYNTAX-OBSERVER-01',
                'orbit_type' => 'LEO',
                'altitude_km' => 540.5,
                'velocity_kms' => 7.62,
                'inclination_deg' => 51.6,
                'period_min' => 95.4,
                'status' => 'active',
                'battery_pct' => 98,
                'solar_output_w' => 1480.0
            ],
            [
                'id' => 2,
                'norad_id' => 'SYN-51042',
                'name' => 'NOCTIS-SAR-04',
                'orbit_type' => 'SSO',
                'altitude_km' => 680.0,
                'velocity_kms' => 7.51,
                'inclination_deg' => 98.2,
                'period_min' => 98.2,
                'status' => 'active',
                'battery_pct' => 92,
                'solar_output_w' => 2100.0
            ],
            [
                'id' => 3,
                'norad_id' => 'SYN-33918',
                'name' => 'HELIOS-SOLAR-09',
                'orbit_type' => 'MEO',
                'altitude_km' => 20200.0,
                'velocity_kms' => 3.87,
                'inclination_deg' => 55.0,
                'period_min' => 718.0,
                'status' => 'calibrating',
                'battery_pct' => 88,
                'solar_output_w' => 3200.0
            ],
            [
                'id' => 4,
                'norad_id' => 'SYN-29104',
                'name' => 'AETHER-RELAY-02',
                'orbit_type' => 'GEO',
                'altitude_km' => 35786.0,
                'velocity_kms' => 3.07,
                'inclination_deg' => 0.05,
                'period_min' => 1436.1,
                'status' => 'active',
                'battery_pct' => 100,
                'solar_output_w' => 4500.0
            ]
        ];
    }
}
