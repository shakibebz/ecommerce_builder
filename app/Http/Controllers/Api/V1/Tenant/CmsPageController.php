<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Services\MagentoApiService;
use App\Services\Tenant\CmsPageService;
use Illuminate\Http\Request;

class CmsPageController extends Controller
{
    protected $cmsPageService;

    public function __construct(CmsPageService $cmsPageService)
    {
        $this->cmsPageService = $cmsPageService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $pages = $this->cmsPageService->getCmsPages();
            return response()->json(['success' => true, 'data' => $pages]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => 'required|string',
                'identifier' => 'required|string',
                'content' => 'required|string',
                'is_active' => 'required|boolean',
                'store_id' => 'required|int',
                'page_layout' => 'string|nullable',
                'meta_title' => 'string|nullable',
                'meta_keywords' => 'string|nullable',
                'meta_description' => 'string|nullable',
            ]);

            $page = $this->cmsPageService->createCmsPage($data);
            return response()->json(['success' => true, 'data' => $page], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $page = $this->cmsPageService->getCmsPageById($id);
            return response()->json(['success' => true, 'data' => $page]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $data = $request->validate([
                'id' => 'required|integer',
                'title' => 'required|string',
                'identifier' => 'required|string',
                'content' => 'required|string',
                'is_active' => 'required|boolean',
                'store_id' => 'required|integer',
                'page_layout' => 'string|nullable',
                'meta_title' => 'string|nullable',
                'meta_keywords' => 'string|nullable',
                'meta_description' => 'string|nullable',
            ]);

            $page = $this->cmsPageService->updateCmsPage($id, $data);
            return response()->json(['success' => true, 'data' => $page]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $result = $this->cmsPageService->deleteCmsPage($id);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
