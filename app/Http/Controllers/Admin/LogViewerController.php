<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogViewerController extends Controller
{
    /**
     * Display a listing of log files.
     */
    public function index()
    {
        $logPath = storage_path('logs');
        $files = glob($logPath . '/*.log');
        $logFiles = [];

        foreach ($files as $file) {
            $logFiles[] = [
                'filename' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'modified_at' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        // Sort latest modified first
        usort($logFiles, function ($a, $b) {
            return strcmp($b['modified_at'], $a['modified_at']);
        });

        if (request()->wantsJson()) {
            return response()->json($logFiles);
        }

        return Inertia::render('admin/logs/index', [
            'logFiles' => $logFiles,
        ]);
    }

    /**
     * Display content of a log file (tail last 500 lines, filter by level).
     */
    public function show(string $filename, Request $request)
    {
        $filePath = storage_path('logs/' . $filename);
        if (!file_exists($filePath) || !str_ends_with($filename, '.log') || str_contains($filename, '..')) {
            abort(404, 'Log file not found.');
        }

        $level = $request->query('level');
        $linesContent = $this->tailFile($filePath, 500);
        $lines = explode("\n", $linesContent);

        if ($level) {
            $pattern = ".{$level}:";
            $lines = array_filter($lines, function ($line) use ($pattern) {
                return stripos($line, $pattern) !== false;
            });
        }

        $content = implode("\n", $lines);

        if ($request->wantsJson()) {
            return response()->json([
                'filename' => $filename,
                'content' => $content,
            ]);
        }

        return Inertia::render('admin/logs/show', [
            'filename' => $filename,
            'content' => $content,
        ]);
    }

    /**
     * Download the log file.
     */
    public function download(string $filename): BinaryFileResponse
    {
        $filePath = storage_path('logs/' . $filename);
        if (!file_exists($filePath) || !str_ends_with($filename, '.log') || str_contains($filename, '..')) {
            abort(404, 'Log file not found.');
        }

        return response()->download($filePath);
    }

    /**
     * Clear the log file content (empty it). Gated by Super Admin role in routes.
     */
    public function clear(string $filename)
    {
        $filePath = storage_path('logs/' . $filename);
        if (!file_exists($filePath) || !str_ends_with($filename, '.log') || str_contains($filename, '..')) {
            abort(404, 'Log file not found.');
        }

        // Clear contents instead of deleting to preserve permissions
        file_put_contents($filePath, '');

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Log file cleared successfully.',
            ]);
        }

        return back()->with('status', 'Log file cleared successfully.');
    }

    /**
     * Efficiently read the last N lines of a file using fseek.
     */
    private function tailFile(string $filePath, int $lines = 500): string
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return '';
        }

        $linecounter = $lines;
        $pos = -2;
        $beginning = false;
        $text = [];

        while ($linecounter > 0) {
            $t = ' ';
            while ($t !== "\n") {
                if (fseek($handle, $pos, SEEK_END) == -1) {
                    $beginning = true;
                    break;
                }
                $t = fgetc($handle);
                $pos--;
            }

            $linecounter--;
            if ($beginning) {
                rewind($handle);
            }
            $text[] = fgets($handle);
            if ($beginning) {
                break;
            }
        }
        fclose($handle);

        return implode('', array_reverse($text));
    }

    /**
     * Format bytes to human readable form.
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
