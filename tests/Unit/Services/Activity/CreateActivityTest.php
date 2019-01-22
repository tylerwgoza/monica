<?php

namespace Tests\Unit\Services\Activity;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Activity;
use App\Models\Instance\Emotion\Emotion;
use App\Models\Contact\ActivityType;
use Illuminate\Validation\ValidationException;
use App\Services\Activity\Activity\CreateActivity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateActivityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_activity()
    {
        $account = factory(Account::class)->create([]);
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'activity_type_id' => $activityType->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'date' => '2009-09-09',
        ];

        $activity = (new CreateActivity)->execute($request);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'account_id' => $account->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'happened_at' => '2009-09-09',
        ]);

        $this->assertInstanceOf(
            Activity::class,
            $activity
        );
    }

    public function test_it_adds_emotions()
    {
        $contact = factory(Contact::class)->create([]);
        $emotion = factory(Emotion::class)->create([]);
        $emotion2 = factory(Emotion::class)->create([]);

        $emotionArray = [];
        array_push($emotionArray, $emotion->id);
        array_push($emotionArray, $emotion2->id);

        $activityType = factory(ActivityType::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $request = [
            'account_id' => $contact->account_id,
            'activity_type_id' => $activityType->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'date' => '2009-09-09',
            'emotions' => $emotionArray,
        ];

        $activity = (new CreateActivity)->execute($request);

        $this->assertDatabaseHas('emotion_activity', [
            'account_id' => $contact->account_id,
            'activity_id' => $activity->id,
            'emotion_id' => $emotion->id,
        ]);

        $this->assertDatabaseHas('emotion_activity', [
            'account_id' => $contact->account_id,
            'activity_id' => $activity->id,
            'emotion_id' => $emotion2->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
        ];

        $this->expectException(ValidationException::class);
        (new CreateActivity)->execute($request);
    }

    public function test_it_throws_an_exception_if_activity_type_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $activityType = factory(ActivityType::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'activity_type_id' => $activityType->id,
            'summary' => 'we went to central perk',
            'description' => 'it was awesome',
            'date' => '2009-09-09',
        ];

        $this->expectException(ModelNotFoundException::class);
        (new CreateActivity)->execute($request);
    }
}