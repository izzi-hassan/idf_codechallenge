<?php
/**
 * @var \App\CourseEnrollment $enrollment
 */
?>
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <h2 class="card-header">Lessons</h2>
                    <div class="card-body">
                        <ol>
                            @foreach($enrollment->course->lessons as $lesson)
                                <li>
                                    <a href="{{ route('lessons.show', ['slug' => $enrollment->course->slug, 'number' => $lesson->number]) }}">
                                        {{ $lesson->title }}
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>

                <div id="vue-course-leaderboard">
                    <course-leaderboard 
                        :course-id="{{ $enrollment->course->id }}"
                        :user='@json($user)'
                    >
                    </course-leaderboard>
                </div>
            </div>
        </div>
    </div>
@endsection
