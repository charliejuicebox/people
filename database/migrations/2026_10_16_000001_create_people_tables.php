<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->unique()->after('id');
            $table->string('entity', 2)->default('AU')->after('email'); // AU | ID
            $table->foreignId('department_id')->nullable()->after('entity')->constrained()->nullOnDelete();
            $table->string('job_title')->nullable()->after('department_id');
            $table->foreignId('team_lead_id')->nullable()->after('job_title')->constrained('users')->nullOnDelete();
            $table->string('role')->default('employee')->after('team_lead_id'); // employee | team_lead | hr | director | admin
            $table->string('employment_type')->nullable()->after('role');
            $table->date('start_date')->nullable()->after('employment_type');
            $table->date('contract_end_date')->nullable()->after('start_date');
            $table->date('left_at')->nullable()->after('contract_end_date');
            $table->string('sign_in_provider')->default('google')->after('left_at'); // google | microsoft
            $table->string('locale', 2)->default('en')->after('sign_in_provider');
            $table->boolean('active')->default(true)->after('locale');
            $table->string('password')->nullable()->change();
        });

        // A person can have several sign-in identities (Google now, Microsoft after 2027).
        // Records are keyed on users.id, never on email.
        Schema::create('social_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_user_id');
            $table->string('email')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_user_id']);
        });

        Schema::create('quarterly_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->string('status')->default('draft'); // draft | shared | acknowledged

            // One rating + comment per company value.
            foreach (['make_it_matter', 'work_as_one', 'embrace_change'] as $value) {
                $table->string("{$value}_rating")->nullable();
                $table->text("{$value}_comment")->nullable();
            }

            $table->text('went_well')->nullable();
            $table->text('concerns')->nullable();
            $table->text('expectations_next_quarter')->nullable();
            $table->text('flexibility_check')->nullable();
            $table->string('overall_rating')->nullable(); // exceeds | meets | developing | below

            $table->text('self_reflection')->nullable();
            $table->text('employee_comment')->nullable();

            $table->timestamp('shared_at')->nullable();
            $table->date('discussed_on')->nullable();
            $table->timestamp('acknowledged_at')->nullable();

            // "No surprises" check.
            $table->boolean('expectation_communicated_confirmed')->default(false);
            $table->timestamp('flagged_to_hr_at')->nullable();

            $table->timestamps();
            $table->unique(['employee_id', 'year', 'quarter']);
        });

        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('value_tag')->nullable();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('praise', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('value_tag');
            $table->text('message');
            $table->timestamp('slack_posted_at')->nullable();
            $table->string('slack_error')->nullable();
            $table->timestamps();
        });

        // Append-only. The model refuses updates and deletes.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('praise');
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('quarterly_reviews');
        Schema::dropIfExists('social_identities');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('team_lead_id');
            $table->dropColumn([
                'employee_id', 'entity', 'job_title', 'role', 'employment_type', 'start_date',
                'contract_end_date', 'left_at', 'sign_in_provider', 'locale', 'active',
            ]);
        });
        Schema::dropIfExists('departments');
    }
};
