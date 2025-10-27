<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function index()
    {
        $randomTags = fn() => DB::table('tags')
                ->orderByRaw('RAND()')
                ->limit(7)
                ->get();
        $images = fn() => DB::table('illusts')
                ->select('id', 'width', 'height')
                ->orderByRaw('RAND()')
                ->limit(19) /* 18 (3 x 6) for random. 1 for heading */
                ->get();

        $imageCount = fn() => DB::table('illusts')->count();
        $tagCount = fn() => DB::table('tags')->count();
        $tagAssignCount = fn() => DB::table('tagAssign')->count();

        $nonTaggedImageAndTag = fn() => DB::table(
            DB::table('tagAssign')
                ->select(['tagAssign.illustId as imageId', 'tagAssign.tagId as tagId', 'tags.tagName as tagName'])
                ->join('tags', 'tagAssign.tagId', '=', 'tags.id')
                ->where('tagAssign.autoAssigned', '=', 1)
                ->limit(10000 /* Due to extremely slow query. */),
            'res'
        )
            ->select(['res.imageId', 'res.tagId', 'res.tagName', 'illusts.width', 'illusts.height'])
            ->join('illusts', 'res.imageId', '=', 'illusts.id')
            ->orderByRaw('RAND()')
            ->limit(1)
            ->first();

        return Inertia::render('Welcome', [
            'randomTags' => $randomTags,
            'nonTaggedImageAndTag' => Inertia::defer($nonTaggedImageAndTag, 'nonTaggedImageAndTag'),
            'images' => $images,
            'imgServerBase' => config('illuststore.image_server_base_url'),
            'imageCount' => Inertia::defer($imageCount, 'stats'),
            'tagCount' => Inertia::defer($tagCount, 'stats'),
            'tagAssignCount' => Inertia::defer($tagAssignCount, 'stats'),
        ]);
    }
}
