<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Wilaya;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class WilayaController extends Controller
{
    public function index(): JsonResponse
    {
        $wilayas = Wilaya::query()->orderBy('code')->get();

        return ApiResponse::success($wilayas->map(fn (Wilaya $wilaya) => [
            'code' => $wilaya->code,
            'name' => ['ar' => $wilaya->name_ar, 'fr' => $wilaya->name_fr, 'en' => $wilaya->name_en],
        ]));
    }
}
