<?php

class CreateOrbitalTables
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            // 1. Satellites table
            $pdo->exec("CREATE TABLE IF NOT EXISTS satellites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                norad_id VARCHAR(20) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                orbit_type VARCHAR(20) NOT NULL,
                altitude_km REAL NOT NULL,
                velocity_kms REAL NOT NULL,
                inclination_deg REAL NOT NULL,
                period_min REAL NOT NULL,
                status VARCHAR(30) DEFAULT 'active',
                battery_pct INTEGER DEFAULT 100,
                solar_output_w REAL DEFAULT 1200.0,
                last_contact DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // 2. Telemetry logs table
            $pdo->exec("CREATE TABLE IF NOT EXISTS telemetry_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                satellite_id INTEGER NOT NULL,
                downlink_snr_db REAL NOT NULL,
                payload_temp_c REAL NOT NULL,
                sub_lat REAL NOT NULL,
                sub_lon REAL NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (satellite_id) REFERENCES satellites(id) ON DELETE CASCADE
            )");

            // 3. Uplink commands table
            $pdo->exec("CREATE TABLE IF NOT EXISTS uplink_commands (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                satellite_id INTEGER NOT NULL,
                command_type VARCHAR(50) NOT NULL,
                payload_args TEXT NULL,
                status VARCHAR(30) DEFAULT 'transmitted',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (satellite_id) REFERENCES satellites(id) ON DELETE CASCADE
            )");
        } else {
            // MySQL Schema
            $pdo->exec("CREATE TABLE IF NOT EXISTS satellites (
                id INT AUTO_INCREMENT PRIMARY KEY,
                norad_id VARCHAR(20) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                orbit_type ENUM('LEO', 'MEO', 'GEO', 'SSO') NOT NULL,
                altitude_km FLOAT NOT NULL,
                velocity_kms FLOAT NOT NULL,
                inclination_deg FLOAT NOT NULL,
                period_min FLOAT NOT NULL,
                status ENUM('active', 'standby', 'calibrating', 'decaying') DEFAULT 'active',
                battery_pct INT DEFAULT 100,
                solar_output_w FLOAT DEFAULT 1200.0,
                last_contact TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS telemetry_logs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                satellite_id INT NOT NULL,
                downlink_snr_db FLOAT NOT NULL,
                payload_temp_c FLOAT NOT NULL,
                sub_lat FLOAT NOT NULL,
                sub_lon FLOAT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sat_created (satellite_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS uplink_commands (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                satellite_id INT NOT NULL,
                command_type VARCHAR(50) NOT NULL,
                payload_args TEXT NULL,
                status ENUM('queued', 'transmitted', 'acknowledged', 'failed') DEFAULT 'transmitted',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sat_command (satellite_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Seed initial real-world inspired orbital constellation
        $this->seedInitialSatellites($pdo);
    }

    protected function seedInitialSatellites(PDO $pdo): void
    {
        $stmt = $pdo->query("SELECT COUNT(*) FROM satellites");
        if ($stmt && (int) $stmt->fetchColumn() > 0) {
            return;
        }

        $satellites = [
            [
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

        $insertSql = "INSERT INTO satellites 
            (norad_id, name, orbit_type, altitude_km, velocity_kms, inclination_deg, period_min, status, battery_pct, solar_output_w) 
            VALUES (:norad_id, :name, :orbit_type, :altitude_km, :velocity_kms, :inclination_deg, :period_min, :status, :battery_pct, :solar_output_w)";

        $insertStmt = $pdo->prepare($insertSql);
        foreach ($satellites as $sat) {
            $insertStmt->execute($sat);
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS uplink_commands");
        $pdo->exec("DROP TABLE IF EXISTS telemetry_logs");
        $pdo->exec("DROP TABLE IF EXISTS satellites");
    }
}
