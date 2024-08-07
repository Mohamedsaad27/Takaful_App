<?php

namespace App\Jobs\Policy;

use App\Models\Company\Policy;
use App\Models\User\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Meneses\LaravelMpdf\Facades\LaravelMpdf as PDF;

class GeneratePolicyTravelInsurancePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $trip;

    /**
     * Create a new job instance.
     *
     * @param Trip $trip
     */
    public function __construct(Trip $trip)
    {
        $this->trip = $trip;  // Corrected assignment
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $trip = Trip::where('id', $this->trip->id)
                ->with(['traveler.user.branche', 'dependents', 'policy.premium'])
                ->first();

            $namePdf = 'TRAVELER-INSURANCE-' . $trip->policy->policy_number . '-' . time() . '.pdf';
            $mainDirectoryPath = 'pdf/policies/traveler-Insurance/';
            $pathPdf = $mainDirectoryPath . $namePdf;

            if (!Storage::disk('public')->exists($mainDirectoryPath)) {
                Storage::disk('public')->makeDirectory($mainDirectoryPath);
            }

            $pdf = PDF::loadView('policy.generatePdf.travelInsurancePolicy', ['trip' => $trip])
                ->save(storage_path('app/public/' . $pathPdf));

            $trip->policy->pdf_path = $pathPdf;
            $trip->policy->save();
        } catch (\Exception $e) {
            \Log::error('PDF Generation failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
