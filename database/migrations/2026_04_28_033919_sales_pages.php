<?php
// File: database/migrations/xxxx_xx_xx_create_sales_pages_table.php
// Jalankan: php artisan migrate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('product_name');
            $table->text('description');
            $table->text('features');           // JSON string
            $table->string('target_audience');
            $table->string('price');
            $table->text('unique_selling_points')->nullable();
            $table->longText('generated_html'); // rendered sales page HTML
            $table->longText('generated_data'); // JSON: headline, sections, etc.
            $table->string('style')->default('modern'); // template style
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sales_pages');
    }
};
