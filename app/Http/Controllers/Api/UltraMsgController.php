<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\UltraMsgService;

class UltraMsgController extends Controller
{
    protected UltraMsgService $ultraMsgService;

    public function __construct()
    {
        $this->ultraMsgService = app(UltraMsgService::class);
    }

    /**
     * Send a text message via UltraMsg API
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'body' => 'required|string|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->ultraMsgService->sendMessage($request->to, $request->body);

        if ($result['status'] === 'success') {
            return response()->json($result);
        } else {
            return response()->json($result, 400);
        }
    }

    /**
     * Send a media message via UltraMsg API
     */
    public function sendMedia(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'media' => 'required|string', // Base64 encoded media
            'filename' => 'required|string',
            'caption' => 'nullable|string|max:1024',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->ultraMsgService->sendMedia(
            $request->to,
            $request->media,
            $request->filename,
            $request->caption
        );

        if ($result['status'] === 'success') {
            return response()->json($result);
        } else {
            return response()->json($result, 400);
        }
    }

    /**
     * Send a document via UltraMsg API
     */
    public function sendDocument(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'document' => 'required|string', // Base64 encoded document
            'filename' => 'required|string',
            'caption' => 'nullable|string|max:1024',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->ultraMsgService->sendDocument(
            $request->to,
            $request->document,
            $request->filename,
            $request->caption
        );

        if ($result['status'] === 'success') {
            return response()->json($result);
        } else {
            return response()->json($result, 400);
        }
    }

    /**
     * Get instance status and information
     */
    public function getInstanceInfo(): JsonResponse
    {
        $result = $this->ultraMsgService->getInstanceInfo();

        if ($result['status'] === 'success') {
            return response()->json($result);
        } else {
            return response()->json($result, 400);
        }
    }

    /**
     * Get chat history
     */
    public function getChatHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->ultraMsgService->getChatHistory(
            $request->to,
            $request->limit ?? 50,
            $request->page ?? 1
        );

        if ($result['status'] === 'success') {
            return response()->json($result);
        } else {
            return response()->json($result, 400);
        }
    }

    /**
     * Send a test message
     */
    public function sendTestMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->ultraMsgService->sendTestMessage($request->to);

        if ($result['status'] === 'success') {
            return response()->json($result);
        } else {
            return response()->json($result, 400);
        }
    }
}
