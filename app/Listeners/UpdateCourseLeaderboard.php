<?php declare(strict_types=1);

namespace App\Listeners;

use App\Course;

use App\Events\QuizAnswerEvaluated;
use App\Events\CourseLeaderboardChanged;

final class UpdateCourseLeaderboard
{
    /**
     * Handle the event.
     * Trigger leaderboard recalculation for the course affected
     *
     * @param  CourseLeaderboardChanged  $event
     * @return void
     */
    public function handle(CourseLeaderboardChanged $event)
    {
        $event->course->updateLeaderboard($event->course);
    }
}
