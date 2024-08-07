<?php

namespace App\Observers\Company;

use App\Models\Company\InsuranceType;
use App\Models\Company\Policy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PolicyObServer
{
    /**
     * Handle the policy "created" event.
     */
    public function creating(Policy $policy, $management, $insuranceTypeId): void
    {
        // get branche_number for user will make policy from this branche 
        $brancheNumber = Auth::user()->branche->branche_number;
        $paddingBrancheNumber = str_pad($brancheNumber, 2, '0', STR_PAD_LEFT);

        // get year policy
        $year = Carbon::now()->year;
        $lastTwoDigitYear = substr($year, -2);

        // get month policy
        $month = Carbon::now()->month;
        $paddingMonth = str_pad($month, 2, '0', STR_PAD_LEFT);

        //get insurance_type_number
        $insuranceTypeNumber = InsuranceType::where('id', $insuranceTypeId)->value('insurance_type_number');
        $insuranceTypeNumber = $insuranceTypeNumber ?? $insuranceTypeId;

        /*
         * generate number it consists of four numbers for each branche special-number start from start month
         */
        // => get last policy for this branche
        $lastPolicy = Policy::where('branche_id', '=', $brancheNumber)->whereMonth('created_at', $month)->latest()->first();

        if ($lastPolicy) {
            $numberLastPolicy = $lastPolicy->policy_number;
            $serialNumber = (int) substr($numberLastPolicy, -4) + 1;
            $paddingSerialNumber = str_pad($serialNumber, 4, '0', STR_PAD_LEFT);
        } else {
            $paddingSerialNumber = "0001";
        }

        $policy->policy_number = $paddingBrancheNumber . $lastTwoDigitYear . $paddingMonth . $management . $insuranceTypeNumber . $paddingSerialNumber;
    }


    /**
     * Handle the policy "updated" event.
     */
    public function updated(Policy $policy): void
    {
        //
    }

    /**
     * Handle the policy "deleted" event.
     */
    public function deleted(Policy $policy): void
    {
        //
    }

    /**
     * Handle the policy "restored" event.
     */
    public function restored(Policy $policy): void
    {
        //
    }

    /**
     * Handle the policy "force deleted" event.
     */
    public function forceDeleted(Policy $policy): void
    {
        //
    }
}
