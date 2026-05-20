<?php

namespace App\Http\Controllers\Api;

use App\Enums\RewardType;
use App\Exceptions\ApiResponseException;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponseClass;
use App\Interfaces\RewardInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;

class RewardController extends Controller
{
    protected $service;

    public function __construct(RewardInterface $service)
    {
        $this->service = $service;
    }

    public function list(Request $request): JsonResponse
    {
        return ApiResponseClass::sendResponse($this->service->list($request->all()), '');
    }

    public function types(): JsonResponse
    {
        return ApiResponseClass::sendResponse($this->service->types(), '');
    }

    public function insert(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', new Enum(RewardType::class)],
        ]);

        DB::beginTransaction();
        try {
            $reward = $this->service->insert($request->all());

            DB::commit();
            return ApiResponseClass::sendResponse($reward, 'The reward was created successfully', 201);
        } catch (\Exception $ex) {
            ApiResponseClass::rollback($ex);
            return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
        }
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', new Enum(RewardType::class)],
        ]);

        DB::beginTransaction();
        try {
            $reward = $this->service->update($request->all());

            DB::commit();
            return ApiResponseClass::sendResponse($reward, 'The reward was updated successfully');
        } catch (\Exception $ex) {
            ApiResponseClass::rollback($ex);
            return ApiResponseClass::sendResponse(['error' => $ex->getMessage()], 'Error');
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            return ApiResponseClass::sendResponse($this->service->delete($request->id), 'The reward was deleted successfully');
        } catch (ApiResponseException $exception) {
            return ApiResponseClass::sendError(['error' => $exception->getMessage()], 'Error');
        }
    }
}
