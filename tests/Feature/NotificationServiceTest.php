<?php

namespace Tests\Feature;

use App\Services\Notifications\Exceptions\AllProvidersFailedException;
use App\Services\Notifications\Exceptions\NotificationSendingException;
use App\Services\Notifications\Interfaces\NotificationManagerInterface;
use App\Services\Notifications\Interfaces\NotificationStrategyInterface;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('notifications.sms.failover_order', ['faraz_sms', 'sms_ir']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sms_is_sent_with_primary_provider_successfully()
    {
        // Mock the primary provider strategy (FarazSms)
        $farazSmsMock = Mockery::mock(NotificationStrategyInterface::class);
        $farazSmsMock->shouldReceive('send')->once()->with('123', 'Test SMS');

        // Mock the secondary provider (SmsIr), which should NOT be called
        $smsIrMock = Mockery::mock(NotificationStrategyInterface::class);
        $smsIrMock->shouldNotReceive('send');

        // Bind our mocks to the container
        $this->app->instance('sms.provider.faraz_sms', $farazSmsMock);
        $this->app->instance('sms.provider.sms_ir', $smsIrMock);

        // Resolve the manager and send the notification
        $manager = $this->app->make(NotificationManagerInterface::class);
        $manager->via('sms')->to('123')->send('Test SMS');

        // **THE FIX**: Add an assertion to satisfy PHPUnit.
        // This confirms the test ran to completion, which means the Mockery
        // expectations above were met without any exceptions being thrown.
        $this->assertTrue(true);
    }

    public function test_sms_failover_to_secondary_provider_works()
    {
        // Mock the primary provider (FarazSms) to fail
        $farazSmsMock = Mockery::mock(NotificationStrategyInterface::class);
        $farazSmsMock->shouldReceive('send')
            ->once()
            ->with('123', 'Test SMS')
            ->andThrow(new NotificationSendingException('FarazSms failed'));

        // Mock the secondary provider (SmsIr) to succeed
        $smsIrMock = Mockery::mock(NotificationStrategyInterface::class);
        $smsIrMock->shouldReceive('send')->once()->with('123', 'Test SMS');

        // Bind mocks
        $this->app->instance('sms.provider.faraz_sms', $farazSmsMock);
        $this->app->instance('sms.provider.sms_ir', $smsIrMock);

        // Resolve and send
        $manager = $this->app->make(NotificationManagerInterface::class);
        $manager->via('sms')->to('123')->send('Test SMS');

        // **THE FIX**: Add an assertion.
        $this->assertTrue(true);
    }

    public function test_it_throws_exception_if_all_providers_fail()
    {
        // This test already has an assertion: expectException(). No change needed.
        $this->expectException(AllProvidersFailedException::class);

        // Mock both providers to fail
        $farazSmsMock = Mockery::mock(NotificationStrategyInterface::class);
        $farazSmsMock->shouldReceive('send')->once()->andThrow(new NotificationSendingException());

        $smsIrMock = Mockery::mock(NotificationStrategyInterface::class);
        $smsIrMock->shouldReceive('send')->once()->andThrow(new NotificationSendingException());

        // Bind mocks
        $this->app->instance('sms.provider.faraz_sms', $farazSmsMock);
        $this->app->instance('sms.provider.sms_ir', $smsIrMock);

        // Resolve and send
        $manager = $this->app->make(NotificationManagerInterface::class);
        $manager->via('sms')->to('123')->send('Test SMS');
    }
}
