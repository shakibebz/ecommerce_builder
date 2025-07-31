<?php

namespace App\Http\Controllers\Api\V1\Tenant;


use App\Http\Controllers\Controller;
use App\Models\Themes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ThemesController extends Controller
{
    public function index()
    {
        $themes = Themes::all();
        return response()->json($themes, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'config' => 'required|json',
            'layout' => 'required|json',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $store = \App\Models\Stores::where('owner_id', auth()->id())->first();
        \Log::debug('Store ID:', ['store_id' => $store->id]);
        if (!$store) {
            return response()->json(['error' => 'No store found for the authenticated tenant'], 404);
        }
        try {
            $theme = Themes::create([
                'title' => $request->title,
                'config' => $request->config,
                'layout' => $request->layout,
                'store_id' => $store->id,
            ]);

            return response()->json($theme, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create theme'], 500);
        }
    }

    public function show($id)
    {
        $theme = Themes::find($id);

        if (!$theme) {
            return response()->json(['error' => 'Theme not found'], 404);
        }

        return response()->json($theme, 200);
    }

    public function update(Request $request, $id)
    {
        $theme = Themes::find($id);

        if (!$theme) {
            return response()->json(['error' => 'Theme not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'config' => 'sometimes|json',
            'layout' => 'sometimes|json',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $theme->update($request->only(['title', 'config', 'layout']));
            return response()->json($theme, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update theme'], 500);
        }
    }

    public function destroy($id)
    {
        $theme = Themes::find($id);

        if (!$theme) {
            return response()->json(['error' => 'Theme not found'], 404);
        }

        try {
            $theme->delete();
            return response()->json(['message' => 'Theme deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete theme'], 500);
        }
    }
}
