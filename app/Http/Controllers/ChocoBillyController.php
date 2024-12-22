<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculateOrderRequest;
use App\Http\Services\EurekaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChocoBillyController extends Controller
{

    public function __construct(
        protected EurekaService $eurekaService,
    ) {}

    public function calculateOrder(CalculateOrderRequest $request)
    {
        $file = $request->file('file');

        $filePath = $file->getRealPath();

        $handle = fopen($filePath, "r");

        $outputContent = '';

        if ($handle) {
            //*Leer la primera linea que es la cantidad de casos
            $cases = trim(fgets($handle));

            for ($i = 0; $i < $cases; $i++) {
                $birdsAvailables = trim(fgets($handle));
                $order = trim(fgets($handle));
                $weights = array_map('intval', explode(',', $birdsAvailables));
                rsort($weights);

                $result = $this->eurekaService->findChocoBillyCombination($weights, $order, 0, []);

                $birdCount = count($result);
                $birdWeights = $result != null ? implode(',', $result) : 'unprocessable';

                $outputContent .= "{$birdCount}:{$birdWeights}\n";
            }

            fclose($handle);
        } else {
            return response()->json(['error' => 'Error al abrir el archivo.'], 500);
        }

        $fileName = 'output.txt';

        Storage::disk('public')->put($fileName, $outputContent);

        $filePath = Storage::disk('public')->path($fileName);

        return response()->download($filePath);
    }

    public function crc(CalculateOrderRequest $request)
    {
        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);

        $results = [];

        // Limitar lineas del archivo
        $maxLines = 3000; // Coloco 3 mil para que no hayan problemas de timeout
        if (count($lines) > $maxLines) {
            return response()->json([
                'error' => "Este controlador solo acepta un máximo de $maxLines líneas."
            ], 400);
        }

        $i = 0;
        while ($i < count($lines)) {
            list($chocoboName, $numModifications) = explode(" ", $lines[$i]);
            $fileName = storage_path('app/' . $chocoboName . '.txt');

            $outputFileHandle = fopen($fileName, 'w');
            if (!$outputFileHandle) {
                return response()->json(['error' => 'No se pudo abrir el archivo de salida.'], 500);
            }

            fwrite($outputFileHandle, "");

            $results[] = "{$chocoboName} 0: " . hash("crc32b", "");

            $i++;
            for ($j = 0; $j < $numModifications; $j++) {
                list($position, $byte) = explode(" ", $lines[$i]);

                if ($position > 1024 * 16) {
                    fclose($outputFileHandle);
                    unlink($fileName);

                    return response()->json([
                        'error' => "La posición ($position) excede el máximo permitido (" . (1024 * 16) . ") en este controlador."
                    ], 400);
                }

                $currentFileContent = file_get_contents($fileName);

                if ($position > strlen($currentFileContent)) {
                    $currentFileContent = str_pad($currentFileContent, $position, "\0");
                }

                $char = chr($byte);

                $newContent = substr($currentFileContent, 0, $position)
                    . $char
                    . substr($currentFileContent, $position);

                file_put_contents($fileName, $newContent);

                $crc32 = hash("crc32b", $newContent);

                $results[] = "{$chocoboName} " . ($j + 1) . ": {$crc32}";

                $i++;
            }

            fclose($outputFileHandle);

            unlink($fileName);
        }

        $outputFile = tempnam(sys_get_temp_dir(), 'output_results_');
        file_put_contents($outputFile, implode("\n", $results));

        return response()->download($outputFile);
    }
}
