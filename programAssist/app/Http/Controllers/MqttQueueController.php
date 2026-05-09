<?php

namespace App\Http\Controllers;

use App\Models\MQTT\HardwareEsp;
use App\Models\MQTT\PublishMqtt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        Log::info('MQTT publish message queued.', [
            'publish_mqtt_id' => $queue->id,
            'id_esp' => $hardware->id_esp,
            'topic' => $hardware->topic_publish,
        ]);

        return response()->json([
            'status' => 'queued',
            'id' => $queue->id,
        ]);
    }
}