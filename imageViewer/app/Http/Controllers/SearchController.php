<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SearchController extends Controller
{

    public static function searchTagIdsFromTagStrings(string $q) {
        $q = trim(trim($q), ' ');
        $q = str_replace('　', ' ', $q);
        $q = explode(' ', $q);

        $tagIds = [];
        foreach($q as $t) {
            $searchedTag = self::searchTagIdFromTagString($t);
            if ($searchedTag === null) {
                continue;
            }

            $tagIds[] = $searchedTag[0];
        }

        return $tagIds;
    }

    public static function searchTagIdFromTagString(string $t): ?array
    {
        $t = strtolower($t);
        $exactTag = DB::table('tags')
            ->whereRaw('LOWER(tagName) = ?', [$t])
            ->select(['id', 'tagName'])
            ->first();
        if ($exactTag === null) {
            $possibleTags = DB::table('tags')
                ->whereRaw('LOWER(tagDanbooru) = ?', [$t])
                ->orWhereRaw('LOWER(tagPixivJpn) = ?', [$t])
                ->orWhereRaw('LOWER(tagPixivEng) = ?', [$t])
                ->select(['id', 'tagName'])
                ->first();

            if ($possibleTags === null) return null;
            return [$possibleTags->id, $possibleTags->tagName];
        } else {
            return [$exactTag->id, $exactTag->tagName];
        }
    }

    public function index(Request $request) {
        $q = $request->get('q');
        if (empty($q)) {
            // If no query, redirect to `/` with See Other.
            return response()->redirectTo('/', 303);
        }

        $q = trim(trim($q), ' ');
        $q = str_replace('　', ' ', $q);
        $q = explode(' ', $q);

        $usedQuery = '';

        $searchTagIds = [];

        foreach($q as $t) {
            $searchedTag = self::searchTagIdFromTagString($t);
            if ($searchedTag === null) {
                continue;
            }

            $tagId = $searchedTag[0];
            $usedQuery .= ' tag:' . $searchedTag[1];

            $searchTagIds[] = $tagId;
        }

        $paginate = DB::table('tagAssign')
            ->join('illusts', 'tagAssign.illustId', '=', 'illusts.id')
            ->orderBy('tagAssign.illustId', 'desc')
	    ->whereIn('tagAssign.tagId', $searchTagIds)
            ->havingRaw('COUNT(illusts.id) = ?', [count($searchTagIds)])
            ->groupBy('tagAssign.illustId')
            ->select(['tagAssign.illustId AS id'])
            ->selectRaw('MIN(illusts.width) AS width')
            ->selectRaw('MIN(illusts.height) AS height')
            ->paginate(100);

        $paginate = $paginate->onEachSide($paginate->lastPage());

        return Inertia::render('Image/Index', [
            'searchParam' => $usedQuery,
            'images' => $paginate,
            'imgServerBase' => config('illuststore.image_server_base_url'),
        ]);
    }

    public function hashSearch(string $hashType, string $hash, Request $request) {
        $threshold = 10;
        if ($hashType === 'colorHash') {
            $threshold = 3;
        }

        $threshold = intval($request->get('threshold', $threshold));
        if ($request->get('exact') !== null) $threshold = 0;
        $exact = $threshold == 0;

        $queryBuilder = DB::table('illusts')
            ->select(['id', 'width', 'height'])
            ->orderBy('similarity')
            ->orderBy('id', 'desc');

        if ($exact) {
            $queryBuilder = $queryBuilder
                ->selectRaw('1 AS similarity')
                ->whereRaw("`$hashType` = CONV(?, 16, 10)", [$hash]);
        } else {
            $queryBuilder = $queryBuilder
                ->selectRaw("BIT_COUNT(`$hashType` ^ CONV(?, 16, 10)) AS similarity", [$hash])
                ->whereRaw("BIT_COUNT(`$hashType` ^ CONV(?, 16, 10)) < ?", [$hash, $threshold]);
        }

        $images = $queryBuilder->paginate(100);
        $images = $images->onEachSide($images->lastPage());

        return Inertia::render('Image/Index', [
            'searchParam' => "$hashType:$hash?threshold=$threshold",
            'images' => $images,
            'imgServerBase' => config('illuststore.image_server_base_url'),
        ]);
    }
}
