<?php

namespace Tests\Unit;

use App\Reminder;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetTitleReturnsNullIfNotDefined()
    {
        $reminder = new Reminder;

        $this->assertNull($reminder->getTitle());
    }

    public function testGetTitleReturnsStringIfDefined()
    {
        $reminder = new Reminder;
        $reminder->title = 'this is a test';

        $this->assertInternalType('string', $reminder->getTitle());
    }

    public function testGetDescriptionReturnsNullIfNotDefined()
    {
        $reminder = new Reminder;

        $this->assertNull($reminder->getDescription());
    }

    public function testGetDescriptionReturnsStringIfDefined()
    {
        $reminder = new Reminder;
        $reminder->description = 'this is a test';

        $this->assertInternalType('string', $reminder->getDescription());
    }

    public function testGetNextExpectedDateReturnsString()
    {
        $reminder = new Reminder;
        $reminder->next_expected_date = '2017-01-01 10:10:10';

        $this->assertEquals(
            '2017-01-01',
            $reminder->getNextExpectedDate()
        );
    }

    public function test_calculate_next_expected_date()
    {
        $timezone = 'US/Eastern';
        $reminder = new Reminder;
        $reminder->next_expected_date = '1980-01-01 10:10:10';
        $reminder->frequency_number = 1;

        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        // from 1980, incrementing one week will lead to Jan 03, 2017
        $reminder->frequency_type = 'week';
        $this->assertEquals(
            '2017-01-03',
            $reminder->calculateNextExpectedDate($timezone)->next_expected_date->toDateString()
        );

        $reminder->frequency_type = 'month';
        $reminder->next_expected_date = '1980-01-01 10:10:10';
        $this->assertEquals(
            '2017-02-01',
            $reminder->calculateNextExpectedDate($timezone)->next_expected_date->toDateString()
        );

        $reminder->frequency_type = 'year';
        $reminder->next_expected_date = '1980-01-01 10:10:10';
        $this->assertEquals(
            '2018-01-01',
            $reminder->calculateNextExpectedDate($timezone)->next_expected_date->toDateString()
        );

        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $reminder->next_expected_date = '2016-12-25 10:10:10';
        $reminder->frequency_type = 'week';
        $this->assertEquals(
            '2017-01-08',
            $reminder->calculateNextExpectedDate($timezone)->next_expected_date->toDateString()
        );

        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $reminder->next_expected_date = '2017-02-02 10:10:10';
        $reminder->frequency_type = 'week';
        $this->assertEquals(
            '2017-02-02',
            $reminder->calculateNextExpectedDate($timezone)->next_expected_date->toDateString()
        );
    }

    public function test_add_birthday_reminder()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $user = factory(\App\User::class)->create([
            'account_id' => $account->id,
        ]);

        $birthdate = '1980-01-01';

        $reminder = Reminder::addBirthdayReminder(
            $contact,
            $birthdate
        );

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'next_expected_date' => '2018-01-01 00:00:00',
            'is_birthday' => 1,
            'contact_id' => $contact->id,
        ]);
    }
}
