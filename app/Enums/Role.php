<?php

namespace App\Enums;

enum Role: string
{
    use HasValues;

    case Admin = 'ADMIN';
    case Editor = 'EDITOR';
    case Instructor = 'INSTRUCTOR';
    case Student = 'STUDENT';

    /**
     * The role that decides what a user can do when they hold several
     * (the CMS assigns one; older data may have more).
     *
     * @param  iterable<string>  $names
     */
    public static function primaryOf(iterable $names): ?self
    {
        $held = [];

        foreach ($names as $name) {
            $held[] = self::tryFrom($name);
        }

        foreach (self::cases() as $role) {
            if (in_array($role, $held, true)) {
                return $role;
            }
        }

        return null;
    }

    /** Anything above STUDENT opens the CMS. */
    public function isStaff(): bool
    {
        return $this !== self::Student;
    }
}
