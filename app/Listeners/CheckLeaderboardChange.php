<?php

namespace App\Listeners;

use App\Events\QuizAnswerEvaluated;
use App\Events\CourseLeaderboardChanged;

class CheckLeaderboardChange
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  QuizAnswerEvaluated  $event
     * @return void
     */
    public function handle(QuizAnswerEvaluated $event)
    {
        $course = $event->quizAnswer->quiz->lesson->course;

        /* Update Leaderboard if a course has just been completed */
        if ($course->isCompletedBy($event->quizAnswer->user)) {
            event(new CourseLeaderboardChanged($course));
        }
    }
}
