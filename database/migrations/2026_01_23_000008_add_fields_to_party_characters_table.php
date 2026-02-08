<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_characters', function (Blueprint $table) {
            $table->string('race', 60)->after('name');
            $table->string('class_name', 60)->after('race');
            $table->string('gender', 30)->after('class_name');
            $table->unsignedSmallInteger('age')->after('gender');
            $table->unsignedSmallInteger('height_cm')->after('age');
            $table->unsignedSmallInteger('weight_kg')->after('height_cm');
            $table->json('traits')->after('weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('party_characters', function (Blueprint $table) {
            $table->dropColumn([
                'race',
                'class_name',
                'gender',
                'age',
                
                
                
                
                
                
                
                
                'height_cm',
                'weight_kg',
                'traits',
            ]);
        });
    }
};
