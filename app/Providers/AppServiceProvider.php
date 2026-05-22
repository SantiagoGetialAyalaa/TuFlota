<?php

namespace App\Providers;

use App\Domain\Repositories\DriverRepositoryInterface;
use App\Domain\Repositories\QueueRepositoryInterface;
use App\Domain\Repositories\ReservationRepositoryInterface;
use App\Domain\Repositories\SeatRepositoryInterface;
use App\Domain\Repositories\TripRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\AuthTokenServiceInterface;
use App\Domain\Services\PasswordHasherInterface;
use App\Domain\Services\PaymentGatewayInterface;
use App\Infrastructure\Persistence\Repositories\DriverRepository;
use App\Infrastructure\Persistence\Repositories\QueueRepository;
use App\Infrastructure\Persistence\Repositories\ReservationRepository;
use App\Infrastructure\Persistence\Repositories\SeatRepository;
use App\Infrastructure\Persistence\Repositories\TripRepository;
use App\Infrastructure\Persistence\Repositories\UserRepository;
use App\Infrastructure\Services\BcryptPasswordHasher;
use App\Infrastructure\Services\JwtAuthService;
use App\Infrastructure\Services\MockPaymentGatewayService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TripRepositoryInterface::class, TripRepository::class);
        $this->app->bind(ReservationRepositoryInterface::class, ReservationRepository::class);
        $this->app->bind(SeatRepositoryInterface::class, SeatRepository::class);
        $this->app->bind(DriverRepositoryInterface::class, DriverRepository::class);
        $this->app->bind(QueueRepositoryInterface::class, QueueRepository::class);
        $this->app->bind(AuthTokenServiceInterface::class, JwtAuthService::class);
        $this->app->bind(PasswordHasherInterface::class, BcryptPasswordHasher::class);
        $this->app->bind(PaymentGatewayInterface::class, MockPaymentGatewayService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
