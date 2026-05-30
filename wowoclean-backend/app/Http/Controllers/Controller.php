<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    description: "Enterprise REST API WowoClean (UAP TIS TI-C)",
    title: "WowoClean API Documentation"
)]
#[OA\Server(
    url: "http://127.0.0.1:8000",
    description: "API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
abstract class Controller
{
}