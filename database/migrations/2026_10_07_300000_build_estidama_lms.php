<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estidama LMS: lessons, lesson progress, exam attempts, certificates,
 * program pricing / sequencing flags and badge rules.
 *
 * Additive and idempotent: every column/table is guarded so it can run on
 * databases that already have part of the schema, and existing rows are kept.
 * Legacy program modules that carried their own content are copied into a
 * first lesson so nothing disappears from the learner side.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Programs ──────────────────────────────────────────────
        Schema::table('training_programs', function (Blueprint $table) {
            if (! Schema::hasColumn('training_programs', 'instructor_id')) {
                $table->char('instructor_id', 36)->nullable()->index()->after('training_center_id');
            }
            if (! Schema::hasColumn('training_programs', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('status');
            }
            if (! Schema::hasColumn('training_programs', 'price')) {
                $table->decimal('price', 10, 2)->default(0)->after('is_paid');
            }
            if (! Schema::hasColumn('training_programs', 'sequential')) {
                $table->boolean('sequential')->default(false)->after('price');
            }
            if (! Schema::hasColumn('training_programs', 'requires_application')) {
                $table->boolean('requires_application')->default(true)->after('sequential');
            }
            if (! Schema::hasColumn('training_programs', 'certificate_enabled')) {
                $table->boolean('certificate_enabled')->default(true)->after('requires_application');
            }
            if (! Schema::hasColumn('training_programs', 'estimated_minutes')) {
                $table->unsignedInteger('estimated_minutes')->nullable()->after('certificate_enabled');
            }
            if (! Schema::hasColumn('training_programs', 'level')) {
                $table->string('level', 30)->nullable()->after('estimated_minutes');
            }
            if (! Schema::hasColumn('training_programs', 'completion_badge_id')) {
                $table->unsignedBigInteger('completion_badge_id')->nullable()->after('exam_id');
            }
        });

        // ── Sections (existing program_modules) ───────────────────
        Schema::table('program_modules', function (Blueprint $table) {
            if (! Schema::hasColumn('program_modules', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('order');
            }
            if (! Schema::hasColumn('program_modules', 'unlock_after_days')) {
                $table->unsignedSmallInteger('unlock_after_days')->nullable()->after('is_published');
            }
            if (! Schema::hasColumn('program_modules', 'estimated_minutes')) {
                $table->unsignedInteger('estimated_minutes')->nullable()->after('unlock_after_days');
            }
        });

        // ── Lessons ───────────────────────────────────────────────
        if (! Schema::hasTable('program_lessons')) {
            Schema::create('program_lessons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('training_program_id')->constrained('training_programs')->cascadeOnDelete();
                $table->foreignId('program_module_id')->constrained('program_modules')->cascadeOnDelete();
                $table->string('title');
                $table->text('summary')->nullable();
                $table->string('type', 20)->default('text'); // video | pdf | text | link | quiz
                $table->string('video_provider', 20)->nullable(); // youtube | vimeo | upload
                $table->string('video_url', 500)->nullable();
                $table->string('file_path')->nullable();
                $table->string('captions_path')->nullable();
                $table->longText('body')->nullable();
                $table->string('external_url', 500)->nullable();
                $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
                $table->unsignedBigInteger('prerequisite_lesson_id')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedInteger('estimated_minutes')->nullable();
                $table->boolean('is_preview')->default(false);
                $table->boolean('is_published')->default(true);
                $table->boolean('is_required')->default(true);
                $table->timestamps();

                $table->index(['training_program_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('lesson_progress')) {
            Schema::create('lesson_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
                $table->foreignId('program_lesson_id')->constrained('program_lessons')->cascadeOnDelete();
                $table->char('user_id', 36)->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('last_position_seconds')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['enrollment_id', 'program_lesson_id']);
            });
        }

        // ── Exams / questions / attempts ─────────────────────────
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'kind')) {
                $table->string('kind', 20)->default('final')->after('title'); // final | quiz
            }
            if (! Schema::hasColumn('exams', 'description')) {
                $table->text('description')->nullable()->after('kind');
            }
            if (! Schema::hasColumn('exams', 'max_attempts')) {
                $table->unsignedSmallInteger('max_attempts')->nullable()->after('passing_score');
            }
            if (! Schema::hasColumn('exams', 'shuffle_questions')) {
                $table->boolean('shuffle_questions')->default(false)->after('max_attempts');
            }
            if (! Schema::hasColumn('exams', 'shuffle_options')) {
                $table->boolean('shuffle_options')->default(false)->after('shuffle_questions');
            }
            if (! Schema::hasColumn('exams', 'allow_review')) {
                $table->boolean('allow_review')->default(true)->after('shuffle_options');
            }
        });
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedBigInteger('training_program_id')->nullable()->change();
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_questions', 'question_type')) {
                $table->string('question_type', 20)->default('mcq')->after('exam_id'); // mcq | true_false | essay
            }
            if (! Schema::hasColumn('exam_questions', 'points')) {
                $table->unsignedSmallInteger('points')->default(1)->after('correct_answer');
            }
            if (! Schema::hasColumn('exam_questions', 'explanation')) {
                $table->text('explanation')->nullable()->after('points');
            }
            if (! Schema::hasColumn('exam_questions', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('explanation');
            }
        });
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->string('correct_answer')->nullable()->change();
        });

        Schema::table('exam_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_submissions', 'enrollment_id')) {
                $table->unsignedBigInteger('enrollment_id')->nullable()->index()->after('user_id');
            }
            if (! Schema::hasColumn('exam_submissions', 'program_lesson_id')) {
                $table->unsignedBigInteger('program_lesson_id')->nullable()->after('enrollment_id');
            }
            if (! Schema::hasColumn('exam_submissions', 'attempt_number')) {
                $table->unsignedSmallInteger('attempt_number')->default(1)->after('program_lesson_id');
            }
            if (! Schema::hasColumn('exam_submissions', 'status')) {
                // in_progress | submitted | needs_grading | expired
                $table->string('status', 20)->default('submitted')->after('attempt_number');
            }
            if (! Schema::hasColumn('exam_submissions', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('exam_submissions', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('started_at');
            }
            if (! Schema::hasColumn('exam_submissions', 'question_order')) {
                $table->json('question_order')->nullable()->after('expires_at');
            }
            if (! Schema::hasColumn('exam_submissions', 'option_order')) {
                $table->json('option_order')->nullable()->after('question_order');
            }
            if (! Schema::hasColumn('exam_submissions', 'earned_points')) {
                $table->decimal('earned_points', 8, 2)->default(0)->after('score');
            }
            if (! Schema::hasColumn('exam_submissions', 'total_points')) {
                $table->decimal('total_points', 8, 2)->default(0)->after('earned_points');
            }
            if (! Schema::hasColumn('exam_submissions', 'graded_by')) {
                $table->char('graded_by', 36)->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('exam_submissions', 'graded_at')) {
                $table->timestamp('graded_at')->nullable()->after('graded_by');
            }
            if (! Schema::hasColumn('exam_submissions', 'feedback')) {
                $table->text('feedback')->nullable()->after('graded_at');
            }
        });
        Schema::table('exam_submissions', function (Blueprint $table) {
            $table->longText('answers')->nullable()->change();
            $table->integer('score')->default(0)->change();
            $table->boolean('passed')->default(false)->change();
            $table->timestamp('submitted_at')->nullable()->change();
        });

        // ── Enrollments ───────────────────────────────────────────
        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'training_application_id')) {
                $table->unsignedBigInteger('training_application_id')->nullable()->after('training_program_id');
            }
            if (! Schema::hasColumn('enrollments', 'payment_status')) {
                $table->string('payment_status', 20)->nullable()->after('status'); // free | unpaid | paid
            }
            if (! Schema::hasColumn('enrollments', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0)->after('payment_status');
            }
            if (! Schema::hasColumn('enrollments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('enrollments', 'progress_percent')) {
                $table->unsignedTinyInteger('progress_percent')->default(0)->after('paid_at');
            }
            if (! Schema::hasColumn('enrollments', 'last_lesson_id')) {
                $table->unsignedBigInteger('last_lesson_id')->nullable()->after('progress_percent');
            }
            if (! Schema::hasColumn('enrollments', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('last_lesson_id');
            }
            if (! Schema::hasColumn('enrollments', 'notes')) {
                $table->text('notes')->nullable()->after('certificate_path');
            }
        });

        Schema::table('training_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('training_applications', 'user_id')) {
                $table->char('user_id', 36)->nullable()->index()->after('training_program_id');
            }
            if (! Schema::hasColumn('training_applications', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
            }
            if (! Schema::hasColumn('training_applications', 'reviewed_by')) {
                $table->char('reviewed_by', 36)->nullable()->after('reviewed_at');
            }
        });

        // ── Certificates ──────────────────────────────────────────
        if (! Schema::hasTable('training_certificates')) {
            Schema::create('training_certificates', function (Blueprint $table) {
                $table->id();
                $table->string('serial', 40)->unique();
                $table->foreignId('enrollment_id')->unique()->constrained('enrollments')->cascadeOnDelete();
                $table->char('user_id', 36)->index();
                $table->foreignId('training_program_id')->constrained('training_programs')->cascadeOnDelete();
                $table->unsignedBigInteger('exam_submission_id')->nullable();
                $table->string('holder_name');
                $table->string('program_title');
                $table->unsignedTinyInteger('final_score')->nullable();
                $table->timestamp('issued_at');
                $table->timestamp('revoked_at')->nullable();
                $table->string('revocation_reason')->nullable();
                $table->timestamps();
            });
        }

        // ── Badge rules ───────────────────────────────────────────
        Schema::table('badges', function (Blueprint $table) {
            if (! Schema::hasColumn('badges', 'rule_type')) {
                // program_completed | programs_count | high_score | lessons_count | manual
                $table->string('rule_type', 40)->nullable()->after('description');
            }
            if (! Schema::hasColumn('badges', 'rule_value')) {
                $table->unsignedInteger('rule_value')->nullable()->after('rule_type');
            }
            if (! Schema::hasColumn('badges', 'training_program_id')) {
                $table->unsignedBigInteger('training_program_id')->nullable()->after('rule_value');
            }
        });

        $this->migrateLegacyModuleContent();
        $this->ensureRoles();
    }

    /** Copy content of legacy modules (type/content_path) into a first lesson. */
    protected function migrateLegacyModuleContent(): void
    {
        $modules = DB::table('program_modules')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('program_lessons')->whereColumn('program_lessons.program_module_id', 'program_modules.id'))
            ->get();

        foreach ($modules as $module) {
            $type = match ($module->type) {
                'video' => $module->content_path ? 'video' : 'text',
                'pdf' => $module->content_path ? 'pdf' : 'text',
                default => 'text',
            };

            DB::table('program_lessons')->insert([
                'training_program_id' => $module->training_program_id,
                'program_module_id' => $module->id,
                'title' => $module->title,
                'type' => $type,
                'video_provider' => $type === 'video' ? 'upload' : null,
                'file_path' => in_array($type, ['video', 'pdf'], true) ? $module->content_path : null,
                'body' => $type === 'text' ? ($module->description ?: '<p>' . e($module->title) . '</p>') : $module->description,
                'sort_order' => 1,
                'is_published' => true,
                'is_required' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function ensureRoles(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }
        foreach (['estidama_admin', 'estidama_trainer'] as $role) {
            if (! DB::table('roles')->where('name', $role)->where('guard_name', 'web')->exists()) {
                DB::table('roles')->insert(['name' => $role, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_certificates');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('program_lessons');

        $drop = function (string $tableName, array $columns) {
            $existing = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($tableName, $c)));
            if ($existing) {
                Schema::table($tableName, fn (Blueprint $t) => $t->dropColumn($existing));
            }
        };

        $drop('training_programs', ['instructor_id', 'is_paid', 'price', 'sequential', 'requires_application', 'certificate_enabled', 'estimated_minutes', 'level', 'completion_badge_id']);
        $drop('program_modules', ['is_published', 'unlock_after_days', 'estimated_minutes']);
        $drop('exams', ['kind', 'description', 'max_attempts', 'shuffle_questions', 'shuffle_options', 'allow_review']);
        $drop('exam_questions', ['question_type', 'points', 'explanation', 'sort_order']);
        $drop('exam_submissions', ['enrollment_id', 'program_lesson_id', 'attempt_number', 'status', 'started_at', 'expires_at', 'question_order', 'option_order', 'earned_points', 'total_points', 'graded_by', 'graded_at', 'feedback']);
        $drop('enrollments', ['training_application_id', 'payment_status', 'amount', 'paid_at', 'progress_percent', 'last_lesson_id', 'last_activity_at', 'notes']);
        $drop('training_applications', ['user_id', 'reviewed_at', 'reviewed_by']);
        $drop('badges', ['rule_type', 'rule_value', 'training_program_id']);
    }
};
