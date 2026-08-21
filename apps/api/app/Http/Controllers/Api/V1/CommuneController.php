<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Commune;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommuneController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->filled('wilaya_code')) {
            throw ApiException::businessRule('wilaya_code_required', 'A wilaya_code query parameter is required.');
        }

        $communes = Commune::query()
            ->where('wilaya_code', $request->string('wilaya_code'))
            ->orderBy('name_ar')
            ->get();

        return ApiResponse::success($communes->map(fn (Commune $commune) => [
            'id' => $commune->id,
            'name' => ['ar' => $commune->name_ar, 'fr' => $commune->name_fr, 'en' => $commune->name_en],
        ]));
    }
}
