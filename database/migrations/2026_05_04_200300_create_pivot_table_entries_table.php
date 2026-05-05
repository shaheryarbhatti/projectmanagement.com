<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pivot_table_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_batch_id')->nullable()->constrained('workbook_uploads')->nullOnDelete();
            $table->unsignedInteger('source_row');
            $table->unsignedInteger('source_column');
            $table->string('cell_reference')->nullable();
            $table->string('section_title')->nullable();
            $table->string('metric_title')->nullable();
            $table->string('row_label')->nullable();
            $table->string('column_label')->nullable();
            $table->decimal('value_numeric', 20, 2)->nullable();
            $table->text('value_text')->nullable();
            $table->timestamps();

            $table->index(['section_title', 'metric_title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pivot_table_entries');
    }
};
