<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculateOrderRequest;
use App\Http\Services\EurekaService;
use Illuminate\Support\Facades\Storage;

class ChocoBillyController extends Controller
{

    public function __construct(
        protected EurekaService $eurekaService,
    )
    {}

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
}
