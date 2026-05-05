<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_smart_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_batch_id')->nullable()->constrained('workbook_uploads')->nullOnDelete();
            $table->unsignedInteger('source_row');
            $table->string('ref')->nullable();
            $table->text('program_name')->nullable();
            $table->text('sap_program_wbs_name')->nullable();
            $table->text('project_name')->nullable();
            $table->text('sap_project_wbs_name')->nullable();
            $table->string('business_case_no')->nullable();
            $table->string('main_wbs')->nullable();
            $table->string('sub_wbs')->nullable();
            $table->string('cost_centre')->nullable();
            $table->text('pr_no')->nullable();
            $table->string('po_no')->nullable();
            $table->decimal('allocated_project_amount', 20, 2)->nullable();
            $table->unsignedInteger('approval_year')->nullable();
            $table->string('owner')->nullable();
            $table->string('executor')->nullable();
            $table->string('department')->nullable();
            $table->string('strategic_driver')->nullable();
            $table->string('level')->nullable();
            $table->string('blank_1')->nullable();
            $table->text('project_name_arabic')->nullable();
            $table->text('contractor_designer_name')->nullable();
            $table->string('contract_type')->nullable();
            $table->decimal('original_contract_value', 20, 2)->nullable();
            $table->decimal('vo_amount', 20, 2)->nullable();
            $table->decimal('vo_pct', 12, 6)->nullable();
            $table->decimal('advance_amount', 20, 2)->nullable();
            $table->decimal('total_paid_amount', 20, 2)->nullable();
            $table->decimal('invoiced_amount', 20, 2)->nullable();
            $table->decimal('remaining_amount', 20, 2)->nullable();
            $table->string('blank_2')->nullable();
            $table->string('project_status')->nullable();
            $table->string('stage_gate')->nullable();
            $table->string('stage_gate_progress')->nullable();
            $table->string('category_1_previous')->nullable();
            $table->string('designer_category')->nullable();
            $table->string('contractor_category')->nullable();
            $table->string('program')->nullable();
            $table->string('sub_program')->nullable();
            $table->string('cm_category')->nullable();
            $table->string('program_manager')->nullable();
            $table->string('project_manager')->nullable();
            $table->string('project_lead')->nullable();
            $table->date('project_start_date')->nullable();
            $table->date('project_finish_date')->nullable();
            $table->integer('original_duration')->nullable();
            $table->date('suspension_date')->nullable();
            $table->date('resumption_date')->nullable();
            $table->date('revised_finish_date')->nullable();
            $table->integer('revised_duration')->nullable();
            $table->decimal('planned_pct', 12, 6)->nullable();
            $table->decimal('actual_pct', 12, 6)->nullable();
            $table->decimal('sv', 16, 6)->nullable();
            $table->decimal('ev', 20, 2)->nullable();
            $table->decimal('pv', 20, 2)->nullable();
            $table->decimal('ac', 20, 2)->nullable();
            $table->decimal('spi', 16, 6)->nullable();
            $table->decimal('cpi', 16, 6)->nullable();
            $table->date('applicable_finish_date')->nullable();
            $table->string('project_health')->nullable();
            $table->string('blank_3')->nullable();
            $table->longText('brief_description')->nullable();
            $table->decimal('engineering_pct', 12, 6)->nullable();
            $table->decimal('procurement_pct', 12, 6)->nullable();
            $table->decimal('construction_pct', 12, 6)->nullable();
            $table->longText('engineering_status_update')->nullable();
            $table->longText('procurement_status_update')->nullable();
            $table->longText('construction_status_update')->nullable();
            $table->longText('weekly_lookahead')->nullable();
            $table->longText('issues_concerns')->nullable();
            $table->longText('risks')->nullable();
            $table->string('data_issue')->nullable();
            $table->string('key_check')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->index(['approval_year', 'project_status']);
            $table->index(['project_health', 'stage_gate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_smart_records');
    }
};
