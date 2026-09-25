<?php

namespace App\Domain\Curriculum\Publishing;

/**
 * Pedagogical contract a lesson must meet before it can be published
 * (docs/content-architecture.md §3).
 */
final class LessonReadiness
{
    public const int MIN_SUMMARY_LENGTH = 80;

    public const int MIN_WHY_IT_MATTERS_LENGTH = 80;

    public const int MIN_BODY_LENGTH = 1_500;

    public const int MIN_OBJECTIVES = 2;

    public const string PRACTICE_HEADING = 'Práctica';

    /**
     * @return list<ReadinessIssue>
     */
    public function check(LessonDraft $draft): array
    {
        $issues = [];

        $summaryLength = mb_strlen(trim($draft->summary));
        if ($summaryLength < self::MIN_SUMMARY_LENGTH) {
            $issues[] = new ReadinessIssue('summary_too_short', ['min' => self::MIN_SUMMARY_LENGTH, 'actual' => $summaryLength]);
        }

        $whyLength = mb_strlen(trim($draft->whyItMatters));
        if ($whyLength < self::MIN_WHY_IT_MATTERS_LENGTH) {
            $issues[] = new ReadinessIssue('why_it_matters_too_short', ['min' => self::MIN_WHY_IT_MATTERS_LENGTH, 'actual' => $whyLength]);
        }

        $objectives = array_filter($draft->objectives, fn (string $objective) => trim($objective) !== '');
        if (count($objectives) < self::MIN_OBJECTIVES) {
            $issues[] = new ReadinessIssue('not_enough_objectives', ['min' => self::MIN_OBJECTIVES, 'actual' => count($objectives)]);
        }

        $bodyLength = mb_strlen($draft->body->plainText());
        if ($bodyLength < self::MIN_BODY_LENGTH) {
            $issues[] = new ReadinessIssue('body_too_short', ['min' => self::MIN_BODY_LENGTH, 'actual' => $bodyLength]);
        }

        if ($draft->skillCount < 1) {
            $issues[] = new ReadinessIssue('missing_skills');
        }

        if ($draft->practiceActivityCount === 0 && ! $this->hasPracticeSection($draft)) {
            $issues[] = new ReadinessIssue('missing_practice', ['heading' => self::PRACTICE_HEADING]);
        }

        if (! $draft->parentsPublished) {
            $issues[] = new ReadinessIssue('parents_not_published');
        }

        return $issues;
    }

    private function hasPracticeSection(LessonDraft $draft): bool
    {
        foreach ($draft->body->headings(2) as $heading) {
            if (mb_strtolower($heading) === mb_strtolower(self::PRACTICE_HEADING)) {
                return true;
            }
        }

        return false;
    }
}
