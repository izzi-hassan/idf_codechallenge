<?php declare(strict_types=1);

namespace App\Events;

use App\Course;

final class CourseLeaderboardChanged
{
    /** @var Course */
    public $course;

    public function __construct(Course $course)
    {
        $this->course = $course;
    }
}
