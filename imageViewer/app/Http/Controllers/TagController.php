<?php

namespace App\Http\Controllers;

use App\Models\Illust;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TagController extends Controller
{
    public function index()
    {
        $tags = DB::table('tags')
            ->selectSub('SELECT COUNT(tagAssign.tagId) AS count FROM tagAssign WHERE tags.id = tagAssign.tagId GROUP BY tagAssign.tagId', "count")
            ->addSelect('tags.id', 'tags.tagName')
            ->orderBy('tags.tagName', 'asc')
            ->paginate(500);

        $tags = $tags->onEachSide($tags->lastPage());

        $maxAssignCount = DB::query()
            ->fromSub(DB::table('tagAssign')->groupBy('tagId')->selectRaw('COUNT(tagAssign.tagId) as cnt'), "cntHost")
            ->max('cntHost.cnt');

        return Inertia::render('Tag/Index', [
            'tags' => $tags,
            'maxAssignCount' => $maxAssignCount,
        ]);
    }

    public function show(int $tagId)
    {
        $tagData = DB::table('tags')
            ->where('id', '=', $tagId)
            ->first();

        $images = DB::table('illusts')
            ->join('tagAssign', 'illusts.id', '=', 'tagAssign.illustId')
            ->where('tagAssign.tagId', '=', $tagId)
            ->paginate(100);

        $images = $images->onEachSide($images->lastPage());

        return Inertia::render('Image/Index', [
            'searchParam' => 'tag:' . $tagData->tagName,
            'images' => $images,
            'tagData' => $tagData,
            'imgServerBase' => config('illuststore.image_server_base_url'),
        ]);
    }

    public function complete(Request $request)
    {
        $sentryTraceHeader = $request->header('sentry-trace');
        $baggageHeader = $request->header('baggage');
        \Sentry\continueTrace($sentryTraceHeader, $baggageHeader);

        $w = $request->input('w', null);
        if ($w == null) {
            return response(null, 400);
        }

        $res = DB::table('tags')
            ->whereLike('tagName', '%' . addcslashes($w, '%_\\') . '%')
            ->orderBy('tagName', 'ASC')
            ->select(['tagName'])
            ->pluck('tagName');

        return response()->json([
            'sw' => $res,
        ]);
    }

    public function newTagView()
    {
        return Inertia::render('Tag/Edit', [
            'isNew' => true,
        ]);
    }

    private function noReturnEmpty(string|null $i) {
        if (empty($i)) {
            return null;
        }
        return $i;
    }

    public function newTag(Request $request)
    {
        $request->validate([
            'tag' => ['required', 'string', 'not_regex:/ /i', 'min:1', 'unique:tags,tagName'],
            // https://dic.pixiv.net/a/%E3%82%BF%E3%82%B0
            'tagPixivJpn' => ['nullable', 'string', 'not_regex:/ /i', 'max:30'],
            'tagPixivEng' => ['nullable', 'string'],
            // https://donmai.moe/wiki_pages/howto%3Atag
            'tagDanbooru' => ['nullable', 'string', 'ascii', 'not_regex:/( |__+|\*|,|_$)/i', 'max:170'],
            'description' => ['nullable', 'string'],
            'taggingNote' => ['nullable', 'string'],
        ]);

        $newId = DB::table('tags')
            ->insertGetId([
                'tagName' => $this->noReturnEmpty($request->input('tag')),
                'tagPixivJpn' => $this->noReturnEmpty($request->input('tagPixivJpn')),
                'tagPixivEng' => $this->noReturnEmpty($request->input('tagPixivEng')),
                'tagDanbooru' => $this->noReturnEmpty($request->input('tagDanbooru')),
                'description' => $this->noReturnEmpty($request->input('description')),
                'taggingNote' => $this->noReturnEmpty($request->input('taggingNote')),
            ]);

        return response()->redirectTo("/tag/$newId", 303);
    }

    public function editTagView(int $tagId) {
        $tagData = DB::table('tags')
            ->where('id', '=', $tagId)
            ->limit(1)
            ->first();

        if ($tagData === null) {
            return response(null, 404);
        }

        return Inertia::render('Tag/Edit', [
            'isNew' => false,
            'tagData' => $tagData,
        ]);
    }

    public function editTag(int $tagId, Request $request) {
        $tagData = DB::table('tags')
            ->where('id', '=', $tagId)
            ->select(['id'])
            ->limit(1)
            ->first();

        if ($tagData === null) {
            return response(null, 404);
        }

        $request->validate([
            'tag' => [
                'required', 'string', 'not_regex:/ /i', 'min:1',
                Rule::unique('tags', 'tagName')->ignore($tagId, 'id')
            ],
            // https://dic.pixiv.net/a/%E3%82%BF%E3%82%B0
            'tagPixivJpn' => ['nullable', 'string', 'not_regex:/ /i', 'max:30'],
            'tagPixivEng' => ['nullable', 'string'],
            // https://donmai.moe/wiki_pages/howto%3Atag
            'tagDanbooru' => ['nullable', 'string', 'ascii', 'not_regex:/( |__+|\*|,|_$)/i', 'max:170'],
            'description' => ['nullable', 'string'],
            'taggingNote' => ['nullable', 'string'],
        ]);

        DB::table('tags')->upsert([
            [
                'id' => $tagId,
                'tagName' => $this->noReturnEmpty($request->input('tag')),
                'tagPixivJpn' => $this->noReturnEmpty($request->input('tagPixivJpn')),
                'tagPixivEng' => $this->noReturnEmpty($request->input('tagPixivEng')),
                'tagDanbooru' => $this->noReturnEmpty($request->input('tagDanbooru')),
                'description' => $this->noReturnEmpty($request->input('description')),
                'taggingNote' => $this->noReturnEmpty($request->input('taggingNote')),
            ]
        ], ['id'], [
            'tagName',
            'tagPixivJpn',
            'tagPixivEng',
            'tagDanbooru',
            'description',
            'taggingNote',
        ]);

        return response()->redirectTo("/tag/$tagId", 303);
    }

    public function pending()
    {
        $images = Illust::whereHas('tags', function ($query) {
            $query->where('autoAssigned', 1);
        })->with(['tags', 'negativeTags'])->paginate(30);

        $images = $images->onEachSide($images->lastPage());

        return Inertia::render('Tag/Pending', [
            'images' => $images,
            'imgServerBase' => config('illuststore.image_server_base_url'),
        ]);
    }
}
