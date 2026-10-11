<?php

namespace App\Services\Ai;

use Anthropic\Client;
use App\Exceptions\DailyLimitExceededException;
use App\Models\Ai\AiChatMessage;
use App\Models\Course;
use Illuminate\Support\Collection;

/**
 * Per-subject AI tutor chat. One continuous thread per student per course.
 *
 * Nothing is written until the model has replied, so a failed API call leaves
 * no half-saved turn behind and does not use up any of the daily allowance.
 */
class TutorChatService
{
    private const EMPTY_REPLY = "I couldn't come up with a reply to that one. Could you try asking it a different way?";

    public function __construct(private Client $client)
    {
    }

    /** The stored thread, oldest first, limited to the most recent rows. */
    public function history(int $studentId, int $courseId, ?int $limit = null): Collection
    {
        $limit ??= (int) config('ai_tutor.history_turns');

        return AiChatMessage::forThread($studentId, $courseId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function remainingMessagesToday(int $studentId): int
    {
        $used = AiChatMessage::where('student_id', $studentId)
            ->where('role', 'user')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        return max(0, (int) config('ai_tutor.daily_message_limit') - $used);
    }

    public function hasReachedDailyLimit(int $studentId): bool
    {
        return $this->remainingMessagesToday($studentId) <= 0;
    }

    /**
     * @throws DailyLimitExceededException
     * @throws \Anthropic\Core\Exceptions\AnthropicException
     */
    public function sendMessage(int $studentId, Course $course, string $userMessage): AiChatMessage
    {
        if ($this->hasReachedDailyLimit($studentId)) {
            throw new DailyLimitExceededException('Daily AI tutor message limit reached.');
        }

        $messages = $this->replayWindow($studentId, $course->id);
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $response = $this->client->messages->create(
            model: (string) config('services.anthropic.model'),
            maxTokens: (int) config('ai_tutor.max_tokens'),
            system: $this->systemPrompt($course),
            messages: $messages,
        );

        $reply = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $reply .= $block->text;
            }
        }
        $reply = trim($reply) !== '' ? trim($reply) : self::EMPTY_REPLY;

        AiChatMessage::create([
            'student_id' => $studentId,
            'course_id' => $course->id,
            'role' => 'user',
            'content' => $userMessage,
            'created_at' => now(),
        ]);

        return AiChatMessage::create([
            'student_id' => $studentId,
            'course_id' => $course->id,
            'role' => 'assistant',
            'content' => $reply,
            'input_tokens' => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
            'created_at' => now(),
        ]);
    }

    /**
     * Recent turns as API messages. The API wants the first message to come
     * from the user, so a window that happens to open on a reply drops it.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function replayWindow(int $studentId, int $courseId): array
    {
        $rows = $this->history($studentId, $courseId)->all();

        while ($rows !== [] && $rows[0]->role === 'assistant') {
            array_shift($rows);
        }

        return array_map(
            fn (AiChatMessage $m) => ['role' => $m->role, 'content' => $m->content],
            $rows
        );
    }

    private function systemPrompt(Course $course): string
    {
        return str_replace(
            ['{course_name}', '{course_level}'],
            [$course->name, $course->level ?: 'all'],
            (string) config('ai_tutor.system_prompt_template')
        );
    }
}
