<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\TeHelper;
use Carbon\Carbon;

class TeHelperTest extends TestCase
{
    public function testWillExpire()
    {
        $dueTime = Carbon::now()->addMinutes(80);
        $createdAt = Carbon::now();
        $expiryTime = TeHelper::willExpireAt($dueTime, $createdAt);
        $this->assertEquals($dueTime->format('Y-m-d H:i:s'), $expiryTime);
    }

}
