<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suspension_resumption_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_batch_id')->nullable()->constrained('workbook_uploads')->nullOnDelete();
            $table->unsignedInteger('source_row');
            $table->text('project_name')->nullable();
            $table->text('contractor_designer_name')->nullable();
            $table->decimal('actual_pct', 12, 6)->nullable();
            $table->string('type_of_suspension')->nullable();
            $table->string('po')->nullable();
            $table->date('project_start_date')->nullable();
            $table->date('suspension_date')->nullable();
            $table->longText('suspension_reason')->nullable();
            $table->date('resumption_date')->nullable();
            $table->date('revised_finish_date')->nullable();
            $table->integer('suspension_duration_days')->nullable();
            $table->string('status_of_resumption')->nullable();
            $table->longText('remarks')->nullable();
            $table->timestamps();

            $table->index('status_of_resumption');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suspension_resumption_records');
    }
};
