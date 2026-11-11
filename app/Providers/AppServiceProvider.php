<?php

namespace App\Providers;

use App\Models\BusinessSetting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer(['pages.*', 'auth.*', 'orders.*', 'admin.orders.*'], function (ViewInstance $view): void {
            $businessSettings = BusinessSetting::query()
                ->get()
                ->mapWithKeys(fn ($setting) => [(string) $setting->setting_key => (string) $setting->setting_value]);

            $normalizePhone = function (?string $value): string {
                $digits = preg_replace('/\D+/', '', (string) $value);

                if ($digits === '') {
                    return '';
                }

                if (str_starts_with($digits, '0')) {
                    return '62'.substr($digits, 1);
                }

                if (str_starts_with($digits, '8')) {
                    return '62'.$digits;
                }

                return $digits;
            };

            $whatsappNumber = $normalizePhone($businessSettings->get('whatsapp'));
            $phoneNumber = $normalizePhone($businessSettings->get('phone'));

            $view->with([
                'businessSettings' => $businessSettings,
                'whatsappNumber' => $whatsappNumber,
                'phoneNumber' => $phoneNumber,
                'whatsappUrl' => $whatsappNumber !== '' ? 'https://wa.me/'.$whatsappNumber : null,
            ]);
        });
    }
}
