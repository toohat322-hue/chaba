<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->get();

        return ApiResponse::success(AddressResource::collection($addresses));
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = DB::transaction(function () use ($request) {
            $user = $request->user();
            $isFirstAddress = $user->addresses()->doesntExist();
            $data = $request->validated();
            $data['is_default'] ??= false;

            // A customer only ever has one default address: their very
            // first address becomes it automatically, and any later address
            // explicitly marked default displaces whichever one held it.
            if ($isFirstAddress || ($data['is_default'] ?? false)) {
                $user->addresses()->update(['is_default' => false]);
                $data['is_default'] = true;
            }

            return $user->addresses()->create($data);
        });

        return ApiResponse::success(new AddressResource($address), 201);
    }

    public function update(UpdateAddressRequest $request, string $address): JsonResponse
    {
        $model = $request->user()->addresses()->find($address);

        if (! $model) {
            throw new ApiException('not_found', 'Address not found.', 404);
        }

        DB::transaction(function () use ($request, $model) {
            $data = $request->validated();

            if ($data['is_default'] ?? false) {
                $request->user()->addresses()->where('id', '!=', $model->id)->update(['is_default' => false]);
            }

            $model->fill($data)->save();
        });

        return ApiResponse::success(new AddressResource($model->fresh()));
    }

    public function destroy(Request $request, string $address): JsonResponse
    {
        $model = $request->user()->addresses()->find($address);

        if (! $model) {
            throw new ApiException('not_found', 'Address not found.', 404);
        }

        DB::transaction(function () use ($request, $model) {
            $wasDefault = $model->is_default;
            $model->delete();

            if ($wasDefault) {
                $request->user()->addresses()->orderByDesc('created_at')->first()?->update(['is_default' => true]);
            }
        });

        return ApiResponse::success(['message' => 'Address deleted.']);
    }
}
