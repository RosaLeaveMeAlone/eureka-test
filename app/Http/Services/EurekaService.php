<?php

namespace App\Http\Services;

class EurekaService
{

    public function findChocoBillyCombination($weights, $remainingWeight, $index, $currentCombination)
    {
        if ($remainingWeight == 0) {
            return $currentCombination;
        }
        
        if ($index >= count($weights)) {
            return null;
        }
    
        $currentWeight = $weights[$index];
        
        $maxCount = intdiv($remainingWeight, $currentWeight);
        
        for ($count = $maxCount; $count >= 0; $count--) {
            $newRemainingWeight = $remainingWeight - ($count * $currentWeight);
            
            if ($newRemainingWeight >= 0) {
                $newCombination = array_merge($currentCombination, array_fill(0, $count, $currentWeight));
                
                $result = $this->findChocoBillyCombination($weights, $newRemainingWeight, $index + 1, $newCombination);
                
                if ($result !== null) {
                    return $result;
                }
            }
        }
        
        return null;
    }

}