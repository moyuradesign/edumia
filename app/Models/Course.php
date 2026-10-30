<?php

namespace App\Models;

/** Presentation fixtures only; no user data or course persistence in v0.0.1. */
final class Course
{
    public static function previews(): array
    {
        return [
            ['slug'=>'leadership-foundations','title'=>'Foundations of Leadership','category'=>'Management','level'=>'Beginner','lessons'=>16,'duration'=>'8 hours','icon'=>'✦','tone'=>'violet','summary'=>'Lead teams with clarity, trust, and purpose.','topics'=>['Leadership principles','Communication and feedback','Decision-making','Team development']],
            ['slug'=>'digital-skills','title'=>'Practical Digital Skills','category'=>'Technology','level'=>'Beginner','lessons'=>12,'duration'=>'6 hours','icon'=>'⌘','tone'=>'cyan','summary'=>'Build confidence with modern digital tools.','topics'=>['Digital productivity','Online collaboration','Safe digital practices','Problem solving']],
            ['slug'=>'project-management','title'=>'Project Management Essentials','category'=>'Business','level'=>'Intermediate','lessons'=>14,'duration'=>'7 hours','icon'=>'◇','tone'=>'amber','summary'=>'Plan, manage, and deliver meaningful projects.','topics'=>['Project planning','Scope and schedules','Risk management','Progress reporting']],
            ['slug'=>'effective-communication','title'=>'Effective Communication','category'=>'Personal Growth','level'=>'All levels','lessons'=>10,'duration'=>'5 hours','icon'=>'◎','tone'=>'rose','summary'=>'Communicate thoughtfully across cultures and teams.','topics'=>['Active listening','Clear messaging','Cultural awareness','Conflict resolution']],
        ];
    }

    public static function findPreview(string $slug): ?array
    {
        foreach (self::previews() as $course) if ($course['slug'] === $slug) return $course;
        return null;
    }

    public static function searchPreviews(string $query): array
    {
        $query = trim($query);
        if ($query === '') return self::previews();
        return array_values(array_filter(self::previews(), static fn (array $course): bool =>
            stripos($course['title'].' '.$course['category'].' '.$course['summary'], $query) !== false));
    }
}
