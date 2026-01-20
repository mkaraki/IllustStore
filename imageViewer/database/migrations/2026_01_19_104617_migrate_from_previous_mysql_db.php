<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('illusts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('path');
            $table->binary('aHash', length: 64)->nullable()->index();
            $table->binary('pHash', length: 64)->nullable()->index();
            $table->binary('dHash', length: 64)->nullable()->index();
            $table->binary('colorHash', length: 48)->nullable()->index();
            $table->unsignedBigInteger('width')->nullable();
            $table->unsignedBigInteger('height')->nullable();
        });

        Schema::create('tagGroups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('name');
            $table->mediumText('description')->nullable();
            $table->longText('taggingNote')->nullable();
            $table->unsignedBigInteger('parentId')->nullable();
            $table->foreign('parentId')->references('id')->on('tagGroups');
        });

        Schema::create('selectiveTagGroups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('name');
            $table->mediumText('description')->nullable();
            $table->longText('taggingNote')->nullable();
            $table->unsignedBigInteger('parentTagGroupId')->nullable();
            $table->foreign('parentTagGroupId')->references('id')->on('tagGroups');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('tagName')->unique();

            $table->text('tagDanbooru')->nullable();
            $table->text('tagPixivJpn')->nullable();
            $table->text('tagPixivEng')->nullable();
            $table->integer('tagType')->nullable();
            $table->text('shortDescription')->nullable();
            $table->mediumText('description')->nullable();
            $table->longText('taggingNote')->nullable();

            $table->unsignedBigInteger('aliasOf')->nullable();

            $table->unsignedBigInteger('tagGroup')->nullable();

            $table->unsignedBigInteger('selectiveTagGroup')->nullable();
        });

        Schema::table('tags', function(Blueprint $table) {
            $table->foreign('aliasOf')->references('id')->on('tags');
            $table->foreign('tagGroup')->references('id')->on('tagGroups');
            $table->foreign('selectiveTagGroup')->references('id')->on('selectiveTagGroups');
        });

        Schema::create('tagAssign', function (Blueprint $table) {
            $table->unsignedBigInteger('illustId')->index();
            $table->foreign('illustId')->references('id')->on('illusts');

            $table->unsignedBigInteger('tagId')->index();
            $table->foreign('tagId')->references('id')->on('tags');

            $table->primary(['illustId', 'tagId']);

            $table->boolean('autoAssigned')->default(false)->index();
            $table->float('accuracy', precision: 7)->nullable();
            $table->boolean('notSure')->default(false);
        });

        Schema::create('tagNegativeAssign', function (Blueprint $table) {
            $table->unsignedBigInteger('illustId');
            $table->foreign('illustId')->references('id')->on('illusts');

            $table->unsignedBigInteger('tagId');
            $table->foreign('tagId')->references('id')->on('tags');

            $table->primary(['illustId', 'tagId']);
        });

        Schema::create('metadata_provider', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->text('pathPattern');
            $table->text('providerUrlReplacement')->nullable();
            $table->text('apiUrlReplacement')->nullable();
            $table->text('sourceUrlReplacement')->nullable();
        });

        Schema::create('tagDependRelation', function (Blueprint $table) {
            $table->unsignedBigInteger('tagId');
            $table->foreign('tagId')->references('id')->on('tags');

            $table->unsignedBigInteger('dependTagId');
            $table->foreign('dependTagId')->references('id')->on('tags');

            $table->primary(['tagId', 'dependTagId']);
        });

        Schema::create('tagRelatedRelation', function (Blueprint $table) {
            $table->unsignedBigInteger('tagId');
            $table->foreign('tagId')->references('id')->on('tags');

            $table->unsignedBigInteger('relatedTagId');
            $table->foreign('relatedTagId')->references('id')->on('tags');

            $table->primary(['tagId', 'relatedTagId']);
        });

        Schema::create('tagCantWithRelation', function (Blueprint $table) {
            $table->unsignedBigInteger('tagId');
            $table->foreign('tagId')->references('id')->on('tags');

            $table->unsignedBigInteger('cantWithTagId');
            $table->foreign('cantWithTagId')->references('id')->on('tags');

            $table->primary(['tagId', 'cantWithTagId']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('illusts');
        Schema::drop('tagGroups');
        Schema::drop('selectiveTagGroups');
        Schema::drop('tags');
        Schema::drop('tagAssign');
        Schema::drop('tagNegativeAssign');
        Schema::drop('metadata_provider');
        Schema::drop('tagDependRelation');
        Schema::drop('tagRelatedRelation');
        Schema::drop('tagCantWithRelation');
    }
};
