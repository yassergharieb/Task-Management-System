<?php

namespace App\Services;

use App\Models\Project;

class CacheGroups
{
    public static function projectsForUser(int $userId): string
    {
        return "projects:user:{$userId}";
    }

    public static function dashboardForUser(int $userId): string
    {
        return "dashboard:user:{$userId}";
    }

    public static function tasksForProject(int $projectId): string
    {
        return "tasks:project:{$projectId}";
    }

    public static function projectsForProject(Project $project): string
    {
        return self::projectsForUser((int) $project->user_id);
    }
}
