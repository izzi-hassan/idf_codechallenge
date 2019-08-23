<?php

use Illuminate\Http\Request;
use App\Course;

Route::get('/course/{course}/leaderboard', function(Course $course) {
    return response()->json($course->leaderboard->values());
});