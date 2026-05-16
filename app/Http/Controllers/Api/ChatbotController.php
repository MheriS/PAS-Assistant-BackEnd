<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $message = $request->input('message');
        [$jadwalBase64, $spesialBase64] = $this->buildSchedulePayloads();

        $inferenceUrl = config('chatbot.inference_url');
        if ($inferenceUrl !== '') {
            try {
                $httpResponse = Http::withOptions([
                    'connect_timeout' => config('chatbot.inference_connect_timeout'),
                ])
                    ->timeout((int) config('chatbot.inference_timeout'))
                    ->post($inferenceUrl.'/infer', [
                        'message' => $message,
                        'jadwal_base64' => $jadwalBase64,
                        'jadwal_spesial_base64' => $spesialBase64,
                    ]);

                if ($httpResponse->successful()) {
                    $json = $httpResponse->json();
                    if (is_array($json) && isset($json['response'])) {
                        return response()->json([
                            'response' => $json['response'],
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Lanjut ke fallback shell_exec
            }
        }

        return $this->chatViaShellExec($message, $jadwalBase64, $spesialBase64);
    }

    /**
     * @return array{0: string, 1: string} base64 jadwal rutin dan jadwal spesial
     */
    private function buildSchedulePayloads(): array
    {
        $daysMap = [
            0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu',
        ];

        $allDays = [];
        for ($i = 0; $i <= 6; $i++) {
            $allDays[$i] = [
                'hari' => $daysMap[$i],
                'status' => 'tutup',
                'jam_mulai' => '',
                'jam_selesai' => '',
            ];
        }

        $recurring = \App\Models\RecurringVisitSlot::where('is_active', true)->get();
        foreach ($recurring as $slot) {
            $day = $slot->day_of_week;
            $allDays[$day]['status'] = 'buka';
            $allDays[$day]['jam_mulai'] = substr($slot->start_time, 0, 5);
            $allDays[$day]['jam_selesai'] = substr($slot->end_time, 0, 5);
        }

        $jadwalData = json_encode(array_values($allDays));
        $jadwalBase64 = base64_encode($jadwalData);

        $specialSlots = \App\Models\VisitSlot::where('date', '>=', now()->toDateString())
            ->where('is_available', true)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $spesialData = [];
        foreach ($specialSlots as $slot) {
            $carbonDate = \Carbon\Carbon::parse($slot->date);
            $dayName = $daysMap[$carbonDate->dayOfWeek];
            $spesialData[] = [
                'tanggal' => $carbonDate->format('Y-m-d'),
                'hari' => $dayName,
                'label' => $dayName.', '.$carbonDate->format('j').' '.$carbonDate->translatedFormat('F'),
                'sesi' => $slot->session_name ?? '',
                'jam_mulai' => substr($slot->start_time, 0, 5),
                'jam_selesai' => substr($slot->end_time, 0, 5),
            ];
        }

        $spesialBase64 = base64_encode(json_encode($spesialData));

        return [$jadwalBase64, $spesialBase64];
    }

    private function chatViaShellExec(string $message, string $jadwalBase64, string $spesialBase64)
    {
        $pythonPath = 'C:\\Users\\herib\\AppData\\Local\\Programs\\Python\\Python311\\python.exe';
        $scriptPath = base_path('chatbot-ai/predict.py');

        $escapedPython = escapeshellarg($pythonPath);
        $escapedScript = escapeshellarg($scriptPath);
        $escapedMessage = escapeshellarg($message);
        $escapedJadwalBase64 = escapeshellarg($jadwalBase64);
        $escapedSpesialBase64 = escapeshellarg($spesialBase64);

        $command = "$escapedPython $escapedScript $escapedMessage --jadwal-base64 $escapedJadwalBase64 --jadwal-spesial-base64 $escapedSpesialBase64 2>&1";
        $output = shell_exec($command);

        if ($output && preg_match('/\{.*\}/s', $output, $matches)) {
            $result = json_decode($matches[0], true);
            if ($result && isset($result['response'])) {
                return response()->json([
                    'response' => $result['response'],
                ]);
            }
        }

        return response()->json([
            'response' => 'Maaf, chatbot sedang mengalami gangguan teknis.',
            'debug' => config('app.debug') ? $output : null,
        ], 500);
    }

    public function transcribeAudio(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:webm,wav,mp3,mp4,ogg|max:10000', // max 10MB
        ]);

        $file = $request->file('audio');

        // Save the file temporarily
        $fileName = 'voice_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $directory = storage_path('app/temp_audio');
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $file->move($directory, $fileName);

        $filePath = $directory.'/'.$fileName;

        $pythonPath = 'C:\\Users\\herib\\AppData\\Local\\Programs\\Python\\Python311\\python.exe';
        $scriptPath = base_path('chatbot-ai/transcribe_audio.py');

        $escapedPython = escapeshellarg($pythonPath);
        $escapedScript = escapeshellarg($scriptPath);
        $escapedAudio = escapeshellarg($filePath);

        $command = "$escapedPython $escapedScript $escapedAudio 2>&1";
        $output = shell_exec($command);

        // Delete the temp file to save space
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        if ($output && preg_match('/\{.*\}/s', $output, $matches)) {
            $result = json_decode($matches[0], true);
            if ($result && isset($result['success']) && $result['success'] == true) {
                return response()->json([
                    'text' => $result['text'],
                ]);
            }
        }

        return response()->json([
            'error' => 'Gagal mengenali suara.',
            'debug' => config('app.debug') ? $output : null,
        ], 500);
    }
}
