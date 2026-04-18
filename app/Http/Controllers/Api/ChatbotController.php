<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $message = $request->input('message');
        $pythonPath = 'C:\\Users\\herib\\AppData\\Local\\Programs\\Python\\Python311\\python.exe';
        $scriptPath = base_path('chatbot-ai/predict.py');

        // Menggunakan shell_exec karena Symfony Process bermasalah dengan path di lingkungan Windows herib
        $escapedPython = escapeshellarg($pythonPath);
        $escapedScript = escapeshellarg($scriptPath);
        $escapedMessage = escapeshellarg($message);

        $command = "$escapedPython $escapedScript $escapedMessage 2>&1";
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
            'debug' => config('app.debug') ? $output : null
        ], 500);
    }
}
