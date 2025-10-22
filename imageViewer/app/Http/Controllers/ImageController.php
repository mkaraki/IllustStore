<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ImageController extends Controller
{
    public function index()
    {
        $images = DB::table('illusts')->paginate(100);
        $images = $images->onEachSide($images->lastPage());

        return Inertia::render('Image/Index', [
            'searchParam' => '*',
            'images' => $images,
            'imgServerBase' => config('illuststore.image_server_base_url'),
        ]);
    }

    public function show(int $imageId)
    {
        $imageData = DB::table('illusts')
            ->select([
                "path",
                DB::raw('CONVERT(CONV(aHash    , 10, 16), CHAR) as aHash'),
                DB::raw('CONVERT(CONV(dHash    , 10, 16), CHAR) as dHash'),
                DB::raw('CONVERT(CONV(pHash    , 10, 16), CHAR) as pHash'),
                DB::raw('CONVERT(CONV(colorHash, 10, 16), CHAR) as colorHash'),
            ])
            ->where('id', '=', $imageId)
            ->limit(1)
            ->first();

        if ($imageData === null) {
            return response(null, 404);
        }

        $metadataProviders = DB::table('metadata_provider')->get();
        foreach($metadataProviders as $provider) {
            $provider['pathPattern'] = '/' . str_replace('/', '\/', $provider['pathPattern']) . '/';

            if (!preg_match($provider['pathPattern'], $imageData->path)) continue;

            if (empty($provider['apiUrlReplacement'])) {
                $metadataApiUrl = null;
            } else {
                $metadataApiUrl = preg_replace($provider['pathPattern'], $provider['apiUrlReplacement'], $imageData->path);
            }

            if (empty($provider['providerUrlReplacement'])) {
                $metadataProviderUrl = null;
            } else {
                $metadataProviderUrl = preg_replace($provider['pathPattern'], $provider['providerUrlReplacement'], $imageData->path);
            }

            $metadataProviderName = $provider['name'];

            if ($provider['sourceUrlReplacement'] !== null) {
                $metadataSourceUrl = preg_replace($provider['pathPattern'], $provider['sourceUrlReplacement'], $imageData->path);
            } else {
                $metadataSourceUrl = null;
            }
        }

        $metadata = [
            'metadataProviderName' => $metadataProviderName ?? null,
            'metadataProviderUrl' => $metadataProviderUrl ?? null,
            'metadataSourceUrl' => $metadataSourceUrl ?? null,
            'metadataApiUrl' => $metadataApiUrl ?? null,
            'apiMetadata' => null,
        ];
        if ($metadata['metadataApiUrl'] !== null) {
            $metadata['apiMetadata'] = json_decode(file_get_contents($metadata['metadataApiUrl']), true);
        }

        $tags = fn() => DB::table('tagAssign')
            ->join('tags', 'tagAssign.tagId', '=', 'tags.id')
            ->where('illustId', $imageId)
            ->get();
        $negativeTags = fn() => DB::table('tagNegativeAssign')
            ->join('tags', 'tagNegativeAssign.tagId', '=', 'tags.id')
            ->where('illustId', $imageId)
            ->get();

        return Inertia::render('Image/Show', [
            'imageId' => fn() => $imageId,
            'imageData' => $imageData,
            'metadata' => $metadata,
            'tags' => $tags,
            'negativeTags' => $negativeTags,
            'imgServerBase' => fn() => config('illuststore.image_server_base_url'),
        ]);
    }

    public function duplicate(int $imageId, Request $request) {
        $imageData = DB::table('illusts')
            ->select([
                "width", "height",
                DB::raw('CONVERT(CONV(aHash    , 10, 16), CHAR) as aHash'),
                DB::raw('CONVERT(CONV(dHash    , 10, 16), CHAR) as dHash'),
                DB::raw('CONVERT(CONV(pHash    , 10, 16), CHAR) as pHash'),
                DB::raw('CONVERT(CONV(colorHash, 10, 16), CHAR) as colorHash'),
            ])
            ->where('id', '=', $imageId)
            ->limit(1)
            ->first();

        if ($imageData === null) {
            return response(null, 404);
        }

        $searchQuery = DB::table('illusts')
            ->select(['id', 'width', 'height']);

        $queryStr = '';

        $aHash = $imageData->aHash ?? null;
        if ($aHash !== null) {
            $searchQuery = $searchQuery->whereRaw("aHash = CONV(?, 16, 10)", [$aHash]);
            $queryStr .= ' aHash:' . $aHash;
        }
        $dHash = $imageData->dHash ?? null;
        if ($dHash !== null) {
            $searchQuery = $searchQuery->whereRaw("dHash = CONV(?, 16, 10)", [$dHash]);
            $queryStr .= ' dHash:' . $dHash;
        }
        $pHash = $imageData->pHash ?? null;
        if ($pHash !== null) {
            $searchQuery = $searchQuery->whereRaw("pHash = CONV(?, 16, 10)", [$pHash]);
            $queryStr .= ' pHash:' . $pHash;
        }
        $colorHash = $imageData->colorHash ?? null;
        if ($colorHash !== null) {
            $searchQuery = $searchQuery->whereRaw("colorHash = CONV(?, 16, 10)", [$colorHash]);
            $queryStr .= ' colorHash:' . $colorHash;
        }

        if ($request->get('exact_size') != null) {
            $width = $imageData->width ?? null;
            if ($width !== null) {
                $searchQuery = $searchQuery->where('width', '=', $width);
                $queryStr .= ' width:' . $width;
            }
            $height = $imageData->height ?? null;
            if ($height !== null) {
                $searchQuery = $searchQuery->where('height', '=', $height);
                $queryStr .= ' height:' . $height;
            }
        }

        $paginate = $searchQuery->paginate(100)->withQueryString();
        $paginate = $paginate->onEachSide($paginate->lastPage());

        return Inertia::render('Image/Index', [
            'searchParam' => ltrim($queryStr, ' '),
            'images' => $paginate,
            'imgServerBase' => config('illuststore.image_server_base_url'),
        ]);
    }

    public function neighbor(int $imageId) {
        $imageData = DB::table('illusts')
            ->select(["id"])
            ->where('id', '=', $imageId)
            ->limit(1)
            ->first();

        if ($imageData === null) {
            return response(null, 404);
        }

        $neighbors = DB::table(
            DB::table('tagAssign')->select(['tagId'])->where('illustId', '=', $imageId),
            'tt'
        )
            ->addSelect('tagAssign.illustId as id')
            ->selectRaw('MIN(illusts.width) AS width')
            ->selectRaw('MIN(illusts.height) AS height')
            //->addSelect('illusts.width')
            //->addSelect('illusts.height')
            ->selectRaw('COUNT(*) AS tag_match_count')
            ->join('tagAssign', 'tt.tagId', '=', 'tagAssign.tagId')
            ->join('illusts', 'tagAssign.illustId', '=', 'illusts.id')
            ->where('tagAssign.illustId', '<>', $imageId)
            ->groupBy('tagAssign.illustId')
            ->orderBy('tag_match_count', 'desc')
            ->paginate(100);

        $neighbors = $neighbors->onEachSide($neighbors->lastPage());

        return Inertia::render('Image/Index', [
            'searchParam' => 'neighbor:' . $imageId,
            'images' => $neighbors,
            'imgServerBase' => config('illuststore.image_server_base_url'),
        ]);
    }

    public function deleteTag(int $imageId, int $tagId) {
        $tagData = DB::table('tags')
            ->where('id', '=', $tagId)
            ->limit(1)
            ->first();

        if ($tagData === null) {
            return response(null, 404);
        }

        $imageData = DB::table('illusts')
            ->where('id', '=', $imageId)
            ->limit(1)
            ->first();

        if ($imageData === null) {
            return response(null, 404);
        }

        $negativeTag = DB::table('tagNegativeAssign')
            ->where('illustId', '=', $imageId)
            ->where('tagId', '=', $tagId)
            ->limit(1)
            ->first();
        if ($negativeTag === null) {
            DB::table('tagNegativeAssign')
                ->insert([
                    'illustId' => $imageId,
                    'tagId' => $tagId,
                ]);
        }

        DB::table('tagAssign')
            ->where('illustId', '=', $imageId)
            ->where('tagId', '=', $tagId)
            ->delete();

        return response()->redirectTo("/image/$imageId", 303);
    }

    private function putTagProcess(int $imageId, int $tagId): void
    {
        $negativeTag = DB::table('tagNegativeAssign')
            ->where('illustId', '=', $imageId)
            ->where('tagId', '=', $tagId)
            ->limit(1)
            ->first();
        if ($negativeTag !== null) {
            DB::table('tagNegativeAssign')
                ->where('illustId', '=', $imageId)
                ->where('tagId', '=', $tagId)
                ->delete();
        }

        DB::table('tagAssign')
            ->where('illustId', '=', $imageId)
            ->where('tagId', '=', $tagId)
            ->updateOrInsert([
                'illustId' => $imageId,
                'tagId' => $tagId,
            ], [
                'autoAssigned' => 0,
            ]);
    }

    public function putTag(int $imageId, int $tagId) {
        $tagData = DB::table('tags')
            ->where('id', '=', $tagId)
            ->limit(1)
            ->first();

        if ($tagData === null) {
            return response(null, 404);
        }

        $imageData = DB::table('illusts')
            ->where('id', '=', $imageId)
            ->limit(1)
            ->first();

        if ($imageData === null) {
            return response(null, 404);
        }

        $this->putTagProcess($imageId, $tagId);

        return response()->redirectTo("/image/$imageId", 303);
    }

    public function assignTagMenu(int $imageId)
    {
        $imageData = DB::table('illusts')
            ->select([
                "id",
            ])
            ->where('id', '=', $imageId)
            ->limit(1)
            ->first();

        if ($imageData === null) {
            return response(null, 404);
        }

        $tags = fn() => DB::table('tagAssign')
            ->join('tags', 'tagAssign.tagId', '=', 'tags.id')
            ->where('illustId', $imageId)
            ->get();
        $negativeTags = fn() => DB::table('tagNegativeAssign')
            ->join('tags', 'tagNegativeAssign.tagId', '=', 'tags.id')
            ->where('illustId', $imageId)
            ->get();

        return Inertia::render('Image/TagAssign', [
            'imageId' => fn() => $imageId,
            'imageData' => $imageData,
            'tags' => $tags,
            'negativeTags' => $negativeTags,
            'imgServerBase' => fn() => config('illuststore.image_server_base_url'),
        ]);
    }

    public function assignTag(int $imageId, Request $request)
    {
        $q = $request->get('newTags');
        if (empty($q)) {
            return response(null, 400);
        }

        $imageData = DB::table('illusts')
            ->select([
                "id",
            ])
            ->where('id', '=', $imageId)
            ->limit(1)
            ->first();

        if ($imageData === null) {
            return response(null, 404);
        }

        $ids = SearchController::searchTagIdsFromTagStrings($q);
        if (count($ids) === 0) {
            return response(null, 400);
        }

        foreach($ids as $tagId) {
            $this->putTagProcess($imageId, $tagId);
        }

        return response()->redirectTo("/image/$imageId", 303);
    }

}
