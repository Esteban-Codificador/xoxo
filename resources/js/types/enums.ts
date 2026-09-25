// GENERADO por `php artisan types:enums` a partir de app/Enums. No editar a mano.

export const ActivityType = {
    LessonStarted: 'LESSON_STARTED',
    LessonCompleted: 'LESSON_COMPLETED',
    LessonMastered: 'LESSON_MASTERED',
    ExerciseSolved: 'EXERCISE_SOLVED',
    QuizPassed: 'QUIZ_PASSED',
    QuizFailed: 'QUIZ_FAILED',
    LabCompleted: 'LAB_COMPLETED',
    MilestoneCompleted: 'MILESTONE_COMPLETED',
    ProjectCompleted: 'PROJECT_COMPLETED',
    SkillMastered: 'SKILL_MASTERED',
    TrackCompleted: 'TRACK_COMPLETED',
    BadgeEarned: 'BADGE_EARNED',
    CertificateIssued: 'CERTIFICATE_ISSUED',
} as const;

export type ActivityType = (typeof ActivityType)[keyof typeof ActivityType];

export const AuditAction = {
    Created: 'CREATED',
    Updated: 'UPDATED',
    Deleted: 'DELETED',
    Submitted: 'SUBMITTED',
    Published: 'PUBLISHED',
    Unpublished: 'UNPUBLISHED',
    Archived: 'ARCHIVED',
    Restored: 'RESTORED',
    RoleAssigned: 'ROLE_ASSIGNED',
    RoleRevoked: 'ROLE_REVOKED',
    Imported: 'IMPORTED',
} as const;

export type AuditAction = (typeof AuditAction)[keyof typeof AuditAction];

export const ContentStatus = {
    Draft: 'DRAFT',
    Review: 'REVIEW',
    Published: 'PUBLISHED',
    Archived: 'ARCHIVED',
} as const;

export type ContentStatus = (typeof ContentStatus)[keyof typeof ContentStatus];

export const ContentType = {
    Concept: 'CONCEPT',
    Tutorial: 'TUTORIAL',
    Exercise: 'EXERCISE',
    Lab: 'LAB',
    Project: 'PROJECT',
    Quiz: 'QUIZ',
    Reading: 'READING',
    Video: 'VIDEO',
    Documentation: 'DOCUMENTATION',
    Challenge: 'CHALLENGE',
} as const;

export type ContentType = (typeof ContentType)[keyof typeof ContentType];

export const DependencyKind = {
    Required: 'REQUIRED',
    Recommended: 'RECOMMENDED',
} as const;

export type DependencyKind =
    (typeof DependencyKind)[keyof typeof DependencyKind];

export const Difficulty = {
    Beginner: 'BEGINNER',
    Intermediate: 'INTERMEDIATE',
    Advanced: 'ADVANCED',
    Expert: 'EXPERT',
} as const;

export type Difficulty = (typeof Difficulty)[keyof typeof Difficulty];

export const LinkStatus = {
    Unchecked: 'UNCHECKED',
    Ok: 'OK',
    Redirected: 'REDIRECTED',
    Broken: 'BROKEN',
} as const;

export type LinkStatus = (typeof LinkStatus)[keyof typeof LinkStatus];

export const Permission = {
    AdminAccess: 'admin.access',
    ContentViewAny: 'content.view_any',
    ContentCreate: 'content.create',
    ContentUpdateAny: 'content.update_any',
    ContentUpdateOwn: 'content.update_own',
    ContentSubmitReview: 'content.submit_review',
    ContentPublish: 'content.publish',
    ContentArchive: 'content.archive',
    ContentDelete: 'content.delete',
    UsersView: 'users.view',
    UsersManage: 'users.manage',
    RolesAssign: 'roles.assign',
    AuditView: 'audit.view',
    AnalyticsView: 'analytics.view',
} as const;

export type Permission = (typeof Permission)[keyof typeof Permission];

export const ProfileVisibility = {
    Private: 'PRIVATE',
    Public: 'PUBLIC',
} as const;

export type ProfileVisibility =
    (typeof ProfileVisibility)[keyof typeof ProfileVisibility];

export const ProgressStatus = {
    InProgress: 'IN_PROGRESS',
    Completed: 'COMPLETED',
    Mastered: 'MASTERED',
} as const;

export type ProgressStatus =
    (typeof ProgressStatus)[keyof typeof ProgressStatus];

export const ResourceType = {
    Documentation: 'DOCUMENTATION',
    Article: 'ARTICLE',
    Tutorial: 'TUTORIAL',
    Course: 'COURSE',
    Book: 'BOOK',
    Paper: 'PAPER',
    Repository: 'REPOSITORY',
    Tool: 'TOOL',
} as const;

export type ResourceType = (typeof ResourceType)[keyof typeof ResourceType];

export const Role = {
    Admin: 'ADMIN',
    Editor: 'EDITOR',
    Instructor: 'INSTRUCTOR',
    Student: 'STUDENT',
} as const;

export type Role = (typeof Role)[keyof typeof Role];

export const UnlockPolicy = {
    Advisory: 'ADVISORY',
    Strict: 'STRICT',
} as const;

export type UnlockPolicy = (typeof UnlockPolicy)[keyof typeof UnlockPolicy];
