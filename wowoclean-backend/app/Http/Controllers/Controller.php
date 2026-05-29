<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="WowoClean API Documentation",
 * description="Enterprise REST API WowoClean (UAP TIS TI-C)"
 * )
 * @OA\Server(
 * url="http://127.0.0.1:8000",
 * description="API Server"
 * )
 * @OA\SecurityScheme(
 * securityScheme="bearerAuth",
 * type="http",
 * scheme="bearer",
 * bearerFormat="JWT"
 * )
 */
abstract class Controller
{
}