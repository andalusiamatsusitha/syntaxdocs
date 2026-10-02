<?php

use Syntax\Core\Http\Request;
use Syntax\Core\Http\Response;
use App\Orbital\OrbitalService;

/** @var \Syntax\Core\Http\Router $router */

// 1. GET /api/satellites - List all satellites in constellation
$router->get('/api/satellites', function (Request $req) {
    $satellites = OrbitalService::getSatellites();

    return Response::json([
        'success' => true,
        'code' => 200,
        'message' => 'Active orbital constellation catalog retrieved successfully',
        'data' => $satellites,
        'meta' => [
            'timestamp' => time(),
            'total' => count($satellites)
        ]
    ]);
});

// 2. GET /api/telemetry/{id} - Live telemetry readouts for a satellite
$router->get('/api/telemetry/{id}', function (Request $req, $id) {
    $telemetry = OrbitalService::getTelemetry((int) $id);

    if (!$telemetry) {
        return Response::json([
            'success' => false,
            'code' => 404,
            'message' => "Satellite #{$id} not found in tracking catalog",
            'data' => null,
            'errors' => ['satellite_id' => 'Not found in orbital database']
        ], 404);
    }

    return Response::json([
        'success' => true,
        'code' => 200,
        'message' => 'Live satellite telemetry retrieved',
        'data' => $telemetry,
        'meta' => [
            'timestamp' => time()
        ]
    ]);
});

// 3. POST /api/commands - Dispatch an uplink command
$router->post('/api/commands', function (Request $req) {
    $satId = (int) $req->input('satellite_id', 1);
    $commandType = (string) $req->input('command_type', 'PING_TRANSPONDER');

    $result = OrbitalService::dispatchCommand($satId, $commandType);

    return Response::json([
        'success' => true,
        'code' => 201,
        'message' => "Command {$commandType} transmitted successfully to satellite #{$satId}",
        'data' => $result,
        'meta' => [
            'timestamp' => time()
        ]
    ], 201);
});
