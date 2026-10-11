<?php

namespace App\Http\Controllers\Student;

use Anthropic\Core\Exceptions\AnthropicException;
use App\Exceptions\DailyLimitExceededException;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Homework;
use App\Models\Student;
use App\Services\Ai\TutorChatService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AiTutorController extends Controller
{
    /** Most stored messages rendered on the chat page. */
    private const DISPLAY_LIMIT = 200;

    public function __construct(private TutorChatService $chat)
    {
    }

    public function index()
    {
        return view('student.ai-tutor.index', [
            'courses' => $this->coursesForStudent(),
        ]);
    }

    public function show(Course $course)
    {
        $this->authorizeCourse($course);

        $studentId = Auth::user()->student_id;

        return view('student.ai-tutor.chat', [
            'course' => $course,
            'history' => $this->chat->history($studentId, $course->id, self::DISPLAY_LIMIT),
            'remaining' => $this->chat->remainingMessagesToday($studentId),
            'dailyLimit' => (int) config('ai_tutor.daily_message_limit'),
        ]);
    }

    public function send(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourse($course);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $studentId = Auth::user()->student_id;

        try {
            $reply = $this->chat->sendMessage($studentId, $course, $data['message']);
        } catch (DailyLimitExceededException) {
            return response()->json([
                'success' => false,
                'message' => "You've used all of today's tutor messages. Come back tomorrow!",
            ], 429);
        } catch (AnthropicException $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'The tutor is unavailable right now. Please try again in a bit.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'reply' => $reply->content,
            'remaining' => $this->chat->remainingMessagesToday($studentId),
        ]);
    }

    private function studentClassId(): ?int
    {
        return Student::where('id', Auth::user()->student_id)->value('cn_class_id');
    }

    /** Subjects this student has homework in, same rule as the Homework page. */
    private function coursesForStudent(): Collection
    {
        $classId = $this->studentClassId();

        if (! $classId) {
            return collect();
        }

        return Homework::whereHas('studentClasses', $this->releasedToClass($classId))
            ->with('course')
            ->get()
            ->pluck('course')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    private function authorizeCourse(Course $course): void
    {
        $classId = $this->studentClassId();

        abort_unless(
            $classId && Homework::where('course_id', $course->id)
                ->whereHas('studentClasses', $this->releasedToClass($classId))
                ->exists(),
            403
        );
    }

    private function releasedToClass(int $classId): Closure
    {
        return function ($q) use ($classId) {
            $q->where('student_class_id', $classId)
                ->where(function ($r) {
                    $r->whereNull('release_at')->orWhere('release_at', '<=', now());
                });
        };
    }
}
