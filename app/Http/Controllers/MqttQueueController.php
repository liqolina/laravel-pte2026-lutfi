<?php

namespace App\Http\Controllers;

use App\Models\MQTT\HardwareEsp;
use App\Models\MQTT\PublishMqtt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MqttQueueController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_esp' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        $hardware = HardwareEsp::query()
            ->where('id_esp', $data['id_esp'])
            ->firstOrFail();

        $queue = PublishMqtt::create([
            'id_esp' => $hardware->id_esp,
            'topic_publish' => $hardware->topic_publish,
            'message' => $data['message'],
            'timestamp' => now(),
        ]);

        return response()->json([
            'status' => 'queued',
            'id' => $queue->id,
        ]);
    }
}